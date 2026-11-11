@props(['ticket', 'showUsedAt' => false])
@php
    // Pas de donnée personnelle superflue : prénom + initiale du nom, jamais d'email ni de téléphone.
    $fullName = trim((string) optional(optional(optional($ticket->orderItem)->order)->user)->name);
    $parts = preg_split('/\s+/', $fullName, -1, PREG_SPLIT_NO_EMPTY);
    $buyer = $parts
        ? $parts[0] . (count($parts) > 1 ? ' ' . mb_strtoupper(mb_substr(end($parts), 0, 1)) . '.' : '')
        : null;
    $status = $ticket->status;
    $labels = ['used' => ['Utilisé', 'bg-secondary'], 'sold' => ['Valide', 'bg-success'], 'cancelled' => ['Annulé', 'bg-danger']];
    [$statusLabel, $statusClass] = $labels[$status] ?? [ucfirst((string) $status), 'bg-warning text-dark'];
@endphp
<li class="list-group-item py-3">
    <div class="d-flex justify-content-between align-items-start gap-2">
        <div class="min-w-0" style="min-width:0">
            <div class="fw-bold text-break">{{ $ticket->ticket_code }}</div>
            <div class="text-break">{{ optional(optional($ticket->ticketType)->event)->title ?? 'Événement' }}</div>
            <div class="text-muted small">
                {{ optional($ticket->ticketType)->name }}@if($buyer) · {{ $buyer }}@endif
            </div>
            @if($showUsedAt)
                <div class="text-muted small">Scanné le {{ $ticket->updated_at?->format('d/m/Y à H:i') }}</div>
            @endif
        </div>
        <span class="badge {{ $statusClass }} flex-shrink-0">{{ $statusLabel }}</span>
    </div>
</li>
