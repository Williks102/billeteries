<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Admin\Concerns\ResolvesStatsPeriod;
use App\Helpers\PrivacyHelper;
use App\Models\Event;
use App\Support\CsvSafe;
use App\Models\Order;
use App\Models\Ticket;
use App\Models\TicketType;
use App\Services\QRCodeService;
use App\Services\Stats\Scope;
use App\Services\Stats\TicketStats;
use App\Support\Stats\DatePeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class TicketController extends Controller
{
    use ResolvesStatsPeriod;

    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('admin');
    }

    /** Statuts de billet filtrables (colonne `status`). */
    public const STATUSES = ['available', 'reserved', 'sold', 'used', 'cancelled'];

    /** Relations chargées pour retrouver la commande et l'acheteur des billets récents ET anciens (sans N+1). */
    private const LIST_RELATIONS = ['ticketType.event', 'orderItem.order.user', 'orders.user'];

    /**
     * Liste des billets : filtres (statut, événement, code, période), cartes de synthèse (TicketStats),
     * pagination. La période s'applique à la date de vente de la commande liée (comme TicketStats) ;
     * elle ne s'applique pas aux billets « disponibles » (sans commande).
     */
    public function index(Request $request)
    {
        $period = $this->statsPeriod($request, 'admin.tickets.index');
        $filters = $this->validatedFilters($request);

        $query = $this->filteredQuery($filters, $period)->with(self::LIST_RELATIONS);
        $tickets = $query->orderByDesc('tickets.id')->paginate(20)->withQueryString();

        $scope = ! empty($filters['event']) ? Scope::event((int) $filters['event']) : Scope::admin();
        $stats = app(TicketStats::class);
        $summary = [
            'sold' => $stats->sold($scope, $period),
            'scanned' => $stats->scanned($scope, $period),
            'usage_rate' => $stats->usageRate($scope, $period),
            'reserved' => $stats->reserved($scope),
        ];

        $events = Event::orderBy('title')->get(['id', 'title']);
        $statuses = self::STATUSES;

        return view('admin.tickets.index', compact('tickets', 'summary', 'period', 'events', 'statuses'));
    }

    /**
     * Fiche d'un billet : détails, commande liée, historique, QR (billet émis seulement), actions permises.
     */
    public function show(Ticket $ticket)
    {
        $ticket->load(['ticketType.event.promoteur', 'orderItem.order.user', 'orders.user']);

        $order = $ticket->linkedOrder();
        $event = $ticket->ticketType?->event;

        $history = collect([['message' => $ticket->status === 'available' ? 'Billet créé' : 'Billet créé / réservé', 'date' => $ticket->created_at, 'icon' => 'fa-ticket-alt']]);
        if ($order?->payment_status === 'paid') {
            $history->push(['message' => 'Vendu (paiement confirmé)', 'date' => $order->paid_at ?? $order->created_at, 'icon' => 'fa-credit-card']);
        }
        if ($ticket->used_at) {
            $history->push(['message' => 'Scanné / utilisé', 'date' => $ticket->used_at, 'icon' => 'fa-check-circle']);
        }
        if ($ticket->status === 'cancelled') {
            // Pas de colonne cancelled_at : dernière modification du billet.
            $history->push(['message' => 'Annulé (date de dernière modification)', 'date' => $ticket->updated_at, 'icon' => 'fa-times-circle']);
        }
        $history = $history->sortBy('date')->values();

        $issued = $ticket->isIssued();
        $qrUri = $issued ? $ticket->qrDataUri() : null;
        $can = [
            'mark_used' => $ticket->canTransitionTo('used'),
            'cancel' => $ticket->canTransitionTo('cancelled'),
            'reactivate' => $ticket->canTransitionTo('sold') && $ticket->status === 'cancelled',
            'regenerate_qr' => $issued,
            'edit' => $ticket->adminTransitions() !== [],
        ];

        return view('admin.tickets.show', compact('ticket', 'order', 'event', 'history', 'issued', 'qrUri', 'can'));
    }

    /**
     * Formulaire d'édition : seul le statut est modifiable, parmi les transitions autorisées.
     */
    public function edit(Ticket $ticket)
    {
        $ticket->load(['ticketType.event', 'orderItem.order.user', 'orders.user']);
        $order = $ticket->linkedOrder();
        $transitions = $ticket->adminTransitions();

        return view('admin.tickets.edit', compact('ticket', 'order', 'transitions'));
    }

    /**
     * Mise à jour : changement de statut selon Ticket::adminTransitions() (mêmes règles que les actions).
     */
    public function update(Request $request, Ticket $ticket)
    {
        $ticket->load(['orderItem.order', 'orders']);
        $request->validate([
            'status' => ['required', Rule::in(array_merge([$ticket->status], $ticket->adminTransitions()))],
        ], [
            'status.in' => "Ce changement de statut n'est pas autorisé pour ce billet.",
        ]);

        $to = $request->input('status');
        if ($to === $ticket->status) {
            return redirect()->route('admin.tickets.show', $ticket)->with('success', 'Aucun changement.');
        }

        if (! $this->applyTransition($ticket, $to)) {
            return back()->withInput()->with('error', "Ce changement de statut n'est pas autorisé pour ce billet.");
        }

        return redirect()->route('admin.tickets.show', $ticket)->with('success', 'Ticket mis à jour avec succès !');
    }

    /**
     * Marquer ticket comme utilisé : billet vendu d'une commande payée uniquement.
     */
    public function markUsed(Request $request, Ticket $ticket)
    {
        $ok = $this->applyTransition($ticket, 'used');
        $message = $ok ? 'Ticket marqué comme utilisé avec succès' : "Seuls les tickets vendus d'une commande payée peuvent être marqués comme utilisés";

        if ($request->expectsJson()) {
            return response()->json(['success' => $ok, 'message' => $message], $ok ? 200 : 400);
        }

        return back()->with($ok ? 'success' : 'error', $message);
    }

    /**
     * Annuler un ticket (sauf s'il est déjà utilisé).
     */
    public function cancel(Ticket $ticket)
    {
        if ($ticket->status === 'used') {
            return back()->with('error', 'Un ticket déjà utilisé ne peut pas être annulé');
        }

        return $this->applyTransition($ticket, 'cancelled')
            ? back()->with('success', 'Ticket annulé avec succès !')
            : back()->with('error', "Erreur lors de l'annulation");
    }

    /**
     * Réactiver un ticket annulé.
     */
    public function reactivate(Ticket $ticket)
    {
        if ($ticket->status !== 'cancelled') {
            return back()->with('error', 'Seuls les tickets annulés peuvent être réactivés');
        }

        return $this->applyTransition($ticket, 'sold')
            ? back()->with('success', 'Ticket réactivé avec succès !')
            : back()->with('error', 'Billet d\'une commande non payée/remboursée : réactivation impossible');
    }

    /**
     * Applique une transition sous verrou : revérifie le statut courant (ticket relu avec lockForUpdate).
     * Journalise sans donnée personnelle.
     */
    private function applyTransition(Ticket $ticket, string $to): bool
    {
        $from = null;
        $done = DB::transaction(function () use ($ticket, $to, &$from) {
            $locked = Ticket::whereKey($ticket->id)->lockForUpdate()->first();
            if ($locked === null) {
                return false;
            }
            $from = $locked->status;
            if (! $locked->canTransitionTo($to)) {
                return false;
            }

            $data = ['status' => $to];
            if ($to === 'used') {
                $data['used_at'] = now();
            }
            $locked->update($data);

            return true;
        });

        if ($done) {
            $ticket->refresh();
            Log::info('Ticket modifié par admin', ['admin_id' => auth()->id(), 'ticket_id' => $ticket->id, 'old_status' => $from, 'new_status' => $to]);
        }

        return $done;
    }

    /** Filtres validés de la liste et de l'export ; une valeur invalide revient à la liste avec l'erreur. */
    private function validatedFilters(Request $request): array
    {
        try {
            return $request->validate([
                'status' => ['nullable', Rule::in(self::STATUSES)],
                'event' => ['nullable', 'integer', 'min:1'],
                'search' => ['nullable', 'string', 'max:100'],
            ]);
        } catch (ValidationException $e) {
            throw $e->redirectTo(route('admin.tickets.index'));
        }
    }

    private function filteredQuery(array $filters, ?DatePeriod $period)
    {
        $query = Ticket::query();

        if (! empty($filters['status'])) {
            $query->where('tickets.status', $filters['status']);
        }

        if (! empty($filters['event'])) {
            $query->whereIn('tickets.ticket_type_id', TicketType::query()->where('event_id', $filters['event'])->select('id'));
        }

        if (! empty($filters['search'])) {
            $like = '%'.addcslashes($filters['search'], '\\%_').'%';
            $query->where('tickets.ticket_code', 'like', $like);
        }

        if ($period !== null && $period->isBounded() && ($filters['status'] ?? null) !== 'available') {
            $query->ofOrders(Order::query()->inPeriod($period));
        }

        return $query;
    }

    /**
     * Télécharger le QR code du ticket
     */
    public function download(Ticket $ticket)
    {
        try {
            // Billet non émis (paiement non confirmé) => pas de QR ; sinon QR généré/persisté par le modèle.
            $qrUri = $ticket->qrDataUri();
            if (!$qrUri) {
                return redirect()->back()->with('error', "QR indisponible : le paiement de la commande n'est pas confirmé ou la génération a échoué.");
            }

            $qrData = base64_decode(str_replace('data:image/png;base64,', '', $qrUri));

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
            'tickets' => 'required|array|max:200',
            'tickets.*' => 'exists:tickets,id'
        ]);

        try {
            $tickets = Ticket::with(['orderItem.order', 'orders'])->whereIn('id', $request->tickets)->get();
            $count = 0;
            $to = ['mark_used' => 'used', 'cancel' => 'cancelled', 'reactivate' => 'sold'][$request->action] ?? null;

            foreach ($tickets as $ticket) {
                if ($to !== null) {
                    // mêmes règles que les actions unitaires (Ticket::adminTransitions)
                    if ($this->applyTransition($ticket, $to)) {
                        $count++;
                    }
                } elseif ($ticket->isIssued()) {
                    // regenerate_qr : billets émis uniquement (jamais de QR avant paiement)
                    try {
                        $qrBase64 = app(QRCodeService::class)->generateTicketQRBase64($ticket);
                        if ($qrBase64) {
                            $ticket->update(['qr_code' => $qrBase64]);
                            $count++;
                        }
                    } catch (\Exception $e) {
                        \Log::warning('Erreur régénération QR ticket ' . $ticket->id . ': ' . $e->getMessage());
                    }
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
            $period = $this->statsPeriod($request, 'admin.tickets.index');
            $query = $this->filteredQuery($this->validatedFilters($request), $period)->with(self::LIST_RELATIONS);

            $out = fopen('php://temp', 'w+');
            fwrite($out, "\xEF\xBB\xBF"); // BOM UTF-8 (Excel)
            fputcsv($out, ['Code', 'Événement', 'Type', 'Client', 'Email (masqué)', 'Statut', 'Prix (FCFA)', 'Créé le', 'Utilisé le']);

            $query->orderBy('tickets.id')->chunk(500, function ($tickets) use ($out) {
                foreach ($tickets as $ticket) {
                    $order = $ticket->linkedOrder();
                    fputcsv($out, array_map([CsvSafe::class, 'cell'], [
                        $ticket->ticket_code,
                        $ticket->ticketType->event->title ?? 'N/A',
                        $ticket->ticketType->name ?? 'N/A',
                        $order?->user?->name ?? 'N/A',
                        PrivacyHelper::maskEmail($order?->user?->email ?? $order?->billing_email),
                        $ticket->status,
                        (int) round($ticket->ticketType->price ?? 0),
                        $ticket->created_at->format('Y-m-d H:i:s'),
                        $ticket->used_at ? $ticket->used_at->format('Y-m-d H:i:s') : 'N/A',
                    ]));
                }
            });
            rewind($out);
            $csvContent = stream_get_contents($out);
            fclose($out);

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