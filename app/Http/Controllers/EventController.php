<?php

namespace App\Http\Controllers;

use App\Support\Media;
use App\Models\Event;
use App\Models\EventCategory;
use App\Services\Stats\PublicStats;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EventController extends Controller
{
    /**
     * Affichage du détail d'un événement (public)
     */
    public function show(Event $event, PublicStats $publicStats)
    {
        // Vérifier que l'événement est publié
        if ($event->status !== 'published') {
            abort(404, 'Événement non trouvé ou non publié');
        }

        // Charger les relations nécessaires. AUCUNE commande n'est chargée : le public ne voit aucun chiffre de vente.
        $event->load([
            'category',
            'promoteur',
            'ticketTypes' => function($query) {
                $query->where('is_active', true)
                      ->where('sale_start_date', '<=', now())
                      ->where('sale_end_date', '>=', now())
                      ->orderBy('price', 'asc');
            },
        ]);

        // Places restantes AFFICHÉES (décision D6) : capacité - quantity_sold, réservations non payées déduites
        // (anti-survente). Aucune vente détaillée ni taux de remplissage n'est exposé au public.
        $availability = $publicStats->remaining($event);
        $hasAvailableTickets = $availability['total'] > 0;

        // Événements similaires (même catégorie)
        $similarEvents = Event::with(['category', 'ticketTypes'])
            ->where('category_id', $event->category_id)
            ->where('id', '!=', $event->id)
            ->where('status', 'published')
            ->where('event_date', '>=', now()->toDateString())
            ->limit(4)
            ->get();

        return view('events.show', compact(
            'event',
            'similarEvents',
            'hasAvailableTickets'
        ));
    }

    /**
     * Liste de tous les événements avec pagination
     */
    public function index(Request $request)
    {
        $query = Event::with(['category', 'ticketTypes', 'promoteur'])
            ->where('status', 'published')
            ->where('event_date', '>=', now()->toDateString());

        // Filtres simples
        if ($request->filled('category')) {
            $query->where('category_id', $request->category);
        }

        if ($request->filled('search')) {
            $searchTerm = $request->search;
            $query->where(function($q) use ($searchTerm) {
                $q->where('title', 'like', '%' . $searchTerm . '%')
                  ->orWhere('description', 'like', '%' . $searchTerm . '%')
                  ->orWhere('venue', 'like', '%' . $searchTerm . '%');
            });
        }

        // Filtres de date prédéfinis
        $dateFilter = $request->get('date_filter', 'all');
        switch ($dateFilter) {
            case 'today':
                $query->whereDate('event_date', today());
                break;
            case 'tomorrow':
                $query->whereDate('event_date', today()->addDay());
                break;
            case 'this_week':
                $query->whereBetween('event_date', [
                    now()->startOfWeek(),
                    now()->endOfWeek()
                ]);
                break;
            case 'this_month':
                $query->whereMonth('event_date', now()->month)
                      ->whereYear('event_date', now()->year);
                break;
            case 'next_month':
                $nextMonth = now()->addMonth();
                $query->whereMonth('event_date', $nextMonth->month)
                      ->whereYear('event_date', $nextMonth->year);
                break;
        }

        $events = $query->orderBy('event_date', 'asc')->paginate(12);

        // Données complémentaires
        $categories = EventCategory::withActiveEvents()->get();
        
        $stats = [
            'total_events' => Event::where('status', 'published')->count(),
            'total_categories' => $categories->count(),
            'upcoming_events' => Event::where('status', 'published')
                ->where('event_date', '>=', now()->toDateString())
                ->count(),
            'today_events' => Event::where('status', 'published')
                ->whereDate('event_date', today())
                ->count(),
            'this_week_events' => Event::where('status', 'published')
                ->whereBetween('event_date', [
                    now()->startOfWeek(),
                    now()->endOfWeek()
                ])
                ->count(),
        ];

        return view('events.all', compact('events', 'categories', 'stats'));
    }

    /**
     * API - Obtenir les événements (pour AJAX/JavaScript)
     */
    public function api(Request $request)
    {
        $query = Event::with(['category', 'ticketTypes'])
            ->where('status', 'published')
            ->where('event_date', '>=', now()->toDateString());

        // Filtres API
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->filled('limit')) {
            $query->limit($request->limit);
        }

        $events = $query->orderBy('event_date', 'asc')->get();

        // Transformer les données pour l'API
        $eventsData = $events->map(function($event) {
            return [
                'id' => $event->id,
                'title' => $event->title,
                'description' => $event->description,
                'venue' => $event->venue,
                'event_date' => $event->event_date->format('Y-m-d'),
                'event_time' => $event->event_time?->format('H:i'),
                'category' => [
                    'id' => $event->category->id,
                    'name' => $event->category->name,
                    'slug' => $event->category->slug,
                ],
                'min_price' => $event->ticketTypes->min('price') ?? 0,
                'max_price' => $event->ticketTypes->max('price') ?? 0,
                'image' => $event->image ? Media::url($event->image) : null,
                'url' => route('events.show', $event),
                'available_tickets' => $event->ticketTypes->sum(function($type) {
                    return $type->remainingTickets();
                }),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $eventsData,
            'total' => $events->count(),
        ]);
    }

}