<?php

namespace Tests\Feature\Stats;

use PHPUnit\Framework\TestCase;

/**
 * Garde-fou de portabilité SQLite / MySQL / TiDB : aucune fonction de date propre à un SGBD dans le code
 * de statistiques (les regroupements passent par App\Support\Stats\SqlDate : SUBSTR + COALESCE) et aucune
 * écriture ni requête brute non portable.
 */
class StatsPortabilityTest extends TestCase
{
    /** Fonctions SQL (appel nu, hors méthodes PHP) propres à MySQL/SQLite. */
    private const FORBIDDEN_FUNCTIONS = [
        'HOUR', 'MINUTE', 'DATE_FORMAT', 'STR_TO_DATE', 'DATE_ADD', 'DATE_SUB', 'DATEDIFF', 'TIMESTAMPDIFF', 'DAYOFWEEK',
        'WEEKDAY', 'YEARWEEK', 'UNIX_TIMESTAMP', 'FROM_UNIXTIME', 'GROUP_CONCAT', 'IFNULL', 'STRFTIME', 'DATE', 'MONTH', 'YEAR', 'EXTRACT',
    ];

    /** Helpers Eloquent de date : non sargables et dépendants du SGBD. */
    private const FORBIDDEN_BUILDER = ['whereMonth', 'whereYear', 'whereDate', 'whereDay', 'whereTime'];

    /** @return list<string> */
    private function files(): array
    {
        $root = dirname(__DIR__, 3);
        $files = array_merge(glob($root.'/app/Services/Stats/*.php'), glob($root.'/app/Support/Stats/*.php'));
        $this->assertNotEmpty($files);

        return $files;
    }

    public function test_no_dbms_specific_function_in_stats_code(): void
    {
        foreach ($this->files() as $file) {
            // retire les commentaires : la doc peut citer les fonctions interdites
            $code = preg_replace(['~/\*.*?\*/~s', '~//[^\n]*~'], '', file_get_contents($file));
            foreach (self::FORBIDDEN_FUNCTIONS as $fn) {
                $this->assertSame(0, preg_match('/(?<!function )(?<![>\w:$])'.$fn.'\s*\(/i', $code), basename($file)." utilise {$fn}()");
            }
            foreach (self::FORBIDDEN_BUILDER as $needle) {
                $this->assertStringNotContainsString($needle, $code, basename($file)." utilise {$needle}");
            }
        }
    }

    public function test_stats_code_never_writes(): void
    {
        foreach ($this->files() as $file) {
            $code = file_get_contents($file);
            foreach (['->insert(', '->update(', '->delete(', '->create(', '->save(', 'DB::statement', 'lockForUpdate', 'increment(', 'decrement('] as $needle) {
                $this->assertStringNotContainsString($needle, $code, basename($file)." écrit en base ({$needle})");
            }
        }
    }
}
