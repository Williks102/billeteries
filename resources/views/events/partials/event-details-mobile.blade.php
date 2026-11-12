{{-- =============================================== --}}
{{-- resources/views/events/partials/event-details-mobile.blade.php --}}

@php
use Illuminate\Support\Facades\Storage;
@endphp
<div class="event-details">
    <!-- Description -->
    @if($event->description)
    <section class="detail-section" aria-labelledby="about-title">
        <h2 class="detail-section__title" id="about-title">À propos</h2>
        <div class="event-description">
            {!! nl2br(e($event->description)) !!}
        </div>
    </section>
    @endif

    <!-- Informations pratiques -->
    <section class="detail-section detail-section--practical" aria-labelledby="practical-title">
        <h2 class="detail-section__title" id="practical-title">Informations pratiques</h2>
        <ul class="info-list">
            <li class="info-row">
                <span class="info-row__icon"><i class="fas fa-calendar-alt" aria-hidden="true"></i></span>
                <div class="info-row__body">
                    <span class="info-row__label">{{ $event->long_date_label }}</span>
                    @if($event->time_range_label !== '')
                    <span class="info-row__value">{{ $event->time_range_label }}</span>
                    @endif
                </div>
            </li>
            <li class="info-row">
                <span class="info-row__icon"><i class="fas fa-map-marker-alt" aria-hidden="true"></i></span>
                <div class="info-row__body">
                    <span class="info-row__label">{{ $event->venue }}</span>
                    @if($event->address)
                    <span class="info-row__value">{{ $event->address }}</span>
                    @endif
                </div>
            </li>
            <li class="info-row">
                <span class="info-row__icon"><i class="fas fa-user-tie" aria-hidden="true"></i></span>
                <div class="info-row__body">
                    <span class="info-row__label">Organisé par {{ $event->promoteur->name ?? 'ClicBillet CI' }}</span>
                </div>
            </li>
            @if($event->ticketTypes->isNotEmpty())
            <li class="info-row">
                <span class="info-row__icon"><i class="fas fa-ticket-alt" aria-hidden="true"></i></span>
                <div class="info-row__body">
                    <span class="info-row__label">{{ $event->placesLabel() }}</span>
                </div>
            </li>
            @endif
        </ul>
    </section>

    <!-- Partage social -->
    <div class="detail-card">
        <div class="detail-title">
            <i class="fas fa-share-alt"></i>
            Partager cet événement
        </div>
        <div class="social-share d-flex gap-2">
            <a href="https://www.facebook.com/sharer/sharer.php?u={{ urlencode(request()->url()) }}" 
               target="_blank" 
               class="btn btn-outline-primary btn-sm flex-fill">
                <i class="fab fa-facebook-f me-1"></i>Facebook
            </a>
            <a href="https://twitter.com/intent/tweet?url={{ urlencode(request()->url()) }}&text={{ urlencode($event->title) }}" 
               target="_blank" 
               class="btn btn-outline-info btn-sm flex-fill">
                <i class="fab fa-twitter me-1"></i>Twitter
            </a>
            <a href="whatsapp://send?text={{ urlencode($event->title . ' - ' . request()->url()) }}" 
               class="btn btn-outline-success btn-sm flex-fill">
                <i class="fab fa-whatsapp me-1"></i>WhatsApp
            </a>
        </div>
    </div>
    
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
