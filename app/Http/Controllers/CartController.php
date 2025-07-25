<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class CartController extends Controller
{
    public function show()
    {
        $cart = Session::get('cart', []);
        return view('cart.show', compact('cart'));
    }

    public function add(Request $request)
    {
        $request->validate([
            'ticket_type_id' => 'required|exists:ticket_types,id',
            'quantity' => 'required|integer|min:1|max:10'
        ]);

        $cart = Session::get('cart', []);
        $ticketTypeId = $request->ticket_type_id;
        $quantity = $request->quantity;

        if (isset($cart[$ticketTypeId])) {
            $cart[$ticketTypeId]['quantity'] += $quantity;
        } else {
            $ticketType = \App\Models\TicketType::findOrFail($ticketTypeId);
            $cart[$ticketTypeId] = [
                'ticket_type_id' => $ticketTypeId,
                'name' => $ticketType->name,
                'price' => $ticketType->price,
                'quantity' => $quantity,
                'event_title' => $ticketType->event->title,
            ];
        }

        Session::put('cart', $cart);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Billet ajouté au panier',
                'cartCount' => $this->getCartCount()
            ]);
        }

        return redirect()->back()->with('success', 'Billet ajouté au panier');
    }

    public function update(Request $request)
    {
        $request->validate([
            'ticket_type_id' => 'required',
            'quantity' => 'required|integer|min:0|max:10'
        ]);

        $cart = Session::get('cart', []);
        $ticketTypeId = $request->ticket_type_id;

        if ($request->quantity == 0) {
            unset($cart[$ticketTypeId]);
        } else {
            if (isset($cart[$ticketTypeId])) {
                $cart[$ticketTypeId]['quantity'] = $request->quantity;
            }
        }

        Session::put('cart', $cart);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'cartCount' => $this->getCartCount()
            ]);
        }

        return redirect()->back()->with('success', 'Panier mis à jour');
    }

    public function remove(Request $request)
    {
        $cart = Session::get('cart', []);
        $ticketTypeId = $request->ticket_type_id;

        unset($cart[$ticketTypeId]);
        Session::put('cart', $cart);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'cartCount' => $this->getCartCount()
            ]);
        }

        return redirect()->back()->with('success', 'Billet retiré du panier');
    }

    public function clear()
    {
        Session::forget('cart');
        return redirect()->back()->with('success', 'Panier vidé');
    }

    public function data()
    {
        $cart = Session::get('cart', []);
        $totalItems = array_sum(array_column($cart, 'quantity'));
        $totalPrice = 0;

        foreach ($cart as $item) {
            $totalPrice += $item['price'] * $item['quantity'];
        }

        return response()->json([
            'items' => $cart,
            'totalItems' => $totalItems,
            'totalPrice' => $totalPrice,
            'formattedTotal' => number_format($totalPrice, 0, ',', ' ') . ' FCFA'
        ]);
    }

    private function getCartCount()
    {
        $cart = Session::get('cart', []);
        return array_sum(array_column($cart, 'quantity'));
    }
}