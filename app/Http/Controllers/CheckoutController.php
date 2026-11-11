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
use App\Services\CheckoutService;
use App\Services\PaiementProService;
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
        $serviceFee = $cartTotal > 0 ? 500 : 0;
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
            'terms_accepted' => 'required|accepted',
            'payment_method' => 'required|in:paiementpro,bank_transfer',
            'channel' => 'required_if:payment_method,paiementpro|in:CARD,MOMO,OMCIV2,FLOOZ,PAYPAL,WAVECI'
        ]);

        try {
            // ===== RÉCUPÉRER LE PANIER =====
            $isDirectBooking = session()->has('direct_booking');
            $cart = $isDirectBooking ? session()->get('direct_booking', []) : session()->get('cart', []);
            
            if (empty($cart)) {
                return redirect()->route('cart.show')->with('error', 'Votre panier est vide.');
            }

            Log::info('Début checkout avec PaiementPro', [
                'user_id' => auth()->id(),
                'payment_method' => $request->payment_method,
                'channel' => $request->channel,
                'cart_items' => count($cart)
            ]);

            $service = app(CheckoutService::class);

            // Prix recalculés côté serveur ; commandes, stock et billets dans UNE transaction
            $orders = DB::transaction(function () use ($service, $cart, $request) {
                $orders = [];
                foreach ($service->buildEventGroups($cart) as $eventId => $group) {
                    $orders[] = $service->createOrder(Auth::id(), $eventId, $group, [
                        'payment_method' => $request->payment_method,
                        'billing_email' => $request->billing_email,
                        'billing_phone' => $request->billing_phone,
                    ]);
                }
                return $orders;
            });

            // ===== TRAITEMENT SELON LE MOYEN DE PAIEMENT =====
            
            if ($request->payment_method === 'paiementpro') {
                return $this->processPaiementPro($orders[0], $request->channel, $isDirectBooking);
            } 
            elseif ($request->payment_method === 'bank_transfer') {
                return $this->processBankTransfer($orders, $isDirectBooking);
            }

        } catch (\Exception $e) {
            Log::error('Erreur checkout avec PaiementPro', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
            ]);

            return redirect()->back()
                ->with('error', 'Erreur lors du traitement : ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * 🆕 NOUVELLE : Traitement PaiementPro
     */
    private function processPaiementPro(Order $order, $channel, $isDirectBooking = false)
    {
        // Libère le stock et lève une exception si l'init échoue
        $redirectUrl = app(CheckoutService::class)->initPaiementPro($order, $channel);

        if ($isDirectBooking) {
            session()->forget(['direct_booking', 'booking_timer']);
        } else {
            session()->forget(['cart', 'cart_timer']);
        }

        return redirect($redirectUrl);
    }

    /**
     * 🆕 NOUVELLE : Traitement virement bancaire
     */
    private function processBankTransfer(array $orders, $isDirectBooking = false)
    {
        try {
            foreach ($orders as $order) {
                // Marquer comme en attente de virement
                $order->update(['payment_status' => 'pending_transfer']);
                
                // Envoyer email avec instructions
                $this->sendBankTransferInstructions($order);
            }

            // Nettoyer les sessions
            if ($isDirectBooking) {
                session()->forget(['direct_booking', 'booking_timer']);
            } else {
                session()->forget(['cart', 'cart_timer']);
            }

            $message = count($orders) > 1 
                ? 'Commandes créées ! Instructions de virement envoyées par email.'
                : 'Commande créée ! Instructions de virement envoyées par email.';

            return redirect()->route('acheteur.orders.index')
                           ->with('success', $message);

        } catch (\Exception $e) {
            Log::error('Erreur processBankTransfer', [
                'orders' => array_column($orders, 'id'),
                'error' => $e->getMessage()
            ]);
            throw $e;
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

    private function sendBankTransferInstructions($order)
    {
        try {
            $emailService = app(EmailService::class);
            $emailService->sendBankTransferInstructions($order);
            
            Log::info('Instructions virement envoyées', [
                'order_id' => $order->id,
                'email' => $order->billing_email
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur envoi instructions virement', [
                'order_id' => $order->id,
                'error' => $e->getMessage()
            ]);
        }
    }
}
