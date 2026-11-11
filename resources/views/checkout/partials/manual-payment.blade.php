{{-- Instructions de paiement manuel (commande en attente). Variables fournies par le contrôleur :
     $order, $paymentInfo, $paymentDeadline. --}}
<p class="mb-1 text-center"><strong>Commande :</strong> #{{ $order->order_number }}</p>
<x-payment-instructions :info="$paymentInfo" :deadline="$paymentDeadline" :reference="$order->order_number" :amount="$order->total_amount" />

@if(session('other_order_numbers') && count(session('other_order_numbers')))
    <p class="mb-4 small text-muted">
        Autres commandes créées (un événement par commande) : {{ implode(', ', session('other_order_numbers')) }}.
        Les instructions de chacune vous ont été envoyées par email.
    </p>
@endif
