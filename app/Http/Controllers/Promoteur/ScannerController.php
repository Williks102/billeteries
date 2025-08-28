<?php

namespace App\Http\Controllers\Promoteur;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ScannerController extends Controller
{
    /**
     * Page principale du scanner QR
     */
    public function index()
    {
        $promoteurId = Auth::id();
        
        // Statistiques récentes - CORRIGÉ pour BDD existante
        $recentScans = Ticket::whereHas('ticketType.event', function($query) use ($promoteurId) {
                $query->where('promoter_id', $promoteurId);
            })
            ->where('status', 'used')
            ->where('updated_at', '>=', now()->subDays(7))
            ->count();

        $todayScans = Ticket::whereHas('ticketType.event', function($query) use ($promoteurId) {
                $query->where('promoter_id', $promoteurId);
            })
            ->where('status', 'used')
            ->whereDate('updated_at', today())
            ->count();

        // Événements actifs avec billets non utilisés
        $activeEvents = Event::where('promoter_id', $promoteurId)
            ->where('status', 'published')
            ->where('event_date', '>=', now())
            ->withCount(['tickets as unused_tickets' => function($query) {
                $query->where('status', 'sold'); // Vendus mais pas encore utilisés
            }])
            ->get();

        return view('promoteur.scanner.index', compact(
            'recentScans', 
            'todayScans', 
            'activeEvents'
        ));
    }

    /**
     * Vérifier un billet via scanner QR
     */
    public function verify(Request $request)
    {
        Log::info('Scanner verify request', [
            'ticket_code' => $request->ticket_code,
            'promoter_id' => Auth::id(),
            'ip' => $request->ip()
        ]);

        $request->validate([
            'ticket_code' => 'required|string|min:1|max:50'
        ]);

        try {
            $ticketCode = strtoupper(trim($request->ticket_code));
            
            $ticket = Ticket::where('ticket_code', $ticketCode)
                ->with(['ticketType.event', 'orderItem.order.user'])
                ->first();

            if (!$ticket) {
                Log::warning('Ticket not found', ['ticket_code' => $ticketCode]);
                
                return response()->json([
                    'success' => false, 
                    'message' => 'Billet non trouvé',
                    'error_type' => 'not_found'
                ], 404);
            }

            // Vérifier que le promoteur est autorisé à scanner ce billet
            if ($ticket->ticketType->event->promoter_id !== Auth::id()) {
                Log::warning('Unauthorized scan attempt', [
                    'ticket_code' => $ticketCode,
                    'promoter_id' => Auth::id(),
                    'event_promoter_id' => $ticket->ticketType->event->promoter_id
                ]);
                
                return response()->json([
                    'success' => false, 
                    'message' => 'Vous n\'êtes pas autorisé à scanner ce billet',
                    'error_type' => 'unauthorized'
                ], 403);
            }

            // Vérifier si déjà utilisé
            if ($ticket->status === 'used') {
                return response()->json([
                    'success' => false, 
                    'message' => 'Billet déjà utilisé le ' . $ticket->updated_at->format('d/m/Y à H:i'),
                    'error_type' => 'already_used',
                    'ticket_info' => [
                        'code' => $ticket->ticket_code,
                        'event' => $ticket->ticketType->event->title,
                        'used_at' => $ticket->updated_at->format('d/m/Y H:i')
                    ]
                ], 400);
            }

            // Vérifier si le ticket peut être utilisé
            if ($ticket->status !== 'sold') {
                return response()->json([
                    'success' => false, 
                    'message' => 'Billet non valide (statut: ' . $ticket->status . ')',
                    'error_type' => 'invalid'
                ], 400);
            }

            // Scanner le billet avec succès - CORRIGÉ
            $ticket->update(['status' => 'used']);
            // updated_at se met à jour automatiquement

            Log::info("Scan successful", [
                'ticket_code' => $ticketCode,
                'scanned_by' => Auth::user()->name,
                'event_id' => $ticket->ticketType->event->id
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Billet scanné avec succès !',
                'ticket_info' => [
                    'code' => $ticket->ticket_code,
                    'event' => $ticket->ticketType->event->title,
                    'type' => $ticket->ticketType->name,
                    'client' => $ticket->orderItem->order->user->name ?? 'N/A',
                    'email' => $ticket->orderItem->order->user->email ?? 'N/A',
                    'scanned_at' => $ticket->updated_at->format('d/m/Y H:i'),
                    'scanned_by' => Auth::user()->name
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Scanner verification error', [
                'ticket_code' => $request->ticket_code,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur système lors de la vérification',
                'error_type' => 'system_error'
            ], 500);
        }
    }

    /**
     * Statistiques du scanner
     */
    public function stats()
    {
        $promoteurId = Auth::id();
        
        // Statistiques générales - CORRIGÉ
        $totalScans = Ticket::whereHas('ticketType.event', function($query) use ($promoteurId) {
                $query->where('promoter_id', $promoteurId);
            })
            ->where('status', 'used')
            ->count();

        $todayScans = Ticket::whereHas('ticketType.event', function($query) use ($promoteurId) {
                $query->where('promoter_id', $promoteurId);
            })
            ->where('status', 'used')
            ->whereDate('updated_at', today())
            ->count();

        $weekScans = Ticket::whereHas('ticketType.event', function($query) use ($promoteurId) {
                $query->where('promoter_id', $promoteurId);
            })
            ->where('status', 'used')
            ->where('updated_at', '>=', now()->subDays(7))
            ->count();

        $monthScans = Ticket::whereHas('ticketType.event', function($query) use ($promoteurId) {
                $query->where('promoter_id', $promoteurId);
            })
            ->where('status', 'used')
            ->where('updated_at', '>=', now()->subMonth())
            ->count();

        // Statistiques par événement
        $eventStats = Event::where('promoter_id', $promoteurId)
            ->with(['ticketTypes.tickets'])
            ->get()
            ->map(function($event) {
                $allTickets = $event->ticketTypes->flatMap->tickets;
                $totalTickets = $allTickets->count();
                $usedTickets = $allTickets->where('status', 'used')->count();
                $soldTickets = $allTickets->where('status', 'sold')->count();
                
                return [
                    'event' => $event,
                    'total_tickets' => $totalTickets,
                    'used_tickets' => $usedTickets,
                    'sold_tickets' => $soldTickets,
                    'unused_sold' => $soldTickets, // Vendus mais pas encore utilisés
                    'usage_rate' => $soldTickets > 0 ? round(($usedTickets / $soldTickets) * 100, 1) : 0
                ];
            })
            ->sortByDesc('total_tickets');

        // Graphique des scans par jour (7 derniers jours) - CORRIGÉ
        $dailyScans = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $count = Ticket::whereHas('ticketType.event', function($query) use ($promoteurId) {
                    $query->where('promoter_id', $promoteurId);
                })
                ->where('status', 'used')
                ->whereDate('updated_at', $date)
                ->count();
                
            $dailyScans[] = [
                'date' => $date->format('d/m'),
                'count' => $count
            ];
        }

        return view('promoteur.scanner.stats', compact(
            'totalScans',
            'todayScans', 
            'weekScans',
            'monthScans',
            'eventStats',
            'dailyScans'
        ));
    }

    /**
     * Scans récents
     */
    public function recent()
    {
        $promoteurId = Auth::id();
        
        $recentScans = Ticket::whereHas('ticketType.event', function($query) use ($promoteurId) {
                $query->where('promoter_id', $promoteurId);
            })
            ->where('status', 'used')
            ->with(['ticketType.event', 'orderItem.order.user'])
            ->orderBy('updated_at', 'desc')
            ->paginate(20);

        return view('promoteur.scanner.recent', compact('recentScans'));
    }

    /**
     * Rechercher un billet
     */
    public function search(Request $request)
    {
        $promoteurId = Auth::id();
        $searchTerm = $request->get('search');
        $results = null;

        if ($searchTerm) {
            $results = Ticket::whereHas('ticketType.event', function($query) use ($promoteurId) {
                    $query->where('promoter_id', $promoteurId);
                })
                ->where(function($query) use ($searchTerm) {
                    $query->where('ticket_code', 'like', "%{$searchTerm}%")
                        ->orWhereHas('orderItem.order.user', function($q) use ($searchTerm) {
                            $q->where('name', 'like', "%{$searchTerm}%")
                              ->orWhere('email', 'like', "%{$searchTerm}%");
                        })
                        ->orWhereHas('ticketType.event', function($q) use ($searchTerm) {
                            $q->where('title', 'like', "%{$searchTerm}%");
                        });
                })
                ->with(['ticketType.event', 'orderItem.order.user'])
                ->orderBy('created_at', 'desc')
                ->paginate(20);
        }

        return view('promoteur.scanner.search', compact('results', 'searchTerm'));
    }

    /**
     * API pour obtenir les statistiques de scan en temps réel
     */
    public function getStats()
    {
        $promoteurId = Auth::id();
        
        return response()->json([
            'today_scans' => Ticket::whereHas('ticketType.event', function($query) use ($promoteurId) {
                    $query->where('promoter_id', $promoteurId);
                })
                ->where('status', 'used')
                ->whereDate('updated_at', today())
                ->count(),
                
            'pending_tickets' => Ticket::whereHas('ticketType.event', function($query) use ($promoteurId) {
                    $query->where('promoter_id', $promoteurId);
                })
                ->where('status', 'sold') // Vendus mais pas encore utilisés
                ->count()
        ]);
    }

    /**
     * API pour les notifications de scan en temps réel
     */
    public function notifications()
    {
        $promoteurId = Auth::id();
        
        $pendingScans = Ticket::whereHas('ticketType.event', function($query) use ($promoteurId) {
                $query->where('promoter_id', $promoteurId);
            })
            ->where('status', 'sold') // Billets vendus mais pas encore utilisés
            ->whereHas('ticketType.event', function($query) {
                $query->whereDate('event_date', today());
            })
            ->count();

        return response()->json([
            'pending_scans' => $pendingScans
        ]);
    }
}