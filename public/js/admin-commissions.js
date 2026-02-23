document.addEventListener('DOMContentLoaded', function() {
    const selectAll = document.getElementById('select-all');
    const commissionCheckboxes = document.querySelectorAll('.commission-checkbox');

    // Sélectionner/désélectionner tout
    selectAll.addEventListener('change', function() {
        commissionCheckboxes.forEach(checkbox => {
            if (checkbox.dataset.status === 'pending') {
                checkbox.checked = this.checked;
            }
        });
        updateBulkPayButton();
    });

    // Gestion des sélections individuelles
    commissionCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', updateBulkPayButton);
    });

    function updateBulkPayButton() {
        const checkedCommissions = document.querySelectorAll('.commission-checkbox:checked');
        const bulkPayBtn = document.querySelector('[data-bs-target="#bulkPayModal"]');
        
        if (checkedCommissions.length > 0) {
            bulkPayBtn.textContent = `Payer ${checkedCommissions.length} commission(s)`;
            bulkPayBtn.classList.remove('btn-outline-success');
            bulkPayBtn.classList.add('btn-success');
        } else {
            bulkPayBtn.innerHTML = '<i class="fas fa-credit-card me-1"></i> Paiement en lot';
            bulkPayBtn.classList.remove('btn-success');
            bulkPayBtn.classList.add('btn-outline-success');
        }
    }

    // Modal paiement en lot
    document.getElementById('bulkPayModal').addEventListener('show.bs.modal', function() {
        const checkedCommissions = document.querySelectorAll('.commission-checkbox:checked');
        const commissionList = document.getElementById('commission-list');
        const totalAmountSpan = document.getElementById('total-amount');
        const confirmBtn = document.getElementById('confirm-payment');
        const form = this.querySelector('form');
        
        // Vider la liste
        commissionList.innerHTML = '';
        
        // Supprimer les anciens inputs cachés
        form.querySelectorAll('input[name="commission_ids[]"]').forEach(input => input.remove());
        
        let totalAmount = 0;
        
        checkedCommissions.forEach(checkbox => {
            const row = checkbox.closest('tr');
            const promoterName = row.querySelector('td:nth-child(3) .fw-semibold').textContent;
            const amount = parseInt(row.querySelector('td:nth-child(6) .h5').textContent.replace(/[^\d]/g, ''));
            
            // Ajouter à la liste
            const li = document.createElement('li');
            li.innerHTML = `<i class="fas fa-user me-2"></i>${promoterName}: <strong>${amount.toLocaleString()} F</strong>`;
            commissionList.appendChild(li);
            
            // Ajouter input caché
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'commission_ids[]';
            input.value = checkbox.value;
            form.appendChild(input);
            
            totalAmount += amount;
        });
        
        totalAmountSpan.textContent = totalAmount.toLocaleString() + ' F';
        confirmBtn.disabled = checkedCommissions.length === 0;
    });
});

// Modal notes
function showNotesModal(commissionId, currentNotes) {
    document.getElementById('admin_notes').value = currentNotes;
    document.getElementById('notesForm').action = `/admin/finances/commissions/${commissionId}/status`;
    
    const modal = new bootstrap.Modal(document.getElementById('notesModal'));
    modal.show();
}

// Animation des statistiques
function animateCounters() {
    const counters = document.querySelectorAll('.h4');
    counters.forEach(counter => {
        const target = parseInt(counter.textContent.replace(/[^\d]/g, ''));
        const duration = 1500;
        const increment = target / (duration / 16);
        let current = 0;
        
        const timer = setInterval(() => {
            current += increment;
            if (current >= target) {
                current = target;
                clearInterval(timer);
            }
            counter.textContent = Math.floor(current).toLocaleString() + ' F';
        }, 16);
    });
}

document.addEventListener('DOMContentLoaded', animateCounters);
