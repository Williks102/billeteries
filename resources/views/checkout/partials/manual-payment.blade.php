{{-- Instructions de paiement manuel (commande en attente de confirmation par l'administrateur) --}}
@php($info = \App\Services\CheckoutService::manualPaymentInfo($order->payment_method))
<div class="card mb-4 border-warning">
    <div class="card-header bg-warning-subtle">
        <h5 class="mb-0"><i class="fas fa-hourglass-half me-2"></i>Paiement en attente de confirmation</h5>
    </div>
    <div class="card-body">
        <p class="mb-1"><strong>Commande :</strong> #{{ $order->order_number }}</p>
        <p class="mb-1"><strong>Montant à régler :</strong> {{ number_format($order->total_amount) }} FCFA</p>
        <p class="mb-3"><strong>Mode de paiement :</strong> {{ $info['label'] }}</p>

        <p>{{ $info['instructions'] }}</p>
        <ul>
            @foreach($info['details'] as $label => $value)
                <li><strong>{{ $label }} :</strong> {{ $value }}</li>
            @endforeach
            <li><strong>Référence à indiquer :</strong> {{ $order->order_number }}</li>
        </ul>

        @if($order->expires_at)
            <div class="alert alert-warning mb-0">
                <i class="fas fa-clock me-2"></i>
                Date limite de paiement : <strong>{{ $order->expires_at->format('d/m/Y à H:i') }}</strong>.
                Passé ce délai, votre réservation est libérée.
            </div>
        @endif

        @if(session('other_order_numbers') && count(session('other_order_numbers')))
            <p class="mt-3 mb-0 small text-muted">
                Autres commandes créées (un événement par commande) : {{ implode(', ', session('other_order_numbers')) }}.
                Les instructions de chacune vous ont été envoyées par email.
            </p>
        @endif
    </div>
</div>
