<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use App\Models\TicketType;
use App\Models\Event;

class CartController extends Controller
{
    public function show()
    {
        $cart = Session::get('cart', []);
        $cartTotal = $this->getCartTotal();
        $cartCount = $this->getCartCount();
        
        return view('cart.show', compact('cart', 'cartTotal', 'cartCount'));
    }

    public function add(Request $request)
    {
        // Nouveau format : support pour plusieurs tickets à la fois
        if ($request->has('tickets') && $request->has('event_id')) {
            return $this->addMultipleTickets($request);
        }
        
        // Ancien format : 1 seul ticket (pour compatibilité)
        return $this->addSingleTicket($request);
    }

    /**
     * Ajouter plusieurs tickets depuis le formulaire événement
     */
    private function addMultipleTickets(Request $request)
    {
        $request->validate([
            'event_id' => 'required|exists:events,id',
            'tickets' => 'required|array',
            'tickets.*' => 'integer|min:0|max:20'
        ]);

        $event = Event::findOrFail($request->event_id);
        $cart = Session::get('cart', []);
        $addedTickets = 0;
        $errors = [];

        foreach ($request->tickets as $ticketTypeId => $quantity) {
            if ($quantity <= 0) continue;

            $ticketType = TicketType::find($ticketTypeId);
            
            if (!$ticketType) {
                $errors[] = "Type de billet #$ticketTypeId non trouvé";
                continue;
            }

            if ($ticketType->event_id != $request->event_id) {
                $errors[] = "Type de billet invalide pour cet événement";
                continue;
            }

            // Vérifier la disponibilité
            $remainingTickets = $ticketType->quantity_available - $ticketType->quantity_sold;
            if ($quantity > $remainingTickets) {
                $errors[] = "Seulement $remainingTickets billets disponibles pour {$ticketType->name}";
                continue;
            }

            // Vérifier la limite par commande
            $maxPerOrder = $ticketType->max_per_order ?? 10;
            if ($quantity > $maxPerOrder) {
                $errors[] = "Maximum $maxPerOrder billets par commande pour {$ticketType->name}";
                continue;
            }

            // Clé unique pour ce type de billet
            $cartKey = 'ticket_' . $ticketType->id;
            
            // Si le billet existe déjà dans le panier, augmenter la quantité
            if (isset($cart[$cartKey])) {
                $newQuantity = $cart[$cartKey]['quantity'] + $quantity;
                
                if ($newQuantity > $maxPerOrder) {
                    $errors[] = "Limite de $maxPerOrder billets atteinte pour {$ticketType->name}";
                    continue;
                }
                
                if ($newQuantity > $remainingTickets) {
                    $errors[] = "Pas assez de billets disponibles pour {$ticketType->name}";
                    continue;
                }
                
                $cart[$cartKey]['quantity'] = $newQuantity;
                $cart[$cartKey]['total_price'] = $cart[$cartKey]['unit_price'] * $newQuantity;
            } else {
                // Ajouter nouveau billet au panier
                $cart[$cartKey] = [
                    'ticket_type_id' => $ticketType->id,
                    'event_id' => $ticketType->event_id,
                    'event_title' => $event->title,
                    'event_date' => $event->event_date ? $event->event_date->format('d/m/Y') : 'Date TBD',
                    'event_venue' => $event->venue,
                    'ticket_name' => $ticketType->name,
                    'unit_price' => $ticketType->price,
                    'quantity' => $quantity,
                    'total_price' => $ticketType->price * $quantity,
                    'max_per_order' => $maxPerOrder
                ];
            }
            
            $addedTickets += $quantity;
        }

        // Sauvegarder le panier en session
        Session::put('cart', $cart);

        if ($request->expectsJson()) {
            if (!empty($errors)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Certains billets n\'ont pas pu être ajoutés',
                    'errors' => $errors,
                    'cartCount' => $this->getCartCount()
                ], 400);
            }

            return response()->json([
                'success' => true,
                'message' => "$addedTickets billet(s) ajouté(s) au panier avec succès !",
                'cartCount' => $this->getCartCount(),
                'cartTotal' => $this->getCartTotal()
            ]);
        }

        if (!empty($errors)) {
            return redirect()->back()->withErrors($errors)->with('warning', 'Certains billets n\'ont pas pu être ajoutés');
        }

        return redirect()->back()->with('success', "$addedTickets billet(s) ajouté(s) au panier !");
    }

    /**
     * Ajouter un seul ticket (format original - pour compatibilité)
     */
    private function addSingleTicket(Request $request)
    {
        $request->validate([
            'ticket_type_id' => 'required|exists:ticket_types,id',
            'quantity' => 'required|integer|min:1|max:10'
        ]);

        $ticketType = TicketType::with('event')->findOrFail($request->ticket_type_id);
        
        // Vérifier la disponibilité
        $remainingTickets = $ticketType->quantity_available - $ticketType->quantity_sold;
        if ($request->quantity > $remainingTickets) {
            return response()->json([
                'success' => false,
                'message' => 'Quantité non disponible pour ce type de billet.'
            ], 400);
        }

        $cart = Session::get('cart', []);
        $cartKey = 'ticket_' . $ticketType->id;
        
        // Si le billet existe déjà dans le panier, augmenter la quantité
        if (isset($cart[$cartKey])) {
            $newQuantity = $cart[$cartKey]['quantity'] + $request->quantity;
            $maxPerOrder = $ticketType->max_per_order ?? 10;
            
            if ($newQuantity > $maxPerOrder) {
                return response()->json([
                    'success' => false,
                    'message' => "Maximum $maxPerOrder billets par commande pour ce type de billet."
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
                'event_date' => $ticketType->event->event_date ? $ticketType->event->event_date->format('d/m/Y') : 'Date TBD',
                'event_venue' => $ticketType->event->venue,
                'ticket_name' => $ticketType->name,
                'unit_price' => $ticketType->price,
                'quantity' => $request->quantity,
                'total_price' => $ticketType->price * $request->quantity,
                'max_per_order' => $ticketType->max_per_order ?? 10
            ];
        }
        
        Session::put('cart', $cart);
        
        return response()->json([
            'success' => true,
            'message' => 'Billets ajoutés au panier avec succès !',
            'cartCount' => $this->getCartCount(),
            'cartTotal' => $this->getCartTotal()
        ]);
    }

    public function update(Request $request)
    {
        $request->validate([
            'cart_key' => 'required|string',
            'quantity' => 'required|integer|min:1|max:20'
        ]);

        $cart = Session::get('cart', []);
        
        if (!isset($cart[$request->cart_key])) {
            return response()->json([
                'success' => false,
                'message' => 'Billet non trouvé dans le panier.'
            ], 404);
        }

        $ticketType = TicketType::find($cart[$request->cart_key]['ticket_type_id']);
        
        if (!$ticketType) {
            return response()->json([
                'success' => false,
                'message' => 'Type de billet non trouvé.'
            ], 404);
        }

        // Vérifier la disponibilité
        $remainingTickets = $ticketType->quantity_available - $ticketType->quantity_sold;
        if ($request->quantity > $remainingTickets) {
            return response()->json([
                'success' => false,
                'message' => 'Quantité non disponible.'
            ], 400);
        }

        // Mettre à jour la quantité
        $cart[$request->cart_key]['quantity'] = $request->quantity;
        $cart[$request->cart_key]['total_price'] = $cart[$request->cart_key]['unit_price'] * $request->quantity;
        
        Session::put('cart', $cart);
        
        return response()->json([
            'success' => true,
            'message' => 'Panier mis à jour !',
            'cartCount' => $this->getCartCount(),
            'cartTotal' => $this->getCartTotal()
        ]);
    }

    public function remove(Request $request)
    {
        $request->validate([
            'cart_key' => 'required|string'
        ]);

        $cart = Session::get('cart', []);
        
        if (isset($cart[$request->cart_key])) {
            unset($cart[$request->cart_key]);
            Session::put('cart', $cart);
            
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Billet supprimé du panier.',
                    'cartCount' => $this->getCartCount(),
                    'cartTotal' => $this->getCartTotal()
                ]);
            }
            
            return redirect()->back()->with('success', 'Billet supprimé du panier');
        }
        
        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => 'Billet non trouvé.'
            ], 404);
        }
        
        return redirect()->back()->with('error', 'Billet non trouvé');
    }

    public function clear()
    {
        Session::forget('cart');
        
        if (request()->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Panier vidé.',
                'cartCount' => 0,
                'cartTotal' => 0
            ]);
        }
        
        return redirect()->back()->with('success', 'Panier vidé');
    }

    public function getCartData()
    {
        $cart = Session::get('cart', []);
        $totalItems = $this->getCartCount();
        $totalPrice = $this->getCartTotal();

        return response()->json([
            'items' => $cart,
            'totalItems' => $totalItems,
            'totalPrice' => $totalPrice,
            'formattedTotal' => number_format($totalPrice, 0, ',', ' ') . ' FCFA'
        ]);
    }

    /**
     * Calculer le nombre total d'articles dans le panier
     */
    private function getCartCount()
    {
        $cart = Session::get('cart', []);
        return array_sum(array_column($cart, 'quantity'));
    }

    /**
     * Calculer le total du panier
     */
    private function getCartTotal()
    {
        $cart = Session::get('cart', []);
        return array_sum(array_column($cart, 'total_price'));
    }
}