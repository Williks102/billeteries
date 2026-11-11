document.addEventListener('DOMContentLoaded', function() {
    const priceInput = document.getElementById('ticket-price');
    const quantityInput = document.getElementById('ticket-quantity');
    
    // Configuration de base (à adapter selon vos paramètres)
    const COMMISSION_RATE = 10; // 10%
    // Aucun frais fixe : la plateforme ne prélève que la commission sur le prix des billets
    
    function updateCalculations() {
        const price = parseInt(priceInput.value) || 0;
        const quantity = parseInt(quantityInput.value) || 0;
        
        // Calculs
        const grossAmount = price * quantity;
        const commissionAmount = Math.round((grossAmount * COMMISSION_RATE) / 100);
        const netEarnings = grossAmount - commissionAmount;
        
        // Pourcentages
        const promoterPercentage = grossAmount > 0 ? Math.round((netEarnings / grossAmount) * 100) : 0;
        const platformPercentage = grossAmount > 0 ? Math.round((commissionAmount / grossAmount) * 100) : 0;
        
        // Mise à jour des éléments
        document.getElementById('total-revenue').textContent = formatCurrency(grossAmount);
        document.getElementById('net-earnings').textContent = formatCurrency(netEarnings);
        document.getElementById('gross-amount').textContent = formatCurrency(grossAmount);
        document.getElementById('commission-amount').textContent = '- ' + formatCurrency(commissionAmount);
        document.getElementById('final-earnings').textContent = formatCurrency(netEarnings);
        
        // Barres de progression
        document.getElementById('promoter-bar').style.width = promoterPercentage + '%';
        document.getElementById('platform-bar').style.width = platformPercentage + '%';
        document.getElementById('promoter-percentage').textContent = promoterPercentage + '%';
        document.getElementById('platform-percentage').textContent = platformPercentage + '%';
        
        // Projections mensuelles
        document.getElementById('monthly-1-event').textContent = formatCurrency(netEarnings);
        document.getElementById('monthly-2-events').textContent = formatCurrency(netEarnings * 2);
        
        // Informations
        document.getElementById('commission-rate').textContent = COMMISSION_RATE + '%';
    }
    
    function formatCurrency(amount) {
        return new Intl.NumberFormat('fr-FR').format(amount) + ' FCFA';
    }
    
    // Event listeners
    priceInput.addEventListener('input', updateCalculations);
    quantityInput.addEventListener('input', updateCalculations);
    
    // Calcul initial
    updateCalculations();
});
