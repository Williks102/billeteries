@props(['status'])
{{-- Badge de statut d'un billet (libellé + icône + couleur : jamais la couleur seule). Jetons : public/css/charte.css (.badge-state--*). --}}
@php
    [$label, $variant, $icon] = match($status) {
        'available' => ['Disponible', 'neutral', 'fa-circle'],
        'reserved' => ['Réservé', 'pending', 'fa-clock'],
        'sold' => ['Vendu', 'paid', 'fa-check-circle'],
        'used' => ['Utilisé', 'info', 'fa-qrcode'],
        'cancelled' => ['Annulé', 'failed', 'fa-ban'],
        default => ['Statut inconnu', 'neutral', 'fa-question-circle'],
    };
@endphp
<span {{ $attributes->merge(['class' => 'badge-state badge-state--' . $variant]) }}><i class="fas {{ $icon }}" aria-hidden="true"></i>{{ $label }}</span>
