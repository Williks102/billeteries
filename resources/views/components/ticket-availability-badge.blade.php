@props(['remaining', 'threshold' => null])
{{-- Badge de disponibilité d'un billet (libellé + icône + couleur : jamais la couleur seule). Affichage seulement : aucune logique de stock.
     Seuil « bientôt complet » : config('payments.low_stock_threshold', 10). Jetons : public/css/charte.css (.badge-state--available|low|soldout). --}}
@php
    $remaining = max(0, (int) $remaining);
    $threshold = (int) ($threshold ?? config('payments.low_stock_threshold', 10));
    if ($remaining <= 0) {
        [$variant, $icon, $label] = ['soldout', 'fa-times-circle', 'Complet'];
    } elseif ($remaining <= $threshold) {
        [$variant, $icon, $label] = ['low', 'fa-exclamation-triangle', 'Bientôt complet · ' . $remaining . ' ' . ($remaining > 1 ? 'restants' : 'restant')];
    } else {
        [$variant, $icon, $label] = ['available', 'fa-check-circle', 'Disponible'];
    }
@endphp
<span {{ $attributes->merge(['class' => 'badge-state badge-state--' . $variant]) }}><i class="fas {{ $icon }}" aria-hidden="true"></i>{{ $label }}</span>
