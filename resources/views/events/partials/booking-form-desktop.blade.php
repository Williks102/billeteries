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
        @if($event->ticketTypes->isNotEmpty())
            @foreach($event->ticketTypes as $ticketType)
            <div class="ticket-type">
                <div class="ticket-header">
                    <div>
                        <div class="ticket-name">{{ $ticketType->name }}</div>
                        @if($ticketType->description)
                        <div class="ticket-description">{{ $ticketType->description }}</div>
                        @endif
                    </div>
                    <div class="ticket-price">{{ \App\Helpers\CurrencyHelper::formatPrice($ticketType->price) }}</div>
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
                             data-max="{{ \App\Services\CheckoutService::maxTicketsPerOrder($ticketType->max_per_order) }}"
                             data-available="{{ $ticketType->publicRemaining() }}">0</div>
                        
                        <button type="button" class="qty-btn" 
                                data-action="increase" 
                                data-ticket-type="{{ $ticketType->id }}">
                            <i class="fas fa-plus"></i>
                        </button>
                    </div>
                </div>
                
                <div class="mt-2">
                    <x-ticket-availability-badge :remaining="$ticketType->publicRemaining()" class="me-2" />
                    <small class="text-muted">
                        <i class="fas fa-shopping-cart me-1" aria-hidden="true"></i>
                        Max {{ \App\Services\CheckoutService::maxTicketsPerOrder($ticketType->max_per_order) }}/commande
                    </small>
                </div>
            </div>
            @endforeach
            
            <p class="text-muted small mb-2" data-max-total="{{ \App\Services\CheckoutService::maxTicketsPerOrder() }}">
                <i class="fas fa-info-circle me-1"></i>Maximum {{ \App\Services\CheckoutService::maxTicketsPerOrder() }} billets au total dans votre panier
            </p>

            <!-- Résumé -->
            <div id="bookingSummaryDesktop" class="booking-summary">
                <div id="summaryContentDesktop"></div>
            </div>
            
            <!-- Action principale : directement aux informations de commande, sans page Panier -->
            <form method="POST" action="{{ route('checkout.direct') }}" id="directFormDesktop">
                @csrf
                <input type="hidden" name="event_id" value="{{ $event->id }}">
                <div id="directTicketsDesktop"></div>
                <button type="submit" id="reserveNowDesktop" class="btn-reserve" disabled>
                    <i class="fas fa-ticket-alt me-2" aria-hidden="true"></i>Réserver maintenant
                </button>
            </form>
            <!-- Action secondaire : plusieurs événements dans le même panier -->
            <button type="button"
                    id="reserveBtnDesktop"
                    class="btn btn-outline-primary w-100 mt-2"
                    style="min-height: 48px;"
                    onclick="reserveTickets()"
                    disabled>
                <i class="fas fa-shopping-cart me-2" aria-hidden="true"></i>
                Ajouter au panier
            </button>
            @php $cartItems = array_sum(array_column((array) session('cart', []), 'quantity')); @endphp
            @if($cartItems > 0)
                <a href="{{ route('cart.show') }}" class="view-cart-link d-flex align-items-center justify-content-center gap-2 mt-3 fw-semibold" style="min-height:44px" data-view-cart>
                    <i class="fas fa-shopping-cart" aria-hidden="true"></i> Voir mon panier ({{ $cartItems }})
                </a>
            @endif
            
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