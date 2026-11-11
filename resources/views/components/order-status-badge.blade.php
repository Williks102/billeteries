@props(['order'])
{{-- Badge de statut de paiement d'une commande (libellé + couleur, lisible sans la couleur seule). --}}
@php
    [$label, $class] = match($order->payment_status) {
        'paid' => ['Payée', 'bg-success'],
        'pending', 'processing' => ['En attente de paiement', 'bg-warning text-dark'],
        'failed' => ['Échec du paiement', 'bg-danger'],
        'cancelled' => ['Annulée', 'bg-secondary'],
        'expired' => ['Expirée', 'bg-secondary'],
        'refunded' => ['Remboursée', 'bg-info text-dark'],
        default => ['Statut inconnu', 'bg-secondary'],
    };
@endphp
<span {{ $attributes->merge(['class' => 'badge ' . $class]) }}>{{ $label }}</span>
