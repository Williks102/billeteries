<?php
// app/Http/Controllers/Promoteur/PromoteurController.php - VERSION HARMONISÉE

namespace App\Http\Controllers\Promoteur;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Models\{Event, EventCategory, Order, TicketType, Ticket};
use Carbon\Carbon;

class PromoteurController extends Controller
{
    /** Dashboard principal */
    public function dashboard()
    {
        try {
            $promoteur = Auth::user();

            $stats = [
                'total_events' => $promoteur->events()->count() ?? 0,
                'published_events' => $promoteur->events()->where('status', 'published')->count() ?? 0,
                'upcoming_events' => $promoteur->events()
                    ->where('status', 'published')
                    ->where('event_date', '>=', now()->toDateString())
                    ->count() ?? 0,
                'total_revenue' => $promoteur->totalRevenue() ?? 0,
                'pending_revenue' => $promoteur->pendingRevenue() ?? 0,
            ];

            $recentEvents = $promoteur->events()
                ->with(['category', 'ticketTypes'])
                ->latest()
                ->limit(5)
                ->get();

            $recentOrders = Order::whereHas('event', function($q) use ($promoteur) {
                    $q->where('promoter_id', $promoter->id);  // ✅ CHANGÉ: promoter_id → promoter_id
                })
                ->with(['user', 'event'])
                ->where('payment_status', 'paid')
                ->latest()
                ->limit(10)
                ->get();

            return view('promoteur.dashboard', compact('stats', 'recentEvents', 'recentOrders'));
            
        } catch (\Exception $e) {
            \Log::error('Erreur dans promoteur.dashboard: ' . $e->getMessage());
            
            $stats = [
                'total_events' => 0,
                'published_events' => 0,
                'upcoming_events' => 0,
                'total_revenue' => 0,
                'pending_revenue' => 0,
            ];
            
            $recentEvents = collect();
            $recentOrders = collect();
            
            return view('promoteur.dashboard', compact('stats', 'recentEvents', 'recentOrders'))
                ->with('error', 'Erreur lors du chargement du dashboard');
        }
    }

    /** Liste des événements */
    public function events(Request $request)
    {
        try {
            $query = Auth::user()->events()->with(['category', 'ticketTypes']);
            
            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }
            
            $events = $query->latest()->paginate(12);
            $categories = EventCategory::all();

            return view('promoteur.events.index', compact('events', 'categories'));
            
        } catch (\Exception $e) {
            \Log::error('Erreur dans promoteur.events: ' . $e->getMessage());
            
            return redirect()->route('promoteur.dashboard')
                ->with('error', 'Erreur lors du chargement des événements');
        }
    }

    /** Créer un événement */
    public function create()
    {
        $categories = EventCategory::all();
        return view('promoteur.events.create', compact('categories'));
    }

    /** Sauvegarder un nouvel événement */
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'category_id' => 'required|exists:event_categories,id',
            'venue' => 'required|string|max:255',
            'address' => 'required|string',
            'event_date' => 'required|date|after:today',
            'event_time' => 'required|date_format:H:i',
            'end_time' => 'nullable|date_format:H:i|after:event_time',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        try {
            $imagePath = null;
            
            if ($request->hasFile('image')) {
                $imagePath = $request->file('image')->store('events', 'public');
            }

            $event = Event::create([
                'promoter_id' => Auth::id(),  // ✅ CHANGÉ: promoteur_id → promoter_id
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
            ]);

            return redirect()->route('promoteur.events.tickets.create', $event)->with('success', 'Événement créé. Configurez les billets.');
            
        } catch (\Exception $e) {
            \Log::error('Erreur lors de la création de l\'événement: ' . $e->getMessage());
            
            return redirect()->back()
                ->with('error', 'Erreur lors de la création de l\'événement')
                ->withInput();
        }
    }

    /** Afficher un événement */
    public function show(Event $event)
    {
        try {
            abort_if($event->promoter_id !== Auth::id(), 403);  // ✅ CHANGÉ: promoteur_id → promoter_id

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

    /** Afficher le formulaire d'édition */
    public function edit(Event $event)
    {
        abort_if($event->promoter_id !== Auth::id(), 403, 'Vous n\'êtes pas autorisé à modifier cet événement');  // ✅ CHANGÉ
        
        try {
            $categories = EventCategory::all();
            return view('promoteur.events.edit', compact('event', 'categories'));
            
        } catch (\Exception $e) {
            \Log::error('Erreur lors de l\'affichage du formulaire d\'édition: ' . $e->getMessage());
            
            return redirect()->route('promoteur.events.index')
                ->with('error', 'Impossible de charger le formulaire d\'édition');
        }
    }

    /** Mettre à jour un événement */
    public function update(Request $request, Event $event)
    {
        abort_if($event->promoter_id !== Auth::id(), 403, 'Vous n\'êtes pas autorisé à modifier cet événement');  // ✅ CHANGÉ

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

    /** Supprimer un événement */
    public function destroy(Event $event)
    {
        abort_if($event->promoter_id !== Auth::id(), 403, 'Vous n\'êtes pas autorisé à supprimer cet événement');  // ✅ CHANGÉ

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

    /** Dupliquer un événement */
    public function duplicate(Event $event)
    {
        abort_if($event->promoter_id !== Auth::id(), 403);  // ✅ CHANGÉ

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
            \Log::error('Erreur lors de la duplication: ' . $e->getMessage());
            
            return redirect()->back()
                ->with('error', 'Erreur lors de la duplication de l\'événement');
        }
    }

    /** Publier un événement */
    public function publish(Event $event)
    {
        abort_if($event->promoter_id !== Auth::id(), 403);  // ✅ CHANGÉ

        if ($event->ticketTypes()->where('is_active', true)->count() === 0) {
            return back()->with('error', 'Ajoutez au moins un billet avant publication.');
        }

        $event->update(['status' => 'published']);
        return back()->with('success', 'Événement publié.');
    }

    /** Dépublier un événement */
    public function unpublish(Event $event)
    {
        abort_if($event->promoter_id !== Auth::id(), 403);  // ✅ CHANGÉ
        $event->update(['status' => 'draft']);
        return back()->with('success', 'Événement dépublié.');
    }

    /** Recherche de billets AJAX */
    public function searchTickets(Request $request)
    {
        if (!$request->filled('search') || strlen($request->search) < 3) {
            return response()->json([]);
        }

        try {
            $search = $request->search;
            
            $tickets = Ticket::whereHas('ticketType.event', function($q) {
                    $q->where('promoter_id', Auth::id());  // ✅ CHANGÉ: promoteur_id → promoter_id
                })
                ->where(function($q) use ($search) {
                    $q->where('ticket_code', 'like', '%' . $search . '%')
                      ->orWhere('holder_name', 'like', '%' . $search . '%')
                      ->orWhere('holder_email', 'like', '%' . $search . '%');
                })
                ->limit(10)
                ->get()
                ->map(function($ticket) {
                    return [
                        'ticket_code' => $ticket->ticket_code,
                        'holder_name' => $ticket->holder_name,
                        'holder_email' => $ticket->holder_email,
                        'event_title' => $ticket->ticketType->event->title,
                        'ticket_type' => $ticket->ticketType->name,
                        'status' => $ticket->status,
                        'used_at' => $ticket->used_at ? $ticket->used_at->format('d/m/Y H:i') : null,
                        'event_date' => $ticket->ticketType->event->event_date->format('d/m/Y'),
                    ];
                });
                
            return response()->json($tickets);
            
        } catch (\Exception $e) {
            \Log::error('Erreur recherche billet: ' . $e->getMessage());
            
            return response()->json([
                'error' => 'Erreur lors de la recherche'
            ], 500);
        }
    }
}
