<?php

namespace Tests\Feature\Security;

use App\Services\CspReportRecorder;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/** POST /csp-report : collecte anonyme et agrégée, toujours 204, bornée, sans donnée personnelle. */
class CspReportEndpointTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['app.key' => 'base64:'.base64_encode(str_repeat('k', 32))]);
        (include base_path('database/migrations/2026_11_14_100000_create_csp_violations_table.php'))->up();
    }

    private function send(array|string $payload, string $type = 'application/csp-report', array $server = [])
    {
        return $this->call('POST', '/csp-report', [], [], [], array_merge(['CONTENT_TYPE' => $type], $server),
            is_string($payload) ? $payload : json_encode($payload));
    }

    private function legacy(string $directive = 'script-src-attr', string $blocked = 'inline', string $doc = 'https://shop.test/login'): array
    {
        return ['csp-report' => [
            'document-uri' => $doc, 'violated-directive' => $directive, 'effective-directive' => $directive,
            'blocked-uri' => $blocked, 'script-sample' => 'onclick=alert(SECRET-SAMPLE)', 'source-file' => 'https://shop.test/app.js?token=SECRET-FILE',
            'referrer' => 'https://ref.test/SECRET-REF', 'original-policy' => "default-src 'self'",
        ]];
    }

    private function reports(string $directive, string $blocked, string $doc): array
    {
        return [['type' => 'csp-violation', 'url' => $doc, 'user_agent' => 'SECRET-UA', 'body' => [
            'documentURL' => $doc, 'effectiveDirective' => $directive, 'blockedURL' => $blocked, 'sample' => 'SECRET-SAMPLE',
            'sourceFile' => 'https://shop.test/x.js', 'referrer' => 'https://ref.test/SECRET-REF',
        ]]];
    }

    public function test_both_formats_answer_204_and_are_stored(): void
    {
        $this->send($this->legacy())->assertNoContent();
        $this->send($this->reports('script-src-elem', 'https://evil.example/a.js?k=SECRET-URL', 'https://shop.test/login'), 'application/reports+json')->assertNoContent();

        $rows = DB::table('csp_violations')->orderBy('id')->get();
        $this->assertCount(2, $rows);
        $this->assertSame(['script-src-attr', 'inline', '/login'], [$rows[0]->directive, $rows[0]->blocked, $rows[0]->document_path]);
        $this->assertSame(['script-src-elem', 'evil.example', '/login'], [$rows[1]->directive, $rows[1]->blocked, $rows[1]->document_path]);
        $this->assertSame('', $this->send($this->legacy())->getContent());
    }

    public function test_unknown_directives_are_grouped_so_an_attacker_cannot_fill_the_table_with_invented_rows(): void
    {
        foreach (['inventee-1', 'inventee-2', '<script>x</script>', str_repeat('a', 30)] as $fake) {
            $this->send($this->legacy($fake, 'inline'))->assertNoContent();
        }
        $this->send($this->legacy('script-src-attr', 'inline'))->assertNoContent();

        $directives = DB::table('csp_violations')->pluck('directive')->all();
        sort($directives);
        $this->assertSame(['inconnue', 'script-src-attr'], $directives, 'toutes les directives inventées tiennent dans UNE ligne');
        $this->assertSame(4, (int) DB::table('csp_violations')->where('directive', 'inconnue')->value('hits'));
    }

    public function test_the_body_is_never_parsed_by_the_global_trim_and_null_middlewares_on_this_route(): void
    {
        // Un corps « +json » est lu en entier par TrimStrings / ConvertEmptyStringsToNull avant le plafond du service,
        // sauf si la route est exclue de leur traitement.
        foreach ([\Illuminate\Foundation\Http\Middleware\TrimStrings::class, \Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull::class] as $class) {
            $ref = new \ReflectionProperty($class, 'skipCallbacks');
            $ref->setAccessible(true);
            $skips = $ref->getValue();

            $this->assertTrue(
                collect($skips)->contains(fn ($cb) => $cb(\Illuminate\Http\Request::create('/csp-report', 'POST'))),
                class_basename($class).' doit sauter /csp-report'
            );
            $this->assertFalse(
                collect($skips)->contains(fn ($cb) => $cb(\Illuminate\Http\Request::create('/login', 'POST'))),
                class_basename($class).' ne doit sauter AUCUNE autre route'
            );
        }

        // Un énorme corps « reports+json » reste sans effet et sans erreur
        $this->send(str_repeat('{"a":', 3000).'1'.str_repeat('}', 3000), 'application/reports+json')->assertNoContent();
        $this->assertSame(0, DB::table('csp_violations')->count());
    }

    public function test_no_csrf_token_and_no_session_are_needed(): void
    {
        $response = $this->send($this->legacy());
        $response->assertNoContent();
        $this->assertEmpty($response->headers->getCookies());
    }

    public function test_stores_no_query_string_sample_ip_or_user_agent(): void
    {
        $this->send(
            $this->legacy('script-src-elem', 'https://cdn.example/lib.js?token=SECRET-QS', 'https://shop.test/events/42?email=a@b.test&t=SECRET-TOKEN#SECRET-FRAG'),
            'application/csp-report',
            ['REMOTE_ADDR' => '203.0.113.77', 'HTTP_USER_AGENT' => 'SECRET-UA/1.0', 'HTTP_REFERER' => 'https://ref.test/SECRET-REF']
        )->assertNoContent();

        $row = DB::table('csp_violations')->first();
        $this->assertSame('/events/{event}', $row->document_path);
        $this->assertSame('cdn.example', $row->blocked);

        $dump = json_encode(DB::table('csp_violations')->get());
        foreach (['SECRET', '203.0.113.77', 'a@b.test', '?', '/events/42'] as $forbidden) {
            $this->assertStringNotContainsString($forbidden, $dump);
        }
        $this->assertSame(
            ['id', 'key_hash', 'directive', 'blocked', 'document_path', 'hits', 'first_seen_at', 'last_seen_at'],
            \Illuminate\Support\Facades\Schema::getColumnListing('csp_violations')
        );
    }

    public function test_same_key_increments_the_counter(): void
    {
        $this->send($this->legacy())->assertNoContent();
        $this->send($this->legacy())->assertNoContent();
        $this->send($this->legacy('script-src-attr', 'inline', 'https://shop.test/login?x=1'))->assertNoContent();
        $this->send($this->legacy('script-src-attr', 'eval'))->assertNoContent();

        $this->assertSame(2, DB::table('csp_violations')->count());
        $this->assertSame(3, (int) DB::table('csp_violations')->where('blocked', 'inline')->value('hits'));
    }

    public function test_row_cap_blocks_new_keys_but_still_increments_existing_ones(): void
    {
        config(['security.csp_report_max_rows' => 2]);
        $this->send($this->legacy('script-src', 'inline'))->assertNoContent();
        $this->send($this->legacy('img-src', 'inline'))->assertNoContent();
        $this->send($this->legacy('font-src', 'inline'))->assertNoContent();
        $this->send($this->legacy('script-src', 'inline'))->assertNoContent();

        $this->assertSame(2, DB::table('csp_violations')->count());
        $this->assertSame(0, DB::table('csp_violations')->where('directive', 'font-src')->count());
        $this->assertSame(2, (int) DB::table('csp_violations')->where('directive', 'script-src')->value('hits'));
    }

    public function test_oversized_body_is_refused_without_storing_and_without_error(): void
    {
        $big = $this->legacy();
        $big['csp-report']['script-sample'] = str_repeat('x', 20000);

        $this->send($big)->assertNoContent();
        $this->assertSame(0, DB::table('csp_violations')->count());
    }

    public function test_garbage_and_wrong_content_type_never_fail(): void
    {
        $this->send('not json')->assertNoContent();
        $this->send('{"csp-report": 5}')->assertNoContent();
        $this->send('[1,2,3]', 'application/reports+json')->assertNoContent();
        $this->send($this->legacy(), 'text/plain')->assertNoContent();
        $this->assertSame(0, DB::table('csp_violations')->count());
    }

    public function test_rate_limit_answers_429_beyond_30_per_minute(): void
    {
        for ($i = 0; $i < 30; $i++) {
            $this->send($this->legacy())->assertNoContent();
        }
        $this->send($this->legacy())->assertStatus(429);
    }

    public function test_global_per_minute_cap_stops_writes_but_not_the_response(): void
    {
        for ($i = 0; $i < CspReportRecorder::GLOBAL_PER_MINUTE; $i++) {
            \Illuminate\Support\Facades\RateLimiter::hit('csp-report:global', 60);
        }
        $this->send($this->legacy())->assertNoContent();

        $this->assertSame(0, DB::table('csp_violations')->count());
    }

    public function test_missing_table_never_gives_a_500(): void
    {
        \Illuminate\Support\Facades\Schema::drop('csp_violations');

        $this->send($this->legacy())->assertNoContent();
    }
}
