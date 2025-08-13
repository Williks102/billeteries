{{-- =============================================== --}}
{{-- resources/views/events/partials/event-details-mobile.blade.php --}}

@php
use Illuminate\Support\Facades\Storage;
@endphp
<div class="event-details">
    <!-- Description -->
    @if($event->description)
    <div class="detail-card">
        <div class="detail-title">
            <i class="fas fa-info-circle"></i>
            À propos de l'événement
        </div>
        <div class="event-description">
            {!! nl2br(e($event->description)) !!}
        </div>
    </div>
    @endif
    
    <!-- Informations pratiques -->
    <div class="detail-card">
        <div class="detail-title">
            <i class="fas fa-map-marker-alt"></i>
            Informations pratiques
        </div>
        <div class="info-grid">
            <div class="info-item mb-3">
                <div class="d-flex align-items-center">
                    <i class="fas fa-calendar-alt text-primary me-3"></i>
                    <div>
                        <div class="fw-bold">Date et heure</div>
                        <div class="text-muted">{{ $event->formatted_event_date }} à {{ $event->event_time ?? '20h00' }}</div>
                    </div>
                </div>
            </div>
            
            <div class="info-item mb-3">
                <div class="d-flex align-items-center">
                    <i class="fas fa-map-marker-alt text-danger me-3"></i>
                    <div>
                        <div class="fw-bold">Lieu</div>
                        <div class="text-muted">{{ $event->venue }}</div>
                        @if($event->address)
                        <div class="text-muted small">{{ $event->address }}</div>
                        @endif
                    </div>
                </div>
            </div>
            
            <div class="info-item mb-3">
                <div class="d-flex align-items-center">
                    <i class="fas fa-user-tie text-success me-3"></i>
                    <div>
                        <div class="fw-bold">Organisateur</div>
                        <div class="text-muted">{{ $event->promoteur->name ?? 'ClicBillet CI' }}</div>
                    </div>
                </div>
            </div>
            
            @if($event->ticketTypes->count() > 0)
            <div class="info-item">
                <div class="d-flex align-items-center">
                    <i class="fas fa-ticket-alt text-warning me-3"></i>
                    <div>
                        <div class="fw-bold">Billets disponibles</div>
                        <div class="text-muted">
                            {{ $event->ticketTypes->sum(fn($t) => $t->quantity_available - $t->quantity_sold) }} 
                            places restantes
                        </div>
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>
    
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
    @if($similarEvents && $similarEvents->count() > 0)
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
                        À partir de {{ number_format($similar->ticketTypes->min('price')) }} FCFA
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
