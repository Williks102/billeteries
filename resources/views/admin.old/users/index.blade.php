@extends('layouts.admin')

@section('title', 'Gestion des Utilisateurs')

@section('breadcrumb')
<li class="breadcrumb-item active">Utilisateurs</li>
@endsection

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">
        <i class="fas fa-users text-orange me-2"></i>
        Gestion des Utilisateurs
    </h1>
    <a href="{{ route('admin.users.create') }}" class="btn btn-orange">
        <i class="fas fa-user-plus me-2"></i>Nouvel utilisateur
    </a>
</div>

<!-- Statistiques -->
<div class="row mb-4">
    <div class="col-md-2 mb-3">
        <div class="stat-card text-center">
            <div class="stat-number text-primary">{{ $stats['total'] ?? 0 }}</div>
            <div class="stat-label">Total</div>
        </div>
    </div>
    <div class="col-md-2 mb-3">
        <div class="stat-card text-center">
            <div class="stat-number text-danger">{{ $stats['admins'] ?? 0 }}</div>
            <div class="stat-label">Admins</div>
        </div>
    </div>
    <div class="col-md-2 mb-3">
        <div class="stat-card text-center">
            <div class="stat-number text-warning">{{ $stats['promoteurs'] ?? 0 }}</div>
            <div class="stat-label">Promoteurs</div>
        </div>
    </div>
    <div class="col-md-2 mb-3">
        <div class="stat-card text-center">
            <div class="stat-number text-info">{{ $stats['acheteurs'] ?? 0 }}</div>
            <div class="stat-label">Acheteurs</div>
        </div>
    </div>
    <div class="col-md-2 mb-3">
        <div class="stat-card text-center">
            <div class="stat-number text-success">{{ $stats['verified'] ?? 0 }}</div>
            <div class="stat-label">Vérifiés</div>
        </div>
    </div>
    <div class="col-md-2 mb-3">
        <div class="stat-card text-center">
            <div class="stat-number text-orange">{{ $stats['new_this_month'] ?? 0 }}</div>
            <div class="stat-label">Ce mois</div>
        </div>
    </div>
</div>

<!-- Filtres et recherche -->
<div class="dashboard-card mb-4">
    <form method="GET" action="{{ route('admin.users.index') }}" class="row g-3">
        <div class="col-md-3">
            <label class="form-label">Recherche</label>
            <input type="text" class="form-control" name="search" 
                   value="{{ request('search') }}" 
                   placeholder="Nom ou email...">
        </div>
        <div class="col-md-2">
            <label class="form-label">Rôle</label>
            <select class="form-select" name="role">
                <option value="">Tous les rôles</option>
                <option value="admin" {{ request('role') === 'admin' ? 'selected' : '' }}>Admin</option>
                <option value="promoteur" {{ request('role') === 'promoteur' ? 'selected' : '' }}>Promoteur</option>
                <option value="acheteur" {{ request('role') === 'acheteur' ? 'selected' : '' }}>Acheteur</option>
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label">Statut</label>
            <select class="form-select" name="status">
                <option value="">Tous</option>
                <option value="verified" {{ request('status') === 'verified' ? 'selected' : '' }}>Vérifiés</option>
                <option value="unverified" {{ request('status') === 'unverified' ? 'selected' : '' }}>Non vérifiés</option>
            </select>
        </div>
        <div class="col-md-3 d-flex align-items-end">
            <button type="submit" class="btn btn-primary me-2">
                <i class="fas fa-search me-1"></i>Filtrer
            </button>
            <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary me-2">
                <i class="fas fa-times me-1"></i>Reset
            </a>
            <a href="{{ route('admin.users.export', request()->query()) }}" class="btn btn-outline-success">
                <i class="fas fa-download me-1"></i>Export
            </a>
        </div>
    </form>
</div>

<!-- Actions en lot -->
<div class="dashboard-card mb-4" id="bulk-actions" style="display: none;">
    <div class="d-flex align-items-center justify-content-between">
        <span id="selected-count">0 utilisateur(s) sélectionné(s)</span>
        <div>
            <button type="button" class="btn btn-success btn-sm" onclick="bulkAction('verify')">
                <i class="fas fa-check me-1"></i>Vérifier emails
            </button>
            <button type="button" class="btn btn-warning btn-sm" onclick="bulkAction('unverify')">
                <i class="fas fa-times me-1"></i>Dé-vérifier
            </button>
            <button type="button" class="btn btn-danger btn-sm" onclick="bulkAction('delete')">
                <i class="fas fa-trash me-1"></i>Supprimer
            </button>
        </div>
    </div>
</div>

<!-- Liste des utilisateurs -->
<div class="dashboard-card">
    <div class="table-responsive">
        <table class="table table-hover">
            <thead class="table-light">
                <tr>
                    <th width="50">
                        <input type="checkbox" id="select-all" class="form-check-input">
                    </th>
                    <th>Utilisateur</th>
                    <th>Rôle</th>
                    <th>Statut Email</th>
                    <th>Téléphone</th>
                    <th>Inscription</th>
                    <th width="120">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($users as $user)
                    <tr>
                        <td>
                            <input type="checkbox" class="form-check-input user-checkbox" 
                                   value="{{ $user->id }}" 
                                   data-name="{{ $user->name }}">
                        </td>
                        <td>
                            <div class="d-flex align-items-center">
                                <div class="avatar-sm bg-light rounded-circle d-flex align-items-center justify-content-center me-3">
                                    <i class="fas fa-user text-muted"></i>
                                </div>
                                <div>
                                    <div class="fw-semibold">{{ $user->name }}</div>
                                    <small class="text-muted">{{ $user->email }}</small>
                                </div>
                            </div>
                        </td>
                        <td>
                            <span class="badge {{ $user->role === 'admin' ? 'bg-danger' : ($user->role === 'promoteur' ? 'bg-warning' : 'bg-info') }}">
                                {{ ucfirst($user->role) }}
                            </span>
                        </td>
                        <td>
                            <div class="d-flex align-items-center">
                                @if($user->email_verified_at)
                                    <span class="badge bg-success me-2">Vérifié</span>
                                @else
                                    <span class="badge bg-warning me-2">Non vérifié</span>
                                @endif
                                
                                @if($user->id !== auth()->id())
                                    <button type="button" 
                                            class="btn btn-sm btn-outline-primary" 
                                            onclick="toggleEmailVerification({{ $user->id }})"
                                            title="Basculer la vérification">
                                        <i class="fas fa-sync-alt"></i>
                                    </button>
                                @endif
                            </div>
                        </td>
                        <td>
                            <span class="text-muted">{{ $user->phone ?: 'N/A' }}</span>
                        </td>
                        <td>
                            <span class="text-muted">{{ $user->created_at->format('d/m/Y') }}</span>
                            <br>
                            <small class="text-muted">{{ $user->created_at->diffForHumans() }}</small>
                        </td>
                        <td>
                            <div class="btn-group" role="group">
                                <a href="{{ route('admin.users.show', $user) }}" 
                                   class="btn btn-sm btn-outline-info"
                                   title="Voir détails">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="{{ route('admin.users.edit', $user) }}" 
                                   class="btn btn-sm btn-outline-primary"
                                   title="Modifier">
                                    <i class="fas fa-edit"></i>
                                </a>
                                @if($user->id !== auth()->id())
                                    <button onclick="deleteUser({{ $user->id }}, '{{ $user->name }}')" 
                                            class="btn btn-sm btn-outline-danger"
                                            title="Supprimer">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center py-5">
                            <i class="fas fa-users fa-3x text-muted mb-3"></i>
                            <p class="text-muted mb-0">Aucun utilisateur trouvé</p>
                            @if(request()->hasAny(['search', 'role', 'status']))
                                <a href="{{ route('admin.users.index') }}" class="btn btn-sm btn-outline-primary mt-2">
                                    Voir tous les utilisateurs
                                </a>
                            @endif
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    
    @if($users->hasPages())
        <div class="card-footer bg-white">
            {{ $users->appends(request()->query())->links() }}
        </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
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
</script>

<style>
.avatar-sm {
    width: 40px;
    height: 40px;
}

.stat-card {
    transition: all 0.3s ease;
}

.stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
}

.btn-group .btn {
    margin-right: 2px;
}

.btn-group .btn:last-child {
    margin-right: 0;
}

.table th {
    border-top: none;
    font-weight: 600;
    color: var(--black-primary);
}

.badge {
    font-size: 0.75rem;
}

#bulk-actions {
    border-left: 4px solid var(--primary-orange);
    background-color: rgba(255, 107, 53, 0.05);
}

.form-check-input:checked {
    background-color: var(--primary-orange);
    border-color: var(--primary-orange);
}

.table-hover tbody tr:hover {
    background-color: rgba(255, 107, 53, 0.05);
}
</style>
@endpush