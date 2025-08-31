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
     * Traitement du checkout invité - VERSION CORRIGÉE
     */
    public function process(Request $request)
    {
        \Log::info('=== DÉBUT PROCESS GUEST CHECKOUT ===', [
            'request_data' => $request->except(['password', 'password_confirmation']),
            'cart_content' => session()->get('cart', [])
        ]);
        
        try {
            // Validation conditionnelle selon le mode
            $rules = [
                'email' => 'required|email',
                'first_name' => 'required|string|max:255',
                'last_name' => 'required|string|max:255', 
                'phone' => 'required|string|min:10',
                'terms_accepted' => 'required'
            ];
            
            // Ajouter validation mot de passe seulement si création de compte
            if ($request->boolean('create_account', false)) {
                $rules['password'] = 'required|min:6|confirmed';
                \Log::info('=== VALIDATION AVEC MOT DE PASSE ===');
            } else {
                \Log::info('=== VALIDATION SANS MOT DE PASSE ===');
            }
            
            $validated = $request->validate($rules);
            \Log::info('=== VALIDATION PASSÉE ===');
            
            // Vérification du panier
            $cart = session()->get('cart', []);
            if (empty($cart)) {
                \Log::warning('=== PANIER VIDE ===');
                return redirect()->route('cart.show')->with('error', 'Votre panier est vide.');
            }
            \Log::info('=== PANIER VÉRIFIÉ ===', ['items' => count($cart)]);
            
            // Début transaction
            DB::beginTransaction();
            \Log::info('=== TRANSACTION DÉMARRÉE ===');
            
            // 1. Gérer l'utilisateur
            $user = $this->handleUser($request);
            \Log::info('=== UTILISATEUR GÉRÉ ===', ['user_id' => $user->id, 'is_guest' => $user->is_guest]);
            
            // 2. Créer les commandes
            $orders = $this->createOrdersFromCart($cart, $user, $request);
            \Log::info('=== COMMANDES CRÉÉES ===', ['count' => count($orders)]);
            
            // 3. Envoyer les confirmations
            foreach ($orders as $order) {
                \Log::info('=== ENVOI EMAIL ===', ['order_id' => $order->id]);
                $this->sendConfirmationEmail($order);
            }
            \Log::info('=== EMAILS ENVOYÉS ===');
            
            // 4. Nettoyer la session
            session()->forget(['cart', 'cart_timer']);
            \Log::info('=== SESSION NETTOYÉE ===');
            
            DB::commit();
            \Log::info('=== TRANSACTION COMMITÉE ===');
            
            // Redirection selon le type d'utilisateur
            if (auth()->check()) {
                \Log::info('=== REDIRECTION VERS ACHETEUR ORDERS ===');
                return redirect()->route('acheteur.orders')->with('success', 
                    'Commande confirmée ! Vos billets vous ont été envoyés par email.');
            }
            
            // Pour les invités, redirection vers confirmation
            $firstOrder = $orders[0];
            if ($firstOrder->guest_token) {
                \Log::info('=== REDIRECTION VERS GUEST CONFIRMATION ===', ['token' => $firstOrder->guest_token]);
                return redirect()->route('checkout.guest.confirmation', $firstOrder->guest_token)
                               ->with('success', 'Commande confirmée ! Consultez votre email pour les billets.');
            } else {
                \Log::info('=== REDIRECTION VERS HOME ===');
                return redirect()->route('home')->with('success', 
                    'Commande confirmée ! Consultez votre email pour les billets.');
            }
            
        } catch (\Throwable $e) {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }
            
            \Log::error('=== ERREUR DÉTAILLÉE ===', [
                'type' => get_class($e),
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return back()->with('error', 'Erreur: ' . $e->getMessage())->withInput();
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
                'name' => $user->first_name . ' ' . $user->last_name,
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
     * Gérer la création/récupération de l'utilisateur - VERSION CORRIGÉE
     */
    private function handleUser(Request $request)
    {
        \Log::info('=== DÉBUT HANDLE USER ===');
        
        $email = $request->email;
        $existingUser = User::where('email', $email)->first();
        $createAccount = $request->boolean('create_account', false);
        
        \Log::info('=== PARAMÈTRES ===', [
            'email' => $email, 
            'user_exists' => !is_null($existingUser),
            'create_account' => $createAccount
        ]);
        
        // Si utilisateur existe
        if ($existingUser) {
            \Log::info('=== UTILISATEUR EXISTANT ===', [
                'user_id' => $existingUser->id,
                'is_guest' => $existingUser->is_guest
            ]);
            
            // Si demande création de compte mais utilisateur normal existe
            if ($createAccount && !$existingUser->is_guest) {
                throw new \Exception('Un compte existe déjà avec cet email. Veuillez vous connecter.');
            }
            
            // Si utilisateur normal, l'utiliser
            if (!$existingUser->is_guest) {
                return $existingUser;
            }
            
            // Si création de compte sur un invité existant
            if ($createAccount && $existingUser->is_guest) {
                \Log::info('=== CONVERSION INVITÉ → COMPTE ===');
                $existingUser->update([
                    'name' => $request->first_name . ' ' . $request->last_name,
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
            
            // Mise à jour invité existant (mode invité)
            \Log::info('=== MISE À JOUR INVITÉ EXISTANT ===');
            $existingUser->update([
                'name' => $request->first_name . ' ' . $request->last_name,
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'phone' => $request->phone,
            ]);
            return $existingUser;
        }
        
        // Utilisateur n'existe pas - créer selon le mode
        if ($createAccount) {
            \Log::info('=== CRÉATION NOUVEAU COMPTE ===');
            $user = User::create([
                'name' => $request->first_name . ' ' . $request->last_name,
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
        } else {
            \Log::info('=== CRÉATION NOUVEL INVITÉ ===');
            return User::create([
                'name' => $request->first_name . ' ' . $request->last_name,
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'email' => $email,
                'phone' => $request->phone,
                'password' => Hash::make(Str::random(16)),
                'role' => 'acheteur',
                'is_guest' => true
            ]);
        }
    }

    /**
     * Créer les commandes depuis le panier - VERSION CORRIGÉE
     */
    private function createOrdersFromCart($cart, $user, $request)
    {
        \Log::info('=== DÉBUT CRÉATION COMMANDES ===');
        
        $orders = [];
        $eventGroups = [];

        // Grouper par événement
        foreach ($cart as $item) {
            $eventGroups[$item['event_id']][] = $item;
        }
        \Log::info('=== GROUPES D\'ÉVÉNEMENTS ===', ['count' => count($eventGroups)]);

        foreach ($eventGroups as $eventId => $items) {
            \Log::info('=== CRÉATION COMMANDE POUR ÉVÉNEMENT ===', ['event_id' => $eventId]);
            
            $eventTotal = array_sum(array_column($items, 'total_price'));
            
            $order = Order::create([
                'user_id' => $user->id,
                'event_id' => $eventId,
                'total_amount' => $eventTotal + 500, // + frais de service
                'payment_status' => 'paid',
                'payment_method' => 'manual',
                'order_number' => $this->generateOrderNumber(),
                'billing_email' => $request->email,
                'billing_phone' => $request->phone,
                'guest_token' => $user->is_guest ? Str::random(32) : null
            ]);
            
            \Log::info('=== COMMANDE CRÉÉE ===', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'guest_token' => $order->guest_token
            ]);

            // Créer les items et billets
            foreach ($items as $item) {
                $this->createOrderItemWithTickets($order, $item);
            }

            $orders[] = $order;
        }
        
        \Log::info('=== TOUTES COMMANDES CRÉÉES ===', ['total' => count($orders)]);
        return $orders;
    }

    /**
     * Créer un item de commande avec ses billets
     */
    private function createOrderItemWithTickets($order, $item)
    {
        \Log::info('=== CRÉATION ITEM + BILLETS ===', [
            'order_id' => $order->id,
            'ticket_type_id' => $item['ticket_type_id'],
            'quantity' => $item['quantity']
        ]);
        
        $ticketType = TicketType::find($item['ticket_type_id']);
        
        // Vérifier et réserver le stock
        if (!$ticketType) {
            throw new \Exception("Type de billet non trouvé: {$item['ticket_type_id']}");
        }
        
        if (method_exists($ticketType, 'reserveTickets')) {
            if (!$ticketType->reserveTickets($item['quantity'])) {
                throw new \Exception("Stock insuffisant pour {$item['ticket_name']}");
            }
        }

        // Créer l'item de commande
        $orderItem = OrderItem::create([
            'order_id' => $order->id,
            'ticket_type_id' => $ticketType->id,
            'quantity' => $item['quantity'],
            'unit_price' => $item['unit_price'],
            'total_price' => $item['total_price'],
        ]);
        
        \Log::info('=== ORDER ITEM CRÉÉ ===', ['order_item_id' => $orderItem->id]);

        // Générer les billets
        for ($i = 0; $i < $item['quantity']; $i++) {
            $ticket = Ticket::create([
                'ticket_type_id' => $ticketType->id,
                'ticket_code' => $this->generateTicketCode(),
                'status' => 'sold',
            ]);
            
            // Générer QR Code si la méthode existe
            if (method_exists($ticket, 'generateQRCode')) {
                $ticket->generateQRCode();
            }

            // Associer à la commande
            DB::table('order_tickets')->insert([
                'order_id' => $order->id,
                'ticket_id' => $ticket->id,
                'order_item_id' => $orderItem->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            
            \Log::info('=== BILLET CRÉÉ ===', ['ticket_id' => $ticket->id, 'code' => $ticket->ticket_code]);
        }

        // Mettre à jour le stock vendu
        $ticketType->increment('quantity_sold', $item['quantity']);
        \Log::info('=== STOCK VENDU MIS À JOUR ===', ['ticket_type_id' => $ticketType->id]);
    }

    /**
     * Envoyer email de confirmation
     */
    private function sendConfirmationEmail($order)
    {
        try {
            \Log::info('=== TENTATIVE ENVOI EMAIL ===', ['order_id' => $order->id]);
            
            if (class_exists('\App\Services\EmailService')) {
                app(\App\Services\EmailService::class)->sendPaymentConfirmation($order);
                \Log::info('=== EMAIL ENVOYÉ VIA SERVICE ===');
            } else {
                \Log::warning('=== SERVICE EMAIL NON TROUVÉ - SKIP ===');
            }
        } catch (\Exception $e) {
            \Log::error('=== ERREUR ENVOI EMAIL ===', [
                'order_id' => $order->id,
                'error' => $e->getMessage()
            ]);
            // Ne pas faire échouer la commande pour un problème d'email
        }
    }
    
    /**
     * Générer un numéro de commande unique
     */
    private function generateOrderNumber()
    {
        do {
            $number = 'ORD-' . strtoupper(Str::random(8));
        } while (Order::where('order_number', $number)->exists());
        
        return $number;
    }
    
    /**
     * Générer un code de billet unique
     */
    private function generateTicketCode()
    {
        do {
            $code = 'TKT-' . strtoupper(Str::random(10));
        } while (Ticket::where('ticket_code', $code)->exists());
        
        return $code;
    }
}