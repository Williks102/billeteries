<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use App\Models\Event;
use App\Models\User;
use App\Models\TicketType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Services\QRCodeService;

class TicketController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('admin');
    }

    /**
     * Liste des tickets (MIGRÉ depuis AdminController::tickets)
     */
    public function index(Request $request)
    {
        $query = Ticket::with(['ticketType.event.promoteur', 'orderTickets.order.user']);

        // Filtres
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('ticket_code', 'like', "%{$search}%")
                  ->orWhereHas('ticketType.event', function($eq) use ($search) {
                      $eq->where('title', 'like', "%{$search}%");
                  })
                  ->orWhereHas('orderTickets.order.user', function($uq) use ($search) {
                      $uq->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('event')) {
            $query->whereHas('ticketType.event', function($eq) use ($request) {
                $eq->where('id', $request->event);
            });
        }

        if ($request->filled('promoteur')) {
            $query->whereHas('ticketType.event.promoteur', function($pq) use ($request) {
                $pq->where('id', $request->promoteur);
            });
        }

        if ($request->filled('date_from')) {
            $query->where('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->where('created_at', '<=', $request->date_to . ' 23:59:59');
        }

        if ($request->filled('ticket_type')) {
            $query->where('ticket_type_id', $request->ticket_type);
        }

        // Tri par défaut : plus récents
        $tickets = $query->orderBy('created_at', 'desc')->paginate(20);

        // Statistiques pour le dashboard
        $stats = [
            'total' => Ticket::count(),
            'sold' => Ticket::where('status', 'sold')->count(),
            'used' => Ticket::where('status', 'used')->count(),
            'cancelled' => Ticket::where('status', 'cancelled')->count(),
            'pending' => Ticket::where('status', 'pending')->count(),
            'this_month' => Ticket::whereMonth('created_at', now()->month)->count(),
            'usage_rate' => Ticket::where('status', 'sold')->count() > 0 
                ? round((Ticket::where('status', 'used')->count() / Ticket::where('status', 'sold')->count()) * 100, 1)
                : 0,
            'total_value' => Ticket::join('ticket_types', 'tickets.ticket_type_id', '=', 'ticket_types.id')
                ->where('tickets.status', 'sold')
                ->sum('ticket_types.price'),
        ];

        // Données pour les filtres
        $events = Event::orderBy('title')->get();
        $promoteurs = User::where('role', 'promoteur')->orderBy('name')->get();
        $ticketTypes = TicketType::with('event')->orderBy('name')->get();

        return view('admin.tickets.index', compact('tickets', 'stats', 'events', 'promoteurs', 'ticketTypes'));
    }

    /**
     * Affichage détail ticket (MIGRÉ depuis AdminController::showTicket)
     */
    public function show(Ticket $ticket)
    {
        try {
            $ticket->load([
                'ticketType.event.promoteur', 
                'orderTickets.order.user'
            ]);

            // Informations complémentaires
            $info = [
                'order' => $ticket->orderTickets->first()?->order,
                'buyer' => $ticket->orderTickets->first()?->order?->user,
                'event' => $ticket->ticketType->event,
                'promoteur' => $ticket->ticketType->event->promoteur,
                'price' => $ticket->ticketType->price,
                'created_ago' => $ticket->created_at->diffForHumans(),
                'qr_exists' => !empty($ticket->qr_code),
                'verify_url' => route('verify-ticket', $ticket->ticket_code),
            ];

            // Historique d'utilisation
            $history = collect([
                [
                    'action' => 'ticket_created',
                    'message' => 'Ticket créé',
                    'date' => $ticket->created_at,
                    'icon' => 'fas fa-ticket-alt',
                    'color' => 'primary'
                ]
            ]);

            if ($ticket->status === 'used' && $ticket->used_at) {
                $history->push([
                    'action' => 'ticket_used',
                    'message' => 'Ticket utilisé',
                    'date' => $ticket->used_at,
                    'icon' => 'fas fa-check-circle',
                    'color' => 'success'
                ]);
            }

            if ($ticket->status === 'cancelled') {
                $history->push([
                    'action' => 'ticket_cancelled',
                    'message' => 'Ticket annulé',
                    'date' => $ticket->updated_at,
                    'icon' => 'fas fa-times-circle',
                    'color' => 'danger'
                ]);
            }

            return view('admin.tickets.show', compact('ticket', 'info', 'history'));

        } catch (\Exception $e) {
            \Log::error('Erreur affichage ticket: ' . $e->getMessage());
            return redirect()->route('admin.tickets.index')->with('error', 'Erreur lors du chargement');
        }
    }

    /**
     * Formulaire d'édition ticket (limité)
     */
    public function edit(Ticket $ticket)
    {
        // Seules certaines propriétés peuvent être modifiées
        $ticket->load(['ticketType.event', 'orderTickets.order.user']);
        
        return view('admin.tickets.edit', compact('ticket'));
    }

    /**
     * Mise à jour ticket (limitée)
     */
    public function update(Request $request, Ticket $ticket)
    {
        $request->validate([
            'status' => 'required|in:pending,sold,used,cancelled',
            'admin_notes' => 'nullable|string|max:1000',
        ]);

        try {
            $oldStatus = $ticket->status;
            
            $updateData = [
                'status' => $request->status,
                'admin_notes' => $request->admin_notes,
            ];

            // Si marqué comme utilisé, enregistrer la date
            if ($request->status === 'used' && $oldStatus !== 'used') {
                $updateData['used_at'] = now();
                $updateData['used_by'] = auth()->id();
            }

            $ticket->update($updateData);

            \Log::info('Ticket modifié par admin', [
                'admin_id' => auth()->id(),
                'ticket_id' => $ticket->id,
                'ticket_code' => $ticket->ticket_code,
                'old_status' => $oldStatus,
                'new_status' => $ticket->status
            ]);

            return redirect()->route('admin.tickets.show', $ticket)
                ->with('success', 'Ticket mis à jour avec succès !');

        } catch (\Exception $e) {
            \Log::error('Erreur mise à jour ticket: ' . $e->getMessage());
            
            return redirect()->back()
                ->with('error', 'Erreur lors de la mise à jour')
                ->withInput();
        }
    }

    /**
     * Marquer ticket comme utilisé (MIGRÉ depuis AdminController::markTicketUsed)
     */
    public function markUsed(Ticket $ticket)
    {
        try {
            if ($ticket->status !== 'sold') {
                return response()->json([
                    'success' => false,
                    'message' => 'Seuls les tickets vendus peuvent être marqués comme utilisés'
                ], 400);
            }

            $ticket->update([
                'status' => 'used',
                'used_at' => now(),
                'used_by' => auth()->id()
            ]);

            \Log::info('Ticket marqué utilisé par admin', [
                'admin_id' => auth()->id(),
                'ticket_id' => $ticket->id,
                'ticket_code' => $ticket->ticket_code
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Ticket marqué comme utilisé avec succès'
            ]);

        } catch (\Exception $e) {
            \Log::error('Erreur marquage ticket utilisé: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du marquage'
            ], 500);
        }
    }

    /**
     * Annuler un ticket
     */
    public function cancel(Ticket $ticket)
    {
        try {
            if ($ticket->status === 'used') {
                return redirect()->back()
                    ->with('error', 'Un ticket déjà utilisé ne peut pas être annulé');
            }

            $ticket->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
                'cancelled_by' => auth()->id()
            ]);

            \Log::info('Ticket annulé par admin', [
                'admin_id' => auth()->id(),
                'ticket_id' => $ticket->id,
                'ticket_code' => $ticket->ticket_code
            ]);

            return redirect()->back()
                ->with('success', 'Ticket annulé avec succès !');

        } catch (\Exception $e) {
            \Log::error('Erreur annulation ticket: ' . $e->getMessage());
            
            return redirect()->back()
                ->with('error', 'Erreur lors de l\'annulation');
        }
    }

    /**
     * Réactiver un ticket annulé
     */
    public function reactivate(Ticket $ticket)
    {
        try {
            if ($ticket->status !== 'cancelled') {
                return redirect()->back()
                    ->with('error', 'Seuls les tickets annulés peuvent être réactivés');
            }

            $ticket->update([
                'status' => 'sold',
                'cancelled_at' => null,
                'cancelled_by' => null
            ]);

            \Log::info('Ticket réactivé par admin', [
                'admin_id' => auth()->id(),
                'ticket_id' => $ticket->id,
                'ticket_code' => $ticket->ticket_code
            ]);

            return redirect()->back()
                ->with('success', 'Ticket réactivé avec succès !');

        } catch (\Exception $e) {
            \Log::error('Erreur réactivation ticket: ' . $e->getMessage());
            
            return redirect()->back()
                ->with('error', 'Erreur lors de la réactivation');
        }
    }

    /**
     * Télécharger le QR code du ticket
     */
    public function download(Ticket $ticket)
    {
        try {
            if (empty($ticket->qr_code)) {
                // Générer le QR code si inexistant
                $qrService = app(QRCodeService::class);
                $qrBase64 = $qrService->generateTicketQRBase64($ticket);
                
                if ($qrBase64) {
                    $ticket->update(['qr_code' => $qrBase64]);
                } else {
                    return redirect()->back()->with('error', 'Impossible de générer le QR code');
                }
            }

            // Décoder le base64 et créer le fichier
            $qrData = base64_decode(str_replace('data:image/png;base64,', '', $ticket->qr_code));
            
            return response($qrData)
                ->header('Content-Type', 'image/png')
                ->header('Content-Disposition', 'attachment; filename="ticket-' . $ticket->ticket_code . '.png"');

        } catch (\Exception $e) {
            \Log::error('Erreur téléchargement QR ticket: ' . $e->getMessage());
            
            return redirect()->back()
                ->with('error', 'Erreur lors du téléchargement du QR code');
        }
    }

    /**
     * Actions en lot sur les tickets
     */
    public function bulkAction(Request $request)
    {
        $request->validate([
            'action' => 'required|in:mark_used,cancel,reactivate,regenerate_qr',
            'tickets' => 'required|array',
            'tickets.*' => 'exists:tickets,id'
        ]);

        try {
            $tickets = Ticket::whereIn('id', $request->tickets)->get();
            $count = 0;

            foreach ($tickets as $ticket) {
                switch ($request->action) {
                    case 'mark_used':
                        if ($ticket->status === 'sold') {
                            $ticket->update([
                                'status' => 'used',
                                'used_at' => now(),
                                'used_by' => auth()->id()
                            ]);
                            $count++;
                        }
                        break;
                        
                    case 'cancel':
                        if ($ticket->status !== 'used') {
                            $ticket->update([
                                'status' => 'cancelled',
                                'cancelled_at' => now(),
                                'cancelled_by' => auth()->id()
                            ]);
                            $count++;
                        }
                        break;
                        
                    case 'reactivate':
                        if ($ticket->status === 'cancelled') {
                            $ticket->update([
                                'status' => 'sold',
                                'cancelled_at' => null,
                                'cancelled_by' => null
                            ]);
                            $count++;
                        }
                        break;

                    case 'regenerate_qr':
                        try {
                            $qrService = app(QRCodeService::class);
                            $qrBase64 = $qrService->generateTicketQRBase64($ticket);
                            if ($qrBase64) {
                                $ticket->update(['qr_code' => $qrBase64]);
                                $count++;
                            }
                        } catch (\Exception $e) {
                            \Log::warning('Erreur régénération QR ticket ' . $ticket->id . ': ' . $e->getMessage());
                        }
                        break;
                }
            }

            \Log::info('Action en lot sur tickets par admin', [
                'admin_id' => auth()->id(),
                'action' => $request->action,
                'tickets_affected' => $count
            ]);

            return redirect()->back()
                ->with('success', "{$count} ticket(s) traité(s) avec succès !");

        } catch (\Exception $e) {
            \Log::error('Erreur action en lot tickets: ' . $e->getMessage());
            
            return redirect()->back()
                ->with('error', 'Erreur lors du traitement des tickets');
        }
    }

    /**
     * Export CSV personnalisé des tickets
     */
    public function export(Request $request)
    {
        try {
            $query = Ticket::with(['ticketType.event', 'orderTickets.order.user']);

            // Appliquer les mêmes filtres que l'index
            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }
            
            if ($request->filled('event')) {
                $query->whereHas('ticketType.event', function($eq) use ($request) {
                    $eq->where('id', $request->event);
                });
            }

            if ($request->filled('date_from')) {
                $query->where('created_at', '>=', $request->date_from);
            }

            if ($request->filled('date_to')) {
                $query->where('created_at', '<=', $request->date_to . ' 23:59:59');
            }

            $tickets = $query->get();

            $csvContent = "Code,Événement,Type,Client,Email,Statut,Prix,Créé le,Utilisé le\n";
            
            foreach ($tickets as $ticket) {
                $order = $ticket->orderTickets->first()?->order;
                $csvContent .= implode(',', [
                    $ticket->ticket_code,
                    '"' . addslashes($ticket->ticketType->event->title ?? 'N/A') . '"',
                    '"' . addslashes($ticket->ticketType->name ?? 'N/A') . '"',
                    '"' . addslashes($order?->user->name ?? 'N/A') . '"',
                    $order?->user->email ?? 'N/A',
                    $ticket->status,
                    number_format($ticket->ticketType->price ?? 0, 2),
                    $ticket->created_at->format('Y-m-d H:i:s'),
                    $ticket->used_at ? $ticket->used_at->format('Y-m-d H:i:s') : 'N/A'
                ]) . "\n";
            }

            return response($csvContent)
                ->header('Content-Type', 'text/csv')
                ->header('Content-Disposition', 'attachment; filename="tickets-export-' . now()->format('Y-m-d') . '.csv"');

        } catch (\Exception $e) {
            \Log::error('Erreur export tickets: ' . $e->getMessage());
            
            return redirect()->back()
                ->with('error', 'Erreur lors de l\'export');
        }
    }
}