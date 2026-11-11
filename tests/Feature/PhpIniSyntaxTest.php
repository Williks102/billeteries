<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Garde-fou : docker/php.ini est chargé par PHP à CHAQUE exécution dans l'image (web et CLI).
 * Dans un php.ini, un commentaire commence par « ; » (jamais « # » : erreur de syntaxe PHP ≥ 7).
 * Une erreur ici s'affiche à chaque commande artisan et peut ignorer des réglages.
 */
class PhpIniSyntaxTest extends TestCase
{
    private function iniPath(): string
    {
        return base_path('docker/php.ini');
    }

    public function test_php_ini_parses_without_any_syntax_error(): void
    {
        $errors = [];
        set_error_handler(function (int $no, string $msg) use (&$errors) {
            $errors[] = $msg;

            return true;
        });

        try {
            $parsed = parse_ini_file($this->iniPath(), false, INI_SCANNER_NORMAL);
        } finally {
            restore_error_handler();
        }

        $this->assertSame([], $errors, 'docker/php.ini contient une erreur de syntaxe : '.implode(' | ', $errors));
        $this->assertIsArray($parsed);
        $this->assertArrayHasKey('disable_functions', $parsed);
    }

    public function test_php_ini_uses_semicolon_comments_only(): void
    {
        foreach (file($this->iniPath(), FILE_IGNORE_NEW_LINES) as $n => $line) {
            $this->assertDoesNotMatchRegularExpression(
                '/^\s*#/',
                $line,
                'docker/php.ini ligne '.($n + 1).' : commentaire en « # » interdit, utilisez « ; ».'
            );
        }
    }

    public function test_php_ini_applies_the_expected_directives(): void
    {
        $parsed = parse_ini_file($this->iniPath(), false, INI_SCANNER_NORMAL);

        $this->assertSame('shell_exec,passthru,system,popen,pcntl_exec,show_source,dl', $parsed['disable_functions']);
        $this->assertNotContains('exec', explode(',', $parsed['disable_functions']), 'exec reste autorisé (backup admin)');
        $this->assertNotContains('proc_open', explode(',', $parsed['disable_functions']), 'proc_open reste autorisé (schedule:work)');
    }
}
