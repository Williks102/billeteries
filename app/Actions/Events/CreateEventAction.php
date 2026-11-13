<?php

namespace App\Actions\Events;

use App\Models\ActivityLog;
use App\Services\ActivityLogger;
use App\Support\Media;
use App\Models\Event;
use App\Models\TicketType;
use App\Support\Events\SalePeriod;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

/**
 * Crée un événement et ses billets dans UNE transaction.
 *
 * - le promoteur est passé explicitement (utilisateur connecté, ou, côté admin, un promoteur choisi) ;
 * - l'événement est créé en `draft` ; si « publier » est demandé, PublishEventAction décide : checklist non
 *   satisfaite => l'événement reste brouillon (jamais publié par accident) ;
 * - aucune ligne `tickets` n'est créée : elles naissent à la réservation (CheckoutService) ;
 * - mass-assignment : liste explicite de champs.
 */
class CreateEventAction
{
    public function __construct(private readonly PublishEventAction $publish)
    {
    }

    /**
     * @param  array<string,mixed>  $data  title, category_id, description, venue, address, event_date (Y-m-d), event_time (H:i), end_time?, terms_conditions?
     * @param  array<int,array<string,mixed>>  $tickets  name, price, quantity_available, description?, max_per_order?, sale_start_date?, sale_end_date?
     */
    public function execute(array $data, array $tickets, ?UploadedFile $image, int $promoterId, bool $publish = false): EventCreationResult
    {
        $imagePath = $image ? Media::store($image, 'events') : null;

        try {
            $event = DB::transaction(function () use ($data, $tickets, $imagePath, $promoterId) {
                $start = SalePeriod::eventStart($data['event_date'], $data['event_time']);
                $date = $data['event_date'];

                $event = Event::create([
                    'promoter_id' => $promoterId,
                    'category_id' => $data['category_id'],
                    'title' => $data['title'],
                    'description' => (string) ($data['description'] ?? ''),
                    'venue' => $data['venue'],
                    'address' => ($data['address'] ?? '') !== '' ? $data['address'] : $data['venue'],
                    'event_date' => $date,
                    'event_time' => $start,
                    'end_time' => ! empty($data['end_time']) ? SalePeriod::eventStart($date, $data['end_time']) : null,
                    'status' => 'draft',
                    'image' => $imagePath,
                    'terms_conditions' => $data['terms_conditions'] ?? null,
                ]);

                foreach ($tickets as $ticket) {
                    [$saleStart, $saleEnd] = SalePeriod::resolve($start, $ticket['sale_start_date'] ?? null, $ticket['sale_end_date'] ?? null);
                    $quantity = (int) $ticket['quantity_available'];

                    TicketType::create([
                        'event_id' => $event->id,
                        'name' => $ticket['name'],
                        'description' => (string) ($ticket['description'] ?? ''),
                        'price' => (int) $ticket['price'],
                        'quantity_available' => $quantity,
                        'quantity_sold' => 0,
                        'sale_start_date' => $saleStart,
                        'sale_end_date' => $saleEnd,
                        'max_per_order' => min((int) ($ticket['max_per_order'] ?? 10), $quantity),
                        'is_active' => true,
                    ]);
                }

                ActivityLogger::record(
                    ActivityLog::EVENT_CREATED,
                    $event,
                    sprintf('Événement n°%d créé (brouillon, %d type(s) de billet)', $event->id, count($tickets)),
                    ['promoter_id' => $promoterId, 'status' => 'draft', 'ticket_types' => count($tickets)]
                );

                return $event;
            });
        } catch (\Throwable $e) {
            if ($imagePath) {
                Media::delete($imagePath);
            }
            throw $e;
        }

        if ($publish) {
            return $this->publish->execute($event);
        }

        return new EventCreationResult($event, false, false, $event->publicationChecklist());
    }
}
