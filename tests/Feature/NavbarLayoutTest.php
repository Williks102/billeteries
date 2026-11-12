<?php

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Concerns\BuildsOrderSchema;
use Tests\TestCase;

/**
 * Barres de navigation des 4 espaces (public, acheteur, promoteur, admin) :
 *  - analyse du source : hauteur, gouttière, largeurs de menu et z-index viennent des jetons de la charte ;
 *  - le fil d'Ariane sépare l'icône d'accueil et le titre de la page active ;
 *  - bouton de menu mobile nommé et relié au tiroir ;
 *  - chaque layout se rend (200) via une vraie page.
 * Les mesures en pixels (alignement, chevauchements, cibles 44 px) sont dans docs/agents/reports/audit-navbars.md.
 */
class NavbarLayoutTest extends TestCase
{
    use BuildsOrderSchema;

    private const DASHBOARD_LAYOUTS = ['admin', 'promoteur', 'acheteur'];

    private function root(): string
    {
        return dirname(__DIR__, 2);
    }

    private function read(string $relative): string
    {
        $path = $this->root().'/'.$relative;
        $this->assertFileExists($path);

        return file_get_contents($path);
    }

    private function layout(string $name): string
    {
        return $this->read("resources/views/layouts/$name.blade.php");
    }

    /** CSS d'un bloc de la charte dont le sélecteur commence par $selector. */
    private function ruleBody(string $css, string $selector): string
    {
        $this->assertMatchesRegularExpression('/'.preg_quote($selector, '/').'[^{}]*\{([^}]*)\}/', $css, "Règle « $selector » absente de charte.css");
        preg_match('/'.preg_quote($selector, '/').'[^{}]*\{([^}]*)\}/', $css, $m);

        return $m[1];
    }

    public function test_charte_defines_navigation_tokens(): void
    {
        $css = $this->read('public/css/charte.css');

        foreach (['topbar-height', 'sidebar-width', 'sidebar-mini-width', 'avatar-size', 'badge-size', 'z-topbar', 'z-overlay', 'z-drawer'] as $token) {
            $this->assertMatchesRegularExpression('/--'.$token.'\s*:\s*[^;]+;/', $css, "Jeton --$token absent");
        }
        $this->assertGreaterThanOrEqual(44, (int) preg_replace('/\D/', '', $this->tokenValue($css, 'touch-min')));
    }

    private function tokenValue(string $css, string $name): string
    {
        $this->assertMatchesRegularExpression('/--'.preg_quote($name, '/').'\s*:\s*([^;]+);/', $css);
        preg_match('/--'.preg_quote($name, '/').'\s*:\s*([^;]+);/', $css, $m);

        return trim($m[1]);
    }

    public function test_topbar_uses_gutter_and_height_tokens(): void
    {
        $css = $this->read('public/css/charte.css');
        $body = $this->ruleBody($css, '.admin-topbar, .promoteur-topbar, .acheteur-topbar');

        $this->assertStringContainsString('height: var(--topbar-height)', $body);
        $this->assertStringContainsString('padding: 0 var(--gutter)', $body);
        $this->assertStringContainsString('z-index: var(--z-topbar)', $body);
        $this->assertStringContainsString('left: var(--sidebar-width)', $body);

        // Le contenu part sous la barre fixe, avec la même gouttière.
        $this->assertStringContainsString('margin-top: var(--topbar-height)', $this->ruleBody($css, '.admin-main, .promoteur-content'));
        $this->assertMatchesRegularExpression('/\.acheteur-content\s*\{\s*padding:\s*calc\(var\(--topbar-height\) \+ var\(--gutter\)\)/', $css);
    }

    #[DataProvider('dashboardLayouts')]
    public function test_layouts_do_not_redefine_or_copy_navigation_values(string $name): void
    {
        $src = $this->layout($name);

        foreach (['topbar-height', 'sidebar-width', 'sidebar-mini-width'] as $token) {
            $this->assertDoesNotMatchRegularExpression('/--'.$token.'\s*:/', $src, "$name redéfinit --$token (à garder dans charte.css)");
        }
        // Pas de décalage magique recopié (60/280 px) ni de gouttière en dur sur la barre.
        $this->assertDoesNotMatchRegularExpression('/\b(margin-left|left|width)\s*:\s*(60|280)px/', $src, "$name recopie la largeur du menu en dur");
        $this->assertDoesNotMatchRegularExpression('/\bheight\s*:\s*60px/', $src, "$name recopie la hauteur de la barre en dur");
        $this->assertDoesNotMatchRegularExpression('/topbar\s*\{[^}]*padding\s*:\s*0\s+\d+px/', $src, "$name fixe sa propre gouttière");
        $this->assertDoesNotMatchRegularExpression('/z-index\s*:\s*(99\d|10[0-9]\d)\b/', $src, "$name utilise un z-index en dur (jetons --z-*)");
        $this->assertStringContainsString('charte.css', $src);
    }

    #[DataProvider('dashboardLayouts')]
    public function test_mobile_menu_button_is_named_and_linked_to_the_drawer(string $name): void
    {
        $src = $this->layout($name);

        $this->assertMatchesRegularExpression('/<button[^>]*class="mobile-toggle"[^>]*aria-label="Ouvrir le menu"[^>]*>/', $src);
        $this->assertMatchesRegularExpression('/<button[^>]*class="mobile-toggle"[^>]*aria-expanded="false"[^>]*>/', $src);
        $this->assertMatchesRegularExpression('/<button[^>]*class="mobile-toggle"[^>]*aria-controls="'.$name.'Sidebar"[^>]*>/', $src);
        $this->assertStringContainsString('id="'.$name.'Sidebar"', $src);
        $this->assertStringContainsString('id="mobileOverlay"', $src);
    }

    public function test_public_header_menu_button_is_named(): void
    {
        $header = $this->read('resources/views/partials/header.blade.php');

        $this->assertMatchesRegularExpression('/class="navbar-toggler[^"]*"[^>]*aria-controls="navbarNav"[^>]*aria-label="Ouvrir le menu"/', $header);
        $this->assertStringContainsString('.search-container', $header);
        $this->assertDoesNotMatchRegularExpression('/\.search-container\s*\{[^}]*min-width\s*:\s*\d{3}px/', $header, 'La recherche ne doit pas imposer de largeur minimale (retour à la ligne à 1024 px)');
        $this->assertStringContainsString('width: var(--avatar-size)', $header);
    }

    public function test_reduced_motion_is_honoured(): void
    {
        $css = $this->read('public/css/charte.css');

        $this->assertMatchesRegularExpression('/@media \(prefers-reduced-motion: reduce\)\s*\{[^@]*transition-duration:\s*0\.01ms !important/s', $css);
        foreach (self::DASHBOARD_LAYOUTS as $name) {
            // Les durées passent par --motion (ou var(--transition-smooth) qui l'utilise), jamais en secondes en dur.
            $this->assertDoesNotMatchRegularExpression('/transition\s*:[^;]*\b0?\.\d+s\b/', $this->layout($name), "$name : durée de transition en dur");
        }
        $this->assertStringContainsString('--transition-smooth: all var(--motion) ease', $this->layout('acheteur'));
    }

    public function test_breadcrumb_keeps_home_icon_and_page_title_apart(): void
    {
        $css = $this->read('public/css/charte.css');

        // Icône d'accueil : cible >= 44 px, couleur de marque ; séparateur « / » ; titre actif en gras, ellipsis.
        $this->assertStringContainsString('min-width: var(--touch-min)', $this->ruleBody($css, '.topbar-breadcrumb .breadcrumb-item .topbar-home'));
        $this->assertStringContainsString('min-height: var(--touch-min)', $this->ruleBody($css, '.topbar-breadcrumb .breadcrumb-item .topbar-home'));
        $this->assertStringContainsString('content: "/"', $this->ruleBody($css, '.topbar-breadcrumb .breadcrumb-item + .breadcrumb-item::before'));
        $this->assertStringContainsString('padding-left: var(--space-2)', $this->ruleBody($css, '.topbar-breadcrumb .breadcrumb-item + .breadcrumb-item'));
        $active = $this->ruleBody($css, '.topbar-breadcrumb .breadcrumb-item.active, .topbar-breadcrumb [aria-current="page"]');
        foreach (['min-width: 0', 'text-overflow: ellipsis', 'white-space: nowrap', 'font-weight: 600'] as $needle) {
            $this->assertStringContainsString($needle, $active);
        }
        $this->assertStringContainsString('gap: var(--space-3)', $this->ruleBody($css, '.topbar-left, .topbar-breadcrumb'));

        foreach (['admin', 'promoteur'] as $name) {
            $src = $this->layout($name);
            $this->assertMatchesRegularExpression('/<nav class="topbar-breadcrumb" aria-label="[^"]+">\s*<ol class="breadcrumb">/', $src);
            $this->assertMatchesRegularExpression('/class="topbar-home" aria-label="[^"]+"/', $src, "$name : l'icône d'accueil n'a pas de nom accessible");
            $this->assertDoesNotMatchRegularExpression('/\.(topbar-home|topbar-breadcrumb)[^{]*\{[^}]*(position\s*:\s*(absolute|fixed)|margin[^:]*:\s*-)/', $src, "$name superpose icône et titre");
        }
    }

    public function test_active_breadcrumb_items_announce_the_current_page(): void
    {
        $missing = [];
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($this->root().'/resources/views'));
        foreach ($iterator as $file) {
            if (! str_ends_with((string) $file, '.blade.php')) {
                continue;
            }
            $src = file_get_contents((string) $file);
            if (str_contains($src, "section('breadcrumb'") && preg_match('/class="breadcrumb-item active"(?![^>]*aria-current)/', $src)) {
                $missing[] = str_replace($this->root().'/', '', (string) $file);
            }
        }
        $this->assertSame([], $missing, 'Éléments actifs du fil d\'Ariane sans aria-current="page"');
    }

    public function test_sidebar_states_are_shared_and_visible(): void
    {
        $css = $this->read('public/css/charte.css');

        $this->assertStringContainsString('min-height: var(--touch-min)', $this->ruleBody($css, ':is(.admin-sidebar, .promoteur-sidebar, .acheteur-sidebar) .sidebar-menu > li > a'));
        $this->assertStringContainsString('.sidebar-menu > li > a.active', $css);
        $this->assertStringContainsString('.sidebar-menu a:focus-visible', $css);
        $this->assertStringContainsString('z-index: var(--z-overlay)', $this->ruleBody($css, '.mobile-overlay'));
        $this->assertStringContainsString('body.nav-drawer-open', $css);
        $this->assertStringContainsString("e.key === 'Escape'", $this->read('public/js/layout-admin.js'));
        $this->assertStringContainsString("event.key === 'Escape'", $this->read('public/js/layouts-promoteur.js'));
        $this->assertStringContainsString("e.key === 'Escape'", $this->layout('acheteur'));
    }

    public static function dashboardLayouts(): array
    {
        return ['admin' => ['admin'], 'promoteur' => ['promoteur'], 'acheteur' => ['acheteur']];
    }

    public function test_each_layout_renders_through_a_real_page(): void
    {
        $this->buildOrderSchema(); // crée l'acheteur n° 1
        $this->makeUser(2, 'promoteur');
        $admin = $this->makeUser(10, 'admin');

        $admin_page = $this->actingAs($admin)->get(route('admin.settings.index'));
        $admin_page->assertOk()->assertSee('class="admin-topbar"', false)->assertSee('topbar-home', false)->assertSee('aria-label="Ouvrir le menu"', false);

        $this->actingAs(\App\Models\User::find(2))->get(route('promoteur.reports.index'))
            ->assertOk()->assertSee('class="promoteur-topbar"', false)->assertSee('aria-label="Ouvrir le menu"', false);

        $this->actingAs(\App\Models\User::find(1))->get(route('acheteur.orders.index'))
            ->assertOk()->assertSee('class="acheteur-topbar"', false)->assertSee('aria-label="Ouvrir le menu"', false);

        $this->app['auth']->forgetGuards();
        $this->get(route('pages.legal'))
            ->assertOk()->assertSee('navbar-toggler', false)->assertSee('aria-label="Ouvrir le menu"', false);
    }

    public function test_promoteur_account_menu_is_a_real_dropdown_with_logout_and_no_fake_notifications(): void
    {
        $src = file_get_contents(dirname(__DIR__, 2).'/resources/views/layouts/promoteur.blade.php');
        $js = file_get_contents(dirname(__DIR__, 2).'/public/js/layouts-promoteur.js');

        $this->assertStringContainsString('data-bs-toggle="dropdown"', $src, 'le menu du compte se déplie (Bootstrap)');
        $this->assertStringContainsString('aria-expanded="false"', $src);
        $this->assertStringContainsString("route('promoteur.profile.show')", $src);
        $this->assertStringContainsString("route('logout')", $src);
        $this->assertMatchesRegularExpression('/<form[^>]*route\(\'logout\'\)[^>]*>\s*@csrf/s', $src, 'la déconnexion est un POST avec jeton CSRF');
        $this->assertStringContainsString('bootstrap.bundle', $src, 'le script Bootstrap des menus déroulants est chargé');

        // Anciennes coquilles vides : des onclick qui ne faisaient que console.log, et un faux compteur « 3 non lues ».
        $this->assertStringNotContainsString('toggleUserMenu', $src.$js);
        $this->assertStringNotContainsString('showNotifications', $src.$js);
        $this->assertStringNotContainsString('3 non lues', $src);
        $this->assertStringNotContainsString("console.log('Toggle user menu')", $js);
    }

    public function test_no_account_menu_entry_points_to_a_dead_anchor(): void
    {
        foreach (['admin', 'promoteur', 'acheteur'] as $layout) {
            $src = file_get_contents(dirname(__DIR__, 2)."/resources/views/layouts/{$layout}.blade.php");
            $this->assertDoesNotMatchRegularExpression('/class="dropdown-item"\s+href="#"/', $src, "layouts/{$layout} : entrée de menu du compte sans destination");
        }
    }
}

