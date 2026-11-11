@props(['info', 'deadline' => null, 'reference' => null, 'amount' => null])
{{-- Instructions de paiement manuel (commande en attente). Coordonnées dans des champs en lecture seule :
     un appui les sélectionne en entier pour les copier, sans JavaScript. --}}
<div class="card mb-4 border-warning" role="region" aria-labelledby="pay-instr-title">
    <div class="card-header bg-warning-subtle">
        <h2 class="h5 mb-0" id="pay-instr-title"><i class="fas fa-hourglass-half me-2" aria-hidden="true"></i>En attente de paiement</h2>
    </div>
    <div class="card-body">
        @if($amount !== null)
            <p class="mb-1"><strong>Montant à régler :</strong> {{ \App\Helpers\CurrencyHelper::formatFCFA($amount) }}</p>
        @endif
        <p class="mb-3"><strong>Mode de paiement :</strong> {{ $info['label'] }}</p>
        <p>{{ $info['instructions'] }}</p>

        @foreach(($info['details'] ?? []) as $label => $value)
            <div class="mb-3">
                <label class="form-label fw-bold mb-1" for="pay-{{ $loop->index }}">{{ $label }}</label>
                <input id="pay-{{ $loop->index }}" type="text" class="form-control" readonly value="{{ $value }}"
                       style="font-size: 16px; min-height: 44px; user-select: all;">
            </div>
        @endforeach
        @if($reference)
            <div class="mb-3">
                <label class="form-label fw-bold mb-1" for="pay-ref">Référence à indiquer</label>
                <input id="pay-ref" type="text" class="form-control" readonly value="{{ $reference }}"
                       style="font-size: 16px; min-height: 44px; user-select: all;">
            </div>
        @endif

        @if($deadline)
            <div class="alert alert-warning mb-0">
                <i class="fas fa-clock me-2" aria-hidden="true"></i>
                À régler avant le <strong>{{ $deadline->format('d/m/Y à H:i') }}</strong>.
                Passé ce délai, votre réservation est libérée.
            </div>
        @endif
        <p class="small text-muted mt-3 mb-0">Votre QR code vous sera remis dès que le paiement aura été confirmé.</p>
    </div>
</div>
