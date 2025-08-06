{{-- =============================================== --}}
{{-- resources/views/events/partials/booking-form-desktop.blade.php --}}
<div class="booking-section">
    <div class="booking-header">
        <h3>
            <i class="fas fa-ticket-alt me-2"></i>
            Réserver vos billets
        </h3>
        <div class="booking-timer" id="timerContainerDesktop" style="display: none;">
            <i class="fas fa-clock me-1"></i>
            <span id="timerDisplayDesktop">15:00</span>
        </div>
    </div>
    
    <div class="booking-form" id="bookingFormDesktop">
        @if($event->ticketTypes->count() > 0)
            @foreach($event->ticketTypes as $ticketType)
            <div class="ticket-type">
                <div class="ticket-header">
                    <div>
                        <div class="ticket-name">{{ $ticketType->name }}</div>
                        @if($ticketType->description)
                        <div class="ticket-description">{{ $ticketType->description }}</div>
                        @endif
                    </div>
                    <div class="ticket-price">{{ number_format($ticketType->price) }} FCFA</div>
                </div>
                
                <div class="quantity-selector">
                    <div class="quantity-label">Quantité:</div>
                    <div class="quantity-controls">
                        <button type="button" class="qty-btn" 
                                data-action="decrease" 
                                data-ticket-type="{{ $ticketType->id }}">
                            <i class="fas fa-minus"></i>
                        </button>
                        
                        <div class="qty-display" 
                             id="qty_{{ $ticketType->id }}_desktop"
                             data-name="{{ $ticketType->name }}"
                             data-price="{{ $ticketType->price }}"
                             data-max="{{ $ticketType->max_per_order ?? 10 }}"
                             data-available="{{ $ticketType->quantity_available - $ticketType->quantity_sold }}">0</div>
                        
                        <button type="button" class="qty-btn" 
                                data-action="increase" 
                                data-ticket-type="{{ $ticketType->id }}">
                            <i class="fas fa-plus"></i>
                        </button>
                    </div>
                </div>
                
                <div class="mt-2">
                    <small class="text-muted">
                        <i class="fas fa-users me-1"></i>
                        {{ $ticketType->quantity_available - $ticketType->quantity_sold }} restant(s)
                        <i class="fas fa-shopping-cart ms-2 me-1"></i>
                        Max {{ $ticketType->max_per_order ?? 10 }}/commande
                    </small>
                </div>
            </div>
            @endforeach
            
            <!-- Résumé -->
            <div id="bookingSummaryDesktop" class="booking-summary">
                <div id="summaryContentDesktop"></div>
            </div>
            
            <!-- Bouton principal -->
            <button type="button" 
                    id="reserveBtnDesktop" 
                    class="btn-reserve" 
                    onclick="reserveTickets()" 
                    disabled>
                <i class="fas fa-ticket-alt me-2"></i>
                Sélectionnez vos billets
            </button>
            
            <!-- Informations de sécurité -->
            <div class="mt-3 text-center">
                <small class="text-muted">
                    <i class="fas fa-shield-alt me-1 text-success"></i>
                    Réservation sécurisée • Aucun paiement à cette étape
                </small>
            </div>
            
        @else
            <div class="text-center py-4">
                <i class="fas fa-calendar-times fa-3x text-muted mb-3"></i>
                <h5 class="text-muted">Billets non disponibles</h5>
                <p class="text-muted mb-3">La vente n'a pas encore commencé ou l'événement est complet.</p>
                <a href="{{ route('events.all') }}" class="btn btn-outline-primary">
                    <i class="fas fa-search me-2"></i>Voir d'autres événements
                </a>
            </div>
        @endif
    </div>
</div>