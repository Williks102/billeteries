<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use App\Models\TicketType;
use App\Models\Ticket;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class GuestCheckoutController extends Controller
{
    /**
     * Page de checkout invité
     */
    public function show()
    {
        $cart = session()->get('cart', []);
        
        if (empty($cart)) {
            return redirect()->route('cart.show')->with('error', 'Votre panier est vide.');
        }

        // Vérifier la disponibilité des billets
        foreach ($cart as $cartKey => $item) {
            $ticketType = TicketType::find($item['ticket_type_id']);
            if (!$ticketType || !$ticketType->canPurchaseQuantity($item['quantity'])) {
                return redirect()->route('cart.show')->with('error', 
                    "Le billet '{$item['ticket_name']}' n'est plus disponible.");
            }
        }

        $cartTotal = array_sum(array_column($cart, 'total_price'));
        $serviceFee = 500; // Frais de service
        $finalTotal = $cartTotal + $serviceFee;

        return view('checkout.guest', compact('cart', 'cartTotal', 'serviceFee', 'finalTotal'));
    }

    /**
     * Traitement du checkout invité
     */
    public function process(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'phone' => 'required|string|min:10',
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'create_account' => 'boolean',
            'password' => 'required_if:create_account,true|min:6|confirmed',
            'terms_accepted' => 'accepted'
        ]);

        $cart = session()->get('cart', []);
        if (empty($cart)) {
            return redirect()->route('cart.show')->with('error', 'Votre panier est vide.');
        }

        try {
            DB::beginTransaction();

            // 1. Gérer l'utilisateur
            $user = $this->handleUser($request);

            // 2. Créer les commandes
            $orders = $this->createOrdersFromCart($cart, $user, $request);

            // 3. Envoyer les confirmations
            foreach ($orders as $order) {
                $this->sendConfirmationEmail($order);
            }

            // 4. Nettoyer la session
            session()->forget(['cart', 'cart_timer']);
            
            DB::commit();

            // Redirection selon le type d'utilisateur
            if (auth()->check()) {
                return redirect()->route('acheteur.orders')->with('success', 
                    'Commande confirmée ! Vos billets vous ont été envoyés par email.');
            }

            // Pour les invités, rediriger vers la confirmation
            $firstOrder = $orders[0];
            return redirect()->route('checkout.guest.confirmation', $firstOrder->guest_token)
                           ->with('success', 'Commande confirmée ! Consultez votre email pour les billets.');

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Erreur checkout invité: ' . $e->getMessage());
            
            return back()->withErrors(['error' => 'Erreur lors du traitement de la commande.'])
                        ->withInput();
        }
    }

    /**
     * Page de confirmation pour invités
     */
    public function confirmation($token)
    {
        $order = Order::where('guest_token', $token)
                     ->with(['user', 'event', 'orderItems.ticketType', 'tickets'])
                     ->firstOrFail();
        
        return view('checkout.guest-confirmation', compact('order'));
    }

    /**
     * Permettre à un invité de créer un compte après achat
     */
    public function createAccountAfterPurchase(Request $request, $token)
    {
        $request->validate([
            'password' => 'required|min:6|confirmed'
        ]);

        $order = Order::where('guest_token', $token)->firstOrFail();
        $user = $order->user;

        if (!$user->is_guest) {
            return redirect()->route('home')->with('error', 'Action non autorisée.');
        }

        try {
            DB::beginTransaction();

            // Convertir l'invité en utilisateur normal
            $user->update([
                'password' => Hash::make($request->password),
                'is_guest' => false,
                'guest_converted_at' => now()
            ]);

            // Supprimer les tokens invité de toutes ses commandes
            Order::where('user_id', $user->id)->update(['guest_token' => null]);

            // Connecter l'utilisateur
            auth()->login($user);

            DB::commit();

            return redirect()->route('acheteur.dashboard')->with('success', 
                'Votre compte a été créé ! Vous pouvez maintenant gérer toutes vos commandes.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Erreur lors de la création du compte.']);
        }
    }

    /**
     * Gérer la création/récupération de l'utilisateur
     */
    private function handleUser(Request $request)
    {
        $email = $request->email;
        $existingUser = User::where('email', $email)->first();

        // Si utilisateur existe et veut créer un compte -> erreur
        if ($existingUser && $request->create_account && !$existingUser->is_guest) {
            throw new \Exception('Un compte existe déjà avec cet email. Veuillez vous connecter.');
        }

        // Si utilisateur normal existe, l'utiliser
        if ($existingUser && !$existingUser->is_guest) {
            return $existingUser;
        }

        // Si demande de création de compte
        if ($request->create_account) {
            // Si c'est un ancien invité, le convertir
            if ($existingUser && $existingUser->is_guest) {
                $existingUser->update([
                    'first_name' => $request->first_name,
                    'last_name' => $request->last_name,
                    'phone' => $request->phone,
                    'password' => Hash::make($request->password),
                    'is_guest' => false,
                    'guest_converted_at' => now()
                ]);
                auth()->login($existingUser);
                return $existingUser;
            }

            // Créer nouveau compte normal
            $user = User::create([
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'email' => $email,
                'phone' => $request->phone,
                'password' => Hash::make($request->password),
                'role' => 'acheteur',
                'email_verified_at' => now()
            ]);

            auth()->login($user);
            return $user;
        }

        // Créer/mettre à jour utilisateur invité
        if ($existingUser && $existingUser->is_guest) {
            $existingUser->update([
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'phone' => $request->phone,
            ]);
            return $existingUser;
        }

        // Créer nouvel invité
        return User::create([
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'email' => $email,
            'phone' => $request->phone,
            'password' => Hash::make(Str::random(16)),
            'role' => 'acheteur',
            'is_guest' => true,
            'email_verified_at' => now()
        ]);
    }

    /**
     * Créer les commandes depuis le panier
     */
    private function createOrdersFromCart($cart, $user, $request)
    {
        $orders = [];
        $eventGroups = [];

        // Grouper par événement
        foreach ($cart as $item) {
            $eventGroups[$item['event_id']][] = $item;
        }

        foreach ($eventGroups as $eventId => $items) {
            $eventTotal = array_sum(array_column($items, 'total_price'));
            
            $order = Order::create([
                'user_id' => $user->id,
                'event_id' => $eventId,
                'total_amount' => $eventTotal + 500, // + frais de service
                'payment_status' => 'paid',
                'payment_method' => 'manual',
                'order_number' => Order::generateOrderNumber(),
                'billing_email' => $request->email,
                'billing_phone' => $request->phone,
                'guest_token' => $user->is_guest ? Str::random(32) : null
            ]);

            // Créer les items et billets
            foreach ($items as $item) {
                $this->createOrderItemWithTickets($order, $item);
            }

            $orders[] = $order;
        }

        return $orders;
    }

    /**
     * Créer un item de commande avec ses billets
     */
    private function createOrderItemWithTickets($order, $item)
    {
        $ticketType = TicketType::find($item['ticket_type_id']);
        
        // Réserver le stock
        if (!$ticketType->reserveTickets($item['quantity'])) {
            throw new \Exception("Stock insuffisant pour {$item['ticket_name']}");
        }

        // Créer l'item de commande
        $orderItem = OrderItem::create([
            'order_id' => $order->id,
            'ticket_type_id' => $ticketType->id,
            'quantity' => $item['quantity'],
            'unit_price' => $item['unit_price'],
            'total_price' => $item['total_price'],
        ]);

        // Générer les billets
        for ($i = 0; $i < $item['quantity']; $i++) {
            $ticket = Ticket::create([
                'ticket_type_id' => $ticketType->id,
                'ticket_code' => Ticket::generateTicketCode(),
                'status' => 'sold',
            ]);

            $ticket->generateQRCode();

            // Associer à la commande
            DB::table('order_tickets')->insert([
                'order_id' => $order->id,
                'ticket_id' => $ticket->id,
                'order_item_id' => $orderItem->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Mettre à jour le stock vendu
        $ticketType->increment('quantity_sold', $item['quantity']);
    }

    /**
     * Envoyer email de confirmation
     */
    private function sendConfirmationEmail($order)
    {
        try {
            if (class_exists('\App\Services\EmailService')) {
                app(\App\Services\EmailService::class)->sendPaymentConfirmation($order);
            }
        } catch (\Exception $e) {
            \Log::error('Erreur envoi email: ' . $e->getMessage());
        }
    }
}