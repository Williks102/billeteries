// Gestion des sélections multiples
document.addEventListener('DOMContentLoaded', function() {
    const selectAll = document.getElementById('select-all');
    const checkboxes = document.querySelectorAll('.ticket-checkbox');
    const bulkActions = document.getElementById('bulk-actions');
    const selectedCount = document.getElementById('selected-count');

    // Sélectionner/désélectionner tout
    selectAll.addEventListener('change', function() {
        checkboxes.forEach(checkbox => {
            checkbox.checked = this.checked;
        });
        updateBulkActions();
    });

    // Gestion des checkboxes individuelles
    checkboxes.forEach(checkbox => {
        checkbox.addEventListener('change', updateBulkActions);
    });

    function updateBulkActions() {
        const selected = document.querySelectorAll('.ticket-checkbox:checked');
        selectedCount.textContent = selected.length;
        
        if (selected.length > 0) {
            bulkActions.style.display = 'block';
        } else {
            bulkActions.style.display = 'none';
        }

        // Mettre à jour le statut de "Sélectionner tout"
        selectAll.indeterminate = selected.length > 0 && selected.length < checkboxes.length;
        selectAll.checked = selected.length === checkboxes.length;
    }

    window.clearSelection = function() {
        checkboxes.forEach(checkbox => checkbox.checked = false);
        selectAll.checked = false;
        updateBulkActions();
    };
});

// Actions individuelles - ADAPTÉES À VOS ROUTES EXISTANTES
function markUsed(ticketId) {
    if (confirm('Marquer ce ticket comme utilisé ?')) {
        fetch(`/admin/tickets/${ticketId}/mark-used`, {
            method: 'PATCH',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Content-Type': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Erreur : ' + (data.message || 'Une erreur est survenue'));
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            alert('Une erreur est survenue');
        });
    }
}

function cancelTicket(ticketId) {
    if (confirm('Êtes-vous sûr de vouloir annuler ce ticket ?')) {
        fetch(`/admin/tickets/${ticketId}/cancel`, {
            method: 'PATCH',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Content-Type': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Erreur : ' + (data.message || 'Une erreur est survenue'));
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            alert('Une erreur est survenue');
        });
    }
}

// Actions en lot - UTILISANT VOTRE ROUTE EXISTANTE
function bulkMarkAsUsed() {
    const selected = Array.from(document.querySelectorAll('.ticket-checkbox:checked')).map(cb => cb.value);
    
    if (selected.length === 0) {
        alert('Aucun ticket sélectionné');
        return;
    }

    if (confirm(`Marquer ${selected.length} ticket(s) comme utilisé(s) ?`)) {
        fetch('/admin/tickets/bulk-action', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ 
                action: 'mark-used',
                ticket_ids: selected 
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Erreur : ' + (data.message || 'Une erreur est survenue'));
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            alert('Une erreur est survenue');
        });
    }
}

function bulkCancel() {
    const selected = Array.from(document.querySelectorAll('.ticket-checkbox:checked')).map(cb => cb.value);
    
    if (selected.length === 0) {
        alert('Aucun ticket sélectionné');
        return;
    }

    if (confirm(`Annuler ${selected.length} ticket(s) ? Cette action est irréversible.`)) {
        fetch('/admin/tickets/bulk-action', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ 
                action: 'cancel',
                ticket_ids: selected 
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Erreur : ' + (data.message || 'Une erreur est survenue'));
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            alert('Une erreur est survenue');
        });
    }
}

function bulkExport() {
    const selected = Array.from(document.querySelectorAll('.ticket-checkbox:checked')).map(cb => cb.value);
    
    if (selected.length === 0) {
        alert('Aucun ticket sélectionné');
        return;
    }

    // Utiliser votre route d'export existante avec des paramètres
    const form = document.createElement('form');
    form.method = 'GET';
    form.action = '/admin/tickets-export';
    form.style.display = 'none';

    // Ajouter les IDs des tickets comme paramètres
    selected.forEach(id => {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'ticket_ids[]';
        input.value = id;
        form.appendChild(input);
    });

    document.body.appendChild(form);
    form.submit();
    document.body.removeChild(form);
}

// Fonction utilitaire pour actualiser la page
function refreshPage() {
    window.location.reload();
}

// Auto-actualisation des statuts (optionnel)
let autoRefreshInterval;

function startAutoRefresh() {
    autoRefreshInterval = setInterval(function() {
        // Vérifier s'il y a des tickets en statut "pending"
        const pendingTickets = document.querySelectorAll('.status-pending').length;
        if (pendingTickets > 0) {
            fetch(window.location.href, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.text())
            .then(html => {
                // Actualiser seulement si nécessaire
                const parser = new DOMParser();
                const newDoc = parser.parseFromString(html, 'text/html');
                const newContent = newDoc.querySelector('.table tbody');
                const currentContent = document.querySelector('.table tbody');
                
                if (newContent && currentContent && 
                    newContent.innerHTML !== currentContent.innerHTML) {
                    currentContent.innerHTML = newContent.innerHTML;
                    
                    // Réattacher les event listeners
                    document.querySelectorAll('.ticket-checkbox').forEach(checkbox => {
                        checkbox.addEventListener('change', updateBulkActions);
                    });
                }
            })
            .catch(error => console.log('Auto-refresh error:', error));
        }
    }, 30000); // 30 secondes
}

function stopAutoRefresh() {
    if (autoRefreshInterval) {
        clearInterval(autoRefreshInterval);
    }
}

// Démarrer l'auto-actualisation au chargement de la page
document.addEventListener('DOMContentLoaded', function() {
    startAutoRefresh();
});

// Arrêter l'auto-actualisation quand la page n'est plus visible
document.addEventListener('visibilitychange', function() {
    if (document.hidden) {
        stopAutoRefresh();
    } else {
        startAutoRefresh();
    }
});

// Filtrage en temps réel (optionnel)
function setupLiveFiltering() {
    const searchInput = document.getElementById('search');
    const statusSelect = document.getElementById('status');
    const eventSelect = document.getElementById('event');
    
    let debounceTimer;
    
    function debounceSearch() {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(function() {
            document.querySelector('form.filter-form').submit();
        }, 500);
    }
    
    if (searchInput) {
        searchInput.addEventListener('input', debounceSearch);
    }
    
    if (statusSelect) {
        statusSelect.addEventListener('change', function() {
            document.querySelector('form.filter-form').submit();
        });
    }
    
    if (eventSelect) {
        eventSelect.addEventListener('change', function() {
            document.querySelector('form.filter-form').submit();
        });
    }
}

// Activer le filtrage en temps réel
// setupLiveFiltering();

// Raccourcis clavier
document.addEventListener('keydown', function(e) {
    // Ctrl+A pour sélectionner tout
    if (e.ctrlKey && e.key === 'a' && e.target.tagName !== 'INPUT') {
        e.preventDefault();
        document.getElementById('select-all').click();
    }
    
    // Échap pour vider la sélection
    if (e.key === 'Escape') {
        clearSelection();
    }
    
    // Ctrl+R pour actualiser
    if (e.ctrlKey && e.key === 'r') {
        e.preventDefault();
        refreshPage();
    }
});

// Tooltip pour les actions
document.addEventListener('DOMContentLoaded', function() {
    // Initialiser les tooltips Bootstrap si disponible
    if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    }
});
