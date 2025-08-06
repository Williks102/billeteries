<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\TicketType;
use App\Models\Event;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Log;

class CartController extends Controller
{
    /**
     * SYSTÈME UNIFIÉ - 15 MINUTES TIMER UNIQUEMENT
     */
    
    /**
     * Ajouter des billets au panier avec timer de 15 minutes
     */
    public function add(Request $request)
    {
        $request->validate([
            'event_id' => 'required|exists:events,id',
            'tickets' => 'required|array',
            'tickets.*' => 'integer|min:0|max:20'
        ]);

        $event = Event::findOrFail($request->event_id);
        $errors = [];
        $addedTickets = 0;
        
        // Nettoyer d'abord les sessions expirées
        $this->cleanExpiredSessions();
        
        $cart = session()->get('cart', []);

        foreach ($request->tickets as $ticketTypeId => $quantity) {
            if ($quantity <= 0) continue;

            $ticketType = TicketType::findOrFail($ticketTypeId);
            
            // Vérifications
            if ($ticketType->event_id != $request->event_id) {
                $errors[] = "Type de billet invalide pour {$ticketType->name}";
                continue;
            }

            $remainingTickets = $ticketType->quantity_available - $ticketType->quantity_sold;
            if ($quantity > $remainingTickets) {
                $errors[] = "Pas assez de billets disponibles pour {$ticketType->name}";
                continue;
            }

            $maxPerOrder = $ticketType->max_per_order ?? 10;
            if ($quantity > $maxPerOrder) {
                $errors[] = "Maximum {$maxPerOrder} billets par commande pour {$ticketType->name}";
                continue;
            }

            // Clé unique pour ce type de billet
            $cartKey = 'ticket_' . $ticketType->id;
            
            // Si le billet existe déjà dans le panier, remplacer la quantité
            if (isset($cart[$cartKey])) {
                $cart[$cartKey]['quantity'] = $quantity;
                $cart[$cartKey]['total_price'] = $cart[$cartKey]['unit_price'] * $quantity;
            } else {
                // Ajouter nouveau billet au panier
                $cart[$cartKey] = [
                    'ticket_type_id' => $ticketType->id,
                    'event_id' => $ticketType->event_id,
                    'event_title' => $event->title,
                    'event_date' => $event->event_date ? $event->event_date->format('d/m/Y') : 'Date TBD',
                    'event_venue' => $event->venue,
                    'event_image' => $event->image,
                    'ticket_name' => $ticketType->name,
                    'unit_price' => $ticketType->price,
                    'quantity' => $quantity,
                    'total_price' => $ticketType->price * $quantity,
                    'max_per_order' => $maxPerOrder,
                    'added_at' => now()
                ];
            }
            
            $addedTickets += $quantity;
        }

        // Sauvegarder le panier en session avec timer de 15 minutes
        session()->put('cart', $cart);
        session()->put('cart_timer', now()->addMinutes(15));
        
        Log::info('Billets ajoutés au panier', [
            'user_id' => auth()->id(),
            'event_id' => $request->event_id,
            'tickets_added' => $addedTickets,
            'timer_expires_at' => session()->get('cart_timer')
        ]);

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
                'message' => "{$addedTickets} billet(s) ajouté(s) au panier avec succès ! (15 min de réservation)",
                'cartCount' => $this->getCartCount(),
                'cartTotal' => $this->getCartTotal(),
                'timerExpiresAt' => session()->get('cart_timer')->toISOString()
            ]);
        }

        if (!empty($errors)) {
            return redirect()->back()->withErrors($errors)->with('warning', 'Certains billets n\'ont pas pu être ajoutés');
        }

        return redirect()->route('cart.show')->with('success', "{$addedTickets} billet(s) ajouté(s) au panier !");
    }

    /**
     * Afficher le panier
     */
    public function show()
    {
        // Nettoyer les sessions expirées
        $this->cleanExpiredSessions();
        
        $cart = session()->get('cart', []);
        $cartTotal = $this->getCartTotal();
        $cartCount = $this->getCartCount();
        
        // Calculer le temps restant
        $cartTimer = session()->get('cart_timer');
        $timeRemaining = null;
        
        if ($cartTimer && now()->lt($cartTimer)) {
            $timeRemaining = now()->diffInMinutes($cartTimer);
        } elseif (!empty($cart) && $cartTimer) {
            // Timer expiré avec des items - nettoyer
            session()->forget(['cart', 'cart_timer']);
            return redirect()->route('cart.show')->with('warning', 'Votre panier a expiré. Les billets ont été libérés.');
        }
        
        return view('cart.show', compact('cart', 'cartTotal', 'cartCount', 'timeRemaining'));
    }

    /**
     * Mettre à jour la quantité d'un billet
     */
    public function update(Request $request)
    {
        $request->validate([
            'cart_key' => 'required|string',
            'quantity' => 'required|integer|min:1|max:20'
        ]);

        // Nettoyer les sessions expirées
        $this->cleanExpiredSessions();

        $cart = session()->get('cart', []);
        
        if (empty($cart)) {
            return response()->json([
                'success' => false,
                'message' => 'Panier vide ou session expirée.'
            ], 404);
        }
        
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
        
        session()->put('cart', $cart);
        
        Log::info('Quantité mise à jour dans le panier', [
            'user_id' => auth()->id(),
            'cart_key' => $request->cart_key,
            'new_quantity' => $request->quantity
        ]);
        
        return response()->json([
            'success' => true,
            'message' => 'Panier mis à jour !',
            'cartCount' => $this->getCartCount(),
            'cartTotal' => $this->getCartTotal(),
            'timeRemaining' => $this->getTimeRemaining()
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

        // Nettoyer les sessions expirées
        $this->cleanExpiredSessions();

        $cart = session()->get('cart', []);
        
        if (empty($cart)) {
            return response()->json([
                'success' => false,
                'message' => 'Panier vide ou session expirée.'
            ], 404);
        }
        
        if (isset($cart[$request->cart_key])) {
            $removedItem = $cart[$request->cart_key];
            unset($cart[$request->cart_key]);
            session()->put('cart', $cart);
            
            Log::info('Article supprimé du panier', [
                'user_id' => auth()->id(),
                'cart_key' => $request->cart_key,
                'ticket_name' => $removedItem['ticket_name']
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Billet supprimé du panier.',
                'cartCount' => $this->getCartCount(),
                'cartTotal' => $this->getCartTotal(),
                'timeRemaining' => $this->getTimeRemaining()
            ]);
        }
        
        return response()->json([
            'success' => false,
            'message' => 'Billet non trouvé dans le panier.'
        ], 404);
    }

    /**
     * Vider le panier
     */
    public function clear()
    {
        session()->forget(['cart', 'cart_timer']);
        
        Log::info('Panier vidé', [
            'user_id' => auth()->id()
        ]);
        
        if (request()->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Panier vidé.',
                'cartCount' => 0,
                'cartTotal' => 0
            ]);
        }
        
        return redirect()->back()->with('success', 'Panier vidé.');
    }

    /**
     * API pour obtenir les données du panier
     */
    public function getCartData()
    {
        $this->cleanExpiredSessions();
        
        $cart = session()->get('cart', []);
        $cartCount = $this->getCartCount();
        $cartTotal = $this->getCartTotal();
        $timeRemaining = $this->getTimeRemaining();

        return response()->json([
            'cart' => $cart,
            'cartCount' => $cartCount,
            'cartTotal' => $cartTotal,
            'timeRemaining' => $timeRemaining,
            'formattedTotal' => number_format($cartTotal, 0, ',', ' ') . ' FCFA',
            'hasTimer' => $timeRemaining !== null
        ]);
    }

    /**
     * Calculer le nombre total d'articles dans le panier
     */
    public function getCartCount()
    {
        $cart = session()->get('cart', []);
        return array_sum(array_column($cart, 'quantity'));
    }

    /**
     * Calculer le total du panier
     */
    public function getCartTotal()
    {
        $cart = session()->get('cart', []);
        return array_sum(array_column($cart, 'total_price'));
    }

    /**
     * Obtenir le temps restant en minutes
     */
    public function getTimeRemaining()
    {
        $cartTimer = session()->get('cart_timer');
        
        if (!$cartTimer) {
            return null;
        }
        
        $remaining = now()->diffInMinutes($cartTimer, false);
        return $remaining > 0 ? $remaining : 0;
    }

    /**
     * Nettoyer les sessions expirées
     */
    private function cleanExpiredSessions()
    {
        $cartTimer = session()->get('cart_timer');
        
        if ($cartTimer && now()->gt($cartTimer)) {
            session()->forget(['cart', 'cart_timer']);
            
            Log::info('Session panier expirée et nettoyée', [
                'user_id' => auth()->id(),
                'expired_at' => $cartTimer
            ]);
        }
    }

    /**
     * Vérifier si le panier a un timer actif
     */
    public function hasActiveTimer()
    {
        $cartTimer = session()->get('cart_timer');
        return $cartTimer && now()->lt($cartTimer);
    }

    /**
     * Prolonger le timer du panier (si nécessaire)
     */
    public function extendTimer()
    {
        if ($this->hasActiveTimer() && !empty(session()->get('cart', []))) {
            session()->put('cart_timer', now()->addMinutes(15));
            
            Log::info('Timer panier prolongé', [
                'user_id' => auth()->id(),
                'new_expiry' => session()->get('cart_timer')
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Timer prolongé de 15 minutes',
                'timeRemaining' => 15,
                'expiresAt' => session()->get('cart_timer')->toISOString()
            ]);
        }
        
        return response()->json([
            'success' => false,
            'message' => 'Aucun timer actif à prolonger'
        ], 400);
    }
}