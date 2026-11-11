<?php

namespace Tests\Feature;

use PHPUnit\Framework\TestCase;

/**
 * Garde-fou de la charte visuelle (étape 1) : jetons, contrastes AA, police embarquée, layouts.
 * Tests sur le contenu des fichiers : aucun accès base ni HTTP.
 */
class CharteVisuelleTest extends TestCase
{
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

    private function token(string $css, string $name): string
    {
        $this->assertMatchesRegularExpression('/--'.preg_quote($name, '/').'\s*:\s*(#[0-9a-fA-F]{6})\s*;/', $css, "Jeton --$name absent ou non hexadécimal");
        preg_match('/--'.preg_quote($name, '/').'\s*:\s*(#[0-9a-fA-F]{6})\s*;/', $css, $m);

        return $m[1];
    }

    private function luminance(string $hex): float
    {
        $c = array_map(function ($i) use ($hex) {
            $v = hexdec(substr($hex, $i, 2)) / 255;

            return $v <= 0.03928 ? $v / 12.92 : (($v + 0.055) / 1.055) ** 2.4;
        }, [1, 3, 5]);

        return 0.2126 * $c[0] + 0.7152 * $c[1] + 0.0722 * $c[2];
    }

    private function contrast(string $a, string $b): float
    {
        $x = $this->luminance($a);
        $y = $this->luminance($b);

        return (max($x, $y) + 0.05) / (min($x, $y) + 0.05);
    }

    public function test_tokens_are_defined(): void
    {
        $css = $this->css();
        foreach (['brand', 'brand-hover', 'brand-soft', 'brand-ink', 'brand-decor', 'brand-rgb', 'ink', 'canvas',
            'state-success', 'state-pending', 'state-failed', 'state-info',
            'state-success-soft', 'state-pending-soft', 'state-failed-soft', 'state-info-soft',
            'radius', 'shadow', 'motion', 'gutter', 'page-max', 'section-gap', 'card-gap', 'field-gap', 'touch-min',
            'space-1', 'space-2', 'space-3', 'space-4', 'space-6', 'space-8', 'space-12'] as $token) {
            $this->assertStringContainsString("--$token:", $css, "Jeton --$token manquant");
        }
        $this->assertStringContainsString('color-scheme: light', $css);
        $this->assertStringContainsString('--motion: 160ms', $css);
        $this->assertStringContainsString('--gutter: var(--space-4)', $css);
        foreach (['4px', '8px', '12px', '16px', '24px', '32px', '48px'] as $i => $px) {
            $n = [1, 2, 3, 4, 6, 8, 12][$i];
            $this->assertStringContainsString("--space-$n: $px;", $css);
        }
        foreach (['.page', '.section', '.stack', '.cluster', '.action-bar', ':focus-visible', '::placeholder'] as $selector) {
            $this->assertStringContainsString($selector, $css, "Règle $selector manquante");
        }
        $this->assertStringContainsString('prefers-reduced-motion: reduce', $css);
    }

    public function test_text_colors_meet_wcag_aa_on_white(): void
    {
        $css = $this->css();
        $pairs = [];
        foreach (['brand', 'brand-hover', 'brand-ink', 'state-success', 'state-pending', 'state-failed', 'state-info', 'ink-muted', 'placeholder'] as $name) {
            $pairs["--$name sur blanc"] = $this->contrast($this->token($css, $name), '#ffffff');
        }
        foreach (['success', 'pending', 'failed', 'info'] as $s) {
            $pairs["--state-$s sur son fond doux"] = $this->contrast($this->token($css, "state-$s"), $this->token($css, "state-$s-soft"));
        }
        $pairs['--brand-ink sur --brand-soft'] = $this->contrast($this->token($css, 'brand-ink'), $this->token($css, 'brand-soft'));
        foreach ($pairs as $label => $ratio) {
            $this->assertGreaterThanOrEqual(4.5, $ratio, "$label : contraste $ratio < 4,5");
        }
    }

    public function test_inter_font_is_embedded(): void
    {
        $css = $this->css();
        $this->assertMatchesRegularExpression('/@font-face\s*\{[^}]*Inter[^}]*font-display:\s*swap[^}]*\}/s', $css);
        preg_match('#url\("\.\./fonts/([^"]+\.woff2)"\)#', $css, $m);
        $this->assertNotEmpty($m, 'Aucune URL woff2 dans @font-face');
        $this->assertFileExists($this->root().'/public/fonts/'.$m[1]);
        $this->assertStringNotContainsString('fonts.googleapis', $css);
    }

    public function test_layouts_include_charte_after_bootstrap_and_before_own_styles(): void
    {
        foreach (['app', 'acheteur', 'promoteur', 'admin'] as $layout) {
            $src = file_get_contents($this->root()."/resources/views/layouts/$layout.blade.php");
            $charte = strpos($src, "asset('css/charte.css')");
            $this->assertNotFalse($charte, "layouts.$layout n'inclut pas charte.css");
            $this->assertGreaterThan(strpos($src, 'bootstrap.min.css'), $charte, "layouts.$layout : charte.css avant Bootstrap");
            $this->assertLessThan(strpos($src, '<style>'), $charte, "layouts.$layout : charte.css après les styles propres");
        }
    }

    public function test_no_hardcoded_decorative_brand_color_in_shared_views(): void
    {
        foreach (['layouts', 'components', 'partials'] as $dir) {
            $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($this->root()."/resources/views/$dir", \FilesystemIterator::SKIP_DOTS));
            foreach ($it as $file) {
                if (! str_ends_with($file->getFilename(), '.php')) {
                    continue;
                }
                $this->assertDoesNotMatchRegularExpression('/#ff6b35/i', file_get_contents($file->getPathname()), $file->getPathname().' contient #FF6B35 en dur');
            }
        }
        // Seule définition autorisée : le jeton décoratif.
        $this->assertSame(1, preg_match_all('/#ff6b35/i', $this->css()));
        $this->assertMatchesRegularExpression('/--brand-decor:\s*#FF6B35/i', $this->css());
    }

    public function test_order_status_badge_uses_state_tokens_with_label(): void
    {
        $src = file_get_contents($this->root().'/resources/views/components/order-status-badge.blade.php');
        $this->assertStringContainsString('badge-state--', $src);
        $this->assertStringContainsString('{{ $label }}', $src);
        $css = $this->css();
        foreach (['success', 'pending', 'failed', 'info'] as $s) {
            $this->assertStringContainsString(".badge-state--$s", $css);
        }
    }
}
