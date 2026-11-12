<?php
// app/Http/Controllers/Admin/EventController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\EventCategory;
use App\Models\EventAuditLog;
use App\Models\TicketType;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use App\Http\Controllers\Admin\Concerns\ResolvesStatsPeriod;
use App\Services\Stats\CommissionStats;
use App\Services\Stats\EventStats;
use App\Services\Stats\PlatformStats;
use App\Services\Stats\SalesStats;
use App\Services\Stats\Scope;
use App\Services\Stats\TicketStats;
use App\Support\Stats\DatePeriod;

class EventController extends Controller
{
    use ResolvesStatsPeriod;

    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('admin');
    }

    /**
     * Liste des événements
     */
    public function index(Request $request)
    {
        $period = $this->statsPeriod($request, 'admin.events.index');

        // Mêmes filtres et même période que l'export (filteredQuery)
        $query = $this->filteredQuery($request, $period)->with(['category', 'promoteur']);

        $events = $query->latest()->paginate(20)->withQueryString();

        // Chiffres : services Stats. Statuts = instantané ; ventes = commandes payées de la période.
        $stats = app(PlatformStats::class)->events();
        $periodSales = app(SalesStats::class)->gmv(Scope::admin(), $period);
        // Ligne par événement : 5 requêtes pour toute la page (toutes dates), aucune requête par ligne.
        $eventStats = app(EventStats::class)->forEvents($events->pluck('id')->all());

        // Données pour les filtres
        $categories = EventCategory::orderBy('name')->get();
        $promoteurs = User::where('role', 'promoteur')->orderBy('name')->get();

        return view('admin.events.index', compact('events', 'stats', 'period', 'periodSales', 'eventStats', 'categories', 'promoteurs'));
    }

    /**
     * Formulaire de création événement standard
     */
    public function create()
    {
        $categories = EventCategory::orderBy('name')->get();
        $promoteurs = User::where('role', 'promoteur')->orderBy('name')->get();

        return view('admin.events.create', compact('categories', 'promoteurs'));
    }



    /**
     * Sauvegarde nouvel événement standard
     */
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'category_id' => 'required|exists:event_categories,id',
            'promoter_id' => 'required|exists:users,id',
            'event_date' => 'required|date|after:today',
            'event_time' => 'required',
            'venue' => 'required|string|max:255',
            'address' => 'nullable|string',
            'status' => 'required|in:draft,pending,published,rejected',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'max_capacity' => 'nullable|integer|min:1',
        ]);

        try {
            $data = $request->only([
                'title', 'description', 'category_id', 'promoter_id', 
                'event_date', 'event_time', 'venue', 'address', 'status', 'max_capacity'
            ]);

            // Mode promoteur par défaut
            $data['management_mode'] = 'promoter';

            // Gestion de l'image
            if ($request->hasFile('image')) {
                $data['image'] = $request->file('image')->store('events', 'public');
            }

            $event = Event::create($data);

            // Log de l'action
            $event->logAction('created', [
                'description' => 'Événement créé par admin en mode standard'
            ]);

            return redirect()->route('admin.events.index')
                ->with('success', "Événement '{$event->title}' créé avec succès !");

        } catch (\Exception $e) {
            Log::error('Erreur création événement: ' . $e->getMessage());
            
            return redirect()->back()
                ->with('error', 'Erreur lors de la création de l\'événement')
                ->withInput();
        }
    }

    /**
     * Affichage détail événement standard
     */
    public function show(Event $event)
    {
        try {
            $event->load(['category', 'promoteur', 'ticketTypes']);

            // Chiffres de l'événement (toutes dates) : services Stats. Promoteur = net après commission.
            $scope = Scope::event($event->id);
            $all = DatePeriod::all();
            $row = app(EventStats::class)->forEvents([$event->id])[$event->id];
            $tickets = app(TicketStats::class);
            $stats = $row + [
                'scanned' => $tickets->scanned($scope),
                'usage_rate' => $tickets->usageRate($scope),
                'reserved' => $tickets->reserved($scope),
                'available' => $tickets->available($scope),
                'pending_payout' => app(CommissionStats::class)->pendingPayout($scope, $all),
                'paid_out' => app(CommissionStats::class)->paidOut($scope, $all),
                'orders_awaiting_payment' => array_sum(array_intersect_key(
                    app(\App\Services\Stats\OrderStats::class)->countsByStatus($scope, $all),
                    ['pending' => 1, 'processing' => 1]
                )),
            ];

            // Commandes récentes pour cet événement
            $recentOrders = $event->orders()
                ->with(['user'])
                ->withSum('orderItems as tickets_quantity', 'quantity')
                ->latest()
                ->take(10)
                ->get();

            return view('admin.events.show', compact('event', 'stats', 'recentOrders'));

        } catch (\Exception $e) {
            Log::error('Erreur affichage événement: ' . $e->getMessage());
            return redirect()->route('admin.events.index')->with('error', 'Erreur lors du chargement');
        }
    }

    /**
     * Formulaire d'édition événement
     */
    public function edit(Event $event)
    {
        try {
            $event->load(['category', 'promoteur', 'ticketTypes']);
            $categories = EventCategory::orderBy('name')->get();
            $promoteurs = User::where('role', 'promoteur')->orderBy('name')->get();

            return view('admin.events.edit', compact('event', 'categories', 'promoteurs'));

        } catch (\Exception $e) {
            Log::error('Erreur édition événement: ' . $e->getMessage());
            return redirect()->route('admin.events.index')->with('error', 'Impossible de charger l\'édition');
        }
    }

    /**
     * Mise à jour événement
     */
    public function update(Request $request, Event $event)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'category_id' => 'required|exists:event_categories,id',
            'promoter_id' => 'required|exists:users,id',
            'event_date' => 'required|date',
            'event_time' => 'required',
            'venue' => 'required|string|max:255',
            'address' => 'nullable|string',
            'status' => 'required|in:draft,pending,published,rejected',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'max_capacity' => 'nullable|integer|min:1',
        ]);

        try {
            $oldValues = $event->only(['title', 'status', 'event_date', 'venue']);
            
            $data = $request->only([
                'title', 'description', 'category_id', 'promoter_id', 
                'event_date', 'event_time', 'venue', 'address', 'status', 'max_capacity'
            ]);

            // Gestion de l'image
            if ($request->hasFile('image')) {
                // Supprimer l'ancienne image
                if ($event->image) {
                    Storage::disk('public')->delete($event->image);
                }
                $data['image'] = $request->file('image')->store('events', 'public');
            }

            $event->update($data);

            // Log des modifications
            $event->logAction('updated', [
                'description' => 'Événement modifié par admin',
                'old_values' => $oldValues,
                'new_values' => $data
            ]);

            return redirect()->route('admin.events.index')
                ->with('success', "Événement '{$event->title}' mis à jour avec succès !");

        } catch (\Exception $e) {
            Log::error('Erreur mise à jour événement: ' . $e->getMessage());
            
            return redirect()->back()
                ->with('error', 'Erreur lors de la mise à jour')
                ->withInput();
        }
    }

    /**
     * Suppression événement
     */
    public function destroy(Event $event)
    {
        try {
            // Vérifier qu'il n'y a pas de commandes payées
            $paidOrders = $event->orders()->where('payment_status', 'paid')->count();
            
            if ($paidOrders > 0) {
                return redirect()->back()
                    ->with('error', 'Impossible de supprimer un événement avec des commandes payées. Vous pouvez le passer en statut "rejeté".');
            }

            // Log avant suppression
            $eventData = [
                'title' => $event->title,
                'promoter_id' => $event->promoter_id,
                'event_date' => $event->event_date
            ];

            // Supprimer l'image si elle existe
            if ($event->image) {
                Storage::disk('public')->delete($event->image);
            }

            $eventTitle = $event->title;
            $event->delete();

            Log::info('Événement supprimé par admin', [
                'admin_id' => auth()->id(),
                'event_data' => $eventData
            ]);

            return redirect()->route('admin.events.index')
                ->with('success', "Événement '{$eventTitle}' supprimé avec succès !");

        } catch (\Exception $e) {
            Log::error('Erreur suppression événement: ' . $e->getMessage());
            
            return redirect()->back()
                ->with('error', 'Erreur lors de la suppression de l\'événement');
        }
    }

    /**
     * Mise à jour rapide du statut
     */
    public function updateStatus(Request $request, Event $event)
    {
        $request->validate([
            'status' => 'required|in:draft,pending,published,rejected'
        ]);

        try {
            $oldStatus = $event->status;
            $event->update(['status' => $request->status]);

            $event->logAction('updated', [
                'description' => 'Statut modifié par admin',
                'old_values' => ['status' => $oldStatus],
                'new_values' => ['status' => $request->status]
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Statut mis à jour avec succès'
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur changement statut événement: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du changement de statut'
            ], 500);
        }
    }

    /**
     * Actions en lot
     */
    public function bulkAction(Request $request)
    {
        $request->validate([
            'action' => 'required|in:publish,reject,delete,take_control',
            'events' => 'required|array',
            'events.*' => 'exists:events,id'
        ]);

        try {
            $events = Event::whereIn('id', $request->events)->get();
            $count = 0;

            foreach ($events as $event) {
                switch ($request->action) {
                    case 'publish':
                        $event->update(['status' => 'published']);
                        $event->logAction('updated', ['description' => 'Publié via action groupée']);
                        $count++;
                        break;
                        
                    case 'reject':
                        $event->update(['status' => 'rejected']);
                        $event->logAction('updated', ['description' => 'Rejeté via action groupée']);
                        $count++;
                        break;
                        
                    case 'delete':
                        if ($event->orders()->where('payment_status', 'paid')->count() === 0) {
                            if ($event->image) {
                                Storage::disk('public')->delete($event->image);
                            }
                            $event->delete();
                            $count++;
                        }
                        break;
                        
                    case 'take_control':
                        $event->enableAdminMode('Prise de contrôle via action groupée');
                        $count++;
                        break;
                }
            }

            Log::info('Action en lot sur événements par admin', [
                'admin_id' => auth()->id(),
                'action' => $request->action,
                'events_affected' => $count
            ]);

            return response()->json([
                'success' => true,
                'message' => "{$count} événement(s) traité(s) avec succès !"
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur action en lot événements: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'action groupée'
            ], 500);
        }
    }

    /**
     * Tableau de bord des événements nécessitant une intervention
     */
    public function interventionDashboard()
    {
        $eventsNeedingIntervention = Event::with(['promoteur', 'category', 'ticketTypes'])
            ->where(function($query) {
                // Événements publiés sans billets
                $query->where('status', 'published')
                      ->whereDoesntHave('ticketTypes');
            })
            ->orWhere(function($query) {
                // Événements proches sans billets actifs
                $query->where('event_date', '<', now()->addDays(2))
                      ->whereDoesntHave('ticketTypes', function($ticketQuery) {
                          $ticketQuery->where('is_active', true)
                                     ->where('sale_start_date', '<=', now())
                                     ->where('sale_end_date', '>=', now());
                      });
            })
            ->latest()
            ->paginate(20);

        return view('admin.events.intervention-dashboard', compact('eventsNeedingIntervention'));
    }

    /** Événements filtrés : mêmes filtres et même période (créés dans la période) pour la liste, les cartes ET l'export. */
    private function filteredQuery(Request $request, \App\Support\Stats\DatePeriod $period)
    {
        $query = Event::query();
        
        // Filtres
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        
        if ($request->filled('category')) {
            $query->where('category_id', $request->category);
        }
        
        if ($request->filled('search')) {
            $query->where(function($q) use ($request) {
                $q->where('title', 'like', '%' . $request->search . '%')
                  ->orWhere('description', 'like', '%' . $request->search . '%')
                  ->orWhereHas('promoteur', function($pq) use ($request) {
                      $pq->where('name', 'like', '%' . $request->search . '%');
                  });
            });
        }

        if ($request->filled('promoteur')) {
            $query->where('promoter_id', $request->promoteur);
        }

        if ($request->filled('date_from')) {
            $query->where('event_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->where('event_date', '<=', $request->date_to);
        }

        // Filtre par mode de gestion (nouveau)
        if ($request->filled('management_mode')) {
            $query->where('management_mode', $request->management_mode);
        }

        // Tri par défaut : plus récents
        // Période : événements créés dans la période affichée (cartes ET liste)
        if ($period->start !== null) {
            $query->where('events.created_at', '>=', $period->startForDb());
        }
        if ($period->end !== null) {
            $query->where('events.created_at', '<=', $period->endForDb());
        }

        return $query;
    }

    /**
     * Export CSV des événements : mêmes filtres et même période que l'écran ; chiffres par événement = ceux de la
     * liste (EventStats::forEvents, toutes dates : vendus = sold + used de commandes payées, ventes = somme des
     * lignes des commandes payées, net = commissions pending/paid), 5 requêtes par paquet de 500.
     * Aucune donnée personnelle d'acheteur : seulement le nom du promoteur.
     */
    public function export(Request $request)
    {
        $period = $this->statsPeriod($request, 'admin.events.index');
        $query = $this->filteredQuery($request, $period)->with(['category:id,name', 'promoteur:id,name']);

        return \App\Support\CsvExport::response(
            'evenements-'.now()->format('Y-m-d').'.csv',
            ['ID', 'Titre', 'Promoteur', 'Catégorie', 'Statut', 'Date événement', 'Billets vendus', 'Capacité',
                'Commandes payées', 'Ventes (FCFA)', 'Net promoteur (FCFA)', 'Créé le'],
            function (callable $emit) use ($query) {
                $query->chunkByIdDesc(500, function ($events) use ($emit) {
                    $figures = app(\App\Services\Stats\EventStats::class)->forEvents($events->pluck('id')->all());
                    foreach ($events as $event) {
                        $f = $figures[$event->id];
                        $emit([
                            $event->id,
                            $event->title,
                            $event->promoteur?->name ?? 'N/A',
                            $event->category?->name ?? 'N/A',
                            $event->status,
                            $event->event_date ? $event->event_date->format('d/m/Y') : 'N/A',
                            $f['sold'],
                            $f['capacity'],
                            $f['orders_paid'],
                            $f['gmv'],
                            $f['promoter_net'],
                            $event->created_at->format('d/m/Y H:i'),
                        ]);
                    }
                });
            }
        );
    }

    /**
     * Publication rapide
     */
    public function quickPublish(Event $event)
    {
        try {
            $event->update(['status' => 'published']);
            $event->logAction('published', ['description' => 'Publication rapide par admin']);

            return response()->json([
                'success' => true,
                'message' => 'Événement publié avec succès'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la publication'
            ], 500);
        }
    }

    /**
     * Dépublication rapide
     */
    public function quickUnpublish(Event $event)
    {
        try {
            $event->update(['status' => 'draft']);
            $event->logAction('unpublished', ['description' => 'Dépublication rapide par admin']);

            return response()->json([
                'success' => true,
                'message' => 'Événement dépublié avec succès'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la dépublication'
            ], 500);
        }
    }

    /**
     * Statistiques de gestion hybride
     */
    public function hybridStats()
    {
        $stats = [
            'interventions_last_month' => EventAuditLog::where('action', 'admin_takeover')
                ->where('created_at', '>=', now()->subMonth())
                ->count(),
            'events_by_mode' => Event::selectRaw('management_mode, COUNT(*) as count')
                ->groupBy('management_mode')
                ->pluck('count', 'management_mode'),
            'active_interventions' => Event::where('management_mode', 'admin')->count(),
            'collaborative_events' => Event::where('management_mode', 'collaborative')->count(),
        ];

        return response()->json($stats);
    }

    // ==================== MÉTHODES UTILITAIRES ====================

    
    
    /**
     * Notifier le promoteur d'un changement de mode
     */
    private function notifyPromoterOfModeChange(Event $event, string $oldMode, string $newMode, string $reason)
    {
        // TODO: Implémenter la notification email/SMS
        try {
            // Mail::to($event->promoteur->email)->send(new ManagementModeChanged($event, $oldMode, $newMode, $reason));
            Log::info('Notification promoteur changement mode', [
                'event_id' => $event->id,
                'promoter_id' => $event->promoter_id,
                'old_mode' => $oldMode,
                'new_mode' => $newMode,
                'reason' => $reason
            ]);
        } catch (\Exception $e) {
            Log::error('Erreur notification promoteur: ' . $e->getMessage());
        }
    }

    /**
     * Valider les permissions pour une action
     */
    private function validateAdminPermission(Event $event, string $permission): bool
    {
        // Super admin peut tout faire
        if (auth()->user()->isAdmin() && auth()->user()->email === 'admin@clicbillet.ci') {
            return true;
        }

        // Vérifier selon le mode de gestion
        switch ($event->management_mode) {
            case 'admin':
                return true; // Admin a tous les droits
                
            case 'collaborative':
                return in_array($permission, $event->admin_permissions ?? []);
                
            case 'promoter':
                return false; // Aucun droit admin en mode promoteur seul
                
            default:
                return false;
        }
    }

    
    

    /**
     * Obtenir la priorité d'un événement
     */
    private function getEventPriority(Event $event): string
    {
        if ($event->status === 'published' && $event->ticketTypes->count() === 0) {
            return 'critique';
        }

        if ($event->event_date <= now()->addDays(2)) {
            return 'urgent';
        }

        return 'normal';
    }

    /**
     * Restaurer le mode promoteur avec notification
     */
    public function restorePromoterControl(Request $request, Event $event)
    {
        $request->validate([
            'reason' => 'required|string|max:500',
            'notify_promoter' => 'boolean'
        ]);

        try {
            $oldMode = $event->management_mode;
            $event->restorePromoterMode($request->reason);

            if ($request->notify_promoter) {
                $this->notifyPromoterOfModeChange($event, $oldMode, 'promoter', $request->reason);
            }

            return response()->json([
                'success' => true,
                'message' => 'Contrôle rendu au promoteur avec succès'
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur restauration contrôle promoteur: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la restauration du contrôle'
            ], 500);
        }
    }
}