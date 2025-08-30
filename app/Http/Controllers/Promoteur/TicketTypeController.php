<?php

namespace App\Http\Controllers\Promoteur;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Event;
use App\Models\TicketType;
use App\Models\Ticket;
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
     * Sauvegarder les types de billets
     */
    public function store(Request $request, Event $event)
    {
        if ($event->promoter_id !== Auth::id()) {
            abort(403, 'Vous n\'êtes pas autorisé à modifier cet événement');
        }

        Log::info('=== DEBUG TICKET CREATION ===');
        Log::info('Données reçues:', $request->all());
        Log::info('Event ID: ' . $event->id);
        Log::info('Date actuelle: ' . now());

        // Validation des données
        try {
            $request->validate([
                'ticket_types' => 'required|array|min:1',
                'ticket_types.*.name' => 'required|string|max:255',
                'ticket_types.*.description' => 'nullable|string|max:500',
                'ticket_types.*.price' => 'required|numeric|min:0',
                'ticket_types.*.quantity_available' => 'required|integer|min:1',
                'ticket_types.*.sale_start_date' => 'required|date',
                'ticket_types.*.sale_end_date' => 'required|date|after:sale_start_date',
                'ticket_types.*.max_per_order' => 'required|integer|min:1|max:20',
            ], [
                'ticket_types.required' => 'Vous devez créer au moins un type de billet',
                'ticket_types.*.name.required' => 'Le nom du billet est obligatoire',
                'ticket_types.*.price.required' => 'Le prix est obligatoire',
                'ticket_types.*.price.min' => 'Le prix ne peut pas être négatif',
                'ticket_types.*.quantity_available.required' => 'La quantité est obligatoire',
                'ticket_types.*.sale_end_date.after' => 'La fin de vente doit être après le début',
            ]);

            Log::info('✅ Validation réussie !');
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('❌ ERREURS DE VALIDATION:', $e->errors());
            throw $e;
        }

        // Transaction pour assurer la cohérence
        DB::beginTransaction();
        
        try {
            Log::info('🔄 Début création des billets');
            
            $createdTypes = [];
            $index = 0;
            
            foreach ($request->ticket_types as $typeData) {
                Log::info("📝 Création du billet #{$index}:", $typeData);
                
                // Créer le type de billet
                $ticketType = TicketType::create([
                    'event_id' => $event->id,
                    'name' => $typeData['name'],
                    'description' => $typeData['description'] ?? '',
                    'price' => $typeData['price'],
                    'quantity_available' => $typeData['quantity_available'],
                    'quantity_sold' => 0,
                    'sale_start_date' => Carbon::parse($typeData['sale_start_date']),
                    'sale_end_date' => Carbon::parse($typeData['sale_end_date'] . ' 23:59:59'),
                    'max_per_order' => $typeData['max_per_order'],
                    'is_active' => true,
                ]);
                
                Log::info("✅ TicketType créé avec ID: {$ticketType->id}");
                
                // Générer les tickets individuels (optionnel - peut être fait lors de l'achat)
                $this->generateTicketsForType($ticketType);
                
                $createdTypes[] = $ticketType;
                $index++;
            }
            
            // Si tout s'est bien passé, on confirme la transaction
            DB::commit();
            
            Log::info("🎉 Création terminée avec succès ! Total: " . count($createdTypes) . " types créés");
            
            return redirect()->route('promoteur.events.show', $event)
                ->with('success', 'Types de billets créés avec succès ! Votre événement est maintenant prêt à être publié.');
                
        } catch (\Exception $e) {
            DB::rollback();
            Log::error("💥 Erreur lors de la création des billets: " . $e->getMessage());
            Log::error("Stack trace: " . $e->getTraceAsString());
            
            return redirect()->back()
                ->with('error', 'Une erreur est survenue lors de la création des billets: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Générer les tickets individuels pour un type de billet
     * (Optionnel - peut être fait lors de l'achat pour optimiser les performances)
     */
    private function generateTicketsForType(TicketType $ticketType)
    {
        Log::info("🎫 Génération des tickets pour type: {$ticketType->name} (Quantité: {$ticketType->quantity_available})");
        
        // Pour éviter la surcharge, on peut limiter la génération pour les gros événements
        if ($ticketType->quantity_available > 1000) {
            Log::info("⚠️ Génération différée pour grosse quantité ({$ticketType->quantity_available} tickets)");
            return; // Les tickets seront générés lors de l'achat
        }
        
        $batchSize = 100; // Traiter par lots de 100
        $generated = 0;
        
        for ($i = 0; $i < $ticketType->quantity_available; $i += $batchSize) {
            $currentBatch = min($batchSize, $ticketType->quantity_available - $i);
            $tickets = [];
            
            for ($j = 0; $j < $currentBatch; $j++) {
                $tickets[] = [
                    'ticket_type_id' => $ticketType->id,
                    'ticket_code' => $this->generateUniqueTicketCode(),
                    'status' => 'available',
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
            
            // Insertion en lot pour optimiser les performances
            Ticket::insert($tickets);
            $generated += count($tickets);
            
            Log::info("📦 Lot généré: {$generated}/{$ticketType->quantity_available}");
        }
        
        Log::info("✅ {$generated} tickets générés pour {$ticketType->name}");
    }
    
    /**
     * Générer un code de ticket unique
     */
    private function generateUniqueTicketCode()
    {
        do {
            $code = 'TKT-' . strtoupper(uniqid());
        } while (Ticket::where('ticket_code', $code)->exists());
        
        return $code;
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
            'price' => 'required|numeric|min:0',
            'quantity_available' => 'required|integer|min:' . $ticketType->quantity_sold,
            'sale_start_date' => 'required|date',
            'sale_end_date' => 'required|date|after:sale_start_date',
            'max_per_order' => 'required|integer|min:1|max:20',
        ], [
            'quantity_available.min' => 'La quantité ne peut pas être inférieure au nombre de billets déjà vendus (' . $ticketType->quantity_sold . ')',
        ]);

        $ticketType->update([
            'name' => $request->name,
            'description' => $request->description ?? '',
            'price' => $request->price,
            'quantity_available' => $request->quantity_available,
            'sale_start_date' => Carbon::parse($request->sale_start_date),
            'sale_end_date' => Carbon::parse($request->sale_end_date . ' 23:59:59'),
            'max_per_order' => $request->max_per_order,
        ]);

        return redirect()->route('promoteur.events.tickets.index', $event)
            ->with('success', 'Type de billet modifié avec succès !');
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