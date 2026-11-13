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
use App\Http\Controllers\Admin\Concerns\ResolvesStatsPeriod;
use App\Services\Stats\CommissionStats;
use App\Services\Stats\DashboardStats;
use App\Services\Stats\EventStats;
use App\Services\Stats\PlatformStats;
use App\Services\Stats\SalesStats;
use App\Services\Stats\Scope;
use App\Support\Stats\DatePeriod;
use Carbon\Carbon;

class AdminController extends Controller
{
    use ResolvesStatsPeriod;

    public function __construct(
        private readonly DashboardStats $dashboardStats,
        private readonly PlatformStats $platformStats,
        private readonly SalesStats $salesStats,
        private readonly CommissionStats $commissionStats,
        private readonly EventStats $eventStats,
    ) {
        $this->middleware('auth');
        $this->middleware('admin');
    }

    // ================================================================
    // DASHBOARD PRINCIPAL - CONSERVÉ
    // ================================================================

    /**
     * Dashboard principal avec statistiques générales
     */
    public function dashboard(Request $request)
    {
        $period = $this->statsPeriod($request, 'admin.dashboard');
        $scope = Scope::admin();

        // Tous les chiffres viennent des services Stats : la vue n'exécute ni requête ni calcul.
        $summary = $this->dashboardStats->summary($scope, $period);
        $platformUsers = $this->platformStats->users($period);
        $platformEvents = $this->platformStats->events();

        // Évolution mensuelle sur 6 mois (ventes hors frais, commandes payées) : une série SQL, pas une requête par mois.
        $tz = DatePeriod::TIMEZONE;
        $monthlyStats = collect($this->salesStats->byMonth($scope, DatePeriod::custom(now($tz)->subMonthsNoOverflow(5)->startOfMonth(), now($tz))))
            ->map(fn ($row) => [
                'month' => Carbon::createFromFormat('!Y-m', $row['date'])->translatedFormat('M Y'),
                'revenue' => $row['gmv'],
                'orders' => $row['orders'],
            ]);

        $pendingEvents = Event::where('status', 'pending')
            ->with(['promoteur', 'category'])
            ->latest()
            ->take(5)
            ->get();

        $recentOrders = Order::with(['user', 'event'])
            ->latest()
            ->take(5)
            ->get();

        $topPromoters = $this->platformStats->topPromoters($period);

        return view('admin.dashboard', compact(
            'period',
            'summary',
            'platformUsers',
            'platformEvents',
            'monthlyStats',
            'pendingEvents',
            'recentOrders',
            'topPromoters'
        ));
    }

    // ================================================================
    // COMMISSIONS ET FINANCES - CONSERVÉ
    // ================================================================

    /**
     * Gestion des commissions
     */
    public function commissions(Request $request)
    {
        $period = $this->statsPeriod($request, 'admin.commissions');
        $scope = Scope::admin();

        $query = Commission::with(['promoteur', 'order.event']);

        // Filtres
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('promoter')) {
            $query->where('promoter_id', $request->promoter);
        }

        // Période unifiée sur la liste. Exception voulue (paiement en lot, comme sur main) : les commissions
        // EN ATTENTE de versement apparaissent TOUTES, quelle que soit leur date ; la période filtre les autres statuts.
        $query->where(function ($q) use ($period) {
            $q->where('status', 'pending');
            $q->orWhere(function ($other) use ($period) {
                if ($period->start !== null) {
                    $other->where('created_at', '>=', $period->startForDb());
                }
                if ($period->end !== null) {
                    $other->where('created_at', '<=', $period->endForDb());
                }
            });
        });
        $commissions = $query->latest()->paginate(20)->withQueryString();

        // Données pour les filtres
        $promoteurs = User::where('role', 'promoteur')->orderBy('name')->get();

        // Chiffres : services Stats (commandes payées de la période ; suspendues à part)
        $stats = [
            'platform_revenue' => $this->commissionStats->platformRevenue($scope, $period),
            'pending_payout' => $this->commissionStats->pendingPayout($scope, DatePeriod::all()),
            'paid_out' => $this->commissionStats->paidOut($scope, $period),
            'paid_out_total' => $this->commissionStats->paidOut($scope, DatePeriod::all()),
            'held' => $this->commissionStats->held($scope, $period),
        ];

        // Net déjà versé par promoteur de la page : une seule requête (pas de requête par ligne)
        $paidOutByPromoter = $this->commissionStats->paidOutByPromoter($commissions->pluck('promoter_id')->unique()->values()->all());

        return view('admin.commissions', compact('commissions', 'promoteurs', 'stats', 'period', 'paidOutByPromoter'));
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
     * Revenus
     */
    public function revenues(Request $request)
    {
        $period = $this->statsPeriod($request, 'admin.revenues');
        $scope = Scope::admin();

        $summary = $this->dashboardStats->summary($scope, $period);

        // Évolution sur 12 mois : deux séries SQL (ventes, commissions), pas 24 requêtes.
        $tz = DatePeriod::TIMEZONE;
        $twelve = DatePeriod::custom(now($tz)->subMonthsNoOverflow(11)->startOfMonth(), now($tz));
        $commissionsByMonth = $this->commissionStats->byMonth($scope, $twelve);
        $monthlyRevenue = collect($this->salesStats->byMonth($scope, $twelve))->map(fn ($row) => [
            'month' => Carbon::createFromFormat('!Y-m', $row['date'])->translatedFormat('M Y'),
            'revenue' => $row['gmv'],
            'commissions' => $commissionsByMonth[$row['date']]['commission'] ?? 0,
            'net_revenue' => $commissionsByMonth[$row['date']]['net'] ?? 0,
        ]);

        return view('admin.revenues', compact('summary', 'period', 'monthlyRevenue'));
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
                'commissions_paid' => Commission::where('status', 'paid')->sum('commission_amount'),
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
    public function reports(Request $request)
    {
        $period = $this->statsPeriod($request, 'admin.reports.index');
        $summary = $this->dashboardStats->summary(Scope::admin(), $period);
        $platformUsers = $this->platformStats->users($period);
        $platformEvents = $this->platformStats->events();

        return view('admin.reports.index', compact('period', 'summary', 'platformUsers', 'platformEvents'));
    }

    /**
     * Rapport des ventes
     */
    public function salesReport(Request $request)
    {
        $period = $this->statsPeriod($request, 'admin.reports.sales');
        $scope = Scope::admin();
        $summary = $this->dashboardStats->summary($scope, $period);

        $salesData = $this->salesStats->paidOrders($scope, $period)
            ->with(['event:id,title', 'user:id,name'])
            ->orderByRaw(\App\Support\Stats\SqlDate::SALE_REF.' DESC')
            ->paginate(25)
            ->withQueryString();

        return view('admin.reports.sales', compact('salesData', 'period', 'summary'));
    }

    /**
     * Rapport financier
     */
    public function financialReport(Request $request)
    {
        $period = $this->statsPeriod($request, 'admin.reports.financial');
        $summary = $this->dashboardStats->summary(Scope::admin(), $period);

        return view('admin.reports.financial', compact('period', 'summary'));
    }

    /**
     * Export global selon le type : redirige vers l'export du type (mêmes paramètres de filtre et de période),
     * sauf les commissions, servies ici.
     */
    public function export(Request $request, $type)
    {
        $targets = [
            'users' => 'admin.users.export',
            'events' => 'admin.events.export',
            'orders' => 'admin.orders.export',
            'tickets' => 'admin.tickets.export',
        ];

        if ($type === 'commissions') {
            return $this->exportCommissions($request);
        }

        if (isset($targets[$type])) {
            return redirect()->route($targets[$type], $request->query());
        }

        return redirect()->back()->with('error', 'Type d\'export non reconnu');
    }

    /**
     * Rapport des utilisateurs (AJOUTÉ)
     */
    public function usersReport(Request $request)
    {
        $period = $this->statsPeriod($request, 'admin.reports.users');

        $stats = $this->platformStats->users($period);
        $registrations = $this->platformStats->registrations($period);
        $activeUsers = $this->platformStats->mostActiveNewUsers($period);

        return view('admin.reports.users', compact('period', 'stats', 'registrations', 'activeUsers'));
    }

    /**
     * Rapport des événements (AJOUTÉ)
     */
    public function eventsReport(Request $request)
    {
        $period = $this->statsPeriod($request, 'admin.reports.events');
        $scope = Scope::admin();

        $stats = $this->platformStats->events($period);
        $summary = $this->dashboardStats->summary($scope, $period);
        $topCategories = $this->eventStats->byCategory($period);

        $eventsQuery = Event::query();
        if ($period->start !== null) {
            $eventsQuery->where('created_at', '>=', $period->startForDb());
        }
        if ($period->end !== null) {
            $eventsQuery->where('created_at', '<=', $period->endForDb());
        }
        $eventsData = $eventsQuery
            ->with(['promoteur:id,name', 'category:id,name'])
            ->latest()
            ->paginate(25)
            ->withQueryString();
        $eventSales = $this->eventStats->forEvents($eventsData->pluck('id')->all(), $period);

        return view('admin.reports.events', compact('period', 'stats', 'summary', 'topCategories', 'eventsData', 'eventSales'));
    }

    /**
     * Export CSV des commissions : mêmes filtres (statut, promoteur) que l'écran, et la MÊME période que les cartes
     * (date de VENTE de la commande, DatePeriod), pour que le total des lignes « compté » égale le revenu plateforme
     * affiché. Différence voulue avec la liste : la liste montre aussi toutes les commissions en attente quelle que
     * soit leur date (paiement en lot) ; l'export, lui, est borné à la période. E-mail promoteur complet (affiché en
     * entier par l'écran). Lecture par paquets.
     */
    private function exportCommissions(Request $request)
    {
        $period = $this->statsPeriod($request, 'admin.commissions');

        $query = Commission::query()
            ->whereIn('commissions.order_id', Order::query()->forScope(Scope::admin())->inPeriod($period)->select('orders.id'))
            ->with(['promoteur:id,name,email', 'order:id,order_number,event_id,payment_status,total_amount', 'order.event:id,title']);

        if ($request->filled('status')) {
            $query->where('commissions.status', $request->status);
        }

        if ($request->filled('promoter')) {
            $query->where('commissions.promoter_id', $request->promoter);
        }

        return \App\Support\CsvExport::response(
            'commissions-'.now()->format('Y-m-d').'.csv',
            ['ID', 'Promoteur', 'Email Promoteur', 'Événement', 'Commande', 'Statut Commande', 'Ventes (FCFA)',
                'Commission (FCFA)', 'Net Promoteur (FCFA)', 'Statut Commission', 'Compté dans les revenus', 'Date création', 'Date paiement'],
            function (callable $emit) use ($query) {
                $query->chunkByIdDesc(500, function ($commissions) use ($emit) {
                    foreach ($commissions as $c) {
                        $counted = $c->order?->payment_status === 'paid' && in_array($c->status, ['pending', 'paid'], true);
                        $emit([
                            $c->id,
                            $c->promoteur?->name ?? 'N/A',
                            $c->promoteur?->email ?? 'N/A',
                            $c->order?->event?->title ?? 'N/A',
                            $c->order?->order_number ?? 'N/A',
                            $c->order?->payment_status ?? 'N/A',
                            (int) ($c->order?->total_amount ?? 0),
                            (int) $c->commission_amount,
                            (int) $c->net_amount,
                            $c->status,
                            $counted ? 'Oui' : 'Non',
                            $c->created_at->format('d/m/Y H:i'),
                            $c->paid_at ? $c->paid_at->format('d/m/Y H:i') : 'N/A',
                        ]);
                    }
                });
            }
        );
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
}
