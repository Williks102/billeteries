<?php

namespace Tests\Feature\Activity;

use App\Models\ActivityLog;
use App\Models\User;
use Tests\Concerns\BuildsOrderSchema;
use Tests\TestCase;

/**
 * Régression : `email_verified_at` n'est pas $fillable ; l'admin qui basculait la vérification d'e-mail ou créait
 * un compte voyait « succès » alors que rien n'était enregistré.
 */
class AdminEmailVerificationTest extends TestCase
{
    use BuildsOrderSchema;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->buildOrderSchema();
        (include base_path('database/migrations/2026_11_13_100000_create_activity_logs_table.php'))->up();
        $this->admin = $this->makeUser(10, 'admin');
        $this->actingAs($this->admin);
    }

    public function test_toggle_really_verifies_then_unverifies_the_email(): void
    {
        $target = $this->makeUser(11, 'promoteur');
        $target->forceFill(['email_verified_at' => null])->save();

        $this->patchJson(route('admin.users.toggle-email', $target))->assertOk()->assertJson(['success' => true]);
        $this->assertNotNull($target->fresh()->email_verified_at);

        $this->patchJson(route('admin.users.toggle-email', $target))->assertOk()->assertJson(['success' => true]);
        $this->assertNull($target->fresh()->email_verified_at);
    }

    public function test_toggle_is_refused_on_own_account(): void
    {
        $this->patchJson(route('admin.users.toggle-email', $this->admin))->assertStatus(422);
    }

    public function test_toggle_is_recorded_in_the_activity_log_without_personal_data(): void
    {
        $target = $this->makeUser(11, 'promoteur');
        $target->forceFill(['email_verified_at' => null])->save();

        $this->patchJson(route('admin.users.toggle-email', $target))->assertOk();

        $log = ActivityLog::where('action', 'user.status_changed')->latest('id')->first();
        $this->assertNotNull($log, 'Le changement de vérification doit être journalisé.');
        $encoded = json_encode([$log->description, $log->properties]);
        $this->assertStringNotContainsString((string) $target->email, $encoded);
    }

    public function test_account_created_by_admin_is_really_verified(): void
    {
        $this->post(route('admin.users.store'), [
            'name' => 'Nouveau Promoteur', 'email' => 'nouveau.promoteur@example.test',
            'password' => 'motdepasse123', 'password_confirmation' => 'motdepasse123',
            'role' => 'promoteur', 'phone' => '0700000000',
        ])->assertRedirect(route('admin.users.index'));

        $created = User::where('email', 'nouveau.promoteur@example.test')->first();
        $this->assertNotNull($created);
        $this->assertNotNull($created->email_verified_at);
    }
}
