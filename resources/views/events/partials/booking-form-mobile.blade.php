{{-- =============================================== --}}
{{-- resources/views/events/partials/booking-form-mobile.blade.php --}}
<div class="booking-section">
    <div class="booking-header">
        <h3>
            <i class="fas fa-ticket-alt me-2"></i>
            Réserver vos billets
        </h3>
        <div class="booking-timer" id="timerContainer" style="display: none;">
            <i class="fas fa-clock me-1"></i>
            Temps restant: <span id="timerDisplay">15:00</span>
        </div>
    </div>
    
    <div class="booking-form" id="bookingForm">
        @if($event->ticketTypes->count() > 0)
            @foreach($event->ticketTypes as $ticketType)
            <div class="ticket-type">
                <div class="ticket-header">
                    <div class="ticket-name">{{ $ticketType->name }}</div>
                    <div class="ticket-price">{{ number_format($ticketType->price) }} FCFA</div>
                </div>
                
                @if($ticketType->description)
                <div class="ticket-description">{{ $ticketType->description }}</div>
                @endif
                
                <div class="quantity-selector">
                    <div class="quantity-label">Quantité:</div>
                    <div class="quantity-controls">
                        <button type="button" class="qty-btn" 
                                data-action="decrease" 
                                data-ticket-type="{{ $ticketType->id }}">
                            <i class="fas fa-minus"></i>
                        </button>
                        
                        <div class="qty-display" 
                             id="qty_{{ $ticketType->id }}"
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
                        <i class="fas fa-info-circle me-1"></i>
                        {{ $ticketType->quantity_available - $ticketType->quantity_sold }} disponible(s) 
                        • Max {{ $ticketType->max_per_order ?? 10 }} par commande
                    </small>
                </div>
            </div>
            @endforeach
            
            <!-- Résumé dynamique -->
            <div id="bookingSummary" class="booking-summary">
                <div id="summaryContent"></div>
            </div>
            
            <!-- Bouton de réservation -->
            <button type="button" 
                    id="reserveBtn" 
                    class="btn-reserve" 
                    onclick="reserveTickets()" 
                    disabled>
                <i class="fas fa-ticket-alt me-2"></i>
                Sélectionnez vos billets
            </button>
            
        @else
            <div class="text-center py-4">
                <i class="fas fa-exclamation-circle fa-3x text-muted mb-3"></i>
                <h5 class="text-muted">Aucun billet disponible</h5>
                <p class="text-muted">La vente n'a pas encore commencé ou tous les billets sont épuisés.</p>
            </div>
        @endif
    </div>
</div>
