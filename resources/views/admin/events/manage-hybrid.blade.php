{{-- resources/views/admin/events/manage-hybrid.blade.php --}}
@extends('layouts.admin')

@section('title', 'Gestion Hybride - ' . $event->title)

@section('content')
<div class="container-fluid px-4">
    <!-- En-tête avec statut de gestion -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">
                <i class="fas fa-cogs me-2 text-warning"></i>
                Gestion Hybride - {{ $event->title }}
            </h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.events.index') }}">Événements</a></li>
                    <li class="breadcrumb-item active">Gestion Hybride</li>
                </ol>
            </nav>
        </div>
        <div>
            <a href="{{ route('admin.events.show', $event) }}" class="btn btn-outline-secondary me-2">
                <i class="fas fa-eye me-2"></i>Vue Standard
            </a>
            @if($needsIntervention)
                <span class="badge bg-danger fs-6 px-3 py-2">
                    <i class="fas fa-exclamation-triangle me-1"></i>
                    Intervention Requise
                </span>
            @endif
        </div>
    </div>

    <!-- Alerte du mode de gestion actuel -->
    <div class="row mb-4">
        <div class="col-12">
            @php
                $modeConfig = match($event->management_mode) {
                    'admin' => ['color' => 'danger', 'icon' => 'user-shield', 'text' => 'Mode Admin Complet'],
                    'collaborative' => ['color' => 'warning', 'icon' => 'users-cog', 'text' => 'Mode Collaboratif'], 
                    'promoter' => ['color' => 'success', 'icon' => 'user', 'text' => 'Mode Promoteur'],
                    default => ['color' => 'secondary', 'icon' => 'question', 'text' => 'Mode Inconnu']
                };
            @endphp
            
            <div class="alert alert-{{ $modeConfig['color'] }} d-flex align-items-center">
                <i class="fas fa-{{ $modeConfig['icon'] }} me-3 fa-2x"></i>
                <div class="flex-grow-1">
                    <h5 class="alert-heading mb-1">{{ $modeConfig['text'] }}</h5>
                    <p class="mb-0">{{ $event->management_reason ?? 'Aucune raison spécifiée' }}</p>
                    @if($event->management_changed_at)
                        <small class="text-muted">
                            Modifié le {{ $event->management_changed_at->format('d/m/Y à H:i') }} 
                            par {{ $event->managementChanger->name ?? 'Système' }}
                        </small>
                    @endif
                </div>
                <button class="btn btn-outline-{{ $modeConfig['color'] }}" data-bs-toggle="modal" data-bs-target="#changeModeModal">
                    <i class="fas fa-exchange-alt me-2"></i>Changer Mode
                </button>
            </div>
        </div>
    </div>

    <!-- Statistiques de l'événement -->
    <div class="row mb-4">
        <div class="col-md-3 mb-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <div class="h5 mb-0">{{ number_format($stats['total_tickets']) }}</div>
                            <div class="small">Billets Totaux</div>
                        </div>
                        <i class="fas fa-ticket-alt fa-2x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <div class="h5 mb-0">{{ number_format($stats['sold_tickets']) }}</div>
                            <div class="small">Billets Vendus</div>
                        </div>
                        <i class="fas fa-chart-line fa-2x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card bg-warning text-white">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <div class="h5 mb-0">{{ number_format($stats['available_tickets']) }}</div>
                            <div class="small">Disponibles</div>
                        </div>
                        <i class="fas fa-clock fa-2x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <div class="h5 mb-0">{{ number_format($stats['total_revenue']) }} FCFA</div>
                            <div class="small">Revenus Totaux</div>
                        </div>
                        <i class="fas fa-dollar-sign fa-2x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Actions de gestion -->
        <div class="col-lg-8">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-tools me-2"></i>Actions de Gestion
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <!-- Gestion des billets -->
                        <div class="col-md-6">
                            <div class="d-grid">
                                @if($event->adminHasPermission('create_tickets') || $event->adminHasPermission('edit_tickets'))
                                    <a href="{{ route('admin.events.manage-tickets', $event) }}" 
                                       class="btn btn-primary btn-lg">
                                        <i class="fas fa-ticket-alt me-2"></i>
                                        Gérer les Billets
                                        <small class="d-block">Créer/Modifier les types de billets</small>
                                    </a>
                                @else
                                    <button class="btn btn-outline-secondary btn-lg" disabled>
                                        <i class="fas fa-lock me-2"></i>
                                        Billets - Accès Restreint
                                        <small class="d-block">Permission requise</small>
                                    </button>
                                @endif
                            </div>
                        </div>

                        <!-- Gestion des prix -->
                        <div class="col-md-6">
                            <div class="d-grid">
                                @if($event->adminHasPermission('set_prices'))
                                    <button class="btn btn-warning btn-lg" data-bs-toggle="modal" data-bs-target="#pricesModal">
                                        <i class="fas fa-dollar-sign me-2"></i>
                                        Modifier les Prix
                                        <small class="d-block">Ajuster les tarifs des billets</small>
                                    </button>
                                @else
                                    <button class="btn btn-outline-secondary btn-lg" disabled>
                                        <i class="fas fa-lock me-2"></i>
                                        Prix - Accès Restreint
                                        <small class="d-block">Permission requise</small>
                                    </button>
                                @endif
                            </div>
                        </div>

                        <!-- Publication -->
                        <div class="col-md-6">
                            <div class="d-grid">
                                @if($event->adminHasPermission('publish_event'))
                                    @if($event->status === 'published')
                                        <button class="btn btn-secondary btn-lg">
                                            <i class="fas fa-eye-slash me-2"></i>
                                            Dépublier
                                            <small class="d-block">Retirer de la vente</small>
                                        </button>
                                    @else
                                        <button class="btn btn-success btn-lg">
                                            <i class="fas fa-eye me-2"></i>
                                            Publier l'Événement
                                            <small class="d-block">Mettre en vente</small>
                                        </button>
                                    @endif
                                @else
                                    <button class="btn btn-outline-secondary btn-lg" disabled>
                                        <i class="fas fa-lock me-2"></i>
                                        Publication - Accès Restreint
                                        <small class="d-block">Permission requise</small>
                                    </button>
                                @endif
                            </div>
                        </div>

                        <!-- Communication -->
                        <div class="col-md-6">
                            <div class="d-grid">
                                <button class="btn btn-info btn-lg" data-bs-toggle="modal" data-bs-target="#contactPromoterModal">
                                    <i class="fas fa-envelope me-2"></i>
                                    Contacter le Promoteur
                                    <small class="d-block">{{ $event->promoteur->name }}</small>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Liste des billets actuels -->
            @if($event->ticketTypes->count() > 0)
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-list me-2"></i>Types de Billets Actuels
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Type</th>
                                        <th>Prix</th>
                                        <th>Disponible</th>
                                        <th>Vendus</th>
                                        <th>Période de Vente</th>
                                        <th>Statut</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($event->ticketTypes as $ticketType)
                                        <tr>
                                            <td>
                                                <strong>{{ $ticketType->name }}</strong>
                                                @if($ticketType->description)
                                                    <br><small class="text-muted">{{ $ticketType->description }}</small>
                                                @endif
                                            </td>
                                            <td>
                                                <span class="fw-bold">{{ number_format($ticketType->price) }} FCFA</span>
                                            </td>
                                            <td>{{ number_format($ticketType->quantity_available) }}</td>
                                            <td>{{ number_format($ticketType->quantity_sold) }}</td>
                                            <td>
                                                <small>
                                                    Du {{ $ticketType->sale_start_date->format('d/m') }}<br>
                                                    au {{ $ticketType->sale_end_date->format('d/m') }}
                                                </small>
                                            </td>
                                            <td>
                                                @if($ticketType->is_active)
                                                    <span class="badge bg-success">Actif</span>
                                                @else
                                                    <span class="badge bg-secondary">Inactif</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if($event->adminHasPermission('edit_tickets'))
                                                    <button class="btn btn-sm btn-outline-primary">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @else
                <div class="card">
                    <div class="card-body text-center py-5">
                        <i class="fas fa-ticket-alt fa-3x text-muted mb-3"></i>
                        <h4>Aucun billet configuré</h4>
                        <p class="text-muted">Cet événement n'a pas encore de types de billets définis.</p>
                        @if($event->adminHasPermission('create_tickets'))
                            <a href="{{ route('admin.events.manage-tickets', $event) }}" class="btn btn-primary">
                                <i class="fas fa-plus me-2"></i>Créer les premiers billets
                            </a>
                        @endif
                    </div>
                </div>
            @endif
        </div>

        <!-- Historique et informations -->
        <div class="col-lg-4">
            <!-- Informations de l'événement -->
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="mb-0">
                        <i class="fas fa-info-circle me-2"></i>Informations
                    </h6>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <strong>Promoteur :</strong><br>
                        <span class="text-muted">{{ $event->promoteur->name }}</span><br>
                        <small>{{ $event->promoteur->email }}</small>
                    </div>
                    <div class="mb-3">
                        <strong>Date :</strong><br>
                        <span class="text-muted">{{ $event->event_date->format('d/m/Y à H:i') }}</span>
                    </div>
                    <div class="mb-3">
                        <strong>Lieu :</strong><br>
                        <span class="text-muted">{{ $event->venue }}</span>
                    </div>
                    <div class="mb-3">
                        <strong>Statut :</strong><br>
                        @php
                            $statusConfig = match($event->status) {
                                'published' => ['color' => 'success', 'text' => 'Publié'],
                                'draft' => ['color' => 'secondary', 'text' => 'Brouillon'],
                                'pending' => ['color' => 'warning', 'text' => 'En attente'],
                                'rejected' => ['color' => 'danger', 'text' => 'Rejeté'],
                                default => ['color' => 'secondary', 'text' => 'Inconnu']
                            };
                        @endphp
                        <span class="badge bg-{{ $statusConfig['color'] }}">{{ $statusConfig['text'] }}</span>
                    </div>
                </div>
            </div>

            <!-- Historique des modifications -->
            @if($managementHistory->count() > 0)
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0">
                            <i class="fas fa-history me-2"></i>Historique de Gestion
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="timeline">
                            @foreach($managementHistory as $log)
                                <div class="timeline-item">
                                    <div class="timeline-marker bg-{{ $log->action_color }}">
                                        <i class="{{ $log->action_icon }}"></i>
                                    </div>
                                    <div class="timeline-content">
                                        <h6 class="mb-1">{{ $log->action_label }}</h6>
                                        @if($log->description)
                                            <p class="mb-1 text-muted small">{{ $log->description }}</p>
                                        @endif
                                        <small class="text-muted">
                                            {{ $log->created_at->diffForHumans() }} par {{ $log->user->name }}
                                        </small>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>

<!-- Modal : Changer le mode de gestion -->
<div class="modal fade" id="changeModeModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('admin.events.change-management-mode', $event) }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Changer le Mode de Gestion</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nouveau Mode de Gestion</label>
                        <select name="management_mode" class="form-select" required id="managementModeSelect">
                            <option value="">Sélectionner un mode...</option>
                            <option value="promoter" {{ $event->management_mode === 'promoter' ? 'selected' : '' }}>
                                Mode Promoteur - Contrôle complet au promoteur
                            </option>
                            <option value="admin" {{ $event->management_mode === 'admin' ? 'selected' : '' }}>
                                Mode Admin - Vous gérez tout
                            </option>
                            <option value="collaborative" {{ $event->management_mode === 'collaborative' ? 'selected' : '' }}>
                                Mode Collaboratif - Permissions partagées
                            </option>
                        </select>
                    </div>

                    <!-- Permissions pour mode collaboratif -->
                    <div class="mb-3" id="collaborativePermissions" style="display: none;">
                        <label class="form-label">Permissions Admin</label>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="admin_permissions[]" value="create_tickets" id="perm1">
                            <label class="form-check-label" for="perm1">Créer des billets</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="admin_permissions[]" value="edit_tickets" id="perm2">
                            <label class="form-check-label" for="perm2">Modifier les billets</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="admin_permissions[]" value="set_prices" id="perm3">
                            <label class="form-check-label" for="perm3">Définir les prix</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="admin_permissions[]" value="publish_event" id="perm4">
                            <label class="form-check-label" for="perm4">Publier l'événement</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="admin_permissions[]" value="manage_sales" id="perm5">
                            <label class="form-check-label" for="perm5">Gérer les ventes</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="admin_permissions[]" value="edit_event_details" id="perm6">
                            <label class="form-check-label" for="perm6">Modifier les détails de l'événement</label>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Raison du changement <span class="text-danger">*</span></label>
                        <textarea name="reason" class="form-control" rows="3" required 
                                  placeholder="Expliquez pourquoi vous changez le mode de gestion..."></textarea>
                        <small class="text-muted">Cette information sera visible dans l'historique</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary">Confirmer le Changement</button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.timeline {
    position: relative;
    padding-left: 2rem;
}

.timeline-item {
    position: relative;
    margin-bottom: 1.5rem;
}

.timeline-item:not(:last-child)::before {
    content: '';
    position: absolute;
    left: -1.5rem;
    top: 2rem;
    width: 2px;
    height: calc(100% + 0.5rem);
    background-color: #dee2e6;
}

.timeline-marker {
    position: absolute;
    left: -2rem;
    top: 0;
    width: 2rem;
    height: 2rem;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 0.75rem;
}

.timeline-content {
    margin-left: 0.5rem;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const modeSelect = document.getElementById('managementModeSelect');
    const collaborativePerms = document.getElementById('collaborativePermissions');
    
    modeSelect.addEventListener('change', function() {
        if (this.value === 'collaborative') {
            collaborativePerms.style.display = 'block';
        } else {
            collaborativePerms.style.display = 'none';
        }
    });
    
    // Déclencher au chargement si déjà en mode collaboratif
    if (modeSelect.value === 'collaborative') {
        collaborativePerms.style.display = 'block';
    }
});
</script>
@endsection