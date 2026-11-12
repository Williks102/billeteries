<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\TicketType;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\BuildsOrderSchema;
use Tests\TestCase;

/**
 * Création d'événement simplifiée (promoteur) : formulaire unique, FormRequest, CreateEventAction,
 * checklist de publication (Event::publicationChecklist, PublishEventAction).
 * « Maintenant » est figé au jeudi 12/11/2026 12:00 : le prochain samedi est le 14/11/2026.
 */
class EventCreationTest extends TestCase
{
    use BuildsOrderSchema;

    private User $promoter;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-11-12 12:00:00');
        Storage::fake('public');
        $this->buildOrderSchema();

        // Table events complète (le schéma partagé est minimal)
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
            $t->string('image')->nullable();
            $t->text('terms_conditions')->nullable();
            $t->timestamps();
        });
        DB::table('event_categories')->insert(['id' => 1, 'name' => 'Concert', 'slug' => 'concert']);

        $this->promoter = $this->makeUser(2, 'promoteur');
        $this->makeUser(3, 'promoteur');
        $this->makeUser(4, 'acheteur');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function payload(array $override = []): array
    {
        return array_replace_recursive([
            'title' => 'Soirée Afrobeat',
            'category_id' => 1,
            'event_date' => '2026-11-14',
            'event_time' => '20:00',
            'venue' => 'Palais de la Culture',
            'action' => 'draft',
            'ticket_types' => [
                0 => ['name' => 'Standard', 'price' => '5000', 'quantity_available' => '100', 'max_per_order' => '10'],
            ],
        ], $override);
    }

    private function store(array $override = [], ?User $as = null)
    {
        return $this->actingAs($as ?? $this->promoter)->post(route('promoteur.events.store'), $this->payload($override));
    }

    /** Événement publiable : billet valide, date future, image. */
    private function readyEvent(int $promoterId = 2): Event
    {
        $event = Event::create([
            'promoter_id' => $promoterId, 'category_id' => 1, 'title' => 'Prêt', 'description' => 'Une description',
            'venue' => 'Lieu', 'address' => 'Lieu', 'event_date' => '2026-11-20', 'event_time' => Carbon::parse('2026-11-20 20:00'),
            'status' => 'draft', 'image' => 'events/x.jpg',
        ]);
        TicketType::create([
            'event_id' => $event->id, 'name' => 'Std', 'price' => 1000, 'quantity_available' => 50, 'quantity_sold' => 0,
            'sale_start_date' => now(), 'sale_end_date' => Carbon::parse('2026-11-20 20:00'), 'max_per_order' => 10, 'is_active' => true,
        ]);

        return $event;
    }

    // ------------------------------------------------------------------ création

    public function test_full_creation_in_one_request_event_plus_two_tickets(): void
    {
        $response = $this->store([
            'ticket_types' => [1 => ['name' => 'VIP', 'price' => '15000', 'quantity_available' => '20', 'max_per_order' => '4']],
            'image' => UploadedFile::fake()->image('cover.jpg'),
            'description' => 'Une grande soirée',
        ]);

        $event = Event::firstOrFail();
        $response->assertRedirect(route('promoteur.events.show', $event));
        $this->assertSame('draft', $event->status);
        $this->assertSame(2, (int) $event->promoter_id);
        $this->assertSame('Palais de la Culture', $event->address, 'adresse = lieu si vide');
        $this->assertSame('2026-11-14 20:00', $event->event_time->format('Y-m-d H:i'));
        $this->assertSame(2, TicketType::count());
        $this->assertSame(['Standard', 'VIP'], TicketType::orderBy('id')->pluck('name')->all());
        $this->assertSame(15000, (int) TicketType::where('name', 'VIP')->value('price'));
        Storage::disk('public')->assertExists($event->image);
    }

    public function test_no_tickets_rows_are_created(): void
    {
        $this->store();

        $this->assertSame(1, TicketType::count());
        $this->assertSame(0, DB::table('tickets')->count(), 'aucune pré-génération des lignes tickets');
    }

    public function test_sale_end_defaults_to_the_event_start_and_sale_start_to_now(): void
    {
        $this->store();

        $type = TicketType::firstOrFail();
        $this->assertSame('2026-11-14 20:00:00', $type->sale_end_date->format('Y-m-d H:i:s'));
        $this->assertSame('2026-11-12 12:00:00', $type->sale_start_date->format('Y-m-d H:i:s'));
        $this->assertSame(10, (int) $type->max_per_order);
    }

    public function test_explicit_sale_end_on_event_day_is_capped_at_event_start(): void
    {
        $this->store(['ticket_types' => [0 => ['sale_end_date' => '2026-11-14']]]);

        $this->assertSame('2026-11-14 20:00:00', TicketType::firstOrFail()->sale_end_date->format('Y-m-d H:i:s'));
    }

    public function test_draft_without_any_ticket_is_saved(): void
    {
        // Le formulaire envoie toujours le bloc « Standard » intact (sans prix) : ce n'est pas un billet.
        $this->store(['ticket_types' => [0 => ['price' => '']]])->assertSessionHasNoErrors();

        $this->assertSame(1, Event::count());
        $this->assertSame(0, TicketType::count());
        $this->assertSame('draft', Event::first()->status);

        // Et sans aucun champ de billet
        Event::query()->delete();
        $data = $this->payload();
        unset($data['ticket_types']);
        $this->actingAs($this->promoter)->post(route('promoteur.events.store'), $data)->assertSessionHasNoErrors();
        $this->assertSame(1, Event::count());
    }

    public function test_publish_requested_with_missing_items_saves_a_draft_and_lists_what_is_missing(): void
    {
        $response = $this->store(['action' => 'publish']); // pas d'image

        $event = Event::firstOrFail();
        $this->assertSame('draft', $event->status);
        $response->assertRedirect(route('promoteur.events.show', $event));
        $response->assertSessionHas('warning', fn ($m) => str_contains($m, "Ajoutez une image de l'événement.") && str_contains($m, 'brouillon'));
    }

    public function test_publish_requested_with_everything_ok_publishes(): void
    {
        $this->store(['action' => 'publish', 'image' => UploadedFile::fake()->image('c.png')]);

        $this->assertSame('published', Event::firstOrFail()->status);
    }

    public function test_address_defaults_to_venue_and_description_to_empty_string(): void
    {
        $this->store();

        $event = Event::firstOrFail();
        $this->assertSame('', $event->description);
        $this->assertSame($event->venue, $event->address);
    }

    public function test_promoter_id_in_the_request_is_ignored(): void
    {
        $this->store(['promoter_id' => 3, 'status' => 'published', 'id' => 99]);

        $event = Event::firstOrFail();
        $this->assertSame(2, (int) $event->promoter_id);
        $this->assertSame('draft', $event->status);
    }

    // ------------------------------------------------------------------ validations

    public static function invalidPayloads(): array
    {
        return [
            'date passée' => [['event_date' => '2026-11-11'], 'event_date'],
            'prix négatif' => [['ticket_types' => [0 => ['price' => '-1']]], 'ticket_types.0.price'],
            'prix décimal' => [['ticket_types' => [0 => ['price' => '10.5']]], 'ticket_types.0.price'],
            'quantité 0' => [['ticket_types' => [0 => ['quantity_available' => '0']]], 'ticket_types.0.quantity_available'],
            'catégorie inexistante' => [['category_id' => 999], 'category_id'],
            'max > quantité' => [['ticket_types' => [0 => ['quantity_available' => '5', 'max_per_order' => '6']]], 'ticket_types.0.max_per_order'],
            'fin de vente avant le début' => [['ticket_types' => [0 => ['sale_start_date' => '2026-11-13', 'sale_end_date' => '2026-11-12']]], 'ticket_types.0.sale_end_date'],
            'fin de vente après l\'événement' => [['ticket_types' => [0 => ['sale_end_date' => '2026-11-15']]], 'ticket_types.0.sale_end_date'],
            'heure de fin avant le début' => [['end_time' => '19:00'], 'end_time'],
            'titre manquant' => [['title' => ''], 'title'],
            'lieu manquant' => [['venue' => ''], 'venue'],
            'nom de billet manquant avec un prix' => [['ticket_types' => [0 => ['name' => '']]], 'ticket_types.0.name'],
        ];
    }

    /** @dataProvider invalidPayloads */
    public function test_invalid_input_is_rejected_and_nothing_is_saved(array $override, string $errorKey): void
    {
        $this->store($override)->assertSessionHasErrors($errorKey);

        $this->assertSame(0, Event::count());
        $this->assertSame(0, TicketType::count());
    }

    public function test_error_messages_are_in_french(): void
    {
        $response = $this->store(['event_date' => '2026-11-11', 'ticket_types' => [0 => ['price' => '-1']]]);

        $this->assertSame("La date de l'événement ne peut pas être dans le passé.", session('errors')->first('event_date'));
        $this->assertSame('Le prix ne peut pas être négatif.', session('errors')->first('ticket_types.0.price'));
    }

    public function test_old_input_is_kept_after_a_validation_error(): void
    {
        $create = route('promoteur.events.create');
        $this->actingAs($this->promoter)->from($create)
            ->post(route('promoteur.events.store'), $this->payload(['title' => 'Mon titre conservé', 'category_id' => 999, 'ticket_types' => [0 => ['price' => '7000']]]))
            ->assertRedirect($create);

        $html = $this->actingAs($this->promoter)->get($create)->assertOk()->getContent();
        $this->assertStringContainsString('value="Mon titre conservé"', $html);
        $this->assertStringContainsString('value="7000"', $html);
        $this->assertStringContainsString(e("Cette catégorie n'existe pas."), $html);
    }

    // ------------------------------------------------------------------ accès

    public function test_other_promoter_gets_403_on_edit_update_publish(): void
    {
        $event = $this->readyEvent(2);
        $other = User::find(3);

        $this->actingAs($other)->get(route('promoteur.events.edit', $event))->assertForbidden();
        $this->actingAs($other)->put(route('promoteur.events.update', $event), [
            'title' => 'Piraté', 'category_id' => 1, 'event_date' => '2026-11-20', 'event_time' => '20:00', 'venue' => 'X', 'address' => 'X',
        ])->assertForbidden();
        $this->actingAs($other)->post(route('promoteur.events.publish', $event))->assertForbidden();

        $this->assertSame('Prêt', $event->fresh()->title);
        $this->assertSame('draft', $event->fresh()->status);
    }

    public function test_buyer_and_visitor_are_refused(): void
    {
        $this->actingAs(User::find(4))->get(route('promoteur.events.create'))->assertForbidden();
        $this->actingAs(User::find(4))->post(route('promoteur.events.store'), $this->payload())->assertForbidden();
        auth()->logout();
        $this->get(route('promoteur.events.create'))->assertRedirect(route('login'));
        $this->post(route('promoteur.events.store'), $this->payload())->assertRedirect(route('login'));
        $this->assertSame(0, Event::count());
    }

    // ------------------------------------------------------------------ checklist et publication

    public function test_checklist_is_empty_when_everything_is_fine(): void
    {
        $checklist = $this->readyEvent()->publicationChecklist();

        $this->assertSame([], $checklist['blocking']);
        $this->assertTrue($checklist['can_publish']);
        $this->assertSame(['single_ticket'], array_keys($checklist['warnings']));
    }

    public static function checklistCases(): array
    {
        return [
            'sans billet' => ['no_ticket', ['tickets']],
            'date passée' => ['past_date', ['date', 'sale_period']],
            'fin de vente après le début' => ['sale_after_event', ['sale_period']],
            'sans image' => ['no_image', ['image']],
            'période de vente close' => ['sale_closed', ['sale_period']],
            'billet inactif seulement' => ['inactive', ['tickets']],
            'début de vente après la fin' => ['start_after_end', ['sale_period']],
        ];
    }

    /** @dataProvider checklistCases */
    public function test_publication_is_refused_with_the_exact_list_of_blockers(string $case, array $expectedCodes): void
    {
        $event = $this->readyEvent();
        $type = $event->ticketTypes()->first();
        match ($case) {
            'no_ticket' => $type->delete(),
            'past_date' => $event->update(['event_date' => '2026-11-10', 'event_time' => Carbon::parse('2026-11-10 20:00')]),
            'sale_after_event' => $type->update(['sale_end_date' => Carbon::parse('2026-11-21 10:00')]),
            'no_image' => $event->update(['image' => null]),
            'sale_closed' => $type->update(['sale_start_date' => '2026-11-01 00:00:00', 'sale_end_date' => '2026-11-10 00:00:00']),
            'inactive' => $type->update(['is_active' => false]),
            'start_after_end' => $type->update(['sale_start_date' => '2026-11-19 00:00:00', 'sale_end_date' => '2026-11-18 00:00:00']),
        };

        $checklist = $event->fresh()->publicationChecklist();
        $this->assertSame($expectedCodes, array_keys($checklist['blocking']));
        $this->assertFalse($checklist['can_publish']);

        $response = $this->actingAs($this->promoter)->post(route('promoteur.events.publish', $event));
        $response->assertSessionHas('error', fn ($m) => str_starts_with($m, 'Publication impossible : ')
            && collect($checklist['blocking'])->every(fn ($msg) => str_contains($m, $msg)));
        $this->assertSame('draft', $event->fresh()->status, 'jamais publié malgré une checklist non satisfaite');
    }

    public function test_publication_is_accepted_when_everything_is_fine(): void
    {
        $event = $this->readyEvent();

        $this->actingAs($this->promoter)->post(route('promoteur.events.publish', $event))->assertSessionHas('success');

        $this->assertSame('published', $event->fresh()->status);
    }

    public function test_publish_is_idempotent_and_unpublish_unchanged(): void
    {
        $event = $this->readyEvent();
        $this->actingAs($this->promoter)->post(route('promoteur.events.publish', $event));
        $this->actingAs($this->promoter)->post(route('promoteur.events.publish', $event))->assertSessionHas('success');
        $this->assertSame('published', $event->fresh()->status);

        $this->actingAs($this->promoter)->post(route('promoteur.events.unpublish', $event))->assertSessionHas('success');
        $this->assertSame('draft', $event->fresh()->status);
    }

    public function test_show_lists_blockers_and_disables_the_publish_button(): void
    {
        $this->store(); // brouillon sans image
        $event = Event::firstOrFail();

        $html = $this->actingAs($this->promoter)->get(route('promoteur.events.show', $event))->assertOk()->getContent();

        $this->assertStringContainsString(e("Ajoutez une image de l'événement."), $html);
        $this->assertMatchesRegularExpression('/<button[^>]*data-publish-button[^>]*disabled/', $html);
    }

    public function test_show_enables_publish_when_ready(): void
    {
        $event = $this->readyEvent();

        $html = $this->actingAs($this->promoter)->get(route('promoteur.events.show', $event))->assertOk()->getContent();

        $this->assertStringContainsString('Tout est prêt', $html);
        $this->assertDoesNotMatchRegularExpression('/<button[^>]*data-publish-button[^>]*disabled/', $html);
    }

    // ------------------------------------------------------------------ mise à jour

    private function updatePayload(array $override = []): array
    {
        return $override + [
            'title' => 'Titre modifié', 'category_id' => 1, 'event_date' => '2026-11-20', 'event_time' => '21:00',
            'venue' => 'Lieu', 'address' => 'Lieu', 'description' => 'D',
        ];
    }

    public function test_update_works_and_keeps_terms_not_sent_by_the_edit_form(): void
    {
        $event = $this->readyEvent();
        $event->update(['terms_conditions' => 'Pas de chiens']);

        $this->actingAs($this->promoter)->put(route('promoteur.events.update', $event), $this->updatePayload())
            ->assertRedirect(route('promoteur.events.show', $event));

        $fresh = $event->fresh();
        $this->assertSame('Titre modifié', $fresh->title);
        $this->assertSame('21:00', $fresh->event_time->format('H:i'));
        $this->assertSame('Pas de chiens', $fresh->terms_conditions);
        $this->assertSame(2, (int) $fresh->promoter_id);
    }

    public function test_update_rejects_a_new_past_date_but_not_an_unchanged_past_event(): void
    {
        $event = $this->readyEvent();
        $this->actingAs($this->promoter)->put(route('promoteur.events.update', $event), $this->updatePayload(['event_date' => '2026-11-01']))
            ->assertSessionHasErrors('event_date');

        $event->update(['event_date' => '2026-11-01', 'event_time' => Carbon::parse('2026-11-01 20:00')]);
        $this->actingAs($this->promoter)->put(route('promoteur.events.update', $event), $this->updatePayload(['event_date' => '2026-11-01']))
            ->assertSessionHasNoErrors();
        $this->assertSame('Titre modifié', $event->fresh()->title);
    }

    // ------------------------------------------------------------------ billets existants (page #16)

    public function test_ticket_update_keeps_minimum_quantity_and_caps_sale_end_at_event_start(): void
    {
        $event = $this->readyEvent();
        $type = $event->ticketTypes()->first();
        $type->update(['quantity_sold' => 30]);
        $url = route('promoteur.events.tickets.update', [$event, $type]);
        $data = ['name' => 'Std', 'price' => 1000, 'quantity_available' => 20, 'sale_start_date' => '2026-11-12', 'sale_end_date' => '2026-11-20', 'max_per_order' => 5];

        $this->actingAs($this->promoter)->put($url, $data)->assertSessionHasErrors('quantity_available');

        $this->actingAs($this->promoter)->put($url, ['quantity_available' => 30] + $data)->assertSessionHasNoErrors();
        $this->assertSame('2026-11-20 20:00:00', $type->fresh()->sale_end_date->format('Y-m-d H:i:s'));

        $this->actingAs($this->promoter)->put($url, ['sale_end_date' => '2026-11-21', 'quantity_available' => 30] + $data)->assertSessionHasErrors('sale_end_date');
        $this->actingAs($this->promoter)->put($url, ['max_per_order' => 31, 'quantity_available' => 30] + $data)->assertSessionHasErrors('max_per_order');
    }

    public function test_ticket_store_on_existing_event_creates_no_ticket_rows(): void
    {
        $event = $this->readyEvent();

        $this->actingAs($this->promoter)->post(route('promoteur.events.tickets.store', $event), ['ticket_types' => [
            0 => ['name' => 'VIP', 'price' => 9000, 'quantity_available' => 10, 'max_per_order' => 2],
        ]])->assertSessionHasNoErrors();

        $vip = TicketType::where('name', 'VIP')->firstOrFail();
        $this->assertSame('2026-11-20 20:00:00', $vip->sale_end_date->format('Y-m-d H:i:s'));
        $this->assertSame(0, DB::table('tickets')->count());
    }

    // ------------------------------------------------------------------ formulaire

    public function test_form_defaults_next_saturday_20h_and_sale_end_note(): void
    {
        $html = $this->actingAs($this->promoter)->get(route('promoteur.events.create'))->assertOk()->getContent();

        $this->assertMatchesRegularExpression('/name="event_date" value="2026-11-14"/', $html);
        $this->assertMatchesRegularExpression('/name="event_time" value="20:00"/', $html);
        $this->assertStringContainsString("vide : début de l'événement", $html);
        $this->assertMatchesRegularExpression('/name="ticket_types\[0\]\[name\]"[^>]*value="Standard"/', $html);
        $this->assertMatchesRegularExpression('/name="ticket_types\[0\]\[quantity_available\]"[^>]*value="100"/s', $html);
    }

    public function test_form_contains_expected_fields_and_buttons(): void
    {
        $html = $this->actingAs($this->promoter)->get(route('promoteur.events.create'))->assertOk()->getContent();

        $this->assertSame(1, substr_count($html, '<html'));
        foreach (['title', 'category_id', 'event_date', 'event_time', 'venue', 'description', 'address', 'end_time', 'terms_conditions', 'image'] as $name) {
            $this->assertStringContainsString('name="'.$name.'"', $html, $name);
        }
        foreach (['name', 'price', 'quantity_available', 'max_per_order', 'sale_start_date', 'sale_end_date', 'description'] as $name) {
            $this->assertStringContainsString('name="ticket_types[0]['.$name.']"', $html, $name);
        }
        $this->assertStringContainsString('name="action" value="draft"', $html);
        $this->assertStringContainsString('name="action" value="publish"', $html);
        $this->assertStringContainsString('Enregistrer en brouillon', $html);
        $this->assertStringContainsString('Options avancées', $html);
        $this->assertStringContainsString('action-bar--sticky', $html);
        $this->assertStringContainsString('action="'.route('promoteur.events.store').'"', $html);
        $this->assertStringContainsString('enctype="multipart/form-data"', $html);
        $this->assertStringNotContainsString('alert(', $html);
        $this->assertStringNotContainsString('promoteur-events-create.js', $html);
        $this->assertFileDoesNotExist(public_path('js/promoteur-events-create.js'));
        foreach (['Standard', 'VIP', 'Gratuit', 'Ajouter un billet'] as $label) {
            $this->assertStringContainsString($label, $html);
        }
    }

    // ------------------------------------------------------------------ journaux

    public function test_no_request_dump_in_logs_or_mass_assignment_of_the_request(): void
    {
        $files = [
            'app/Http/Controllers/Promoteur/EventController.php',
            'app/Http/Controllers/Promoteur/TicketTypeController.php',
            'app/Actions/Events/CreateEventAction.php',
            'app/Actions/Events/PublishEventAction.php',
            'app/Http/Requests/StoreEventRequest.php',
            'app/Http/Requests/UpdateEventRequest.php',
            'app/Http/Requests/EventFormRequest.php',
        ];
        foreach ($files as $file) {
            $src = file_get_contents(base_path($file));
            $this->assertStringNotContainsString('$request->all()', $src, $file);
            $this->assertStringNotContainsString('DEBUG TICKET CREATION', $src, $file);
            $this->assertStringNotContainsString('Données reçues', $src, $file);
            $this->assertDoesNotMatchRegularExpression('/Log::\w+\([^;]*\$request/', $src, "$file : journal contenant la requête");
        }
        $this->assertStringNotContainsString('generateTicketsForType', file_get_contents(base_path($files[1])));
        $this->assertStringNotContainsString('Ticket::', file_get_contents(base_path('app/Actions/Events/CreateEventAction.php')));
    }

    public function test_promoter_id_is_never_read_from_the_request_in_the_source(): void
    {
        foreach (['app/Http/Controllers/Promoteur/EventController.php', 'app/Http/Requests/StoreEventRequest.php', 'app/Http/Requests/EventFormRequest.php'] as $file) {
            $src = file_get_contents(base_path($file));
            $this->assertDoesNotMatchRegularExpression('/(request->|input\(|validated\(\)\[)[\'"]?promoter_id/', $src, $file);
        }
    }
}
