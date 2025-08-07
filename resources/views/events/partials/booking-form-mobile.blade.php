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
            <!-- Token CSRF pour les requêtes AJAX -->
            <input type="hidden" name="event_id" value="{{ $event->id }}">
            
            @foreach($event->ticketTypes as $ticketType)
            <div class="ticket-type" data-ticket-type-id="{{ $ticketType->id }}">
                <div class="ticket-header">
                    <div class="ticket-info">
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
                                data-ticket-type="{{ $ticketType->id }}"
                                aria-label="Diminuer la quantité">
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
                                data-ticket-type="{{ $ticketType->id }}"
                                aria-label="Augmenter la quantité">
                            <i class="fas fa-plus"></i>
                        </button>
                    </div>
                </div>
                
                <div class="ticket-availability mt-2">
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
                    disabled>
                <i class="fas fa-ticket-alt me-2"></i>
                Sélectionnez vos billets
            </button>
            
            <!-- Informations de sécurité -->
            <div class="security-info mt-3 text-center">
                <small class="text-muted">
                    <i class="fas fa-shield-alt me-1 text-success"></i>
                    Réservation sécurisée • Paiement à l'étape suivante
                </small>
            </div>
            
        @else
            <div class="no-tickets text-center py-4">
                <i class="fas fa-exclamation-circle fa-3x text-muted mb-3"></i>
                <h5 class="text-muted">Aucun billet disponible</h5>
                <p class="text-muted">La vente n'a pas encore commencé ou tous les billets sont épuisés.</p>
                <a href="{{ route('events.all') }}" class="btn btn-outline-primary mt-2">
                    <i class="fas fa-search me-2"></i>Voir d'autres événements
                </a>
            </div>
        @endif
    </div>
</div>

{{-- Styles spécifiques mobile --}}
@push('styles')
<style>
/* Styles pour le formulaire de réservation mobile */
.booking-section {
    background: white;
    border-radius: 15px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.1);
    margin-bottom: 2rem;
    overflow: hidden;
}

.booking-header {
    background: linear-gradient(135deg, #FF6B35, #E55A2B);
    color: white;
    padding: 1.5rem;
    text-align: center;
}

.booking-header h3 {
    margin: 0;
    font-size: 1.3rem;
    font-weight: 600;
}

.booking-timer {
    background: rgba(255,255,255,0.2);
    border-radius: 20px;
    padding: 8px 16px;
    margin-top: 10px;
    font-size: 0.9rem;
    display: inline-block;
}

.booking-form {
    padding: 1.5rem;
}

.ticket-type {
    border: 2px solid #f0f0f0;
    border-radius: 12px;
    padding: 1rem;
    margin-bottom: 1rem;
    transition: all 0.3s ease;
    position: relative;
}

.ticket-type:hover {
    border-color: rgba(255,107,53,0.3);
    background: rgba(255,107,53,0.02);
}

.ticket-type.has-selection {
    border-color: #FF6B35;
    background: rgba(255,107,53,0.05);
}

.ticket-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 1rem;
}

.ticket-info {
    flex: 1;
}

.ticket-name {
    font-weight: 600;
    font-size: 1.1rem;
    color: #333;
    margin-bottom: 0.25rem;
}

.ticket-description {
    font-size: 0.9rem;
    color: #6c757d;
    margin-bottom: 0.5rem;
}

.ticket-price {
    font-size: 1.1rem;
    font-weight: 700;
    color: #FF6B35;
    text-align: right;
}

.quantity-selector {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.5rem;
}

.quantity-label {
    font-weight: 500;
    color: #495057;
}

.quantity-controls {
    display: flex;
    align-items: center;
    gap: 1rem;
    background: #f8f9fa;
    border-radius: 25px;
    padding: 0.5rem;
}

.qty-btn {
    background: #FF6B35;
    color: white;
    border: none;
    width: 35px;
    height: 35px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s ease;
    cursor: pointer;
}

.qty-btn:hover {
    background: #E55A2B;
    transform: scale(1.05);
}

.qty-btn:disabled {
    background: #ccc;
    cursor: not-allowed;
    transform: none;
}

.qty-display {
    min-width: 40px;
    text-align: center;
    font-weight: 600;
    font-size: 1.1rem;
    color: #333;
}

.ticket-availability {
    border-top: 1px solid #eee;
    padding-top: 0.5rem;
}

.booking-summary {
    margin-bottom: 1.5rem;
}

.selection-summary {
    background: rgba(255,107,53,0.1);
    border-radius: 8px;
    padding: 1rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-left: 4px solid #FF6B35;
}

.summary-total {
    font-size: 1.2rem;
    font-weight: bold;
    color: #FF6B35;
}

.btn-reserve {
    width: 100%;
    background: linear-gradient(135deg, #FF6B35, #E55A2B);
    border: none;
    color: white;
    padding: 1rem 1.5rem;
    font-size: 1.1rem;
    font-weight: 600;
    border-radius: 10px;
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}

.btn-reserve:hover:not(:disabled) {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(255,107,53,0.3);
    color: white;
}

.btn-reserve:disabled {
    background: #ccc;
    cursor: not-allowed;
    transform: none;
    box-shadow: none;
}

.security-info {
    opacity: 0.8;
}

.no-tickets {
    padding: 2rem 1rem;
}

/* Animation pour les erreurs de limite */
.qty-limit-reached {
    animation: shake 0.5s;
    border-color: #dc3545 !important;
}

@keyframes shake {
    0%, 50%, 100% { transform: translateX(0); }
    25% { transform: translateX(-2px); }
    75% { transform: translateX(2px); }
}

/* Responsive adjustments */
@media (max-width: 576px) {
    .ticket-header {
        flex-direction: column;
        align-items: stretch;
    }
    
    .ticket-price {
        text-align: left;
        margin-top: 0.5rem;
    }
    
    .quantity-selector {
        flex-direction: column;
        align-items: stretch;
        gap: 1rem;
    }
    
    .quantity-controls {
        justify-content: center;
    }
}
</style>
@endpush