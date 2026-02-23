        // ========================= SIDEBAR FUNCTIONALITY =========================
        
        // Variables globales
        let sidebarState = 'full'; // full, mini, mobile-hidden
        
        // Toggle sidebar desktop (mini/full)
        function toggleSidebar() {
            const sidebar = document.getElementById('promoteurSidebar');
            
            if (window.innerWidth > 1024) {
                if (sidebarState === 'full') {
                    sidebar.classList.add('mini');
                    sidebarState = 'mini';
                    localStorage.setItem('sidebarState', 'mini');
                } else {
                    sidebar.classList.remove('mini');
                    sidebarState = 'full';
                    localStorage.setItem('sidebarState', 'full');
                }
            }
        }
        
        // Toggle sidebar mobile (show/hide)
        function toggleMobileSidebar() {
            const sidebar = document.getElementById('promoteurSidebar');
            const overlay = document.getElementById('mobileOverlay');
            
            if (window.innerWidth <= 768) {
                if (sidebar.classList.contains('mobile-visible')) {
                    closeMobileSidebar();
                } else {
                    openMobileSidebar();
                }
            }
        }
        
        // Ouvrir sidebar mobile
        function openMobileSidebar() {
            const sidebar = document.getElementById('promoteurSidebar');
            const overlay = document.getElementById('mobileOverlay');
            
            sidebar.classList.add('mobile-visible');
            overlay.classList.add('active');
            document.body.style.overflow = 'hidden';
        }
        
        // Fermer sidebar mobile
        function closeMobileSidebar() {
            const sidebar = document.getElementById('promoteurSidebar');
            const overlay = document.getElementById('mobileOverlay');
            
            sidebar.classList.remove('mobile-visible');
            overlay.classList.remove('active');
            document.body.style.overflow = '';
        }
        
        // Toggle submenu
        function toggleSubmenu(menuId, element) {
            const submenu = document.getElementById('submenu-' + menuId);
            const parent = element.closest('.submenu-parent');
            
            if (window.innerWidth > 1024 && sidebarState === 'mini') {
                return; // Pas de submenu en mode mini
            }
            
            if (submenu) {
                const isOpen = submenu.classList.contains('open');
                
                // Fermer tous les autres submenus
                document.querySelectorAll('.sidebar-submenu.open').forEach(sub => {
                    if (sub !== submenu) {
                        sub.classList.remove('open');
                        sub.closest('.submenu-parent').classList.remove('open');
                    }
                });
                
                // Toggle le submenu actuel
                if (isOpen) {
                    submenu.classList.remove('open');
                    parent.classList.remove('open');
                } else {
                    submenu.classList.add('open');
                    parent.classList.add('open');
                }
            }
        }
        
        // Gestion responsive au redimensionnement
        function handleResize() {
            const sidebar = document.getElementById('promoteurSidebar');
            const overlay = document.getElementById('mobileOverlay');
            
            if (window.innerWidth <= 768) {
                // Mode mobile
                sidebar.classList.remove('mini');
                closeMobileSidebar();
                sidebarState = 'mobile-hidden';
            } else if (window.innerWidth <= 1024) {
                // Mode tablette
                closeMobileSidebar();
                sidebar.classList.add('mini');
                sidebarState = 'mini';
            } else {
                // Mode desktop
                closeMobileSidebar();
                const savedState = localStorage.getItem('sidebarState');
                if (savedState === 'mini') {
                    sidebar.classList.add('mini');
                    sidebarState = 'mini';
                } else {
                    sidebar.classList.remove('mini');
                    sidebarState = 'full';
                }
            }
        }
        
        // Fermer mobile sidebar si clic en dehors
        function handleClickOutside(event) {
            const sidebar = document.getElementById('promoteurSidebar');
            const mobileToggle = document.querySelector('.mobile-toggle');
            
            if (window.innerWidth <= 768 && 
                sidebar.classList.contains('mobile-visible') &&
                !sidebar.contains(event.target) &&
                !mobileToggle.contains(event.target)) {
                closeMobileSidebar();
            }
        }
        
        // Gestion ESC key pour fermer mobile sidebar
        function handleKeyPress(event) {
            if (event.key === 'Escape' && window.innerWidth <= 768) {
                const sidebar = document.getElementById('promoteurSidebar');
                if (sidebar.classList.contains('mobile-visible')) {
                    closeMobileSidebar();
                }
            }
        }
        
        // ========================= USER MENU =========================
        function toggleUserMenu() {
            // TODO: Implémenter dropdown utilisateur
            console.log('Toggle user menu');
        }
        
        function showNotifications() {
            // TODO: Implémenter panneau notifications
            console.log('Show notifications');
        }
        
        // ========================= INITIALIZATION =========================
        document.addEventListener('DOMContentLoaded', function() {
            // Restaurer l'état de la sidebar depuis localStorage
            const savedState = localStorage.getItem('sidebarState');
            if (savedState && window.innerWidth > 1024) {
                const sidebar = document.getElementById('promoteurSidebar');
                if (savedState === 'mini') {
                    sidebar.classList.add('mini');
                    sidebarState = 'mini';
                }
            }
            
            // Event listeners
            window.addEventListener('resize', handleResize);
            document.addEventListener('click', handleClickOutside);
            document.addEventListener('keydown', handleKeyPress);
            
            // Appel initial pour définir l'état correct
            handleResize();
            
            // Auto-hide alerts after 5 seconds
            setTimeout(function() {
                const alerts = document.querySelectorAll('.alert:not(.alert-permanent)');
                alerts.forEach(function(alert) {
                    const bsAlert = new bootstrap.Alert(alert);
                    bsAlert.close();
                });
            }, 5000);
        });
        
        // ========================= UTILITY FUNCTIONS =========================
        
        // Smooth scroll to top
        function scrollToTop() {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        }
        
        // Format numbers with spaces
        function formatNumber(num) {
            return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, " ");
        }
        
        // Show loading state
        function showLoading(element) {
            const originalText = element.innerHTML;
            element.dataset.originalText = originalText;
            element.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Chargement...';
            element.disabled = true;
        }
        
        // Hide loading state
        function hideLoading(element) {
            const originalText = element.dataset.originalText;
            if (originalText) {
                element.innerHTML = originalText;
                delete element.dataset.originalText;
            }
            element.disabled = false;
        }
        
        // Toast notifications
        function showToast(message, type = 'success', duration = 3000) {
            // Create toast element
            const toast = document.createElement('div');
            toast.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
            toast.style.cssText = 'top: 20px; right: 20px; z-index: 1060; min-width: 300px;';
            toast.innerHTML = `
                <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle'} me-2"></i>
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            
            document.body.appendChild(toast);
            
            // Auto remove after duration
            setTimeout(() => {
                if (toast.parentNode) {
                    const bsAlert = new bootstrap.Alert(toast);
                    bsAlert.close();
                }
            }, duration);
        }
        
        // Confirm dialog
        function confirmAction(message, callback) {
            if (confirm(message)) {
                callback();
            }
        }
        
        // AJAX helper
        function makeRequest(url, method = 'GET', data = null) {
            const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            
            const options = {
                method: method,
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': token,
                    'Accept': 'application/json'
                }
            };
            
            if (data) {
                options.body = JSON.stringify(data);
            }
            
            return fetch(url, options)
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.json();
                })
                .catch(error => {
                    console.error('Request failed:', error);
                    showToast('Une erreur est survenue', 'danger');
                    throw error;
                });
        }
        
    
