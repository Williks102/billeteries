<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Event;
use App\Models\User;
use App\Exceptions\OrderPaymentException;
use App\Services\OrderPaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

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
        try {
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

            // Statistiques pour le dashboard - CORRIGÉ
            $stats = [
                'total' => Order::count(),
                'paid' => Order::where('payment_status', 'paid')->count(),
                'pending' => Order::where('payment_status', 'pending')->count(),
                'failed' => Order::where('payment_status', 'failed')->count(),
                'refunded' => Order::where('payment_status', 'refunded')->count(),
                'total_revenue' => Order::where('payment_status', 'paid')->sum('total_amount') ?? 0,
                'pending_revenue' => Order::where('payment_status', 'pending')->sum('total_amount') ?? 0,
                'this_month_orders' => Order::whereMonth('created_at', now()->month)->count(),
                'this_month_revenue' => Order::where('payment_status', 'paid')
                    ->whereMonth('created_at', now()->month)
                    ->sum('total_amount') ?? 0, // ✅ CORRIGÉ : Ajout ?? 0 et fermeture correcte
            ];

            // Données pour les filtres - Avec protection contre null
            $events = Event::orderBy('title')->get();
            $users = User::where('role', 'acheteur')->orderBy('name')->get();

            return view('admin.orders.index', compact('orders', 'stats', 'events', 'users'));

        } catch (\Exception $e) {
            Log::error('Erreur admin orders index: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            
            // Valeurs par défaut en cas d'erreur
            $orders = Order::paginate(20);
            $stats = [
                'total' => 0,
                'paid' => 0,
                'pending' => 0,
                'failed' => 0,
                'refunded' => 0,
                'total_revenue' => 0,
                'pending_revenue' => 0,
                'this_month_orders' => 0,
                'this_month_revenue' => 0,
            ];
            $events = collect();
            $users = collect();

            return view('admin.orders.index', compact('orders', 'stats', 'events', 'users'))
                ->with('error', 'Erreur lors du chargement des commandes. Veuillez réessayer.');
        }
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
                'commission.promoteur'
            ]);

            // Statistiques de la commande - Avec protection contre null
            $stats = [
                'total_tickets' => $order->tickets->count(),
                'used_tickets' => $order->tickets->where('status', 'used')->count(),
                'remaining_tickets' => $order->tickets->where('status', 'sold')->count(),
                'commission_amount' => optional($order->commission)->commission_amount ?? 0,
                'net_revenue' => $order->total_amount - (optional($order->commission)->commission_amount ?? 0),
            ];

            // Historique des actions sur cette commande
            $history = collect([
                [
                    'action' => 'order_created',
                    'message' => 'Commande créée',
                    'date' => $order->created_at,
                    'user' => $order->user ? $order->user->name : 'Utilisateur inconnu',
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
            Log::error('Erreur affichage commande: ' . $e->getMessage());
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

            if ($request->payment_status === 'paid' && $oldStatus !== 'paid') {
                // Passage à payé : toujours via OrderPaymentService (verrou, idempotence, stock, commission)
                app(OrderPaymentService::class)->confirmPayment($order, null, auth()->user());
            } else {
                $order->update([
                    'payment_status' => $request->payment_status,
                    'admin_notes' => $request->admin_notes,
                ]);
            }

            // Si passage à "refunded" ou "failed", annuler les tickets
            if (in_array($request->payment_status, ['refunded', 'failed'])) {
                $order->tickets()->update(['status' => 'cancelled']);
            }

            Log::info('Commande modifiée par admin', [
                'admin_id' => auth()->id(),
                'order_id' => $order->id,
                'old_status' => $oldStatus,
                'new_status' => $order->payment_status
            ]);

            return redirect()->route('admin.orders.show', $order)
                ->with('success', 'Commande mise à jour avec succès !');

        } catch (OrderPaymentException $e) {
            return redirect()->back()->with('error', $e->getMessage())->withInput();
        } catch (\Exception $e) {
            Log::error('Erreur mise à jour commande: ' . $e->getMessage());
            
            return redirect()->back()
                ->with('error', 'Erreur lors de la mise à jour de la commande')
                ->withInput();
        }
    }

    /**
     * Mettre à jour le statut de paiement uniquement
     */
    public function updateStatus(Request $request, Order $order)
    {
        $request->validate([
            'payment_status' => 'required|in:pending,paid,failed,refunded',
        ]);

        try {
            if ($request->payment_status === 'paid') {
                // Passage à payé : verrou + idempotence + stock + commission unique (OrderPaymentService)
                app(OrderPaymentService::class)->confirmPayment($order, null, auth()->user());
            } else {
                $order->update(['payment_status' => $request->payment_status]);

                if (in_array($request->payment_status, ['failed', 'refunded'], true)) {
                    $order->tickets()->update(['status' => 'cancelled']);
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Statut mis à jour avec succès'
            ]);

        } catch (OrderPaymentException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        } catch (\Exception $e) {
            Log::error('Erreur update status: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour'
            ], 500);
        }
    }

    /**
     * Confirmer manuellement la réception du paiement (paiement manuel : virement, Mobile Money, espèces).
     * Idempotent ; une commande expirée (failed) n'est confirmée que si le stock peut être re-réservé.
     */
    public function confirmPayment(Request $request, Order $order)
    {
        $validated = $request->validate([
            'payment_reference' => 'nullable|string|max:100',
        ]);

        try {
            $alreadyPaid = $order->payment_status === 'paid';

            app(OrderPaymentService::class)->confirmPayment(
                $order,
                $validated['payment_reference'] ?? null,
                auth()->user()
            );

            $message = $alreadyPaid ? 'Cette commande est déjà payée.' : 'Paiement confirmé, billets émis.';

            if ($request->expectsJson()) {
                return response()->json(['success' => true, 'message' => $message]);
            }

            return redirect()->route('admin.orders.show', $order)->with('success', $message);

        } catch (OrderPaymentException $e) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
            }

            return redirect()->route('admin.orders.show', $order)->with('error', $e->getMessage());

        } catch (\Exception $e) {
            Log::error('Erreur confirmation paiement admin', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);

            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'Erreur lors de la confirmation'], 500);
            }

            return redirect()->back()->with('error', 'Erreur lors de la confirmation du paiement');
        }
    }

    /**
     * Rembourser une commande
     */
    public function refund(Request $request, Order $order)
    {
        if ($order->payment_status !== 'paid') {
            return redirect()->back()->with('error', 'Seules les commandes payées peuvent être remboursées');
        }

        try {
            // Marquer comme remboursée
            $order->update([
                'payment_status' => 'refunded',
                'admin_notes' => $request->reason ?? 'Remboursement manuel par administrateur'
            ]);

            // Annuler tous les tickets
            $order->tickets()->update(['status' => 'cancelled']);

            // TODO: Intégrer avec le système de paiement pour remboursement réel
            
            Log::info('Commande remboursée par admin', [
                'admin_id' => auth()->id(),
                'order_id' => $order->id,
                'amount' => $order->total_amount
            ]);

            return redirect()->route('admin.orders.show', $order)
                ->with('success', 'Commande remboursée avec succès !');

        } catch (\Exception $e) {
            Log::error('Erreur remboursement commande: ' . $e->getMessage());
            
            return redirect()->back()
                ->with('error', 'Erreur lors du remboursement');
        }
    }

    /**
     * Renvoyer l'email de confirmation
     */
    public function resendEmail(Order $order)
    {
        if ($order->payment_status !== 'paid') {
            return redirect()->back()->with('error', 'Seules les commandes payées peuvent recevoir un email de confirmation');
        }

        try {
            // ✅ Utiliser votre EmailService existant
            $emailService = app(\App\Services\EmailService::class);
            
            // Envoyer l'email de confirmation avec les billets
            $success = $emailService->sendPaymentConfirmation($order);
            
            if ($success) {
                Log::info('Email renvoyé par admin', [
                    'admin_id' => auth()->id(),
                    'order_id' => $order->id,
                    'user_email' => $order->user->email
                ]);

                return redirect()->back()
                    ->with('success', 'Email de confirmation renvoyé avec succès !');
            } else {
                return redirect()->back()
                    ->with('error', 'Erreur lors de l\'envoi de l\'email. Vérifiez les logs.');
            }

        } catch (\Exception $e) {
            Log::error('Erreur renvoi email commande: ' . $e->getMessage());
            
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
            $emailService = app(\App\Services\EmailService::class);

            foreach ($orders as $order) {
                switch ($request->action) {
                    case 'mark_paid':
                        if (in_array($order->payment_status, ['pending', 'processing'], true)) {
                            try {
                                // Verrou, idempotence, commission unique et email après commit
                                app(OrderPaymentService::class)->confirmPayment($order, null, auth()->user());
                                $count++;
                            } catch (OrderPaymentException $e) {
                                Log::warning('Confirmation en lot refusée', ['order_id' => $order->id]);
                            }
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
                            // ✅ Utiliser le service email existant
                            $emailService->sendPaymentConfirmation($order);
                            $count++;
                        }
                        break;
                }
            }

            Log::info('Action en lot sur commandes par admin', [
                'admin_id' => auth()->id(),
                'action' => $request->action,
                'orders_affected' => $count
            ]);

            return redirect()->back()
                ->with('success', "{$count} commande(s) traitée(s) avec succès !");

        } catch (\Exception $e) {
            Log::error('Erreur action en lot commandes: ' . $e->getMessage());
            
            return redirect()->back()
                ->with('error', 'Erreur lors du traitement des commandes');
        }
    }

    /**
     * Export des commandes - ✅ Améliorer l'export existant
     */
    public function export(Request $request)
    {
        try {
            $query = Order::with(['user', 'event', 'orderItems.ticketType']);

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

            $orders = $query->orderBy('created_at', 'desc')->get();

            // ✅ Export CSV détaillé avec en-têtes français
            $filename = 'commandes_export_' . date('Y_m_d_H_i_s') . '.csv';
            
            $headers = [
                'Content-Type' => 'text/csv; charset=utf-8',
                'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            ];

            $callback = function() use ($orders) {
                $file = fopen('php://output', 'w');
                
                // BOM UTF-8 pour Excel français
                fwrite($file, "\xEF\xBB\xBF");
                
                // En-têtes détaillés
                fputcsv($file, [
                    'Numéro Commande',
                    'Date Commande', 
                    'Client',
                    'Email Client',
                    'Téléphone',
                    'Événement',
                    'Date Événement',
                    'Lieu',
                    'Statut Paiement',
                    'Montant Total (FCFA)',
                    'Nombre de Billets',
                    'Types de Billets',
                    'Codes Billets'
                ], ';');
                
                foreach ($orders as $order) {
                    $ticketTypes = $order->orderItems->map(function($item) {
                        return $item->ticketType->name . ' (x' . $item->quantity . ')';
                    })->implode(', ');
                    
                    $ticketCodes = $order->tickets->pluck('ticket_code')->implode(', ');
                    
                    fputcsv($file, [
                        $order->order_number,
                        $order->created_at->format('d/m/Y H:i'),
                        $order->user->name,
                        $order->user->email,
                        $order->user->phone ?? 'N/A',
                        $order->event->title ?? 'N/A',
                        $order->event && $order->event->event_date ? $order->event->event_date->format('d/m/Y') : 'N/A',
                        $order->event->venue ?? 'N/A',
                        ucfirst($order->payment_status),
                        number_format($order->total_amount, 0, ',', ' '),
                        $order->tickets->count(),
                        $ticketTypes,
                        $ticketCodes
                    ], ';');
                }
                
                fclose($file);
            };

            Log::info('Export commandes par admin', [
                'admin_id' => auth()->id(),
                'total_orders' => $orders->count(),
                'filters' => $request->all()
            ]);

            return response()->stream($callback, 200, $headers);

        } catch (\Exception $e) {
            Log::error('Erreur export commandes: ' . $e->getMessage());
            
            return redirect()->back()
                ->with('error', 'Erreur lors de l\'export des commandes');
        }
    }
}