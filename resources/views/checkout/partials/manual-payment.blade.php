{{-- Instructions de paiement manuel (commande en attente). Variables fournies par le contrôleur :
     $order, $paymentInfo, $paymentDeadline. --}}
<p class="mb-1 text-center"><strong>Commande :</strong> #{{ $order->order_number }}</p>
<x-payment-instructions :info="$paymentInfo" :deadline="$paymentDeadline" :reference="$order->order_number" :amount="$order->total_amount" />

@if(session('other_free_orders') && count(session('other_free_orders')))
    <div class="alert alert-success small" role="status">
        <i class="fas fa-check-circle me-1" aria-hidden="true"></i>
        @foreach(session('other_free_orders') as $free)
            Votre commande gratuite #{{ $free['number'] }} est déjà émise :
            <a href="{{ $free['url'] }}">voir mes billets gratuits</a>.@if(!$loop->last)<br>@endif
        @endforeach
    </div>
@endif

@if(session('other_order_numbers') && count(session('other_order_numbers')))
    <p class="mb-4 small text-muted">
        Autres commandes créées (un événement par commande) : {{ implode(', ', session('other_order_numbers')) }}.
        Chaque commande a ses propres instructions de paiement.
    </p>
@endif
