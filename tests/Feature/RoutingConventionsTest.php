<?php

namespace Tests\Feature;

use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Routing\RouteCollection;
use Tests\TestCase;

/**
 * Garde-fous sur la structure des routes (convention ressource Laravel).
 * Les listes « DETTE CONNUE » recensent des défauts antérieurs au refactor des routes :
 * elles ne doivent que diminuer (retirer l'entrée quand le défaut est corrigé).
 */
class RoutingConventionsTest extends TestCase
{
    /** DETTE CONNUE : routes dont la méthode de contrôleur n'existe pas. */
    private const MISSING_ACTIONS = [
        'acheteur.favorites', 'acheteur.favorites.add', 'acheteur.favorites.remove',
    ];

    /** DETTE CONNUE : noms de route utilisés dans le code mais non déclarés. */
    private const UNKNOWN_NAMES = [
        'checkout.payment-method', 'checkout.resend-code', 'contact',
        'orders.show', 'pages.show', 'verification.resend',
    ];

    /** Closures tolérées (redirections triviales). */
    private const ALLOWED_CLOSURES = ['up', 'storage.local', 'dashboard', 'checkout.choose', 'profile', 'admin.promoters.show'];

    private function appRoutes(): RouteCollection
    {
        return app('router')->getRoutes();
    }

    private function eachAppRoute(): iterable
    {
        foreach ($this->appRoutes() as $route) {
            if (! str_starts_with($route->uri(), '_debugbar') && ! str_starts_with($route->uri(), '_ignition')) {
                yield $route;
            }
        }
    }

    public function test_no_route_is_shadowed_by_a_wildcard_route(): void
    {
        $problems = [];
        foreach ($this->eachAppRoute() as $route) {
            $uri = preg_replace('/\{[^}?]+\??\}/', '7', $route->uri());
            foreach (array_diff($route->methods(), ['HEAD']) as $method) {
                $matched = $this->appRoutes()->match(Request::create('/'.ltrim($uri, '/'), $method));
                if ($matched->getActionName() !== $route->getActionName() || $matched->uri() !== $route->uri()) {
                    $problems[] = "$method /{$route->uri()} masquée par /{$matched->uri()}";
                }
            }
        }
        $this->assertSame([], $problems);
    }

    public function test_route_names_are_unique(): void
    {
        $seen = [];
        foreach ($this->eachAppRoute() as $route) {
            if ($name = $route->getName()) {
                $seen[$name][] = $route->uri();
            }
        }
        $dups = array_filter($seen, fn ($uris) => count($uris) > 1);
        $this->assertSame([], $dups);
    }

    public function test_every_route_points_to_an_existing_controller_method(): void
    {
        $missing = [];
        foreach ($this->eachAppRoute() as $route) {
            $action = $route->getActionName();
            if ($action === 'Closure' || ! str_contains($action, '@')) {
                continue;
            }
            [$class, $method] = explode('@', $action);
            if ((! class_exists($class) || ! method_exists($class, $method)) && ! in_array($route->getName(), self::MISSING_ACTIONS, true)) {
                $missing[] = "{$route->getName()} -> $action";
            }
        }
        $this->assertSame([], $missing);
    }

    public function test_no_business_logic_closures_in_routes(): void
    {
        $closures = [];
        foreach ($this->eachAppRoute() as $route) {
            if ($route->getActionName() === 'Closure' && ! in_array($route->getName() ?? $route->uri(), self::ALLOWED_CLOSURES, true)) {
                $closures[] = $route->getName() ?? $route->uri();
            }
        }
        $this->assertSame([], $closures);
    }

    public function test_route_names_referenced_in_code_exist(): void
    {
        $names = array_filter(array_map(fn (Route $r) => $r->getName(), iterator_to_array($this->appRoutes())));
        $names = array_flip($names);
        $unknown = [];

        foreach (['app', 'resources/views'] as $dir) {
            $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(base_path($dir)));
            foreach ($it as $file) {
                $path = $file->getPathname();
                if (! $file->isFile() || str_contains($path, '.old') || str_contains($path, '.backup')) {
                    continue;
                }
                preg_match_all('/(?:route|to_route|routeIs|Route::has)\(\s*[\'"]([a-zA-Z0-9_.\-*]+)[\'"]/', file_get_contents($path), $m);
                foreach ($m[1] as $name) {
                    $ok = str_contains($name, '*')
                        ? (bool) preg_grep('/^'.str_replace('\*', '.*', preg_quote($name, '/')).'$/', array_keys($names))
                        : isset($names[$name]);
                    if (! $ok && ! in_array($name, self::UNKNOWN_NAMES, true)) {
                        $unknown[$name] = str_replace(base_path().'/', '', $path);
                    }
                }
            }
        }
        $this->assertSame([], $unknown);
    }

    public function test_routes_can_be_cached(): void
    {
        $this->artisan('route:cache')->assertSuccessful();
        $this->artisan('route:clear')->assertSuccessful();
    }
}
