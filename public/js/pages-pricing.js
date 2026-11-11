document.addEventListener('DOMContentLoaded', function() {
    const priceInput = document.getElementById('ticketPrice');
    const participantPriceEl = document.getElementById('participantPrice');
    const organizerEarningsEl = document.getElementById('organizerEarnings');
    
    // Configuration ClicBillet CI
    // Taux injecté par la vue (data-commission-rate <- config('payments.default_commission_rate'))
    const COMMISSION_RATE = parseFloat(priceInput.dataset.commissionRate);
    
    function updateCalculations() {
        const ticketPrice = parseInt(priceInput.value) || 0;
        
        if (ticketPrice <= 0) {
            participantPriceEl.textContent = '0 F';
            organizerEarningsEl.textContent = '0 F';
            return;
        }
        
        // Ce que paient les participants : le prix du ticket, sans frais supplémentaire
        const participantPrice = ticketPrice;
        
        // Commission ClicBillet (sur le prix du ticket seulement)
        const commission = Math.round((ticketPrice * COMMISSION_RATE) / 100);
        
        // Ce que reçoit l'organisateur
        const organizerEarnings = ticketPrice - commission;
        
        // Mise à jour de l'affichage
        participantPriceEl.textContent = formatCurrency(participantPrice);
        organizerEarningsEl.textContent = formatCurrency(organizerEarnings);
    }
    
    function formatCurrency(amount) {
        return new Intl.NumberFormat('fr-FR').format(amount) + ' F';
    }
    
    // Écouteur d'événements
    priceInput.addEventListener('input', updateCalculations);
    
    // Calcul initial
    updateCalculations();
    
    // Suggestions de prix au focus
    priceInput.addEventListener('focus', function() {
        if (!this.value) {
            // Suggestions de prix populaires
            const suggestions = [2500, 5000, 7500, 10000, 15000];
            const randomPrice = suggestions[Math.floor(Math.random() * suggestions.length)];
            this.placeholder = `Ex: ${randomPrice} FCFA`;
        }
    });
});
