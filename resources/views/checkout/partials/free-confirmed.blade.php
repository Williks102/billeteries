{{-- Commande gratuite confirmée (total 0 FCFA) : état confirmé, aucun paiement, aucune instruction ni décompte.
     Variables : $order ; $showTickets (bool) : afficher les billets et leurs QR (propriétaire de la commande). --}}
<div class="text-center mb-4">
    <i class="fas fa-check-circle text-success" style="font-size: 4rem;" aria-hidden="true"></i>
    <h1 class="text-success">Réservation confirmée</h1>
    <p class="mb-2"><span class="badge-state badge-state--paid"><i class="fas fa-gift" aria-hidden="true"></i>Commande gratuite</span></p>
    <p class="lead">Vos billets sont émis. Aucun paiement n'est nécessaire.</p>
</div>

@if(!empty($showTickets))
    <section class="parcours-card mb-4" aria-labelledby="free-tickets-title">
        <h2 class="parcours-card__title" id="free-tickets-title"><i class="fas fa-qrcode" aria-hidden="true"></i>Vos billets</h2>
        <div class="stack">
            @foreach($order->tickets as $ticket)
                <div class="border rounded p-3 text-center">
                    <p class="fw-bold mb-1">{{ $ticket->ticketType->name ?? 'Billet' }}</p>
                    <p class="mb-2"><code>{{ $ticket->ticket_code }}</code></p>
                    @php($qr = $ticket->qrDataUri())
                    @if($qr)
                        <img src="{{ $qr }}" alt="QR code du billet {{ $ticket->ticket_code }}" width="200" height="200" class="img-fluid">
                    @endif
                </div>
            @endforeach
        </div>
    </section>
@endif

@if(session('other_order_numbers') && count(session('other_order_numbers')))
    <p class="mb-4 small text-muted">
        Autres commandes créées (un événement par commande) : {{ implode(', ', session('other_order_numbers')) }}.
    </p>
@endif
