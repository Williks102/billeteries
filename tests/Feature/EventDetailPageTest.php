<?php

namespace Tests\Feature;

use App\Models\EventCategory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\Concerns\BuildsOrderSchema;
use Tests\TestCase;

/** Fiche publique d'un événement : barre d'achat mobile, date lisible, informations pratiques. */
class EventDetailPageTest extends TestCase
{
    use BuildsOrderSchema;

    protected function setUp(): void
    {
        parent::setUp();
        $this->buildOrderSchema();
        Schema::table('events', function ($t) {
            $t->text('description')->nullable();
            $t->string('address')->nullable();
            $t->string('image')->nullable();
            $t->dateTime('end_time')->nullable();
        });
        $cat = EventCategory::create(['name' => 'Musique', 'slug' => 'musique', 'is_active' => true]);
        DB::table('events')->where('id', 1)->update([
            'category_id' => $cat->id,
            'title' => 'Concert Public',
            'event_date' => '2030-11-17',
            'event_time' => '2030-11-17 12:00:00',
            'end_time' => '2030-11-17 15:00:00',
            'address' => 'Boulevard de Marseille',
        ]);
        $this->ticketType(10);
        DB::table('ticket_types')->update(['event_id' => 1]);
    }

    public function test_buy_bar_present_when_sales_open(): void
    {
        $this->get('/events/1')->assertOk()->assertSee('buy-bar', false)->assertSee('data-open-ticket-modal', false);
    }

    public function test_buy_bar_absent_when_event_past(): void
    {
        DB::table('events')->where('id', 1)->update(['event_date' => now()->subDay()->toDateString()]);
        $this->get('/events/1')->assertOk()->assertDontSee('data-open-ticket-modal', false);
    }

    public function test_date_is_french_with_end_time_and_organizer(): void
    {
        $res = $this->get('/events/1')->assertOk();
        $res->assertSee('Dimanche 17 novembre 2030')->assertSee('12h00 – 15h00')->assertSee('Organisé par');
        $res->assertDontSee('17/11/2030');
    }

    public function test_places_label(): void
    {
        DB::table('ticket_types')->update(['quantity_available' => 50]);
        $this->assertSame('Places disponibles', \App\Models\Event::with('ticketTypes')->find(1)->placesLabel());
        DB::table('ticket_types')->update(['quantity_available' => 5]);
        $this->assertSame('5 places restantes', \App\Models\Event::with('ticketTypes')->find(1)->placesLabel());
    }
}
