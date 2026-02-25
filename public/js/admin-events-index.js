// Gestion des sélections en lot
document.addEventListener('DOMContentLoaded', function() {
    const selectAll = document.getElementById('select-all');
    const rowCheckboxes = document.querySelectorAll('.row-checkbox');
    const bulkActions = document.getElementById('bulk-actions');
    const selectedCount = document.getElementById('selected-count');
    const bulkForm = document.getElementById('bulk-form');

    // Sélectionner/désélectionner tout
    selectAll.addEventListener('change', function() {
        rowCheckboxes.forEach(checkbox => {
            checkbox.checked = this.checked;
        });
        updateBulkActions();
    });

    // Gestion des sélections individuelles
    rowCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            updateBulkActions();
            
            // Mettre à jour le statut "select all"
            const checkedCount = document.querySelectorAll('.row-checkbox:checked').length;
            selectAll.checked = checkedCount === rowCheckboxes.length;
            selectAll.indeterminate = checkedCount > 0 && checkedCount < rowCheckboxes.length;
        });
    });

    function updateBulkActions() {
        const checkedBoxes = document.querySelectorAll('.row-checkbox:checked');
        const count = checkedBoxes.length;

        if (count > 0) {
            bulkActions.style.display = 'block';
            selectedCount.textContent = `${count} événement(s) sélectionné(s)`;
            
            // Ajouter les IDs sélectionnés au formulaire
            const existingInputs = bulkForm.querySelectorAll('input[name="events[]"]');
            existingInputs.forEach(input => input.remove());
            
            checkedBoxes.forEach(checkbox => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'events[]';
                input.value = checkbox.value;
                bulkForm.appendChild(input);
            });
        } else {
            bulkActions.style.display = 'none';
        }
    }

    // Confirmation pour les actions en lot
    bulkForm.addEventListener('submit', function(e) {
        const action = this.querySelector('select[name="action"]').value;
        const count = document.querySelectorAll('.row-checkbox:checked').length;
        
        let message = '';
        switch(action) {
            case 'publish':
                message = `Êtes-vous sûr de vouloir publier ${count} événement(s) ?`;
                break;
            case 'reject':
                message = `Êtes-vous sûr de vouloir rejeter ${count} événement(s) ?`;
                break;
            case 'delete':
                message = `⚠️ ATTENTION: Êtes-vous sûr de vouloir SUPPRIMER définitivement ${count} événement(s) ?\n\nCette action est irréversible !`;
                break;
            default:
                message = `Confirmer l'action sur ${count} événement(s) ?`;
        }
        
        if (!confirm(message)) {
            e.preventDefault();
        }
    });

    window.clearSelection = function() {
        rowCheckboxes.forEach(checkbox => checkbox.checked = false);
        selectAll.checked = false;
        selectAll.indeterminate = false;
        bulkActions.style.display = 'none';
    };
});

// Actualisation automatique du statut
setInterval(function() {
    // Optionnel: actualiser les données sans recharger la page
    // fetch(window.location.href + '?ajax=1')...
}, 60000); // 1 minute

// Animation des cartes de statistiques
document.querySelectorAll('.card').forEach(card => {
    card.addEventListener('mouseenter', function() {
        this.style.transform = 'translateY(-2px)';
    });
    
    card.addEventListener('mouseleave', function() {
        this.style.transform = 'translateY(0)';
    });
});
