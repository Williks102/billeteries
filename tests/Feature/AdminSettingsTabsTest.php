<?php

namespace Tests\Feature;

use App\Support\EmailTemplates;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Concerns\BuildsOrderSchema;
use Tests\TestCase;

/**
 * Sous-menu admin « Paramètres » : Emails, Configuration, Sauvegardes.
 */
class AdminSettingsTabsTest extends TestCase
{
    use BuildsOrderSchema;

    protected function setUp(): void
    {
        parent::setUp();
        $this->buildOrderSchema();
        $this->makeUser(10, 'admin');
        $this->makeUser(2, 'promoteur');
        $this->makeUser(3, 'acheteur');
    }

    private function admin()
    {
        return \App\Models\User::find(10);
    }

    /** @return array<string, array{0: string}> */
    public static function tabs(): array
    {
        return [
            'emails' => ['admin.emails.dashboard'],
            'modèles' => ['admin.emails.templates'],
            'configuration' => ['admin.settings.index'],
            'sauvegardes' => ['admin.backups.index'],
        ];
    }

    #[DataProvider('tabs')]
    public function test_page_is_200_for_admin_403_for_others_redirect_for_guest(string $route): void
    {
        $this->actingAs($this->admin())->get(route($route))->assertOk();
        $this->actingAs(\App\Models\User::find(2))->get(route($route))->assertForbidden();
        $this->actingAs(\App\Models\User::find(3))->get(route($route))->assertForbidden();
        auth()->logout();
        $this->get(route($route))->assertRedirect();
    }

    public function test_settings_submenu_has_no_dead_link_and_points_to_existing_routes(): void
    {
        $html = $this->actingAs($this->admin())->get(route('admin.settings.index'))->getContent();
        preg_match('/id="submenu-settings">(.*?)<\/ul>/s', $html, $m);
        $this->assertNotEmpty($m, 'Sous-menu Paramètres introuvable.');
        $this->assertStringNotContainsString('href="#"', $m[1]);

        foreach (['admin.emails.dashboard', 'admin.settings.index', 'admin.backups.index'] as $name) {
            $this->assertTrue(Route::has($name));
            $this->assertStringContainsString('href="'.route($name).'"', $m[1]);
        }
    }

    #[DataProvider('tabs')]
    public function test_settings_menu_stays_open_and_current_entry_is_active(string $route): void
    {
        $html = $this->actingAs($this->admin())->get(route($route))->getContent();

        $this->assertMatchesRegularExpression('/submenu-parent open/', $html);
        $this->assertMatchesRegularExpression('/id="submenu-settings"/', $html);
        $this->assertMatchesRegularExpression('/<ul class="sidebar-submenu open" id="submenu-settings">/', $html);
        $this->assertMatchesRegularExpression('/<a href="'.preg_quote(route($route === 'admin.emails.templates' ? 'admin.emails.dashboard' : $route), '/').'" class="active">/', $html);
    }

    public function test_configuration_page_is_titled_configuration(): void
    {
        $this->actingAs($this->admin())->get(route('admin.settings.index'))
            ->assertSee('<title>Configuration', false)
            ->assertSee('<h1 class="h3 mb-1">Configuration</h1>', false);
    }

    public function test_email_templates_match_mail_classes(): void
    {
        $files = collect(glob(app_path('Mail/*.php')))
            ->map(fn ($f) => 'App\\Mail\\'.basename($f, '.php'))
            ->sort()->values()->all();
        $listed = array_keys(EmailTemplates::all());
        sort($listed);

        $this->assertSame($files, $listed, 'EmailTemplates doit lister exactement les classes de app/Mail.');
        foreach ($listed as $class) {
            $this->assertTrue(is_subclass_of($class, \Illuminate\Mail\Mailable::class));
        }

        $page = $this->actingAs($this->admin())->get(route('admin.emails.templates'));
        foreach (EmailTemplates::all() as $t) {
            $page->assertSee($t['label']);
        }
        foreach (['ticket_delivery', 'event_reminder', 'commission_payment'] as $invented) {
            $page->assertDontSee($invented);
        }
    }

    public function test_emails_page_shows_driver_sender_and_never_secrets(): void
    {
        config(['mail.default' => 'log', 'mail.from.address' => 'billets@exemple.test', 'mail.from.name' => 'Billetterie',
            'mail.mailers.smtp.password' => 'SECRET-SMTP-PWD', 'mail.mailers.smtp.username' => 'SECRET-SMTP-USER']);

        $page = $this->actingAs($this->admin())->get(route('admin.emails.dashboard'));
        $page->assertOk()->assertSee('billets@exemple.test')->assertSee('Billetterie')
            ->assertSee('Aucun message n\'est réellement envoyé', false)
            ->assertDontSee('SECRET-SMTP-PWD')->assertDontSee('SECRET-SMTP-USER')
            ->assertDontSee('<script>', false); // pas de JavaScript inline : fichier externe
        $this->assertDoesNotMatchRegularExpression('/<script(?![^>]*\bsrc=)[^>]*>/', $page->getContent());
        $page->assertSee('js/admin-emails-test.js', false);
        $this->assertFileExists(public_path('js/admin-emails-test.js'));
    }

    public function test_test_email_validates_the_address(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin)->postJson(route('admin.emails.test'), ['email' => 'pas-une-adresse'])
            ->assertStatus(422)->assertJsonValidationErrors('email');
        $this->actingAs($admin)->postJson(route('admin.emails.test'), [])->assertStatus(422);
        $this->actingAs(\App\Models\User::find(2))->postJson(route('admin.emails.test'), ['email' => 'a@b.test'])->assertForbidden();
    }

    public function test_test_email_succeeds_and_says_when_simulated(): void
    {
        config(['mail.default' => 'array']);
        $this->actingAs($this->admin())->postJson(route('admin.emails.test'), ['email' => 'a@b.test'])
            ->assertOk()->assertJson(['success' => true])
            ->assertJsonPath('message', fn ($m) => str_contains($m, 'aucun message n\'a été réellement envoyé'));
    }

    public function test_test_email_failure_never_leaks_technical_details(): void
    {
        \Illuminate\Support\Facades\Mail::shouldReceive('raw')->andThrow(new \RuntimeException('smtp://user:TOPSECRET@host:587 refused'));

        $response = $this->actingAs($this->admin())->postJson(route('admin.emails.test'), ['email' => 'a@b.test']);
        $response->assertStatus(500)->assertJson(['success' => false]);
        $this->assertStringNotContainsString('TOPSECRET', $response->getContent());
        $this->assertStringNotContainsString('smtp://', $response->getContent());
    }

    public function test_backups_page_is_honest_and_leaks_no_database_identity(): void
    {
        config(['database.connections.mysql.host' => 'gateway.secret-host.example', 'database.connections.mysql.username' => 'secret_user',
            'database.connections.mysql.password' => 'secret_pwd', 'database.connections.mysql.database' => 'secret_db']);

        $page = $this->actingAs($this->admin())->get(route('admin.backups.index'));
        $page->assertOk()->assertSee('TiDB Cloud')->assertSee('SQLite')
            ->assertSee('ni une sauvegarde complète ni un moyen de restauration', false)
            ->assertSee(route('admin.activity.index'), false);
        foreach (['secret-host', 'secret_user', 'secret_pwd', 'secret_db'] as $leak) {
            $page->assertDontSee($leak);
        }
        $this->assertDoesNotMatchRegularExpression('/<script(?![^>]*\bsrc=)[^>]*>/', $page->getContent());
    }

    public function test_backups_page_offers_only_working_exports(): void
    {
        $page = $this->actingAs($this->admin())->get(route('admin.backups.index'));
        foreach (['admin.users.export', 'admin.events.export', 'admin.orders.export', 'admin.tickets.export'] as $name) {
            $this->assertTrue(Route::has($name));
            $page->assertSee('href="'.route($name).'"', false);
        }
    }

    public function test_backups_page_reports_media_disk(): void
    {
        config(['filesystems.media_disk' => 'public']);
        $this->actingAs($this->admin())->get(route('admin.backups.index'))->assertSee('<strong>public</strong>', false)
            ->assertSee('stockées sur le serveur');

        config(['filesystems.media_disk' => 'r2']);
        $this->actingAs($this->admin())->get(route('admin.backups.index'))->assertSee('<strong>r2</strong>', false)
            ->assertSee('hors du serveur');
    }

    public function test_no_mysqldump_or_dead_maintenance_route_remains(): void
    {
        foreach (['admin.settings.backup', 'admin.settings.clear-cache', 'admin.settings.optimize-db', 'admin.settings.cleanup-files', 'admin.settings.test-email'] as $name) {
            $this->assertFalse(Route::has($name), $name.' devrait avoir disparu.');
        }
        $this->assertStringNotContainsString('mysqldump', file_get_contents(app_path('Http/Controllers/Admin/SettingsController.php')));
        foreach (['backup', 'clearCache', 'optimizeDatabase', 'cleanupFiles'] as $method) {
            $this->assertFalse(method_exists(\App\Http\Controllers\Admin\SettingsController::class, $method));
        }
    }
}
