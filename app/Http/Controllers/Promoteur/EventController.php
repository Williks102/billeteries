<?php

namespace App\Http\Controllers\Promoteur;

use App\Http\Controllers\Controller;
use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Carbon\Carbon;

class EventController extends Controller
{
    /**
     * Liste des événements du promoteur
     */
    public function index(Request $request)
    {
        $promoteurId = Auth::id();
        $status = $request->get('status');
        $search = $request->get('search');
        
        $query = Event::where('promoter_id', $promoteurId);
        
        // Filtrer par statut
        if ($status) {
            $query->where('status', $status);
        }
        
        // Recherche
        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('location', 'like', "%{$search}%");
            });
        }
        
        $events = $query->with(['category', 'ticketTypes'])
            ->withCount(['orders', 'tickets'])
            ->orderBy('created_at', 'desc')
            ->paginate(12);

        // Statistiques rapides
        $stats = [
            'total' => Event::where('promoter_id', $promoteurId)->count(),
            'published' => Event::where('promoter_id', $promoteurId)->where('status', 'published')->count(),
            'draft' => Event::where('promoter_id', $promoteurId)->where('status', 'draft')->count(),
            'pending' => Event::where('promoter_id', $promoteurId)->where('status', 'pending')->count(),
        ];

        return view('promoteur.events.index', compact('events', 'stats', 'status', 'search'));
    }

    /**
     * Afficher un événement
     */
    public function show(Event $event)
    {
        $this->authorize('view', $event);
        
        $event->load(['category', 'ticketTypes.orderItems.order', 'orders.user']);
        
        // Statistiques de l'événement
        $stats = [
            'total_revenue' => $event->orders()->where('payment_status', 'paid')->sum('total_amount'),
            'tickets_sold' => $event->tickets()->count(),
            'tickets_used' => $event->tickets()->where('payment_status', 'used')->count(),
            'pending_orders' => $event->orders()->where('payment_status', 'pending')->count(),
        ];
        
        // Ventes récentes
        $recentOrders = $event->orders()
            ->with('user')
            ->where('payment_status', 'paid')
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->get();

        return view('promoteur.events.show', compact('event', 'stats', 'recentOrders'));
    }

    /**
     * Formulaire de création d'événement
     */
    public function create()
    {
        $categories = Category::where('is_active', true)->orderBy('name')->get();
        
        return view('promoteur.events.create', compact('categories'));
    }

    /**
     * Enregistrer un nouvel événement
     */
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'category_id' => 'required|exists:categories,id',
            'event_date' => 'required|date|after:now',
            'location' => 'required|string|max:255',
            'max_capacity' => 'nullable|integer|min:1',
            'image' => 'nullable|image|max:2048',
            'status' => 'in:draft,pending'
        ]);

        $eventData = $request->except(['image']);
        $eventData['promoter_id'] = Auth::id();
        $eventData['slug'] = Str::slug($request->title);
        
        // Gestion de l'image
        if ($request->hasFile('image')) {
            $eventData['image'] = $request->file('image')->store('events', 'public');
        }

        $event = Event::create($eventData);

        $message = $event->status === 'draft' 
            ? 'Événement sauvé en brouillon' 
            : 'Événement créé et soumis pour validation';

        return redirect()->route('promoteur.events.show', $event)
            ->with('success', $message);
    }

    /**
     * Formulaire d'édition d'événement
     */
    public function edit(Event $event)
    {
        $this->authorize('update', $event);
        
        $categories = Category::where('is_active', true)->orderBy('name')->get();
        
        return view('promoteur.events.edit', compact('event', 'categories'));
    }

    /**
     * Mettre à jour un événement
     */
    public function update(Request $request, Event $event)
    {
        $this->authorize('update', $event);
        
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'category_id' => 'required|exists:categories,id',
            'event_date' => 'required|date',
            'location' => 'required|string|max:255',
            'max_capacity' => 'nullable|integer|min:1',
            'image' => 'nullable|image|max:2048',
        ]);

        $eventData = $request->except(['image']);
        
        // Régénérer le slug si le titre a changé
        if ($request->title !== $event->title) {
            $eventData['slug'] = Str::slug($request->title);
        }
        
        // Gestion de l'image
        if ($request->hasFile('image')) {
            // Supprimer l'ancienne image
            if ($event->image) {
                Storage::disk('public')->delete($event->image);
            }
            $eventData['image'] = $request->file('image')->store('events', 'public');
        }

        $event->update($eventData);

        return redirect()->route('promoteur.events.show', $event)
            ->with('success', 'Événement mis à jour avec succès');
    }

    /**
     * Supprimer un événement
     */
    public function destroy(Event $event)
    {
        $this->authorize('delete', $event);
        
        // Vérifier qu'il n'y a pas de commandes payées
        if ($event->orders()->where('payment_status', 'paid')->exists()) {
            return back()->with('error', 'Impossible de supprimer un événement avec des commandes payées');
        }
        
        // Supprimer l'image
        if ($event->image) {
            Storage::disk('public')->delete($event->image);
        }
        
        $event->delete();
        
        return redirect()->route('promoteur.events.index')
            ->with('success', 'Événement supprimé avec succès');
    }

    /**
     * Publier un événement
     */
    public function publish(Event $event)
    {
        $this->authorize('update', $event);
        
        // Vérifications avant publication
        if (!$event->ticketTypes()->where('is_active', true)->exists()) {
            return back()->with('error', 'Vous devez avoir au moins un type de billet actif pour publier l\'événement');
        }
        
        if ($event->event_date <= now()) {
            return back()->with('error', 'Impossible de publier un événement passé');
        }

        $event->update(['status' => 'published']);
        
        return back()->with('success', 'Événement publié avec succès');
    }

    /**
     * Dépublier un événement
     */
    public function unpublish(Event $event)
    {
        $this->authorize('update', $event);
        
        $event->update(['status' => 'draft']);
        
        return back()->with('success', 'Événement retiré de la publication');
    }

    /**
     * Dupliquer un événement
     */
    public function duplicate(Event $event)
    {
        $this->authorize('view', $event);
        
        $newEvent = $event->replicate();
        $newEvent->title = $event->title . ' (Copie)';
        $newEvent->slug = Str::slug($newEvent->title);
        $newEvent->status = 'draft';
        $newEvent->event_date = null;
        $newEvent->save();
        
        // Dupliquer les types de billets
        foreach ($event->ticketTypes as $ticketType) {
            $newTicketType = $ticketType->replicate();
            $newTicketType->event_id = $newEvent->id;
            $newTicketType->save();
        }
        
        return redirect()->route('promoteur.events.edit', $newEvent)
            ->with('success', 'Événement dupliqué avec succès');
    }

    /**
     * Prévisualiser un événement
     */
    public function preview(Event $event)
    {
        $this->authorize('view', $event);
        
        return view('events.show', compact('event'));
    }

    /**
     * Obtenir les statistiques rapides pour AJAX
     */
    public function getQuickStats(Event $event)
    {
        $this->authorize('view', $event);
        
        return response()->json([
            'total_revenue' => $event->orders()->where('payment_status', 'paid')->sum('total_amount'),
            'tickets_sold' => $event->tickets()->count(),
            'tickets_used' => $event->tickets()->where('payment_status', 'used')->count(),
            'pending_orders' => $event->orders()->where('payment_status', 'pending')->count(),
        ]);
    }
}