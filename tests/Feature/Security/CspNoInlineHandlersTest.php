<?php

namespace Tests\Feature\Security;

use Tests\TestCase;

/**
 * CSP lot B : plus aucun attribut d'événement inline dans les vues (script-src-attr 'none' deviendra la règle).
 * Les comportements passent par public/js/csp-actions.js (data-confirm, data-on-*, data-img-fallback).
 */
class CspNoInlineHandlersTest extends TestCase
{
    /** @return array<string, string> chemin => contenu, hors vues d'e-mails */
    private function views(): array
    {
        $out = [];
        $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(resource_path('views'), \FilesystemIterator::SKIP_DOTS));
        foreach ($files as $file) {
            $path = str_replace('\\', '/', $file->getPathname());
            if (str_ends_with($path, '.php') && ! str_contains($path, '/views/emails/')) {
                $out[$path] = (string) file_get_contents($path);
            }
        }

        return $out;
    }

    public function test_no_inline_event_attribute_and_no_javascript_url_in_views(): void
    {
        $offenders = [];
        foreach ($this->views() as $path => $content) {
            if (preg_match_all('/\son[a-z]+\s*=\s*["\']/i', $content, $m)) {
                $offenders[] = $path.' : '.implode(', ', array_map('trim', $m[0]));
            }
            if (preg_match('/href\s*=\s*["\']\s*javascript:/i', $content)) {
                $offenders[] = $path.' : href="javascript:"';
            }
        }

        $this->assertSame([], $offenders, 'Attribut d\'événement inline interdit : utiliser data-confirm / data-on-* (docs/csp.md).');
    }

    public function test_every_layout_loads_the_csp_actions_script(): void
    {
        foreach (glob(resource_path('views/layouts/*.blade.php')) as $layout) {
            $this->assertStringContainsString("asset('js/csp-actions.js')", (string) file_get_contents($layout), basename($layout));
        }
        $this->assertFileExists(public_path('js/csp-actions.js'));
    }

    public function test_csp_actions_script_never_evaluates_strings(): void
    {
        $js = (string) file_get_contents(public_path('js/csp-actions.js'));
        $code = preg_replace('#/\*.*?\*/|//[^\n]*#s', '', $js);

        $this->assertDoesNotMatchRegularExpression('/\beval\s*\(|new\s+Function\b|setTimeout\s*\(\s*["\']|window\s*\[/', (string) $code);
    }

    public function test_every_action_used_in_views_is_registered(): void
    {
        $used = [];
        $registered = [];
        $sources = $this->views();
        foreach (glob(public_path('js/*.js')) as $js) {
            $sources[$js] = (string) file_get_contents($js);
        }

        foreach ($sources as $path => $content) {
            // La documentation de csp-actions.js cite « nom » en exemple : ce n'est pas un usage.
            if (basename($path) !== 'csp-actions.js' && preg_match_all('/\sdata-on-(?:click|change|submit|input)="([^"{]+)"/', $content, $m)) {
                foreach ($m[1] as $name) {
                    $used[$name][] = basename($path);
                }
            }
            if (preg_match_all('/CspActions\.register\(\s*[\'"]([^\'"]+)[\'"]/', $content, $m)) {
                array_push($registered, ...$m[1]);
            }
            if (preg_match_all('/[\'"]([a-z0-9][a-z0-9-]*)[\'"]\s*:\s*function\b/', $content, $m)) {
                array_push($registered, ...$m[1]);
            }
        }

        $this->assertNotEmpty($used);
        $orphans = array_diff_key($used, array_flip($registered));
        $this->assertSame([], $orphans, 'Action data-on-* sans CspActions.register : '.json_encode($orphans));
    }

    /** Un `id` en double rend le second ignoré : le profil promoteur plantait (toggleEdit lisait « nameInput » inexistant). */
    public function test_no_element_declares_two_id_attributes(): void
    {
        $offenders = [];
        $dir = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(base_path('resources/views')));
        foreach ($dir as $file) {
            if (! $file->isFile() || ! str_ends_with($file->getFilename(), '.blade.php') || str_contains($file->getPathname(), '/emails/')) {
                continue;
            }
            if (preg_match('/<[a-z][^<>]*\sid="[^"]*"[^<>]*\sid="[^"]*"/i', (string) file_get_contents($file->getPathname()))) {
                $offenders[] = str_replace(base_path().'/', '', $file->getPathname());
            }
        }

        $this->assertSame([], $offenders, 'balise avec deux attributs id');
    }

    public function test_promoter_profile_name_input_has_the_id_the_script_looks_for(): void
    {
        $view = (string) file_get_contents(base_path('resources/views/promoteur/profile.blade.php'));

        $this->assertStringContainsString('for="nameInput"', $view);
        $this->assertSame(1, preg_match_all('/id="nameInput"/', $view));
        $this->assertStringContainsString("'nameInput'", $view, 'le script cible bien nameInput');
    }
}
