{{-- =============================================== --}}
{{-- resources/views/events/partials/event-details-desktop.blade.php --}}
<div class="event-details">
    <!-- Description principale -->
    @if($event->description)
    <div class="detail-card">
        <div class="detail-title">
            <i class="fas fa-info-circle"></i>
            Description de l'événement
        </div>
        <div class="event-description" style="line-height: 1.6;">
            {!! nl2br(e($event->description)) !!}
        </div>
    </div>
    @endif
    
    <!-- Informations détaillées -->
    <div class="row">
        <div class="col-md-6">
            <div class="detail-card">
                <div class="detail-title">
                    <i class="fas fa-clock"></i>
                    Horaires et durée
                </div>
                <div class="info-list">
                    <div class="d-flex justify-content-between py-2 border-bottom">
                        <span class="text-muted">Début:</span>
                        <span class="fw-bold">{{ $event->event_time ?? '20h00' }}</span>
                    </div>
                    @if($event->end_time)
                    <div class="d-flex justify-content-between py-2 border-bottom">
                        <span class="text-muted">Fin:</span>
                        <span class="fw-bold">{{ $event->end_time }}</span>
                    </div>
                    @endif
                    <div class="d-flex justify-content-between py-2">
                        <span class="text-muted">Durée estimée:</span>
                        <span class="fw-bold">{{ $event->duration ?? '2-3 heures' }}</span>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="detail-card">
                <div class="detail-title">
                    <i class="fas fa-map-marker-alt"></i>
                    Lieu et accès
                </div>
                <div class="info-list">
                    <div class="py-2 border-bottom">
                        <div class="fw-bold">{{ $event->venue }}</div>
                        @if($event->address)
                        <div class="text-muted small">{{ $event->address }}</div>
                        @endif
                    </div>
                    <div class="py-2">
                        <div class="text-muted small">
                            <i class="fas fa-car me-2"></i>Parking disponible
                            <br>
                            <i class="fas fa-bus me-2"></i>Accessible en transport en commun
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Organisateur -->
    <div class="detail-card">
        <div class="detail-title">
            <i class="fas fa-user-tie"></i>
            Organisé par {{ $event->promoteur->name ?? 'ClicBillet CI' }}
        </div>
        <div class="row align-items-center">
            <div class="col-md-8">
                <p class="mb-2">
                    Organisateur professionnel d'événements en Côte d'Ivoire. 
                    Découvrez nos autres événements et restez informés des prochaines dates.
                </p>
                <div class="organizer-stats">
                    <span class="badge bg-light text-dark me-2">
                        <i class="fas fa-calendar-check me-1"></i>{{ rand(5, 20) }} événements organisés
                    </span>
                    <span class="badge bg-light text-dark">
                        <i class="fas fa-star me-1"></i>{{ rand(40, 48)/10 }}/5 avis clients
                    </span>
                </div>
            </div>
            <div class="col-md-4 text-center">
                <button class="btn btn-outline-primary">
                    <i class="fas fa-eye me-2"></i>Voir tous leurs événements
                </button>
            </div>
        </div>
    </div>
    
    <!-- Événements similaires - Version desktop -->
    @if($similarEvents && $similarEvents->count() > 0)
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
                    <img src="{{ asset('storage/' . $similar->image) }}" 
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
                            {{ number_format($similar->ticketTypes->min('price')) }} FCFA
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
    
    <!-- Conditions et politique -->
    <div class="detail-card">
        <div class="detail-title">
            <i class="fas fa-shield-alt"></i>
            Conditions d'annulation et remboursement
        </div>
        <div class="conditions-text small text-muted">
            <div class="row">
                <div class="col-md-6">
                    <h6 class="fw-bold text-dark">Annulation par l'organisateur</h6>
                    <ul class="mb-3">
                        <li>Remboursement intégral garanti</li>
                        <li>Notification immédiate par email</li>
                        <li>Report possible selon disponibilité</li>
                    </ul>
                </div>
                <div class="col-md-6">
                    <h6 class="fw-bold text-dark">Annulation par l'acheteur</h6>
                    <ul class="mb-3">
                        <li>Jusqu'à 48h avant: remboursement 80%</li>
                        <li>Jusqu'à 24h avant: remboursement 50%</li>
                        <li>Moins de 24h: aucun remboursement</li>
                    </ul>
                </div>
            </div>
            <div class="alert alert-info small mb-0">
                <i class="fas fa-info-circle me-2"></i>
                En achetant ce billet, vous acceptez les 
                <a href="{{ route('pages.terms') }}" target="_blank">conditions générales</a> 
                de ClicBillet CI.
            </div>
        </div>
    </div>
</div>