{{-- =============================================== --}}
{{-- resources/views/events/partials/event-details-mobile.blade.php --}}

@php
use Illuminate\Support\Facades\Storage;
@endphp
<div class="event-details">
    <!-- Description -->
    @if($event->description)
    <section class="detail-section" aria-labelledby="about-title-mobile">
        <h2 class="detail-section__title" id="about-title-mobile">À propos</h2>
        <div class="event-description">
            {!! nl2br(e($event->description)) !!}
        </div>
    </section>
    @endif

    <x-event-tickets-list :event="$event" suffix="mobile" />
    <x-event-practical-info :event="$event" suffix="mobile" />
    <x-event-share :event="$event" />

    <!-- Événements similaires -->
    @if($similarEvents && $similarEvents->isNotEmpty())
    <div class="detail-card">
        <div class="detail-title">
            <i class="fas fa-heart"></i>
            Vous pourriez aussi aimer
        </div>
        <div class="similar-events">
            @foreach($similarEvents->take(2) as $similar)
            <div class="similar-event d-flex align-items-center p-2 border rounded mb-2">
                <div class="similar-event-image me-3">
                    @if($similar->image)
                    <img src="{{ Storage::url( $similar->image) }}" 
                         alt="{{ $similar->title }}"
                         class="rounded"
                         style="width: 60px; height: 60px; object-fit: cover;">
                    @else
                    <div class="bg-light rounded d-flex align-items-center justify-content-center"
                         style="width: 60px; height: 60px;">
                        <i class="fas fa-calendar-alt text-muted"></i>
                    </div>
                    @endif
                </div>
                <div class="flex-grow-1">
                    <div class="fw-bold small">{{ Str::limit($similar->title, 40) }}</div>
                    <div class="text-muted small">
                        {{ $similar->formatted_event_date }} • {{ $similar->venue }}
                    </div>
                    <div class="text-primary small fw-bold">
                        À partir de {{ \App\Helpers\CurrencyHelper::formatFCFA($similar->ticketTypes->min('price')) }}
                    </div>
                </div>
                <a href="{{ route('events.show', $similar) }}" 
                   class="btn btn-outline-primary btn-sm">
                    <i class="fas fa-eye"></i>
                </a>
            </div>
            @endforeach
        </div>
    </div>
    @endif
</div>
