<?php

namespace Tests\Feature\Stats;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

/** <x-stat-card> et <x-stat-grid> : valeurs non calculables, unités, deltas, accessibilité. */
class StatCardComponentTest extends TestCase
{
    private function render(string $tpl): string
    {
        return Blade::render($tpl);
    }

    private function text(string $html): string
    {
        return trim(preg_replace('/\s+/u', ' ', str_replace("\u{00A0}", ' ', html_entity_decode(strip_tags($html)))));
    }

    public function test_null_value_shows_dash_with_not_computable_title(): void
    {
        $html = $this->render('<x-stat-card label="Taux de paiement" unit="%" :value="null" />');

        $this->assertStringContainsString('—', $html);
        $this->assertStringContainsString('title="non calculable"', $html);
        $this->assertStringContainsString('aria-label="non calculable"', $html);
        $this->assertStringNotContainsString('0,0', $html);
    }

    public function test_units_amounts_and_percentages(): void
    {
        // un revenu de 0 s'affiche « 0 FCFA », jamais « Gratuit »
        $zero = $this->render('<x-stat-card label="Ventes" unit="FCFA" :value="0" />');
        $this->assertStringContainsString('0 FCFA', $this->text($zero));
        $this->assertStringNotContainsString('Gratuit', $zero);

        $this->assertStringContainsString('10 000 FCFA', $this->text($this->render('<x-stat-card label="Ventes" unit="FCFA" :value="10000" />')));
        $this->assertStringContainsString("10\u{00A0}000\u{00A0}FCFA", $this->render('<x-stat-card label="Ventes" unit="FCFA" :value="10000" />'));
        $this->assertStringContainsString('16,7 %', $this->text($this->render('<x-stat-card label="Taux" unit="%" :value="16.67" />')));
        $this->assertStringContainsString('50,0 %', $this->text($this->render('<x-stat-card label="Taux" unit="%" :value="50" />')));
        $this->assertStringContainsString('1 250', $this->text($this->render('<x-stat-card label="Billets" :value="1250" />')));
        $this->assertStringContainsString('Du 01/10', $this->text($this->render('<x-stat-card label="Période" value="Du 01/10" />')));
    }

    public function test_negative_delta_never_shows_an_upward_arrow_and_is_not_colour_only(): void
    {
        $down = $this->render('<x-stat-card label="Ventes" unit="FCFA" :value="100" :delta="-12.5" />');
        $this->assertStringNotContainsString('fa-arrow-trend-up', $down);
        $this->assertStringNotContainsString('kpi-card__delta--up', $down);
        $this->assertStringContainsString('fa-arrow-trend-down', $down);
        $this->assertStringContainsString('−12,5 %', $this->text($down));   // texte signé
        $this->assertStringContainsString('en baisse', $down);               // et mot, pas seulement la couleur

        $up = $this->render('<x-stat-card label="Ventes" unit="FCFA" :value="100" :delta="3" />');
        $this->assertStringContainsString('fa-arrow-trend-up', $up);
        $this->assertStringContainsString('+3,0 %', $this->text($up));
        $this->assertStringContainsString('en hausse', $up);

        $flat = $this->render('<x-stat-card label="Ventes" unit="FCFA" :value="100" :delta="0" />');
        $this->assertStringNotContainsString('fa-arrow-trend-up', $flat);
        $this->assertStringContainsString('stable', $flat);

        $none = $this->render('<x-stat-card label="Ventes" unit="FCFA" :value="100" />');
        $this->assertStringNotContainsString('kpi-card__delta', $none);
    }

    public function test_card_is_an_accessible_labelled_group(): void
    {
        $html = $this->render('<x-stat-card label="Commission plateforme" unit="FCFA" :value="3300" hint="Revenu de la plateforme" />');

        $this->assertMatchesRegularExpression('/role="group" aria-labelledby="(stat-[A-Za-z0-9]+-label)"/', $html);
        preg_match('/aria-labelledby="([^"]+)"/', $html, $m);
        $this->assertStringContainsString('id="'.$m[1].'"', $html);
        $this->assertStringContainsString('Revenu de la plateforme', $html);
        $this->assertStringNotContainsString('style=', $html, 'pas de style en ligne : jetons de charte.css');
    }

    public function test_grid_wraps_cards_with_responsive_classes(): void
    {
        $html = $this->render('<x-stat-grid label="Chiffres"><x-stat-card label="A" :value="1" /></x-stat-grid>');
        $this->assertStringContainsString('class="stat-grid stat-grid--4"', $html);
        $this->assertStringContainsString('aria-label="Chiffres"', $html);
        $this->assertStringContainsString('kpi-card', $html);
        $this->assertStringContainsString('stat-grid--3', $this->render('<x-stat-grid cols="3"></x-stat-grid>'));

        $css = file_get_contents(public_path('css/charte.css'));
        $this->assertMatchesRegularExpression('/\.stat-grid\s*\{[^}]*grid-template-columns:\s*minmax\(0, 1fr\)/', $css);   // 1 colonne par défaut (390 px)
        $this->assertStringContainsString('@media (min-width: 576px) { .stat-grid { grid-template-columns: repeat(2', $css);
        $this->assertStringContainsString('var(--card-gap)', $css);
    }
}
