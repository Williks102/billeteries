<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\TicketType;
use App\Models\Ticket;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Models\Commission;

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


        $cart = session()->get('cart', []);
        
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
     * Traiter la commande (sans paiement pour l'instant)
     */
    /**
 * Traiter la commande (modifié pour le système unifié)
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
        // Vérifier le timer de réservation directe (1 heure)
        $bookingTimer = session()->get('booking_timer');
        if (!$bookingTimer || now()->gt($bookingTimer)) {
            session()->forget(['direct_booking', 'booking_timer']);
            return redirect()->route('home')->with('error', 'Votre réservation a expiré. Veuillez recommencer.');
        }
        
        \Log::info('Traitement réservation directe', [
            'user_id' => auth()->id(),
            'time_remaining' => now()->diffInMinutes($bookingTimer),
            'items_count' => count($cart)
        ]);
    } else {
        // Vérifier le timer du panier (15 minutes)
        $cartTimer = session()->get('cart_timer');
        if ($cartTimer && now()->gt($cartTimer)) {
            session()->forget(['cart', 'cart_timer']);
            return redirect()->route('cart.show')->with('error', 'Votre panier a expiré. Veuillez recommencer.');
        }
        
        \Log::info('Traitement commande panier', [
            'user_id' => auth()->id(),
            'items_count' => count($cart)
        ]);
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
            // Calculer le total pour cet événement
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
                // Ajouter un flag pour identifier le type de commande
                'booking_type' => $isDirectBooking ? 'direct_reservation' : 'cart_order'
            ]);

            \Log::info('Commande créée', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'booking_type' => $order->booking_type ?? 'cart_order',
                'event_id' => $eventId,
                'total_amount' => $eventTotal
            ]);

            // ===== CRÉER LES ITEMS ET BILLETS =====
            foreach ($items as $item) {
                $ticketType = TicketType::findOrFail($item['ticket_type_id']);
                
                // ===== VÉRIFICATION FINALE DE DISPONIBILITÉ =====
                if (!$ticketType->canPurchaseQuantity($item['quantity'])) {
                    throw new \Exception("Le billet '{$item['ticket_name']}' n'est plus disponible en quantité demandée.");
                }

                // ===== RÉSERVER LE STOCK IMMÉDIATEMENT =====
                $stockReserved = $ticketType->reserveTickets($item['quantity']);
                if (!$stockReserved) {
                    throw new \Exception("Impossible de réserver {$item['quantity']} billets pour '{$item['ticket_name']}'.");
                }

                \Log::info('Stock réservé', [
                    'ticket_type_id' => $ticketType->id,
                    'quantity' => $item['quantity'],
                    'remaining_stock' => $ticketType->quantity_available - $ticketType->quantity_sold
                ]);

                // Créer l'item de commande
                $orderItem = OrderItem::create([
                    'order_id' => $order->id,
                    'ticket_type_id' => $ticketType->id,
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'total_price' => $item['total_price'],
                ]);

                // ===== GÉNÉRER ET RÉSERVER LES BILLETS =====
                $tickets = [];
                for ($i = 0; $i < $item['quantity']; $i++) {
                    $ticket = Ticket::create([
                        'ticket_type_id' => $ticketType->id,
                        'ticket_code' => Ticket::generateTicketCode(),
                        'status' => 'sold', // Directement vendu
                    ]);

                    // Générer le QR code
                    $ticket->generateQRCode();
                    
                    $tickets[] = $ticket;
                }

                // ===== ASSOCIER LES BILLETS À LA COMMANDE =====
                foreach ($tickets as $ticket) {
                    DB::table('order_tickets')->insert([
                        'order_id' => $order->id,
                        'ticket_id' => $ticket->id,
                        'order_item_id' => $orderItem->id,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }

                // ===== METTRE À JOUR LE STOCK VENDU =====
                $ticketType->increment('quantity_sold', $item['quantity']);

                \Log::info('Billets générés et associés', [
                    'order_item_id' => $orderItem->id,
                    'tickets_generated' => count($tickets),
                    'ticket_codes' => array_column($tickets, 'ticket_code')
                ]);
            }

            // ===== MARQUER LA COMMANDE COMME PAYÉE =====
            $order->markAsPaid('MANUAL-' . time());
            
            $orders[] = $order;

            // ===== CRÉER LA COMMISSION =====
            try {
                $this->createCommissionForOrder($order);
                \Log::info('Commission créée', ['order_id' => $order->id]);
            } catch (\Exception $e) {
                \Log::error('Erreur création commission', [
                    'order_id' => $order->id,
                    'error' => $e->getMessage()
                ]);
                // Ne pas faire échouer la commande pour une erreur de commission
            }
        }

        // ===== NETTOYER LES SESSIONS =====
        if ($isDirectBooking) {
            session()->forget(['direct_booking', 'booking_timer']);
            \Log::info('Session réservation directe nettoyée');
        } else {
            session()->forget(['cart', 'cart_timer']);
            \Log::info('Session panier nettoyée');
        }

        DB::commit();

        // ===== MESSAGES DE SUCCÈS ADAPTATIFS =====
        $successMessage = $isDirectBooking 
            ? 'Réservation confirmée avec succès ! Vos billets sont maintenant disponibles.'
            : 'Commande validée avec succès ! Vos billets sont maintenant disponibles.';

        \Log::info('Commande finalisée avec succès', [
            'user_id' => auth()->id(),
            'booking_type' => $isDirectBooking ? 'direct_reservation' : 'cart_order',
            'orders_created' => count($orders),
            'total_amount' => array_sum(array_column($orders, 'total_amount'))
        ]);

        // Redirection vers le dashboard acheteur
        return redirect()->route('acheteur.dashboard')
            ->with('success', $successMessage);

    } catch (\Exception $e) {
        DB::rollback();
        
        \Log::error('Erreur lors du traitement de commande', [
            'user_id' => auth()->id(),
            'booking_type' => $isDirectBooking ? 'direct_reservation' : 'cart_order',
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        
        // ===== LIBÉRER LE STOCK EN CAS D'ERREUR =====
        foreach ($eventGroups ?? [] as $eventId => $items) {
            foreach ($items as $item) {
                try {
                    $ticketType = TicketType::find($item['ticket_type_id']);
                    if ($ticketType) {
                        $ticketType->releaseTickets($item['quantity']);
                    }
                } catch (\Exception $releaseError) {
                    \Log::error('Erreur libération stock', [
                        'ticket_type_id' => $item['ticket_type_id'],
                        'quantity' => $item['quantity'],
                        'error' => $releaseError->getMessage()
                    ]);
                }
            }
        }
        
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
     * Créer une commission pour une commande
     */
 private function createCommissionForOrder($order)
{
    try {
        $commissionData = $order->calculateCommission();
        
        Commission::create([
            'order_id' => $order->id,
            'promoter_id' => $order->event->promoter_id, // ✅ Harmonisé
            'gross_amount' => $commissionData['gross_amount'],
            'commission_rate' => $commissionData['commission_rate'],
            'commission_amount' => $commissionData['commission_amount'],
            'net_amount' => $commissionData['net_amount'],
            'platform_fee' => $commissionData['platform_fee'],
            'status' => 'pending'
        ]);
        
        return true;
        
    } catch (\Exception $e) {
        \Log::error('Erreur création commission détaillée', [
            'order_id' => $order->id,
            'event_id' => $order->event_id,
            'promoter_id' => $order->event->promoter_id ?? 'NULL',
            'error' => $e->getMessage()
        ]);
        
        throw $e;
    }
}
}