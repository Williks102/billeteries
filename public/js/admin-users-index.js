// Gestion de la sélection multiple
document.addEventListener('DOMContentLoaded', function() {
    const selectAll = document.getElementById('select-all');
    const checkboxes = document.querySelectorAll('.user-checkbox');
    const bulkActions = document.getElementById('bulk-actions');
    const selectedCount = document.getElementById('selected-count');

    // Sélectionner tout
    selectAll.addEventListener('change', function() {
        checkboxes.forEach(checkbox => {
            checkbox.checked = this.checked;
        });
        updateBulkActions();
    });

    // Sélection individuelle
    checkboxes.forEach(checkbox => {
        checkbox.addEventListener('change', updateBulkActions);
    });

    function updateBulkActions() {
        const selected = document.querySelectorAll('.user-checkbox:checked');
        const count = selected.length;
        
        if (count > 0) {
            bulkActions.style.display = 'block';
            selectedCount.textContent = `${count} utilisateur(s) sélectionné(s)`;
            selectAll.indeterminate = count > 0 && count < checkboxes.length;
            selectAll.checked = count === checkboxes.length;
        } else {
            bulkActions.style.display = 'none';
            selectAll.indeterminate = false;
            selectAll.checked = false;
        }
    }
});

// Suppression d'un utilisateur
function deleteUser(userId, userName) {
    if (!confirm(`Êtes-vous sûr de vouloir supprimer l'utilisateur "${userName}" ?\n\nCette action est irréversible.`)) {
        return;
    }

    fetch(`/admin/users/${userId}`, {
        method: 'DELETE',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json',
            'Content-Type': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Supprimer la ligne du tableau
            document.querySelector(`input[value="${userId}"]`).closest('tr').remove();
            
            // Afficher message de succès
            showAlert('success', data.message);
            
            // Mettre à jour les compteurs
            updateStats();
        } else {
            showAlert('error', data.message);
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        showAlert('error', 'Une erreur est survenue lors de la suppression');
    });
}

// Basculer la vérification email
function toggleEmailVerification(userId) {
    fetch(`/admin/users/${userId}/toggle-email`, {
        method: 'PATCH',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json',
            'Content-Type': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Recharger la page pour mettre à jour l'affichage
            location.reload();
        } else {
            showAlert('error', data.message);
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        showAlert('error', 'Une erreur est survenue');
    });
}

// Actions en lot
function bulkAction(action) {
    const selected = Array.from(document.querySelectorAll('.user-checkbox:checked')).map(cb => cb.value);
    
    if (selected.length === 0) {
        showAlert('warning', 'Veuillez sélectionner au moins un utilisateur');
        return;
    }

    let confirmMessage = '';
    switch (action) {
        case 'verify':
            confirmMessage = `Marquer ${selected.length} utilisateur(s) comme vérifié(s) ?`;
            break;
        case 'unverify':
            confirmMessage = `Marquer ${selected.length} utilisateur(s) comme non vérifié(s) ?`;
            break;
        case 'delete':
            confirmMessage = `Supprimer définitivement ${selected.length} utilisateur(s) ?\n\nCette action est irréversible.`;
            break;
    }

    if (!confirm(confirmMessage)) {
        return;
    }

    fetch('/admin/users/bulk-action', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json',
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            action: action,
            users: selected
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('success', data.message);
            // Recharger la page pour voir les changements
            setTimeout(() => location.reload(), 1500);
        } else {
            showAlert('error', data.message);
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        showAlert('error', 'Une erreur est survenue lors de l\'action en lot');
    });
}

// Afficher les alertes
function showAlert(type, message) {
    const alertClass = type === 'success' ? 'alert-success' : 
                      type === 'warning' ? 'alert-warning' : 'alert-danger';
    
    const alertHtml = `
        <div class="alert ${alertClass} alert-dismissible fade show" role="alert">
            <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'warning' ? 'exclamation-triangle' : 'exclamation-circle'} me-2"></i>
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    `;
    
    // Insérer l'alerte en haut du contenu
    const content = document.querySelector('.container-fluid.admin-content');
    content.insertAdjacentHTML('afterbegin', alertHtml);
    
    // Auto-supprimer après 5 secondes
    setTimeout(() => {
        const alert = content.querySelector('.alert');
        if (alert) {
            alert.remove();
        }
    }, 5000);
}

// Mettre à jour les statistiques (optionnel)
function updateStats() {
    // Ici vous pourriez faire un appel AJAX pour récupérer les nouvelles stats
    // ou simplement décrémenter les compteurs existants
}
