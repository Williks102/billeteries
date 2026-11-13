<?php

namespace Tests\Feature\Activity;

use App\Models\ActivityLog;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\BuildsOrderSchema;
use Tests\TestCase;

/** Écran admin « Journal d'activité » : accès, filtres, affichage propre, lecture seule. */
class ActivityLogScreenTest extends TestCase
{
    use BuildsOrderSchema;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->buildOrderSchema();
        (include base_path('database/migrations/2026_11_13_100000_create_activity_logs_table.php'))->up();

        $this->admin = $this->makeUser(10, 'admin');
        $this->makeUser(2, 'promoteur');
    }

    private function seedLog(string $action, string $description, ?int $userId, string $createdAt, ?string $role = 'admin'): ActivityLog
    {
        return ActivityLog::create([
            'user_id' => $userId, 'actor_role' => $role, 'action' => $action, 'subject_type' => 'Event', 'subject_id' => 1,
            'event_id' => 1, 'description' => $description, 'properties' => ['status' => ['from' => 'draft', 'to' => 'published']],
            'created_at' => $createdAt,
        ]);
    }

    public function test_admin_sees_the_journal_and_others_are_refused(): void
    {
        $this->seedLog(ActivityLog::EVENT_PUBLISHED, 'Événement n°1 publié', 10, now()->toDateTimeString());

        $this->actingAs($this->admin)->get(route('admin.activity.index'))
            ->assertOk()
            ->assertSee('Journal d\'activité')
            ->assertSee('Événement publié')
            ->assertSee('Événement n°1 publié')
            ->assertSee('status : draft → published')
            ->assertSee(route('admin.events.show', 1), false);

        auth()->logout();
        $this->actingAs($this->makeUser(3, 'acheteur'))->get(route('admin.activity.index'))->assertForbidden();
        $this->actingAs(User::find(2))->get(route('admin.activity.index'))->assertForbidden();
        auth()->logout();
        $this->get(route('admin.activity.index'))->assertRedirect(route('login'));
    }

    public function test_filters_by_family_actor_and_period(): void
    {
        $this->seedLog(ActivityLog::PAYMENT_CONFIRMED, 'PAIEMENT-RECENT', 10, now()->toDateTimeString());
        $this->seedLog(ActivityLog::EVENT_PUBLISHED, 'EVENEMENT-SYSTEME', null, now()->toDateTimeString(), 'system');
        $this->seedLog(ActivityLog::TICKET_TYPE_UPDATED, 'BILLET-ANCIEN', 2, now()->subMonths(3)->toDateTimeString(), 'promoteur');
        $this->actingAs($this->admin);

        // défaut : mois civil => l'ancien est exclu
        $this->get(route('admin.activity.index'))->assertSee('PAIEMENT-RECENT')->assertSee('EVENEMENT-SYSTEME')->assertDontSee('BILLET-ANCIEN');

        $this->get(route('admin.activity.index', ['family' => 'payment']))->assertSee('PAIEMENT-RECENT')->assertDontSee('EVENEMENT-SYSTEME');
        $this->get(route('admin.activity.index', ['actor' => 'system']))->assertSee('EVENEMENT-SYSTEME')->assertDontSee('PAIEMENT-RECENT');
        $this->get(route('admin.activity.index', ['actor' => '10']))->assertSee('PAIEMENT-RECENT')->assertDontSee('EVENEMENT-SYSTEME');
        $this->get(route('admin.activity.index', ['period' => 'all', 'family' => 'ticket']))->assertSee('BILLET-ANCIEN')->assertDontSee('PAIEMENT-RECENT');
        $this->get(route('admin.activity.index', ['family' => 'inconnue']))->assertOk()->assertSee('PAIEMENT-RECENT');
    }

    public function test_invalid_period_is_a_validation_error_not_a_500(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.activity.index', ['period' => 'n-importe-quoi']))
            ->assertRedirect(route('admin.activity.index'));
    }

    public function test_pagination_is_25_per_page_newest_first_and_no_raw_translation_keys(): void
    {
        foreach (range(1, 30) as $i) {
            $this->seedLog(ActivityLog::SETTINGS_UPDATED, 'ENTREE-'.str_pad((string) $i, 2, '0', STR_PAD_LEFT), 10, now()->subMinutes(40 - $i)->toDateTimeString());
        }

        $html = $this->actingAs($this->admin)->get(route('admin.activity.index'))->assertOk()->getContent();

        $this->assertSame(25, substr_count($html, 'class="activity-entry"'));
        $this->assertStringContainsString('ENTREE-30', $html);
        $this->assertStringNotContainsString('ENTREE-05', $html);
        $this->assertDoesNotMatchRegularExpression('/\b(validation|activity)\.[a-z_]+/', strip_tags($html));
        $this->assertStringNotContainsString('<script>', $this->journalFragment($html));
    }

    public function test_no_write_route_exists_for_the_journal(): void
    {
        $routes = collect(app('router')->getRoutes()->getRoutes())->filter(fn ($r) => str_contains($r->uri(), 'activity'));
        $this->assertCount(1, $routes);
        $this->assertSame(['GET', 'HEAD'], $routes->first()->methods());
        $this->actingAs($this->admin)->delete('/admin/activity/1')->assertNotFound();
    }

    public function test_screen_survives_a_deleted_subject_and_deleted_actor(): void
    {
        $this->seedLog(ActivityLog::EVENT_DELETED, 'SUJET-DISPARU', 2, now()->toDateTimeString(), 'promoteur');
        DB::table('events')->delete();
        DB::table('users')->where('id', 2)->delete();

        $html = $this->actingAs($this->admin)->get(route('admin.activity.index'))->assertOk()->getContent();
        $this->assertStringContainsString('SUJET-DISPARU', $html);
        $this->assertStringNotContainsString('Voir l\'élément concerné', $html);
    }

    private function journalFragment(string $html): string
    {
        $start = strpos($html, 'activity-filters');

        return $start === false ? '' : substr($html, $start);
    }
}
