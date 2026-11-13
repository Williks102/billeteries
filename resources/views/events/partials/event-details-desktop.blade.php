{{-- =============================================== --}}
{{-- resources/views/events/partials/event-details-desktop.blade.php --}}

@php
use Illuminate\Support\Facades\Storage;
@endphp
<div class="event-details">
    @if($event->description)
    <section class="detail-section" aria-labelledby="about-title-desktop">
        <h2 class="detail-section__title" id="about-title-desktop">À propos</h2>
        <div class="event-description">
            {!! nl2br(e($event->description)) !!}
        </div>
    </section>
    @endif

    <x-event-tickets-list :event="$event" suffix="desktop" />
    <x-event-practical-info :event="$event" suffix="desktop" />
    <x-event-share :event="$event" />

    <!-- Événements similaires - Version desktop -->
    @if($similarEvents && $similarEvents->isNotEmpty())
    <div class="detail-card">
        <div class="detail-title">
            <i class="fas fa-heart"></i>
            Dans la même catégorie
        </div>
        <div class="row">
            @foreach($similarEvents->take(3) as $similar)
            <div class="col-md-4">
                <div class="similar-event-card border rounded p-3 h-100">
                    @if($similar->image)
                    <img src="{{ \App\Support\Media::url($similar->image) }}" 
                         alt="{{ $similar->title }}"
                         class="w-100 rounded mb-3"
                         style="height: 120px; object-fit: cover;">
                    @else
                    <div class="bg-light rounded d-flex align-items-center justify-content-center mb-3"
                         style="height: 120px;">
                        <i class="fas fa-calendar-alt fa-2x text-muted"></i>
                    </div>
                    @endif
                    
                    <h6 class="fw-bold mb-2">{{ Str::limit($similar->title, 50) }}</h6>
                    <div class="text-muted small mb-2">
                        <i class="fas fa-calendar me-1"></i>{{ $similar->formatted_event_date }}
                        <br>
                        <i class="fas fa-map-marker-alt me-1"></i>{{ $similar->venue }}
                    </div>
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="text-primary fw-bold">
                            {{ \App\Helpers\CurrencyHelper::formatFCFA($similar->ticketTypes->min('price')) }}
                        </div>
                        <a href="{{ route('events.show', $similar) }}" 
                           class="btn btn-outline-primary btn-sm">
                            Voir
                        </a>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif
</div>