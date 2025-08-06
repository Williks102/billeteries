// ===============================================
// SYSTÈME UNIFIÉ DE PANIER AVEC TIMER 15 MINUTES
// resources/js/cart-unified.js
// ===============================================

class UnifiedCartSystem {
    constructor() {
        this.selectedTickets = {};
        this.cartTimer = null;
        this.timeRemaining = 0;
        this.timerInterval = null;
        this.isTimerActive = false;
        
        this.init();
    }
    
    init() {
        console.log('🛒 Système de panier unifié initialisé');
        this.setupCSRF();
        this.initializeQuantityControls();
        this.loadExistingTimer();
        this.setupEventListeners();
    }
    
    setupCSRF() {
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });
    }
    
    // ===== GESTION DES QUANTITÉS =====
    initializeQuantityControls() {
        $(document).on('click', '.qty-btn', (e) => {
            const button = $(e.currentTarget);
            const action = button.data('action');
            const ticketTypeId = button.data('ticket-type');
            
            if (action === 'increase') {
                this.increaseQuantity(ticketTypeId);
            } else if (action === 'decrease') {
                this.decreaseQuantity(ticketTypeId);
            }
        });
        
        // Contrôles direct input (si présent)
        $(document).on('change', '.qty-input', (e) => {
            const input = $(e.currentTarget);
            const ticketTypeId = input.data('ticket-type');
            const quantity = parseInt(input.val()) || 0;
            this.setQuantity(ticketTypeId, quantity);
        });
    }
    
    increaseQuantity(ticketTypeId) {
        const displays = $(`#qty_${ticketTypeId}, #qty_${ticketTypeId}_desktop`);
        const display = displays.first();
        
        if (display.length === 0) return;
        
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
        const display = displays.first();
        
        if (display.length === 0) return;
        
        const currentQty = parseInt(display.text()) || 0;
        
        if (currentQty > 0) {
            const newQty = currentQty - 1;
            this.setQuantity(ticketTypeId, newQty);
        }
    }
    
    setQuantity(ticketTypeId, quantity) {
        quantity = Math.max(0, parseInt(quantity) || 0);
        
        // Mettre à jour les affichages (mobile et desktop)
        const displays = $(`#qty_${ticketTypeId}, #qty_${ticketTypeId}_desktop`);
        displays.each(function() {
            $(this).text(quantity);
        });
        
        // Mettre à jour la sélection
        if (quantity > 0) {
            this.selectedTickets[ticketTypeId] = quantity;
            
            // Ajouter classe de sélection
            displays.closest('.ticket-type').addClass('has-selection');
        } else {
            delete this.selectedTickets[ticketTypeId];
            
            // Retirer classe de sélection
            displays.closest('.ticket-type').removeClass('has-selection');
        }
        
        this.updateSummary();
        this.updateButtons();
    }
    
    showLimitFeedback(element) {
        element.closest('.quantity-controls').addClass('shake');
        setTimeout(() => {
            element.closest('.quantity-controls').removeClass('shake');
        }, 500);
    }
    
    // ===== GESTION DU RÉSUMÉ =====
    updateSummary() {
        const summaryElements = $('#bookingSummary, #bookingSummaryDesktop');
        const contentElements = $('#summaryContent, #summaryContentDesktop');
        
        let total = 0;
        let totalTickets = 0;
        let html = '';
        
        Object.keys(this.selectedTickets).forEach(ticketTypeId => {
            const quantity = this.selectedTickets[ticketTypeId];
            const display = $(`#qty_${ticketTypeId}, #qty_${ticketTypeId}_desktop`).first();
            
            if (display.length === 0) return;
            
            const ticketName = display.data('name');
            const ticketPrice = parseInt(display.data('price'));
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
            
            contentElements.html(html);
            summaryElements.addClass('show');
        } else {
            summaryElements.removeClass('show');
        }
    }
    
    // ===== GESTION DES BOUTONS =====
    updateButtons() {
        const reserveButtons = $('#reserveBtn, #reserveBtnDesktop');
        const totalTickets = Object.values(this.selectedTickets).reduce((sum, qty) => sum + qty, 0);
        
        if (totalTickets > 0) {
            reserveButtons.prop('disabled', false);
            reserveButtons.html(`
                <i class="fas fa-clock me-2"></i>
                Réserver ${totalTickets} billet${totalTickets > 1 ? 's' : ''} (15 min)
                <div class="btn-timer-info">Mise de côté automatique</div>
            `);
        } else {
            reserveButtons.prop('disabled', true);
            reserveButtons.html(`
                <i class="fas fa-ticket-alt me-2"></i>
                Sélectionnez vos billets
            `);
        }
    }
    
    // ===== GESTION DU TIMER =====
    loadExistingTimer() {
        // Vérifier s'il y a déjà un timer actif
        fetch('/cart/data')
            .then(response => response.json())
            .then(data => {
                if (data.hasTimer && data.timeRemaining > 0) {
                    this.timeRemaining = data.timeRemaining * 60; // Convertir en secondes
                    this.startTimer();
                    this.showTimerDisplay();
                }
            })
            .catch(error => {
                console.error('Erreur chargement timer:', error);
            });
    }
    
    startTimer() {
        if (this.timerInterval) {
            clearInterval(this.timerInterval);
        }
        
        this.isTimerActive = true;
        this.showTimerDisplay();
        
        this.timerInterval = setInterval(() => {
            this.updateTimerDisplay();
            
            if (this.timeRemaining <= 0) {
                this.expireTimer();
            }
            
            this.timeRemaining--;
        }, 1000);
        
        console.log('⏰ Timer démarré:', this.timeRemaining, 'secondes');
    }
    
    showTimerDisplay() {
        const timerContainers = $('#timerContainer, #timerContainerDesktop');
        timerContainers.show();
    }
    
    updateTimerDisplay() {
        const minutes = Math.floor(this.timeRemaining / 60);
        const seconds = this.timeRemaining % 60;
        const timeString = `${minutes}:${seconds.toString().padStart(2, '0')}`;
        
        const timerDisplays = $('#timerDisplay, #timerDisplayDesktop');
        timerDisplays.text(timeString);
        
        // Alertes progressives
        if (this.timeRemaining === 300) { // 5 minutes
            this.showAlert('⚠️ Plus que 5 minutes ! Finalisez votre réservation.', 'warning');
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
    
    // ===== RÉSERVATION =====
    async reserveTickets() {
        const totalTickets = Object.values(this.selectedTickets).reduce((sum, qty) => sum + qty, 0);
        
        if (totalTickets === 0) {
            this.showAlert('Veuillez sélectionner au moins un billet', 'error');
            return;
        }
        
        // Désactiver le bouton pendant la requête
        const reserveButtons = $('#reserveBtn, #reserveBtnDesktop');
        const originalHtml = reserveButtons.html();
        
        reserveButtons.prop('disabled', true);
        reserveButtons.html(`
            <i class="fas fa-spinner fa-spin me-2"></i>
            Réservation en cours...
        `);
        
        try {
            // Préparer les données
            const formData = new FormData();
            formData.append('_token', $('meta[name="csrf-token"]').attr('content'));
            formData.append('event_id', this.getEventId());
            
            Object.keys(this.selectedTickets).forEach(ticketTypeId => {
                formData.append(`tickets[${ticketTypeId}]`, this.selectedTickets[ticketTypeId]);
            });
            
            // Envoyer la requête
            const response = await fetch('/cart/add', {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            
            if (data.success) {
                // Démarrer le timer avec les nouvelles données
                this.timeRemaining = 15 * 60; // 15 minutes en secondes
                this.startTimer();
                
                this.showAlert(data.message, 'success');
                
                // Rediriger vers le panier après 2 secondes
                setTimeout(() => {
                    window.location.href = '/cart';
                }, 2000);
            } else {
                this.showAlert(data.message, 'error');
                
                // Réactiver le bouton
                reserveButtons.prop('disabled', false);
                reserveButtons.html(originalHtml);
            }
            
        } catch (error) {
            console.error('Erreur réservation:', error);
            this.showAlert('Erreur lors de la réservation. Veuillez réessayer.', 'error');
            
            // Réactiver le bouton
            reserveButtons.prop('disabled', false);
            reserveButtons.html(originalHtml);
        }
    }
    
    // ===== UTILITAIRES =====
    getEventId() {
        return $('input[name="event_id"]').val() || window.eventId || null;
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
        
        const alertHtml = `
            <div class="alert ${alertClass} alert-dismissible fade show position-fixed cart-alert" 
                 style="top: 20px; right: 20px; z-index: 9999; min-width: 320px; max-width: 400px;">
                <i class="${icon} me-2"></i>
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `;
        
        $('body').append(alertHtml);
        
        // Auto-remove après 5 secondes
        setTimeout(() => {
            $('.cart-alert').fadeOut(500, function() {
                $(this).remove();
            });
        }, 5000);
    }
    
    setupEventListeners() {
        // Écouter les clics sur les boutons de réservation
        $(document).on('click', '#reserveBtn, #reserveBtnDesktop', () => {
            this.reserveTickets();
        });
        
        // Prolonger le timer si nécessaire (optionnel)
        $(document).on('click', '.extend-timer', () => {
            this.extendTimer();
        });
        
        // Nettoyer les ressources avant déchargement de la page
        $(window).on('beforeunload', () => {
            if (this.timerInterval) {
                clearInterval(this.timerInterval);
            }
        });
    }
    
    async extendTimer() {
        try {
            const response = await fetch('/cart/extend-timer', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                    'Content-Type': 'application/json'
                }
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.timeRemaining = 15 * 60; // Reset à 15 minutes
                this.showAlert(data.message, 'success');
            } else {
                this.showAlert(data.message, 'error');
            }
        } catch (error) {
            console.error('Erreur prolongation timer:', error);
            this.showAlert('Erreur lors de la prolongation', 'error');
        }
    }
    
    // ===== API PUBLIQUE =====
    reset() {
        this.selectedTickets = {};
        $('.qty-display').text('0');
        $('.ticket-type').removeClass('has-selection');
        this.updateSummary();
        this.updateButtons();
    }
    
    getSelectedTickets() {
        return { ...this.selectedTickets };
    }
    
    getTotalTickets() {
        return Object.values(this.selectedTickets).reduce((sum, qty) => sum + qty, 0);
    }
    
    getTotalPrice() {
        let total = 0;
        Object.keys(this.selectedTickets).forEach(ticketTypeId => {
            const quantity = this.selectedTickets[ticketTypeId];
            const display = $(`#qty_${ticketTypeId}, #qty_${ticketTypeId}_desktop`).first();
            const price = parseInt(display.data('price')) || 0;
            total += quantity * price;
        });
        return total;
    }
}

// ===== INITIALISATION GLOBALE =====
let unifiedCartSystem;

$(document).ready(function() {
    console.log('🚀 Initialisation du système de panier unifié');
    unifiedCartSystem = new UnifiedCartSystem();
    
    // Exposer l'instance globalement pour les fonctions inline
    window.cartSystem = unifiedCartSystem;
    window.reserveTickets = () => unifiedCartSystem.reserveTickets();
});

// ===== FONCTIONS DE COMPATIBILITÉ =====
// Pour les appels directs dans les templates
function increaseQuantity(ticketTypeId) {
    if (window.cartSystem) {
        window.cartSystem.increaseQuantity(ticketTypeId);
    }
}

function decreaseQuantity(ticketTypeId) {
    if (window.cartSystem) {
        window.cartSystem.decreaseQuantity(ticketTypeId);
    }
}

function reserveTickets() {
    if (window.cartSystem) {
        window.cartSystem.reserveTickets();
    }
}

// Export pour utilisation en module (si nécessaire)
if (typeof module !== 'undefined' && module.exports) {
    module.exports = UnifiedCartSystem;
}