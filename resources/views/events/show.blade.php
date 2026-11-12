{{-- =============================================== --}}
{{-- resources/views/events/show.blade.php - SYSTÈME UNIFIÉ CORRIGÉ --}}
@extends('layouts.app')

@section('title', $event->title . ' - ClicBillet CI')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/event-detail.css') }}">
<style>
/* ===== SYSTÈME UNIFIÉ - MOBILE FIRST ===== */

.event-hero {
    position: relative;
    min-height: 300px;
    background: linear-gradient(135deg, var(--brand), #1a237e);
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
    color: var(--brand);
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
    background: rgba(var(--brand-rgb), 0.1);
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
    color: var(--brand);
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
                    <i class="fas fa-calendar-alt" aria-hidden="true"></i>
                    <span>{{ $event->long_date_label }}</span>
                </div>
                @if($event->time_range_label !== '')
                <div class="info-item">
                    <i class="fas fa-clock" aria-hidden="true"></i>
                    <span>{{ $event->time_range_label }}</span>
                </div>
                @endif
                <div class="info-item">
                    <i class="fas fa-map-marker-alt"></i>
                    <span>{{ $event->venue }}</span>
                </div>
                @if($event->startingPriceLabel())
                <div class="info-item">
                    <i class="fas fa-ticket-alt"></i>
                    <span>{{ $event->startingPriceLabel() }}</span>
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

    @if($event->showsBuyBar())
        <x-event-buy-bar :event="$event" />
    @endif
    
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
<script src="{{ asset('js/bfcache-reset.js') }}"></script>
<script src="{{ asset('js/event-buy-bar.js') }}" defer></script>
<!-- Charger jQuery AVANT le script de réservation -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<!-- SYSTÈME UNIFIÉ DE RÉSERVATION - SCRIPT CORRIGÉ -->
<script>
// ===== CLASSE PRINCIPALE DE GESTION DES RÉSERVATIONS =====
// ===== CLASSE PRINCIPALE DE GESTION DES RÉSERVATIONS =====
class BookingSystem {
    constructor() {
        this.selectedTickets = {};
        this.isTimerActive = false;
        this.timerInterval = null;
        this.timeRemaining = 15 * 60; // 15 minutes en secondes
        
        this.init();
    }
    
    init() {
        console.log('🚀 Initialisation du système de réservation');
        this.setupEventListeners();
        this.updateButtons();
        this.updateSummary();
    }
    
    setupEventListeners() {
        console.log('📡 Configuration des event listeners');
        
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
        $(document).on('click', '#reserveBtn, #reserveBtnDesktop', (e) => {
            e.preventDefault();
            console.log('🎫 Bouton réservation cliqué');
            this.reserveTickets();
        });
        
        // Debug des boutons existants
        console.log('Boutons trouvés:', {
            mobile: $('#reserveBtn').length,
            desktop: $('#reserveBtnDesktop').length,
            qtyButtons: $('.qty-btn').length
        });
    }
    
    increaseQuantity(ticketTypeId) {
        console.log(`➕ Augmenter quantité pour ticket ${ticketTypeId}`);
        
        // Sélectionner TOUS les affichages (mobile et desktop)
        const displays = $(`#qty_${ticketTypeId}, #qty_${ticketTypeId}_desktop`);
        if (displays.length === 0) {
            console.error(`❌ Aucun affichage trouvé pour ticket ${ticketTypeId}`);
            return;
        }
        
        const display = displays.first();
        const currentQty = parseInt(display.text()) || 0;
        const maxPerOrder = parseInt(display.data('max')) || 10;
        const available = parseInt(display.data('available')) || 0;
        const maxAllowed = Math.min(maxPerOrder, available);
        // I-a : plafond sur le TOTAL de billets (même règle que le serveur : panier et checkout)
        const maxTotal = {{ \App\Services\CheckoutService::maxTicketsPerOrder() }};
        
        console.log(`Quantité actuelle: ${currentQty}, Max: ${maxAllowed}`);
        
        if (currentQty < maxAllowed && this.getTotalTickets() < maxTotal) {
            const newQty = currentQty + 1;
            this.setQuantity(ticketTypeId, newQty);
        } else {
            this.showLimitFeedback(display);
            console.log(`⚠️ Limite atteinte pour ticket ${ticketTypeId}`);
        }
    }
    
    decreaseQuantity(ticketTypeId) {
        console.log(`➖ Diminuer quantité pour ticket ${ticketTypeId}`);
        
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
        
        console.log(`🔢 Mise à jour quantité: ticket ${ticketTypeId} = ${quantity}`);
        
        // Mettre à jour TOUS les affichages (mobile et desktop)
        const displays = $(`#qty_${ticketTypeId}, #qty_${ticketTypeId}_desktop`);
        displays.each(function() {
            $(this).text(quantity);
        });
        
        // Mettre à jour la sélection interne
        if (quantity > 0) {
            this.selectedTickets[ticketTypeId] = quantity;
            displays.closest('.ticket-type').addClass('has-selection');
        } else {
            delete this.selectedTickets[ticketTypeId];
            displays.closest('.ticket-type').removeClass('has-selection');
        }
        
        this.updateSummary();
        this.updateButtons();
        
        console.log('📊 État actuel:', this.selectedTickets);
    }
    
    updateSummary() {
        const totalTickets = this.getTotalTickets();
        const totalPrice = this.getTotalPrice();
        
        console.log(`📋 Résumé: ${totalTickets} billets, ${totalPrice} FCFA`);
        
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
        
        console.log(`🔘 Mise à jour boutons: ${totalTickets} billets sélectionnés`);
        
        // Le bouton « Ajouter au panier » garde son libellé ; « Réserver maintenant » envoie la sélection à checkout.direct
        buttons.prop('disabled', totalTickets === 0);
        const holder = document.getElementById('directTicketsDesktop');
        if (holder) {
            holder.textContent = '';
            Object.keys(this.selectedTickets).forEach(id => {
                if (this.selectedTickets[id] > 0) {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'tickets[' + id + ']';
                    input.value = this.selectedTickets[id];
                    holder.appendChild(input);
                }
            });
            $('#reserveNowDesktop').prop('disabled', totalTickets === 0);
        }
    }
    
    // ===== FONCTION DE RÉSERVATION PRINCIPALE =====
    async reserveTickets() {
        console.log('🚀 Début de la réservation');
        
        const totalTickets = this.getTotalTickets();
        
        if (totalTickets === 0) {
            this.showAlert('Veuillez sélectionner au moins un billet', 'error');
            console.log('❌ Aucun billet sélectionné');
            return;
        }
        
        // Préparer les données pour l'API
        const ticketsData = [];
        Object.keys(this.selectedTickets).forEach(ticketTypeId => {
            ticketsData.push({
                ticket_type_id: ticketTypeId,
                quantity: this.selectedTickets[ticketTypeId]
            });
        });
        
        const requestData = {
            event_id: this.getEventId(),
            tickets: ticketsData
        };
        
        console.log('📤 Données envoyées:', requestData);
        
        // Désactiver les boutons pendant la requête
        const reserveButtons = $('#reserveBtn, #reserveBtnDesktop');
        const originalHtml = reserveButtons.html();
        
        reserveButtons.prop('disabled', true)
                     .html('<i class="fas fa-spinner fa-spin me-2"></i>Réservation en cours...');
        
        try {
            const response = await fetch('{{ route("cart.add") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json'
                },
                body: JSON.stringify(requestData)
            });
            
            console.log('📡 Réponse statut:', response.status);
            
            if (!response.ok) {
                throw new Error(`Erreur HTTP: ${response.status}`);
            }
            
            const data = await response.json();
            console.log('📥 Réponse reçue:', data);
            
            if (data.success) {
                // Animation de succès
                reserveButtons.html('<i class="fas fa-check me-2"></i>Ajouté au panier !');
                
                this.showAlert(data.message, 'success');
                
                // Mettre à jour le compteur du header si présent
                this.updateCartBadge(data.cart_count);
                
                // Redirection après délai
                setTimeout(() => {
                    if (data.redirect) {
                        window.location.href = data.redirect;
                    } else {
                        window.location.href = '{{ route("cart.show") }}';
                    }
                }, 2000);
                
            } else {
                throw new Error(data.message || 'Erreur lors de la réservation');
            }
            
        } catch (error) {
            console.error('❌ Erreur réservation:', error);
            
            // Remettre le bouton en état
            reserveButtons.prop('disabled', false)
                         .html(originalHtml);
            
            this.showAlert(error.message || 'Erreur lors de la réservation. Veuillez réessayer.', 'error');
        }
    }
    
    // ===== GESTION DU TIMER =====
    startTimer() {
        if (this.isTimerActive) return;
        
        this.isTimerActive = true;
        $('#timerContainer, #timerContainerDesktop').show();
        
        this.timerInterval = setInterval(() => {
            this.timeRemaining--;
            
            const minutes = Math.floor(this.timeRemaining / 60);
            const seconds = this.timeRemaining % 60;
            const timeString = `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
            
            $('#timerDisplay, #timerDisplayDesktop').text(timeString);
            
            // Alertes de fin de temps
            if (this.timeRemaining === 300) { // 5 minutes
                this.showAlert('⏰ Plus que 5 minutes ! Finalisez votre réservation.', 'warning');
            } else if (this.timeRemaining === 60) { // 1 minute
                this.showAlert('🚨 Plus qu\'1 minute ! Votre panier va expirer !', 'danger');
            } else if (this.timeRemaining <= 0) {
                this.expireTimer();
            }
        }, 1000);
        
        console.log('⏰ Timer démarré: 15 minutes');
    }
    
    expireTimer() {
        clearInterval(this.timerInterval);
        this.isTimerActive = false;
        
        this.showAlert('⏰ Votre réservation a expiré. Les billets ont été libérés.', 'info');
        
        setTimeout(() => {
            location.reload();
        }, 3000);
    }
    
    // ===== UTILITAIRES =====
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
    
    getEventId() {
        // Plusieurs façons de récupérer l'ID de l'événement
        return $('input[name="event_id"]').val() || 
               $('[data-event-id]').data('event-id') || 
               window.eventId || 
               {{ $event->id ?? 'null' }};
    }
    
    updateCartBadge(count) {
        const badge = $('#cartBadge');
        if (badge.length && count > 0) {
            badge.text(count).show().addClass('animate');
            setTimeout(() => badge.removeClass('animate'), 600);
        }
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
        
        // Supprimer les anciennes alertes
        $('.cart-alert').remove();
        
        // S-9 : le message (qui peut contenir un nom de type de billet saisi par l'organisateur) est inséré
        // comme TEXTE (nœud de texte), jamais interprété comme HTML. Seuls alertClass/icon (valeurs internes) servent de classes.
        const alertEl = $('<div>', {
            'class': `alert ${alertClass} alert-dismissible fade show position-fixed cart-alert`,
            style: 'top: 20px; right: 20px; z-index: 9999; min-width: 320px; max-width: 400px;'
        });
        alertEl.append($('<i>', { 'class': `${icon} me-2` }));
        alertEl.append(document.createTextNode(String(message ?? '')));
        alertEl.append($('<button>', { type: 'button', 'class': 'btn-close', 'data-bs-dismiss': 'alert' }));

        $('body').append(alertEl);
        
        // Auto-remove après 5 secondes
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
    
    // Vérifier les prérequis
    if (typeof $ === 'undefined') {
        console.error('❌ jQuery non trouvé');
        return;
    }
    
    if (!$('meta[name="csrf-token"]').length) {
        console.error('❌ Token CSRF manquant');
        return;
    }
    
    bookingSystem = new BookingSystem();
    
    // Exposer les fonctions globalement pour compatibilité
    window.bookingSystem = bookingSystem;
    window.increaseQuantity = (ticketTypeId) => bookingSystem.increaseQuantity(ticketTypeId);
    window.decreaseQuantity = (ticketTypeId) => bookingSystem.decreaseQuantity(ticketTypeId);
    window.reserveTickets = () => bookingSystem.reserveTickets();
    
    console.log('✅ Système de réservation initialisé');
});
</script>
@endpush