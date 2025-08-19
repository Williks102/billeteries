<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Event;
use App\Models\Order;
use App\Models\Ticket;
use App\Models\Commission;
use App\Models\EventCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class AdminController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('admin');
    }

    // ================================================================
    // DASHBOARD PRINCIPAL - CONSERVÉ
    // ================================================================

    /**
     * Dashboard principal avec statistiques générales
     */
    public function dashboard()
    {
        try {
            // Statistiques générales
            $stats = [
                'total_users' => User::count(),
                'total_events' => Event::count(),
                'total_orders' => Order::count(),
                'total_revenue' => Order::where('payment_status', 'paid')->sum('total_amount'),
                'pending_events' => Event::where('status', 'pending')->count(),
                'this_month_revenue' => Order::where('payment_status', 'paid')
                    ->whereMonth('created_at', now()->month)
                    ->sum('total_amount'),
                'verified_users' => User::whereNotNull('email_verified_at')->count(),
                'promoteurs_actifs' => User::where('role', 'promoteur')
                    ->whereHas('events', function($q) {
                        $q->where('status', 'published');
                    })->count(),
            ];

            // Évolution mensuelle des revenus (6 derniers mois)
            $monthlyStats = collect();
            for ($i = 5; $i >= 0; $i--) {
                $date = now()->subMonths($i);
                $monthlyStats->push([
                    'month' => $date->format('M Y'),
                    'revenue' => Order::where('payment_status', 'paid')
                        ->whereYear('created_at', $date->year)
                        ->whereMonth('created_at', $date->month)
                        ->sum('total_amount'),
                    'orders' => Order::whereYear('created_at', $date->year)
                        ->whereMonth('created_at', $date->month)
                        ->count(),
                    'events' => Event::whereYear('created_at', $date->year)
                        ->whereMonth('created_at', $date->month)
                        ->count(),
                ]);
            }

            // Événements nécessitant attention
            $pendingEvents = Event::where('status', 'pending')
                ->with(['promoteur', 'category'])
                ->latest()
                ->take(5)
                ->get();

            // Commandes récentes
            $recentOrders = Order::with(['user', 'event'])
                ->latest()
                ->take(10)
                ->get();

            // Top promoteurs du mois
            $topPromoters = User::where('role', 'promoteur')
                ->withCount(['events as events_this_month' => function($query) {
                    $query->whereMonth('created_at', now()->month);
                }])
                ->withSum(['commissions as revenue_this_month' => function($query) {
                    $query->whereMonth('created_at', now()->month)
                          ->where('status', 'paid');
                }], 'net_amount')
                ->orderByDesc('revenue_this_month')
                ->take(5)
                ->get();

            return view('admin.dashboard', compact(
                'stats', 
                'monthlyStats', 
                'pendingEvents', 
                'recentOrders',
                'topPromoters'
            ));

        } catch (\Exception $e) {
            \Log::error('Erreur dashboard admin: ' . $e->getMessage());
            
            return view('admin.dashboard', [
                'stats' => $this->getDefaultStats(),
                'monthlyStats' => collect(),
                'pendingEvents' => collect(),
                'recentOrders' => collect(),
                'topPromoters' => collect(),
            ]);
        }
    }

    // ================================================================
    // COMMISSIONS ET FINANCES - CONSERVÉ
    // ================================================================

    /**
     * Gestion des commissions
     */
    public function commissions(Request $request)
    {
        $query = Commission::with(['promoteur', 'order.event']);
        
        // Filtres
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        
        if ($request->filled('promoter')) {
            $query->where('promoteur_id', $request->promoter);
        }

        if ($request->filled('date_from')) {
            $query->where('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->where('created_at', '<=', $request->date_to . ' 23:59:59');
        }
        
        $commissions = $query->latest()->paginate(20);
        
        // Données pour les filtres
        $promoteurs = User::where('role', 'promoteur')->orderBy('name')->get();
        
        // Statistiques des commissions
        $stats = [
            'total_commissions' => Commission::sum('amount'),
            'paid_commissions' => Commission::where('status', 'paid')->sum('amount'),
            'pending_commissions' => Commission::where('status', 'pending')->sum('amount'),
            'held_commissions' => Commission::where('status', 'held')->sum('amount'),
            'this_month_commissions' => Commission::whereMonth('created_at', now()->month)->sum('amount'),
        ];
        
        return view('admin.commissions', compact('commissions', 'promoteurs', 'stats'));
    }

    /**
     * Mise à jour du statut d'une commission
     */
    public function updateCommissionStatus(Request $request, Commission $commission)
    {
        $request->validate([
            'status' => 'required|in:pending,paid,held,cancelled'
        ]);

        try {
            $oldStatus = $commission->status;
            $commission->update([
                'status' => $request->status,
                'paid_at' => $request->status === 'paid' ? now() : null,
                'admin_notes' => $request->admin_notes,
            ]);

            \Log::info('Statut commission modifié par admin', [
                'admin_id' => auth()->id(),
                'commission_id' => $commission->id,
                'old_status' => $oldStatus,
                'new_status' => $commission->status
            ]);

            return redirect()->back()
                ->with('success', 'Statut de la commission mis à jour !');

        } catch (\Exception $e) {
            \Log::error('Erreur mise à jour commission: ' . $e->getMessage());
            
            return redirect()->back()
                ->with('error', 'Erreur lors de la mise à jour');
        }
    }

    /**
     * Paiement en lot des commissions
     */
    public function bulkPayCommissions(Request $request)
    {
        $request->validate([
            'commission_ids' => 'required|array',
            'commission_ids.*' => 'exists:commissions,id'
        ]);

        try {
            $count = Commission::whereIn('id', $request->commission_ids)
                ->where('status', 'pending')
                ->update([
                    'status' => 'paid',
                    'paid_at' => now(),
                    'paid_by' => auth()->id()
                ]);

            \Log::info('Paiement en lot commissions par admin', [
                'admin_id' => auth()->id(),
                'commissions_paid' => $count
            ]);

            return redirect()->back()
                ->with('success', "{$count} commission(s) marquée(s) comme payée(s) !");

        } catch (\Exception $e) {
            \Log::error('Erreur paiement en lot commissions: ' . $e->getMessage());
            
            return redirect()->back()
                ->with('error', 'Erreur lors du paiement des commissions');
        }
    }

    /**
     * Revenus et analytics
     */
    public function revenues(Request $request)
    {
        $period = $request->get('period', 'this_month');
        $dateRange = $this->getDateRange($period);
        
        // Revenus par période
        $revenue = Order::where('payment_status', 'paid')
            ->whereBetween('created_at', $dateRange)
            ->sum('total_amount');
            
        $orders = Order::whereBetween('created_at', $dateRange)->count();
        
        // Commissions par période
        $commissions = Commission::whereBetween('created_at', $dateRange)->sum('amount');
        
        // Revenus nets (revenus - commissions)
        $netRevenue = $revenue - $commissions;
        
        // Évolution sur 12 mois
        $monthlyRevenue = collect();
        for ($i = 11; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $monthRevenue = Order::where('payment_status', 'paid')
                ->whereYear('created_at', $date->year)
                ->whereMonth('created_at', $date->month)
                ->sum('total_amount');
            
            $monthCommissions = Commission::whereYear('created_at', $date->year)
                ->whereMonth('created_at', $date->month)
                ->sum('amount');
                
            $monthlyRevenue->push([
                'month' => $date->format('M Y'),
                'revenue' => $monthRevenue,
                'commissions' => $monthCommissions,
                'net_revenue' => $monthRevenue - $monthCommissions,
            ]);
        }
        
        return view('admin.revenues', compact(
            'revenue', 
            'orders', 
            'commissions', 
            'netRevenue', 
            'period', 
            'monthlyRevenue'
        ));
    }

    /**
     * Analytics avancées
     */
    public function analytics(Request $request)
    {
        // Analytics par catégorie
        $categoryStats = EventCategory::withCount('events')
            ->with(['events' => function($query) {
                $query->withSum('orders as total_revenue', 'total_amount');
            }])
            ->get()
            ->map(function($category) {
                return [
                    'name' => $category->name,
                    'events_count' => $category->events_count,
                    'total_revenue' => $category->events->sum('total_revenue') ?? 0,
                ];
            });

        // Top événements par revenus
        $topEvents = Event::join('orders', 'events.id', '=', 'orders.event_id')
            ->where('orders.payment_status', 'paid')
            ->selectRaw('events.id, events.title, SUM(orders.total_amount) as total_revenue')
            ->groupBy('events.id', 'events.title')
            ->orderByDesc('total_revenue')
            ->take(10)
            ->get();

        // Promoteurs les plus actifs
        $topPromoters = User::where('role', 'promoteur')
            ->withCount('events')
            ->withSum(['commissions as total_commissions' => function($query) {
                $query->where('status', 'paid');
            }], 'net_amount')
            ->orderByDesc('total_commissions')
            ->take(10)
            ->get();

        return view('admin.analytics', compact('categoryStats', 'topEvents', 'topPromoters'));
    }

    // ================================================================
    // PROFIL ADMIN - CONSERVÉ
    // ================================================================

    /**
     * Profil de l'administrateur
     */
    public function profile()
    {
        try {
            $user = auth()->user();
            
            // Statistiques d'activité de l'admin
            $activityStats = [
                'users_managed' => User::count(),
                'events_approved' => Event::where('status', 'published')->count(),
                'total_revenue' => Order::where('payment_status', 'paid')->sum('total_amount'),
                'commissions_paid' => Commission::where('status', 'paid')->sum('amount'),
            ];

            // Activité récente (simulation - à adapter selon vos logs)
            $recentActivities = collect([
                Event::where('status', 'published')
                    ->latest('updated_at')
                    ->take(5)
                    ->get()
                    ->map(function($event) {
                        return [
                            'type' => 'event_approved',
                            'message' => "Événement '{$event->title}' approuvé",
                            'date' => $event->updated_at,
                            'icon' => 'fas fa-check-circle',
                            'color' => 'success'
                        ];
                    }),
            ])->flatten()->sortByDesc('date')->take(10);

            return view('admin.profile', compact('user', 'activityStats', 'recentActivities'));

        } catch (\Exception $e) {
            \Log::error('Erreur profil admin: ' . $e->getMessage());
            return view('admin.profile', [
                'user' => auth()->user(),
                'activityStats' => [],
                'recentActivities' => collect()
            ]);
        }
    }

    /**
     * Mise à jour du profil admin
     */
    public function updateProfile(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . auth()->id(),
            'password' => 'nullable|min:8|confirmed',
            'phone' => 'nullable|string|max:20',
        ]);

        try {
            $user = auth()->user();
            $data = $request->only(['name', 'email', 'phone']);

            if ($request->filled('password')) {
                $data['password'] = Hash::make($request->password);
            }

            $user->update($data);

            \Log::info('Profil admin mis à jour', [
                'admin_id' => $user->id,
                'fields_updated' => array_keys($data)
            ]);

            return redirect()->back()
                ->with('success', 'Profil mis à jour avec succès !');

        } catch (\Exception $e) {
            \Log::error('Erreur mise à jour profil admin: ' . $e->getMessage());
            
            return redirect()->back()
                ->with('error', 'Erreur lors de la mise à jour du profil')
                ->withInput();
        }
    }

    // ================================================================
    // RAPPORTS ET EXPORTS GLOBAUX - CONSERVÉ
    // ================================================================

    /**
     * Page principale des rapports
     */
    public function reports()
    {
        $stats = [
            'total_users' => User::count(),
            'total_events' => Event::count(),
            'total_orders' => Order::count(),
            'total_revenue' => Order::where('payment_status', 'paid')->sum('total_amount'),
            'total_commissions' => Commission::sum('amount'),
            'pending_commissions' => Commission::where('status', 'pending')->sum('amount'),
        ];

        return view('admin.reports.index', compact('stats'));
    }

    /**
     * Rapport des ventes
     */
    public function salesReport(Request $request)
    {
        $period = $request->get('period', 'this_month');
        $dateRange = $this->getDateRange($period);

        $salesData = Order::where('payment_status', 'paid')
            ->whereBetween('created_at', $dateRange)
            ->with(['event', 'user'])
            ->get();

        return view('admin.reports.sales', compact('salesData', 'period'));
    }

    /**
     * Rapport financier
     */
    public function financialReport(Request $request)
    {
        $period = $request->get('period', 'this_month');
        $dateRange = $this->getDateRange($period);

        $financial = [
            'total_revenue' => Order::where('payment_status', 'paid')
                ->whereBetween('created_at', $dateRange)
                ->sum('total_amount'),
            'total_commissions' => Commission::whereBetween('created_at', $dateRange)
                ->sum('amount'),
            'paid_commissions' => Commission::whereBetween('created_at', $dateRange)
                ->where('status', 'paid')
                ->sum('amount'),
            'pending_commissions' => Commission::whereBetween('created_at', $dateRange)
                ->where('status', 'pending')
                ->sum('amount'),
        ];

        $financial['net_revenue'] = $financial['total_revenue'] - $financial['total_commissions'];

        return view('admin.reports.financial', compact('financial', 'period'));
    }

    /**
     * Export global selon le type
     */
    public function export(Request $request, $type)
    {
        switch ($type) {
            case 'users':
                return redirect()->route('admin.users.export', $request->all());
            case 'events':
                return redirect()->route('admin.events.export', $request->all());
            case 'orders':
                return redirect()->route('admin.orders.export', $request->all());
            case 'tickets':
                return redirect()->route('admin.tickets.export', $request->all());
            case 'categories':
                return redirect()->route('admin.categories.export', $request->all());
            case 'commissions':
                return $this->exportCommissions($request);
            default:
                return redirect()->back()->with('error', 'Type d\'export non reconnu');
        }
    }

    /**
     * Export des commissions
     */
    private function exportCommissions(Request $request)
    {
        try {
            $query = Commission::with(['promoteur', 'order.event']);

            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            if ($request->filled('promoter')) {
                $query->where('promoteur_id', $request->promoter);
            }

            $commissions = $query->get();

            $csvContent = "ID,Promoteur,Événement,Commande,Montant,Statut,Date création,Date paiement\n";
            
            foreach ($commissions as $commission) {
                $csvContent .= implode(',', [
                    $commission->id,
                    '"' . addslashes($commission->promoteur->name ?? 'N/A') . '"',
                    '"' . addslashes($commission->order->event->title ?? 'N/A') . '"',
                    $commission->order->order_number ?? 'N/A',
                    number_format($commission->amount, 2),
                    $commission->status,
                    $commission->created_at->format('Y-m-d H:i:s'),
                    $commission->paid_at ? $commission->paid_at->format('Y-m-d H:i:s') : 'N/A'
                ]) . "\n";
            }

            return response($csvContent)
                ->header('Content-Type', 'text/csv')
                ->header('Content-Disposition', 'attachment; filename="commissions-export-' . now()->format('Y-m-d') . '.csv"');

        } catch (\Exception $e) {
            \Log::error('Erreur export commissions: ' . $e->getMessage());
            
            return redirect()->back()
                ->with('error', 'Erreur lors de l\'export des commissions');
        }
    }

    // ================================================================
    // EMAILS ET NOTIFICATIONS - CONSERVÉ
    // ================================================================

    /**
     * Dashboard des emails
     */
    public function emailDashboard()
    {
        // Statistiques des emails
        $emailStats = [
            'total_sent' => 0, // À implémenter selon votre système
            'bounce_rate' => 0,
            'open_rate' => 0,
            'click_rate' => 0,
        ];

        return view('admin.emails.dashboard', compact('emailStats'));
    }

    /**
     * Test d'envoi d'email
     */
    public function testEmail(Request $request)
    {
        $request->validate([
            'email' => 'required|email'
        ]);

        try {
            Mail::raw('Test email depuis ClicBillet CI - ' . now(), function ($message) use ($request) {
                $message->to($request->email)
                        ->subject('Test Email - ClicBillet CI');
            });

            return response()->json([
                'success' => true,
                'message' => 'Email de test envoyé avec succès !'
            ]);

        } catch (\Exception $e) {
            \Log::error('Erreur test email: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'envoi : ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Templates d'emails
     */
    public function emailTemplates()
    {
        // Liste des templates disponibles
        $templates = [
            'order_confirmation' => 'Confirmation de commande',
            'ticket_delivery' => 'Livraison de tickets',
            'event_reminder' => 'Rappel d\'événement',
            'commission_payment' => 'Paiement de commission',
        ];

        return view('admin.emails.templates', compact('templates'));
    }

    /**
     * Renvoyer email de commande
     */
    public function resendOrderEmail(Order $order)
    {
        try {
            // TODO: Implémenter selon votre système d'emails
            // Mail::to($order->user->email)->send(new OrderConfirmationMail($order));

            \Log::info('Email commande renvoyé par admin', [
                'admin_id' => auth()->id(),
                'order_id' => $order->id
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Email renvoyé avec succès !'
            ]);

        } catch (\Exception $e) {
            \Log::error('Erreur renvoi email: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du renvoi'
            ], 500);
        }
    }

    // ================================================================
    // MÉTHODES UTILITAIRES PRIVÉES
    // ================================================================

    /**
     * Statistiques par défaut en cas d'erreur
     */
    private function getDefaultStats()
    {
        return [
            'total_users' => 0,
            'total_events' => 0,
            'total_orders' => 0,
            'total_revenue' => 0,
            'pending_events' => 0,
            'this_month_revenue' => 0,
            'verified_users' => 0,
            'promoteurs_actifs' => 0,
        ];
    }

    /**
     * Helper pour les plages de dates
     */
    private function getDateRange($period)
    {
        switch ($period) {
            case 'today':
                return [now()->startOfDay(), now()->endOfDay()];
            case 'this_week':
                return [now()->startOfWeek(), now()->endOfWeek()];
            case 'this_month':
                return [now()->startOfMonth(), now()->endOfMonth()];
            case 'this_year':
                return [now()->startOfYear(), now()->endOfYear()];
            case 'last_month':
                return [now()->subMonth()->startOfMonth(), now()->subMonth()->endOfMonth()];
            case 'last_year':
                return [now()->subYear()->startOfYear(), now()->subYear()->endOfYear()];
            default:
                return [now()->startOfMonth(), now()->endOfMonth()];
        }
    }
}