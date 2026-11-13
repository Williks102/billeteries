<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use ReflectionClass;
use Tests\Concerns\BuildsOrderSchema;
use Tests\TestCase;

/**
 * Garde-fous du nettoyage « écrans morts » : l'onglet Analytics, les écrans événement admin
 * (gestion hybride, billets d'un événement, historique d'audit) et les pages publiques sans route
 * ont été supprimés ; rien ne doit y renvoyer, et aucune route ne doit pointer dans le vide.
 *
 * La table `event_audit_logs`, sa migration et le modèle EventAuditLog sont CONSERVÉS exprès
 * (futur journal d'activité) : ce test ne les concerne pas.
 */
class DeadCodeRemovalTest extends TestCase
{
    use BuildsOrderSchema;

    /**
     * DETTE CONNUE (hors périmètre du nettoyage, même liste que RoutingConventionsTest) : routes
     * dont la méthode de contrôleur n'existe pas. Cette liste ne doit que diminuer.
     */
    private const KNOWN_ROUTES_WITHOUT_ACTION = [
        'acheteur.favorites', 'acheteur.favorites.add', 'acheteur.favorites.remove',
    ];

    /**
     * DETTE CONNUE (hors périmètre) : vue d'auth « verify » appelant verification.resend, route absente
     * (vérification d'email non activée, voir decisions.md). Ne doit que diminuer.
     */
    private const KNOWN_UNKNOWN_NAMES_IN_VIEWS = ['verification.resend'];

    /** Noms de routes supprimés : plus aucune vue ne doit les utiliser. */
    private const REMOVED_ROUTE_NAMES = [
        'admin.analytics', 'admin.analytics.data', 'admin.analytics.filter', 'admin.analytics.export',
        'admin.analytics.realtime', 'admin.analytics.events.show', 'admin.analytics.promoters.show',
        'admin.analytics.compare', 'admin.analytics.cart-abandonment', 'admin.analytics.sales-heatmap',
        'admin.analytics.cohort', 'admin.analytics.predictions',
        'admin.events.manage-tickets', 'admin.events.store-tickets', 'admin.events.audit-history',
        'admin.events.manage-hybrid',
    ];

    /** @return list<string> chemins absolus de toutes les vues Blade */
    private function bladeFiles(): array
    {
        $files = [];
        $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(resource_path('views')));
        foreach ($it as $file) {
            if ($file->isFile() && str_ends_with($file->getFilename(), '.blade.php')) {
                $files[] = $file->getPathname();
            }
        }

        return $files;
    }

    public function test_removed_urls_answer_404(): void
    {
        $this->buildOrderSchema();
        $admin = $this->makeUser(10, 'admin');

        foreach (['/admin/analytics', '/admin/analytics/data', '/admin/events/1/manage-tickets', '/admin/events/1/audit-history'] as $url) {
            $this->actingAs($admin)->get($url)->assertNotFound();
        }
        $this->actingAs($admin)->post('/admin/events/1/store-tickets')->assertStatus(404);
        $this->actingAs($admin)->post('/admin/analytics/filter')->assertStatus(404);
        $this->actingAs($admin)->get('/admin/analytics/export')->assertNotFound();
    }

    public function test_removed_route_names_do_not_exist(): void
    {
        foreach (self::REMOVED_ROUTE_NAMES as $name) {
            $this->assertFalse(Route::has($name), "La route $name devrait avoir été supprimée.");
        }
    }

    public function test_no_view_links_to_a_removed_route(): void
    {
        $found = [];
        foreach ($this->bladeFiles() as $path) {
            $content = file_get_contents($path);
            foreach (self::REMOVED_ROUTE_NAMES as $name) {
                if (preg_match('/[\'"]'.preg_quote($name, '/').'[\'"]/', $content)) {
                    $found[] = basename($path)." -> $name";
                }
            }
            if (preg_match('#/(manage-tickets|store-tickets|audit-history|manage-hybrid)\b|admin/analytics#', $content)) {
                $found[] = basename($path).' -> URL supprimée';
            }
        }

        $this->assertSame([], $found);
    }

    public function test_every_route_points_to_an_existing_controller_method(): void
    {
        $problems = [];
        foreach (Route::getRoutes() as $route) {
            $action = $route->getActionName();
            if ($action === 'Closure' || ! str_contains($action, '@')) {
                if (! is_string($route->getAction('uses')) || $route->getAction('uses') === '') {
                    continue; // closure (redirections triviales, voir RoutingConventionsTest)
                }
            }
            [$class, $method] = array_pad(explode('@', $action), 2, '__invoke');
            if (str_starts_with($route->uri(), '_') || ! str_starts_with($class, 'App\\')) {
                continue; // routes de paquets (debugbar, ignition, telescope...)
            }
            $exists = class_exists($class) && method_exists($class, $method);
            if (! $exists && ! in_array($route->getName(), self::KNOWN_ROUTES_WITHOUT_ACTION, true)) {
                $problems[] = $route->methods()[0].' /'.$route->uri()." -> $action";
            }
            if ($exists && in_array($route->getName(), self::KNOWN_ROUTES_WITHOUT_ACTION, true)) {
                $problems[] = 'Entrée périmée dans KNOWN_ROUTES_WITHOUT_ACTION : '.$route->getName();
            }
        }

        $this->assertSame([], $problems);
    }

    public function test_every_static_route_name_used_in_views_exists(): void
    {
        $missing = [];
        foreach ($this->bladeFiles() as $path) {
            // Seuls les appels route('nom.statique') sont vérifiés : les noms construits dynamiquement
            // (route($var), route('a.'.$x), route("a.{$x}")) ne peuvent pas être résolus par regex et sont ignorés.
            preg_match_all('/\broute\(\s*([\'"])([a-zA-Z0-9_.\-]+)\1\s*[,)]/', file_get_contents($path), $m);
            foreach ($m[2] as $name) {
                if (! Route::has($name) && ! in_array($name, self::KNOWN_UNKNOWN_NAMES_IN_VIEWS, true)) {
                    $missing[] = basename($path)." -> $name";
                }
            }
        }

        $this->assertSame([], array_values(array_unique($missing)));
    }

    public function test_admin_menu_has_no_analytics_entry(): void
    {
        $layout = file_get_contents(resource_path('views/layouts/admin.blade.php'));

        $this->assertStringNotContainsStringIgnoringCase('analytics', $layout);
    }

    public function test_dead_files_are_gone_and_audit_table_model_is_kept(): void
    {
        $this->assertFileDoesNotExist(app_path('Http/Controllers/Admin/AnalyticsController.php'));
        $this->assertFileDoesNotExist(resource_path('views/admin/analytics.blade.php'));
        $this->assertFileDoesNotExist(resource_path('views/checkout/verify-phone.blade.php'));
        $this->assertTrue(class_exists(\App\Models\EventAuditLog::class), 'EventAuditLog est conservé pour le futur journal d\'activité.');
    }

    public function test_known_missing_views_list_is_empty(): void
    {
        $list = (new ReflectionClass(ViewReferencesTest::class))->getReflectionConstant('KNOWN_MISSING')->getValue();

        $this->assertSame([], $list);
    }
}
