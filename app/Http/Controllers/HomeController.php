<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Event;
use App\Models\EventCategory;

class HomeController extends Controller
{
    /**
     * Affichage de la page d'accueil - Style Tikerama
     */
    public function index(Request $request)
    {
        try {
            // Récupérer les événements publiés et à venir
            $query = Event::with(['category', 'ticketTypes', 'promoteur'])
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
                $query->where(function($q) use ($request) {
                    $q->where('title', 'like', '%' . $request->search . '%')
                      ->orWhere('description', 'like', '%' . $request->search . '%')
                      ->orWhere('venue', 'like', '%' . $request->search . '%');
                });
            }

            $events = $query->limit(12)->get(); // Limiter à 12 pour la page d'accueil
            
            // Récupérer les catégories pour les filtres
            $categories = EventCategory::withActiveEvents()->get();

            // Statistiques pour la page - Style Tikerama
            $stats = [
                'total_events' => Event::where('status', 'published')->count(),
                'total_categories' => $categories->count(),
                'upcoming_events' => Event::where('status', 'published')
                    ->where('event_date', '>=', now()->toDateString())
                    ->count(),
                'today_events' => Event::where('status', 'published')
                    ->where('event_date', now()->toDateString())
                    ->count(),
            ];

            return view('welcome', compact('events', 'categories', 'stats'));
            
        } catch (\Exception $e) {
            \Log::error('Erreur page d\'accueil: ' . $e->getMessage());
            
            // Données par défaut en cas d'erreur
            $events = collect();
            $categories = collect();
            $stats = [
                'total_events' => 0,
                'total_categories' => 0,
                'upcoming_events' => 0,
                'today_events' => 0,
            ];

            return view('welcome', compact('events', 'categories', 'stats'));
        }
    }

    /**
     * Affichage du détail d'un événement - Style Tikerama
     */
    public function show(Event $event)
    {
        try {
            // Vérifier que l'événement est publié
            if ($event->status !== 'published') {
                abort(404, 'Événement non trouvé');
            }

            // Charger les relations nécessaires
            $event->load(['category', 'promoteur', 'ticketTypes' => function($query) {
                $query->where('is_active', true)
                      ->where('sale_start_date', '<=', now())
                      ->where('sale_end_date', '>=', now())
                      ->orderBy('price', 'asc');
            }]);

            // Événements similaires (même catégorie) - Style Tikerama
            $similarEvents = Event::with(['category', 'ticketTypes'])
                ->where('category_id', $event->category_id)
                ->where('id', '!=', $event->id)
                ->where('status', 'published')
                ->where('event_date', '>=', now()->toDateString())
                ->limit(3)
                ->get();

            // Statistiques de l'événement
            $eventStats = [
                'tickets_sold' => $event->getTicketsSoldCount(),
                'tickets_available' => $event->totalTicketsAvailable(),
                'progress_percentage' => $event->getProgressPercentage(),
                'is_sold_out' => $event->isSoldOut(),
                'lowest_price' => $event->getLowestPrice(),
                'highest_price' => $event->getHighestPrice(),
            ];

            return view('events.show', compact('event', 'similarEvents', 'eventStats'));
            
        } catch (\Exception $e) {
            \Log::error('Erreur détail événement: ' . $e->getMessage());
            abort(404, 'Événement non trouvé');
        }
    }

    /**
     * Affichage des événements par catégorie - Style Tikerama
     */
    public function category(EventCategory $category, Request $request)
    {
        try {
            $query = Event::with(['category', 'ticketTypes', 'promoteur'])
                ->where('category_id', $category->id)
                ->where('status', 'published')
                ->where('event_date', '>=', now()->toDateString())
                ->orderBy('event_date', 'asc');

            // Recherche dans la catégorie
            if ($request->has('search') && $request->search != '') {
                $query->where(function($q) use ($request) {
                    $q->where('title', 'like', '%' . $request->search . '%')
                      ->orWhere('description', 'like', '%' . $request->search . '%')
                      ->orWhere('venue', 'like', '%' . $request->search . '%');
                });
            }

            $events = $query->paginate(12);
            $categories = EventCategory::withActiveEvents()->get();

            // Statistiques de la catégorie
            $categoryStats = [
                'total_events' => $category->events()->where('status', 'published')->count(),
                'upcoming_events' => $events->total(),
                'category_name' => $category->name,
            ];

            return view('categories.show', compact('category', 'events', 'categories', 'categoryStats'));
            
        } catch (\Exception $e) {
            \Log::error('Erreur catégorie: ' . $e->getMessage());
            abort(404, 'Catégorie non trouvée');
        }
    }

    /**
     * Page de recherche globale - Style Tikerama
     */
    public function search(Request $request)
    {
        $searchTerm = $request->get('q', '');
        
        if (empty($searchTerm)) {
            return redirect()->route('home');
        }

        try {
            $query = Event::with(['category', 'ticketTypes', 'promoteur'])
                ->where('status', 'published')
                ->where('event_date', '>=', now()->toDateString())
                ->where(function($q) use ($searchTerm) {
                    $q->where('title', 'like', '%' . $searchTerm . '%')
                      ->orWhere('description', 'like', '%' . $searchTerm . '%')
                      ->orWhere('venue', 'like', '%' . $searchTerm . '%')
                      ->orWhereHas('category', function($categoryQuery) use ($searchTerm) {
                          $categoryQuery->where('name', 'like', '%' . $searchTerm . '%');
                      });
                });

            // Filtres additionnels
            if ($request->has('category') && $request->category != '') {
                $query->where('category_id', $request->category);
            }

            if ($request->has('date') && $request->date != '') {
                $query->whereDate('event_date', $request->date);
            }

            if ($request->has('city') && $request->city != '') {
                $query->where('address', 'like', '%' . $request->city . '%');
            }

            $events = $query->orderBy('event_date', 'asc')->paginate(12);
            $categories = EventCategory::withActiveEvents()->get();

            // Statistiques de recherche
            $searchStats = [
                'total_results' => $events->total(),
                'search_term' => $searchTerm,
                'has_filters' => $request->hasAny(['category', 'date', 'city']),
            ];

            return view('search.results', compact('events', 'categories', 'searchStats', 'searchTerm'));
            
        } catch (\Exception $e) {
            \Log::error('Erreur recherche: ' . $e->getMessage());
            
            $events = collect();
            $categories = collect();
            $searchStats = ['total_results' => 0, 'search_term' => $searchTerm, 'has_filters' => false];
            
            return view('search.results', compact('events', 'categories', 'searchStats', 'searchTerm'));
        }
    }

    /**
     * API pour la recherche en temps réel (AJAX)
     */
    public function apiSearch(Request $request)
    {
        $searchTerm = $request->get('q', '');
        
        if (strlen($searchTerm) < 2) {
            return response()->json(['results' => []]);
        }

        try {
            $events = Event::where('status', 'published')
                ->where('event_date', '>=', now()->toDateString())
                ->where('title', 'like', '%' . $searchTerm . '%')
                ->with(['category'])
                ->limit(5)
                ->get(['id', 'title', 'venue', 'event_date', 'category_id']);

            $results = $events->map(function($event) {
                return [
                    'id' => $event->id,
                    'title' => $event->title,
                    'venue' => $event->venue,
                    'date' => $event->event_date->format('d/m/Y'),
                    'category' => $event->category->name ?? '',
                    'url' => route('events.show', $event),
                ];
            });

            return response()->json(['results' => $results]);
            
        } catch (\Exception $e) {
            \Log::error('Erreur API recherche: ' . $e->getMessage());
            return response()->json(['results' => []]);
        }
    }

    /**
     * Page des événements populaires
     */
    public function popular()
    {
        try {
            $events = Event::with(['category', 'ticketTypes', 'promoteur'])
                ->where('status', 'published')
                ->where('event_date', '>=', now()->toDateString())
                ->withCount(['orders' => function($query) {
                    $query->where('payment_status', 'paid');
                }])
                ->orderByDesc('orders_count')
                ->paginate(12);

            $categories = EventCategory::withActiveEvents()->get();

            return view('events.popular', compact('events', 'categories'));
            
        } catch (\Exception $e) {
            \Log::error('Erreur événements populaires: ' . $e->getMessage());
            
            $events = collect();
            $categories = collect();
            
            return view('events.popular', compact('events', 'categories'));
        }
    }
}