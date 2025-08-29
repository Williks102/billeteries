<?php

namespace App\Http\Controllers\Promoteur;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Models\{Event, EventCategory};
use Carbon\Carbon;

class EventController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('promoteur');
    }

    /**
     * Liste des événements du promoteur
     */
    public function index(Request $request)
    {
        try {
            $query = Auth::user()->events()->with(['category', 'ticketTypes']);
            
            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }
            
            if ($request->filled('search')) {
                $query->where(function($q) use ($request) {
                    $q->where('title', 'like', '%' . $request->search . '%')
                      ->orWhere('description', 'like', '%' . $request->search . '%')
                      ->orWhere('venue', 'like', '%' . $request->search . '%');
                });
            }
            
            $events = $query->latest()->paginate(12);
            $categories = EventCategory::all();

            return view('promoteur.events.index', compact('events', 'categories'));
            
        } catch (\Exception $e) {
            \Log::error('Erreur dans promoteur.events.index: ' . $e->getMessage());
            
            $events = collect();
            $categories = collect();
            
            return view('promoteur.events.index', compact('events', 'categories'))
                ->with('error', 'Erreur lors du chargement des événements');
        }
    }

    /**
     * Formulaire création événement
     */
    public function create()
    {
        $categories = EventCategory::all();
        return view('promoteur.events.create', compact('categories'));
    }

    /**
     * Sauvegarde événement
     */
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required', 
            'description' => 'required',
            'category_id' => 'required|exists:event_categories,id',
            'venue' => 'required', 
            'address' => 'required',
            'event_date' => 'required|date|after:today',
            'event_time' => 'required|date_format:H:i',
            'end_time' => 'nullable|date_format:H:i|after:event_time',
            'image' => 'nullable|image|max:2048',
            'terms_conditions' => 'nullable|string',
        ]);

        try {
            $imagePath = $request->hasFile('image')
                ? $request->file('image')->store('events', 'public')
                : null;

            $event = Event::create([
                'promoter_id' => Auth::id(),
                'category_id' => $request->category_id,
                'title' => $request->title,
                'description' => $request->description,
                'venue' => $request->venue,
                'address' => $request->address,
                'event_date' => $request->event_date,
                'event_time' => Carbon::createFromFormat('Y-m-d H:i', $request->event_date . ' ' . $request->event_time),
                'end_time' => $request->end_time ? Carbon::createFromFormat('Y-m-d H:i', $request->event_date . ' ' . $request->end_time) : null,
                'status' => 'draft',
                'image' => $imagePath,
                'terms_conditions' => $request->terms_conditions,
            ]);

            return redirect()->route('promoteur.events.tickets.index', $event)
                ->with('success', 'Événement créé. Configurez les billets.');

        } catch (\Exception $e) {
            \Log::error('Erreur création événement: ' . $e->getMessage());
            
            return back()
                ->withInput()
                ->with('error', 'Erreur lors de la création de l\'événement');
        }
    }

    /**
     * Affichage d'un événement
     */
    public function show(Event $event)
    {
        try {
            abort_if($event->promoter_id !== Auth::id(), 403);

            $event->load(['category', 'ticketTypes', 'orders.user']);
            
            $stats = [
                'total_revenue' => $event->totalRevenue(),
                'tickets_sold' => $event->getTicketsSoldCount(),
                'tickets_available' => $event->totalTicketsAvailable(),
                'orders_count' => $event->getOrdersCount(),
                'progress_percentage' => $event->getProgressPercentage(),
            ];

            $recentOrders = $event->orders()
                ->where('payment_status', 'paid')
                ->with('user')
                ->latest()
                ->limit(10)
                ->get();
                
            return view('promoteur.events.show', compact('event', 'stats', 'recentOrders'));
            
        } catch (\Exception $e) {
            \Log::error('Erreur dans promoteur.events.show: ' . $e->getMessage());
            
            return redirect()->route('promoteur.events.index')
                ->with('error', 'Erreur lors du chargement de l\'événement');
        }
    }

    /**
     * Formulaire d'édition d'un événement
     */
    public function edit(Event $event)
    {
        abort_if($event->promoter_id !== Auth::id(), 403, 'Vous n\'êtes pas autorisé à modifier cet événement');
        
        try {
            $categories = EventCategory::all();
            return view('promoteur.events.edit', compact('event', 'categories'));
            
        } catch (\Exception $e) {
            \Log::error('Erreur lors de l\'affichage du formulaire d\'édition: ' . $e->getMessage());
            
            return redirect()->route('promoteur.events.index')
                ->with('error', 'Impossible de charger le formulaire d\'édition');
        }
    }

    /**
     * Mettre à jour un événement existant
     */
    public function update(Request $request, Event $event)
    {
        abort_if($event->promoter_id !== Auth::id(), 403, 'Vous n\'êtes pas autorisé à modifier cet événement');

        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'category_id' => 'required|exists:event_categories,id',
            'venue' => 'required|string|max:255',
            'address' => 'required|string',
            'event_date' => 'required|date',
            'event_time' => 'required|date_format:H:i',
            'end_time' => 'nullable|date_format:H:i|after:event_time',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'terms_conditions' => 'nullable|string',
        ]);

        try {
            $imagePath = $event->image;
            
            if ($request->hasFile('image')) {
                if ($event->image && Storage::disk('public')->exists($event->image)) {
                    Storage::disk('public')->delete($event->image);
                }
                
                $imagePath = $request->file('image')->store('events', 'public');
            }

            $event->update([
                'category_id' => $request->category_id,
                'title' => $request->title,
                'description' => $request->description,
                'venue' => $request->venue,
                'address' => $request->address,
                'event_date' => $request->event_date,
                'event_time' => Carbon::createFromFormat('Y-m-d H:i', $request->event_date . ' ' . $request->event_time),
                'end_time' => $request->end_time ? Carbon::createFromFormat('Y-m-d H:i', $request->event_date . ' ' . $request->end_time) : null,
                'image' => $imagePath,
                'terms_conditions' => $request->terms_conditions,
            ]);

            return redirect()->route('promoteur.events.show', $event)
                ->with('success', 'Événement mis à jour avec succès !');
                
        } catch (\Exception $e) {
            \Log::error('Erreur lors de la mise à jour de l\'événement: ' . $e->getMessage());
            
            return redirect()->back()
                ->with('error', 'Erreur lors de la mise à jour de l\'événement')
                ->withInput();
        }
    }

    /**
     * Supprimer un événement
     */
    public function destroy(Event $event)
    {
        abort_if($event->promoter_id !== Auth::id(), 403, 'Vous n\'êtes pas autorisé à supprimer cet événement');

        try {
            $paidOrders = $event->orders()->where('payment_status', 'paid')->count();
            
            if ($paidOrders > 0) {
                return redirect()->back()
                    ->with('error', 'Impossible de supprimer un événement avec des billets vendus');
            }

            if ($event->image && Storage::disk('public')->exists($event->image)) {
                Storage::disk('public')->delete($event->image);
            }

            $event->delete();

            return redirect()->route('promoteur.events.index')
                ->with('success', 'Événement supprimé avec succès');
                
        } catch (\Exception $e) {
            \Log::error('Erreur lors de la suppression de l\'événement: ' . $e->getMessage());
            
            return redirect()->back()
                ->with('error', 'Erreur lors de la suppression de l\'événement');
        }
    }

    /**
     * Publier un événement
     */
    public function publish(Event $event)
    {
        abort_if($event->promoter_id !== Auth::id(), 403);

        if ($event->ticketTypes()->where('is_active', true)->count() === 0) {
            return back()->with('error', 'Ajoutez au moins un billet avant publication.');
        }

        $event->update(['status' => 'published']);
        return back()->with('success', 'Événement publié.');
    }

    /**
     * Dépublier un événement
     */
    public function unpublish(Event $event)
    {
        abort_if($event->promoter_id !== Auth::id(), 403);
        $event->update(['status' => 'draft']);
        return back()->with('success', 'Événement dépublié.');
    }

    /**
     * Dupliquer un événement
     */
    public function duplicate(Event $event)
    {
        abort_if($event->promoter_id !== Auth::id(), 403);

        try {
            $newEvent = $event->replicate();
            $newEvent->title = $event->title . ' (Copie)';
            $newEvent->status = 'draft';
            $newEvent->event_date = now()->addDays(7);
            $newEvent->created_at = now();
            $newEvent->updated_at = now();
            $newEvent->save();

            foreach ($event->ticketTypes as $ticketType) {
                $newTicketType = $ticketType->replicate();
                $newTicketType->event_id = $newEvent->id;
                $newTicketType->quantity_sold = 0;
                $newTicketType->save();
            }

            return redirect()->route('promoteur.events.edit', $newEvent)
                ->with('success', 'Événement dupliqué avec succès ! Vous pouvez maintenant le modifier.');
                
        } catch (\Exception $e) {
            \Log::error('Erreur lors de la duplication de l\'événement: ' . $e->getMessage());
            
            return redirect()->back()
                ->with('error', 'Erreur lors de la duplication de l\'événement');
        }
    }
}