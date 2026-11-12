<?php

namespace Tests\Feature;

use PHPUnit\Framework\TestCase;

/**
 * Garde-fou des barres d'actions de formulaire : une seule règle (.action-bar dans charte.css),
 * markup commun dans les vues migrées. Tests sur le contenu des fichiers : aucun accès base ni HTTP.
 */
class ActionBarsTest extends TestCase
{
    /** Vues migrées => nombre attendu de <button type="submit"> (inchangé par la migration). */
    private const MIGRATED = [
        'acheteur/profile.blade.php' => 1,
        'admin/emails/dashboard.blade.php' => 1,
        'admin/orders/edit.blade.php' => 2,
        'admin/profile.blade.php' => 1,
        'admin/users/create.blade.php' => 2,
        'admin/users/edit.blade.php' => 1,
        'components/admin/category-form.blade.php' => 1,
        'pages/contact.blade.php' => 1,
        'pages/support.blade.php' => 1,
        'events/_form.blade.php' => 3,
        'promoteur/events/tickets/_form.blade.php' => 1,
        'promoteur/profile.blade.php' => 2,
    ];

    private function root(): string
    {
        return dirname(__DIR__, 2);
    }

    private function css(): string
    {
        $path = $this->root().'/public/css/charte.css';
        $this->assertFileExists($path);

        return file_get_contents($path);
    }

    private function view(string $relative): string
    {
        $path = $this->root().'/resources/views/'.$relative;
        $this->assertFileExists($path);

        return file_get_contents($path);
    }

    public function test_charte_defines_action_bar_and_variants(): void
    {
        $css = $this->css();
        foreach (['.action-bar {', '.action-bar--split', '.action-bar--sticky', '.action-bar__cancel'] as $selector) {
            $this->assertStringContainsString($selector, $css, "$selector absent de charte.css");
        }
    }

    public function test_action_bar_is_stacked_full_width_on_mobile_and_a_row_from_576px(): void
    {
        $css = $this->css();
        preg_match('/\.action-bar\s*\{([^}]*)\}/', $css, $base);
        $this->assertStringContainsString('flex-direction: column', $base[1] ?? '', 'Empilé par défaut (mobile)');
        preg_match('/\.action-bar > \.btn\s*\{([^}]*)\}/', $css, $btn);
        $this->assertStringContainsString('width: 100%', $btn[1] ?? '');
        $this->assertStringContainsString('min-height: var(--touch-min)', $btn[1] ?? '');
        $this->assertMatchesRegularExpression('/--touch-min:\s*44px/', $css);
        $this->assertStringContainsString('inline-flex', $btn[1] ?? '', 'Icône et texte alignés');
        $this->assertMatchesRegularExpression('/@media \(min-width: 576px\)\s*\{[^@]*\.action-bar\s*\{[^}]*flex-direction: row-reverse[^}]*\}/', $css);
        $this->assertMatchesRegularExpression('/@media \(min-width: 576px\)\s*\{[^@]*\.action-bar > \.btn\s*\{\s*width: auto/', $css);
    }

    public function test_sticky_is_a_variant_only_below_576px_not_the_default(): void
    {
        $css = $this->css();
        preg_match('/\.action-bar\s*\{([^}]*)\}/', $css, $base);
        $this->assertStringNotContainsString('sticky', $base[1] ?? '');
        $this->assertMatchesRegularExpression('/@media \(max-width: 575\.98px\)\s*\{\s*\.action-bar--sticky\s*\{[^}]*position: sticky/', $css);
    }

    public function test_spacing_and_colors_use_charte_tokens_only(): void
    {
        $css = $this->css();
        $start = strpos($css, "Barre d'actions de formulaire");
        $end = strpos($css, '/* Tableaux et cartes responsives */');
        $block = substr($css, $start, $end - $start);
        $this->assertStringContainsString('var(--space-3)', $block);
        $this->assertDoesNotMatchRegularExpression('/#[0-9a-fA-F]{3,6}\b/', $block, 'Aucune couleur en dur dans .action-bar');
    }

    public function test_migrated_views_use_the_common_markup_without_the_faulty_pattern(): void
    {
        foreach (self::MIGRATED as $file => $submitCount) {
            $src = $this->view($file);
            $this->assertStringContainsString('class="action-bar', $src, "$file : .action-bar absent");
            $this->assertSame($submitCount, substr_count($src, 'type="submit"'), "$file : nombre de boutons submit modifié");

            preg_match_all('/<div class="action-bar[^"]*"[^>]*>(.*?)<\/div>/s', $src, $bars);
            $this->assertNotEmpty($bars[1], "$file : barre introuvable");
            foreach ($bars[1] as $bar) {
                $this->assertStringNotContainsString('<div', $bar, "$file : div imbriqué dans la barre");
                $this->assertStringNotContainsString('justify-content-between', $bar, $file);
                $this->assertStringNotContainsString('float-end', $bar, $file);
                $this->assertStringNotContainsString('text-end', $bar, $file);
                $withoutIcons = preg_replace('/<i\b[^>]*>/', '', $bar);
                $this->assertStringNotContainsString('me-2', $withoutIcons, "$file : me-2 sur un bouton de la barre");
            }
        }
    }

    public function test_create_user_keeps_its_three_actions(): void
    {
        $src = $this->view('admin/users/create.blade.php');
        $this->assertStringContainsString('name="action" value="save_and_notify"', $src);
        $this->assertStringContainsString('name="action" value="save"', $src);
        $this->assertStringContainsString('onclick="history.back()"', $src);
        $this->assertStringContainsString('action-bar action-bar--split', $src);

        $events = $this->view('events/_form.blade.php'); // le formulaire admin de création délègue au partial partagé
        $this->assertStringContainsString('name="action" value="draft"', $events);
        $this->assertStringContainsString('name="action" value="publish"', $events);
    }

    public function test_ticket_form_uses_sticky_variant_and_no_local_action_css(): void
    {
        $src = $this->view('promoteur/events/tickets/_form.blade.php');
        $this->assertStringContainsString('action-bar action-bar--sticky', $src);
        $this->assertStringNotContainsString('tt-actions', $src);
    }
}
