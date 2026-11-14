<?php

namespace Tests\Feature\Security;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\Concerns\BuildsOrderSchema;
use Tests\TestCase;

/** Écran admin « Rapports CSP » : accès, tri par fréquence, table absente. */
class CspAdminScreenTest extends TestCase
{
    use BuildsOrderSchema;

    protected function setUp(): void
    {
        parent::setUp();
        $this->buildOrderSchema();
        $this->makeUser(10, 'admin');
        $this->makeUser(2, 'promoteur');
        $this->makeUser(3, 'acheteur');
        DB::table('users')->update(['created_at' => now(), 'updated_at' => now()]);
    }

    private function migrate(): void
    {
        (include base_path('database/migrations/2026_11_14_100000_create_csp_violations_table.php'))->up();
    }

    private function seedRow(string $directive, string $blocked, string $path, int $hits): void
    {
        DB::table('csp_violations')->insert([
            'key_hash' => hash('sha256', $directive.$blocked.$path), 'directive' => $directive, 'blocked' => $blocked,
            'document_path' => $path, 'hits' => $hits, 'first_seen_at' => now(), 'last_seen_at' => now(),
        ]);
    }

    public function test_access_rules(): void
    {
        $this->migrate();

        $this->get(route('admin.csp.index'))->assertRedirect(route('login'));
        $this->actingAs(\App\Models\User::find(2))->get(route('admin.csp.index'))->assertForbidden();
        $this->actingAs(\App\Models\User::find(3))->get(route('admin.csp.index'))->assertForbidden();
        $this->actingAs(\App\Models\User::find(10))->get(route('admin.csp.index'))->assertOk()
            ->assertSee('Rapports CSP')
            ->assertSee('rien n\'est bloqué', false)
            ->assertSee('Aucun rapport reçu');
    }

    public function test_rows_are_sorted_by_decreasing_frequency(): void
    {
        $this->migrate();
        $this->seedRow('script-src-attr', 'inline', '/rare', 2);
        $this->seedRow('script-src-elem', 'cdn.example', '/frequent', 90);
        $this->seedRow('style-src', 'inline', '/milieu', 15);

        $html = $this->actingAs(\App\Models\User::find(10))->get(route('admin.csp.index'))->assertOk()->getContent();

        $this->assertLessThan(strpos($html, '/milieu'), strpos($html, '/frequent'));
        $this->assertLessThan(strpos($html, '/rare'), strpos($html, '/milieu'));
        $this->assertStringContainsString('cdn.example', $html);
        $this->assertStringContainsString('90 fois', $html);
    }

    public function test_missing_table_shows_a_dash_message_without_exception(): void
    {
        $this->assertFalse(Schema::hasTable('csp_violations'));

        $this->actingAs(\App\Models\User::find(10))->get(route('admin.csp.index'))->assertOk()->assertSee('— Le stockage');
    }

    public function test_menu_entry_is_in_the_settings_submenu(): void
    {
        $html = $this->actingAs(\App\Models\User::find(10))->get(route('admin.csp.index'))->getContent();

        $this->assertStringContainsString('>Rapports CSP</a>', $html);
        $this->assertLessThan(strpos($html, '>Rapports CSP</a>'), strpos($html, "Journal d'activité</a>"));
    }
}
