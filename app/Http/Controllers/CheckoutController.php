<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\TicketType;
use App\Models\Ticket;
use App\Models\Event;
use App\Models\Commission;
use App\Exceptions\CheckoutRefusedException;
use App\Services\EmailService;
use App\Services\CheckoutService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class CheckoutController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth')->except('direct');
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

        // Total recalculé côté serveur (jamais la session) : décide de l'affichage « Gratuit » / choix de paiement
        $cartTotal = app(CheckoutService::class)->serverCartTotal($cart);
        $isFree = $cartTotal === 0;

        return view('checkout.show', compact(
            'cart', 'cartTotal', 'isFree',
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
                'max_per_order' => CheckoutService::maxTicketsPerOrder($ticketType->max_per_order)
            ];

            $cartTotal += $ticketType->price * $quantity;
        }

        if (empty($selectedTickets)) {
            return redirect()->back()->with('error', 'Aucun billet sélectionné.');
        }

        // Sélection mise en session (le checkout recalcule prix et stock côté serveur) : on va directement
        // à l'étape « Informations », sans page intermédiaire ni passage par le panier.
        session()->put('direct_booking', $selectedTickets);

        return Auth::check()
            ? redirect()->route('checkout.show')
            : redirect()->route('checkout.guest.show');
    }

    /**
     * Traiter la commande (version complète avec emails)
     */
    public function process(Request $request)
    {
        // payment_method n'est validé qu'après le calcul du total serveur (voir plus bas) : jamais de champ client pour décider « gratuit »
        $request->validate([
            'billing_email' => 'required|email|max:255',
            'billing_phone' => 'required|string|max:20',
            'terms_accepted' => 'required|accepted',
        ]);

        try {
            // ===== RÉCUPÉRER LE PANIER =====
            $isDirectBooking = session()->has('direct_booking');
            $cart = $isDirectBooking ? session()->get('direct_booking', []) : session()->get('cart', []);
            
            if (empty($cart)) {
                return redirect()->route('cart.show')->with('error', 'Votre panier est vide.');
            }

            Log::info('Début checkout', [
                'user_id' => auth()->id(),
                'cart_items' => count($cart)
            ]);

            $service = app(CheckoutService::class);

            // Prix recalculés côté serveur ; commandes, stock et billets dans UNE transaction.
            // Commandes payantes `pending` (expires_at = now + réservation) ; commandes à 0 FCFA confirmées tout de suite.
            $groups = $service->buildEventGroups($cart);
            if ($service->requiresPaymentMethod($groups)) {
                $request->validate(['payment_method' => CheckoutService::paymentMethodRule()]);
            }
            $capByIp = $service->shouldCapByIp(Auth::user());
            $paid = $service->assertPendingLimitsForPaidGroups($groups, (string) $request->billing_email, Auth::id(), $request->ip(), $capByIp);

            $orders = $service->withFreeOrderLocks($groups, (string) $request->billing_email, $request->ip(), function () use ($service, $groups, $request) {
                $service->assertWithinFreeOrderLimits($groups, (string) $request->billing_email, $request->ip());

                return DB::transaction(function () use ($service, $groups, $request) {
                    $orders = [];
                    foreach ($groups as $eventId => $group) {
                        $orders[] = $service->createOrder(Auth::id(), $eventId, $group, [
                            'payment_method' => $request->input('payment_method'),
                            'billing_email' => $request->billing_email,
                            'billing_phone' => $request->billing_phone,
                        ], $request->ip());
                    }
                    return $orders;
                });
            });

            if ($capByIp && $paid['orders'] > 0) {
                $service->recordGuestOrders($request->ip(), $paid['orders'], $paid['tickets']);
            }

            // Commandes à 0 FCFA : confirmées via OrderPaymentService APRÈS commit (billets, QR, email).
            $orders = $service->finalizeFreeOrders($orders);

            // Mode démo (désactivé par défaut) : confirmation automatique APRÈS commit, validation et plafonds.
            $orders = $service->autoConfirmDemo($orders);

            $service->sendManualInstructions($orders);

            if ($isDirectBooking) {
                session()->forget(['direct_booking', 'booking_timer']);
            } else {
                session()->forget(['cart', 'cart_timer']);
            }

            $message = CheckoutService::successMessage(count($orders), collect($orders)->every(fn ($o) => $o->isFree()));

            return redirect()->route('checkout.confirmation', $orders[0])
                ->with('success', $message)
                ->with('other_order_numbers', collect($orders)->skip(1)->pluck('order_number')->all());

        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (CheckoutRefusedException $e) {
            return redirect()->back()->with('error', $e->getMessage())->withInput();
        } catch (\Throwable $e) {
            app(CheckoutService::class)->logFailure('Erreur checkout', $e, ['user_id' => auth()->id()]);

            return redirect()->back()
                ->with('error', CheckoutService::GENERIC_ERROR)
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

        return view('checkout.confirmation', $this->confirmationData($order));
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
                'promoteur_id' => $order->event->promoter_id
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
     * Variables de la page de confirmation : instructions de paiement tant que la commande est en attente ;
     * `demoSimulatedPayment` vrai si la commande a été confirmée par le mode démo.
     */
    private function confirmationData(Order $order): array
    {
        return [
            'order' => $order,
            'demoSimulatedPayment' => $order->isPaid() && $order->payment_reference === CheckoutService::DEMO_REFERENCE,
            'isFreeOrder' => $order->isFree(),
            'awaitingPayment' => $order->isAwaitingPayment(),
            'paymentInfo' => CheckoutService::manualPaymentInfo($order->payment_method),
            'paymentDeadline' => $order->paymentDeadline(),
        ];
    }
}
