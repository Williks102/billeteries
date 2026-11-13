<?php

namespace App\Actions\Events;

use App\Models\ActivityLog;
use App\Services\ActivityLogger;
use App\Support\Media;
use App\Models\Event;
use App\Models\TicketType;
use App\Support\Events\EventFormDefaults;
use App\Support\Events\SalePeriod;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Duplique un événement (organisateurs récurrents : on ne change que la date). Toujours un BROUILLON.
 *
 * Copié : titre « … (copie) », catégorie, lieu, adresse, description, conditions, heure (début et fin),
 * image (le FICHIER est copié sous un nouveau nom : supprimer l'original ne casse pas la copie),
 * billets (nom, prix, quantité = capacité d'origine, max par commande, description, is_active) avec quantity_sold = 0.
 * Recalé : date = prochain samedi (comme le formulaire de création) ; période de vente = de maintenant au début
 * de l'événement copié.
 * Jamais copié : commandes, billets émis, commissions, ventes, statut, lien vers l'original.
 * Le promoteur est celui de l'événement SOURCE (un admin qui duplique garde le promoteur d'origine).
 * Le contrôle d'accès (propriétaire / admin) est fait par l'appelant. L'original n'est pas modifié.
 */
class DuplicateEventAction
{
    public function execute(Event $source, ?Carbon $now = null): Event
    {
        $now = $now ?? Carbon::now();
        $imagePath = $this->copyImage($source->image);

        try {
            return DB::transaction(function () use ($source, $now, $imagePath) {
                $date = EventFormDefaults::values($now)['event_date'];
                $start = SalePeriod::eventStart($date, $source->event_time?->format('H:i') ?? EventFormDefaults::TIME);

                $copy = Event::create([
                    'promoter_id' => $source->promoter_id,
                    'category_id' => $source->category_id,
                    'title' => Str::limit($source->title, 247, '').' (copie)',
                    'description' => (string) $source->description,
                    'venue' => $source->venue,
                    'address' => $source->address,
                    'event_date' => $date,
                    'event_time' => $start,
                    'end_time' => $source->end_time ? SalePeriod::eventStart($date, $source->end_time->format('H:i')) : null,
                    'status' => 'draft',
                    'image' => $imagePath,
                    'terms_conditions' => $source->terms_conditions,
                ]);

                foreach ($source->ticketTypes()->orderBy('id')->get() as $type) {
                    TicketType::create([
                        'event_id' => $copy->id,
                        'name' => $type->name,
                        'description' => $type->description,
                        'price' => (int) $type->price,
                        'quantity_available' => (int) $type->quantity_available,
                        'quantity_sold' => 0,
                        'max_per_order' => $type->max_per_order,
                        'sale_start_date' => $now->copy(),
                        'sale_end_date' => $start->copy(),
                        'is_active' => (bool) $type->is_active,
                    ]);
                }

                ActivityLogger::record(
                    ActivityLog::EVENT_DUPLICATED,
                    $copy,
                    sprintf('Événement n°%d dupliqué depuis l\'événement n°%d (brouillon)', $copy->id, $source->id),
                    ['source_event_id' => $source->id, 'status' => 'draft']
                );

                return $copy;
            });
        } catch (\Throwable $e) {
            if ($imagePath) {
                Media::delete($imagePath);
            }
            throw $e;
        }
    }

    /** Copie le fichier image sous un nouveau nom généré ; null si pas d'image ou fichier introuvable. */
    private function copyImage(?string $path): ?string
    {
        if (empty($path) || ! Media::disk()->exists($path)) {
            return null;
        }

        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $extension = in_array($extension, ['jpg', 'jpeg', 'png', 'gif'], true) ? $extension : 'jpg';
        $new = 'events/'.Str::random(40).'.'.$extension;
        Media::disk()->copy($path, $new);

        return $new;
    }
}
