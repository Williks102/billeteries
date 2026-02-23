document.addEventListener('DOMContentLoaded', function() {
    const priceInput = document.getElementById('ticket-price');
    const quantityInput = document.getElementById('ticket-quantity');
    
    // Configuration de base (à adapter selon vos paramètres)
    const COMMISSION_RATE = 10; // 10%
    const PLATFORM_FEE_PER_TICKET = 500; // 500 FCFA par billet
    
    function updateCalculations() {
        const price = parseInt(priceInput.value) || 0;
        const quantity = parseInt(quantityInput.value) || 0;
        
        // Calculs
        const grossAmount = price * quantity;
        const platformFees = PLATFORM_FEE_PER_TICKET * quantity;
        const commissionAmount = Math.round((grossAmount * COMMISSION_RATE) / 100);
        const totalDeductions = platformFees + commissionAmount;
        const netEarnings = grossAmount - totalDeductions;
        
        // Pourcentages
        const promoterPercentage = grossAmount > 0 ? Math.round((netEarnings / grossAmount) * 100) : 0;
        const platformPercentage = grossAmount > 0 ? Math.round((totalDeductions / grossAmount) * 100) : 0;
        
        // Mise à jour des éléments
        document.getElementById('total-revenue').textContent = formatCurrency(grossAmount);
        document.getElementById('net-earnings').textContent = formatCurrency(netEarnings);
        document.getElementById('gross-amount').textContent = formatCurrency(grossAmount);
        document.getElementById('commission-amount').textContent = '- ' + formatCurrency(commissionAmount);
        document.getElementById('platform-fees').textContent = '- ' + formatCurrency(platformFees);
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
        document.getElementById('platform-fees-display').textContent = formatCurrency(PLATFORM_FEE_PER_TICKET) + '/billet';
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
