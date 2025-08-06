{{-- =============================================== --}}
{{-- resources/views/events/show.blade.php - SYSTÈME UNIFIÉ --}}
@extends('layouts.app')

@section('title', $event->title . ' - ClicBillet CI')

@push('styles')
<style>
/* ===== SYSTÈME UNIFIÉ - MOBILE FIRST ===== */

.event-hero {
    position: relative;
    min-height: 300px;
    background: linear-gradient(135deg, #FF6B35, #1a237e);
    margin-bottom: 2rem;
}

.hero-content {
    position: relative;
    z-index: 3;
    padding: 2rem 0;
    color: white;
}

.event-title {
    font-size: 2rem;
    font-weight: 700;
    margin-bottom: 1rem;
    text-shadow: 2px 2px 4px rgba(0,0,0,0.5);
}

.event-quick-info {
    display: flex;
    flex-wrap: wrap;
    gap: 1rem;
    margin-top: 1rem;
}

.info-item {
    display: flex;
    align-items: center;
    gap: 8px;
    color: rgba(255,255,255,0.9);
    font-size: 0.9rem;
}

.info-item i {
    color: #FF6B35;
    width: 16px;
}

/* ===== FORMULAIRE DE RÉSERVATION - MOBILE OPTIMISÉ ===== */
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
}

.ticket-type:hover,
.ticket-type.has-selection {
    border-color: #FF6B35;
    background: rgba(255,107,53,0.05);
}

.ticket-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.5rem;
}

.ticket-name {
    font-weight: 600;
    color: #333;
    font-size: 1.1rem;
}

.ticket-price {
    font-weight: 700;
    color: #FF6B35;
    font-size: 1.2rem;
}

.ticket-description {
    color: #666;
    font-size: 0.9rem;
    margin-bottom: 1rem;
}

.quantity-selector {
    display: flex;
    align-items: center;
    justify-content: space-between;
    background: #f8f9fa;
    border-radius: 8px;
    padding: 0.5rem;
}

.quantity-label {
    font-size: 0.9rem;
    color: #666;
}

.quantity-controls {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.qty-btn {
    width: 32px;
    height: 32px;
    border: 1px solid #ddd;
    background: white;
    border-radius: 6px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    color: #666;
    transition: all 0.2s ease;
}

.qty-btn:hover:not(:disabled) {
    background: #FF6B35;
    border-color: #FF6B35;
    color: white;
}

.qty-btn:disabled {
    opacity: 0.3;
    cursor: not-allowed;
}

.qty-display {
    min-width: 40px;
    text-align: center;
    font-weight: 600;
    color: #333;
}

/* Résumé total */
.booking-summary {
    background: #f8f9fa;
    border-radius: 10px;
    padding: 1rem;
    margin: 1.5rem 0;
    display: none;
}

.booking-summary.show {
    display: block;
    animation: slideDown 0.3s ease;
}

@keyframes slideDown {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}

.summary-line {
    display: flex;
    justify-content: space-between;
    margin-bottom: 0.5rem;
}

.summary-total {
    font-size: 1.2rem;
    font-weight: 700;
    color: #FF6B35;
    border-top: 1px solid #ddd;
    padding-top: 0.5rem;
    margin-top: 0.5rem;
}

/* Bouton principal */
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

.btn-reserve:hover {
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

.btn-timer-info {
    font-size: 0.85rem;
    opacity: 0.9;
    margin-top: 0.25rem;
}

/* ===== INFORMATIONS UTILES - APRÈS FORMULAIRE EN MOBILE ===== */
.event-details {
    margin-top: 2rem;
}

.detail-card {
    background: white;
    border-radius: 15px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    padding: 1.5rem;
    margin-bottom: 1.5rem;
}

.detail-title {
    font-size: 1.2rem;
    font-weight: 600;
    color: #333;
    margin-bottom: 1rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.detail-title i {
    color: #FF6B35;
    font-size: 1.1rem;
}

/* ===== RESPONSIVE DESKTOP ===== */
@media (min-width: 768px) {
    .event-title {
        font-size: 2.5rem;
    }
    
    .hero-content {
        padding: 3rem 0;
    }
    
    .event-quick-info {
        gap: 2rem;
    }
    
    .info-item {
        font-size: 1rem;
    }
    
    .booking-section {
        position: sticky;
        top: 100px;
        margin-top: 0;
    }
    
    .event-details {
        margin-top: 0;
    }
}

@media (min-width: 992px) {
    .event-title {
        font-size: 3rem;
    }
    
    .desktop-layout {
        display: flex;
        gap: 2rem;
        align-items: flex-start;
    }
    
    .desktop-content {
        flex: 1;
    }
    
    .desktop-sidebar {
        width: 380px;
        flex-shrink: 0;
    }
}

/* Feedback visuel */
.shake {
    animation: shake 0.5s;
}

@keyframes shake {
    0%, 100% { transform: translateX(0); }
    25% { transform: translateX(-5px); }
    75% { transform: translateX(5px); }
}

.success-feedback {
    background: #d4edda;
    border: 1px solid #c3e6cb;
    color: #155724;
    padding: 1rem;
    border-radius: 8px;
    margin-top: 1rem;
    display: none;
}

.error-feedback {
    background: #f8d7da;
    border: 1px solid #f5c6cb;
    color: #721c24;
    padding: 1rem;
    border-radius: 8px;
    margin-top: 1rem;
    display: none;
}
</style>
@endpush

@section('content')
<!-- Hero Section -->
<section class="event-hero">
    <div class="hero-overlay"></div>
    <div class="container">
        <div class="hero-content">
            <div class="category-badge mb-3">
                <i class="fas fa-tag me-2"></i>{{ $event->category->name }}
            </div>
            <h1 class="event-title">{{ $event->title }}</h1>
            
            <div class="event-quick-info">
                <div class="info-item">
                    <i class="fas fa-calendar-alt"></i>
                    <span>{{ $event->formatted_event_date }}</span>
                </div>
                <div class="info-item">
                    <i class="fas fa-clock"></i>
                    <span>{{ $event->event_time ?? '20h00' }}</span>
                </div>
                <div class="info-item">
                    <i class="fas fa-map-marker-alt"></i>
                    <span>{{ $event->venue }}</span>
                </div>
                @if($event->ticketTypes->count() > 0)
                <div class="info-item">
                    <i class="fas fa-ticket-alt"></i>
                    <span>À partir de {{ number_format($event->ticketTypes->min('price')) }} FCFA</span>
                </div>
                @endif
            </div>
        </div>
    </div>
</section>

<div class="container">
    <!-- Layout Mobile: Formulaire en premier -->
    <div class="d-lg-none">
        <!-- Formulaire de réservation mobile -->
        @include('events.partials.booking-form-mobile')
        
        <!-- Informations utiles après le formulaire -->
        @include('events.partials.event-details-mobile')
    </div>
    
    <!-- Layout Desktop: Côte à côte -->
    <div class="d-none d-lg-block">
        <div class="desktop-layout">
            <div class="desktop-content">
                @include('events.partials.event-details-desktop')
            </div>
            <div class="desktop-sidebar">
                @include('events.partials.booking-form-desktop')
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
// ===== SYSTÈME UNIFIÉ DE RÉSERVATION ===== 
let selectedTickets = {};
let cartTimer = null;
let timeRemaining = 15 * 60; // 15 minutes

// Initialisation
document.addEventListener('DOMContentLoaded', function() {
    console.log('Système de réservation initialisé');
    initializeQuantityControls();
    setupCartTimer();
});

// Contrôles de quantité
function initializeQuantityControls() {
    document.querySelectorAll('.qty-btn').forEach(button => {
        button.addEventListener('click', function() {
            const action = this.dataset.action;
            const ticketTypeId = this.dataset.ticketType;
            
            if (action === 'increase') {
                increaseQuantity(ticketTypeId);
            } else if (action === 'decrease') {
                decreaseQuantity(ticketTypeId);
            }
        });
    });
}

function increaseQuantity(ticketTypeId) {
    const display = document.getElementById(`qty_${ticketTypeId}`);
    const currentQty = parseInt(display.textContent);
    const maxPerOrder = parseInt(display.dataset.max);
    const available = parseInt(display.dataset.available);
    
    if (currentQty < Math.min(maxPerOrder, available)) {
        const newQty = currentQty + 1;
        display.textContent = newQty;
        selectedTickets[ticketTypeId] = newQty;
        
        // Feedback visuel
        const ticketType = display.closest('.ticket-type');
        ticketType.classList.add('has-selection');
        
        updateSummary();
        updateButtons();
    } else {
        // Shake animation pour indiquer la limite
        display.closest('.quantity-controls').classList.add('shake');
        setTimeout(() => {
            display.closest('.quantity-controls').classList.remove('shake');
        }, 500);
    }
}

function decreaseQuantity(ticketTypeId) {
    const display = document.getElementById(`qty_${ticketTypeId}`);
    const currentQty = parseInt(display.textContent);
    
    if (currentQty > 0) {
        const newQty = currentQty - 1;
        display.textContent = newQty;
        
        if (newQty === 0) {
            delete selectedTickets[ticketTypeId];
            display.closest('.ticket-type').classList.remove('has-selection');
        } else {
            selectedTickets[ticketTypeId] = newQty;
        }
        
        updateSummary();
        updateButtons();
    }
}

// Mise à jour du résumé
function updateSummary() {
    const summaryDiv = document.getElementById('bookingSummary');
    const summaryContent = document.getElementById('summaryContent');
    
    let total = 0;
    let totalTickets = 0;
    let html = '';
    
    Object.keys(selectedTickets).forEach(ticketTypeId => {
        const quantity = selectedTickets[ticketTypeId];
        const display = document.getElementById(`qty_${ticketTypeId}`);
        const ticketName = display.dataset.name;
        const ticketPrice = parseInt(display.dataset.price);
        const lineTotal = quantity * ticketPrice;
        
        html += `
            <div class="summary-line">
                <span>${quantity}x ${ticketName}</span>
                <span>${lineTotal.toLocaleString()} FCFA</span>
            </div>
        `;
        
        total += lineTotal;
        totalTickets += quantity;
    });
    
    if (totalTickets > 0) {
        html += `
            <div class="summary-line summary-total">
                <span><strong>Total (${totalTickets} billet${totalTickets > 1 ? 's' : ''})</strong></span>
                <span><strong>${total.toLocaleString()} FCFA</strong></span>
            </div>
        `;
        
        summaryContent.innerHTML = html;
        summaryDiv.classList.add('show');
    } else {
        summaryDiv.classList.remove('show');
    }
}

// Mise à jour des boutons
function updateButtons() {
    const reserveBtn = document.getElementById('reserveBtn');
    const totalTickets = Object.values(selectedTickets).reduce((sum, qty) => sum + qty, 0);
    
    if (totalTickets > 0) {
        reserveBtn.disabled = false;
        reserveBtn.innerHTML = `
            <i class="fas fa-clock me-2"></i>
            Réserver ${totalTickets} billet${totalTickets > 1 ? 's' : ''} (15 min)
            <div class="btn-timer-info">Mise de côté automatique</div>
        `;
    } else {
        reserveBtn.disabled = true;
        reserveBtn.innerHTML = `
            <i class="fas fa-ticket-alt me-2"></i>
            Sélectionnez vos billets
        `;
    }
}

// Timer du panier (15 minutes)
function setupCartTimer() {
    const timerDisplay = document.getElementById('timerDisplay');
    
    function updateTimer() {
        const minutes = Math.floor(timeRemaining / 60);
        const seconds = timeRemaining % 60;
        
        if (timerDisplay) {
            timerDisplay.textContent = `${minutes}:${seconds.toString().padStart(2, '0')}`;
        }
        
        if (timeRemaining <= 0) {
            clearInterval(cartTimer);
            // Rediriger ou vider le panier
            showTimerExpired();
        }
        
        timeRemaining--;
    }
    
    // Démarrer le timer seulement si il y a des tickets sélectionnés
    function startTimer() {
        if (!cartTimer) {
            cartTimer = setInterval(updateTimer, 1000);
        }
    }
    
    // Exposer la fonction pour l'utiliser lors de la sélection
    window.startCartTimer = startTimer;
}

function showTimerExpired() {
    alert('⏰ Votre réservation a expiré. Les billets ont été libérés.');
    location.reload();
}

// Réservation
function reserveTickets() {
    const totalTickets = Object.values(selectedTickets).reduce((sum, qty) => sum + qty, 0);
    
    if (totalTickets === 0) {
        showFeedback('Veuillez sélectionner au moins un billet', 'error');
        return;
    }
    
    // Démarrer le timer
    if (window.startCartTimer) {
        window.startCartTimer();
    }
    
    // Préparer les données
    const formData = new FormData();
    formData.append('_token', document.querySelector('meta[name="csrf-token"]').content);
    formData.append('event_id', '{{ $event->id }}');
    
    Object.keys(selectedTickets).forEach(ticketTypeId => {
        formData.append(`tickets[${ticketTypeId}]`, selectedTickets[ticketTypeId]);
    });
    
    // Envoyer la requête
    fetch('{{ route("cart.add") }}', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showFeedback(data.message, 'success');
            
            // Rediriger vers le panier après 2 secondes
            setTimeout(() => {
                window.location.href = '{{ route("cart.show") }}';
            }, 2000);
        } else {
            showFeedback(data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        showFeedback('Erreur lors de la réservation', 'error');
    });
}

// Feedback utilisateur
function showFeedback(message, type) {
    // Supprimer les anciens feedbacks
    document.querySelectorAll('.success-feedback, .error-feedback').forEach(el => {
        el.style.display = 'none';
    });
    
    const feedbackClass = type === 'success' ? 'success-feedback' : 'error-feedback';
    let feedback = document.querySelector(`.${feedbackClass}`);
    
    if (!feedback) {
        feedback = document.createElement('div');
        feedback.className = feedbackClass;
        document.getElementById('bookingForm').appendChild(feedback);
    }
    
    feedback.textContent = message;
    feedback.style.display = 'block';
    
    // Auto-hide après 5 secondes
    setTimeout(() => {
        feedback.style.display = 'none';
    }, 5000);
}
</script>
@endpush