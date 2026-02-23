    // Graphiques simples ou animations peuvent être ajoutés ici
    document.addEventListener('DOMContentLoaded', function() {
        // Animation des chiffres
        const statCards = document.querySelectorAll('.stat-card h4');
        statCards.forEach(card => {
            const finalValue = parseInt(card.textContent.replace(/\D/g, ''));
            if (finalValue > 0) {
                animateValue(card, 0, finalValue, 1000);
            }
        });
    });
    
    function animateValue(element, start, end, duration) {
        let startTimestamp = null;
        const step = (timestamp) => {
            if (!startTimestamp) startTimestamp = timestamp;
            const progress = Math.min((timestamp - startTimestamp) / duration, 1);
            const current = Math.floor(progress * (end - start) + start);
            
            if (element.textContent.includes('F')) {
                element.textContent = current.toLocaleString() + ' F';
            } else {
                element.textContent = current;
            }
            
            if (progress < 1) {
                window.requestAnimationFrame(step);
            }
        };
        window.requestAnimationFrame(step);
    }
