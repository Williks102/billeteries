<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Event;
use App\Models\EventCategory;
use App\Models\User;
use App\Models\Ticket;
use App\Models\Order;

class HomeController extends Controller
{
    // PAS de middleware auth dans le constructeur pour permettre l'accès public !
    
    /**
     * Affichage de la page d'accueil avec statistiques complètes
     */
    public function index(Request $request)
    {
        try {
            // Récupérer les événements publiés et à venir
            $query = Event::with(['category', 'ticketTypes', 'promoter'])
                ->where('status', 'published')
                ->where('event_date', '>=', now()->toDateString())
                ->orderBy('event_date', 'asc');

            // Filtrer par catégorie si demandé
            if ($request->has('category') && $request->category != '') {
                $categorySlug = $request->category;
                $query->whereHas('category', function($q) use ($categorySlug) {
                    $q->where('slug', $categorySlug);
                });
            }

            // Recherche par mot-clé
            if ($request->has('search') && $request->search != '') {
                $searchTerm = $request->search;
                $query->where(function($q) use ($searchTerm) {
                    $q->where('title', 'like', '%' . $searchTerm . '%')
                      ->orWhere('description', 'like', '%' . $searchTerm . '%')
                      ->orWhere('venue', 'like', '%' . $searchTerm . '%');
                });
            }

            // Limiter à 12 événements pour la page d'accueil (ou paginer si nécessaire)
            $events = $query->limit(12)->get();
            
            // Récupérer les catégories pour les filtres
            $categories = EventCategory::withActiveEvents()->get();

            // Statistiques détaillées pour la page d'accueil
            $totalEvents = Event::where('status', 'published')->count();
            $totalTickets = Ticket::where('status', 'sold')->count();
            $totalUsers = User::where('role', 'acheteur')->count();
            $totalPromoters = User::where('role', 'promoteur')->count();

            // Statistiques pour les cartes de la page d'accueil
            $stats = [
                'total_events' => $totalEvents,
                'total_categories' => $categories->count(),
                'upcoming_events' => Event::where('status', 'published')
                    ->where('event_date', '>=', now()->toDateString())
                    ->count(),
                'today_events' => Event::where('status', 'published')
                    ->where('event_date', now()->toDateString())
                    ->count(),
                'sold_tickets' => $totalTickets,
                'active_users' => $totalUsers,
                'active_promoters' => $totalPromoters,
            ];

        // CORRECTION : Afficher les événements les plus récents en premier
$events = $query->orderBy('created_at', 'desc')
              ->orderBy('event_date', 'asc')
              ->limit(12)
              ->get();

            return view('welcome', compact(
                'events', 
                'categories', 
                'stats',
                'totalEvents',
                'totalTickets', 
                'totalUsers'
            ));
            
        } catch (\Exception $e) {
            \Log::error('Erreur page d\'accueil: ' . $e->getMessage());
            
            // En cas d'erreur, retourner une page basique
            $events = collect();
            $categories = EventCategory::all();
            $stats = [
                'total_events' => 0,
                'upcoming_events' => 0,
                'total_categories' => $categories->count(),
                'today_events' => 0,
            ];
            
            return view('welcome', compact('events', 'categories', 'stats'))
                ->with('error', 'Une erreur est survenue lors du chargement de la page.');
        }
    }

    /**
     * Affichage du détail d'un événement
     */
    public function show(Event $event)
    {
        // Vérifier que l'événement est publié
        if ($event->status !== 'published') {
            abort(404, 'Événement non trouvé ou non publié');
        }

        // Charger les relations nécessaires
        $event->load(['category', 'promoter', 'ticketTypes' => function($query) {
            $query->where('is_active', true)
                  ->where('sale_start_date', '<=', now())
                  ->where('sale_end_date', '>=', now())
                  ->orderBy('price', 'asc');
        }]);

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
            'total_tickets' => $event->ticketTypes->sum('quantity_available'),
            'sold_tickets' => $event->ticketTypes->sum('quantity_sold'),
            'available_tickets' => $event->ticketTypes->sum(function($type) {
                return $type->remainingTickets();
            }),
            'min_price' => $event->ticketTypes->min('price'),
            'max_price' => $event->ticketTypes->max('price'),
        ];

        return view('events.show', compact(
            'event', 
            'similarEvents', 
            'hasAvailableTickets',
            'eventStats'
        ));
    }

    /**
     * Affichage des événements par catégorie
     */
    public function category(EventCategory $category, Request $request)
    {
        $query = Event::with(['category', 'ticketTypes', 'promoter'])
            ->where('category_id', $category->id)
            ->where('status', 'published')
            ->where('event_date', '>=', now()->toDateString())
            ->orderBy('event_date', 'asc');

        // Recherche dans la catégorie
        if ($request->has('search') && $request->search != '') {
            $searchTerm = $request->search;
            $query->where(function($q) use ($searchTerm) {
                $q->where('title', 'like', '%' . $searchTerm . '%')
                  ->orWhere('description', 'like', '%' . $searchTerm . '%')
                  ->orWhere('venue', 'like', '%' . $searchTerm . '%');
            });
        }

        $events = $query->paginate(12);
        $categories = EventCategory::withActiveEvents()->get();

        // Statistiques de la catégorie
        $categoryStats = [
            'total_events' => Event::where('category_id', $category->id)
                ->where('status', 'published')
                ->count(),
            'upcoming_events' => Event::where('category_id', $category->id)
                ->where('status', 'published')
                ->where('event_date', '>=', now()->toDateString())
                ->count(),
            'this_month' => Event::where('category_id', $category->id)
                ->where('status', 'published')
                ->whereBetween('event_date', [
                    now()->startOfMonth(),
                    now()->endOfMonth()
                ])
                ->count(),
        ];

        return view('categories.show', compact(
            'category', 
            'events', 
            'categories',
            'categoryStats'
        ));
    }

    /**
     * API pour récupérer les événements (pour filtres AJAX)
     */
    public function getEvents(Request $request)
    {
        $query = Event::with(['category', 'ticketTypes'])
            ->where('status', 'published')
            ->where('event_date', '>=', now()->toDateString());

        // Filtres
        if ($request->has('category') && $request->category != 'all') {
            $query->whereHas('category', function($q) use ($request) {
                $q->where('slug', $request->category);
            });
        }

        if ($request->has('search') && $request->search != '') {
            $searchTerm = $request->search;
            $query->where(function($q) use ($searchTerm) {
                $q->where('title', 'like', '%' . $searchTerm . '%')
                  ->orWhere('venue', 'like', '%' . $searchTerm . '%');
            });
        }

        // Tri
        $sortBy = $request->get('sort', 'date');
        switch ($sortBy) {
            case 'price_asc':
                $query->join('ticket_types', 'events.id', '=', 'ticket_types.event_id')
                      ->orderBy('ticket_types.price', 'asc')
                      ->select('events.*')
                      ->distinct();
                break;
            case 'price_desc':
                $query->join('ticket_types', 'events.id', '=', 'ticket_types.event_id')
                      ->orderBy('ticket_types.price', 'desc')
                      ->select('events.*')
                      ->distinct();
                break;
            case 'name':
                $query->orderBy('title', 'asc');
                break;
            default:
                $query->orderBy('event_date', 'asc');
                break;
        }

        $events = $query->limit(12)->get();

        return response()->json([
            'success' => true,
            'events' => $events->map(function($event) {
                return [
                    'id' => $event->id,
                    'title' => $event->title,
                    'venue' => $event->venue,
                    'event_date' => $event->event_date->format('d/m/Y'),
                    'event_time' => $event->event_time ? $event->event_time->format('H:i') : null,
                    'category' => $event->category->name,
                    'category_slug' => $event->category->slug,
                    'min_price' => $event->ticketTypes->min('price'),
                    'max_price' => $event->ticketTypes->max('price'),
                    'image_url' => $event->image ? asset('storage/' . $event->image) : null,
                    'url' => route('events.show', $event),
                ];
            })
        ]);
    }

    /**
     * Recherche globale d'événements
     */
    public function search(Request $request)
    {
        $request->validate([
            'q' => 'required|string|min:2|max:100'
        ]);

        $searchTerm = $request->q;
        
        $events = Event::with(['category', 'ticketTypes'])
            ->where('status', 'published')
            ->where('event_date', '>=', now()->toDateString())
            ->where(function($query) use ($searchTerm) {
                $query->where('title', 'like', '%' . $searchTerm . '%')
                      ->orWhere('description', 'like', '%' . $searchTerm . '%')
                      ->orWhere('venue', 'like', '%' . $searchTerm . '%')
                      ->orWhereHas('category', function($q) use ($searchTerm) {
                          $q->where('name', 'like', '%' . $searchTerm . '%');
                      });
            })
            ->orderBy('event_date', 'asc')
            ->paginate(12);

        $categories = EventCategory::withActiveEvents()->get();

        return view('search.results', compact('events', 'categories', 'searchTerm'));
    }
    /**
 * NOUVELLE MÉTHODE : Page avec tous les événements
 */
public function allEvents(Request $request)
{
    $query = Event::with(['category', 'ticketTypes', 'promoter'])
        ->where('status', 'published')
        ->where('event_date', '>=', now()->toDateString());

    // Filtrer par catégorie
    if ($request->has('category') && $request->category != '' && $request->category != 'all') {
        $query->whereHas('category', function($q) use ($request) {
            $q->where('slug', $request->category);
        });
    }

    // Recherche par mot-clé
    if ($request->has('search') && $request->search != '') {
        $searchTerm = $request->search;
        $query->where(function($q) use ($searchTerm) {
            $q->where('title', 'like', '%' . $searchTerm . '%')
              ->orWhere('description', 'like', '%' . $searchTerm . '%')
              ->orWhere('venue', 'like', '%' . $searchTerm . '%');
        });
    }

    // Filtrer par date
    if ($request->has('date_filter')) {
        switch ($request->date_filter) {
            case 'today':
                $query->where('event_date', now()->toDateString());
                break;
            case 'this_week':
                $query->whereBetween('event_date', [
                    now()->startOfWeek(),
                    now()->endOfWeek()
                ]);
                break;
            case 'this_month':
                $query->whereBetween('event_date', [
                    now()->startOfMonth(),
                    now()->endOfMonth()
                ]);
                break;
            case 'next_month':
                $query->whereBetween('event_date', [
                    now()->addMonth()->startOfMonth(),
                    now()->addMonth()->endOfMonth()
                ]);
                break;
        }
    }

    // Tri
    $sortBy = $request->get('sort', 'newest');
    switch ($sortBy) {
        case 'newest':
            $query->orderBy('created_at', 'desc');
            break;
        case 'oldest':
            $query->orderBy('created_at', 'asc');
            break;
        case 'date_asc':
            $query->orderBy('event_date', 'asc');
            break;
        case 'date_desc':
            $query->orderBy('event_date', 'desc');
            break;
        case 'title':
            $query->orderBy('title', 'asc');
            break;
    }

    $events = $query->paginate(20)->withQueryString();
    $categories = EventCategory::withActiveEvents()->get();

    // Statistiques
    $stats = [
        'total_events' => Event::where('status', 'published')
            ->where('event_date', '>=', now()->toDateString())
            ->count(),
        'total_categories' => $categories->count(),
        'today_events' => Event::where('status', 'published')
            ->where('event_date', now()->toDateString())
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
}