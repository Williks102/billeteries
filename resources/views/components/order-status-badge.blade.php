@props(['order'])
{{-- Badge de statut de paiement d'une commande (libellé + icône + couleur : jamais la couleur seule). Jetons : public/css/charte.css (.badge-state--*). --}}
@php
    [$label, $variant, $icon] = match($order->payment_status) {
        'paid' => ['Payée', 'paid', 'fa-check-circle'],
        'pending', 'processing' => ['En attente de paiement', 'pending', 'fa-clock'],
        'failed' => ['Échec du paiement', 'failed', 'fa-times-circle'],
        'cancelled' => ['Annulée', 'neutral', 'fa-ban'],
        'expired' => ['Expirée', 'neutral', 'fa-hourglass-end'],
        'refunded' => ['Remboursée', 'refunded', 'fa-undo'],
        default => ['Statut inconnu', 'neutral', 'fa-question-circle'],
    };
@endphp
<span {{ $attributes->merge(['class' => 'badge-state badge-state--' . $variant]) }}><i class="fas {{ $icon }}" aria-hidden="true"></i>{{ $label }}</span>
