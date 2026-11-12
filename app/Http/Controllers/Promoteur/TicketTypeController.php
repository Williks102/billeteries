<?php

namespace App\Http\Controllers\Promoteur;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Event;
use App\Models\TicketType;
use App\Support\Events\SalePeriod;
use Illuminate\Validation\ValidationException;
use App\Models\EventCategory;
use App\Models\Commission;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class TicketTypeController extends Controller
{
    /**
     * Formulaire de création de types de billets
     */
    public function create(Event $event)
    {
        // Vérifier que l'événement appartient au promoteur connecté
        if ($event->promoter_id !== Auth::id()) {
            abort(403, 'Vous n\'êtes pas autorisé à modifier cet événement');
        }

        return view('promoteur.events.tickets.create', compact('event'));
    }

    /**
     * Sauvegarder les types de billets (aucune ligne `tickets` n'est pré-générée : elles naissent à la réservation)
     */
    public function store(Request $request, Event $event)
    {
        if ($event->promoter_id !== Auth::id()) {
            abort(403, 'Vous n\'êtes pas autorisé à modifier cet événement');
        }

        $validated = $request->validate([
            'ticket_types' => 'required|array|min:1',
            'ticket_types.*.name' => 'required|string|max:255',
            'ticket_types.*.description' => 'nullable|string|max:500',
            'ticket_types.*.price' => 'required|integer|min:0',
            'ticket_types.*.quantity_available' => 'required|integer|min:1',
            'ticket_types.*.sale_start_date' => 'nullable|date_format:Y-m-d',
            'ticket_types.*.sale_end_date' => 'nullable|date_format:Y-m-d',
            'ticket_types.*.max_per_order' => 'nullable|integer|min:1|max:20',
        ], [
            'ticket_types.required' => 'Vous devez créer au moins un type de billet',
            'ticket_types.*.name.required' => 'Le nom du billet est obligatoire',
            'ticket_types.*.price.required' => 'Le prix est obligatoire',
            'ticket_types.*.price.integer' => 'Le prix doit être un nombre entier de FCFA',
            'ticket_types.*.price.min' => 'Le prix ne peut pas être négatif',
            'ticket_types.*.quantity_available.required' => 'La quantité est obligatoire',
        ]);

        $start = $event->startsAt();
        $errors = [];
        foreach ($validated['ticket_types'] as $i => $row) {
            $quantity = (int) $row['quantity_available'];
            if (isset($row['max_per_order']) && (int) $row['max_per_order'] > $quantity) {
                $errors["ticket_types.$i.max_per_order"] = 'Le maximum par commande ne peut pas dépasser la quantité disponible.';
            }
            if ($start && ($violation = SalePeriod::violation($start, $row['sale_start_date'] ?? null, $row['sale_end_date'] ?? null))) {
                $errors["ticket_types.$i.{$violation[0]}"] = $violation[1];
            }
        }
        if ($errors) {
            throw ValidationException::withMessages($errors);
        }

        try {
            DB::transaction(function () use ($validated, $event, $start) {
                foreach ($validated['ticket_types'] as $typeData) {
                    [$saleStart, $saleEnd] = SalePeriod::resolve($start ?? now(), $typeData['sale_start_date'] ?? null, $typeData['sale_end_date'] ?? null);
                    $quantity = (int) $typeData['quantity_available'];

                    TicketType::create([
                        'event_id' => $event->id,
                        'name' => $typeData['name'],
                        'description' => $typeData['description'] ?? '',
                        'price' => (int) $typeData['price'],
                        'quantity_available' => $quantity,
                        'quantity_sold' => 0,
                        'sale_start_date' => $saleStart,
                        'sale_end_date' => $saleEnd,
                        'max_per_order' => min((int) ($typeData['max_per_order'] ?? 10), $quantity),
                        'is_active' => true,
                    ]);
                }
            });

            return redirect()->route('promoteur.events.show', $event)
                ->with('success', 'Types de billets créés avec succès !');
        } catch (\Throwable $e) {
            Log::error('Erreur lors de la création des billets', ['event_id' => $event->id, 'exception' => get_class($e)]);

            return redirect()->back()
                ->with('error', 'Une erreur est survenue lors de la création des billets.')
                ->withInput();
        }
    }

    /**
     * Liste des types de billets pour un événement
     */
    public function index(Event $event)
    {
        if ($event->promoter_id !== Auth::id()) {
            abort(403);
        }

        $ticketTypes = $event->ticketTypes()->orderBy('price', 'asc')->get();

        return view('promoteur.events.tickets.index', compact('event', 'ticketTypes'));
    }

    /**
     * Formulaire d'édition d'un type de billet
     */
    public function edit(Event $event, TicketType $ticketType)
    {
        if ($event->promoter_id !== Auth::id() || $ticketType->event_id !== $event->id) {
            abort(403);
        }

        return view('promoteur.events.tickets.edit', compact('event', 'ticketType'));
    }

    /**
     * Mettre à jour un type de billet
     */
    public function update(Request $request, Event $event, TicketType $ticketType)
    {
        if ($event->promoter_id !== Auth::id() || $ticketType->event_id !== $event->id) {
            abort(403);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
            'price' => 'required|integer|min:0',
            'quantity_available' => 'required|integer|min:' . max(1, (int) $ticketType->quantity_sold),
            'sale_start_date' => 'required|date_format:Y-m-d',
            'sale_end_date' => 'required|date_format:Y-m-d',
            'max_per_order' => 'required|integer|min:1|max:20',
        ], [
            'price.integer' => 'Le prix doit être un nombre entier de FCFA',
            'quantity_available.min' => 'La quantité ne peut pas être inférieure au nombre de billets déjà vendus (' . $ticketType->quantity_sold . ')',
        ]);

        $start = $event->startsAt();
        $errors = [];
        if ((int) $request->max_per_order > (int) $request->quantity_available) {
            $errors['max_per_order'] = 'Le maximum par commande ne peut pas dépasser la quantité disponible.';
        }
        if ($start && ($violation = SalePeriod::violation($start, $request->sale_start_date, $request->sale_end_date))) {
            $errors[$violation[0]] = $violation[1];
        }
        if ($errors) {
            throw ValidationException::withMessages($errors);
        }

        [$saleStart, $saleEnd] = SalePeriod::resolve($start ?? now(), $request->sale_start_date, $request->sale_end_date);

        $ticketType->update([
            'name' => $request->name,
            'description' => $request->description ?? '',
            'price' => (int) $request->price,
            'quantity_available' => (int) $request->quantity_available,
            'sale_start_date' => $saleStart,
            'sale_end_date' => $saleEnd,
            'max_per_order' => (int) $request->max_per_order,
        ]);

        return redirect()->route('promoteur.events.tickets.index', $event)
            ->with('success', 'Type de billet modifié avec succès !');
    }

    /**
     * Activer / désactiver la vente d'un type de billet
     */
    public function toggle(Event $event, TicketType $ticketType)
    {
        if ($event->promoter_id !== Auth::id() || $ticketType->event_id !== $event->id) {
            abort(403);
        }

        $ticketType->update(['is_active' => !$ticketType->is_active]);

        return redirect()->back()->with(
            'success',
            $ticketType->is_active ? 'Type de billet activé.' : 'Type de billet désactivé.'
        );
    }

    /**
     * Supprimer un type de billet
     */
    public function destroy(Event $event, TicketType $ticketType)
    {
        if ($event->promoter_id !== Auth::id() || $ticketType->event_id !== $event->id) {
            abort(403);
        }

        // Vérifier qu'aucun billet n'a été vendu
        if ($ticketType->quantity_sold > 0) {
            return redirect()->back()
                ->with('error', 'Impossible de supprimer un type de billet déjà vendu');
        }

        // Supprimer les tickets associés
        $ticketType->tickets()->delete();
        $ticketType->delete();

        return redirect()->route('promoteur.events.tickets.index', $event)
            ->with('success', 'Type de billet supprimé avec succès');
    }
}