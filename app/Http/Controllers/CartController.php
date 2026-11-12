<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\TicketType;
use App\Models\Event;
use App\Services\CheckoutService;

class CartController extends Controller
{
    /**
     * Ajouter des billets au panier - NOUVELLE VERSION POUR MODAL
     */
    public function add(Request $request)
    {
        session()->forget(['direct_booking', 'booking_timer']); // une réservation directe abandonnée ne doit pas masquer le panier
        // Supporter les deux formats : ancien (single) et nouveau (multiple)
        if ($request->has('tickets')) {
            // Nouveau format du modal (multiple tickets)
            return $this->addMultipleTickets($request);
        } else {
            // Ancien format (single ticket) pour compatibilité
            return $this->addSingleTicket($request);
        }
    }

    /**
     * Ajouter plusieurs types de billets (format modal)
     */
    private function addMultipleTickets(Request $request)
    {
        $request->validate([
            'event_id' => 'required|exists:events,id',
            'tickets' => 'required|array|min:1',
            'tickets.*.ticket_type_id' => 'required|exists:ticket_types,id',
            'tickets.*.quantity' => 'required|integer|min:1|max:10'
        ]);

        $event = Event::findOrFail($request->event_id);
        $cart = session()->get('cart', []);
        $addedTickets = [];
        $totalAdded = 0;

        foreach ($request->tickets as $ticketData) {
            $ticketType = TicketType::with('event')->findOrFail($ticketData['ticket_type_id']);
            
            // Vérifier que le ticket appartient bien à cet événement
            if ($ticketType->event_id != $request->event_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Type de billet non valide pour cet événement.'
                ], 400);
            }

            // Vérifier la disponibilité
            $requestedQuantity = $ticketData['quantity'];
            $cartKey = 'ticket_' . $ticketType->id;
            $currentCartQuantity = isset($cart[$cartKey]) ? $cart[$cartKey]['quantity'] : 0;
            $totalQuantity = $currentCartQuantity + $requestedQuantity;

            $perOrder = CheckoutService::maxTicketsPerOrder($ticketType->max_per_order !== null ? (int) $ticketType->max_per_order : null);
            if ($totalQuantity > $perOrder) {
                return response()->json([
                    'success' => false,
                    'message' => "'{$ticketType->name}' : " . CheckoutService::perOrderLimitMessage($perOrder)
                ], 400);
            }
            if (!$ticketType->canPurchaseQuantity($totalQuantity)) {
                return response()->json([
                    'success' => false,
                    'message' => "Quantité non disponible pour '{$ticketType->name}'. Maximum: " . $perOrder
                ], 400);
            }

            // Ajouter ou mettre à jour dans le panier
            if (isset($cart[$cartKey])) {
                $cart[$cartKey]['quantity'] = $totalQuantity;
                $cart[$cartKey]['total_price'] = $cart[$cartKey]['unit_price'] * $totalQuantity;
            } else {
                $cart[$cartKey] = [
                    'ticket_type_id' => $ticketType->id,
                    'event_id' => $ticketType->event_id,
                    'event_title' => $ticketType->event->title,
                    'event_date' => $ticketType->event->formatted_event_date,
                    'event_venue' => $ticketType->event->venue,
                    'event_image' => $ticketType->event->image,
                    'ticket_name' => $ticketType->name,
                    'unit_price' => $ticketType->price,
                    'quantity' => $requestedQuantity,
                    'total_price' => $ticketType->price * $requestedQuantity,
                    'max_per_order' => CheckoutService::maxTicketsPerOrder($ticketType->max_per_order)
                ];
            }

            $addedTickets[] = [
                'name' => $ticketType->name,
                'quantity' => $requestedQuantity
            ];
            $totalAdded += $requestedQuantity;
        }

        // I-a : le TOTAL de billets du panier ne doit pas dépasser le plafond par commande (checkout : même règle)
        $maxTotal = CheckoutService::maxTicketsPerOrder();
        if ($this->cartQuantity($cart) > $maxTotal) {
            return response()->json([
                'success' => false,
                'message' => CheckoutService::totalLimitMessage($maxTotal)
            ], 400);
        }

        // Sauvegarder le panier
        session()->put('cart', $cart);

        // Message de succès personnalisé
        $message = $totalAdded === 1 
            ? "1 billet ajouté au panier !"
            : "{$totalAdded} billets ajoutés au panier !";

        return response()->json([
            'success' => true,
            'message' => $message,
            'added_tickets' => $addedTickets,
            'cart_count' => $this->getCartCount(),
            'cart_total' => $this->getCartTotal(),
            'redirect' => route('cart.show') // Optionnel pour redirection
        ]);
    }

    /**
     * Ajouter un seul type de billet (ancien format)
     */
    private function addSingleTicket(Request $request)
    {
        $request->validate([
            'ticket_type_id' => 'required|exists:ticket_types,id',
            'quantity' => 'required|integer|min:1|max:10'
        ]);

        $ticketType = TicketType::with('event')->findOrFail($request->ticket_type_id);

        $perOrder = CheckoutService::maxTicketsPerOrder($ticketType->max_per_order !== null ? (int) $ticketType->max_per_order : null);
        $already = (int) (session()->get('cart', ['ticket_' . $ticketType->id => ['quantity' => 0]])['ticket_' . $ticketType->id]['quantity'] ?? 0);
        if ($already + (int) $request->quantity > $perOrder) {
            return response()->json(['success' => false, 'message' => CheckoutService::perOrderLimitMessage($perOrder)], 400);
        }

        // I-a : total du panier (tous types et événements) = ce que le checkout acceptera
        $maxTotal = CheckoutService::maxTicketsPerOrder();
        if ($this->cartQuantity(session()->get('cart', [])) + (int) $request->quantity > $maxTotal) {
            return response()->json(['success' => false, 'message' => CheckoutService::totalLimitMessage($maxTotal)], 400);
        }

        // Vérifier la disponibilité
        if (!$ticketType->canPurchaseQuantity($request->quantity)) {
            return response()->json([
                'success' => false,
                'message' => 'Quantité non disponible pour ce type de billet.'
            ], 400);
        }

        $cart = session()->get('cart', []);
        $cartKey = 'ticket_' . $ticketType->id;
        
        // Si le billet existe déjà dans le panier
        if (isset($cart[$cartKey])) {
            $newQuantity = $cart[$cartKey]['quantity'] + $request->quantity;
            
            if (!$ticketType->canPurchaseQuantity($newQuantity)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Impossible d\'ajouter cette quantité. Limite atteinte.'
                ], 400);
            }
            
            $cart[$cartKey]['quantity'] = $newQuantity;
            $cart[$cartKey]['total_price'] = $cart[$cartKey]['unit_price'] * $newQuantity;
        } else {
            // Nouveau billet dans le panier
            $cart[$cartKey] = [
                'ticket_type_id' => $ticketType->id,
                'event_id' => $ticketType->event_id,
                'event_title' => $ticketType->event->title,
                'event_date' => $ticketType->event->formatted_event_date,
                'event_venue' => $ticketType->event->venue,
                'event_image' => $ticketType->event->image,
                'ticket_name' => $ticketType->name,
                'unit_price' => $ticketType->price,
                'quantity' => $request->quantity,
                'total_price' => $ticketType->price * $request->quantity,
                'max_per_order' => CheckoutService::maxTicketsPerOrder($ticketType->max_per_order)
            ];
        }
        
        session()->put('cart', $cart);
        
        return response()->json([
            'success' => true,
            'message' => 'Billets ajoutés au panier avec succès !',
            'cart_count' => $this->getCartCount(),
            'cart_total' => $this->getCartTotal()
        ]);
    }

    /**
     * API pour obtenir les données du panier
     */
    public function getCartData()
    {
        $cart = session()->get('cart', []);
        
        return response()->json([
            'cart' => $cart,
            'totalItems' => $this->getCartCount(), // Support pour le badge du header
            'count' => $this->getCartCount(),
            'cart_count' => $this->getCartCount(),
            'cart_total' => $this->getCartTotal(),
            'formatted_total' => number_format($this->getCartTotal()) . ' FCFA'
        ]);
    }

    // ... Autres méthodes existantes (show, update, remove, clear, etc.)
    
    /**
     * Afficher le panier
     */
    public function show()
    {
        session()->forget(['direct_booking', 'booking_timer']);
        $cart = session()->get('cart', []);
        $cartTotal = $this->getCartTotal();
        $cartCount = $this->getCartCount();
        
        return view('cart.show', compact('cart', 'cartTotal', 'cartCount'));
    }

    /**
     * Mettre à jour la quantité d'un billet
     */
    public function update(Request $request)
    {
        $request->validate([
            'cart_key' => 'required|string',
            'quantity' => 'required|integer|min:1|max:10'
        ]);

        $cart = session()->get('cart', []);
        
        if (!isset($cart[$request->cart_key])) {
            return response()->json([
                'success' => false,
                'message' => 'Billet non trouvé dans le panier.'
            ], 404);
        }

        $ticketType = TicketType::find($cart[$request->cart_key]['ticket_type_id']);
        
        if (!$ticketType || !$ticketType->canPurchaseQuantity($request->quantity)) {
            return response()->json([
                'success' => false,
                'message' => 'Quantité non disponible.'
            ], 400);
        }

        // I-a : le total du panier après modification ne doit pas dépasser le plafond par commande
        $maxTotal = CheckoutService::maxTicketsPerOrder();
        $others = $this->cartQuantity($cart) - (int) $cart[$request->cart_key]['quantity'];
        if ($others + (int) $request->quantity > $maxTotal) {
            return response()->json([
                'success' => false,
                'message' => CheckoutService::totalLimitMessage($maxTotal)
            ], 400);
        }

        // Mettre à jour
        $cart[$request->cart_key]['quantity'] = $request->quantity;
        $cart[$request->cart_key]['total_price'] = $cart[$request->cart_key]['unit_price'] * $request->quantity;
        
        session()->put('cart', $cart);
        
        return response()->json([
            'success' => true,
            'message' => 'Panier mis à jour !',
            'cart_count' => $this->getCartCount(),
            'cart_total' => $this->getCartTotal()
        ]);
    }

    /**
     * Supprimer un billet du panier
     */
    public function remove(Request $request)
    {
        $request->validate([
            'cart_key' => 'required|string'
        ]);

        $cart = session()->get('cart', []);
        
        if (isset($cart[$request->cart_key])) {
            unset($cart[$request->cart_key]);
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
        session()->forget('cart');
        
        return response()->json([
            'success' => true,
            'message' => 'Panier vidé.',
            'cart_count' => 0,
            'cart_total' => 0
        ]);
    }

    private function cartQuantity(array $cart): int
    {
        return (int) array_sum(array_map(fn ($l) => (int) ($l['quantity'] ?? 0), $cart));
    }

    /**
     * Obtenir le nombre total d'articles
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
}