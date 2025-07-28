{{-- resources/views/admin/orders.blade.php --}}
@extends('layouts.admin')

@section('title', 'Gestion des commandes - ClicBillet CI')

@section('breadcrumb')
    <li class="breadcrumb-item active">Commandes</li>
@endsection

@section('content')
    <!-- Header avec actions -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-1">Gestion des commandes</h2>
            <p class="text-muted mb-0">Suivez et gérez toutes les commandes de la plateforme</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.export.orders') }}" class="btn btn-outline-orange">
                <i class="fas fa-download me-2"></i>Exporter
            </a>
            <div class="dropdown">
                <button class="btn btn-orange dropdown-toggle" type="button" data-bs-toggle="dropdown">
                    <i class="fas fa-cog me-2"></i>Actions groupées
                </button>
                <ul class="dropdown-menu">
                    <li><a class="dropdown-item" href="#" onclick="bulkUpdate('completed')">
                        <i class="fas fa-check me-2"></i>Marquer comme terminées
                    </a></li>
                    <li><a class="dropdown-item" href="#" onclick="bulkUpdate('cancelled')">
                        <i class="fas fa-times me-2"></i>Annuler sélectionnées
                    </a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item" href="#" onclick="bulkExport()">
                        <i class="fas fa-file-export me-2"></i>Exporter sélectionnées
                    </a></li>
                    <li><a class="dropdown-item text-danger" href="#" onclick="bulkDelete()">
                        <i class="fas fa-trash me-2"></i>Supprimer sélectionnées
                    </a></li>
                </ul>
            </div>
        </div>
    </div>
@endsection

@push('styles')
<style>
    .stat-card {
        background: white;
        border-radius: 12px;
        padding: 20px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        border: 1px solid #f0f0f0;
        transition: transform 0.2s ease;
        height: 100%;
    }
    
    .stat-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    }
    
    .stat-icon {
        width: 50px;
        height: 50px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        color: white;
    }
    
    .stat-icon.success { background: linear-gradient(135deg, #28a745, #20c997); }
    .stat-icon.warning { background: linear-gradient(135deg, #ffc107, #fd7e14); }
    .stat-icon.danger { background: linear-gradient(135deg, #dc3545, #e83e8c); }
    .stat-icon.info { background: linear-gradient(135deg, #17a2b8, #6f42c1); }
    
    .stat-info h4 {
        font-size: 1.8rem;
        font-weight: 700;
        margin-bottom: 5px;
        color: #2d3748;
    }
    
    .stat-info p {
        margin-bottom: 5px;
        color: #718096;
        font-weight: 500;
    }
    
    .table th {
        font-weight: 600;
        color: #4a5568;
        border-bottom: 2px solid #e2e8f0;
        background-color: #f8fafc;
        position: sticky;
        top: 0;
        z-index: 10;
    }
    
    .table-hover tbody tr:hover {
        background-color: #f8fafc;
    }
    
    .badge {
        font-size: 0.75rem;
        font-weight: 500;
        padding: 6px 10px;
    }
    
    .order-status {
        min-width: 80px;
        display: inline-block;
        text-align: center;
    }
    
    .payment-status {
        min-width: 70px;
        display: inline-block;
        text-align: center;
    }
    
    .user-avatar {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        background: linear-gradient(135deg, #FF6B35, #fd7e14);
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-weight: bold;
        font-size: 0.85rem;
        flex-shrink: 0;
    }
    
    .dropdown-menu {
        border: none;
        box-shadow: 0 4px 20px rgba(0,0,0,0.15);
        border-radius: 8px;
        min-width: 180px;
    }
    
    .dropdown-item {
        padding: 8px 16px;
        transition: background-color 0.2s ease;
        font-size: 0.9rem;
    }
    
    .dropdown-item:hover {
        background-color: #f8fafc;
    }
    
    .dropdown-item i {
        width: 16px;
        text-align: center;
    }
    
    #bulk-actions {
        border: 1px solid #e2e8f0;
        min-width: 500px;
        max-width: 90vw;
    }
    
    .card {
        border: none;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        border-radius: 12px;
    }
    
    .card-header {
        background: #f8fafc;
        border-bottom: 1px solid #e2e8f0;
        border-radius: 12px 12px 0 0 !important;
    }
    
    .form-control, .form-select {
        border: 2px solid #e2e8f0;
        border-radius: 8px;
        transition: border-color 0.2s ease;
    }
    
    .form-control:focus, .form-select:focus {
        border-color: #FF6B35;
        box-shadow: 0 0 0 0.2rem rgba(255, 107, 53, 0.25);
    }
    
    .btn-orange {
        background: linear-gradient(135deg, #FF6B35, #fd7e14);
        border: none;
        color: white;
        font-weight: 600;
        transition: all 0.3s ease;
    }
    
    .btn-orange:hover {
        background: linear-gradient(135deg, #e55a2b, #e8690b);
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(255, 107, 53, 0.3);
        color: white;
    }
    
    .btn-outline-orange {
        border: 2px solid #FF6B35;
        color: #FF6B35;
        font-weight: 600;
        transition: all 0.3s ease;
    }
    
    .btn-outline-orange:hover {
        background: #FF6B35;
        color: white;
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(255, 107, 53, 0.3);
    }
    
    /* Colonnes cachables */
    .column-client.hidden { display: none; }
    .column-event.hidden { display: none; }
    .column-payment.hidden { display: none; }
    
    /* Animation de chargement */
    .loading-row {
        background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
        background-size: 200% 100%;
        animation: loading 1.5s infinite;
    }
    
    @keyframes loading {
        0% { background-position: 200% 0; }
        100% { background-position: -200% 0; }
    }
    
    /* Responsive */
    @media (max-width: 768px) {
        .stat-card {
            margin-bottom: 1rem;
        }
        
        .table-responsive {
            font-size: 0.85rem;
        }
        
        .user-avatar {
            width: 28px;
            height: 28px;
            font-size: 0.75rem;
        }
        
        #bulk-actions {
            min-width: 350px;
            bottom: 10px;
        }
        
        .d-flex.gap-2 {
            flex-wrap: wrap;
        }
    }
    
    /* Mise en évidence des lignes sélectionnées */
    tr.selected {
        background-color: rgba(255, 107, 53, 0.1) !important;
        border-left: 3px solid #FF6B35;
    }
    
    /* Pagination customisée */
    .pagination .page-link {
        border: none;
        color: #6c757d;
        font-weight: 500;
        padding: 8px 12px;
        margin: 0 2px;
        border-radius: 6px;
        transition: all 0.2s ease;
    }
    
    .pagination .page-link:hover {
        background-color: #f8fafc;
        color: #FF6B35;
    }
    
    .pagination .page-item.active .page-link {
        background-color: #FF6B35;
        border-color: #FF6B35;
        color: white;
    }
</style>
@endpush

@push('scripts')
<script>
    let selectedOrders = [];
    
    // Sélection/désélection
    function toggleAll(checkbox) {
        const checkboxes = document.querySelectorAll('.order-checkbox');
        checkboxes.forEach(cb => {
            cb.checked = checkbox.checked;
            toggleRowSelection(cb.closest('tr'), cb.checked);
        });
        updateSelection();
    }
    
    function selectAll() {
        const checkboxes = document.querySelectorAll('.order-checkbox');
        checkboxes.forEach(cb => {
            cb.checked = true;
            toggleRowSelection(cb.closest('tr'), true);
        });
        document.getElementById('select-all').checked = true;
        updateSelection();
    }
    
    function clearSelection() {
        const checkboxes = document.querySelectorAll('.order-checkbox');
        checkboxes.forEach(cb => {
            cb.checked = false;
            toggleRowSelection(cb.closest('tr'), false);
        });
        document.getElementById('select-all').checked = false;
        updateSelection();
    }
    
    function toggleRowSelection(row, selected) {
        if (selected) {
            row.classList.add('selected');
        } else {
            row.classList.remove('selected');
        }
    }
    
    function updateSelection() {
        const checkboxes = document.querySelectorAll('.order-checkbox:checked');
        selectedOrders = Array.from(checkboxes).map(cb => cb.value);
        
        const count = selectedOrders.length;
        document.getElementById('selected-count').textContent = count;
        
        const bulkActions = document.getElementById('bulk-actions');
        if (count > 0) {
            bulkActions.style.display = 'block';
        } else {
            bulkActions.style.display = 'none';
        }
        
        // Mettre à jour le checkbox "select all"
        const allCheckboxes = document.querySelectorAll('.order-checkbox');
        const selectAllCheckbox = document.getElementById('select-all');
        if (count === 0) {
            selectAllCheckbox.indeterminate = false;
            selectAllCheckbox.checked = false;
        } else if (count === allCheckboxes.length) {
            selectAllCheckbox.indeterminate = false;
            selectAllCheckbox.checked = true;
        } else {
            selectAllCheckbox.indeterminate = true;
        }
    }
    
    // Actions sur les commandes
    function updateOrderStatus(orderId, status) {
        const statusNames = {
            'completed': 'terminée',
            'cancelled': 'annulée',
            'pending': 'en attente',
            'refunded': 'remboursée'
        };
        
        if (confirm(`Êtes-vous sûr de vouloir marquer cette commande comme ${statusNames[status]} ?`)) {
            showLoading(true);
            
            fetch(`/admin/orders/${orderId}/status`, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({ status: status })
            })
            .then(response => response.json())
            .then(data => {
                showLoading(false);
                if (data.success) {
                    // Mettre à jour la ligne dans le tableau
                    updateOrderRow(orderId, data.order);
                    showNotification('Statut mis à jour avec succès', 'success');
                } else {
                    showNotification('Erreur lors de la mise à jour: ' + (data.message || 'Erreur inconnue'), 'error');
                }
            })
            .catch(error => {
                showLoading(false);
                console.error('Erreur:', error);
                showNotification('Erreur lors de la mise à jour', 'error');
            });
        }
    }
    
    function deleteOrder(orderId) {
        if (confirm('Êtes-vous sûr de vouloir supprimer cette commande ? Cette action est irréversible.')) {
            showLoading(true);
            
            fetch(`/admin/orders/${orderId}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            })
            .then(response => response.json())
            .then(data => {
                showLoading(false);
                if (data.success) {
                    // Supprimer la ligne du tableau
                    const row = document.querySelector(`tr[data-order-id="${orderId}"]`);
                    if (row) {
                        row.remove();
                    }
                    showNotification('Commande supprimée avec succès', 'success');
                    updateSelection(); // Mettre à jour la sélection
                } else {
                    showNotification('Erreur lors de la suppression: ' + (data.message || 'Erreur inconnue'), 'error');
                }
            })
            .catch(error => {
                showLoading(false);
                console.error('Erreur:', error);
                showNotification('Erreur lors de la suppression', 'error');
            });
        }
    }
    
    function refundOrder(orderId) {
        if (confirm('Êtes-vous sûr de vouloir rembourser cette commande ?')) {
            showLoading(true);
            
            fetch(`/admin/orders/${orderId}/refund`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            })
            .then(response => response.json())
            .then(data => {
                showLoading(false);
                if (data.success) {
                    updateOrderRow(orderId, data.order);
                    showNotification('Remboursement effectué avec succès', 'success');
                } else {
                    showNotification('Erreur lors du remboursement: ' + (data.message || 'Erreur inconnue'), 'error');
                }
            })
            .catch(error => {
                showLoading(false);
                console.error('Erreur:', error);
                showNotification('Erreur lors du remboursement', 'error');
            });
        }
    }
    
    function sendEmail(orderId) {
        // Récupérer les infos de la commande
        const row = document.querySelector(`tr[data-order-id="${orderId}"]`);
        const userEmail = row.querySelector('.column-client small').textContent.trim();
        
        document.getElementById('email-to').value = userEmail;
        document.querySelector('#email-form').dataset.orderId = orderId;
        
        const modal = new bootstrap.Modal(document.getElementById('emailModal'));
        modal.show();
    }
    
    // Actions groupées
    function bulkUpdate(status) {
        if (selectedOrders.length === 0) {
            showNotification('Veuillez sélectionner au moins une commande', 'warning');
            return;
        }
        
        const statusNames = {
            'completed': 'terminées',
            'cancelled': 'annulées'
        };
        
        if (confirm(`Êtes-vous sûr de vouloir marquer ${selectedOrders.length} commande(s) comme ${statusNames[status]} ?`)) {
            showLoading(true);
            
            fetch('/admin/orders/bulk-update', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({
                    orders: selectedOrders,
                    status: status
                })
            })
            .then(response => response.json())
            .then(data => {
                showLoading(false);
                if (data.success) {
                    location.reload(); // Recharger pour voir les changements
                } else {
                    showNotification('Erreur lors de la mise à jour groupée: ' + (data.message || 'Erreur inconnue'), 'error');
                }
            })
            .catch(error => {
                showLoading(false);
                console.error('Erreur:', error);
                showNotification('Erreur lors de la mise à jour groupée', 'error');
            });
        }
    }
    
    function bulkDelete() {
        if (selectedOrders.length === 0) {
            showNotification('Veuillez sélectionner au moins une commande', 'warning');
            return;
        }
        
        if (confirm(`Êtes-vous sûr de vouloir supprimer ${selectedOrders.length} commande(s) ? Cette action est irréversible.`)) {
            showLoading(true);
            
            fetch('/admin/orders/bulk-delete', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({
                    orders: selectedOrders
                })
            })
            .then(response => response.json())
            .then(data => {
                showLoading(false);
                if (data.success) {
                    // Supprimer les lignes du tableau
                    selectedOrders.forEach(orderId => {
                        const row = document.querySelector(`tr[data-order-id="${orderId}"]`);
                        if (row) row.remove();
                    });
                    clearSelection();
                    showNotification(`${data.deleted_count} commande(s) supprimée(s) avec succès`, 'success');
                } else {
                    showNotification('Erreur lors de la suppression groupée: ' + (data.message || 'Erreur inconnue'), 'error');
                }
            })
            .catch(error => {
                showLoading(false);
                console.error('Erreur:', error);
                showNotification('Erreur lors de la suppression groupée', 'error');
            });
        }
    }
    
    function bulkExport() {
        if (selectedOrders.length === 0) {
            showNotification('Veuillez sélectionner au moins une commande', 'warning');
            return;
        }
        
        const params = new URLSearchParams();
        selectedOrders.forEach(id => params.append('orders[]', id));
        
        window.open(`/admin/orders/export?${params.toString()}`, '_blank');
        showNotification(`Export de ${selectedOrders.length} commande(s) en cours...`, 'info');
    }
    
    // Gestion des colonnes
    function toggleColumn(columnName) {
        const columns = document.querySelectorAll(`.column-${columnName}`);
        const isHidden = columns[0].classList.contains('hidden');
        
        columns.forEach(col => {
            if (isHidden) {
                col.classList.remove('hidden');
            } else {
                col.classList.add('hidden');
            }
        });
        
        // Sauvegarder la préférence
        const hiddenColumns = JSON.parse(localStorage.getItem('orders_hidden_columns') || '[]');
        if (isHidden) {
            const index = hiddenColumns.indexOf(columnName);
            if (index > -1) hiddenColumns.splice(index, 1);
        } else {
            if (!hiddenColumns.includes(columnName)) hiddenColumns.push(columnName);
        }
        localStorage.setItem('orders_hidden_columns', JSON.stringify(hiddenColumns));
    }
    
    // Changement du nombre d'éléments par page
    function changePerPage(value) {
        const url = new URL(window.location);
        url.searchParams.set('per_page', value);
        url.searchParams.delete('page'); // Retour à la première page
        window.location.href = url.toString();
    }
    
    // Utilitaires
    function updateOrderRow(orderId, orderData) {
        const row = document.querySelector(`tr[data-order-id="${orderId}"]`);
        if (row) {
            // Mettre à jour le statut
            const statusBadge = row.querySelector('.order-status');
            if (statusBadge) {
                statusBadge.className = `badge order-status ${getStatusClass(orderData.status)}`;
                statusBadge.textContent = orderData.status.charAt(0).toUpperCase() + orderData.status.slice(1);
            }
            
            // Mettre à jour le statut de paiement si disponible
            if (orderData.payment_status) {
                const paymentBadge = row.querySelector('.payment-status');
                if (paymentBadge) {
                    paymentBadge.className = `badge payment-status ${getPaymentStatusClass(orderData.payment_status)}`;
                    paymentBadge.textContent = orderData.payment_status.charAt(0).toUpperCase() + orderData.payment_status.slice(1);
                }
            }
        }
    }
    
    function getStatusClass(status) {
        const classes = {
            'completed': 'bg-success',
            'pending': 'bg-warning',
            'cancelled': 'bg-danger',
            'refunded': 'bg-info'
        };
        return classes[status] || 'bg-secondary';
    }
    
    function getPaymentStatusClass(status) {
        const classes = {
            'paid': 'bg-success',
            'pending': 'bg-warning',
            'failed': 'bg-danger',
            'refunded': 'bg-info'
        };
        return classes[status] || 'bg-secondary';
    }
    
    function showLoading(show) {
        const existingLoader = document.querySelector('.page-loader');
        if (show && !existingLoader) {
            const loader = document.createElement('div');
            loader.className = 'page-loader position-fixed top-0 start-0 w-100 h-100 d-flex align-items-center justify-content-center';
            loader.style.cssText = 'background: rgba(255,255,255,0.8); z-index: 9999;';
            loader.innerHTML = '<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Chargement...</span></div>';
            document.body.appendChild(loader);
        } else if (!show && existingLoader) {
            existingLoader.remove();
        }
    }
    
    function showNotification(message, type = 'info') {
        const colors = {
            'success': '#28a745',
            'error': '#dc3545',
            'warning': '#ffc107',
            'info': '#17a2b8'
        };
        
        const icons = {
            'success': 'fas fa-check-circle',
            'error': 'fas fa-exclamation-circle',
            'warning': 'fas fa-exclamation-triangle',
            'info': 'fas fa-info-circle'
        };
        
        const notification = document.createElement('div');
        notification.className = 'position-fixed';
        notification.style.cssText = `
            top: 20px;
            right: 20px;
            z-index: 9999;
            min-width: 300px;
            background: white;
            border-left: 4px solid ${colors[type]};
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            padding: 16px;
            animation: slideIn 0.3s ease;
        `;
        
        notification.innerHTML = `
            <div class="d-flex align-items-center">
                <i class="${icons[type]} me-2" style="color: ${colors[type]};"></i>
                <span class="flex-grow-1">${message}</span>
                <button type="button" class="btn-close btn-sm" onclick="this.parentElement.parentElement.remove()"></button>
            </div>
        `;
        
        document.body.appendChild(notification);
        
        // Auto-suppression après 5 secondes
        setTimeout(() => {
            if (notification.parentElement) {
                notification.style.animation = 'slideOut 0.3s ease';
                setTimeout(() => notification.remove(), 300);
            }
        }, 5000);
    }
    
    // Événements
    document.addEventListener('DOMContentLoaded', function() {
        // Écouter les changements de sélection
        const checkboxes = document.querySelectorAll('.order-checkbox');
        checkboxes.forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                toggleRowSelection(this.closest('tr'), this.checked);
                updateSelection();
            });
        });
        
        // Restaurer les colonnes cachées
        const hiddenColumns = JSON.parse(localStorage.getItem('orders_hidden_columns') || '[]');
        hiddenColumns.forEach(columnName => {
            const columns = document.querySelectorAll(`.column-${columnName}`);
            columns.forEach(col => col.classList.add('hidden'));
        });
        
        // Formulaire d'envoi d'email
        document.getElementById('email-form').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const orderId = this.dataset.orderId;
            const formData = {
                to: document.getElementById('email-to').value,
                subject: document.getElementById('email-subject').value,
                message: document.getElementById('email-message').value
            };
            
            showLoading(true);
            
            fetch(`/admin/orders/${orderId}/email`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify(formData)
            })
            .then(response => response.json())
            .then(data => {
                showLoading(false);
                if (data.success) {
                    showNotification('Email envoyé avec succès', 'success');
                    bootstrap.Modal.getInstance(document.getElementById('emailModal')).hide();
                    this.reset();
                } else {
                    showNotification('Erreur lors de l\'envoi: ' + (data.message || 'Erreur inconnue'), 'error');
                }
            })
            .catch(error => {
                showLoading(false);
                console.error('Erreur:', error);
                showNotification('Erreur lors de l\'envoi de l\'email', 'error');
            });
        });
    });
    
    // Ajout du CSS pour les animations
    const style = document.createElement('style');
    style.textContent = `
        @keyframes slideIn {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        
        @keyframes slideOut {
            from { transform: translateX(0); opacity: 1; }
            to { transform: translateX(100%); opacity: 0; }
        }
    `;
    document.head.appendChild(style);
</script>
@endpush

    <!-- Statistiques des commandes -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="stat-card">
                <div class="d-flex align-items-center">
                    <div class="stat-icon success me-3">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stat-info">
                        <h4>{{ $stats['completed'] ?? 0 }}</h4>
                        <p class="text-muted mb-0">Terminées</p>
                        <small class="text-success">
                            {{ number_format($stats['completed_revenue'] ?? 0) }} F
                        </small>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="d-flex align-items-center">
                    <div class="stat-icon warning me-3">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="stat-info">
                        <h4>{{ $stats['pending'] ?? 0 }}</h4>
                        <p class="text-muted mb-0">En attente</p>
                        <small class="text-warning">
                            {{ number_format($stats['pending_revenue'] ?? 0) }} F
                        </small>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="d-flex align-items-center">
                    <div class="stat-icon danger me-3">
                        <i class="fas fa-times-circle"></i>
                    </div>
                    <div class="stat-info">
                        <h4>{{ $stats['cancelled'] ?? 0 }}</h4>
                        <p class="text-muted mb-0">Annulées</p>
                        <small class="text-danger">
                            {{ number_format($stats['cancelled_revenue'] ?? 0) }} F
                        </small>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="d-flex align-items-center">
                    <div class="stat-icon info me-3">
                        <i class="fas fa-money-bill-wave"></i>
                    </div>
                    <div class="stat-info">
                        <h4>{{ number_format($stats['total_revenue'] ?? 0) }} F</h4>
                        <p class="text-muted mb-0">Chiffre d'affaires</p>
                        <small class="text-info">
                            Panier moyen: {{ number_format($stats['average_order'] ?? 0) }} F
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filtres et recherche -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.orders') }}">
                <div class="row align-items-end">
                    <div class="col-md-3">
                        <label class="form-label">Recherche</label>
                        <input type="text" name="search" class="form-control" 
                               placeholder="N° commande, client..." value="{{ request('search') }}">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Statut</label>
                        <select name="status" class="form-select">
                            <option value="">Tous</option>
                            <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>En attente</option>
                            <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Terminées</option>
                            <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Annulées</option>
                            <option value="refunded" {{ request('status') == 'refunded' ? 'selected' : '' }}>Remboursées</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Événement</label>
                        <select name="event_id" class="form-select">
                            <option value="">Tous</option>
                            @foreach($events ?? [] as $event)
                                <option value="{{ $event->id }}" {{ request('event_id') == $event->id ? 'selected' : '' }}>
                                    {{ Str::limit($event->title, 20) }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Date</label>
                        <select name="date_filter" class="form-select">
                            <option value="">Toutes</option>
                            <option value="today" {{ request('date_filter') == 'today' ? 'selected' : '' }}>Aujourd'hui</option>
                            <option value="week" {{ request('date_filter') == 'week' ? 'selected' : '' }}>Cette semaine</option>
                            <option value="month" {{ request('date_filter') == 'month' ? 'selected' : '' }}>Ce mois</option>
                            <option value="year" {{ request('date_filter') == 'year' ? 'selected' : '' }}>Cette année</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Montant</label>
                        <select name="amount_filter" class="form-select">
                            <option value="">Tous</option>
                            <option value="0-10000" {{ request('amount_filter') == '0-10000' ? 'selected' : '' }}>0 - 10 000 F</option>
                            <option value="10000-50000" {{ request('amount_filter') == '10000-50000' ? 'selected' : '' }}>10 000 - 50 000 F</option>
                            <option value="50000-100000" {{ request('amount_filter') == '50000-100000' ? 'selected' : '' }}>50 000 - 100 000 F</option>
                            <option value="100000+" {{ request('amount_filter') == '100000+' ? 'selected' : '' }}>100 000 F+</option>
                        </select>
                    </div>
                    <div class="col-md-1">
                        <button type="submit" class="btn btn-orange w-100">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </div>
                
                <!-- Filtres avancés (collapsible) -->
                <div class="row mt-3">
                    <div class="col-12">
                        <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-toggle="collapse" data-bs-target="#advancedFilters">
                            <i class="fas fa-filter me-2"></i>Filtres avancés
                        </button>
                        <a href="{{ route('admin.orders') }}" class="btn btn-outline-secondary btn-sm ms-2">
                            <i class="fas fa-times me-2"></i>Réinitialiser
                        </a>
                    </div>
                </div>
                
                <div class="collapse mt-3" id="advancedFilters">
                    <div class="border-top pt-3">
                        <div class="row">
                            <div class="col-md-3">
                                <label class="form-label">Statut paiement</label>
                                <select name="payment_status" class="form-select">
                                    <option value="">Tous</option>
                                    <option value="pending" {{ request('payment_status') == 'pending' ? 'selected' : '' }}>En attente</option>
                                    <option value="paid" {{ request('payment_status') == 'paid' ? 'selected' : '' }}>Payé</option>
                                    <option value="failed" {{ request('payment_status') == 'failed' ? 'selected' : '' }}>Échoué</option>
                                    <option value="refunded" {{ request('payment_status') == 'refunded' ? 'selected' : '' }}>Remboursé</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Méthode de paiement</label>
                                <select name="payment_method" class="form-select">
                                    <option value="">Toutes</option>
                                    <option value="orange_money" {{ request('payment_method') == 'orange_money' ? 'selected' : '' }}>Orange Money</option>
                                    <option value="mtn_money" {{ request('payment_method') == 'mtn_money' ? 'selected' : '' }}>MTN Money</option>
                                    <option value="moov_money" {{ request('payment_method') == 'moov_money' ? 'selected' : '' }}>Moov Money</option>
                                    <option value="wave" {{ request('payment_method') == 'wave' ? 'selected' : '' }}>Wave</option>
                                    <option value="card" {{ request('payment_method') == 'card' ? 'selected' : '' }}>Carte bancaire</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Date début</label>
                                <input type="date" name="start_date" class="form-control" value="{{ request('start_date') }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Date fin</label>
                                <input type="date" name="end_date" class="form-control" value="{{ request('end_date') }}">
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Liste des commandes -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">
                <i class="fas fa-shopping-cart me-2"></i>
                Liste des commandes
                @if(isset($orders) && $orders->total() > 0)
                    <span class="badge bg-secondary ms-2">{{ $orders->total() }}</span>
                @endif
            </h5>
            <div class="d-flex gap-2">
                <button class="btn btn-outline-secondary btn-sm" onclick="selectAll()">
                    <i class="fas fa-check-square me-1"></i>Tout sélectionner
                </button>
                <button class="btn btn-outline-secondary btn-sm" onclick="clearSelection()">
                    <i class="fas fa-square me-1"></i>Désélectionner
                </button>
                <div class="dropdown">
                    <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown">
                        <i class="fas fa-columns me-1"></i>Colonnes
                    </button>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="#" onclick="toggleColumn('client')">
                            <input type="checkbox" checked> Client
                        </a></li>
                        <li><a class="dropdown-item" href="#" onclick="toggleColumn('event')">
                            <input type="checkbox" checked> Événement
                        </a></li>
                        <li><a class="dropdown-item" href="#" onclick="toggleColumn('payment')">
                            <input type="checkbox" checked> Paiement
                        </a></li>
                    </ul>
                </div>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0" id="orders-table">
                    <thead>
                        <tr>
                            <th width="30">
                                <input type="checkbox" id="select-all" onchange="toggleAll(this)">
                            </th>
                            <th>
                                <a href="{{ request()->fullUrlWithQuery(['sort' => 'order_number', 'direction' => request('direction') == 'asc' ? 'desc' : 'asc']) }}" 
                                   class="text-decoration-none text-dark">
                                    N° Commande
                                    @if(request('sort') == 'order_number')
                                        <i class="fas fa-sort-{{ request('direction') == 'asc' ? 'up' : 'down' }} ms-1"></i>
                                    @endif
                                </a>
                            </th>
                            <th class="column-client">Client</th>
                            <th class="column-event">Événement</th>
                            <th>
                                <a href="{{ request()->fullUrlWithQuery(['sort' => 'total_amount', 'direction' => request('direction') == 'asc' ? 'desc' : 'asc']) }}" 
                                   class="text-decoration-none text-dark">
                                    Montant
                                    @if(request('sort') == 'total_amount')
                                        <i class="fas fa-sort-{{ request('direction') == 'asc' ? 'up' : 'down' }} ms-1"></i>
                                    @endif
                                </a>
                            </th>
                            <th>Statut</th>
                            <th class="column-payment">Paiement</th>
                            <th>
                                <a href="{{ request()->fullUrlWithQuery(['sort' => 'created_at', 'direction' => request('direction') == 'asc' ? 'desc' : 'asc']) }}" 
                                   class="text-decoration-none text-dark">
                                    Date
                                    @if(request('sort') == 'created_at')
                                        <i class="fas fa-sort-{{ request('direction') == 'asc' ? 'up' : 'down' }} ms-1"></i>
                                    @endif
                                </a>
                            </th>
                            <th width="120">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($orders ?? [] as $order)
                        <tr data-order-id="{{ $order->id }}">
                            <td>
                                <input type="checkbox" class="order-checkbox" value="{{ $order->id }}">
                            </td>
                            <td>
                                <div class="fw-bold">
                                    <a href="{{ route('admin.orders.show', $order) }}" class="text-decoration-none">
                                        #{{ $order->order_number ?? $order->id }}
                                    </a>
                                </div>
                                <small class="text-muted">{{ $order->reference ?? '' }}</small>
                            </td>
                            <td class="column-client">
                                @if($order->user)
                                    <div class="d-flex align-items-center">
                                        <div class="user-avatar me-2">
                                            {{ substr($order->user->name, 0, 1) }}
                                        </div>
                                        <div>
                                            <div class="fw-semibold">{{ $order->user->name }}</div>
                                            <small class="text-muted">{{ $order->user->email }}</small>
                                        </div>
                                    </div>
                                @else
                                    <span class="text-muted">
                                        <i class="fas fa-user-slash me-1"></i>Client supprimé
                                    </span>
                                @endif
                            </td>
                            <td class="column-event">
                                @if($order->event)
                                    <div>
                                        <div class="fw-semibold">{{ Str::limit($order->event->title, 25) }}</div>
                                        <small class="text-muted">
                                            <i class="fas fa-calendar me-1"></i>
                                            {{ $order->event->date?->format('d/m/Y') ?? 'Date non définie' }}
                                        </small>
                                    </div>
                                @else
                                    <span class="text-muted">
                                        <i class="fas fa-calendar-times me-1"></i>Événement supprimé
                                    </span>
                                @endif
                            </td>
                            <td>
                                <div class="fw-bold text-primary">{{ number_format($order->total_amount ?? 0) }} F</div>
                                @if($order->discount_amount > 0)
                                    <small class="text-success">
                                        <i class="fas fa-tag me-1"></i>-{{ number_format($order->discount_amount) }} F
                                    </small>
                                @endif
                                @if($order->tickets_count ?? 0)
                                    <div><small class="text-muted">{{ $order->tickets_count }} billet(s)</small></div>
                                @endif
                            </td>
                            <td>
                                <span class="badge order-status {{ 
                                    $order->status == 'completed' ? 'bg-success' : 
                                    ($order->status == 'pending' ? 'bg-warning' : 
                                    ($order->status == 'cancelled' ? 'bg-danger' : 
                                    ($order->status == 'refunded' ? 'bg-info' : 'bg-secondary'))) 
                                }}">
                                    {{ ucfirst($order->status ?? 'pending') }}
                                </span>
                            </td>
                            <td class="column-payment">
                                <div>
                                    <span class="badge payment-status {{ 
                                        $order->payment_status == 'paid' ? 'bg-success' : 
                                        ($order->payment_status == 'pending' ? 'bg-warning' : 'bg-danger') 
                                    }}">
                                        {{ ucfirst($order->payment_status ?? 'pending') }}
                                    </span>
                                </div>
                                <small class="text-muted">
                                    @if($order->payment_method)
                                        @switch($order->payment_method)
                                            @case('orange_money')
                                                <i class="fas fa-mobile-alt me-1" style="color: #FF6B35;"></i>Orange Money
                                                @break
                                            @case('mtn_money')
                                                <i class="fas fa-mobile-alt me-1" style="color: #FFCD00;"></i>MTN Money
                                                @break
                                            @case('moov_money')
                                                <i class="fas fa-mobile-alt me-1" style="color: #00AEEF;"></i>Moov Money
                                                @break
                                            @case('wave')
                                                <i class="fas fa-mobile-alt me-1" style="color: #01C3F7;"></i>Wave
                                                @break
                                            @case('card')
                                                <i class="fas fa-credit-card me-1"></i>Carte
                                                @break
                                            @default
                                                {{ $order->payment_method }}
                                        @endswitch
                                    @else
                                        N/A
                                    @endif
                                </small>
                            </td>
                            <td>
                                <div>{{ $order->created_at->format('d/m/Y') }}</div>
                                <small class="text-muted">{{ $order->created_at->format('H:i') }}</small>
                            </td>
                            <td>
                                <div class="dropdown">
                                    <button class="btn btn-outline-secondary btn-sm dropdown-toggle" 
                                            type="button" data-bs-toggle="dropdown">
                                        <i class="fas fa-ellipsis-v"></i>
                                    </button>
                                    <ul class="dropdown-menu">
                                        <li>
                                            <a class="dropdown-item" href="{{ route('admin.orders.show', $order) }}">
                                                <i class="fas fa-eye me-2"></i>Voir détails
                                            </a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item" href="{{ route('admin.orders.pdf', $order) }}" target="_blank">
                                                <i class="fas fa-file-pdf me-2"></i>Télécharger PDF
                                            </a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item" href="#" onclick="sendEmail({{ $order->id }})">
                                                <i class="fas fa-envelope me-2"></i>Envoyer email
                                            </a>
                                        </li>
                                        <li><hr class="dropdown-divider"></li>
                                        @if($order->status == 'pending')
                                        <li>
                                            <a class="dropdown-item text-success" href="#" 
                                               onclick="updateOrderStatus({{ $order->id }}, 'completed')">
                                                <i class="fas fa-check me-2"></i>Confirmer
                                            </a>
                                        </li>
                                        @endif
                                        @if($order->status != 'cancelled')
                                        <li>
                                            <a class="dropdown-item text-warning" href="#" 
                                               onclick="updateOrderStatus({{ $order->id }}, 'cancelled')">
                                                <i class="fas fa-times me-2"></i>Annuler
                                            </a>
                                        </li>
                                        @endif
                                        @if($order->status == 'completed' && $order->payment_status == 'paid')
                                        <li>
                                            <a class="dropdown-item text-info" href="#" 
                                               onclick="refundOrder({{ $order->id }})">
                                                <i class="fas fa-undo me-2"></i>Rembourser
                                            </a>
                                        </li>
                                        @endif
                                        <li>
                                            <a class="dropdown-item text-danger" href="#" 
                                               onclick="deleteOrder({{ $order->id }})">
                                                <i class="fas fa-trash me-2"></i>Supprimer
                                            </a>
                                        </li>
                                    </ul>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="9" class="text-center py-5">
                                <i class="fas fa-shopping-cart fa-3x text-muted mb-3"></i>
                                <p class="text-muted mb-0">Aucune commande trouvée</p>
                                <small class="text-muted">
                                    @if(request()->hasAny(['search', 'status', 'event_id', 'date_filter', 'amount_filter']))
                                        Essayez de modifier vos filtres de recherche
                                    @else
                                        Les commandes apparaîtront ici une fois créées
                                    @endif
                                </small>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        
        @if(isset($orders) && $orders->hasPages())
        <div class="card-footer">
            <div class="d-flex justify-content-between align-items-center">
                <div class="text-muted">
                    Affichage de {{ $orders->firstItem() }} à {{ $orders->lastItem() }} 
                    sur {{ $orders->total() }} commandes
                </div>
                <div class="d-flex align-items-center gap-2">
                    <span class="text-muted small">Par page:</span>
                    <select class="form-select form-select-sm" style="width: auto;" onchange="changePerPage(this.value)">
                        <option value="15" {{ request('per_page', 15) == 15 ? 'selected' : '' }}>15</option>
                        <option value="25" {{ request('per_page', 15) == 25 ? 'selected' : '' }}>25</option>
                        <option value="50" {{ request('per_page', 15) == 50 ? 'selected' : '' }}>50</option>
                        <option value="100" {{ request('per_page', 15) == 100 ? 'selected' : '' }}>100</option>
                    </select>
                </div>
                {{ $orders->links() }}
            </div>
        </div>
        @endif
    </div>

    <!-- Actions groupées sélectionnées -->
    <div id="bulk-actions" class="position-fixed bottom-0 start-50 translate-middle-x bg-white border rounded-3 p-3 shadow-lg" style="display: none; z-index: 1050;">
        <div class="d-flex align-items-center gap-3">
            <span class="fw-bold">
                <span id="selected-count">0</span> commande(s) sélectionnée(s)
            </span>
            <div class="d-flex gap-2">
                <button class="btn btn-success btn-sm" onclick="bulkUpdate('completed')">
                    <i class="fas fa-check me-1"></i>Confirmer
                </button>
                <button class="btn btn-warning btn-sm" onclick="bulkUpdate('cancelled')">
                    <i class="fas fa-times me-1"></i>Annuler
                </button>
                <button class="btn btn-info btn-sm" onclick="bulkExport()">
                    <i class="fas fa-download me-1"></i>Exporter
                </button>
                <button class="btn btn-danger btn-sm" onclick="bulkDelete()">
                    <i class="fas fa-trash me-1"></i>Supprimer
                </button>
                <button class="btn btn-outline-secondary btn-sm" onclick="clearSelection()">
                    <i class="fas fa-times me-1"></i>Fermer
                </button>
            </div>
        </div>
    </div>

    <!-- Modal d'envoi d'email -->
    <div class="modal fade" id="emailModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Envoyer un email</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="email-form">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Destinataire</label>
                            <input type="email" id="email-to" class="form-control" readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Sujet</label>
                            <input type="text" id="email-subject" class="form-control" value="Concernant votre commande">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Message</label>
                            <textarea id="email-message" class="form-control" rows="5" placeholder="Votre message..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-orange">
                            <i class="fas fa-paper-plane me-2"></i>Envoyer
                        </button>
                    </div>
                </form>
            </div>