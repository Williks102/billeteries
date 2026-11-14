<?php

namespace Tests\Feature\Security;

use App\Models\EventCategory;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\BuildsOrderSchema;
use Tests\TestCase;

/** CSP lot A : nonce par requête, politique stricte en rapport seulement, politique appliquée inchangée. */
class CspReportOnlyTest extends TestCase
{
    use BuildsOrderSchema;

    /** Politique appliquée AVANT le lot A (disque média local) : elle ne doit pas changer d'un caractère. */
    private const APPLIED = "default-src 'self'; "
        ."script-src 'self' 'unsafe-inline' https://code.jquery.com https://cdn.jsdelivr.net; "
        ."style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdnjs.cloudflare.com https://cdn.jsdelivr.net; "
        ."img-src 'self' data: blob: https://res.cloudinary.com; "
        ."font-src 'self' data: https://fonts.gstatic.com https://cdnjs.cloudflare.com https://cdn.jsdelivr.net; "
        ."connect-src 'self' https://cdn.jsdelivr.net; "
        ."frame-src 'self'; object-src 'none'; base-uri 'self'; frame-ancestors 'self'; form-action 'self'; "
        .'upgrade-insecure-requests;';

    protected function setUp(): void
    {
        parent::setUp();
        config(['app.key' => 'base64:'.base64_encode(str_repeat('k', 32))]);
    }

    private function nonceOf($response): string
    {
        $this->assertSame(1, preg_match("/'nonce-([A-Za-z0-9+\/=]+)'/", (string) $response->headers->get('Content-Security-Policy-Report-Only'), $m));

        return $m[1];
    }

    public function test_applied_policy_is_unchanged(): void
    {
        $this->assertSame(self::APPLIED, $this->get('/login')->headers->get('Content-Security-Policy'));
    }

    public function test_report_only_header_has_the_strict_policy(): void
    {
        $header = (string) $this->get('/login')->assertOk()->headers->get('Content-Security-Policy-Report-Only');

        $this->assertStringContainsString("default-src 'self'", $header);
        $this->assertMatchesRegularExpression("/script-src 'self' 'nonce-[A-Za-z0-9+\/=]{24,}' https:\/\/code\.jquery\.com https:\/\/cdn\.jsdelivr\.net;/", $header);
        $this->assertStringContainsString("script-src-attr 'none'", $header);
        $this->assertStringContainsString('report-uri /csp-report', $header);
        $this->assertStringContainsString("object-src 'none'", $header);
        $this->assertStringNotContainsString("script-src 'self' 'unsafe-inline'", $header);
        $this->assertStringNotContainsString('upgrade-insecure-requests', $header);
    }

    public function test_report_only_header_includes_the_media_origin(): void
    {
        config([
            'filesystems.media_disk' => 'r2',
            'filesystems.disks.r2.url' => 'https://pub-abc123.r2.dev',
            'filesystems.disks.r2.key' => 'k', 'filesystems.disks.r2.secret' => 's',
            'filesystems.disks.r2.bucket' => 'b', 'filesystems.disks.r2.endpoint' => 'https://acc.r2.cloudflarestorage.com',
        ]);
        Storage::forgetDisk('r2');

        $response = $this->get('/login');
        $this->assertStringContainsString('img-src \'self\' data: blob: https://res.cloudinary.com https://pub-abc123.r2.dev;', (string) $response->headers->get('Content-Security-Policy-Report-Only'));
        $this->assertStringContainsString('https://pub-abc123.r2.dev', (string) $response->headers->get('Content-Security-Policy'));
    }

    public function test_report_only_header_can_be_disabled(): void
    {
        config(['security.csp_report_only' => false]);

        $response = $this->get('/login');
        $this->assertNull($response->headers->get('Content-Security-Policy-Report-Only'));
        $this->assertSame(self::APPLIED, $response->headers->get('Content-Security-Policy'));
    }

    public function test_each_request_gets_a_different_nonce(): void
    {
        $a = $this->nonceOf($this->get('/login'));
        $b = $this->nonceOf($this->get('/login'));

        $this->assertNotSame($a, $b);
        $this->assertGreaterThanOrEqual(16, strlen(base64_decode($a, true)));
    }

    public function test_helper_and_directive_do_not_fail_outside_a_request(): void
    {
        $this->assertSame('', csp_nonce());
        $this->assertSame('<script >x</script>', trim(\Illuminate\Support\Facades\Blade::render('<script @cspNonce>x</script>')));
    }

    private function assertPageUsesTheHeaderNonce($response, bool $expectInlineScript = true): void
    {
        $response->assertOk();
        $nonce = $this->nonceOf($response);
        $html = $response->getContent();

        preg_match_all('/<script(?![^>]*\ssrc=)([^>]*)>/', $html, $m);
        if ($expectInlineScript) {
            $this->assertNotEmpty($m[1], 'la page doit contenir un script inline pour ce test');
        }
        foreach ($m[1] as $attrs) {
            $this->assertStringContainsString('nonce="'.$nonce.'"', $attrs);
        }
        $this->assertStringNotContainsString('nonce=""', $html);
    }

    public function test_the_header_nonce_is_the_one_rendered_in_real_pages(): void
    {
        $this->buildOrderSchema();
        Schema::table('events', function ($t) {
            $t->text('description')->nullable();
            $t->string('address')->nullable();
            $t->string('image')->nullable();
        });
        $category = EventCategory::create(['name' => 'Musique', 'slug' => 'musique', 'is_active' => true]);
        DB::table('events')->where('id', 1)->update(['category_id' => $category->id, 'event_date' => now()->addWeek()->toDateString(), 'title' => 'Concert']);
        $this->ticketType(10);
        DB::table('ticket_types')->update(['event_id' => 1]);
        $this->makeUser(2, 'promoteur');
        $this->makeUser(10, 'admin');
        DB::table('users')->update(['created_at' => now(), 'updated_at' => now()]);
        DB::table('events')->update(['created_at' => now(), 'updated_at' => now()]);

        $this->assertPageUsesTheHeaderNonce($this->get(route('events.show', 1)));            // layout public
        $this->assertPageUsesTheHeaderNonce($this->get('/register'));                        // authentification
        $this->assertPageUsesTheHeaderNonce($this->actingAs(User::find(1))->get(route('acheteur.orders.index'))); // acheteur
        $this->assertPageUsesTheHeaderNonce($this->actingAs(User::find(2))->get(route('promoteur.profile.show'))); // promoteur
        $this->assertPageUsesTheHeaderNonce($this->actingAs(User::find(10))->get(route('admin.profile'))); // admin
    }

    /** Garde-fou statique : le lot B/C ne doit pas réintroduire de script inline sans nonce. */
    public function test_every_inline_script_in_views_has_a_nonce(): void
    {
        $offenders = [];
        $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(resource_path('views'), \FilesystemIterator::SKIP_DOTS));
        foreach ($files as $file) {
            $path = str_replace('\\', '/', $file->getPathname());
            if (! str_ends_with($path, '.blade.php') || str_contains($path, '/views/emails/')) {
                continue;
            }
            preg_match_all('/<script\b([^>]*)>/i', (string) file_get_contents($path), $m);
            foreach ($m[1] as $attrs) {
                if (! preg_match('/\bsrc\s*=/i', $attrs) && ! preg_match('/@cspNonce|\bnonce\s*=/', $attrs)) {
                    $offenders[] = $path;
                }
            }
        }

        $this->assertSame([], $offenders, 'Script inline sans nonce : ajouter @cspNonce.');
    }
}
