<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\EventCategory;
use App\Models\TicketType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class EventController extends Controller
{
    /**
     * Affichage du détail d'un événement (public)
     */
    public function show(Event $event)
    {
        // Vérifier que l'événement est publié
        if ($event->status !== 'published') {
            abort(404, 'Événement non trouvé ou non publié');
        }

        // Charger les relations nécessaires
        $event->load([
            'category', 
            'promoteur', 
            'ticketTypes' => function($query) {
                $query->where('is_active', true)
                      ->where('sale_start_date', '<=', now())
                      ->where('sale_end_date', '>=', now())
                      ->orderBy('price', 'asc');
            },
            'orders' => function($query) {
                $query->where('payment_status', 'paid');
            }
        ]);

        // Vérifier que l'événement a des billets disponibles
        $hasAvailableTickets = $event->ticketTypes->filter(function($ticketType) {
            return $ticketType->remainingTickets() > 0;
        })->count() > 0;

        // Événements similaires (même catégorie)
        $similarEvents = Event::with(['category', 'ticketTypes'])
            ->where('category_id', $event->category_id)
            ->where('id', '!=', $event->id)
            ->where('status', 'published')
            ->where('event_date', '>=', now()->toDateString())
            ->limit(4)
            ->get();

        // Statistiques de l'événement
        $eventStats = [
            'total_tickets' => $event->ticketTypes->sum('quantity_available') ?? 0,
            'sold_tickets' => $event->ticketTypes->sum('quantity_sold') ?? 0,
            'available_tickets' => $event->ticketTypes->sum(function($type) {
                return $type->remainingTickets();
            }),
            'min_price' => $event->ticketTypes->min('price') ?? 0,
            'max_price' => $event->ticketTypes->max('price') ?? 0,
            'total_revenue' => $event->orders->sum('total_amount') ?? 0,
        ];

        // Calcul du pourcentage de vente
        $salesPercentage = $eventStats['total_tickets'] > 0 
            ? round(($eventStats['sold_tickets'] / $eventStats['total_tickets']) * 100, 1)
            : 0;

        return view('events.show', compact(
            'event', 
            'similarEvents', 
            'hasAvailableTickets',
            'eventStats',
            'salesPercentage'
        ));
    }

    /**
     * Recherche d'événements avec filtres
     */
    public function search(Request $request)
    {
        $query = Event::with(['category', 'ticketTypes', 'promoteur'])
            ->where('status', 'published')
            ->where('event_date', '>=', now()->toDateString());

        // Filtres de recherche
        if ($request->filled('q')) {
            $searchTerm = $request->q;
            $query->where(function($q) use ($searchTerm) {
                $q->where('title', 'like', '%' . $searchTerm . '%')
                  ->orWhere('description', 'like', '%' . $searchTerm . '%')
                  ->orWhere('venue', 'like', '%' . $searchTerm . '%')
                  ->orWhereHas('category', function($categoryQuery) use ($searchTerm) {
                      $categoryQuery->where('name', 'like', '%' . $searchTerm . '%');
                  });
            });
        }

        // Filtre par catégorie
        if ($request->filled('category')) {
            if (is_numeric($request->category)) {
                $query->where('category_id', $request->category);
            } else {
                $query->whereHas('category', function($q) use ($request) {
                    $q->where('slug', $request->category);
                });
            }
        }

        // Filtre par date
        if ($request->filled('date_from')) {
            $query->where('event_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->where('event_date', '<=', $request->date_to);
        }

        // Filtre par prix
        if ($request->filled('min_price') || $request->filled('max_price')) {
            $query->whereHas('ticketTypes', function($q) use ($request) {
                if ($request->filled('min_price')) {
                    $q->where('price', '>=', $request->min_price * 100); // Convertir en centimes
                }
                if ($request->filled('max_price')) {
                    $q->where('price', '<=', $request->max_price * 100);
                }
            });
        }

        // Filtre par lieu/ville
        if ($request->filled('location')) {
            $query->where('venue', 'like', '%' . $request->location . '%')
                  ->orWhere('address', 'like', '%' . $request->location . '%');
        }

        // Tri
        $sortBy = $request->get('sort', 'date');
        switch ($sortBy) {
            case 'date':
                $query->orderBy('event_date', 'asc');
                break;
            case 'price_low':
                $query->leftJoin('ticket_types', 'events.id', '=', 'ticket_types.event_id')
                      ->orderBy('ticket_types.price', 'asc');
                break;
            case 'price_high':
                $query->leftJoin('ticket_types', 'events.id', '=', 'ticket_types.event_id')
                      ->orderBy('ticket_types.price', 'desc');
                break;
            case 'popularity':
                $query->withCount(['orders' => function($q) {
                    $q->where('payment_status', 'paid');
                }])->orderBy('orders_count', 'desc');
                break;
            case 'name':
                $query->orderBy('title', 'asc');
                break;
            default:
                $query->orderBy('event_date', 'asc');
        }

        $events = $query->paginate(12)->appends($request->query());

        // Données pour les filtres
        $categories = EventCategory::withActiveEvents()->orderBy('name')->get();
        $locations = Event::where('status', 'published')
            ->where('event_date', '>=', now()->toDateString())
            ->distinct()
            ->pluck('venue')
            ->filter()
            ->sort()
            ->values();

        // Statistiques de recherche
        $searchStats = [
            'total_results' => $events->total(),
            'categories_count' => $categories->count(),
            'locations_count' => $locations->count(),
            'date_range' => [
                'min' => Event::where('status', 'published')->min('event_date'),
                'max' => Event::where('status', 'published')->max('event_date'),
            ]
        ];

        return view('events.search', compact(
            'events', 
            'categories', 
            'locations', 
            'searchStats'
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
     * Événements par catégorie
     */
    public function category(EventCategory $category, Request $request)
    {
        $query = Event::with(['category', 'ticketTypes', 'promoteur'])
            ->where('category_id', $category->id)
            ->where('status', 'published')
            ->where('event_date', '>=', now()->toDateString());

        // Recherche dans la catégorie
        if ($request->filled('search')) {
            $searchTerm = $request->search;
            $query->where(function($q) use ($searchTerm) {
                $q->where('title', 'like', '%' . $searchTerm . '%')
                  ->orWhere('description', 'like', '%' . $searchTerm . '%')
                  ->orWhere('venue', 'like', '%' . $searchTerm . '%');
            });
        }

        // Tri
        $sortBy = $request->get('sort', 'date');
        switch ($sortBy) {
            case 'date':
                $query->orderBy('event_date', 'asc');
                break;
            case 'price':
                $query->join('ticket_types', 'events.id', '=', 'ticket_types.event_id')
                      ->orderBy('ticket_types.price', 'asc');
                break;
            case 'popularity':
                $query->withCount('orders')->orderBy('orders_count', 'desc');
                break;
            default:
                $query->orderBy('event_date', 'asc');
        }

        $events = $query->paginate(12)->appends($request->query());

        // Statistiques de la catégorie
        $categoryStats = [
            'total_events' => $category->events()->where('status', 'published')->count(),
            'upcoming_events' => $events->total(),
            'avg_price' => $category->events()
                ->join('ticket_types', 'events.id', '=', 'ticket_types.event_id')
                ->avg('ticket_types.price') ?? 0,
        ];

        // Autres catégories pour navigation
        $allCategories = EventCategory::withActiveEvents()->get();

        return view('events.category', compact(
            'category', 
            'events', 
            'categoryStats', 
            'allCategories'
        ));
    }

    /**
     * Événements populaires/tendance
     */
    public function trending()
    {
        $trendingEvents = Event::with(['category', 'ticketTypes', 'promoteur'])
            ->where('status', 'published')
            ->where('event_date', '>=', now()->toDateString())
            ->withCount(['orders' => function($query) {
                $query->where('payment_status', 'paid')
                      ->where('created_at', '>=', now()->subWeek()); // Commandes récentes
            }])
            ->orderBy('orders_count', 'desc')
            ->limit(10)
            ->get();

        return view('events.trending', compact('trendingEvents'));
    }

    /**
     * Événements par lieu/ville
     */
    public function byLocation($location)
    {
        $events = Event::with(['category', 'ticketTypes', 'promoteur'])
            ->where('status', 'published')
            ->where('event_date', '>=', now()->toDateString())
            ->where(function($query) use ($location) {
                $query->where('venue', 'like', '%' . $location . '%')
                      ->orWhere('address', 'like', '%' . $location . '%');
            })
            ->orderBy('event_date', 'asc')
            ->paginate(12);

        $locationStats = [
            'total_events' => $events->total(),
            'categories' => EventCategory::whereHas('events', function($query) use ($location) {
                $query->where('status', 'published')
                      ->where(function($q) use ($location) {
                          $q->where('venue', 'like', '%' . $location . '%')
                            ->orWhere('address', 'like', '%' . $location . '%');
                      });
            })->count(),
        ];

        return view('events.location', compact('events', 'location', 'locationStats'));
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
                'image' => $event->image ? Storage::url($event->image) : null,
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

    /**
     * Recommandations d'événements basées sur l'historique
     */
    public function recommendations()
    {
        if (!Auth::check()) {
            // Événements populaires pour les invités
            return $this->trending();
        }

        $user = Auth::user();
        
        // Récupérer les catégories préférées de l'utilisateur
        $preferredCategories = $user->orders()
            ->where('payment_status', 'paid')
            ->with('event.category')
            ->get()
            ->pluck('event.category.id')
            ->filter()
            ->unique()
            ->values();

        if ($preferredCategories->isEmpty()) {
            return $this->trending();
        }

        // Recommander des événements dans les catégories préférées
        $recommendedEvents = Event::with(['category', 'ticketTypes', 'promoteur'])
            ->where('status', 'published')
            ->where('event_date', '>=', now()->toDateString())
            ->whereIn('category_id', $preferredCategories)
            ->whereNotIn('id', $user->orders()->pluck('event_id')) // Exclure les événements déjà achetés
            ->orderBy('event_date', 'asc')
            ->limit(8)
            ->get();

        return view('events.recommendations', compact('recommendedEvents'));
    }
}