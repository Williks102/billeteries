<?php

namespace Tests\Feature\Security;

use App\Listeners\LogAuthFailure;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\Concerns\BuildsOrderSchema;
use Tests\TestCase;

/** Journal des échecs de connexion : sans donnée personnelle, plafonné contre l'inondation, sans effet sur la connexion. */
class LoginFailureLogTest extends TestCase
{
    use BuildsOrderSchema;

    protected function setUp(): void
    {
        parent::setUp();
        $this->buildOrderSchema();
        (include base_path('database/migrations/2026_11_13_100000_create_activity_logs_table.php'))->up();
        $this->makeUser(3, 'acheteur');
        DB::table('users')->where('id', 3)->update(['password' => Hash::make('Bon-mot-de-passe-1')]);
    }

    private function attempt(string $email, string $password, string $ip = '10.0.0.1')
    {
        return $this->withServerVariables(['REMOTE_ADDR' => $ip])->from('/login')->post('/login', ['email' => $email, 'password' => $password]);
    }

    public function test_failed_login_is_logged_without_personal_data(): void
    {
        $this->attempt('acheteur3@example.test', 'Mauvais-secret-42')->assertRedirect('/login');

        $row = ActivityLog::where('action', ActivityLog::AUTH_LOGIN_FAILED)->firstOrFail();
        $this->assertNull($row->user_id);
        $this->assertSame('mot_de_passe_incorrect', $row->properties['reason']);
        $this->assertSame(64, strlen($row->properties['identifier_hash']));
        $this->assertSame(64, strlen((string) $row->ip_fingerprint));

        $raw = json_encode(DB::table('activity_logs')->get());
        foreach (['acheteur3', 'example.test', 'Mauvais-secret-42', '10.0.0.1'] as $forbidden) {
            $this->assertStringNotContainsString($forbidden, $raw);
        }

        $this->attempt('inconnu@example.test', 'x-y-z-123', '10.0.0.2')->assertRedirect('/login');
        $this->assertSame('compte_inconnu', ActivityLog::where('action', ActivityLog::AUTH_LOGIN_FAILED)->latest('id')->first()->properties['reason']);
    }

    public function test_flood_is_capped(): void
    {
        $this->travelTo(now()->startOfMinute()->addSeconds(5)); // fenêtre d'une minute figée : test déterministe
        // même identifiant, IP différentes : une seule ligne par minute
        for ($i = 1; $i <= 4; $i++) {
            $this->attempt('cible@example.test', 'faux', "10.1.0.$i");
        }
        $this->assertSame(1, ActivityLog::where('action', ActivityLog::AUTH_LOGIN_FAILED)->count());

        // même IP, identifiants différents : une seule ligne de plus
        for ($i = 1; $i <= 6; $i++) {
            $this->attempt("autre{$i}@example.test", 'faux', '10.2.0.1');
        }
        $this->assertSame(2, ActivityLog::where('action', ActivityLog::AUTH_LOGIN_FAILED)->count());

        // identifiants ET IP tous différents : plafond global par minute
        for ($i = 1; $i <= LogAuthFailure::GLOBAL_PER_MINUTE + 20; $i++) {
            $this->attempt("rot{$i}@example.test", 'faux', '10.3.'.intdiv($i, 250).'.'.($i % 250));
        }
        $this->assertLessThanOrEqual(LogAuthFailure::GLOBAL_PER_MINUTE, ActivityLog::where('action', ActivityLog::AUTH_LOGIN_FAILED)->count());
    }

    public function test_successful_login_is_unchanged_and_not_logged(): void
    {
        $this->attempt('acheteur3@example.test', 'Bon-mot-de-passe-1')->assertRedirect();
        $this->assertAuthenticatedAs(\App\Models\User::find(3));
        $this->assertSame(0, ActivityLog::count());
    }

    public function test_lockout_is_logged(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->attempt('acheteur3@example.test', 'faux');
        }
        $this->attempt('acheteur3@example.test', 'faux')->assertSessionHasErrors('email');

        $lock = ActivityLog::where('action', ActivityLog::AUTH_LOCKOUT)->firstOrFail();
        $this->assertSame('trop_de_tentatives', $lock->properties['reason']);
        $this->assertStringNotContainsString('acheteur3', json_encode($lock->toArray()));
    }

    public function test_logger_failure_never_breaks_login(): void
    {
        DB::statement('DROP TABLE activity_logs'); // écriture du journal impossible
        $this->attempt('acheteur3@example.test', 'faux')->assertRedirect('/login');
        $this->attempt('acheteur3@example.test', 'Bon-mot-de-passe-1')->assertRedirect();
        $this->assertAuthenticatedAs(\App\Models\User::find(3));
    }

    public function test_connections_family_filters_the_screen(): void
    {
        $admin = $this->makeUser(10, 'admin');
        $this->attempt('acheteur3@example.test', 'faux');
        auth()->logout();

        $this->actingAs($admin)->get(route('admin.activity.index', ['family' => 'auth']))
            ->assertOk()->assertSee('Échec de connexion')->assertSee('Connexions');
    }
}
