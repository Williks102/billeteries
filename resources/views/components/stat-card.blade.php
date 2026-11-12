{{--
    Carte de chiffre unique pour tous les écrans de statistiques.
    Props : label (obligatoire), value (int|float|string|null), unit ('FCFA' | '%' | ''), hint, delta (variation en %, optionnelle).
    - value null => « — » avec title « non calculable » (jamais un chiffre inventé).
    - FCFA : montant entier via CurrencyHelper (0 s'affiche « 0 FCFA », jamais « Gratuit »).
    - % : une décimale, virgule française.
    - delta : icône + texte (jamais la couleur seule) ; une valeur négative n'a JAMAIS de flèche vers le haut.
--}}
@props(['label', 'value' => null, 'unit' => '', 'hint' => null, 'delta' => null])
@php
    $nbsp = \App\Helpers\CurrencyHelper::NBSP;
    $id = 'stat-'.\Illuminate\Support\Str::random(8);
    $computable = $value !== null && $value !== '';

    if (! $computable) {
        $display = null;
    } elseif (is_string($value) && ! is_numeric($value)) {
        $display = $value;
    } elseif ($unit === 'FCFA') {
        $display = \App\Helpers\CurrencyHelper::formatFCFA((int) round((float) $value));
    } elseif ($unit === '%') {
        $display = number_format((float) $value, 1, ',', $nbsp).$nbsp.'%';
    } else {
        $display = \App\Helpers\CurrencyHelper::formatNumber($value).($unit !== '' ? $nbsp.$unit : '');
    }

    $deltaState = null;
    if ($delta !== null) {
        $deltaState = $delta > 0 ? 'up' : ($delta < 0 ? 'down' : 'flat');
        $deltaIcon = ['up' => 'fa-arrow-trend-up', 'down' => 'fa-arrow-trend-down', 'flat' => 'fa-minus'][$deltaState];
        $deltaWord = ['up' => 'en hausse', 'down' => 'en baisse', 'flat' => 'stable'][$deltaState];
        $deltaText = ($delta > 0 ? '+' : ($delta < 0 ? '−' : '')).number_format(abs((float) $delta), 1, ',', $nbsp).$nbsp.'%';
    }
@endphp
<div {{ $attributes->class(['kpi-card']) }} role="group" aria-labelledby="{{ $id }}-label" data-stat="{{ \Illuminate\Support\Str::slug($label) }}">
    <p class="kpi-card__label" id="{{ $id }}-label">{{ $label }}</p>
    <p class="kpi-card__value">
        @if($computable)
            {{ $display }}
        @else
            <span title="non calculable" aria-label="non calculable">—</span>
        @endif
    </p>
    @if($deltaState)
        <p class="kpi-card__delta kpi-card__delta--{{ $deltaState }}">
            <i class="fas {{ $deltaIcon }}" aria-hidden="true"></i>
            <span>{{ $deltaText }} <span class="visually-hidden">({{ $deltaWord }})</span></span>
        </p>
    @endif
    @if($hint)
        <p class="kpi-card__hint">{{ $hint }}</p>
    @endif
</div>
