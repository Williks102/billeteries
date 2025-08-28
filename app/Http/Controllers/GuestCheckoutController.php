<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use App\Models\TicketType;
use App\Models\Ticket;
use App\Models\Commission;
use App\Services\EmailService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
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

            // 3. 🔥 CORRECTION : Envoyer TOUS les emails (y compris promoteur)
            foreach ($orders as $order) {
                $this->sendAllOrderEmails($order);
            }

            // 4. Nettoyer la session
            session()->forget(['cart', 'cart_timer']);
            
            DB::commit();

            Log::info('Checkout invité réussi', [
                'user_id' => $user->id,
                'orders_count' => count($orders),
                'is_guest' => $user->is_guest,
                'create_account' => $request->create_account ?? false
            ]);

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
            Log::error('Erreur checkout invité', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->only(['email', 'first_name', 'last_name'])
            ]);
            
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
     * Gérer la création ou récupération de l'utilisateur
     */
    private function handleUser($request)
    {
        // Chercher un utilisateur existant avec cet email
        $existingUser = User::where('email', $request->email)->first();

        if ($existingUser) {
            // Si l'utilisateur existe et veut créer un compte
            if ($request->create_account && $existingUser->is_guest) {
                // Convertir l'invité en utilisateur normal
                $existingUser->update([
                    'name' => $request->first_name . ' ' . $request->last_name,
                    'first_name' => $request->first_name,
                    'last_name' => $request->last_name,
                    'phone' => $request->phone,
                    'password' => Hash::make($request->password),
                    'is_guest' => false,
                    'guest_converted_at' => now()
                ]);
                
                // Connecter l'utilisateur
                auth()->login($existingUser);
                
                Log::info('Invité converti en utilisateur', [
                    'user_id' => $existingUser->id,
                    'email' => $existingUser->email
                ]);
                
                return $existingUser;
            }
            
            // Sinon, utiliser l'utilisateur existant
            return $existingUser;
        }

        // Créer un nouvel utilisateur
        $newUser = User::create([
            'name' => $request->first_name . ' ' . $request->last_name,
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'email' => $request->email,
            'phone' => $request->phone,
            'password' => $request->create_account ? Hash::make($request->password) : null,
            'is_guest' => !$request->create_account,
            'email_verified_at' => now()
        ]);

        Log::info('Nouvel utilisateur créé', [
            'user_id' => $newUser->id,
            'email' => $newUser->email,
            'is_guest' => $newUser->is_guest,
            'create_account' => $request->create_account ?? false
        ]);

        // Si création de compte, connecter l'utilisateur
        if ($request->create_account) {
            auth()->login($newUser);
        }

        return $newUser;
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
                'payment_status' => 'paid', // Directement payé pour les invités
                'payment_method' => 'manual',
                'order_number' => Order::generateOrderNumber(),
                'billing_email' => $request->email,
                'billing_phone' => $request->phone,
                'guest_token' => $user->is_guest ? Str::random(32) : null
            ]);

            Log::info('Commande invité créée', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'user_id' => $user->id,
                'event_id' => $eventId,
                'total_amount' => $order->total_amount
            ]);

            // Créer les items et billets
            foreach ($items as $item) {
                $this->createOrderItemWithTickets($order, $item);
            }

            // 🔥 NOUVEAU : Créer la commission
            $this->createCommissionForOrder($order);

            $orders[] = $order;
        }

        return $orders;
    }

    /**
     * Créer un item de commande avec ses billets
     */
    private function createOrderItemWithTickets($order, $item)
    {
        $ticketType = TicketType::findOrFail($item['ticket_type_id']);
        
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
        $tickets = [];
        for ($i = 0; $i < $item['quantity']; $i++) {
            $ticket = Ticket::create([
                'ticket_type_id' => $ticketType->id,
                'ticket_code' => Ticket::generateTicketCode(),
                'status' => 'sold',
            ]);

            $ticket->generateQRCode();
            $tickets[] = $ticket;

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

        Log::info('Billets invité générés', [
            'order_item_id' => $orderItem->id,
            'ticket_type' => $ticketType->name,
            'quantity' => $item['quantity'],
            'tickets_generated' => count($tickets)
        ]);

        return $orderItem;
    }
}