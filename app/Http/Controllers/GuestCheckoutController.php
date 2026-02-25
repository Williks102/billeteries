<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\TicketType;
use App\Models\Ticket;
use App\Models\User;
use App\Models\Commission;
use App\Services\PaiementProService;
use App\Services\EmailService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class GuestCheckoutController extends Controller
{
    /**
     * Page de checkout invité (méthode show inchangée)
     */
    public function show()
    {
        $cart = session()->get('cart', []);
        
        if (empty($cart)) {
            return redirect()->route('cart.show')->with('error', 'Votre panier est vide.');
        }

        // Vérifications de disponibilité...
        
        $cartTotal = array_sum(array_column($cart, 'total_price'));
        $serviceFee = $cartTotal > 0 ? 500 : 0;
        $finalTotal = $cartTotal + $serviceFee;

        return view('checkout.guest', compact('cart', 'cartTotal', 'serviceFee', 'finalTotal'));
    }

    /**
     * Traitement du checkout invité INTÉGRÉ avec PaiementPro (VERSION ADAPTÉE À VOTRE LOGIQUE)
     */
    public function process(Request $request)
    {
        Log::info('Début checkout invité avec PaiementPro', [
            'create_account' => $request->boolean('create_account'),
            'payment_method' => $request->payment_method,
            'channel' => $request->channel
        ]);

        try {
            // Validation conditionnelle
            $rules = [
                'email' => 'required|email',
                'first_name' => 'required|string|max:255',
                'last_name' => 'required|string|max:255', 
                'phone' => 'required|string|min:10',
                'terms_accepted' => 'required',
                'payment_method' => 'required|in:paiementpro,bank_transfer',
                'channel' => 'required_if:payment_method,paiementpro|in:CARD,MOMO,OMCIV2,FLOOZ,PAYPAL,WAVECI'
            ];
            
            // Mot de passe requis seulement si création de compte
            if ($request->boolean('create_account')) {
                $rules['password'] = 'required|min:6|confirmed';
            }
            
            $validated = $request->validate($rules);
            
            // Vérification du panier
            $cart = session()->get('cart', []);
            if (empty($cart)) {
                return redirect()->route('cart.show')->with('error', 'Votre panier est vide.');
            }

            DB::beginTransaction();

            // 1. Gérer l'utilisateur
            $user = $this->handleUser($request);
            
            // 2. Créer les commandes (VOTRE LOGIQUE ADAPTÉE)
            $orders = $this->createOrdersFromCart($cart, $user, $request);
            
            DB::commit();

            // 3. Traitement selon le moyen de paiement
            if ($request->payment_method === 'paiementpro') {
                return $this->processPaiementPro($orders[0], $request->channel, $user->is_guest);
            }
            
            if ($request->payment_method === 'bank_transfer') {
                return $this->processBankTransfer($orders, $user->is_guest);
            }

        } catch (\Exception $e) {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }
            
            Log::error('Erreur checkout invité avec PaiementPro', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return back()->with('error', 'Erreur: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * 🆕 NOUVELLE : Traitement PaiementPro pour invités
     */
    private function processPaiementPro(Order $order, $channel, $isGuest = true)
    {
        try {
            $paiementProService = app(\App\Services\PaiementProService::class);
            
            // Marquer en traitement
            $order->update(['payment_status' => 'processing']);

            // Initialiser PaiementPro
            $result = $paiementProService->initTransaction($order, [
                'channel' => $channel
            ]);

            if ($result['success']) {
                // Nettoyer la session après succès
                session()->forget(['cart', 'cart_timer']);

                Log::info('Redirection PaiementPro invité réussie', [
                    'order_id' => $order->id,
                    'session_id' => $result['sessionId'],
                    'is_guest' => $isGuest
                ]);

                // Redirection vers PaiementPro
                return redirect($result['redirectUrl']);
            } else {
                // Remettre en pending
                $order->update(['payment_status' => 'pending']);
                throw new \Exception('Erreur PaiementPro : ' . $result['error']);
            }

        } catch (\Exception $e) {
            Log::error('Erreur redirection PaiementPro invité', [
                'order_id' => $order->id,
                'error' => $e->getMessage()
            ]);

            $order->update(['payment_status' => 'pending']);
            throw $e;
        }
    }

    /**
     * 🆕 NOUVELLE : Finalisation virement bancaire pour invités
     */
    private function processBankTransfer(array $orders, $isGuest = true)
    {
        try {
            foreach ($orders as $order) {
                // Marquer comme en attente de virement
                $order->update(['payment_status' => 'pending_transfer']);
                
                // Envoyer email avec instructions
                $this->sendBankTransferInstructions($order);
            }

            // Nettoyer la session
            session()->forget(['cart', 'cart_timer']);

            $message = count($orders) > 1 
                ? 'Commandes créées ! Instructions de virement envoyées par email.'
                : 'Commande créée ! Instructions de virement envoyées par email.';

            // Redirection selon le type d'utilisateur
            if (auth()->check()) {
                return redirect()->route('acheteur.orders')->with('success', $message);
            } else {
                // Pour les invités
                $firstOrder = $orders[0];
                if ($firstOrder->guest_token) {
                    return redirect()->route('checkout.guest.confirmation', $firstOrder->guest_token)
                                   ->with('success', $message);
                } else {
                    return redirect()->route('home')->with('success', $message);
                }
            }

        } catch (\Exception $e) {
            Log::error('Erreur virement bancaire invité', [
                'orders' => array_column($orders, 'id'),
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Créer les commandes depuis le panier (ADAPTÉE À VOTRE LOGIQUE)
     */
    private function createOrdersFromCart($cart, $user, $request)
    {
        Log::info('Début création commandes invité');
        
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
                'payment_status' => 'pending', // ⚠️ CHANGEMENT: pending pour PaiementPro
                'payment_method' => $request->payment_method,
                'order_number' => Order::generateOrderNumber(),
                'billing_email' => $request->email,
                'billing_phone' => $request->phone,
                'guest_token' => $user->is_guest ? Str::random(32) : null
            ]);

            // ✅ UTILISER VOTRE MÉTHODE EXISTANTE (adaptée)
            foreach ($items as $item) {
                $this->createOrderItemWithTickets($order, $item);
            }

            // Créer commission
            $this->createCommissionForOrder($order);
            
            $orders[] = $order;

            Log::info('Commande invité créée', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'total_amount' => $order->total_amount
            ]);
        }

        return $orders;
    }

    /**
     * ✅ VOTRE LOGIQUE EXACTE adaptée pour les invités
     */
    private function createOrderItemWithTickets($order, $item)
    {
        $ticketType = TicketType::findOrFail($item['ticket_type_id']);
        
        // Vérification finale de disponibilité
        if (!$ticketType->canPurchaseQuantity($item['quantity'])) {
            throw new \Exception("Le billet '{$item['ticket_name']}' n'est plus disponible.");
        }

        // Réserver le stock
        if (!$ticketType->reserveTickets($item['quantity'])) {
            throw new \Exception("Impossible de réserver {$item['quantity']} billets.");
        }

        // Créer l'item de commande
        $orderItem = OrderItem::create([
            'order_id' => $order->id,
            'ticket_type_id' => $ticketType->id,
            'quantity' => $item['quantity'],
            'unit_price' => $item['unit_price'],
            'total_price' => $item['total_price']
        ]);

        // ✅ GÉNÉRER LES BILLETS (VOTRE LOGIQUE EXACTE)
        $tickets = [];
        for ($i = 0; $i < $item['quantity']; $i++) {
            $ticket = Ticket::create([
                'ticket_type_id' => $ticketType->id,
                'ticket_code' => Ticket::generateTicketCode(),
                'status' => 'reserved', // ⚠️ CHANGEMENT: reserved jusqu'au paiement
                //'holder_name' => $order->user->name,
                'holder_email' => $order->billing_email,
            ]);

            // Générer QR Code si la méthode existe
            if (method_exists($ticket, 'generateQRCode')) {
                $ticket->generateQRCode();
            }

            $tickets[] = $ticket;

            // ✅ VOTRE LOGIQUE PIVOT (inchangée)
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

        Log::info('Billets invité créés', [
            'order_id' => $order->id,
            'quantity' => $item['quantity'],
            'tickets_created' => count($tickets)
        ]);

        return $orderItem;
    }

    /**
     * ✅ VOTRE LOGIQUE DE COMMISSION (inchangée)
     */
    private function createCommissionForOrder($order)
    {
        // Utiliser le système de calcul de commission existant
        $commissionData = $order->calculateCommission();
        
        Commission::create([
            'order_id' => $order->id,
            'promoter_id' => $order->event->promoter_id,
            'gross_amount' => $commissionData['gross_amount'],
            'commission_rate' => $commissionData['commission_rate'],
            'commission_amount' => $commissionData['commission_amount'],
            'net_amount' => $commissionData['net_amount'],
            'platform_fee' => $commissionData['platform_fee'] ?? 0,
            'payment_processor_fee' => $commissionData['payment_processor_fee'] ?? 0,
            'status' => 'pending'
        ]);

        Log::info('Commission invité créée', [
            'order_id' => $order->id,
            'commission_amount' => $commissionData['commission_amount']
        ]);
    }

    /**
     * 🆕 NOUVELLE : Instructions virement bancaire invité
     */
    private function sendBankTransferInstructions($order)
    {
        try {
            $emailService = app(EmailService::class);
            $emailService->sendBankTransferInstructions($order);
            
            Log::info('Instructions virement invité envoyées', [
                'order_id' => $order->id,
                'email' => $order->billing_email
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur envoi instructions virement invité', [
                'order_id' => $order->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    // ===== VOS MÉTHODES EXISTANTES (inchangées) =====

    /**
     * Gérer la création/récupération de l'utilisateur (VOTRE LOGIQUE)
     */
    private function handleUser(Request $request)
    {
        Log::info('Début handle user invité', [
            'email' => $request->email,
            'create_account' => $request->boolean('create_account')
        ]);
        
        $email = $request->email;
        $existingUser = User::where('email', $email)->first();
        $createAccount = $request->boolean('create_account');
        
        // Si utilisateur existe
        if ($existingUser) {
            Log::info('Utilisateur existant trouvé', [
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
                Log::info('Conversion invité vers compte');
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
            
            // Mise à jour invité existant
            Log::info('Mise à jour invité existant');
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
            Log::info('Création nouveau compte');
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
            Log::info('Création nouvel invité');
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
     * Page de confirmation pour invités (méthode existante)
     */
    public function confirmation($token)
    {
        $order = Order::where('guest_token', $token)
                     ->with(['user', 'event', 'orderItems.ticketType', 'tickets'])
                     ->firstOrFail();
        
        return view('checkout.guest-confirmation', compact('order'));
    }

    /**
     * Permettre à un invité de créer un compte après achat (méthode existante)
     */
    public function createAccountAfterPurchase(Request $request, $token)
    {
        // Votre méthode existante reste identique
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

            // Supprimer les tokens invité
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
}