<?php

namespace App\Http\Controllers\Promoteur;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Actions\Events\CreateEventAction;
use App\Actions\Events\PublishEventAction;
use App\Http\Requests\StoreEventRequest;
use App\Http\Requests\UpdateEventRequest;
use App\Models\{Event, EventCategory};
use App\Support\Events\EventFormDefaults;
use App\Support\Events\SalePeriod;
use App\Services\Stats\CommissionStats;
use App\Services\Stats\PromoterStats;
use App\Services\Stats\Scope;
use App\Services\Stats\TicketStats;
use App\Support\Stats\DatePeriod;
use Carbon\Carbon;

class EventController extends Controller
{
    public function __construct(
        private readonly PromoterStats $promoterStats,
        private readonly TicketStats $ticketStats,
        private readonly CommissionStats $commissionStats,
    ) {
        $this->middleware('auth');
        $this->middleware('promoteur');
    }

    /**
     * Liste des événements du promoteur
     */
    public function index(Request $request)
    {
        try {
            $query = Auth::user()->events()->with('category')->withCount('ticketTypes');
            
            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }
            
            if ($request->filled('search')) {
                $query->where(function($q) use ($request) {
                    $q->where('title', 'like', '%' . $request->search . '%')
                      ->orWhere('description', 'like', '%' . $request->search . '%')
                      ->orWhere('venue', 'like', '%' . $request->search . '%');
                });
            }
            
            $events = $query->latest()->paginate(12);
            $categories = EventCategory::all();

            // Chiffres cumulés (toutes dates) de la page : 5 requêtes, jamais une par événement
            $eventStats = $this->promoterStats->eventsFigures((int) Auth::id(), $events->pluck('id')->all());

            $counts = $this->promoterStats->eventCounts((int) Auth::id(), DatePeriod::all());

            return view('promoteur.events.index', compact('events', 'categories', 'eventStats', 'counts'));
            
        } catch (\Exception $e) {
            \Log::error('Erreur dans promoteur.events.index: ' . $e->getMessage());
            
            $events = collect();
            $categories = collect();
            $eventStats = [];
            $counts = ['total' => 0, 'published' => 0, 'draft' => 0, 'created' => 0];
            
            return view('promoteur.events.index', compact('events', 'categories', 'eventStats', 'counts'))
                ->with('error', 'Erreur lors du chargement des événements');
        }
    }

    /**
     * Formulaire unique de création (événement + billets)
     */
    public function create()
    {
        $categories = EventCategory::all();
        $defaults = EventFormDefaults::values();

        return view('promoteur.events.create', compact('categories', 'defaults'));
    }

    /**
     * Sauvegarde événement + billets en une seule requête (brouillon, ou publication si la checklist le permet)
     */
    public function store(StoreEventRequest $request, CreateEventAction $createEvent)
    {
        try {
            $result = $createEvent->execute(
                $request->eventData(),
                $request->ticketsData(),
                $request->file('image'),
                (int) Auth::id(),
                $request->wantsPublication()
            );
        } catch (\Throwable $e) {
            \Log::error('Erreur création événement', ['exception' => get_class($e), 'message' => $e->getMessage()]);

            return back()
                ->withInput()
                ->with('error', 'Erreur lors de la création de l\'événement');
        }

        $redirect = redirect()->route('promoteur.events.show', $result->event);

        if ($result->published) {
            return $redirect->with('success', 'Événement créé et publié.');
        }
        if ($result->publicationRefused()) {
            return $redirect->with('warning', 'Événement enregistré en brouillon, il n\'est pas publié : '.implode(' ', $result->checklist['blocking']));
        }

        return $redirect->with('success', 'Brouillon enregistré. Vérifiez la liste ci-dessous avant de publier.');
    }

    /**
     * Affichage d'un événement
     */
    public function show(Event $event)
    {
        try {
            abort_if($event->promoter_id !== Auth::id(), 403);

            $event->load(['category', 'ticketTypes']);

            // Chiffres cumulés de l'événement (toutes dates) : mêmes définitions que le tableau de bord
            $figures = $this->promoterStats->eventsFigures((int) Auth::id(), [$event->id])[$event->id];
            $scope = Scope::event($event->id);
            $all = DatePeriod::all();
            $stats = $figures + [
                'reserved' => $this->ticketStats->reserved($scope),
                'available' => $this->ticketStats->available($scope),
                'pending_payout' => $this->commissionStats->pendingPayout($scope, $all),
                'paid_out' => $this->commissionStats->paidOut($scope, $all),
                'sold_by_type' => $this->promoterStats->soldByTicketType((int) Auth::id(), $event->id),
                'has_active_tickets' => $event->ticketTypes->contains('is_active', true),
            ];
            $checklist = $event->publicationChecklist();

            $recentOrders = $event->orders()
                ->where('payment_status', 'paid')
                ->with('user')
                ->withSum('orderItems as tickets_quantity', 'quantity')
                ->latest()
                ->limit(10)
                ->get();
                
            return view('promoteur.events.show', compact('event', 'stats', 'recentOrders', 'checklist'));
            
        } catch (\Exception $e) {
            \Log::error('Erreur dans promoteur.events.show: ' . $e->getMessage());
            
            return redirect()->route('promoteur.events.index')
                ->with('error', 'Erreur lors du chargement de l\'événement');
        }
    }

    /**
     * Formulaire d'édition d'un événement
     */
    public function edit(Event $event)
    {
        abort_if($event->promoter_id !== Auth::id(), 403, 'Vous n\'êtes pas autorisé à modifier cet événement');
        
        try {
            $categories = EventCategory::all();
            $stats = $this->promoterStats->eventsFigures((int) Auth::id(), [$event->id])[$event->id];
            $event->loadCount('ticketTypes');

            return view('promoteur.events.edit', compact('event', 'categories', 'stats'));
            
        } catch (\Exception $e) {
            \Log::error('Erreur lors de l\'affichage du formulaire d\'édition: ' . $e->getMessage());
            
            return redirect()->route('promoteur.events.index')
                ->with('error', 'Impossible de charger le formulaire d\'édition');
        }
    }

    /**
     * Mettre à jour un événement existant (autorisation : UpdateEventRequest)
     */
    public function update(UpdateEventRequest $request, Event $event)
    {
        try {
            $data = $request->eventData();
            $data['event_time'] = SalePeriod::eventStart($data['event_date'], $data['event_time']);
            $data['end_time'] = ! empty($data['end_time']) ? SalePeriod::eventStart($data['event_date'], $data['end_time']) : null;

            if ($request->hasFile('image')) {
                if ($event->image && Storage::disk('public')->exists($event->image)) {
                    Storage::disk('public')->delete($event->image);
                }

                $data['image'] = $request->file('image')->store('events', 'public');
            }

            $event->update($data);

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
        abort_if($event->promoter_id !== Auth::id(), 403, 'Vous n\'êtes pas autorisé à supprimer cet événement');

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
     * Publier un événement
     */
    public function publish(Event $event, PublishEventAction $publishEvent)
    {
        abort_if($event->promoter_id !== Auth::id(), 403);

        $result = $publishEvent->execute($event);

        if (! $result->checklist['can_publish']) {
            return back()->with('error', 'Publication impossible : '.implode(' ', $result->checklist['blocking']));
        }

        return back()->with('success', 'Événement publié.');
    }

    /**
     * Dépublier un événement
     */
    public function unpublish(Event $event)
    {
        abort_if($event->promoter_id !== Auth::id(), 403);
        $event->update(['status' => 'draft']);
        return back()->with('success', 'Événement dépublié.');
    }

    /**
     * Dupliquer un événement
     */
    public function duplicate(Event $event)
    {
        abort_if($event->promoter_id !== Auth::id(), 403);

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
}