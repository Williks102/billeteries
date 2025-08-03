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

        return view('checkout.show', compact('cart', 'cartTotal', 'serviceFee', 'finalTotal'));
    }

    /**
     * Traiter la commande (sans paiement pour l'instant)
     */
    public function process(Request $request)
    {
        $request->validate([
            'billing_email' => 'required|email',
            'billing_phone' => 'required|string|max:20',
            'terms_accepted' => 'required|accepted'
        ]);

        $cart = session()->get('cart', []);
        
        if (empty($cart)) {
            return redirect()->route('cart.show')->with('error', 'Votre panier est vide.');
        }

        try {
            DB::beginTransaction();

            // Grouper les billets par événement pour créer une commande par événement
            $eventGroups = [];
            foreach ($cart as $item) {
                $eventId = $item['event_id'];
                if (!isset($eventGroups[$eventId])) {
                    $eventGroups[$eventId] = [];
                }
                $eventGroups[$eventId][] = $item;
            }

            $orders = [];

            foreach ($eventGroups as $eventId => $items) {
                // Calculer le total pour cet événement
                $eventTotal = array_sum(array_column($items, 'total_price'));
                
                // Créer la commande
                $order = Order::create([
                    'user_id' => Auth::id(),
                    'event_id' => $eventId,
                    'total_amount' => $eventTotal,
                    'payment_status' => 'pending', // En attente pour l'instant
                    'payment_method' => 'manual', // Méthode manuelle pour l'instant
                    'order_number' => Order::generateOrderNumber(),
                    'billing_email' => $request->billing_email,
                    'billing_phone' => $request->billing_phone,
                ]);

                

                // Créer les items de commande et réserver les billets
                foreach ($items as $item) {
                    $ticketType = TicketType::findOrFail($item['ticket_type_id']);
                    
                    // Vérifier encore la disponibilité
                    if (!$ticketType->canPurchaseQuantity($item['quantity'])) {
                        throw new \Exception("Le billet '{$item['ticket_name']}' n'est plus disponible.");
                    }

                    // Créer l'item de commande
                    $orderItem = OrderItem::create([
                        'order_id' => $order->id,
                        'ticket_type_id' => $ticketType->id,
                        'quantity' => $item['quantity'],
                        'unit_price' => $item['unit_price'],
                        'total_price' => $item['total_price'],
                    ]);

                    // Générer et réserver les billets
                    $tickets = [];
                    for ($i = 0; $i < $item['quantity']; $i++) {
                        $ticket = Ticket::create([
                            'ticket_type_id' => $ticketType->id,
                            'ticket_code' => Ticket::generateTicketCode(),
                            'status' => 'sold', // Directement vendu pour simplifier
                        ]);

                        // Générer le QR code
                        $ticket->generateQRCode();
                        
                        $tickets[] = $ticket;
                    }

                    // Associer les billets à la commande
                    foreach ($tickets as $ticket) {
                        DB::table('order_tickets')->insert([
                            'order_id' => $order->id,
                            'ticket_id' => $ticket->id,
                            'order_item_id' => $orderItem->id,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                        // Envoyer les emails
                        $emailService = app(\App\Services\EmailService::class);
                        $emailService->sendAllOrderEmails($order);
                    }

                    // Mettre à jour le stock
                    $ticketType->increment('quantity_sold', $item['quantity']);
                }

                // Marquer la commande comme payée (simulation pour l'instant)
                $order->markAsPaid('MANUAL-' . time());
                
                $orders[] = $order;

                // Créer la commission pour la commande
                $this->createCommissionForOrder($order);
            }

            // Vider le panier
            session()->forget('cart');

            DB::commit();

            // Redirection vers le dashboard acheteur avec succès
            return redirect()->route('acheteur.dashboard')
                ->with('success', 'Commande validée avec succès ! Vos billets sont maintenant disponibles.');

        } catch (\Exception $e) {
            DB::rollback();
            
            return redirect()->back()
                ->with('error', 'Erreur lors de la validation de votre commande : ' . $e->getMessage())
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
    $event = $order->event;
    
    // 🔍 DEBUG
    \Log::info('Debug Commission Creation', [
        'event_id' => $event->id,
        'promoter_id' => $event->promoter_id,
        'promoteur_id' => $event->promoteur_id ?? 'N/A (normal)',
        'event_fillable' => $event->getFillable()
    ]);
    
    $commissionData = $order->calculateCommission();
    
    if (!$event->promoter_id) {
        throw new \Exception('ERREUR: promoter_id manquant pour l\'événement #' . $event->id);
    }
    
    Commission::create([
        'order_id' => $order->id,
        'promoter_id' => $event->promoter_id,  // ✅ Correct
        'gross_amount' => $commissionData['gross_amount'],
        'commission_rate' => $commissionData['commission_rate'],
        'commission_amount' => $commissionData['commission_amount'],
        'net_amount' => $commissionData['net_amount'],
        'platform_fee' => $commissionData['platform_fee'],
        'status' => 'pending'
    ]);
}
}