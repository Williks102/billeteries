@props(['event'])
@php
    $minPrice = $event->ticketTypes->min('price');
    $maxPrice = $event->ticketTypes->max('price');
    // Affichage seulement (D6) : places restantes publiques, calculées par Event::remainingPlaces (types déjà chargés)
    $remainingTotal = $event->remainingPlaces();
@endphp
<article class="card h-100 shadow-sm border-0">
    @if($event->image)
        <img src="{{ \Illuminate\Support\Facades\Storage::url($event->image) }}" class="card-img-top" alt="{{ $event->title }}" loading="lazy" style="aspect-ratio:16/9;object-fit:cover;">
    @endif
    <div class="card-body">
        @if($event->category)
            <span class="badge bg-primary mb-2">{{ $event->category->name }}</span>
        @endif
        <h2 class="h6 card-title mb-2">
            <a href="{{ route('events.show', $event) }}" class="text-decoration-none stretched-link">{{ \Illuminate\Support\Str::limit($event->title, 60) }}</a>
        </h2>
        <p class="small text-muted mb-1">
            {{ optional($event->event_date)->format('d/m/Y') }}@if($event->event_time) à {{ $event->event_time->format('H:i') }}@endif
        </p>
        @if($event->venue)
            <p class="small text-muted mb-2">{{ \Illuminate\Support\Str::limit($event->venue, 40) }}</p>
        @endif
        @if($minPrice !== null && $remainingTotal <= config('payments.low_stock_threshold', 10))
            <p class="mb-2"><x-ticket-availability-badge :remaining="$remainingTotal" /></p>
        @endif
        @if($minPrice !== null)
            <p class="fw-bold text-primary mb-0">
                @if($minPrice == $maxPrice)
                    {{ \App\Helpers\CurrencyHelper::formatFCFA($minPrice) }}
                @else
                    À partir de {{ \App\Helpers\CurrencyHelper::formatFCFA($minPrice) }}
                @endif
            </p>
        @endif
    </div>
</article>
