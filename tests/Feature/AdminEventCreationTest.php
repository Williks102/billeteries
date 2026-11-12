<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\TicketType;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\BuildsEventsTable;
use Tests\Concerns\BuildsOrderSchema;
use Tests\TestCase;

/**
 * Création d'un événement par l'ADMIN pour un promoteur (StoreEventRequest + CreateEventAction partagés),
 * publication rapide soumise à la checklist, accès fermé aux non-admins.
 * « Maintenant » figé au jeudi 12/11/2026 12:00.
 */
class AdminEventCreationTest extends TestCase
{
    use BuildsEventsTable;
    use BuildsOrderSchema;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-11-12 12:00:00');
        Storage::fake('public');
        $this->buildOrderSchema();
        $this->buildEventsTable();
        $this->admin = User::find(5);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function payload(array $override = []): array
    {
        return array_replace_recursive([
            'promoter_id' => 2,
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
        return $this->actingAs($as ?? $this->admin)->post(route('admin.events.store'), $this->payload($override));
    }

    // ------------------------------------------------------------------ création

    public function test_admin_creates_event_for_promoter_in_one_request_with_two_tickets(): void
    {
        $response = $this->store([
            'ticket_types' => [1 => ['name' => 'VIP', 'price' => '15000', 'quantity_available' => '20', 'max_per_order' => '4']],
            'image' => UploadedFile::fake()->image('cover.jpg'),
        ]);

        $event = Event::firstOrFail();
        $response->assertRedirect(route('admin.events.show', $event));
        $this->assertSame(2, (int) $event->promoter_id, "l'événement appartient au promoteur choisi, pas à l'admin");
        $this->assertSame('draft', $event->status);
        $this->assertSame(['Standard', 'VIP'], TicketType::orderBy('id')->pluck('name')->all());
        $this->assertSame(0, (int) TicketType::sum('quantity_sold'));
        Storage::disk('public')->assertExists($event->image);
    }

    public function test_draft_without_ticket_is_accepted(): void
    {
        $this->store(['ticket_types' => [0 => ['name' => 'Standard', 'price' => '', 'quantity_available' => '100', 'max_per_order' => '10']]])
            ->assertSessionHasNoErrors();

        $this->assertSame(1, Event::count());
        $this->assertSame(0, TicketType::count());
        $this->assertSame('draft', Event::first()->status);
    }

    public function test_publish_incomplete_saves_draft_and_lists_what_is_missing(): void
    {
        $response = $this->store(['action' => 'publish']); // pas d'image

        $event = Event::firstOrFail();
        $this->assertSame('draft', $event->status);
        $response->assertRedirect(route('admin.events.show', $event));
        $warning = (string) session('warning');
        $this->assertStringContainsString("Ajoutez une image de l'événement.", $warning);

        $this->actingAs($this->admin)->get(route('admin.events.show', $event))
            ->assertOk()
            ->assertSee('Avant de publier')
            ->assertSee("Ajoutez une image de l'événement.");
    }

    public function test_publish_complete_publishes(): void
    {
        $this->store(['action' => 'publish', 'image' => UploadedFile::fake()->image('c.jpg')])->assertSessionHasNoErrors();

        $this->assertSame('published', Event::firstOrFail()->status);
    }

    public function test_management_mode_is_the_database_default_and_not_mass_assignable(): void
    {
        $this->store(['management_mode' => 'admin', 'status' => 'published', 'id' => 999]);

        $event = Event::firstOrFail();
        $this->assertSame('promoter', DB::table('events')->where('id', $event->id)->value('management_mode'));
        $this->assertSame('draft', $event->status, 'le statut ne vient jamais de la requête');
        $this->assertNotSame(999, (int) $event->id);
        $this->assertNotContains('management_mode', $event->getFillable());
    }

    // ------------------------------------------------------------------ promoteur choisi

    public function test_promoter_id_must_be_an_existing_promoter(): void
    {
        foreach ([4 => 'acheteur', 5 => 'admin', 999 => 'inexistant', '' => 'absent', 'abc' => 'texte'] as $id => $label) {
            $this->store(['promoter_id' => $id])->assertSessionHasErrors('promoter_id');
        }
        $this->assertSame(0, Event::count());
    }

    public function test_chosen_promoter_sees_the_event_and_the_other_does_not(): void
    {
        $this->store(['title' => 'Chez P']);

        $this->actingAs(User::find(2))->get(route('promoteur.events.index'))->assertOk()->assertSee('Chez P');
        $this->actingAs(User::find(3))->get(route('promoteur.events.index'))->assertOk()->assertDontSee('Chez P');
        $this->actingAs(User::find(3))->get(route('promoteur.events.edit', Event::first()))->assertForbidden();
    }

    public function test_create_form_lists_only_promoters_and_has_both_buttons(): void
    {
        $html = $this->actingAs($this->admin)->get(route('admin.events.create'))->assertOk()->getContent();

        $this->assertStringContainsString('name="promoter_id"', $html);
        $this->assertStringContainsString('promoteur2@example.test', $html);
        $this->assertStringContainsString('promoteur3@example.test', $html);
        $this->assertStringNotContainsString('acheteur4@example.test', $html);
        $this->assertStringNotContainsString('name="management_mode"', $html);
        $this->assertStringContainsString('value="publish"', $html);
        $this->assertStringContainsString('Enregistrer en brouillon', $html);
        $this->assertStringContainsString('name="ticket_types[0][price]"', $html);
        $this->assertStringContainsString('name="_token"', $html);
    }

    // ------------------------------------------------------------------ accès

    public function test_non_admins_are_refused_on_admin_routes(): void
    {
        $event = $this->readyEvent();
        $calls = [
            ['get', route('admin.events.create')],
            ['post', route('admin.events.store')],
            ['post', route('admin.events.quick-publish', $event)],
            ['post', route('admin.events.quick-unpublish', $event)],
            ['post', route('admin.events.duplicate', $event)],
            ['put', route('admin.events.update', $event)],
        ];
        foreach ([2, 4] as $userId) {
            foreach ($calls as [$method, $url]) {
                $this->actingAs(User::find($userId))->{$method}($url, $this->payload())->assertForbidden();
            }
        }
        $this->assertSame(0, Event::where('title', 'Soirée Afrobeat')->count());
        $this->assertSame('draft', $event->fresh()->status);

        auth()->logout();
        foreach ($calls as [$method, $url]) {
            $this->{$method}($url, $this->payload())->assertRedirect(route('login'));
        }
    }

    // ------------------------------------------------------------------ publication admin

    public function test_quick_publish_refused_without_image_lists_the_reasons(): void
    {
        $event = $this->readyEvent(2, ['image' => null]);

        $json = $this->actingAs($this->admin)->postJson(route('admin.events.quick-publish', $event))
            ->assertStatus(422)
            ->assertJson(['success' => false]);
        $this->assertContains("Ajoutez une image de l'événement.", $json->json('reasons'));
        $this->assertSame('draft', $event->fresh()->status);

        $this->actingAs($this->admin)->post(route('admin.events.quick-publish', $event))
            ->assertRedirect()
            ->assertSessionHas('error');
        $this->assertSame('draft', $event->fresh()->status);
    }

    public function test_quick_publish_refused_without_ticket(): void
    {
        $event = $this->readyEvent();
        TicketType::query()->delete();

        $json = $this->actingAs($this->admin)->postJson(route('admin.events.quick-publish', $event))->assertStatus(422);
        $this->assertContains('Ajoutez au moins un billet actif.', $json->json('reasons'));
        $this->assertSame('draft', $event->fresh()->status);
    }

    public function test_quick_publish_and_unpublish_work_when_complete(): void
    {
        $event = $this->readyEvent();

        $this->actingAs($this->admin)->postJson(route('admin.events.quick-publish', $event))->assertOk()->assertJson(['success' => true]);
        $this->assertSame('published', $event->fresh()->status);

        $this->actingAs($this->admin)->postJson(route('admin.events.quick-unpublish', $event))->assertOk();
        $this->assertSame('draft', $event->fresh()->status);
    }

    public function test_status_change_and_bulk_publish_also_go_through_the_checklist(): void
    {
        $incomplete = $this->readyEvent(2, ['image' => null]);

        $this->actingAs($this->admin)->patchJson(route('admin.events.update-status', $incomplete), ['status' => 'published'])->assertStatus(422);
        $this->actingAs($this->admin)->postJson(route('admin.events.bulk-action'), ['action' => 'publish', 'events' => [$incomplete->id]])->assertOk();
        $this->assertSame('draft', $incomplete->fresh()->status);

        $ok = $this->readyEvent();
        $this->actingAs($this->admin)->patchJson(route('admin.events.update-status', $ok), ['status' => 'published'])->assertOk();
        $this->assertSame('published', $ok->fresh()->status);
    }

    // ------------------------------------------------------------------ édition admin

    private function updatePayload(Event $event, array $override = []): array
    {
        return $override + [
            'title' => $event->title, 'category_id' => 1, 'event_date' => $event->event_date->format('Y-m-d'),
            'event_time' => $event->event_time->format('H:i'), 'venue' => $event->venue, 'address' => $event->address,
            'description' => 'Une description', 'promoter_id' => $event->promoter_id, 'status' => $event->status,
        ];
    }

    public function test_admin_update_realigns_sale_periods_and_never_errors(): void
    {
        $event = $this->readyEvent();

        $this->actingAs($this->admin)
            ->put(route('admin.events.update', $event), $this->updatePayload($event, ['event_date' => '2026-11-25']))
            ->assertRedirect(route('admin.events.index'))
            ->assertSessionHas('success');

        $this->assertSame('2026-11-25 19:30', TicketType::first()->sale_end_date->format('Y-m-d H:i'));
        $this->assertSame(0, (int) TicketType::first()->quantity_sold);
    }

    public function test_admin_update_cannot_publish_incomplete_nor_assign_a_non_promoter(): void
    {
        $event = $this->readyEvent(2, ['image' => null]);

        $this->actingAs($this->admin)
            ->put(route('admin.events.update', $event), $this->updatePayload($event, ['status' => 'published']))
            ->assertSessionHas('warning');
        $this->assertSame('draft', $event->fresh()->status);

        $this->actingAs($this->admin)
            ->put(route('admin.events.update', $event), $this->updatePayload($event, ['promoter_id' => 4]))
            ->assertSessionHasErrors('promoter_id');
        $this->assertSame(2, (int) $event->fresh()->promoter_id);

        $this->actingAs($this->admin)
            ->put(route('admin.events.update', $event), $this->updatePayload($event, ['status' => 'rejected']))
            ->assertSessionHasErrors('status');
    }

    public function test_admin_can_reassign_to_another_promoter(): void
    {
        $event = $this->readyEvent();

        $this->actingAs($this->admin)
            ->put(route('admin.events.update', $event), $this->updatePayload($event, ['promoter_id' => 3]))
            ->assertSessionHasNoErrors();
        $this->assertSame(3, (int) $event->fresh()->promoter_id);
    }

    public function test_no_request_all_and_no_log_action_in_admin_event_code(): void
    {
        foreach ([
            'app/Http/Controllers/Admin/EventController.php',
            'app/Http/Requests/AdminStoreEventRequest.php',
            'app/Http/Requests/AdminUpdateEventRequest.php',
            'app/Actions/Events/DuplicateEventAction.php',
        ] as $file) {
            $src = file_get_contents(base_path($file));
            $this->assertStringNotContainsString('$request->all()', $src, $file);
            $this->assertStringNotContainsString('logAction', $src, $file);
        }
    }

    // ------------------------------------------------------------------ statuts et actions groupées

    public function test_update_status_only_accepts_draft_published_cancelled(): void
    {
        $event = $this->readyEvent();

        foreach (['rejected', 'pending', 'finished', 'nimporte'] as $bad) {
            $this->actingAs($this->admin)->patchJson(route('admin.events.update-status', $event), ['status' => $bad])->assertStatus(422);
            $this->assertSame('draft', $event->fresh()->status, $bad);
        }
        $this->actingAs($this->admin)->patchJson(route('admin.events.update-status', $event), ['status' => 'cancelled'])->assertOk();
        $this->assertSame('cancelled', $event->fresh()->status);
    }

    public function test_bulk_action_refuses_reject_and_take_control_and_reports_refused_events(): void
    {
        $event = $this->readyEvent();
        foreach (['reject', 'take_control'] as $bad) {
            $this->actingAs($this->admin)->postJson(route('admin.events.bulk-action'), ['action' => $bad, 'events' => [$event->id]])->assertStatus(422);
        }
        $this->assertSame('draft', $event->fresh()->status);

        $incomplete = [];
        for ($i = 1; $i <= 7; $i++) {
            $incomplete[] = $this->readyEvent(2, ['image' => null, 'title' => "Incomplet $i"])->id;
        }
        $json = $this->actingAs($this->admin)->postJson(route('admin.events.bulk-action'), ['action' => 'publish', 'events' => array_merge([$event->id], $incomplete)])->assertOk();
        $this->assertSame('published', $event->fresh()->status);
        $this->assertStringContainsString('1 événement(s) traité(s)', $json->json('message'));
        $this->assertStringContainsString("Incomplet 1 : Ajoutez une image de l'événement.", $json->json('message'));
        $this->assertStringContainsString('et 2 autre(s)', $json->json('message'));
        $this->assertCount(7, $json->json('refused'));
    }

    public function test_restore_promoter_control_route_and_dead_code_are_gone(): void
    {
        $event = $this->readyEvent();
        $this->assertFalse(\Illuminate\Support\Facades\Route::has('admin.events.restore-promoter-control'));
        $this->actingAs($this->admin)->post('/admin/events/'.$event->id.'/restore-promoter-control', ['reason' => 'x'])->assertNotFound();

        $src = file_get_contents(base_path('app/Http/Controllers/Admin/EventController.php'));
        foreach (['restorePromoterMode', 'enableAdminMode', 'take_control', 'hybridStats', "'rejected'"] as $dead) {
            $this->assertStringNotContainsString($dead, $src, $dead);
        }
    }

    public function test_admin_list_no_longer_offers_reject_take_control_or_pending_statuses(): void
    {
        $this->readyEvent();
        $html = $this->actingAs($this->admin)->get(route('admin.events.index'))->assertOk()->getContent();

        foreach (['Rejeter', 'take_control', 'value="rejected"', 'value="pending"', 'En attente de validation'] as $gone) {
            $this->assertStringNotContainsString($gone, $html, $gone);
        }
        $this->assertStringContainsString('value="publish"', $html);
    }
}
