<?php
// app/Http/Controllers/Admin/EventController.php

namespace App\Http\Controllers\Admin;

use App\Support\Media;
use App\Actions\Events\CreateEventAction;
use App\Actions\Events\DuplicateEventAction;
use App\Actions\Events\PublishEventAction;
use App\Actions\Events\RealignSalePeriodsAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\AdminStoreEventRequest;
use App\Http\Requests\AdminUpdateEventRequest;
use App\Support\Events\EventFormDefaults;
use App\Support\Events\SalePeriod;
use Illuminate\Support\Facades\DB;
use App\Models\Event;
use App\Models\EventCategory;
use App\Models\TicketType;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use App\Http\Controllers\Admin\Concerns\ResolvesStatsPeriod;
use App\Services\Stats\CommissionStats;
use App\Services\Stats\EventStats;
use App\Services\Stats\PlatformStats;
use App\Services\Stats\SalesStats;
use App\Services\Stats\Scope;
use App\Services\Stats\TicketStats;
use App\Support\Stats\DatePeriod;

class EventController extends Controller
{
    use ResolvesStatsPeriod;

    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('admin');
    }

    /**
     * Liste des événements
     */
    public function index(Request $request)
    {
        $period = $this->statsPeriod($request, 'admin.events.index');

        // Mêmes filtres et même période que l'export (filteredQuery)
        $query = $this->filteredQuery($request, $period)->with(['category', 'promoteur']);

        $events = $query->latest()->paginate(20)->withQueryString();

        // Chiffres : services Stats. Statuts = instantané ; ventes = commandes payées de la période.
        $stats = app(PlatformStats::class)->events();
        $periodSales = app(SalesStats::class)->gmv(Scope::admin(), $period);
        // Ligne par événement : 5 requêtes pour toute la page (toutes dates), aucune requête par ligne.
        $eventStats = app(EventStats::class)->forEvents($events->pluck('id')->all());

        // Données pour les filtres
        $categories = EventCategory::orderBy('name')->get();
        $promoteurs = User::where('role', 'promoteur')->orderBy('name')->get();

        return view('admin.events.index', compact('events', 'stats', 'period', 'periodSales', 'eventStats', 'categories', 'promoteurs'));
    }

    /**
     * Formulaire unique de création (événement + billets) pour un promoteur choisi
     */
    public function create()
    {
        $categories = EventCategory::orderBy('name')->get();
        $promoteurs = User::where('role', 'promoteur')->orderBy('name')->get();
        $defaults = EventFormDefaults::values();

        return view('admin.events.create', compact('categories', 'promoteurs', 'defaults'));
    }

    /**
     * Création pour le promoteur choisi : mêmes règles et même Action que le promoteur (brouillon, ou publication si
     * la checklist le permet). Le statut et le mode de gestion ne sont pas des champs de création
     * (statut = brouillon ; management_mode = valeur par défaut de la base).
     */
    public function store(AdminStoreEventRequest $request, CreateEventAction $createEvent)
    {
        try {
            $result = $createEvent->execute(
                $request->eventData(),
                $request->ticketsData(),
                $request->file('image'),
                $request->promoterId(),
                $request->wantsPublication()
            );
        } catch (\Throwable $e) {
            Log::error('Erreur création événement (admin)', ['exception' => get_class($e), 'admin_id' => Auth::id()]);

            return back()->withInput()->with('error', 'Erreur lors de la création de l\'événement');
        }

        $redirect = redirect()->route('admin.events.show', $result->event);

        if ($result->published) {
            return $redirect->with('success', 'Événement créé et publié.');
        }
        if ($result->publicationRefused()) {
            return $redirect->with('warning', 'Événement enregistré en brouillon, il n\'est pas publié : '.implode(' ', $result->checklist['blocking']));
        }

        return $redirect->with('success', 'Brouillon enregistré. Vérifiez la liste ci-dessous avant de publier.');
    }

    /**
     * Affichage détail événement standard
     */
    public function show(Event $event)
    {
        try {
            $event->load(['category', 'promoteur', 'ticketTypes']);

            // Chiffres de l'événement (toutes dates) : services Stats. Promoteur = net après commission.
            $scope = Scope::event($event->id);
            $all = DatePeriod::all();
            $row = app(EventStats::class)->forEvents([$event->id])[$event->id];
            $tickets = app(TicketStats::class);
            $stats = $row + [
                'scanned' => $tickets->scanned($scope),
                'usage_rate' => $tickets->usageRate($scope),
                'reserved' => $tickets->reserved($scope),
                'available' => $tickets->available($scope),
                'pending_payout' => app(CommissionStats::class)->pendingPayout($scope, $all),
                'paid_out' => app(CommissionStats::class)->paidOut($scope, $all),
                'orders_awaiting_payment' => array_sum(array_intersect_key(
                    app(\App\Services\Stats\OrderStats::class)->countsByStatus($scope, $all),
                    ['pending' => 1, 'processing' => 1]
                )),
            ];

            // Commandes récentes pour cet événement
            $recentOrders = $event->orders()
                ->with(['user'])
                ->withSum('orderItems as tickets_quantity', 'quantity')
                ->latest()
                ->take(10)
                ->get();

            $checklist = $event->publicationChecklist();

            return view('admin.events.show', compact('event', 'stats', 'recentOrders', 'checklist'));

        } catch (\Exception $e) {
            Log::error('Erreur affichage événement: ' . $e->getMessage());
            return redirect()->route('admin.events.index')->with('error', 'Erreur lors du chargement');
        }
    }

    /**
     * Formulaire d'édition événement
     */
    public function edit(Event $event)
    {
        try {
            $event->load(['category', 'promoteur', 'ticketTypes']);
            $categories = EventCategory::orderBy('name')->get();
            $promoteurs = User::where('role', 'promoteur')->orderBy('name')->get();

            return view('admin.events.edit', compact('event', 'categories', 'promoteurs'));

        } catch (\Exception $e) {
            Log::error('Erreur édition événement: ' . $e->getMessage());
            return redirect()->route('admin.events.index')->with('error', 'Impossible de charger l\'édition');
        }
    }

    /**
     * Mise à jour événement (règles d'UpdateEventRequest + promoteur + statut). La publication passe par la checklist ;
     * un changement de date recale les fins de vente (RealignSalePeriodsAction).
     */
    public function update(AdminUpdateEventRequest $request, Event $event, RealignSalePeriodsAction $realign, PublishEventAction $publish)
    {
        try {
            $data = $request->eventData();
            $data['event_time'] = SalePeriod::eventStart($data['event_date'], $data['event_time']);
            $data['end_time'] = ! empty($data['end_time']) ? SalePeriod::eventStart($data['event_date'], $data['end_time']) : null;
            $data['promoter_id'] = (int) $request->validated()['promoter_id'];

            $newImage = null;
            $oldImage = $event->image;
            if ($request->hasFile('image')) {
                $newImage = Media::store($request->file('image'), 'events');
                $data['image'] = $newImage;
            }

            $wanted = $request->validated()['status'];
            $oldStart = $event->startsAt();
            $realigned = ['realigned' => 0, 'inconsistent' => 0];

            try {
                DB::transaction(function () use ($event, $data, $wanted, $oldStart, $realign, &$realigned) {
                    // La publication n'est jamais appliquée ici : PublishEventAction (checklist) après l'enregistrement.
                    $event->update($data + ['status' => $wanted === 'published' ? ($event->status === 'published' ? 'published' : 'draft') : $wanted]);
                    $newStart = $event->fresh()->startsAt();
                    if ($oldStart && $newStart && ! $oldStart->equalTo($newStart)) {
                        $realigned = $realign->execute($event, $oldStart, $newStart);
                    }
                });
            } catch (\Throwable $e) {
                if ($newImage) {
                    Media::delete($newImage);
                }
                throw $e;
            }

            if ($newImage && $oldImage) {
                Media::delete($oldImage);
            }

            $message = "Événement '{$event->title}' mis à jour avec succès !";
            $level = 'success';
            if ($wanted === 'published' && $event->status !== 'published') {
                $result = $publish->execute($event);
                if (! $result->published) {
                    $level = 'warning';
                    $message .= ' Il n\'est pas publié : '.implode(' ', $result->checklist['blocking']);
                }
            }
            if ($realigned['realigned'] > 0) {
                $message .= " Fins de vente recalées pour {$realigned['realigned']} billet(s).";
            }
            if ($realigned['inconsistent'] > 0) {
                $message .= " {$realigned['inconsistent']} billet(s) ont une période de vente incohérente : vérifiez-les.";
            }

            return redirect()->route('admin.events.index')->with($level, $message);

        } catch (\Throwable $e) {
            Log::error('Erreur mise à jour événement (admin)', ['exception' => get_class($e), 'event_id' => $event->id]);

            return redirect()->back()
                ->with('error', 'Erreur lors de la mise à jour')
                ->withInput();
        }
    }

    /**
     * Suppression événement
     */
    public function destroy(Event $event)
    {
        try {
            // Vérifier qu'il n'y a pas de commandes payées
            $paidOrders = $event->orders()->where('payment_status', 'paid')->count();
            
            if ($paidOrders > 0) {
                return redirect()->back()
                    ->with('error', 'Impossible de supprimer un événement avec des commandes payées. Vous pouvez le passer en statut "rejeté".');
            }

            // Log avant suppression
            $eventData = [
                'title' => $event->title,
                'promoter_id' => $event->promoter_id,
                'event_date' => $event->event_date
            ];

            // Supprimer l'image si elle existe
            if ($event->image) {
                Media::delete($event->image);
            }

            $eventTitle = $event->title;
            $event->delete();

            Log::info('Événement supprimé par admin', [
                'admin_id' => auth()->id(),
                'event_data' => $eventData
            ]);

            return redirect()->route('admin.events.index')
                ->with('success', "Événement '{$eventTitle}' supprimé avec succès !");

        } catch (\Exception $e) {
            Log::error('Erreur suppression événement: ' . $e->getMessage());
            
            return redirect()->back()
                ->with('error', 'Erreur lors de la suppression de l\'événement');
        }
    }

    /**
     * Mise à jour rapide du statut. `published` passe par la checklist (PublishEventAction) : refus 422 avec les raisons.
     */
    public function updateStatus(Request $request, Event $event, PublishEventAction $publish)
    {
        $request->validate([
            'status' => 'required|in:draft,published,cancelled'
        ]);

        try {
            if ($request->status === 'published') {
                $result = $publish->execute($event);
                if (! $result->checklist['can_publish']) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Publication impossible : '.implode(' ', $result->checklist['blocking']),
                        'reasons' => array_values($result->checklist['blocking']),
                    ], 422);
                }
            } else {
                $event->update(['status' => $request->status]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Statut mis à jour avec succès'
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur changement statut événement: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du changement de statut'
            ], 500);
        }
    }

    /**
     * Actions en lot
     */
    public function bulkAction(Request $request)
    {
        $request->validate([
            'action' => 'required|in:publish,delete',
            'events' => 'required|array',
            'events.*' => 'exists:events,id'
        ]);

        try {
            $events = Event::whereIn('id', $request->events)->get();
            $count = 0;
            $refused = [];

            foreach ($events as $event) {
                switch ($request->action) {
                    case 'publish':
                        // Même checklist que partout : un événement incomplet n'est pas publié (et n'est pas compté).
                        $result = app(PublishEventAction::class)->execute($event);
                        if ($result->published) {
                            $count++;
                        } else {
                            $refused[] = $event->title.' : '.(string) (array_values($result->checklist['blocking'])[0] ?? 'publication impossible');
                        }
                        break;
                        
                    case 'delete':
                        if ($event->orders()->where('payment_status', 'paid')->count() === 0) {
                            if ($event->image) {
                                Media::delete($event->image);
                            }
                            $event->delete();
                            $count++;
                        }
                        break;
                        
                }
            }

            Log::info('Action en lot sur événements par admin', [
                'admin_id' => auth()->id(),
                'action' => $request->action,
                'events_affected' => $count
            ]);

            $message = "{$count} événement(s) traité(s) avec succès !";
            if ($refused !== []) {
                $shown = array_slice($refused, 0, 5);
                $more = count($refused) - count($shown);
                $message .= ' Non publié(s) : '.implode(' ; ', $shown).($more > 0 ? " et {$more} autre(s)" : '').'.';
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'refused' => $refused,
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur action en lot événements: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'action groupée'
            ], 500);
        }
    }

    /**
     * Tableau de bord des événements nécessitant une intervention
     */
    public function interventionDashboard()
    {
        $eventsNeedingIntervention = Event::with(['promoteur', 'category', 'ticketTypes'])
            ->where(function($query) {
                // Événements publiés sans billets
                $query->where('status', 'published')
                      ->whereDoesntHave('ticketTypes');
            })
            ->orWhere(function($query) {
                // Événements proches sans billets actifs
                $query->where('event_date', '<', now()->addDays(2))
                      ->whereDoesntHave('ticketTypes', function($ticketQuery) {
                          $ticketQuery->where('is_active', true)
                                     ->where('sale_start_date', '<=', now())
                                     ->where('sale_end_date', '>=', now());
                      });
            })
            ->latest()
            ->paginate(20);

        return view('admin.events.intervention-dashboard', compact('eventsNeedingIntervention'));
    }

    /** Événements filtrés : mêmes filtres et même période (créés dans la période) pour la liste, les cartes ET l'export. */
    private function filteredQuery(Request $request, \App\Support\Stats\DatePeriod $period)
    {
        $query = Event::query();
        
        // Filtres
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        
        if ($request->filled('category')) {
            $query->where('category_id', $request->category);
        }
        
        if ($request->filled('search')) {
            $query->where(function($q) use ($request) {
                $q->where('title', 'like', '%' . $request->search . '%')
                  ->orWhere('description', 'like', '%' . $request->search . '%')
                  ->orWhereHas('promoteur', function($pq) use ($request) {
                      $pq->where('name', 'like', '%' . $request->search . '%');
                  });
            });
        }

        if ($request->filled('promoteur')) {
            $query->where('promoter_id', $request->promoteur);
        }

        if ($request->filled('date_from')) {
            $query->where('event_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->where('event_date', '<=', $request->date_to);
        }

        // Filtre par mode de gestion (nouveau)
        if ($request->filled('management_mode')) {
            $query->where('management_mode', $request->management_mode);
        }

        // Tri par défaut : plus récents
        // Période : événements créés dans la période affichée (cartes ET liste)
        if ($period->start !== null) {
            $query->where('events.created_at', '>=', $period->startForDb());
        }
        if ($period->end !== null) {
            $query->where('events.created_at', '<=', $period->endForDb());
        }

        return $query;
    }

    /**
     * Export CSV des événements : mêmes filtres et même période que l'écran ; chiffres par événement = ceux de la
     * liste (EventStats::forEvents, toutes dates : vendus = sold + used de commandes payées, ventes = somme des
     * lignes des commandes payées, net = commissions pending/paid), 5 requêtes par paquet de 500.
     * Aucune donnée personnelle d'acheteur : seulement le nom du promoteur.
     */
    public function export(Request $request)
    {
        $period = $this->statsPeriod($request, 'admin.events.index');
        $query = $this->filteredQuery($request, $period)->with(['category:id,name', 'promoteur:id,name']);

        return \App\Support\CsvExport::response(
            'evenements-'.now()->format('Y-m-d').'.csv',
            ['ID', 'Titre', 'Promoteur', 'Catégorie', 'Statut', 'Date événement', 'Billets vendus', 'Capacité',
                'Commandes payées', 'Ventes (FCFA)', 'Net promoteur (FCFA)', 'Créé le'],
            function (callable $emit) use ($query) {
                $query->chunkByIdDesc(500, function ($events) use ($emit) {
                    $figures = app(\App\Services\Stats\EventStats::class)->forEvents($events->pluck('id')->all());
                    foreach ($events as $event) {
                        $f = $figures[$event->id];
                        $emit([
                            $event->id,
                            $event->title,
                            $event->promoteur?->name ?? 'N/A',
                            $event->category?->name ?? 'N/A',
                            $event->status,
                            $event->event_date ? $event->event_date->format('d/m/Y') : 'N/A',
                            $f['sold'],
                            $f['capacity'],
                            $f['orders_paid'],
                            $f['gmv'],
                            $f['promoter_net'],
                            $event->created_at->format('d/m/Y H:i'),
                        ]);
                    }
                });
            }
        );
    }

    /**
     * Publication rapide : même checklist que le promoteur (PublishEventAction). Refus = liste des raisons
     * (JSON 422 pour l'appel AJAX, message d'erreur sinon), jamais une erreur 500.
     */
    public function quickPublish(Request $request, Event $event, PublishEventAction $publish)
    {
        try {
            $result = $publish->execute($event);
        } catch (\Throwable $e) {
            Log::error('Erreur publication rapide', ['exception' => get_class($e), 'event_id' => $event->id]);

            return $this->quickResponse($request, false, 'Erreur lors de la publication', 500);
        }

        if (! $result->checklist['can_publish']) {
            return $this->quickResponse($request, false, 'Publication impossible : '.implode(' ', $result->checklist['blocking']), 422, array_values($result->checklist['blocking']));
        }

        return $this->quickResponse($request, true, 'Événement publié avec succès');
    }

    /**
     * Dépublication rapide
     */
    public function quickUnpublish(Request $request, Event $event)
    {
        try {
            $event->update(['status' => 'draft']);
        } catch (\Throwable $e) {
            Log::error('Erreur dépublication rapide', ['exception' => get_class($e), 'event_id' => $event->id]);

            return $this->quickResponse($request, false, 'Erreur lors de la dépublication', 500);
        }

        return $this->quickResponse($request, true, 'Événement dépublié avec succès');
    }

    /**
     * Duplique un événement (de n'importe quel promoteur, qui reste son propriétaire) en brouillon, puis ouvre l'édition.
     */
    public function duplicate(Event $event, DuplicateEventAction $duplicate)
    {
        try {
            $copy = $duplicate->execute($event);
        } catch (\Throwable $e) {
            Log::error('Erreur duplication événement (admin)', ['exception' => get_class($e), 'event_id' => $event->id]);

            return back()->with('error', 'Erreur lors de la duplication de l\'événement');
        }

        return redirect()->route('admin.events.edit', $copy)
            ->with('success', 'Brouillon créé : choisissez la nouvelle date.');
    }

    private function quickResponse(Request $request, bool $success, string $message, int $status = 200, array $reasons = [])
    {
        if ($request->expectsJson()) {
            return response()->json(['success' => $success, 'message' => $message, 'reasons' => $reasons], $status);
        }

        return back()->with($success ? 'success' : 'error', $message);
    }

}