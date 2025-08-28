<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\TicketType;
use App\Models\Ticket;
use App\Models\Event;
use App\Models\Commission;
use App\Services\EmailService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class CheckoutController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Afficher la page de checkout
     */
    public function show()
    { 
        // Déterminer le type de checkout et récupérer le bon panier
        if (session()->has('direct_booking')) {
            $cart = session()->get('direct_booking');
            $isDirectBooking = true;
            $timeRemaining = session()->get('booking_timer') ? 
                now()->diffInMinutes(session()->get('booking_timer')) : null;
        } else {
            $cart = session()->get('cart', []);
            $isDirectBooking = false;
            $timeRemaining = session()->get('cart_timer') ? 
                now()->diffInMinutes(session()->get('cart_timer')) : null;
        }

        if (empty($cart)) {
            return redirect()->route('cart.show')->with('error', 'Votre panier est vide.');
        }

        // Vérifier la disponibilité de tous les billets
        foreach ($cart as $cartKey => $item) {
            $ticketType = TicketType::find($item['ticket_type_id']);
            if (!$ticketType || !$ticketType->canPurchaseQuantity($item['quantity'])) {
                return redirect()->route('cart.show')->with('error', 
                    "Le billet '{$item['ticket_name']}' n'est plus disponible en quantité demandée.");
            }
        }

        $cartTotal = array_sum(array_column($cart, 'total_price'));
        $serviceFee = 500; // Frais de service fixes
        $finalTotal = $cartTotal + $serviceFee;

        return view('checkout.show', compact(
            'cart', 'cartTotal', 'serviceFee', 'finalTotal', 
            'isDirectBooking', 'timeRemaining'
        ));
    }

    /**
     * Réservation directe (bypass du panier)
     */
    public function direct(Request $request)
    {
        $request->validate([
            'event_id' => 'required|exists:events,id',
            'tickets' => 'required|array',
            'tickets.*' => 'integer|min:0|max:20'
        ]);

        $event = Event::findOrFail($request->event_id);
        $selectedTickets = [];
        $cartTotal = 0;

        // Traiter les billets sélectionnés
        foreach ($request->tickets as $ticketTypeId => $quantity) {
            if ($quantity <= 0) continue;

            $ticketType = TicketType::findOrFail($ticketTypeId);
            
            // Vérifications
            if ($ticketType->event_id != $request->event_id) {
                return redirect()->back()->with('error', 'Type de billet invalide.');
            }

            if (!$ticketType->canPurchaseQuantity($quantity)) {
                return redirect()->back()->with('error', "Quantité non disponible pour {$ticketType->name}");
            }

            // Ajouter à la sélection
            $selectedTickets[] = [
                'ticket_type_id' => $ticketType->id,
                'event_id' => $event->id,
                'event_title' => $event->title,
                'event_date' => $event->formatted_event_date,
                'event_venue' => $event->venue,
                'event_image' => $event->image,
                'ticket_name' => $ticketType->name,
                'unit_price' => $ticketType->price,
                'quantity' => $quantity,
                'total_price' => $ticketType->price * $quantity,
                'max_per_order' => $ticketType->max_per_order
            ];

            $cartTotal += $ticketType->price * $quantity;
        }

        if (empty($selectedTickets)) {
            return redirect()->back()->with('error', 'Aucun billet sélectionné.');
        }

        // Stocker temporairement en session pour le checkout
        session()->put('direct_booking', $selectedTickets);
        session()->put('booking_timer', now()->addMinutes(60)); // 1 heure de réservation

        $serviceFee = 500;
        $finalTotal = $cartTotal + $serviceFee;

        return view('checkout.direct', compact('selectedTickets', 'cartTotal', 'serviceFee', 'finalTotal', 'event'));
    }

    /**
     * Traiter la commande (version complète avec emails)
     */
    public function process(Request $request)
    {
        $request->validate([
            'billing_email' => 'required|email',
            'billing_phone' => 'required|string|max:20',
            'terms_accepted' => 'required|accepted'
        ]);

        // ===== DÉTERMINER LE TYPE DE CHECKOUT =====
        $isDirectBooking = session()->has('direct_booking');
        $cart = $isDirectBooking ? session()->get('direct_booking', []) : session()->get('cart', []);
        
        if (empty($cart)) {
            return redirect()->route('cart.show')->with('error', 'Votre panier est vide.');
        }

        // ===== VÉRIFIER LES TIMERS =====
        if ($isDirectBooking) {
            $bookingTimer = session()->get('booking_timer');
            if (!$bookingTimer || now()->gt($bookingTimer)) {
                session()->forget(['direct_booking', 'booking_timer']);
                return redirect()->route('home')->with('error', 'Votre réservation a expiré. Veuillez recommencer.');
            }
        } else {
            $cartTimer = session()->get('cart_timer');
            if ($cartTimer && now()->gt($cartTimer)) {
                session()->forget(['cart', 'cart_timer']);
                return redirect()->route('cart.show')->with('error', 'Votre panier a expiré. Veuillez recommencer.');
            }
        }

        try {
            DB::beginTransaction();

            // ===== GROUPER LES BILLETS PAR ÉVÉNEMENT =====
            $eventGroups = [];
            foreach ($cart as $item) {
                $eventId = $item['event_id'];
                if (!isset($eventGroups[$eventId])) {
                    $eventGroups[$eventId] = [];
                }
                $eventGroups[$eventId][] = $item;
            }

            $orders = [];

            // ===== CRÉER UNE COMMANDE PAR ÉVÉNEMENT =====
            foreach ($eventGroups as $eventId => $items) {
                $eventTotal = array_sum(array_column($items, 'total_price'));
                
                // Créer la commande
                $order = Order::create([
                    'user_id' => Auth::id(),
                    'event_id' => $eventId,
                    'total_amount' => $eventTotal,
                    'payment_status' => 'pending',
                    'payment_method' => 'manual',
                    'order_number' => Order::generateOrderNumber(),
                    'billing_email' => $request->billing_email,
                    'billing_phone' => $request->billing_phone,
                    'booking_type' => $isDirectBooking ? 'direct_reservation' : 'cart_order'
                ]);

                Log::info('Commande créée', [
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                    'event_id' => $eventId,
                    'user_id' => Auth::id()
                ]);

                // ===== CRÉER LES ITEMS ET BILLETS =====
                foreach ($items as $item) {
                    $this->createOrderItemWithTickets($order, $item);
                }

                // ===== MARQUER COMME PAYÉ =====
                $order->markAsPaid('MANUAL-' . time());
                
                // ===== CRÉER LA COMMISSION =====
                $this->createCommissionForOrder($order);
                
                // 🔥 CRUCIAL : ENVOYER TOUS LES EMAILS
                $this->sendOrderEmails($order);
                
                $orders[] = $order;
            }

            // ===== NETTOYER LES SESSIONS =====
            if ($isDirectBooking) {
                session()->forget(['direct_booking', 'booking_timer']);
            } else {
                session()->forget(['cart', 'cart_timer']);
            }

            DB::commit();

            $successMessage = $isDirectBooking 
                ? 'Réservation confirmée avec succès ! Vos billets vous ont été envoyés par email.'
                : 'Commande validée avec succès ! Vos billets vous ont été envoyés par email.';

            return redirect()->route('acheteur.dashboard')->with('success', $successMessage);

        } catch (\Exception $e) {
            DB::rollback();
            
            Log::error('Erreur checkout process', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            $errorMessage = $isDirectBooking 
                ? 'Erreur lors de la confirmation de votre réservation : ' . $e->getMessage()
                : 'Erreur lors de la validation de votre commande : ' . $e->getMessage();
            
            return redirect()->back()
                ->with('error', $errorMessage)
                ->withInput();
        }
    }

    /**
     * Confirmation de commande
     */
    public function confirmation(Order $order)
    {
        // Vérifier que l'utilisateur peut voir cette commande
        if (!Auth::user()->isAdmin() && $order->user_id !== Auth::id()) {
            abort(403);
        }

        $order->load(['orderItems.ticketType', 'event', 'tickets']);

        return view('checkout.confirmation', compact('order'));
    }

    /**
     * 🔥 NOUVELLE MÉTHODE : Créer un item de commande avec ses billets
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
            throw new \Exception("Impossible de réserver {$item['quantity']} billets pour '{$item['ticket_name']}'.");
        }

        // Créer l'item de commande
        $orderItem = OrderItem::create([
            'order_id' => $order->id,
            'ticket_type_id' => $ticketType->id,
            'quantity' => $item['quantity'],
            'unit_price' => $item['unit_price'],
            'total_price' => $item['total_price'],
        ]);

        // Générer les billets individuels
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

        Log::info('Billets créés pour commande', [
            'order_id' => $order->id,
            'ticket_type' => $ticketType->name,
            'quantity' => $item['quantity'],
            'tickets_created' => count($tickets)
        ]);

        return $orderItem;
    }

    /**
     * 🔥 NOUVELLE MÉTHODE CRUCIALE : Envoyer tous les emails
     */
    private function sendOrderEmails($order)
    {
        try {
            $emailService = app(EmailService::class);
            
            // Envoyer TOUS les emails (client, promoteur, admin)
            $emailService->sendAllOrderEmails($order);
            
            Log::info("Tous les emails envoyés avec succès", [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'client_email' => $order->user->email,
                'promoteur_email' => $order->event->promoteur->email ?? 'N/A'
            ]);
            
        } catch (\Exception $e) {
            Log::error("ERREUR CRITIQUE - Échec envoi emails", [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // Ne pas faire échouer toute la commande à cause d'un email
            // Mais alerter dans les logs
        }
    }

    /**
     * Créer une commission pour une commande
     */
    private function createCommissionForOrder($order)
    {
        try {
            $commissionData = $order->calculateCommission();
            
            Commission::create([
                'order_id' => $order->id,
                'promoter_id' => $order->event->promoter_id,
                'gross_amount' => $commissionData['gross_amount'],
                'commission_rate' => $commissionData['commission_rate'],
                'commission_amount' => $commissionData['commission_amount'],
                'net_amount' => $commissionData['net_amount'],
                'platform_fee' => $commissionData['platform_fee'],
                'status' => 'pending'
            ]);
            
            Log::info('Commission créée', [
                'order_id' => $order->id,
                'promoter_id' => $order->event->promoter_id,
                'commission_amount' => $commissionData['commission_amount']
            ]);
            
            return true;
            
        } catch (\Exception $e) {
            Log::error('Erreur création commission', [
                'order_id' => $order->id,
                'event_id' => $order->event_id,
                'promoter_id' => $order->event->promoter_id ?? 'NULL',
                'error' => $e->getMessage()
            ]);
            
            throw $e;
        }
    }

    /**
     * 🔥 MÉTHODE MANQUANTE : Libérer le stock en cas d'erreur
     */
    private function releaseCartStock($cart)
    {
        foreach ($cart as $item) {
            try {
                $ticketType = TicketType::find($item['ticket_type_id']);
                if ($ticketType && method_exists($ticketType, 'releaseTickets')) {
                    $ticketType->releaseTickets($item['quantity']);
                }
            } catch (\Exception $e) {
                Log::error('Erreur libération stock', [
                    'ticket_type_id' => $item['ticket_type_id'],
                    'quantity' => $item['quantity'],
                    'error' => $e->getMessage()
                ]);
            }
        }
    }
}