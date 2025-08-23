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

class EventController extends Controller
{
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
        $query = Event::with(['category', 'promoteur', 'ticketTypes']);
        
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
        $events = $query->latest()->paginate(20);

        // Statistiques pour le dashboard
        $stats = [
            'total' => Event::count(),
            'published' => Event::where('status', 'published')->count(),
            'pending' => Event::where('status', 'pending')->count(),
            'draft' => Event::where('status', 'draft')->count(),
            'rejected' => Event::where('status', 'rejected')->count(),
            'this_month' => Event::whereMonth('created_at', now()->month)->count(),
            'total_revenue' => Event::join('orders', 'events.id', '=', 'orders.event_id')
                ->where('orders.payment_status', 'paid')
                ->sum('orders.total_amount'),
            // Nouvelles statistiques hybrides
            'admin_managed' => Event::where('management_mode', 'admin')->count(),
            'collaborative' => Event::where('management_mode', 'collaborative')->count(),
            'promoter_only' => Event::where('management_mode', 'promoter')->count(),
            'need_intervention' => Event::whereIn('id', $this->getEventsNeedingIntervention())->count(),
        ];

        // Données pour les filtres
        $categories = EventCategory::orderBy('name')->get();
        $promoteurs = User::where('role', 'promoteur')->orderBy('name')->get();

        return view('admin.events.index', compact('events', 'stats', 'categories', 'promoteurs'));
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
     * Formulaire de création événement hybride
     */
    public function createHybrid()
    {
        $categories = EventCategory::orderBy('name')->get();
        $promoteurs = User::where('role', 'promoteur')->orderBy('name')->get();

        return view('admin.events.create-hybrid', compact('categories', 'promoteurs'));
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
     * Sauvegarde événement avec mode de gestion hybride
     */
    public function storeWithMode(Request $request)
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
            
            // Nouveaux champs hybrides
            'management_mode' => 'required|in:promoter,admin,collaborative',
            'admin_permissions' => 'nullable|array',
            'management_reason' => 'nullable|string',
            'configure_tickets_now' => 'boolean',
            
            // Billets (si configurés immédiatement)
            'ticket_types' => 'nullable|array',
            'ticket_types.*.name' => 'required_with:ticket_types|string|max:255',
            'ticket_types.*.price' => 'required_with:ticket_types|numeric|min:0',
            'ticket_types.*.quantity_available' => 'required_with:ticket_types|integer|min:1',
            'ticket_types.*.sale_start_date' => 'required_with:ticket_types|date|after_or_equal:today',
            'ticket_types.*.sale_end_date' => 'required_with:ticket_types|date|after:sale_start_date',
            'ticket_types.*.max_per_order' => 'required_with:ticket_types|integer|min:1|max:20',
        ]);

        try {
            $data = $request->only([
                'title', 'description', 'category_id', 'promoter_id', 
                'event_date', 'event_time', 'venue', 'address', 'status', 'max_capacity',
                'management_mode', 'admin_permissions', 'management_reason'
            ]);

            // Gestion de l'image
            if ($request->hasFile('image')) {
                $data['image'] = $request->file('image')->store('events', 'public');
            }

            // Traçabilité du changement de mode
            if ($data['management_mode'] !== 'promoter') {
                $data['management_changed_by'] = auth()->id();
                $data['management_changed_at'] = now();
            }

            $event = Event::create($data);

            // Créer les billets si demandé
            if ($request->configure_tickets_now && $request->ticket_types) {
                $this->createTicketTypes($event, $request->ticket_types);
                
                $event->logAction('tickets_created', [
                    'description' => 'Billets créés par admin lors de la création',
                    'ticket_count' => count($request->ticket_types)
                ]);
            }

            // Log de création
            $event->logAction('created', [
                'description' => "Événement créé en mode {$data['management_mode']}"
            ]);

            $successMessage = "Événement '{$event->title}' créé avec succès";
            if ($request->configure_tickets_now) {
                $successMessage .= " avec " . count($request->ticket_types ?? []) . " types de billets";
            }

            return redirect()->route('admin.events.manage-hybrid', $event)
                ->with('success', $successMessage);

        } catch (\Exception $e) {
            Log::error('Erreur création événement hybride: ' . $e->getMessage());
            
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
            $event->load(['category', 'promoteur', 'ticketTypes', 'orders.user', 'tickets']);
            
            // Statistiques de l'événement
            $stats = [
                'total_tickets' => $event->ticketTypes->sum('quantity_available') ?? 0,
                'sold_tickets' => $event->ticketTypes->sum('quantity_sold') ?? 0,
                'total_revenue' => $event->totalRevenue() ?? 0,
                'total_orders' => $event->orders()->count(),
                'pending_orders' => $event->orders()->where('payment_status', 'pending')->count(),
                'used_tickets' => $event->tickets()->where('status', 'used')->count(),
                'available_tickets' => $event->ticketTypes->sum('quantity_available') - $event->ticketTypes->sum('quantity_sold'),
                'revenue_promoteur' => $event->promoteur && $event->promoteur->commissions()
                    ->whereHas('order', function($q) use ($event) {
                        $q->where('event_id', $event->id);
                    })->sum('net_amount') ?? 0,
            ];

            // Commandes récentes pour cet événement
            $recentOrders = $event->orders()
                ->with(['user', 'orderItems.ticketType'])
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
     * Interface de gestion hybride
     */
    public function manageHybrid(Event $event)
    {
        $event->load(['category', 'promoteur', 'ticketTypes', 'managementChanger']);
        
        $stats = [
            'total_tickets' => $event->ticketTypes->sum('quantity_available') ?? 0,
            'sold_tickets' => $event->ticketTypes->sum('quantity_sold') ?? 0,
            'total_revenue' => $event->totalRevenue() ?? 0,
            'available_tickets' => $event->ticketTypes->sum('quantity_available') - $event->ticketTypes->sum('quantity_sold'),
        ];

        $managementHistory = $event->getManagementHistory();
        $needsIntervention = $event->needsAdminIntervention();

        return view('admin.events.manage-hybrid', compact(
            'event', 
            'stats', 
            'managementHistory', 
            'needsIntervention'
        ));
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
     * Changer le mode de gestion d'un événement
     */
    public function changeManagementMode(Request $request, Event $event)
    {
        $request->validate([
            'management_mode' => 'required|in:promoter,admin,collaborative',
            'admin_permissions' => 'nullable|array',
            'reason' => 'required|string|max:500',
            'notify_promoter' => 'boolean',
            'create_tickets_immediately' => 'boolean'
        ]);

        try {
            switch ($request->management_mode) {
                case 'admin':
                    $event->enableAdminMode($request->reason);
                    $message = 'Mode admin activé - Vous avez maintenant le contrôle complet';
                    break;
                    
                case 'collaborative':
                    $event->enableCollaborativeMode(
                        $request->admin_permissions ?? [], 
                        $request->reason
                    );
                    $message = 'Mode collaboratif activé';
                    break;
                    
                case 'promoter':
                    $event->restorePromoterMode($request->reason);
                    $message = 'Contrôle rendu au promoteur';
                    break;
            }

            // Préparer les redirections
            $redirects = [
                'redirect_manage' => route('admin.events.manage-hybrid', $event),
                'redirect_create_tickets' => route('admin.events.manage-tickets', $event)
            ];

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => $message,
                    ...$redirects
                ]);
            }

            return redirect()->back()->with('success', $message);

        } catch (\Exception $e) {
            Log::error('Erreur changement mode gestion: ' . $e->getMessage());
            
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Erreur lors du changement de mode'
                ], 500);
            }
            
            return redirect()->back()->with('error', 'Erreur lors du changement de mode');
        }
    }

    /**
     * Interface de gestion des billets pour admin
     */
    public function manageTickets(Event $event)
    {
        // Vérifier les permissions
        if (!$event->adminHasPermission('create_tickets') && !$event->adminHasPermission('edit_tickets')) {
            abort(403, 'Vous n\'avez pas la permission de gérer les billets de cet événement');
        }

        $event->load(['ticketTypes' => function($query) {
            $query->orderBy('price', 'asc');
        }]);

        return view('admin.events.manage-tickets', compact('event'));
    }

    /**
     * Sauvegarder les billets créés par admin
     */
    public function storeTickets(Request $request, Event $event)
    {
        // Vérifier les permissions
        if (!$event->adminHasPermission('create_tickets')) {
            abort(403, 'Vous n\'avez pas la permission de créer des billets');
        }

        $request->validate([
            'ticket_types' => 'required|array|min:1',
            'ticket_types.*.name' => 'required|string|max:255',
            'ticket_types.*.description' => 'nullable|string|max:500',
            'ticket_types.*.price' => 'required|numeric|min:0',
            'ticket_types.*.quantity_available' => 'required|integer|min:1',
            'ticket_types.*.sale_start_date' => 'required|date|after_or_equal:today',
            'ticket_types.*.sale_end_date' => 'required|date|after:sale_start_date',
            'ticket_types.*.max_per_order' => 'required|integer|min:1|max:20',
        ]);

        try {
            $createdTickets = $this->createTicketTypes($event, $request->ticket_types);

            // Log de l'action
            $event->logAction('tickets_created', [
                'description' => 'Types de billets créés par administrateur',
                'ticket_count' => count($createdTickets),
                'new_values' => $request->ticket_types
            ]);

            return redirect()->route('admin.events.manage-hybrid', $event)
                ->with('success', count($createdTickets) . ' types de billets créés avec succès');

        } catch (\Exception $e) {
            Log::error('Erreur création billets par admin: ' . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Erreur lors de la création des billets')
                ->withInput();
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

    /**
     * Historique détaillé d'un événement
     */
    public function auditHistory(Event $event)
    {
        $auditLogs = $event->auditLogs()
            ->with('user')
            ->paginate(50);

        return view('admin.events.audit-history', compact('event', 'auditLogs'));
    }

    /**
     * Exporter les événements
     */
    public function export(Request $request)
    {
        // TODO: Implémenter l'export CSV/Excel
        return response()->json(['message' => 'Export en cours de développement']);
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
     * Créer les types de billets
     */
    private function createTicketTypes(Event $event, array $ticketTypesData): array
    {
        $createdTickets = [];

        foreach ($ticketTypesData as $typeData) {
            $ticket = TicketType::create([
                'event_id' => $event->id,
                'name' => $typeData['name'],
                'description' => $typeData['description'] ?? '',
                'price' => $typeData['price'],
                'quantity_available' => $typeData['quantity_available'],
                'quantity_sold' => 0,
                'sale_start_date' => Carbon::parse($typeData['sale_start_date']),
                'sale_end_date' => Carbon::parse($typeData['sale_end_date'] . ' 23:59:59'),
                'max_per_order' => $typeData['max_per_order'],
                'is_active' => true,
            ]);

            $createdTickets[] = $ticket;
        }

        return $createdTickets;
    }

    /**
     * Obtenir les IDs des événements nécessitant une intervention
     */
    private function getEventsNeedingIntervention(): array
    {
        $eventIds = [];

        // Événements publiés sans billets
        $publishedWithoutTickets = Event::where('status', 'published')
            ->whereDoesntHave('ticketTypes')
            ->pluck('id')
            ->toArray();

        // Événements proches sans billets actifs
        $upcomingWithoutActiveTickets = Event::where('event_date', '<', now()->addDays(2))
            ->whereDoesntHave('ticketTypes', function($query) {
                $query->where('is_active', true)
                      ->where('sale_start_date', '<=', now())
                      ->where('sale_end_date', '>=', now());
            })
            ->pluck('id')
            ->toArray();

        // Événements avec promoteurs inactifs
        $withInactivePromoters = Event::whereHas('promoteur', function($query) {
                $query->where('last_login_at', '<', now()->subDays(7));
            })
            ->where('status', '!=', 'rejected')
            ->pluck('id')
            ->toArray();

        return array_unique(array_merge(
            $publishedWithoutTickets,
            $upcomingWithoutActiveTickets,
            $withInactivePromoters
        ));
    }

    /**
     * Vérifier si un événement nécessite une intervention
     */
    private function eventNeedsIntervention(Event $event): bool
    {
        // Événement publié sans billets
        if ($event->status === 'published' && $event->ticketTypes->count() === 0) {
            return true;
        }

        // Événement imminent sans billets actifs
        if ($event->event_date < now()->addDays(2)) {
            $hasActiveTickets = $event->ticketTypes()
                ->where('is_active', true)
                ->where('sale_start_date', '<=', now())
                ->where('sale_end_date', '>=', now())
                ->exists();
            
            if (!$hasActiveTickets) {
                return true;
            }
        }

        // Promoteur inactif depuis plus de 7 jours
        if ($event->promoteur && $event->promoteur->last_login_at < now()->subDays(7)) {
            return true;
        }

        return false;
    }

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
     * Obtenir les statistiques d'intervention pour un événement
     */
    private function getEventInterventionStats(Event $event): array
    {
        return [
            'total_interventions' => $event->auditLogs()
                ->whereIn('action', ['admin_takeover', 'management_mode_changed'])
                ->count(),
            'last_intervention' => $event->auditLogs()
                ->whereIn('action', ['admin_takeover', 'management_mode_changed'])
                ->latest()
                ->first(),
            'current_mode_duration' => $event->management_changed_at 
                ? $event->management_changed_at->diffForHumans() 
                : 'Mode initial',
            'is_currently_admin_managed' => $event->management_mode === 'admin',
            'needs_intervention' => $this->eventNeedsIntervention($event)
        ];
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
     * Générer un rapport d'intervention
     */
    public function generateInterventionReport(Event $event)
    {
        $report = [
            'event' => [
                'id' => $event->id,
                'title' => $event->title,
                'date' => $event->event_date->format('d/m/Y H:i'),
                'status' => $event->status,
                'management_mode' => $event->management_mode
            ],
            'promoter' => [
                'name' => $event->promoteur->name ?? 'N/A',
                'email' => $event->promoteur->email ?? 'N/A',
                'last_login' => $event->promoteur->last_login_at?->format('d/m/Y H:i') ?? 'Jamais'
            ],
            'issues' => [],
            'recommendations' => [],
            'actions_taken' => $event->auditLogs()
                ->whereIn('action', ['admin_takeover', 'tickets_created', 'management_mode_changed'])
                ->with('user')
                ->get()
                ->map(function($log) {
                    return [
                        'action' => $log->action_label,
                        'date' => $log->created_at->format('d/m/Y H:i'),
                        'user' => $log->user->name,
                        'description' => $log->description
                    ];
                })
        ];

        // Analyser les problèmes
        if ($event->status === 'published' && $event->ticketTypes->count() === 0) {
            $report['issues'][] = 'Événement publié sans billets disponibles';
            $report['recommendations'][] = 'Créer immédiatement des types de billets';
        }

        if ($event->event_date < now()->addDays(2)) {
            $report['issues'][] = 'Événement imminent (moins de 48h)';
            $report['recommendations'][] = 'Vérifier que tout est prêt pour la vente';
        }

        if ($event->promoteur && $event->promoteur->last_login_at < now()->subDays(7)) {
            $report['issues'][] = 'Promoteur inactif depuis plus de 7 jours';
            $report['recommendations'][] = 'Contacter le promoteur ou prendre le contrôle temporaire';
        }

        return $report;
    }

    /**
     * API pour obtenir les événements nécessitant une intervention (AJAX)
     */
    public function getInterventionEvents()
    {
        $events = Event::with(['promoteur', 'category', 'ticketTypes'])
            ->whereIn('id', $this->getEventsNeedingIntervention())
            ->get()
            ->map(function($event) {
                return [
                    'id' => $event->id,
                    'title' => $event->title,
                    'promoter' => $event->promoteur->name ?? 'N/A',
                    'date' => $event->event_date->format('d/m/Y'),
                    'status' => $event->status,
                    'management_mode' => $event->management_mode,
                    'issues' => $this->getEventIssues($event),
                    'priority' => $this->getEventPriority($event),
                    'needs_intervention' => $this->eventNeedsIntervention($event)
                ];
            });

        return response()->json($events);
    }

    /**
     * Obtenir les problèmes d'un événement
     */
    private function getEventIssues(Event $event): array
    {
        $issues = [];

        if ($event->status === 'published' && $event->ticketTypes->count() === 0) {
            $issues[] = 'Publié sans billets';
        }

        if ($event->event_date <= now()->addDays(2)) {
            $issues[] = 'Événement imminent';
        }

        if (!$event->ticketTypes->where('is_active', true)->count()) {
            $issues[] = 'Aucun billet actif';
        }

        if ($event->promoteur && $event->promoteur->last_login_at < now()->subDays(7)) {
            $issues[] = 'Promoteur inactif';
        }

        return $issues;
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