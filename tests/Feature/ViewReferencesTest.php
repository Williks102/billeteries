<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Garde-fou : chaque vue appelée par un contrôleur via view('nom') doit exister.
 * (Un remplacement automatique de noms de routes avait déjà réécrit des noms de vues :
 * acheteur.orders -> acheteur.orders.index, ce qui provoquait des erreurs 500.)
 *
 * DETTE CONNUE : vues appelées mais absentes AVANT cette vérification (pages jamais écrites ou
 * renommées). Cette liste ne doit que diminuer : retirer l'entrée dès que la vue est créée ou
 * que l'appel est supprimé. Ne JAMAIS l'allonger pour faire passer une régression.
 */
class ViewReferencesTest extends TestCase
{
    private const KNOWN_MISSING = [
        'admin.events.audit-history',
        'admin.events.manage-hybrid',
        'admin.events.manage-tickets',
        'events.category',
        'events.location',
        'events.recommendations',
        'events.search',
        'events.trending',
        'tickets.public-verify',
    ];

    public function test_every_view_called_by_a_controller_exists(): void
    {
        $missing = [];
        $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(base_path('app')));

        foreach ($it as $file) {
            if (! $file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }
            preg_match_all('/\bview\(\s*[\'"]([a-zA-Z0-9_.\-]+)[\'"]/', file_get_contents($file->getPathname()), $m);
            foreach ($m[1] as $name) {
                if (! view()->exists($name) && ! in_array($name, self::KNOWN_MISSING, true)) {
                    $missing[$name] = str_replace(base_path().'/', '', $file->getPathname());
                }
            }
        }

        $this->assertSame([], $missing);
    }

    public function test_known_missing_list_has_no_stale_entries(): void
    {
        $stale = array_values(array_filter(self::KNOWN_MISSING, fn ($name) => view()->exists($name)));

        $this->assertSame([], $stale, 'Ces vues existent maintenant : retirez-les de KNOWN_MISSING.');
    }
}
