{{-- =============================================== --}}
{{-- resources/views/events/show.blade.php - SYSTÈME UNIFIÉ CORRIGÉ --}}
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

/* ===== RESPONSIVE DESKTOP ===== */
.desktop-layout {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 3rem;
    align-items: start;
}

.desktop-sidebar {
    position: sticky;
    top: 100px;
}

@media (min-width: 992px) {
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
}

/* Alertes de système */
.cart-alert {
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    border-radius: 8px;
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

.selection-summary {
    background: rgba(255,107,53,0.1);
    border-radius: 8px;
    padding: 1rem;
    margin-bottom: 1rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.summary-total {
    font-size: 1.2rem;
    font-weight: bold;
    color: #FF6B35;
}
</style>
@endpush

@section('content')
<!-- Hero Section -->
<section class="event-hero">
    <div class="container">
        <div class="hero-content">
            <h1 class="event-title">{{ $event->title }}</h1>
            
            <div class="event-quick-info">
                <div class="info-item">
                    <i class="fas fa-calendar-alt"></i>
                    <span>{{ $event->formatted_event_date ?? $event->event_date->format('d/m/Y') }}</span>
                </div>
                <div class="info-item">
                    <i class="fas fa-clock"></i>
                    <span>{{ $event->formatted_event_time ?? '20h00' }}</span>
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
<!-- Charger jQuery AVANT le script de réservation -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<!-- SYSTÈME UNIFIÉ DE RÉSERVATION - SCRIPT CORRIGÉ -->
<script>
// ===== CLASSE PRINCIPALE DE GESTION DES RÉSERVATIONS =====
class BookingSystem {
    constructor() {
        this.selectedTickets = {};
        this.isTimerActive = false;
        this.timerInterval = null;
        this.timeRemaining = 15 * 60;
        
        this.init();
    }
    
    init() {
        console.log('🚀 Initialisation du système de réservation');
        this.setupEventListeners();
        this.updateButtons();
    }
    
    setupEventListeners() {
        // Gestionnaires pour les boutons + et -
        $(document).on('click', '.qty-btn', (e) => {
            e.preventDefault();
            const button = $(e.currentTarget);
            const action = button.data('action');
            const ticketTypeId = button.data('ticket-type');
            
            console.log(`Action: ${action}, Ticket: ${ticketTypeId}`);
            
            if (action === 'increase') {
                this.increaseQuantity(ticketTypeId);
            } else if (action === 'decrease') {
                this.decreaseQuantity(ticketTypeId);
            }
        });
        
        // Gestionnaire pour le bouton de réservation
        $(document).on('click', '#reserveBtn, #reserveBtnDesktop', () => {
            this.reserveTickets();
        });
    }
    
    increaseQuantity(ticketTypeId) {
        // Sélectionner TOUS les affichages (mobile et desktop)
        const displays = $(`#qty_${ticketTypeId}, #qty_${ticketTypeId}_desktop`);
        if (displays.length === 0) return;
        
        const display = displays.first();
        const currentQty = parseInt(display.text()) || 0;
        const maxPerOrder = parseInt(display.data('max')) || 10;
        const available = parseInt(display.data('available')) || 0;
        
        if (currentQty < Math.min(maxPerOrder, available)) {
            const newQty = currentQty + 1;
            this.setQuantity(ticketTypeId, newQty);
        } else {
            this.showLimitFeedback(display);
        }
    }
    
    decreaseQuantity(ticketTypeId) {
        const displays = $(`#qty_${ticketTypeId}, #qty_${ticketTypeId}_desktop`);
        if (displays.length === 0) return;
        
        const currentQty = parseInt(displays.first().text()) || 0;
        
        if (currentQty > 0) {
            const newQty = currentQty - 1;
            this.setQuantity(ticketTypeId, newQty);
        }
    }
    
    setQuantity(ticketTypeId, quantity) {
        quantity = Math.max(0, parseInt(quantity) || 0);
        
        // Mettre à jour TOUS les affichages (mobile et desktop)
        const displays = $(`#qty_${ticketTypeId}, #qty_${ticketTypeId}_desktop`);
        displays.each(function() {
            $(this).text(quantity);
        });
        
        // Mettre à jour la sélection
        if (quantity > 0) {
            this.selectedTickets[ticketTypeId] = quantity;
            displays.closest('.ticket-type').addClass('has-selection');
        } else {
            delete this.selectedTickets[ticketTypeId];
            displays.closest('.ticket-type').removeClass('has-selection');
        }
        
        this.updateSummary();
        this.updateButtons();
    }
    
    updateSummary() {
        const totalTickets = this.getTotalTickets();
        const totalPrice = this.getTotalPrice();
        
        if (totalTickets === 0) {
            $('#summaryContent, #summaryContentDesktop').html('');
            return;
        }
        
        const summaryHtml = `
            <div class="selection-summary">
                <div class="summary-item">
                    <strong>${totalTickets} billet${totalTickets > 1 ? 's' : ''}</strong>
                </div>
                <div class="summary-total">${this.formatPrice(totalPrice)} FCFA</div>
            </div>
        `;
        
        $('#summaryContent, #summaryContentDesktop').html(summaryHtml);
    }
    
    updateButtons() {
        const totalTickets = this.getTotalTickets();
        const buttons = $('#reserveBtn, #reserveBtnDesktop');
        
        if (totalTickets === 0) {
            buttons.prop('disabled', true)
                   .html('<i class="fas fa-ticket-alt me-2"></i>Sélectionnez vos billets');
        } else {
            buttons.prop('disabled', false)
                   .html(`<i class="fas fa-shopping-cart me-2"></i>Réserver ${totalTickets} billet${totalTickets > 1 ? 's' : ''}`);
        }
    }
    
    async reserveTickets() {
        const totalTickets = this.getTotalTickets();
        
        if (totalTickets === 0) {
            this.showAlert('Veuillez sélectionner au moins un billet', 'error');
            return;
        }
        
        const buttons = $('#reserveBtn, #reserveBtnDesktop');
        const originalHtml = buttons.html();
        
        buttons.prop('disabled', true)
               .html('<i class="fas fa-spinner fa-spin me-2"></i>Réservation...');
        
        try {
            // Préparer les données pour l'ajout au panier
            const requests = [];
            
            Object.keys(this.selectedTickets).forEach(ticketTypeId => {
                const quantity = this.selectedTickets[ticketTypeId];
                
                const formData = new FormData();
                formData.append('_token', $('meta[name="csrf-token"]').attr('content'));
                formData.append('ticket_type_id', ticketTypeId);
                formData.append('quantity', quantity);
                
                requests.push(
                    fetch('/cart/add', {
                        method: 'POST',
                        body: formData
                    })
                );
            });
            
            // Envoyer toutes les requêtes
            const responses = await Promise.all(requests);
            const results = await Promise.all(responses.map(r => r.json()));
            
            // Vérifier si toutes les requêtes ont réussi
            const allSuccessful = results.every(result => result.success);
            
            if (allSuccessful) {
                this.showAlert('Billets ajoutés au panier avec succès !', 'success');
                
                // Démarrer le timer
                this.startTimer();
                
                // Rediriger vers le panier
                setTimeout(() => {
                    window.location.href = '/cart';
                }, 1500);
            } else {
                const errors = results.filter(r => !r.success).map(r => r.message);
                this.showAlert(`Erreurs: ${errors.join(', ')}`, 'error');
                
                buttons.prop('disabled', false).html(originalHtml);
            }
            
        } catch (error) {
            console.error('Erreur réservation:', error);
            this.showAlert('Erreur lors de la réservation. Veuillez réessayer.', 'error');
            
            buttons.prop('disabled', false).html(originalHtml);
        }
    }
    
    startTimer() {
        if (this.isTimerActive) return;
        
        this.isTimerActive = true;
        this.timeRemaining = 15 * 60;
        
        // Afficher les conteneurs de timer
        $('#timerContainer, #timerContainerDesktop').show();
        
        this.timerInterval = setInterval(() => {
            this.timeRemaining--;
            this.updateTimerDisplay();
            
            if (this.timeRemaining <= 0) {
                this.expireTimer();
            }
        }, 1000);
    }
    
    updateTimerDisplay() {
        const minutes = Math.floor(this.timeRemaining / 60);
        const seconds = this.timeRemaining % 60;
        const display = `${minutes}:${seconds.toString().padStart(2, '0')}`;
        
        $('#timerDisplay, #timerDisplayDesktop').text(display);
        
        // Alertes de temps
        if (this.timeRemaining === 300) { // 5 minutes
            this.showAlert('⏰ Plus que 5 minutes pour finaliser votre réservation!', 'warning');
        } else if (this.timeRemaining === 60) { // 1 minute
            this.showAlert('🚨 Plus qu\'1 minute ! Votre panier va expirer !', 'danger');
        }
    }
    
    expireTimer() {
        clearInterval(this.timerInterval);
        this.isTimerActive = false;
        
        this.showAlert('⏰ Votre réservation a expiré. Les billets ont été libérés.', 'info');
        
        setTimeout(() => {
            location.reload();
        }, 3000);
    }
    
    // Utilitaires
    getTotalTickets() {
        return Object.values(this.selectedTickets).reduce((sum, qty) => sum + qty, 0);
    }
    
    getTotalPrice() {
        let total = 0;
        Object.keys(this.selectedTickets).forEach(ticketTypeId => {
            const quantity = this.selectedTickets[ticketTypeId];
            const displays = $(`#qty_${ticketTypeId}, #qty_${ticketTypeId}_desktop`);
            const price = parseInt(displays.first().data('price')) || 0;
            total += quantity * price;
        });
        return total;
    }
    
    formatPrice(price) {
        return new Intl.NumberFormat('fr-FR').format(price);
    }
    
    showAlert(message, type = 'info') {
        const alertClass = {
            'success': 'alert-success',
            'error': 'alert-danger',
            'warning': 'alert-warning',
            'info': 'alert-info',
            'danger': 'alert-danger'
        }[type] || 'alert-info';
        
        const icon = {
            'success': 'fas fa-check-circle',
            'error': 'fas fa-exclamation-triangle',
            'warning': 'fas fa-exclamation-triangle',
            'info': 'fas fa-info-circle',
            'danger': 'fas fa-exclamation-triangle'
        }[type] || 'fas fa-info-circle';
        
        $('.cart-alert').remove();
        
        const alertHtml = `
            <div class="alert ${alertClass} alert-dismissible fade show position-fixed cart-alert" 
                 style="top: 20px; right: 20px; z-index: 9999; min-width: 320px; max-width: 400px;">
                <i class="${icon} me-2"></i>
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `;
        
        $('body').append(alertHtml);
        
        setTimeout(() => {
            $('.cart-alert').fadeOut(500, function() {
                $(this).remove();
            });
        }, 5000);
    }
    
    showLimitFeedback(element) {
        element.addClass('qty-limit-reached');
        setTimeout(() => {
            element.removeClass('qty-limit-reached');
        }, 1000);
        
        this.showAlert('Limite maximum atteinte pour ce type de billet', 'warning');
    }
}

// ===== INITIALISATION =====
let bookingSystem;

// Initialisation quand jQuery et le DOM sont prêts
$(document).ready(function() {
    console.log('🚀 jQuery et DOM prêts - Démarrage du système de billetterie');
    bookingSystem = new BookingSystem();
    
    // Exposer les fonctions globalement pour compatibilité
    window.bookingSystem = bookingSystem;
    window.increaseQuantity = (ticketTypeId) => bookingSystem.increaseQuantity(ticketTypeId);
    window.decreaseQuantity = (ticketTypeId) => bookingSystem.decreaseQuantity(ticketTypeId);
    window.reserveTickets = () => bookingSystem.reserveTickets();
});
</script>
@endpush