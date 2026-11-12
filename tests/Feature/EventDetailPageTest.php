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

    public function test_hero_is_compact_without_image_and_normal_with_image(): void
    {
        $this->get('/events/1')->assertOk()->assertSee('event-hero event-hero--compact', false);
        DB::table('events')->where('id', 1)->update(['image' => 'events/a.jpg']);
        $this->get('/events/1')->assertOk()->assertDontSee('event-hero--compact', false);
    }

    public function test_ticket_list_shows_price_free_and_sold_out(): void
    {
        $now = now();
        $base = ['event_id' => 1, 'quantity_sold' => 0, 'max_per_order' => 10, 'is_active' => 1,
            'sale_start_date' => $now->copy()->subDay(), 'sale_end_date' => $now->copy()->addDay(),
            'created_at' => $now, 'updated_at' => $now];
        DB::table('ticket_types')->insert([
            $base + ['name' => 'Entrée libre', 'price' => 0, 'quantity_available' => 50],
            array_merge($base, ['name' => 'VIP Or', 'price' => 25000, 'quantity_available' => 5, 'quantity_sold' => 5]),
        ]);
        $res = $this->get('/events/1')->assertOk();
        $res->assertSee('ticket-list', false)->assertSee('Entrée libre')->assertSee('Gratuit')
            ->assertSee('VIP Or')->assertSee(\App\Helpers\CurrencyHelper::formatFCFA(25000))->assertSee('Épuisé');
        $res->assertDontSee('Réservation immédiate')->assertDontSee('2 types de billets');
    }

    public function test_directions_link_is_encoded_and_safe(): void
    {
        DB::table('events')->where('id', 1)->update(['venue' => 'Palais & Culture', 'address' => 'Bd de Marseille, Treichville']);
        $url = 'https://www.google.com/maps/search/?api=1&amp;query=' . rawurlencode('Palais & Culture, Bd de Marseille, Treichville');
        $this->get('/events/1')->assertOk()->assertSee($url, false)
            ->assertSee('target="_blank" rel="noopener noreferrer"', false)->assertSee('Itinéraire');
    }

    public function test_share_order_whatsapp_first_and_x_label(): void
    {
        $page = $this->get('/events/1')->assertOk()->getContent();
        $start = strpos($page, 'share-actions');
        $html = substr($page, $start, strpos($page, '</section>', $start) - $start);
        $w = strpos($html, 'WhatsApp');
        $f = strpos($html, 'Facebook');
        $x = strpos($html, '</i>X<');
        $this->assertTrue($w !== false && $w < $f && $f < $x, 'ordre WhatsApp, Facebook, X');
        $this->assertStringNotContainsString('Twitter', $html);
        $this->assertStringContainsString('data-native-share', $html);
        $this->assertStringContainsString('js/event-share.js', $page);
    }

    public function test_desktop_partial_has_long_date_and_organizer(): void
    {
        DB::table('ticket_types')->update(['quantity_available' => 50]);
        $event = \App\Models\Event::with(['ticketTypes', 'promoteur'])->find(1);
        $html = view('events.partials.event-details-desktop', ['event' => $event, 'similarEvents' => collect()])->render();
        $this->assertStringContainsString('Dimanche 17 novembre 2030', $html);
        $this->assertStringContainsString('12h00 – 15h00', $html);
        $this->assertStringContainsString('Organisé par', $html);
        $this->assertStringContainsString('Itinéraire', $html);
        $this->assertStringContainsString('Places disponibles', $html);
    }
}
