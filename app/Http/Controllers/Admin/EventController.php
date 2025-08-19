<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\EventCategory;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class EventController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('admin');
    }

    /**
     * Liste des événements (MIGRÉ depuis AdminController::events)
     */
    public function index(Request $request)
    {
        $query = Event::with(['category', 'promoteur', 'ticketTypes']);
        
        // Filtres existants de votre code
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
            $query->where('promoteur_id', $request->promoteur);
        }

        if ($request->filled('date_from')) {
            $query->where('event_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->where('event_date', '<=', $request->date_to);
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
        ];

        // Données pour les filtres
        $categories = EventCategory::orderBy('name')->get();
        $promoteurs = User::where('role', 'promoteur')->orderBy('name')->get();

        return view('admin.events.index', compact('events', 'stats', 'categories', 'promoteurs'));
    }

    /**
     * Formulaire de création événement
     */
    public function create()
    {
        $categories = EventCategory::orderBy('name')->get();
        $promoteurs = User::where('role', 'promoteur')->orderBy('name')->get();

        return view('admin.events.create', compact('categories', 'promoteurs'));
    }

    /**
     * Sauvegarde nouvel événement
     */
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'category_id' => 'required|exists:event_categories,id',
            'promoteur_id' => 'required|exists:users,id',
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
                'title', 'description', 'category_id', 'promoteur_id', 
                'event_date', 'event_time', 'venue', 'address', 'status', 'max_capacity'
            ]);

            // Gestion de l'image
            if ($request->hasFile('image')) {
                $data['image'] = $request->file('image')->store('events', 'public');
            }

            $event = Event::create($data);

            // Log de l'action
            \Log::info('Événement créé par admin', [
                'admin_id' => auth()->id(),
                'event_id' => $event->id,
                'event_title' => $event->title
            ]);

            return redirect()->route('admin.events.index')
                ->with('success', "Événement '{$event->title}' créé avec succès !");

        } catch (\Exception $e) {
            \Log::error('Erreur création événement: ' . $e->getMessage());
            
            return redirect()->back()
                ->with('error', 'Erreur lors de la création de l\'événement')
                ->withInput();
        }
    }

    /**
     * Affichage détail événement (MIGRÉ depuis AdminController::showEvent)
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
                'revenue_promoteur' => $event->promoteur->commissions()
                    ->whereHas('order', function($q) use ($event) {
                        $q->where('event_id', $event->id);
                    })->sum('net_amount'),
            ];

            // Commandes récentes pour cet événement
            $recentOrders = $event->orders()
                ->with(['user', 'orderItems.ticketType'])
                ->latest()
                ->take(10)
                ->get();

            return view('admin.events.show', compact('event', 'stats', 'recentOrders'));

        } catch (\Exception $e) {
            \Log::error('Erreur affichage événement: ' . $e->getMessage());
            return redirect()->route('admin.events.index')->with('error', 'Erreur lors du chargement');
        }
    }

    /**
     * Formulaire d'édition événement (MIGRÉ depuis AdminController::editEvent)
     */
    public function edit(Event $event)
    {
        try {
            $event->load(['category', 'promoteur', 'ticketTypes']);
            $categories = EventCategory::orderBy('name')->get();
            $promoteurs = User::where('role', 'promoteur')->orderBy('name')->get();

            return view('admin.events.edit', compact('event', 'categories', 'promoteurs'));

        } catch (\Exception $e) {
            \Log::error('Erreur édition événement: ' . $e->getMessage());
            return redirect()->route('admin.events.index')->with('error', 'Impossible de charger l\'édition');
        }
    }

    /**
     * Mise à jour événement (MIGRÉ depuis AdminController::updateEvent)
     */
    public function update(Request $request, Event $event)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'category_id' => 'required|exists:event_categories,id',
            'promoteur_id' => 'required|exists:users,id',
            'event_date' => 'required|date',
            'event_time' => 'required',
            'venue' => 'required|string|max:255',
            'address' => 'nullable|string',
            'status' => 'required|in:draft,pending,published,rejected',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'max_capacity' => 'nullable|integer|min:1',
        ]);

        try {
            $data = $request->only([
                'title', 'description', 'category_id', 'promoteur_id', 
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

            $oldStatus = $event->status;
            $event->update($data);

            // Log du changement de statut si applicable
            if ($oldStatus !== $event->status) {
                \Log::info('Statut événement modifié par admin', [
                    'admin_id' => auth()->id(),
                    'event_id' => $event->id,
                    'old_status' => $oldStatus,
                    'new_status' => $event->status
                ]);
            }

            return redirect()->route('admin.events.index')
                ->with('success', "Événement '{$event->title}' mis à jour avec succès !");

        } catch (\Exception $e) {
            \Log::error('Erreur mise à jour événement: ' . $e->getMessage());
            
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
                    ->with('error', 'Impossible de supprimer un événement avec des commandes payées. Vous pouvez le passer en statut "annulé".');
            }

            // Supprimer l'image si elle existe
            if ($event->image) {
                Storage::disk('public')->delete($event->image);
            }

            $eventTitle = $event->title;
            $event->delete();

            \Log::info('Événement supprimé par admin', [
                'admin_id' => auth()->id(),
                'event_title' => $eventTitle
            ]);

            return redirect()->route('admin.events.index')
                ->with('success', "Événement '{$eventTitle}' supprimé avec succès !");

        } catch (\Exception $e) {
            \Log::error('Erreur suppression événement: ' . $e->getMessage());
            
            return redirect()->back()
                ->with('error', 'Erreur lors de la suppression de l\'événement');
        }
    }

    /**
     * Mise à jour rapide du statut (NOUVELLE FONCTIONNALITÉ)
     */
    public function updateStatus(Request $request, Event $event)
    {
        $request->validate([
            'status' => 'required|in:draft,pending,published,rejected'
        ]);

        try {
            $oldStatus = $event->status;
            $event->update(['status' => $request->status]);

            \Log::info('Statut événement modifié par admin', [
                'admin_id' => auth()->id(),
                'event_id' => $event->id,
                'old_status' => $oldStatus,
                'new_status' => $event->status
            ]);

            return redirect()->back()
                ->with('success', "Statut de l'événement mis à jour !");

        } catch (\Exception $e) {
            \Log::error('Erreur changement statut événement: ' . $e->getMessage());
            
            return redirect()->back()
                ->with('error', 'Erreur lors du changement de statut');
        }
    }

    /**
     * Actions en lot (NOUVELLE FONCTIONNALITÉ)
     */
    public function bulkAction(Request $request)
    {
        $request->validate([
            'action' => 'required|in:publish,reject,delete',
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
                        $count++;
                        break;
                        
                    case 'reject':
                        $event->update(['status' => 'rejected']);
                        $count++;
                        break;
                        
                    case 'delete':
                        // Vérifier qu'il n'y a pas de commandes payées
                        if ($event->orders()->where('payment_status', 'paid')->count() === 0) {
                            if ($event->image) {
                                Storage::disk('public')->delete($event->image);
                            }
                            $event->delete();
                            $count++;
                        }
                        break;
                }
            }

            \Log::info('Action en lot sur événements par admin', [
                'admin_id' => auth()->id(),
                'action' => $request->action,
                'events_affected' => $count
            ]);

            return redirect()->back()
                ->with('success', "{$count} événement(s) traité(s) avec succès !");

        } catch (\Exception $e) {
            \Log::error('Erreur action en lot événements: ' . $e->getMessage());
            
            return redirect()->back()
                ->with('error', 'Erreur lors du traitement des événements');
        }
    }

    /**
     * Export CSV personnalisé (NOUVELLE FONCTIONNALITÉ)
     */
    public function export(Request $request)
    {
        try {
            $query = Event::with(['category', 'promoteur']);

            // Appliquer les mêmes filtres que l'index
            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }
            
            if ($request->filled('category')) {
                $query->where('category_id', $request->category);
            }

            if ($request->filled('promoteur')) {
                $query->where('promoteur_id', $request->promoteur);
            }

            $events = $query->get();

            $csvContent = "ID,Titre,Promoteur,Catégorie,Date,Statut,Tickets vendus,Revenus,Créé le\n";
            
            foreach ($events as $event) {
                $csvContent .= implode(',', [
                    $event->id,
                    '"' . addslashes($event->title) . '"',
                    '"' . addslashes($event->promoteur->name ?? 'N/A') . '"',
                    '"' . addslashes($event->category->name ?? 'N/A') . '"',
                    $event->event_date,
                    $event->status,
                    $event->ticketTypes->sum('quantity_sold') ?? 0,
                    number_format($event->totalRevenue() ?? 0, 2),
                    $event->created_at->format('Y-m-d H:i:s')
                ]) . "\n";
            }

            return response($csvContent)
                ->header('Content-Type', 'text/csv')
                ->header('Content-Disposition', 'attachment; filename="events-export-' . now()->format('Y-m-d') . '.csv"');

        } catch (\Exception $e) {
            \Log::error('Erreur export événements: ' . $e->getMessage());
            
            return redirect()->back()
                ->with('error', 'Erreur lors de l\'export');
        }
    }
}