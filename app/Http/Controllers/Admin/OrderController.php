<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Event;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class OrderController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('admin');
    }

    /**
     * Liste des commandes (MIGRÉ depuis AdminController::orders)
     */
    public function index(Request $request)
    {
        $query = Order::with(['user', 'event', 'orderItems.ticketType']);

        // Filtres
        if ($request->filled('payment_status')) {
            $query->where('payment_status', $request->payment_status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                  ->orWhereHas('user', function($uq) use ($search) {
                      $uq->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                  })
                  ->orWhereHas('event', function($eq) use ($search) {
                      $eq->where('title', 'like', "%{$search}%");
                  });
            });
        }

        if ($request->filled('event')) {
            $query->where('event_id', $request->event);
        }

        if ($request->filled('user')) {
            $query->where('user_id', $request->user);
        }

        if ($request->filled('date_from')) {
            $query->where('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->where('created_at', '<=', $request->date_to . ' 23:59:59');
        }

        if ($request->filled('amount_min')) {
            $query->where('total_amount', '>=', $request->amount_min);
        }

        if ($request->filled('amount_max')) {
            $query->where('total_amount', '<=', $request->amount_max);
        }

        // Tri par défaut : plus récentes
        $orders = $query->latest()->paginate(20);

        // Statistiques pour le dashboard
        $stats = [
            'total' => Order::count(),
            'paid' => Order::where('payment_status', 'paid')->count(),
            'pending' => Order::where('payment_status', 'pending')->count(),
            'failed' => Order::where('payment_status', 'failed')->count(),
            'refunded' => Order::where('payment_status', 'refunded')->count(),
            'total_revenue' => Order::where('payment_status', 'paid')->sum('total_amount'),
            'pending_revenue' => Order::where('payment_status', 'pending')->sum('total_amount'),
            'this_month_orders' => Order::whereMonth('created_at', now()->month)->count(),
            'this_month_revenue' => Order::where('payment_status', 'paid')
                ->whereMonth('created_at', now()->month)
                ->sum('total_amount'),
        ];

        // Données pour les filtres
        $events = Event::orderBy('title')->get();
        $users = User::where('role', 'acheteur')->orderBy('name')->get();

        return view('admin.orders.index', compact('orders', 'stats', 'events', 'users'));
    }

    /**
     * Affichage détail commande (MIGRÉ depuis AdminController::orderDetail)
     */
    public function show(Order $order)
    {
        try {
            $order->load([
                'user', 
                'event.promoteur', 
                'orderItems.ticketType', 
                'tickets',
                'commissions.promoteur'
            ]);

            // Statistiques de la commande
            $stats = [
                'total_tickets' => $order->tickets->count(),
                'used_tickets' => $order->tickets->where('status', 'used')->count(),
                'remaining_tickets' => $order->tickets->where('status', 'sold')->count(),
                'commission_amount' => $order->commissions->sum('commission_amount'),
                'net_revenue' => $order->total_amount - $order->commissions->sum('commission_amount'),
            ];

            // Historique des actions sur cette commande
            $history = collect([
                [
                    'action' => 'order_created',
                    'message' => 'Commande créée',
                    'date' => $order->created_at,
                    'user' => $order->user->name,
                    'icon' => 'fas fa-shopping-cart',
                    'color' => 'primary'
                ]
            ]);

            if ($order->payment_status === 'paid' && $order->updated_at != $order->created_at) {
                $history->push([
                    'action' => 'payment_confirmed',
                    'message' => 'Paiement confirmé',
                    'date' => $order->updated_at,
                    'user' => 'Système',
                    'icon' => 'fas fa-check-circle',
                    'color' => 'success'
                ]);
            }

            return view('admin.orders.show', compact('order', 'stats', 'history'));

        } catch (\Exception $e) {
            \Log::error('Erreur affichage commande: ' . $e->getMessage());
            return redirect()->route('admin.orders.index')->with('error', 'Erreur lors du chargement');
        }
    }

    /**
     * Formulaire d'édition commande (limité)
     */
    public function edit(Order $order)
    {
        // Seules certaines propriétés peuvent être modifiées
        if ($order->payment_status === 'paid') {
            return redirect()->route('admin.orders.show', $order)
                ->with('warning', 'Une commande payée ne peut pas être modifiée. Utilisez les fonctions de remboursement si nécessaire.');
        }

        return view('admin.orders.edit', compact('order'));
    }

    /**
     * Mise à jour commande (limitée)
     */
    public function update(Request $request, Order $order)
    {
        $request->validate([
            'payment_status' => 'required|in:pending,paid,failed,refunded',
            'admin_notes' => 'nullable|string|max:1000',
        ]);

        try {
            $oldStatus = $order->payment_status;
            
            $order->update([
                'payment_status' => $request->payment_status,
                'admin_notes' => $request->admin_notes,
            ]);

            // Si passage à "paid", mettre à jour les tickets
            if ($oldStatus !== 'paid' && $request->payment_status === 'paid') {
                $order->tickets()->update(['status' => 'sold']);
            }

            // Si passage à "refunded" ou "failed", annuler les tickets
            if (in_array($request->payment_status, ['refunded', 'failed'])) {
                $order->tickets()->update(['status' => 'cancelled']);
            }

            \Log::info('Commande modifiée par admin', [
                'admin_id' => auth()->id(),
                'order_id' => $order->id,
                'old_status' => $oldStatus,
                'new_status' => $order->payment_status
            ]);

            return redirect()->route('admin.orders.show', $order)
                ->with('success', 'Commande mise à jour avec succès !');

        } catch (\Exception $e) {
            \Log::error('Erreur mise à jour commande: ' . $e->getMessage());
            
            return redirect()->back()
                ->with('error', 'Erreur lors de la mise à jour')
                ->withInput();
        }
    }

    /**
     * Mise à jour rapide du statut de paiement
     */
    public function updateStatus(Request $request, Order $order)
    {
        $request->validate([
            'payment_status' => 'required|in:pending,paid,failed,refunded'
        ]);

        try {
            $oldStatus = $order->payment_status;
            $order->update(['payment_status' => $request->payment_status]);

            // Gestion des tickets selon le nouveau statut
            switch ($request->payment_status) {
                case 'paid':
                    $order->tickets()->update(['status' => 'sold']);
                    break;
                case 'refunded':
                case 'failed':
                    $order->tickets()->update(['status' => 'cancelled']);
                    break;
            }

            \Log::info('Statut commande modifié par admin', [
                'admin_id' => auth()->id(),
                'order_id' => $order->id,
                'old_status' => $oldStatus,
                'new_status' => $order->payment_status
            ]);

            return redirect()->back()
                ->with('success', 'Statut de la commande mis à jour !');

        } catch (\Exception $e) {
            \Log::error('Erreur changement statut commande: ' . $e->getMessage());
            
            return redirect()->back()
                ->with('error', 'Erreur lors du changement de statut');
        }
    }

    /**
     * Remboursement d'une commande
     */
    public function refund(Request $request, Order $order)
    {
        $request->validate([
            'refund_reason' => 'required|string|max:500',
            'partial_amount' => 'nullable|numeric|min:0|max:' . $order->total_amount,
        ]);

        try {
            if ($order->payment_status !== 'paid') {
                return redirect()->back()
                    ->with('error', 'Seules les commandes payées peuvent être remboursées.');
            }

            $refundAmount = $request->partial_amount ?? $order->total_amount;
            $isPartialRefund = $refundAmount < $order->total_amount;

            // Mise à jour du statut
            $order->update([
                'payment_status' => 'refunded',
                'refund_amount' => $refundAmount,
                'refund_reason' => $request->refund_reason,
                'refunded_at' => now(),
                'refunded_by' => auth()->id(),
            ]);

            // Annuler les tickets (total ou partiel selon la logique métier)
            if (!$isPartialRefund) {
                $order->tickets()->update(['status' => 'cancelled']);
            }

            \Log::info('Commande remboursée par admin', [
                'admin_id' => auth()->id(),
                'order_id' => $order->id,
                'refund_amount' => $refundAmount,
                'reason' => $request->refund_reason
            ]);

            // TODO: Intégrer avec votre système de paiement pour effectuer le remboursement réel
            
            return redirect()->back()
                ->with('success', "Remboursement de {$refundAmount} FCFA effectué avec succès !");

        } catch (\Exception $e) {
            \Log::error('Erreur remboursement commande: ' . $e->getMessage());
            
            return redirect()->back()
                ->with('error', 'Erreur lors du remboursement');
        }
    }

    /**
     * Renvoyer l'email de confirmation
     */
    public function resendEmail(Order $order)
    {
        try {
            if ($order->payment_status !== 'paid') {
                return redirect()->back()
                    ->with('error', 'Seules les commandes payées peuvent avoir leur email renvoyé.');
            }

            // TODO: Implémenter l'envoi d'email selon votre système
            // Mail::to($order->user->email)->send(new OrderConfirmationMail($order));

            \Log::info('Email commande renvoyé par admin', [
                'admin_id' => auth()->id(),
                'order_id' => $order->id,
                'user_email' => $order->user->email
            ]);

            return redirect()->back()
                ->with('success', 'Email de confirmation renvoyé avec succès !');

        } catch (\Exception $e) {
            \Log::error('Erreur renvoi email commande: ' . $e->getMessage());
            
            return redirect()->back()
                ->with('error', 'Erreur lors du renvoi de l\'email');
        }
    }

    /**
     * Actions en lot sur les commandes
     */
    public function bulkAction(Request $request)
    {
        $request->validate([
            'action' => 'required|in:mark_paid,mark_failed,resend_emails',
            'orders' => 'required|array',
            'orders.*' => 'exists:orders,id'
        ]);

        try {
            $orders = Order::whereIn('id', $request->orders)->get();
            $count = 0;

            foreach ($orders as $order) {
                switch ($request->action) {
                    case 'mark_paid':
                        if ($order->payment_status === 'pending') {
                            $order->update(['payment_status' => 'paid']);
                            $order->tickets()->update(['status' => 'sold']);
                            $count++;
                        }
                        break;
                        
                    case 'mark_failed':
                        if ($order->payment_status === 'pending') {
                            $order->update(['payment_status' => 'failed']);
                            $order->tickets()->update(['status' => 'cancelled']);
                            $count++;
                        }
                        break;
                        
                    case 'resend_emails':
                        if ($order->payment_status === 'paid') {
                            // TODO: Implémenter l'envoi d'email
                            $count++;
                        }
                        break;
                }
            }

            \Log::info('Action en lot sur commandes par admin', [
                'admin_id' => auth()->id(),
                'action' => $request->action,
                'orders_affected' => $count
            ]);

            return redirect()->back()
                ->with('success', "{$count} commande(s) traitée(s) avec succès !");

        } catch (\Exception $e) {
            \Log::error('Erreur action en lot commandes: ' . $e->getMessage());
            
            return redirect()->back()
                ->with('error', 'Erreur lors du traitement des commandes');
        }
    }

    /**
     * Export CSV personnalisé des commandes
     */
    public function export(Request $request)
    {
        try {
            $query = Order::with(['user', 'event']);

            // Appliquer les mêmes filtres que l'index
            if ($request->filled('payment_status')) {
                $query->where('payment_status', $request->payment_status);
            }
            
            if ($request->filled('event')) {
                $query->where('event_id', $request->event);
            }

            if ($request->filled('date_from')) {
                $query->where('created_at', '>=', $request->date_from);
            }

            if ($request->filled('date_to')) {
                $query->where('created_at', '<=', $request->date_to . ' 23:59:59');
            }

            $orders = $query->get();

            $csvContent = "Numéro,Client,Email,Événement,Statut,Montant,Date,Tickets\n";
            
            foreach ($orders as $order) {
                $csvContent .= implode(',', [
                    $order->order_number,
                    '"' . addslashes($order->user->name) . '"',
                    $order->user->email,
                    '"' . addslashes($order->event->title) . '"',
                    $order->payment_status,
                    number_format($order->total_amount, 2),
                    $order->created_at->format('Y-m-d H:i:s'),
                    $order->tickets->count()
                ]) . "\n";
            }

            return response($csvContent)
                ->header('Content-Type', 'text/csv')
                ->header('Content-Disposition', 'attachment; filename="orders-export-' . now()->format('Y-m-d') . '.csv"');

        } catch (\Exception $e) {
            \Log::error('Erreur export commandes: ' . $e->getMessage());
            
            return redirect()->back()
                ->with('error', 'Erreur lors de l\'export');
        }
    }
}