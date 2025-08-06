<?php

namespace App\Http\Controllers\Promoteur;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Models\{Event, EventCategory, Order, TicketType};
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
                    $q->where('promoter_id', $promoteur->id); // ✅ CORRIGÉ: promoteur_id → promoter_id
                })
                ->with(['user', 'event'])
                ->where('payment_status', 'paid')
                ->latest()
                ->limit(10)
                ->get();

            return view('promoteur.dashboard', compact('stats', 'recentEvents', 'recentOrders'));
            
        } catch (\Exception $e) {
            \Log::error('Erreur dans promoteur.dashboard: ' . $e->getMessage());
            
            // Données par défaut en cas d'erreur
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
            \Log::error('Stack trace: ' . $e->getTraceAsString());
            
            // Retour avec des données vides en cas d'erreur
            $events = collect();
            $categories = collect();
            
            return view('promoteur.events.index', compact('events', 'categories'))
                ->with('error', 'Erreur lors du chargement des événements');
        }
    }

    /** Formulaire création */
    public function create()
    {
        $categories = EventCategory::all();
        return view('promoteur.events.create', compact('categories'));
    }

    /** Sauvegarde événement */
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
        ]);

        $imagePath = $request->hasFile('image')
            ? $request->file('image')->store('events', 'public')
            : null;

        $event = Event::create([
            'promoter_id' => Auth::id(), // ✅ CORRIGÉ: promoteur_id → promoter_id
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
    }

    public function show(Event $event)
    {
        try {
            abort_if($event->promoter_id !== Auth::id(), 403); // ✅ CORRIGÉ

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

    public function publish(Event $event)
    {
        abort_if($event->promoter_id !== Auth::id(), 403); // ✅ CORRIGÉ

        if ($event->ticketTypes()->where('is_active', true)->count() === 0) {
            return back()->with('error', 'Ajoutez au moins un billet avant publication.');
        }

        $event->update(['status' => 'published']);
        return back()->with('success', 'Événement publié.');
    }

    public function unpublish(Event $event)
    {
        abort_if($event->promoter_id !== Auth::id(), 403); // ✅ CORRIGÉ
        $event->update(['status' => 'draft']);
        return back()->with('success', 'Événement dépublié.');
    }

    /**
     * Afficher la page du scanner QR
     */
    public function scanner()
    {
        return view('promoteur.scanner');
    }

    /**
     * Vérifier un billet via le scanner QR
     */
    public function verifyTicket(Request $request)
    {
        \Log::info('Scanner verify request', [
            'ticket_code' => $request->ticket_code,
            'user_id' => Auth::id(),
            'ip' => $request->ip()
        ]);

        $request->validate([
            'ticket_code' => 'required|string|min:1|max:50'
        ]);

        try {
            $ticketCode = strtoupper(trim($request->ticket_code));
            
            $ticket = \App\Models\Ticket::where('ticket_code', $ticketCode)
                ->with(['ticketType.event', 'orderItem.order.user'])
                ->first();

            if (!$ticket) {
                \Log::warning('Ticket not found', ['ticket_code' => $ticketCode]);
                
                return response()->json([
                    'success' => false, 
                    'message' => 'Billet non trouvé',
                    'error_type' => 'not_found'
                ], 404);
            }

            // ✅ CORRIGÉ: promoteur_id → promoter_id
            if ($ticket->ticketType->event->promoter_id !== Auth::id()) {
                \Log::warning('Unauthorized scan attempt', [
                    'ticket_code' => $ticketCode,
                    'promoteur_id' => Auth::id(),
                    'event_promoter_id' => $ticket->ticketType->event->promoter_id
                ]);
                
                return response()->json([
                    'success' => false, 
                    'message' => 'Vous n\'êtes pas autorisé à scanner ce billet',
                    'error_type' => 'unauthorized'
                ], 403);
            }

            if ($ticket->status === 'used') {
                return response()->json([
                    'success' => false, 
                    'message' => 'Billet déjà utilisé le ' . $ticket->used_at->format('d/m/Y à H:i'),
                    'error_type' => 'already_used',
                    'ticket' => [
                        'ticket_code' => $ticket->ticket_code,
                        'event' => [
                            'title' => $ticket->ticketType->event->title
                        ],
                        'status' => $ticket->status,
                        'used_at' => $ticket->used_at
                    ]
                ]);
            }

            if ($ticket->status !== 'sold') {
                return response()->json([
                    'success' => false, 
                    'message' => 'Billet non valide (statut: ' . $ticket->status . ')',
                    'error_type' => 'invalid_status',
                    'ticket' => [
                        'ticket_code' => $ticket->ticket_code,
                        'status' => $ticket->status
                    ]
                ]);
            }

            $ticket->update([
                'status' => 'used',
                'used_at' => now(),
                'scanned_by' => Auth::user()->name
            ]);

            \Log::info('Ticket successfully scanned', [
                'ticket_code' => $ticketCode,
                'scanned_by' => Auth::user()->name
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Billet scanné avec succès !',
                'ticket' => [
                    'ticket_code' => $ticket->ticket_code,
                    'event' => [
                        'title' => $ticket->ticketType->event->title,
                        'date' => $ticket->ticketType->event->event_date
                    ],
                    'ticket_type' => $ticket->ticketType->name,
                    'holder' => [
                        'name' => $ticket->orderItem->order->user->name ?? 'N/A'
                    ],
                    'status' => 'used'
                ],
                'scanned_at' => now()->toISOString()
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Données invalides : ' . implode(', ', $e->validator->errors()->all()),
                'error_type' => 'validation'
            ], 422);
            
        } catch (\Exception $e) {
            \Log::error('Scanner error', [
                'ticket_code' => $request->ticket_code ?? 'unknown',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur serveur. Veuillez réessayer.',
                'error_type' => 'server_error'
            ], 500);
        }
    }

    /**
     * Obtenir les statistiques de scan en temps réel
     */
    public function getScanStats(Request $request)
    {
        $promoteur = Auth::user();
        $eventId = $request->get('event_id');
        
        try {
            $query = \App\Models\Ticket::whereHas('ticketType.event', function($q) use ($promoteur) {
                $q->where('promoter_id', $promoteur->id); // ✅ CORRIGÉ
            });
            
            if ($eventId) {
                $query->whereHas('ticketType', function($q) use ($eventId) {
                    $q->where('event_id', $eventId);
                });
            }
            
            $stats = [
                'total_tickets' => $query->count(),
                'sold_tickets' => $query->where('status', 'sold')->count(),
                'used_tickets' => $query->where('status', 'used')->count(),
                'scans_today' => $query->where('status', 'used')
                                      ->whereDate('used_at', now()->toDateString())
                                      ->count(),
                'scans_last_hour' => $query->where('status', 'used')
                                          ->where('used_at', '>=', now()->subHour())
                                          ->count(),
            ];
            
            $stats['remaining_tickets'] = $stats['sold_tickets'] - $stats['used_tickets'];
            $stats['usage_percentage'] = $stats['sold_tickets'] > 0 
                ? round(($stats['used_tickets'] / $stats['sold_tickets']) * 100, 1)
                : 0;
            
            return response()->json($stats);
            
        } catch (\Exception $e) {
            \Log::error('Erreur récupération stats scan: ' . $e->getMessage());
            
            return response()->json([
                'error' => 'Impossible de récupérer les statistiques'
            ], 500);
        }
    }

    /**
     * Historique des scans récents
     */
    public function getRecentScans(Request $request)
    {
        $promoteur = Auth::user();
        $eventId = $request->get('event_id');
        $limit = $request->get('limit', 20);
        
        try {
            $query = \App\Models\Ticket::whereHas('ticketType.event', function($q) use ($promoteur) {
                $q->where('promoter_id', $promoteur->id); // ✅ CORRIGÉ
            })
            ->where('status', 'used')
            ->with(['ticketType.event', 'orderItem.order.user']);
            
            if ($eventId) {
                $query->whereHas('ticketType', function($q) use ($eventId) {
                    $q->where('event_id', $eventId);
                });
            }
            
            $recentScans = $query->orderBy('used_at', 'desc')
                               ->limit($limit)
                               ->get()
                               ->map(function($ticket) {
                                   return [
                                       'ticket_code' => $ticket->ticket_code,
                                       'holder_name' => $ticket->holder_name,
                                       'event_title' => $ticket->ticketType->event->title,
                                       'ticket_type' => $ticket->ticketType->name,
                                       'used_at' => $ticket->used_at->format('d/m/Y H:i'),
                                       'used_at_human' => $ticket->used_at->diffForHumans(),
                                   ];
                               });
            
            return response()->json($recentScans);
            
        } catch (\Exception $e) {
            \Log::error('Erreur récupération historique scans: ' . $e->getMessage());
            
            return response()->json([
                'error' => 'Impossible de récupérer l\'historique'
            ], 500);
        }
    }

    /**
     * Rechercher un billet spécifique (pour vérification manuelle)
     */
    public function searchTicket(Request $request)
    {
        $request->validate([
            'search' => 'required|string|min:3'
        ]);
        
        $promoteur = Auth::user();
        $search = $request->search;
        
        try {
            $tickets = \App\Models\Ticket::whereHas('ticketType.event', function($q) use ($promoteur) {
                $q->where('promoter_id', $promoteur->id); // ✅ CORRIGÉ
            })
            ->with(['ticketType.event', 'orderItem.order.user'])
            ->where(function($query) use ($search) {
                $query->where('ticket_code', 'like', '%' . $search . '%')
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

    /**
     * Afficher le formulaire d'édition d'un événement
     */
    public function edit(Event $event)
    {
        abort_if($event->promoter_id !== Auth::id(), 403, 'Vous n\'êtes pas autorisé à modifier cet événement'); // ✅ CORRIGÉ
        
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
        abort_if($event->promoter_id !== Auth::id(), 403, 'Vous n\'êtes pas autorisé à modifier cet événement'); // ✅ CORRIGÉ

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

    /**
     * Supprimer un événement
     */
    public function destroy(Event $event)
    {
        abort_if($event->promoter_id !== Auth::id(), 403, 'Vous n\'êtes pas autorisé à supprimer cet événement'); // ✅ CORRIGÉ

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
     * Dupliquer un événement
     */
    public function duplicate(Event $event)
    {
        abort_if($event->promoter_id !== Auth::id(), 403); // ✅ CORRIGÉ

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

   /**
 * Page des ventes - VERSION AMÉLIORÉE
 */
public function sales(Request $request)
{
    $promoteur = Auth::user();
    
    // Période par défaut : ce mois
    $startDate = $request->get('start_date', now()->startOfMonth()->toDateString());
    $endDate = $request->get('end_date', now()->endOfMonth()->toDateString());
    
    try {
        // ✅ CORRIGÉ: Ventes par événement avec la bonne relation
        $salesByEvent = $promoteur->events()
            ->with(['orders' => function($query) use ($startDate, $endDate) {
                $query->where('payment_status', 'paid')
                      ->whereBetween('created_at', [$startDate, $endDate])
                      ->with('orderItems');
            }])
            ->get()
            ->map(function($event) {
                $orders = $event->orders;
                
                return [
                    'event' => $event,
                    'revenue' => $orders->sum('total_amount'),
                    'tickets_sold' => $orders->sum(function($order) {
                        return $order->orderItems->sum('quantity');
                    }),
                    'orders_count' => $orders->count(),
                    'average_order' => $orders->count() > 0 ? 
                        $orders->sum('total_amount') / $orders->count() : 0,
                ];
            })
            ->sortByDesc('revenue');
            
    } catch (\Exception $e) {
        \Log::error('Erreur lors de la récupération des ventes par événement: ' . $e->getMessage());
        $salesByEvent = collect();
    }
    
    try {
        // ✅ CORRIGÉ: Ventes quotidiennes avec la bonne relation
        $dailySales = Order::whereIn('event_id', $promoteur->events()->pluck('id'))
            ->where('payment_status', 'paid')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->selectRaw('DATE(created_at) as date, SUM(total_amount) as revenue, COUNT(*) as orders')
            ->groupBy('date')
            ->orderBy('date')
            ->get();
            
    } catch (\Exception $e) {
        \Log::error('Erreur lors de la récupération des ventes quotidiennes: ' . $e->getMessage());
        $dailySales = collect();
    }
    
    // Statistiques globales
    $totalStats = [
        'total_revenue' => $salesByEvent->sum('revenue'),
        'total_tickets' => $salesByEvent->sum('tickets_sold'),
        'total_orders' => $salesByEvent->sum('orders_count'),
        'average_order' => 0,
    ];
    
    // Calcul du panier moyen
    if ($totalStats['total_orders'] > 0) {
        $totalStats['average_order'] = $totalStats['total_revenue'] / $totalStats['total_orders'];
    }
    
    // ✅ NOUVEAU: Répartition par type de billets
    try {
        $ticketTypesSales = \App\Models\TicketType::whereIn('event_id', $promoteur->events()->pluck('id'))
            ->with(['orderItems' => function($query) use ($startDate, $endDate) {
                $query->whereHas('order', function($q) use ($startDate, $endDate) {
                    $q->where('payment_status', 'paid')
                      ->whereBetween('created_at', [$startDate, $endDate]);
                });
            }])
            ->get()
            ->map(function($ticketType) {
                $orderItems = $ticketType->orderItems;
                return [
                    'name' => $ticketType->name,
                    'price' => $ticketType->price,
                    'quantity_sold' => $orderItems->sum('quantity'),
                    'revenue' => $orderItems->sum('total_price'),
                ];
            })
            ->where('quantity_sold', '>', 0)
            ->sortByDesc('revenue');
            
    } catch (\Exception $e) {
        \Log::error('Erreur calcul ventes par type de billet: ' . $e->getMessage());
        $ticketTypesSales = collect();
    }
    
    return view('promoteur.sales', compact(
        'salesByEvent', 
        'dailySales', 
        'totalStats', 
        'ticketTypesSales',
        'startDate', 
        'endDate'
    ));
}

    /**
     * Page des rapports
     */
    public function reports(Request $request)
    {
        $promoteur = Auth::user();
        
        $startDate = $request->get('start_date', now()->startOfMonth()->toDateString());
        $endDate = $request->get('end_date', now()->endOfMonth()->toDateString());
        
        try {
            $stats = [
                'total_events' => $promoteur->events()->count() ?? 0,
                'published_events' => $promoteur->events()->where('status', 'published')->count() ?? 0,
                'draft_events' => $promoteur->events()->where('status', 'draft')->count() ?? 0,
                'total_revenue' => $promoteur->totalRevenue() ?? 0,
                'period_revenue' => $promoteur->commissions()
                    ->where('status', 'paid')
                    ->whereBetween('created_at', [$startDate, $endDate])
                    ->sum('net_amount') ?? 0,
            ];
        } catch (\Exception $e) {
            \Log::error('Erreur lors du calcul des statistiques: ' . $e->getMessage());
            $stats = [
                'total_events' => 0,
                'published_events' => 0, 
                'draft_events' => 0,
                'total_revenue' => 0,
                'period_revenue' => 0
            ];
        }
        
        try {
            $topEvents = $promoteur->events()
                ->withSum(['orders as revenue' => function($query) {
                    $query->where('payment_status', 'paid');
                }], 'total_amount')
                ->withCount(['orders as orders_count' => function($query) {
                    $query->where('payment_status', 'paid');
                }])
                ->orderByDesc('revenue')
                ->limit(10)
                ->get();
        } catch (\Exception $e) {
            \Log::error('Erreur lors de la récupération des top événements: ' . $e->getMessage());
            $topEvents = collect();
        }
        
        try {
            $monthlyData = [];
            for ($i = 11; $i >= 0; $i--) {
                $date = now()->subMonths($i);
                
                $revenue = $promoteur->commissions()
                    ->whereYear('created_at', $date->year)
                    ->whereMonth('created_at', $date->month)
                    ->where('status', 'paid')
                    ->sum('net_amount') ?? 0;
                
                $monthlyData[] = [
                    'month' => $date->format('M Y'),
                    'revenue' => $revenue
                ];
            }
        } catch (\Exception $e) {
            \Log::error('Erreur lors du calcul de l\'évolution mensuelle: ' . $e->getMessage());
            $monthlyData = [];
        }
        
        try {
            $topTicketTypes = \App\Models\TicketType::whereHas('event', function($query) use ($promoteur) {
                    $query->where('promoter_id', $promoteur->id); // ✅ CORRIGÉ
                })
                ->withSum(['orderItems as revenue' => function($query) use ($startDate, $endDate) {
                    $query->whereHas('order', function($q) use ($startDate, $endDate) {
                        $q->where('payment_status', 'paid')
                          ->whereBetween('created_at', [$startDate, $endDate]);
                    });
                }], 'total_price')
                ->withSum(['orderItems as tickets_sold' => function($query) use ($startDate, $endDate) {
                    $query->whereHas('order', function($q) use ($startDate, $endDate) {
                        $q->where('payment_status', 'paid')
                          ->whereBetween('created_at', [$startDate, $endDate]);
                    });
                }], 'quantity')
                ->having('tickets_sold', '>', 0)
                ->orderByDesc('revenue')
                ->limit(10)
                ->get();
        } catch (\Exception $e) {
            \Log::error('Erreur lors de la récupération des types de billets: ' . $e->getMessage());
            $topTicketTypes = collect();
        }
        
        return view('promoteur.reports', compact(
            'stats', 'topEvents', 'monthlyData', 'topTicketTypes', 
            'startDate', 'endDate'
        ));
    }

    /**
     * Export des données avec gestion d'erreur
     */
    public function exportData(Request $request)
    {
        $type = $request->get('type', 'commissions');
        $format = $request->get('format', 'excel');
        
        try {
            switch ($type) {
                case 'commissions':
                    return $this->exportCommissions($request);
                case 'sales':
                    return $this->exportSales($request);
                case 'events':
                    return $this->exportEvents($request);
                default:
                    return redirect()->back()->with('error', 'Type d\'export non valide');
            }
        } catch (\Exception $e) {
            \Log::error('Erreur lors de l\'export ' . $type . ': ' . $e->getMessage());
            return redirect()->back()->with('error', 'Erreur lors de l\'export: ' . $e->getMessage());
        }
    }

    /**
     * Export des commissions
     */
    private function exportCommissions(Request $request)
    {
        $promoteur = Auth::user();
        
        $commissions = $promoteur->commissions()
            ->with(['order.event', 'order.user'])
            ->orderBy('created_at', 'desc')
            ->get();
        
        $filename = 'commissions_' . $promoteur->name . '_' . now()->format('Y-m-d') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];
        
        $callback = function() use ($commissions) {
            $file = fopen('php://output', 'w');
            
            fputcsv($file, [
                'Date',
                'Événement',
                'Client',
                'Montant Brut (FCFA)',
                'Commission (%)',
                'Net (FCFA)',
                'Statut',
                'Date Paiement'
            ]);
            
            foreach ($commissions as $commission) {
                fputcsv($file, [
                    $commission->created_at->format('d/m/Y'),
                    $commission->order->event->title,
                    $commission->order->user->name,
                    number_format($commission->gross_amount),
                    $commission->commission_rate . '%',
                    number_format($commission->net_amount),
                    ucfirst($commission->status),
                    $commission->paid_at ? $commission->paid_at->format('d/m/Y') : ''
                ]);
            }
            
            fclose($file);
        };
        
        return response()->stream($callback, 200, $headers);
    }

    /**
     * Export des ventes
     */
    private function exportSales(Request $request)
    {
        $promoteur = Auth::user();
        
        $orders = Order::whereHas('event', function($query) use ($promoteur) {
                $query->where('promoter_id', $promoteur->id); // ✅ CORRIGÉ
            })
            ->with(['user', 'event', 'orderItems.ticketType'])
            ->where('payment_status', 'paid')
            ->orderBy('created_at', 'desc')
            ->get();
        
        $filename = 'ventes_' . $promoteur->name . '_' . now()->format('Y-m-d') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];
        
        $callback = function() use ($orders) {
            $file = fopen('php://output', 'w');
            
            fputcsv($file, [
                'Date',
                'Commande',
                'Client',
                'Email',
                'Événement',
                'Billets',
                'Montant (FCFA)'
            ]);
            
            foreach ($orders as $order) {
                fputcsv($file, [
                    $order->created_at->format('d/m/Y H:i'),
                    $order->order_number,
                    $order->user->name,
                    $order->billing_email,
                    $order->event->title,
                    $order->orderItems->sum('quantity'),
                    number_format($order->total_amount)
                ]);
            }
            
            fclose($file);
        };
        
        return response()->stream($callback, 200, $headers);
    }

    /**
     * Export des événements
     */
    private function exportEvents(Request $request)
    {
        $promoteur = Auth::user();
        
        $events = $promoteur->events()
            ->with(['category', 'ticketTypes'])
            ->withSum(['orders as revenue' => function($query) {
                $query->where('payment_status', 'paid');
            }], 'total_amount')
            ->withCount(['orders as orders_count' => function($query) {
                $query->where('payment_status', 'paid');
            }])
            ->orderBy('created_at', 'desc')
            ->get();
        
        $filename = 'evenements_' . $promoteur->name . '_' . now()->format('Y-m-d') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];
        
        $callback = function() use ($events) {
            $file = fopen('php://output', 'w');
            
            fputcsv($file, [
                'Titre',
                'Catégorie',
                'Date',
                'Lieu',
                'Statut',
                'Billets Disponibles',
                'Billets Vendus',
                'Revenus (FCFA)',
                'Commandes'
            ]);
            
            foreach ($events as $event) {
                fputcsv($file, [
                    $event->title,
                    $event->category->name,
                    $event->event_date ? $event->event_date->format('d/m/Y') : '',
                    $event->venue,
                    ucfirst($event->status),
                    $event->totalTicketsAvailable(),
                    $event->totalTicketsSold(),
                    number_format($event->revenue ?? 0),
                    $event->orders_count ?? 0
                ]);
            }
            
            fclose($file);
        };
        
        return response()->stream($callback, 200, $headers);
    }

    /**
     * Profil du promoteur
     */
    public function profile()
    {
        $promoteur = Auth::user();
        
        try {
            $stats = [
                'total_events' => $promoteur->events()->count(),
                'published_events' => $promoteur->events()->where('status', 'published')->count(),
                'total_revenue' => $promoteur->totalRevenue(),
                'pending_revenue' => $promoteur->pendingRevenue(),
                'total_tickets_sold' => $promoteur->totalTicketsSold(),
            ];
            
            return view('promoteur.profile', compact('promoteur', 'stats'));
            
        } catch (\Exception $e) {
            \Log::error('Erreur lors du chargement du profil: ' . $e->getMessage());
            
            return view('promoteur.profile', [
                'promoteur' => $promoteur,
                'stats' => [
                    'total_events' => 0,
                    'published_events' => 0,
                    'total_revenue' => 0,
                    'pending_revenue' => 0,
                    'total_tickets_sold' => 0,
                ]
            ])->with('error', 'Erreur lors du chargement des statistiques');
        }
    }
}