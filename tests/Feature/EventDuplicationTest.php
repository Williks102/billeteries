<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\TicketType;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\BuildsEventsTable;
use Tests\Concerns\BuildsOrderSchema;
use Tests\TestCase;

/**
 * Duplication d'un événement (DuplicateEventAction) : copie TOUJOURS en brouillon, sans aucune vente.
 * « Maintenant » figé au jeudi 12/11/2026 12:00 : le prochain samedi est le 14/11/2026.
 */
class EventDuplicationTest extends TestCase
{
    use BuildsEventsTable;
    use BuildsOrderSchema;

    private Event $source;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-11-12 12:00:00');
        Storage::fake('public');
        $this->buildOrderSchema();
        $this->buildEventsTable();

        Storage::disk('public')->put('events/original.jpg', 'contenu-image');
        $this->source = $this->readyEvent(2, [
            'status' => 'published', 'image' => 'events/original.jpg', 'title' => 'Soirée Jazz',
            'terms_conditions' => 'Pas de remboursement', 'end_time' => Carbon::parse('2026-11-20 23:30'),
        ]);
        // Billet vendu en partie + billet inactif
        TicketType::query()->update(['quantity_sold' => 12, 'quantity_available' => 80, 'max_per_order' => 6, 'description' => 'Entrée simple']);
        TicketType::create([
            'event_id' => $this->source->id, 'name' => 'VIP désactivé', 'price' => 20000, 'quantity_available' => 10, 'quantity_sold' => 3,
            'sale_start_date' => now()->subDays(30), 'sale_end_date' => now()->subDays(10), 'max_per_order' => 2, 'is_active' => false,
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function duplicate(?User $as = null, ?Event $event = null)
    {
        return $this->actingAs($as ?? User::find(2))->post(route('promoteur.events.duplicate', $event ?? $this->source));
    }

    private function copy(): Event
    {
        return Event::where('id', '!=', $this->source->id)->firstOrFail();
    }

    // ------------------------------------------------------------------ copie

    public function test_copy_is_a_draft_with_the_same_content_and_opens_in_edit(): void
    {
        $response = $this->duplicate();

        $copy = $this->copy();
        $response->assertRedirect(route('promoteur.events.edit', $copy));
        $response->assertSessionHas('success', 'Brouillon créé : choisissez la nouvelle date.');
        $this->assertSame('draft', $copy->status, 'un événement publié est copié en BROUILLON');
        $this->assertSame('Soirée Jazz (copie)', $copy->title);
        $this->assertSame(2, (int) $copy->promoter_id);
        $this->assertSame(1, (int) $copy->category_id);
        $this->assertSame('Lieu', $copy->venue);
        $this->assertSame('Adresse', $copy->address);
        $this->assertSame('Une description', $copy->description);
        $this->assertSame('Pas de remboursement', $copy->terms_conditions);
        $this->assertSame('promoter', DB::table('events')->where('id', $copy->id)->value('management_mode'));
    }

    public function test_date_is_next_saturday_with_the_same_time(): void
    {
        $this->duplicate();

        $copy = $this->copy();
        $this->assertSame('2026-11-14', $copy->event_date->format('Y-m-d'));
        $this->assertSame('2026-11-14 19:30', $copy->event_time->format('Y-m-d H:i'));
        $this->assertSame('2026-11-14 23:30', $copy->end_time->format('Y-m-d H:i'));
    }

    public function test_tickets_are_copied_without_sales_and_with_a_realigned_sale_period(): void
    {
        $this->duplicate();

        $copy = $this->copy();
        $types = TicketType::where('event_id', $copy->id)->orderBy('id')->get();
        $this->assertCount(2, $types);

        [$std, $vip] = [$types[0], $types[1]];
        $this->assertSame(['Std', 'VIP désactivé'], [$std->name, $vip->name]);
        $this->assertSame([1000, 20000], [(int) $std->price, (int) $vip->price]);
        $this->assertSame([80, 10], [(int) $std->quantity_available, (int) $vip->quantity_available], 'quantité = capacité d\'origine');
        $this->assertSame([6, 2], [(int) $std->max_per_order, (int) $vip->max_per_order]);
        $this->assertSame('Entrée simple', $std->description);
        $this->assertSame([0, 0], [(int) $std->quantity_sold, (int) $vip->quantity_sold]);
        $this->assertTrue($std->is_active);
        $this->assertFalse($vip->is_active, 'un billet inactif reste inactif');
        foreach ($types as $type) {
            $this->assertSame('2026-11-12 12:00', $type->sale_start_date->format('Y-m-d H:i'));
            $this->assertSame('2026-11-14 19:30', $type->sale_end_date->format('Y-m-d H:i'), 'fin de vente = début de la copie');
        }
        // Capacité = somme des billets copiés : rien n'est vendu
        $this->assertSame(0, (int) TicketType::where('event_id', $copy->id)->sum('quantity_sold'));
    }

    public function test_no_order_ticket_commission_or_payment_is_copied(): void
    {
        $type = TicketType::where('event_id', $this->source->id)->first();
        DB::table('orders')->insert(['id' => 10, 'user_id' => 4, 'event_id' => $this->source->id, 'total_amount' => 5000, 'payment_status' => 'paid', 'order_number' => 'ORD-1']);
        DB::table('order_items')->insert(['order_id' => 10, 'ticket_type_id' => $type->id, 'quantity' => 1, 'unit_price' => 5000, 'total_price' => 5000]);
        DB::table('tickets')->insert(['ticket_type_id' => $type->id, 'order_item_id' => 1, 'ticket_code' => 'TCK-1', 'status' => 'sold']);
        DB::table('commissions')->insert(['order_id' => 10, 'promoter_id' => 2, 'gross_amount' => 5000, 'commission_amount' => 500, 'net_amount' => 4500]);
        DB::table('payments')->insert(['order_id' => 10, 'payment_method' => 'manual', 'amount' => 5000]);
        $before = [
            'orders' => DB::table('orders')->count(), 'order_items' => DB::table('order_items')->count(), 'tickets' => DB::table('tickets')->count(),
            'commissions' => DB::table('commissions')->count(), 'payments' => DB::table('payments')->count(),
        ];

        $this->duplicate();

        foreach ($before as $table => $count) {
            $this->assertSame($count, DB::table($table)->count(), "$table inchangé");
        }
        $copy = $this->copy();
        $this->assertSame(0, DB::table('orders')->where('event_id', $copy->id)->count());
        $this->assertSame(0, DB::table('tickets')->whereIn('ticket_type_id', TicketType::where('event_id', $copy->id)->pluck('id'))->count());
    }

    public function test_image_file_is_copied_under_a_new_name_and_survives_deleting_the_original(): void
    {
        $this->duplicate();

        $copy = $this->copy();
        $this->assertNotSame('events/original.jpg', $copy->image);
        $this->assertMatchesRegularExpression('#^events/[A-Za-z0-9]{40}\.jpg$#', $copy->image);
        Storage::disk('public')->assertExists($copy->image);
        $this->assertSame('contenu-image', Storage::disk('public')->get($copy->image));

        Storage::disk('public')->delete('events/original.jpg');
        Storage::disk('public')->assertExists($copy->image);
    }

    public function test_event_without_image_or_with_missing_file_is_copied_without_image(): void
    {
        $this->source->update(['image' => 'events/disparu.jpg']);

        $this->duplicate()->assertSessionHasNoErrors();
        $this->assertNull($this->copy()->image);
    }

    public function test_original_is_not_modified(): void
    {
        $before = [
            'event' => DB::table('events')->where('id', $this->source->id)->first(),
            'types' => DB::table('ticket_types')->where('event_id', $this->source->id)->orderBy('id')->get(),
        ];

        $this->duplicate();

        $this->assertEquals($before['event'], DB::table('events')->where('id', $this->source->id)->first());
        $this->assertEquals($before['types'], DB::table('ticket_types')->where('event_id', $this->source->id)->orderBy('id')->get());
        Storage::disk('public')->assertExists('events/original.jpg');
    }

    public function test_the_copy_is_not_published_by_publishing_rules_until_complete(): void
    {
        $this->duplicate();
        $copy = $this->copy();

        $this->assertTrue($copy->publicationChecklist()['can_publish'], 'copie prête (image copiée, billet valide, date future)');
        $this->assertSame('draft', $copy->status);
    }

    // ------------------------------------------------------------------ accès

    public function test_another_promoter_gets_403_and_nothing_is_created(): void
    {
        $this->duplicate(User::find(3))->assertForbidden();

        $this->assertSame(1, Event::count());
        $this->assertSame(2, TicketType::count());
    }

    public function test_buyer_and_visitor_are_refused(): void
    {
        $this->duplicate(User::find(4))->assertForbidden();
        auth()->logout();
        $this->post(route('promoteur.events.duplicate', $this->source))->assertRedirect(route('login'));
        $this->assertSame(1, Event::count());
    }

    public function test_get_is_not_a_way_to_duplicate(): void
    {
        $this->actingAs(User::find(2))->get('/promoteur/events/'.$this->source->id.'/duplicate')->assertStatus(405);
        $this->assertSame(1, Event::count());
    }

    public function test_duplicate_route_is_registered_as_post_only(): void
    {
        // (Aucune route POST `events/{event}/...` de la ressource ne la masquerait : on vérifie l'enregistrement, pas le masquage.)
        $url = '/promoteur/events/'.$this->source->id.'/duplicate';
        $route = app('router')->getRoutes()->match(\Illuminate\Http\Request::create($url, 'POST'));
        $this->assertSame('promoteur.events.duplicate', $route->getName());
        $this->assertSame(['POST'], $route->methods());

        $this->actingAs(User::find(2))->post($url)->assertRedirect(route('promoteur.events.edit', Event::orderByDesc('id')->first()));
        $this->assertSame(2, Event::count());
    }

    public function test_admin_duplicates_for_any_promoter_who_stays_the_owner(): void
    {
        $this->actingAs(User::find(5))->post(route('admin.events.duplicate', $this->source))
            ->assertRedirect(route('admin.events.edit', $this->copy()));

        $this->assertSame(2, (int) $this->copy()->promoter_id, 'le promoteur d\'origine reste propriétaire');
        $this->assertSame('draft', $this->copy()->status);

        // Et ce promoteur voit la copie chez lui
        $this->actingAs(User::find(2))->get(route('promoteur.events.index'))->assertSee('Soirée Jazz (copie)');
        $this->actingAs(User::find(3))->get(route('promoteur.events.index'))->assertDontSee('Soirée Jazz (copie)');
    }

    public function test_promoter_cannot_use_the_admin_duplicate_route(): void
    {
        $this->actingAs(User::find(2))->post(route('admin.events.duplicate', $this->source))->assertForbidden();
        $this->assertSame(1, Event::count());
    }

    // ------------------------------------------------------------------ boutons

    public function test_duplicate_buttons_are_post_forms_with_csrf(): void
    {
        $index = $this->actingAs(User::find(2))->get(route('promoteur.events.index'))->assertOk()->getContent();
        $show = $this->actingAs(User::find(2))->get(route('promoteur.events.show', $this->source))->assertOk()->getContent();
        $action = route('promoteur.events.duplicate', $this->source);

        foreach ([$index, $show] as $html) {
            $this->assertMatchesRegularExpression('#<form(?=[^>]*method="POST")(?=[^>]*action="'.preg_quote($action, '#').'")[^>]*>\s*<input type="hidden" name="_token"#', $html);
            $this->assertStringContainsString('Dupliquer', $html);
        }
    }

    public function test_admin_pages_have_the_duplicate_button(): void
    {
        $admin = User::find(5);
        $action = route('admin.events.duplicate', $this->source);

        $show = $this->actingAs($admin)->get(route('admin.events.show', $this->source))->assertOk()->getContent();
        $this->assertStringContainsString('action="'.$action.'"', $show);
        $this->assertStringContainsString('Dupliquer', $show);
    }

    public function test_old_buggy_method_is_gone(): void
    {
        $src = file_get_contents(base_path('app/Http/Controllers/Promoteur/EventController.php'));
        $this->assertStringNotContainsString('replicate()', $src);
        $this->assertStringNotContainsString('(Copie)', $src);
        $this->assertStringContainsString('DuplicateEventAction', $src);
    }
}
