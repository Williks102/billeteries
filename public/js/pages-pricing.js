document.addEventListener('DOMContentLoaded', function() {
    const priceInput = document.getElementById('ticketPrice');
    const participantPriceEl = document.getElementById('participantPrice');
    const organizerEarningsEl = document.getElementById('organizerEarnings');
    
    // Configuration ClicBillet CI
    const COMMISSION_RATE = 8; // 8%
    const TECHNICAL_FEE = 500; // 500 FCFA par billet
    
    function updateCalculations() {
        const ticketPrice = parseInt(priceInput.value) || 0;
        
        if (ticketPrice <= 0) {
            participantPriceEl.textContent = '0 F';
            organizerEarningsEl.textContent = '0 F';
            return;
        }
        
        // Ce que paient les participants (prix + frais techniques)
        const participantPrice = ticketPrice + TECHNICAL_FEE;
        
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
