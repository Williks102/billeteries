<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\TicketType;
use App\Models\Event;

class CartController extends Controller
{
    /**
     * Ajouter des billets au panier (version améliorée)
     */
    public function add(Request $request)
    {
        $request->validate([
            'ticket_type_id' => 'required|exists:ticket_types,id',
            'quantity' => 'required|integer|min:1|max:10'
        ]);

        $ticketType = TicketType::with('event')->findOrFail($request->ticket_type_id);
        
        // Vérifier que l'événement est encore disponible à la vente
        if (!$this->isEventAvailableForSale($ticketType->event)) {
            return response()->json([
                'success' => false,
                'message' => 'Cet événement n\'est plus disponible à la vente.'
            ], 400);
        }
        
        // Vérifier que les billets sont disponibles
        if (!$ticketType->canPurchaseQuantity($request->quantity)) {
            return response()->json([
                'success' => false,
                'message' => 'Quantité non disponible pour ce type de billet.'
            ], 400);
        }

        // Récupérer le panier actuel depuis la session
        $cart = session()->get('cart', []);
        
        // Clé unique pour ce type de billet
        $cartKey = 'ticket_' . $ticketType->id;
        
        // Si le billet existe déjà dans le panier, augmenter la quantité
        if (isset($cart[$cartKey])) {
            $newQuantity = $cart[$cartKey]['quantity'] + $request->quantity;
            
            // Vérifier que la nouvelle quantité ne dépasse pas les limites
            if (!$ticketType->canPurchaseQuantity($newQuantity)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Impossible d\'ajouter cette quantité. Limite atteinte.',
                    'current_in_cart' => $cart[$cartKey]['quantity'],
                    'max_allowed' => $ticketType->max_per_order
                ], 400);
            }
            
            $cart[$cartKey]['quantity'] = $newQuantity;
            $cart[$cartKey]['total_price'] = $cart[$cartKey]['unit_price'] * $newQuantity;
        } else {
            // Ajouter nouveau billet au panier
            $cart[$cartKey] = [
                'ticket_type_id' => $ticketType->id,
                'event_id' => $ticketType->event_id,
                'event_title' => $ticketType->event->title,
                'event_date' => $ticketType->event->formatted_event_date,
                'event_venue' => $ticketType->event->venue,
                'ticket_name' => $ticketType->name,
                'unit_price' => $ticketType->price,
                'quantity' => $request->quantity,
                'total_price' => $ticketType->price * $request->quantity,
                'max_per_order' => $ticketType->max_per_order,
                'added_at' => now()->timestamp
            ];
        }
        
        // Définir un timer de panier si ce n'est pas déjà fait
        if (!session()->has('cart_timer')) {
            session()->put('cart_timer', now()->addMinutes(15));
        }
        
        // Sauvegarder le panier en session
        session()->put('cart', $cart);
        
        return response()->json([
            'success' => true,
            'message' => 'Billets ajoutés au panier avec succès !',
            'cart_count' => $this->getCartCount(),
            'cart_total' => $this->getCartTotal(),
            'timer_remaining' => $this->getTimerRemaining()
        ]);
    }

    /**
     * Ajouter plusieurs types de billets en une fois (nouvelle méthode)
     */
    public function addMultiple(Request $request)
    {
        $request->validate([
            'tickets' => 'required|array',
            'tickets.*.ticket_type_id' => 'required|exists:ticket_types,id',
            'tickets.*.quantity' => 'required|integer|min:1|max:10'
        ]);

        $cart = session()->get('cart', []);
        $addedTickets = [];
        $errors = [];

        foreach ($request->tickets as $ticketData) {
            $ticketType = TicketType::with('event')->find($ticketData['ticket_type_id']);
            
            if (!$ticketType) {
                $errors[] = "Type de billet {$ticketData['ticket_type_id']} non trouvé";
                continue;
            }

            if (!$this->isEventAvailableForSale($ticketType->event)) {
                $errors[] = "L'événement {$ticketType->event->title} n'est plus disponible";
                continue;
            }

            if (!$ticketType->canPurchaseQuantity($ticketData['quantity'])) {
                $errors[] = "Quantité non disponible pour {$ticketType->name}";
                continue;
            }

            // Ajouter au panier
            $cartKey = 'ticket_' . $ticketType->id;
            
            if (isset($cart[$cartKey])) {
                $newQuantity = $cart[$cartKey]['quantity'] + $ticketData['quantity'];
                
                if (!$ticketType->canPurchaseQuantity($newQuantity)) {
                    $errors[] = "Limite atteinte pour {$ticketType->name}";
                    continue;
                }
                
                $cart[$cartKey]['quantity'] = $newQuantity;
                $cart[$cartKey]['total_price'] = $cart[$cartKey]['unit_price'] * $newQuantity;
            } else {
                $cart[$cartKey] = [
                    'ticket_type_id' => $ticketType->id,
                    'event_id' => $ticketType->event_id,
                    'event_title' => $ticketType->event->title,
                    'event_date' => $ticketType->event->formatted_event_date,
                    'event_venue' => $ticketType->event->venue,
                    'ticket_name' => $ticketType->name,
                    'unit_price' => $ticketType->price,
                    'quantity' => $ticketData['quantity'],
                    'total_price' => $ticketType->price * $ticketData['quantity'],
                    'max_per_order' => $ticketType->max_per_order,
                    'added_at' => now()->timestamp
                ];
            }

            $addedTickets[] = $ticketType->name;
        }

        // Définir un timer de panier
        if (!session()->has('cart_timer')) {
            session()->put('cart_timer', now()->addMinutes(15));
        }

        session()->put('cart', $cart);

        if (empty($addedTickets)) {
            return response()->json([
                'success' => false,
                'message' => 'Aucun billet n\'a pu être ajouté',
                'errors' => $errors
            ], 400);
        }

        $message = count($addedTickets) === 1 
            ? "Billet ajouté : " . $addedTickets[0]
            : count($addedTickets) . " types de billets ajoutés au panier";

        return response()->json([
            'success' => true,
            'message' => $message,
            'added_tickets' => $addedTickets,
            'errors' => $errors,
            'cart_count' => $this->getCartCount(),
            'cart_total' => $this->getCartTotal(),
            'timer_remaining' => $this->getTimerRemaining()
        ]);
    }

    /**
     * Vérifier si un événement est disponible à la vente
     */
    private function isEventAvailableForSale($event)
    {
        if (!$event) return false;
        
        // Vérifier si l'événement est dans le futur
        if ($event->event_date < now()) return false;
        
        // Vérifier si l'événement a des billets disponibles
        if ($event->ticketTypes->sum(function($ticketType) {
            return $ticketType->quantity_available - $ticketType->quantity_sold;
        }) <= 0) return false;
        
        return true;
    }

    /**
     * Obtenir le temps restant du timer (en secondes)
     */
    private function getTimerRemaining()
    {
        $timer = session()->get('cart_timer');
        if (!$timer) return null;
        
        $remaining = now()->diffInSeconds($timer, false);
        return max(0, $remaining);
    }

    /**
     * Mettre à jour la quantité d'un billet
     */
    public function update(Request $request)
    {
        $request->validate([
            'ticket_type_id' => 'required|exists:ticket_types,id',
            'quantity' => 'required|integer|min:0|max:10'
        ]);

        $cart = session()->get('cart', []);
        $cartKey = 'ticket_' . $request->ticket_type_id;
        
        if (!isset($cart[$cartKey])) {
            return response()->json([
                'success' => false,
                'message' => 'Billet non trouvé dans le panier.'
            ], 404);
        }

        if ($request->quantity == 0) {
            // Supprimer du panier
            unset($cart[$cartKey]);
            session()->put('cart', $cart);
            
            return response()->json([
                'success' => true,
                'message' => 'Billet supprimé du panier.',
                'cart_count' => $this->getCartCount(),
                'cart_total' => $this->getCartTotal()
            ]);
        }

        // Vérifier la disponibilité
        $ticketType = TicketType::find($request->ticket_type_id);
        if (!$ticketType || !$ticketType->canPurchaseQuantity($request->quantity)) {
            return response()->json([
                'success' => false,
                'message' => 'Quantité non disponible.'
            ], 400);
        }

        // Mettre à jour
        $cart[$cartKey]['quantity'] = $request->quantity;
        $cart[$cartKey]['total_price'] = $cart[$cartKey]['unit_price'] * $request->quantity;
        
        session()->put('cart', $cart);
        
        return response()->json([
            'success' => true,
            'message' => 'Quantité mise à jour.',
            'cart_count' => $this->getCartCount(),
            'cart_total' => $this->getCartTotal()
        ]);
    }

    /**
     * Supprimer un billet du panier
     */
    public function remove(Request $request)
    {
        $ticketTypeId = $request->ticket_type_id;
        $cart = session()->get('cart', []);
        $cartKey = 'ticket_' . $ticketTypeId;
        
        if (isset($cart[$cartKey])) {
            unset($cart[$cartKey]);
            session()->put('cart', $cart);
            
            return response()->json([
                'success' => true,
                'message' => 'Billet supprimé du panier.',
                'cart_count' => $this->getCartCount(),
                'cart_total' => $this->getCartTotal()
            ]);
        }
        
        return response()->json([
            'success' => false,
            'message' => 'Billet non trouvé.'
        ], 404);
    }

    /**
     * Vider le panier
     */
    public function clear()
    {
        session()->forget(['cart', 'cart_timer']);
        
        return response()->json([
            'success' => true,
            'message' => 'Panier vidé.',
            'cart_count' => 0,
            'cart_total' => 0
        ]);
    }

    /**
     * Prolonger le timer du panier
     */
    public function extendTimer(Request $request)
    {
        $cart = session()->get('cart', []);
        
        if (empty($cart)) {
            return response()->json([
                'success' => false,
                'message' => 'Aucun panier actif.'
            ], 400);
        }

        // Prolonger de 15 minutes
        session()->put('cart_timer', now()->addMinutes(15));
        
        return response()->json([
            'success' => true,
            'message' => 'Temps de réservation prolongé de 15 minutes.',
            'timer_remaining' => $this->getTimerRemaining()
        ]);
    }

    /**
     * Obtenir le nombre total d'articles dans le panier
     */
    public function getCartCount()
    {
        $cart = session()->get('cart', []);
        return array_sum(array_column($cart, 'quantity'));
    }

    /**
     * Obtenir le total du panier
     */
    public function getCartTotal()
    {
        $cart = session()->get('cart', []);
        return array_sum(array_column($cart, 'total_price'));
    }

    /**
     * API pour obtenir les données du panier
     */
    public function getCartData()
    {
        $cart = session()->get('cart', []);
        
        // Vérifier le timer
        $timer = session()->get('cart_timer');
        $timerExpired = $timer && now()->gt($timer);
        
        if ($timerExpired && !empty($cart)) {
            // Timer expiré, vider le panier
            session()->forget(['cart', 'cart_timer']);
            $cart = [];
        }
        
        return response()->json([
            'cart' => $cart,
            'cart_count' => $this->getCartCount(),
            'cart_total' => $this->getCartTotal(),
            'timer_remaining' => $this->getTimerRemaining(),
            'timer_expired' => $timerExpired
        ]);
    }
    /**
 * Afficher la page du panier
 */
public function show()
{
    $cart = session()->get('cart', []);
    
    // Vérifier le timer
    $cartTimer = session()->get('cart_timer');
    $timerExpired = $cartTimer && now()->gt($cartTimer);
    
    if ($timerExpired && !empty($cart)) {
        // Timer expiré, vider le panier
        session()->forget(['cart', 'cart_timer']);
        $cart = [];
        
        return redirect()->route('cart.show')->with('warning', 'Votre panier a expiré. Les billets ont été libérés.');
    }
    
    // Calculer les totaux
    $cartTotal = array_sum(array_column($cart, 'total_price'));
    $serviceFee = $cartTotal > 0 ? 500 : 0; // Frais de service
    $finalTotal = $cartTotal + $serviceFee;
    
    // Calculer le temps restant
    $timeRemaining = $cartTimer ? now()->diffInSeconds($cartTimer, false) : null;
    $timeRemaining = $timeRemaining > 0 ? $timeRemaining : null;
    
    return view('cart.show', compact('cart', 'cartTotal', 'serviceFee', 'finalTotal', 'timeRemaining'));
}
}