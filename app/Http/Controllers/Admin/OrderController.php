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
use App\Http\Controllers\Admin\Concerns\ResolvesStatsPeriod;
use App\Services\Stats\DashboardStats;
use App\Services\Stats\Scope;

class OrderController extends Controller
{
    use ResolvesStatsPeriod;

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
        // Période lue AVANT le try : une période invalide doit produire une erreur de validation lisible.
        $period = $this->statsPeriod($request, 'admin.orders.index');

        try {
            $query = Order::with(['user', 'event'])->withSum('orderItems as tickets_quantity', 'quantity');

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

            // Une seule période (DatePeriod) pour les cartes ET la liste : commandes créées dans la période
            $query->inPeriod($period, 'created');

            if ($request->filled('amount_min')) {
                $query->where('total_amount', '>=', $request->amount_min);
            }

            if ($request->filled('amount_max')) {
                $query->where('total_amount', '<=', $request->amount_max);
            }

            // Tri par défaut : plus récentes
            $orders = $query->latest()->paginate(20)->withQueryString();

            // Chiffres : services Stats (commandes créées / payées de la période, ventes hors frais)
            $summary = app(DashboardStats::class)->summary(Scope::admin(), $period);

            // Données pour les filtres - Avec protection contre null
            $events = Event::orderBy('title')->get();
            $users = User::where('role', 'acheteur')->orderBy('name')->get();

            return view('admin.orders.index', compact('orders', 'summary', 'period', 'events', 'users'));

        } catch (\Exception $e) {
            $this->logFailure('Erreur admin orders index', $e);
            
            // En cas d'erreur : aucun chiffre inventé (les cartes affichent « — »)
            $orders = Order::paginate(20);
            $summary = [];
            $events = collect();
            $users = collect();

            return view('admin.orders.index', compact('orders', 'summary', 'period', 'events', 'users'))
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
            $this->logFailure('Erreur affichage commande', $e, $order);
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

            // Toutes les transitions passent par le service (table de transitions, verrou, stock, commission)
            app(OrderPaymentService::class)->changeStatus(
                $order,
                $request->payment_status,
                $request->input('admin_notes'),
                auth()->user()
            );

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
        } catch (\Throwable $e) {
            $this->logFailure('Erreur mise à jour commande', $e, $order);

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
            app(OrderPaymentService::class)->changeStatus($order, $request->payment_status, null, auth()->user());

            return response()->json([
                'success' => true,
                'message' => 'Statut mis à jour avec succès'
            ]);

        } catch (OrderPaymentException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        } catch (\Throwable $e) {
            $this->logFailure('Erreur update status', $e, $order);

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

        } catch (\Throwable $e) {
            $this->logFailure('Erreur confirmation paiement admin', $e, $order);

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
        $request->validate(['reason' => 'nullable|string|max:1000']);

        if ($order->payment_status !== 'paid') {
            return redirect()->back()->with('error', 'Seules les commandes payées peuvent être remboursées');
        }

        try {
            app(OrderPaymentService::class)->changeStatus(
                $order,
                'refunded',
                $request->input('reason') ?: 'Remboursement manuel par administrateur',
                auth()->user()
            );

            // TODO: Intégrer avec le système de paiement pour remboursement réel

            Log::info('Commande remboursée par admin', [
                'admin_id' => auth()->id(),
                'order_id' => $order->id,
                'amount' => $order->total_amount
            ]);

            return redirect()->route('admin.orders.show', $order)
                ->with('success', 'Commande remboursée avec succès !');

        } catch (OrderPaymentException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            $this->logFailure('Erreur remboursement commande', $e, $order);

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
                ]);

                return redirect()->back()
                    ->with('success', 'Email de confirmation renvoyé avec succès !');
            } else {
                return redirect()->back()
                    ->with('error', 'Erreur lors de l\'envoi de l\'email. Vérifiez les logs.');
            }

        } catch (\Throwable $e) {
            $this->logFailure('Erreur renvoi email commande', $e, $order);
            
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
            $failures = [];
            $emailService = app(\App\Services\EmailService::class);
            $service = app(OrderPaymentService::class);

            foreach ($orders as $order) {
                switch ($request->action) {
                    case 'mark_paid':
                    case 'mark_failed':
                        if (!in_array($order->payment_status, ['pending', 'processing'], true)) {
                            break;
                        }
                        try {
                            // Table de transitions, verrou, stock, commission et email après commit
                            $service->changeStatus($order, $request->action === 'mark_paid' ? 'paid' : 'failed', null, auth()->user());
                            $count++;
                        } catch (OrderPaymentException $e) {
                            Log::warning('Action en lot refusée', ['order_id' => $order->id, 'action' => $request->action]);
                            $failures[] = $order->order_number . ' (' . $e->getMessage() . ')';
                        }
                        break;

                    case 'resend_emails':
                        if ($order->payment_status === 'paid') {
                            $emailService->sendPaymentConfirmation($order);
                            $count++;
                        }
                        break;
                }
            }

            Log::info('Action en lot sur commandes par admin', [
                'admin_id' => auth()->id(),
                'action' => $request->action,
                'orders_affected' => $count,
                'orders_failed' => count($failures),
            ]);

            $redirect = redirect()->back();
            if ($failures !== []) {
                $redirect = $redirect->with('error', 'Échec pour : ' . implode(' ; ', $failures));
            }

            return $redirect->with('success', "{$count} commande(s) traitée(s) avec succès !");

        } catch (\Throwable $e) {
            $this->logFailure('Erreur action en lot commandes', $e);

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
                'filters' => $request->only(['payment_status', 'event', 'date_from', 'date_to'])
            ]);

            return response()->stream($callback, 200, $headers);

        } catch (\Exception $e) {
            $this->logFailure('Erreur export commandes', $e);
            
            return redirect()->back()
                ->with('error', 'Erreur lors de l\'export des commandes');
        }
    }

    /** Journalise une erreur interne sans message brut ni PII (les messages SQL contiennent des valeurs). */
    private function logFailure(string $context, \Throwable $e, ?Order $order = null): void
    {
        app(\App\Services\CheckoutService::class)->logFailure($context, $e, [
            'order_id' => $order?->id,
            'admin_id' => auth()->id(),
        ]);
    }
}
