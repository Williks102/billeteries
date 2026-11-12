{{-- resources/views/events/partials/booking-form-mobile.blade.php --}}

{{-- Bouton d'ouverture du modal --}}
<div class="booking-section">
    <div class="booking-header">
        <h3>
            <i class="fas fa-ticket-alt me-2"></i>
            Réservation
        </h3>
    </div>
    
    <div class="booking-form">
        @if($event->ticketTypes->isNotEmpty())
            <button type="button" 
                    class="btn-open-modal" 
                    onclick="openTicketModal()"
                    data-event-id="{{ $event->id }}">
                <i class="fas fa-ticket-alt me-2"></i>
                Choisir mes billets
            </button>
            
            {{-- Résumé rapide des options --}}
            <div class="quick-info mt-3">
                <div class="info-row">
                    <i class="fas fa-tags me-2"></i>
                    <span>{{ $event->ticketTypesCount() }} type{{ $event->ticketTypesCount() > 1 ? 's' : '' }} de billet{{ $event->ticketTypesCount() > 1 ? 's' : '' }}</span>
                </div>
                <div class="info-row">
                    <i class="fas fa-clock me-2"></i>
                    <span>Réservation immédiate</span>
                </div>
            </div>
        @else
            <div class="no-tickets text-center py-4">
                <i class="fas fa-exclamation-circle fa-2x text-muted mb-2"></i>
                <h6 class="text-muted">Aucun billet disponible</h6>
                <p class="text-muted small">La vente n'a pas encore commencé.</p>
            </div>
        @endif
    </div>
</div>

{{-- Modal Backdrop --}}
<div class="modal-backdrop" id="ticketModalBackdrop" onclick="closeTicketModal()"></div>

{{-- Bottom Modal Facebook Style --}}
<div class="ticket-modal" id="ticketModal">
    {{-- Handle de glissement --}}
    <div class="modal-handle"></div>
    
    {{-- Header --}}
    <div class="modal-header">
        <h3 class="modal-title">Sélectionner vos billets</h3>
        <button class="close-btn" onclick="closeTicketModal()">
            <i class="fas fa-times"></i>
        </button>
    </div>

    {{-- Body --}}
    <div class="modal-body">
        {{-- Titre court : date, lieu et prix sont déjà visibles sur la fiche --}}
        <p class="sheet-event-title">{{ $event->title }}</p>

        {{-- Types de billets --}}
        @if($event->ticketTypes->isNotEmpty())
            <div class="tickets-section">
                <h4 class="section-title">Types de billets</h4>
                
                @foreach($event->ticketTypes as $ticketType)
                    @php
                        $available = $ticketType->publicRemaining();
                        $isAvailable = $available > 0 && now()->between($ticketType->sale_start_date, $ticketType->sale_end_date);
                    @endphp
                    
                    <div class="ticket-type {{ $isAvailable ? '' : 'unavailable' }}" 
                         data-ticket-id="{{ $ticketType->id }}"
                         data-price="{{ $ticketType->price }}"
                         data-max="{{ \App\Services\CheckoutService::maxTicketsPerOrder($ticketType->max_per_order) }}"
                         data-available="{{ $available }}">
                        
                        <div class="ticket-header">
                            <div class="ticket-info">
                                <div class="ticket-name">{{ $ticketType->name }}</div>
                                @if($ticketType->description)
                                    <div class="ticket-description">{{ $ticketType->description }}</div>
                                @endif
                            </div>
                            <div class="ticket-price">
                                {{ \App\Helpers\CurrencyHelper::formatPrice($ticketType->price) }}
                            </div>
                        </div>
                        
                        @if($isAvailable)
                            <div class="ticket-controls">
                                <div class="stock-info">
                                    <x-ticket-availability-badge :remaining="$available" />
                                </div>
                                <div class="quantity-controls">
                                    <button class="qty-btn" onclick="updateQuantity('{{ $ticketType->id }}', -1)">
                                        <i class="fas fa-minus"></i>
                                    </button>
                                    <span class="qty-display" id="qty-{{ $ticketType->id }}">0</span>
                                    <button class="qty-btn" onclick="updateQuantity('{{ $ticketType->id }}', 1)">
                                        <i class="fas fa-plus"></i>
                                    </button>
                                </div>
                            </div>
                        @else
                            <div class="ticket-controls">
                                <div class="stock-info">
                                    @if($available <= 0)
                                        <x-ticket-availability-badge :remaining="0" />
                                    @else
                                        <span class="badge-state badge-state--neutral"><i class="fas fa-lock" aria-hidden="true"></i>Vente fermée</span>
                                    @endif
                                </div>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    {{-- Footer --}}
    <div class="modal-footer">
        <div class="price-summary">
            <div class="summary-details" id="summaryDetails"></div>
            <div class="total-section">
                <span class="total-label">Total</span>
                <span class="total-price" id="totalPrice">—</span>
            </div>
        </div>
        {{-- Action principale : directement aux informations de commande (un seul événement), sans page Panier --}}
        <form method="POST" action="{{ route('checkout.direct') }}" id="directForm">
            @csrf
            <input type="hidden" name="event_id" value="{{ $event->id }}">
            <div id="directTickets"></div>
            <button type="submit" class="reserve-now-btn" id="reserveNowBtn" disabled>
                <i class="fas fa-ticket-alt" aria-hidden="true"></i>
                Réserver maintenant
            </button>
        </form>
        <button type="button" class="add-to-cart-btn" id="addToCartBtn" onclick="addToCart()" disabled>
            <i class="fas fa-shopping-cart" aria-hidden="true"></i>
            Ajouter au panier
        </button>
        @php $cartItems = array_sum(array_column((array) session('cart', []), 'quantity')); @endphp
        @if($cartItems > 0)
            <a href="{{ route('cart.show') }}" class="view-cart-link" data-view-cart>
                <i class="fas fa-shopping-cart" aria-hidden="true"></i> Voir mon panier ({{ $cartItems }})
            </a>
        @endif
    </div>
</div>

{{-- Styles --}}
@push('styles')
<style>
/* Styles de base */
* {
    box-sizing: border-box;
}

/* Section booking simplifiée */
.booking-section {
    background: white;
    border-radius: 15px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.1);
    margin-bottom: 2rem;
    overflow: hidden;
}

.booking-header {
    background: linear-gradient(135deg, var(--brand), var(--brand-hover));
    color: white;
    padding: 1.5rem;
    text-align: center;
}

.booking-header h3 {
    margin: 0 0 0.5rem 0;
    font-size: 1.3rem;
    font-weight: 600;
}

.booking-preview {
    background: rgba(255,255,255,0.2);
    border-radius: 20px;
    padding: 8px 16px;
    font-size: 0.9rem;
    display: inline-block;
}

.booking-form {
    padding: 1.5rem;
}

.btn-open-modal {
    width: 100%;
    background: linear-gradient(135deg, var(--brand), var(--brand-hover));
    border: none;
    color: white;
    padding: 1rem 1.5rem;
    font-size: 1.1rem;
    font-weight: 600;
    border-radius: 12px;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
}

.btn-open-modal:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(var(--brand-rgb), 0.3);
}

.quick-info {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.info-row {
    display: flex;
    align-items: center;
    color: #6c757d;
    font-size: 0.9rem;
}

/* No tickets state */
.no-tickets {
    text-align: center;
    padding: 2rem 1rem;
}

/* ===== MODAL STYLES ===== */
.modal-backdrop {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    z-index: 999;
    opacity: 0;
    visibility: hidden;
    transition: all 0.3s ease;
}

.modal-backdrop.show {
    opacity: 1;
    visibility: visible;
}

.ticket-modal {
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    background: white;
    border-radius: 20px 20px 0 0;
    z-index: 1000;
    transform: translateY(100%);
    transition: transform 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94);
    max-height: 90vh;
    display: flex;
    flex-direction: column;
    box-shadow: 0 -10px 30px rgba(0,0,0,0.3);
}

.ticket-modal.show {
    transform: translateY(0);
}

/* Handle */
.modal-handle {
    width: 40px;
    height: 4px;
    background: #c4c4c4;
    border-radius: 2px;
    margin: 12px auto 8px;
    cursor: grab;
}

.modal-header {
    padding: 0 20px 16px;
    border-bottom: 1px solid #e9ecef;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.modal-title {
    font-size: 1.2rem;
    font-weight: bold;
    color: #333;
    margin: 0;
}

.close-btn {
    width: 32px;
    height: 32px;
    border: none;
    background: #f8f9fa;
    border-radius: 50%;
    color: #666;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s ease;
}

.close-btn:hover {
    background: #e9ecef;
}

.modal-body {
    flex: 1;
    overflow-y: auto;
    padding: 20px;
}

/* Event Info */
.event-info {
    display: flex;
    gap: 12px;
    margin-bottom: 24px;
    padding-bottom: 16px;
    border-bottom: 1px solid #f0f0f0;
}

.event-image {
    width: 60px;
    height: 60px;
    border-radius: 8px;
    overflow: hidden;
    flex-shrink: 0;
    background: linear-gradient(135deg, var(--brand), var(--brand-decor));
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.2rem;
}

.event-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.event-details {
    flex: 1;
}

.event-details h4 {
    font-size: 1rem;
    font-weight: 600;
    color: #333;
    margin: 0 0 4px 0;
}

.event-details p {
    color: #666;
    font-size: 0.9rem;
    margin: 2px 0;
}

.venue {
    font-weight: 500;
}

/* Tickets Section */
.tickets-section {
    margin-bottom: 20px;
}

.section-title {
    font-size: 1.1rem;
    font-weight: 600;
    margin-bottom: 16px;
    color: #333;
}

.ticket-type {
    border: 1px solid #e9ecef;
    border-radius: 12px;
    padding: 16px;
    margin-bottom: 12px;
    transition: all 0.2s ease;
}

.ticket-type:hover:not(.unavailable) {
    border-color: var(--brand);
    box-shadow: 0 2px 8px rgba(var(--brand-rgb), 0.1);
}

.ticket-type.selected {
    border-color: var(--brand);
    background: rgba(var(--brand-rgb), 0.05);
}

.ticket-type.unavailable {
    background: #f8f9fa;
    opacity: 0.6;
}

.ticket-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 8px;
}

.ticket-name {
    font-weight: 600;
    color: #333;
    font-size: 1rem;
}

.ticket-description {
    color: #666;
    font-size: 0.9rem;
    margin-top: 4px;
}

.ticket-price {
    font-weight: bold;
    color: var(--brand);
    font-size: 1.1rem;
    white-space: nowrap;
}

.ticket-controls {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 12px;
}

.stock-info {
    color: #28a745;
    font-size: 0.9rem;
    font-weight: 500;
}

.stock-info.limited {
    color: #ffc107;
}

.stock-info.sold-out {
    color: #dc3545;
}

/* Quantity Controls */
.quantity-controls {
    display: flex;
    align-items: center;
    gap: 12px;
}

.qty-btn {
    width: 36px;
    height: 36px;
    border: 2px solid #e9ecef;
    background: white;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.2s ease;
    color: #666;
}

.qty-btn:hover:not(:disabled) {
    border-color: var(--brand);
    color: var(--brand);
    background: rgba(var(--brand-rgb), 0.05);
}

.qty-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.qty-display {
    min-width: 30px;
    text-align: center;
    font-weight: bold;
    font-size: 1.1rem;
    color: #333;
}

/* Modal Footer */
.modal-footer {
    padding: 20px;
    border-top: 1px solid #e9ecef;
    background: #f8f9fa;
    border-radius: 0 0 20px 20px;
}

.price-summary {
    margin-bottom: 16px;
}

.summary-details {
    margin-bottom: 8px;
    font-size: 0.9rem;
    color: #666;
}

.total-section {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding-top: 8px;
    border-top: 1px solid #dee2e6;
}

.total-label {
    font-weight: 500;
    color: #333;
}

.total-price {
    font-size: 1.3rem;
    font-weight: bold;
    color: var(--brand);
}

.sheet-event-title { font-weight: 600; margin: 0 0 var(--space-3); overflow-wrap: anywhere; }
.total-section { gap: var(--space-3); }
.add-to-cart-btn, .reserve-now-btn {
    width: 100%; min-height: 48px; padding: 12px 16px; border-radius: var(--radius);
    font-size: 1.0625rem; font-weight: 600; cursor: pointer;
    display: flex; align-items: center; justify-content: center; gap: var(--space-2);
}
.reserve-now-btn { background: var(--brand); color: var(--surface); border: 2px solid var(--brand); }
.reserve-now-btn:hover:not(:disabled) { background: var(--brand-hover); border-color: var(--brand-hover); }
.add-to-cart-btn { background: var(--surface); color: var(--brand); border: 2px solid var(--brand); margin-top: var(--space-3); }
.add-to-cart-btn:hover:not(:disabled) { background: var(--brand-soft); }
.add-to-cart-btn:disabled, .reserve-now-btn:disabled { opacity: .5; cursor: not-allowed; }
.view-cart-link { display: flex; align-items: center; justify-content: center; gap: var(--space-2, 8px); min-height: 44px; margin-top: var(--space-3, 12px); color: var(--link-ink, var(--brand)); font-weight: 600; text-decoration: underline; }

/* Responsive */
@media (max-width: 576px) {
    .ticket-header {
        flex-direction: column;
        align-items: stretch;
        gap: 8px;
    }
    
    .ticket-price {
        text-align: left;
    }
}
</style>
@endpush

{{-- Scripts --}}
@push('scripts')
<script>
// État global des billets
let ticketQuantities = {};
let ticketPrices = {};
let ticketMaxes = {};
let ticketAvailable = {};

// Initialiser les données des billets
@if($event->ticketTypes->isNotEmpty())
    @foreach($event->ticketTypes as $ticketType)
        @php
            $available = $ticketType->publicRemaining();
            $isAvailable = $available > 0 && now()->between($ticketType->sale_start_date, $ticketType->sale_end_date);
        @endphp
        @if($isAvailable)
            ticketPrices['{{ $ticketType->id }}'] = {{ $ticketType->price }};
            ticketMaxes['{{ $ticketType->id }}'] = {{ \App\Services\CheckoutService::maxTicketsPerOrder($ticketType->max_per_order) }};
            ticketAvailable['{{ $ticketType->id }}'] = {{ $available }};
            ticketQuantities['{{ $ticketType->id }}'] = 0;
        @endif
    @endforeach
@endif

// Ouvrir le modal
function openTicketModal() {
    document.getElementById('ticketModalBackdrop').classList.add('show');
    document.getElementById('ticketModal').classList.add('show');
    document.body.style.overflow = 'hidden';
}

// Fermer le modal
function closeTicketModal() {
    document.getElementById('ticketModalBackdrop').classList.remove('show');
    document.getElementById('ticketModal').classList.remove('show');
    document.body.style.overflow = '';
}

// Mettre à jour la quantité
function updateQuantity(ticketId, delta) {
    const currentQty = ticketQuantities[ticketId] || 0;
    const maxQty = Math.min(ticketMaxes[ticketId], ticketAvailable[ticketId]);
    let newQty = Math.max(0, Math.min(maxQty, currentQty + delta));

    // I-a : plafond sur le TOTAL de billets (même règle que le serveur : panier et checkout)
    const maxTotal = {{ \App\Services\CheckoutService::maxTicketsPerOrder() }};
    const othersTotal = Object.entries(ticketQuantities).reduce((sum, [id, q]) => sum + (String(id) === String(ticketId) ? 0 : q), 0);
    if (delta > 0 && othersTotal + newQty > maxTotal) {
        showLimitFeedback(ticketId);
        return;
    }
    
    // Vérifier les limites
    if (newQty === maxQty && delta > 0 && currentQty === maxQty) {
        // Montrer feedback de limite atteinte
        showLimitFeedback(ticketId);
        return;
    }
    
    ticketQuantities[ticketId] = newQty;
    
    // Mettre à jour l'affichage
    document.getElementById(`qty-${ticketId}`).textContent = newQty;
    
    // Mettre à jour le style du ticket
    const ticketElement = document.querySelector(`[data-ticket-id="${ticketId}"]`);
    if (newQty > 0) {
        ticketElement.classList.add('selected');
    } else {
        ticketElement.classList.remove('selected');
    }
    
    // Mettre à jour les boutons
    const minusBtn = ticketElement.querySelector('.qty-btn');
    const plusBtn = ticketElement.querySelectorAll('.qty-btn')[1];
    
    minusBtn.disabled = newQty === 0;
    plusBtn.disabled = newQty >= maxQty;
    
    updateSummary();
}

// Calculer et afficher le résumé
function updateSummary() {
    let totalPrice = 0;
    let totalTickets = 0;
    let summaryItems = [];
    
    for (const [ticketId, quantity] of Object.entries(ticketQuantities)) {
        if (quantity > 0) {
            const price = ticketPrices[ticketId];
            const subtotal = quantity * price;
            totalPrice += subtotal;
            totalTickets += quantity;
            
            // Trouver le nom du billet
            const ticketElement = document.querySelector(`[data-ticket-id="${ticketId}"]`);
            const ticketName = ticketElement?.querySelector('.ticket-name')?.textContent || 'Billet';
            
            summaryItems.push(`${quantity}x ${ticketName}`);
        }
    }
    
    // Mettre à jour l'affichage
    const summaryDetails = document.getElementById('summaryDetails');
    const totalPriceElement = document.getElementById('totalPrice');
    const addToCartBtn = document.getElementById('addToCartBtn');
    
    if (totalTickets === 0) {
        summaryDetails.innerHTML = '';
        totalPriceElement.textContent = '—';
        addToCartBtn.disabled = true;
        addToCartBtn.innerHTML = '<i class="fas fa-shopping-cart" aria-hidden="true"></i>Ajouter au panier';
    } else {
        // S-9 : le nom du billet est du texte saisi par l'organisateur : jamais d'innerHTML
        summaryDetails.textContent = summaryItems.join(' • ');
        totalPriceElement.textContent = totalPrice === 0 ? 'Gratuit' : String(totalPrice).replace(/\B(?=(\d{3})+(?!\d))/g, '\u00A0') + '\u00A0FCFA';
        addToCartBtn.disabled = false;
        addToCartBtn.innerHTML = `<i class="fas fa-shopping-cart" aria-hidden="true"></i>Ajouter au panier`;
    }
    syncDirectForm(totalTickets);
}

// Réservation directe : les quantités choisies alimentent le formulaire POST checkout.direct
function syncDirectForm(totalTickets) {
    const holder = document.getElementById('directTickets');
    const btn = document.getElementById('reserveNowBtn');
    if (!holder || !btn) return;
    holder.textContent = '';
    for (const [ticketId, quantity] of Object.entries(ticketQuantities)) {
        if (quantity > 0) {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'tickets[' + ticketId + ']';
            input.value = quantity;
            holder.appendChild(input);
        }
    }
    btn.disabled = totalTickets === 0;
}

// Ajouter au panier
function addToCart() {
    const selectedTickets = [];
    
    for (const [ticketId, quantity] of Object.entries(ticketQuantities)) {
        if (quantity > 0) {
            selectedTickets.push({
                ticket_type_id: ticketId,
                quantity: quantity
            });
        }
    }
    
    if (selectedTickets.length === 0) return;
    
    // Désactiver le bouton pendant la requête
    const btn = document.getElementById('addToCartBtn');
    const originalText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Ajout en cours...';
    
    // Envoyer la requête AJAX
    fetch('{{ route("cart.add") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json'
        },
        body: JSON.stringify({
            event_id: {{ $event->id }},
            tickets: selectedTickets
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Animation de succès
            btn.innerHTML = '<i class="fas fa-check me-2"></i>Ajouté au panier !';
            
            
            // Fermer le modal après un délai
            setTimeout(() => {
                closeTicketModal();
                
                // Rediriger vers le panier ou afficher une notification
                if (data.redirect) {
                    window.location.href = data.redirect;
                } else {
                    // Optionnel: montrer une notification de succès
                    showSuccessNotification('Billets ajoutés au panier !');
                    resetModal();
                }
            }, 1500);
        } else {
            throw new Error(data.message || 'Erreur lors de l\'ajout au panier');
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        btn.disabled = false;
        btn.innerHTML = originalText;
        btn.style.background = '';
        
        // Afficher l'erreur
        safeErrorNotification(error.message || 'Erreur lors de l\'ajout au panier');
    });
}

// Reset du modal
function resetModal() {
    // Reset quantités
    for (const ticketId of Object.keys(ticketQuantities)) {
        ticketQuantities[ticketId] = 0;
        document.getElementById(`qty-${ticketId}`).textContent = '0';
        
        const ticketElement = document.querySelector(`[data-ticket-id="${ticketId}"]`);
        ticketElement?.classList.remove('selected');
        
        // Reset boutons
        const buttons = ticketElement?.querySelectorAll('.qty-btn');
        if (buttons) {
            buttons[0].disabled = true; // minus
            buttons[1].disabled = false; // plus
        }
    }
    
    // Reset bouton principal
    const btn = document.getElementById('addToCartBtn');
    btn.innerHTML = '<i class="fas fa-shopping-cart" aria-hidden="true"></i>Ajouter au panier';
    btn.style.background = '';
    btn.disabled = true;
    
    updateSummary();
}

// Feedback de limite atteinte
function showLimitFeedback(ticketId) {
    const ticketElement = document.querySelector(`[data-ticket-id="${ticketId}"]`);
    ticketElement?.classList.add('qty-limit-reached');
    
    setTimeout(() => {
        ticketElement?.classList.remove('qty-limit-reached');
    }, 1000);
}

// Notifications (optionnel - à adapter selon votre système)
function showSuccessNotification(message) {
    // Implémentez votre système de notification ici
    console.log('Succès:', message);
}

function safeErrorNotification(message) {
    console.error('Erreur:', message);
    // S-9 : window.notifications insère le message en HTML : on l'échappe avant (le message serveur
    // peut contenir un nom de type de billet saisi par l'organisateur). Repli : alert() (texte brut).
    if (window.notifications && typeof window.notifications.error === 'function') {
        const div = document.createElement('div');
        div.textContent = String(message ?? '');
        window.notifications.error(div.innerHTML);
    } else {
        alert('Erreur: ' + message);
    }
}

// Fermer avec Escape
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeTicketModal();
    }
});

// Gestion du swipe pour fermer (optionnel)
let startY = 0;

document.getElementById('ticketModal')?.addEventListener('touchstart', function(e) {
    startY = e.touches[0].clientY;
}, { passive: true });

document.getElementById('ticketModal')?.addEventListener('touchmove', function(e) {
    const currentY = e.touches[0].clientY;
    const diffY = currentY - startY;
    
    if (diffY > 50) { // Swipe vers le bas
        closeTicketModal();
    }
}, { passive: true });
</script>
@endpush