<?php

namespace Tests\Concerns;

use App\Models\Event;
use App\Models\TicketType;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Table `events` complète (avec `management_mode` et son défaut de base), catégorie, utilisateurs types
 * et fabrique d'événement publiable. À utiliser avec BuildsOrderSchema (appeler buildOrderSchema() avant).
 * Utilisateurs : 1 acheteur, 2 promoteur P, 3 promoteur P2, 4 acheteur, 5 admin.
 */
trait BuildsEventsTable
{
    protected function buildEventsTable(): void
    {
        Schema::dropIfExists('events');
        Schema::create('events', function ($t) {
            $t->id();
            $t->unsignedBigInteger('promoter_id');
            $t->unsignedBigInteger('category_id');
            $t->string('title');
            $t->text('description');
            $t->string('venue');
            $t->text('address');
            $t->date('event_date');
            $t->dateTime('event_time');
            $t->dateTime('end_time')->nullable();
            $t->string('status')->default('draft');
            $t->string('management_mode')->default('promoter');
            $t->string('image')->nullable();
            $t->text('terms_conditions')->nullable();
            $t->timestamps();
        });
        DB::table('event_categories')->insert(['id' => 1, 'name' => 'Concert', 'slug' => 'concert']);

        $this->makeUser(2, 'promoteur');
        $this->makeUser(3, 'promoteur');
        $this->makeUser(4, 'acheteur');
        $this->makeUser(5, 'admin');
    }

    /** Événement publiable (billet valide, date future, image) du promoteur donné. */
    protected function readyEvent(int $promoterId = 2, array $override = []): Event
    {
        $event = Event::create($override + [
            'promoter_id' => $promoterId, 'category_id' => 1, 'title' => 'Prêt', 'description' => 'Une description',
            'venue' => 'Lieu', 'address' => 'Adresse', 'event_date' => '2026-11-20', 'event_time' => Carbon::parse('2026-11-20 19:30'),
            'status' => 'draft', 'image' => 'events/x.jpg',
        ]);
        TicketType::create([
            'event_id' => $event->id, 'name' => 'Std', 'price' => 1000, 'quantity_available' => 50, 'quantity_sold' => 0,
            'sale_start_date' => now(), 'sale_end_date' => Carbon::parse('2026-11-20 19:30'), 'max_per_order' => 10, 'is_active' => true,
        ]);

        return $event;
    }
}
