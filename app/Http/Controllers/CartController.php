<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\TicketType;
use App\Models\Event;

class CartController extends Controller
{
    /**
     * Ajouter des billets au panier
     */
    public function add(Request $request)
    {
        $request->validate([
            'ticket_type_id' => 'required|exists:ticket_types,id',
            'quantity' => 'required|integer|min:1|max:10'
        ]);

        $ticketType = TicketType::with('event')->findOrFail($request->ticket_type_id);
        
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
                    'message' => 'Impossible d\'ajouter cette quantité. Limite atteinte.'
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
                'max_per_order' => $ticketType->max_per_order
            ];
        }
        
        // Sauvegarder le panier en session
        session()->put('cart', $cart);
        
        return response()->json([
            'success' => true,
            'message' => 'Billets ajoutés au panier avec succès !',
            'cart_count' => $this->getCartCount(),
            'cart_total' => $this->getCartTotal()
        ]);
    }

    /**
     * Afficher le panier
     */
    public function show()
    {
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

        // Mettre à jour la quantité
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
     * API pour obtenir les données du panier - CORRECTION
     */
    public function getCartData()
    {
        return response()->json([
            'cart' => session()->get('cart', []),
            'count' => $this->getCartCount(), // CORRIGÉ : count au lieu de cart_count
            'cart_count' => $this->getCartCount(), // Gardé pour compatibilité
            'cart_total' => $this->getCartTotal()
        ]);
    }
}