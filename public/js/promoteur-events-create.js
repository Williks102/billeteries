    document.addEventListener('DOMContentLoaded', function() {
        // Validation des heures
        const startTimeInput = document.querySelector('input[name="event_time"]');
        const endTimeInput = document.querySelector('input[name="end_time"]');
        
        endTimeInput.addEventListener('change', function(e) {
            const startTime = startTimeInput.value;
            const endTime = e.target.value;
            
            if (startTime && endTime && endTime <= startTime) {
                alert('⚠️ L\'heure de fin doit être après l\'heure de début');
                e.target.value = '';
                e.target.focus();
            }
        });

        // Validation de la date
        const dateInput = document.querySelector('input[name="event_date"]');
        dateInput.addEventListener('change', function(e) {
            const selectedDate = new Date(e.target.value);
            const today = new Date();
            today.setHours(0, 0, 0, 0);
            
            if (selectedDate <= today) {
                alert('⚠️ La date de l\'événement doit être dans le futur');
                e.target.value = '';
                e.target.focus();
            }
        });

        // Animation du formulaire
        const cards = document.querySelectorAll('.card-custom');
        cards.forEach((card, index) => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(20px)';
            
            setTimeout(() => {
                card.style.transition = 'all 0.5s ease';
                card.style.opacity = '1';
                card.style.transform = 'translateY(0)';
            }, index * 100);
        });

        // Amélioration de l'UX du formulaire
        const inputs = document.querySelectorAll('input, select, textarea');
        inputs.forEach(input => {
            input.addEventListener('focus', function() {
                this.closest('.card-custom').style.borderLeftColor = '#FF6B35';
                this.closest('.card-custom').style.borderLeftWidth = '6px';
            });
            
            input.addEventListener('blur', function() {
                this.closest('.card-custom').style.borderLeftColor = '#FF6B35';
                this.closest('.card-custom').style.borderLeftWidth = '4px';
            });
        });

        // Compteur de caractères pour la description
        const descriptionTextarea = document.querySelector('textarea[name="description"]');
        if (descriptionTextarea) {
            const counter = document.createElement('small');
            counter.className = 'text-muted float-end';
            descriptionTextarea.parentNode.appendChild(counter);
            
            descriptionTextarea.addEventListener('input', function() {
                const length = this.value.length;
                counter.textContent = `${length} caractères`;
                
                if (length < 50) {
                    counter.className = 'text-warning float-end';
                    counter.textContent = `${length} caractères (minimum recommandé: 50)`;
                } else {
                    counter.className = 'text-muted float-end';
                }
            });
        }
    });
