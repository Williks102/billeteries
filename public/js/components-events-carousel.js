// ===== CARROUSEL CLICBILLET CI - VERSION BLADE INTÉGRÉE =====
document.addEventListener('DOMContentLoaded', function() {
    const carousel = document.getElementById('eventsCarousel');
    if (!carousel) return;
    
    console.log('🎠 Initialisation carrousel ClicBillet CI...');
    
    const prevBtn = document.getElementById('prevBtn');
    const nextBtn = document.getElementById('nextBtn');
    const indicators = document.querySelectorAll('.indicator-dot');
    const categoryButtons = document.querySelectorAll('[data-category]');
    const eventItems = document.querySelectorAll('.event-item');
    const autoplayEnabled = carousel.dataset.autoplay === 'true';
    
    let currentIndex = 0;
    let slidesToShow = getSlidesToShow();
    let totalSlides = carousel.children.length;
    let maxIndex = Math.max(0, totalSlides - slidesToShow);
    let isTransitioning = false;
    let autoplayInterval;

    // Configuration responsive
    function getSlidesToShow() {
        const width = window.innerWidth;
        if (width >= 1200) return 4;      // Desktop XL
        if (width >= 992) return 3;       // Desktop large
        if (width >= 768) return 2;       // Tablette
        return 1;                         // Mobile
    }

    // Mise à jour de l'affichage
    function updateCarousel(animate = true) {
        if (isTransitioning || totalSlides === 0) return;
        
        const slideWidth = carousel.children[0]?.offsetWidth || 300;
        const gap = parseInt(getComputedStyle(carousel).gap) || 20;
        const offset = currentIndex * (slideWidth + gap);
        
        if (animate) {
            isTransitioning = true;
            carousel.style.transition = 'transform 0.6s cubic-bezier(0.25, 0.46, 0.45, 0.94)';
            setTimeout(() => isTransitioning = false, 600);
        } else {
            carousel.style.transition = 'none';
        }
        
        carousel.style.transform = `translateX(-${offset}px)`;
        updateUI();
    }

    // Mise à jour de l'interface
    function updateUI() {
        // Boutons navigation
        if (prevBtn) prevBtn.disabled = currentIndex === 0;
        if (nextBtn) nextBtn.disabled = currentIndex >= maxIndex;
        
        // Indicateurs
        updateIndicators();
    }

    // Navigation
    function nextSlide() {
        if (currentIndex < maxIndex) {
            currentIndex++;
            updateCarousel();
        }
    }

    function prevSlide() {
        if (currentIndex > 0) {
            currentIndex--;
            updateCarousel();
        }
    }

    function goToSlide(index) {
        if (index >= 0 && index <= maxIndex) {
            currentIndex = index;
            updateCarousel();
        }
    }

    // Event listeners
    if (nextBtn) nextBtn.addEventListener('click', nextSlide);
    if (prevBtn) prevBtn.addEventListener('click', prevSlide);

    // Gestion tactile
    let startX = 0;
    let currentX = 0;
    let isDragging = false;

    carousel.addEventListener('touchstart', function(e) {
        startX = e.touches[0].clientX;
        isDragging = true;
        carousel.style.transition = 'none';
    }, { passive: true });

    carousel.addEventListener('touchmove', function(e) {
        if (!isDragging) return;
        currentX = e.touches[0].clientX;
        const deltaX = currentX - startX;
        const slideWidth = carousel.children[0]?.offsetWidth || 300;
        const gap = 20;
        const currentOffset = currentIndex * (slideWidth + gap);
        carousel.style.transform = `translateX(-${currentOffset - deltaX}px)`;
    }, { passive: true });

    carousel.addEventListener('touchend', function() {
        if (!isDragging) return;
        isDragging = false;
        
        const deltaX = startX - currentX;
        const threshold = 50;
        
        if (Math.abs(deltaX) > threshold) {
            if (deltaX > 0) nextSlide();
            else prevSlide();
        } else {
            updateCarousel();
        }
    });

    // Filtres de catégorie
    categoryButtons.forEach(button => {
        button.addEventListener('click', function() {
            const category = this.dataset.category;
            
            categoryButtons.forEach(btn => btn.classList.remove('active'));
            this.classList.add('active');
            
            eventItems.forEach(item => {
                const itemCategory = item.dataset.category;
                const slide = item.closest('.whippet-slide');
                if (slide) {
                    slide.style.display = (category === 'all' || itemCategory === category) ? 'block' : 'none';
                }
            });
            
            setTimeout(() => {
                totalSlides = Array.from(carousel.children).filter(slide => 
                    slide.style.display !== 'none'
                ).length;
                maxIndex = Math.max(0, totalSlides - slidesToShow);
                currentIndex = 0;
                updateCarousel(false);
                updateIndicators();
            }, 50);
        });
    });

    // Mise à jour des indicateurs
    function updateIndicators() {
        const indicatorsContainer = document.getElementById('carouselIndicators');
        if (!indicatorsContainer) return;
        
        indicatorsContainer.innerHTML = '';
        const numIndicators = Math.ceil(totalSlides / slidesToShow);
        
        for (let i = 0; i < numIndicators; i++) {
            const indicator = document.createElement('div');
            indicator.className = 'indicator-dot';
            indicator.dataset.slide = i;
            if (i === Math.floor(currentIndex / slidesToShow)) {
                indicator.classList.add('active');
            }
            
            indicator.addEventListener('click', () => goToSlide(i * slidesToShow));
            indicatorsContainer.appendChild(indicator);
        }
    }

    // Auto-play
    function startAutoplay() {
        if (!autoplayEnabled) return;
        autoplayInterval = setInterval(() => {
            if (currentIndex >= maxIndex) {
                currentIndex = 0;
            } else {
                currentIndex++;
            }
            updateCarousel();
        }, 5000);
    }

    function stopAutoplay() {
        clearInterval(autoplayInterval);
    }

    // Responsive
    window.addEventListener('resize', debounce(() => {
        const newSlidesToShow = getSlidesToShow();
        if (newSlidesToShow !== slidesToShow) {
            slidesToShow = newSlidesToShow;
            maxIndex = Math.max(0, totalSlides - slidesToShow);
            currentIndex = Math.min(currentIndex, maxIndex);
            updateCarousel(false);
            updateIndicators();
        }
    }, 250));

    // Accessibilité
    carousel.setAttribute('tabindex', '0');
    carousel.setAttribute('role', 'region');
    carousel.setAttribute('aria-label', 'Carrousel d\'événements');

    carousel.addEventListener('keydown', function(e) {
        switch(e.key) {
            case 'ArrowLeft':
                e.preventDefault();
                prevSlide();
                break;
            case 'ArrowRight':
                e.preventDefault();
                nextSlide();
                break;
        }
    });

    // Pause auto-play au survol
    if (autoplayEnabled && window.innerWidth > 768) {
        carousel.addEventListener('mouseenter', stopAutoplay);
        carousel.addEventListener('mouseleave', startAutoplay);
    }

    // Utilitaires
    function debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }

    // Initialisation
    updateIndicators();
    updateCarousel(false);
    if (autoplayEnabled) startAutoplay();
    
    console.log(`✅ Carrousel initialisé: ${totalSlides} événements, ${slidesToShow} visibles`);

    // API publique
    window.ClicBilletCarousel = {
        next: nextSlide,
        prev: prevSlide,
        goTo: goToSlide,
        refresh: () => {
            totalSlides = carousel.children.length;
            maxIndex = Math.max(0, totalSlides - slidesToShow);
            currentIndex = 0;
            updateIndicators();
            updateCarousel(false);
        }
    };
});
