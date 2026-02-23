// Sélection multiple
document.getElementById('select-all').addEventListener('change', function() {
    const checkboxes = document.querySelectorAll('.order-checkbox');
    checkboxes.forEach(checkbox => {
        checkbox.checked = this.checked;
    });
    updateSelectedCount();
});

document.querySelectorAll('.order-checkbox').forEach(checkbox => {
    checkbox.addEventListener('change', updateSelectedCount);
});

function updateSelectedCount() {
    const selected = document.querySelectorAll('.order-checkbox:checked').length;
    const countElement = document.getElementById('selected-count');
    if (countElement) {
        countElement.textContent = `${selected} commande(s) sélectionnée(s)`;
    }
}

// Mise à jour du statut
function updateStatus(orderId, status) {
    if (!confirm(`Êtes-vous sûr de vouloir changer le statut de cette commande ?`)) {
        return;
    }

    fetch(`/admin/orders/${orderId}/status`, {
        method: 'PATCH',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ payment_status: status })
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
        alert('Erreur lors de la mise à jour');
    });
}

// Renvoyer email
function resendEmail(orderId) {
    if (!confirm('Renvoyer l\'email de confirmation à ce client ?')) {
        return;
    }

    fetch(`/admin/orders/${orderId}/resend-email`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Email renvoyé avec succès !');
        } else {
            alert('Erreur : ' + (data.message || 'Impossible d\'envoyer l\'email'));
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        alert('Erreur lors de l\'envoi de l\'email');
    });
}

// Remboursement
function refundOrder(orderId) {
    const reason = prompt('Raison du remboursement (optionnel):');
    if (reason === null) return; // Annulé
    
    if (!confirm('Êtes-vous sûr de vouloir rembourser cette commande ? Cette action est irréversible.')) {
        return;
    }

    fetch(`/admin/orders/${orderId}/refund`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ reason: reason })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Erreur : ' + (data.message || 'Impossible de rembourser'));
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        alert('Erreur lors du remboursement');
    });
}
