<?php

namespace Tests\Feature\Security;

use Tests\TestCase;

/** CSP lot C : plus aucun script chargé depuis un CDN ; les bibliothèques sont hébergées dans public/vendor. */
class CspLocalScriptsTest extends TestCase
{
    public function test_no_view_loads_a_script_from_another_origin(): void
    {
        $offenders = [];
        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(resource_path('views'))) as $file) {
            if (! str_ends_with($file->getFilename(), '.blade.php') || str_contains($file->getPathname(), '/emails/')) {
                continue;
            }
            if (preg_match('/<script[^>]*\ssrc=["\']\s*(https?:)?\/\//i', file_get_contents($file->getPathname()))) {
                $offenders[] = $file->getPathname();
            }
        }

        $this->assertSame([], $offenders, 'Un script externe serait bloqué par la politique stricte : l\'héberger dans public/vendor.');
    }

    public function test_hosted_libraries_exist_and_define_their_globals(): void
    {
        $expected = [
            'bootstrap.bundle.min.js' => 'Tooltip',
            'jquery.min.js' => 'jQuery',
            'chart.umd.js' => 'Chart',
            'Sortable.min.js' => 'Sortable',
            'jsQR.js' => 'jsQR',
        ];
        foreach ($expected as $file => $marker) {
            $path = public_path('vendor/'.$file);
            $this->assertFileExists($path);
            $this->assertGreaterThan(30_000, filesize($path), $file);
            $this->assertStringContainsString($marker, file_get_contents($path), $file);
            $this->assertStringNotContainsString('sourceMappingURL', file_get_contents($path), $file);
        }
    }

    public function test_every_vendor_script_referenced_by_a_view_is_hosted(): void
    {
        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(resource_path('views'))) as $file) {
            if (! str_ends_with($file->getFilename(), '.blade.php')) {
                continue;
            }
            preg_match_all("/asset\('vendor\/([^']+)'\)/", file_get_contents($file->getPathname()), $m);
            foreach ($m[1] as $name) {
                $this->assertFileExists(public_path('vendor/'.$name), $file->getPathname());
            }
        }
    }

    /** CSP lot D : tout bloc <style> des vues (hors e-mails) porte le nonce, sinon il est bloqué par la politique. */
    public function test_every_style_block_in_views_has_a_nonce(): void
    {
        $offenders = [];
        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(resource_path('views'))) as $file) {
            if (! str_ends_with($file->getFilename(), '.blade.php') || str_contains($file->getPathname(), '/emails/')) {
                continue;
            }
            if (preg_match('/<style(?![^>]*@cspNonce)[\s>]/i', file_get_contents($file->getPathname()))) {
                $offenders[] = $file->getPathname();
            }
        }

        $this->assertSame([], $offenders);
    }

    public function test_applied_policy_has_no_unsafe_inline_for_style_blocks(): void
    {
        $this->buildStyleHeaders();
    }

    private function buildStyleHeaders(): void
    {
        config(['app.key' => 'base64:'.base64_encode(str_repeat('k', 32))]);
        $header = (string) $this->get('/login')->headers->get('Content-Security-Policy');

        $this->assertMatchesRegularExpression("/style-src 'self' 'nonce-[A-Za-z0-9+\/=]+' https:/", $header);
        $this->assertStringContainsString("style-src-attr 'unsafe-inline'", $header);
        $this->assertDoesNotMatchRegularExpression("/style-src 'self'[^;]*unsafe-inline/", $header);
    }
}
