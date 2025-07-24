@extends('layouts.admin')

@push('styles')
<style>
    .page-header {
        background: linear-gradient(135deg, #FF6B35, #E55A2B);
        color: white;
        padding: 2rem;
        border-radius: 15px;
        margin-bottom: 2rem;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    }
    
    .btn-black {
        background-color: #000;
        border-color: #000;
        color: white;
        border-radius: 8px;
        transition: all 0.3s ease;
    }
    
    .btn-black:hover {
        background-color: #333;
        border-color: #333;
        color: white;
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.2);
    }
    
    .table-container {
        background: white;
        border-radius: 15px;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        border-left: 4px solid #FF6B35;
        overflow: hidden;
    }
    
    .table-orange thead th {
        background: linear-gradient(135deg, #FF6B35, #E55A2B);
        color: white;
        border: none;
        font-weight: 600;
        padding: 1rem;
    }
    
    .table tbody tr {
        transition: all 0.3s ease;
    }
    
    .table tbody tr:hover {
        background-color: rgba(255, 107, 53, 0.05);
        transform: translateX(5px);
    }
    
    .table td {
        padding: 1rem;
        vertical-align: middle;
        border-bottom: 1px solid #eee;
    }
    
    .user-avatar {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background: linear-gradient(135deg, #FF6B35, #E55A2B);
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-weight: bold;
        margin-right: 1rem;
    }
    
    .user-info {
        display: flex;
        align-items: center;
    }
    
    .user-name {
        font-weight: 600;
        color: #000;
        margin-bottom: 0;
    }
    
    .user-email {
        color: #666;
        font-size: 0.9rem;
        margin-bottom: 0;
    }
    
    .role-badge {
        padding: 0.5rem 1rem;
        border-radius: 20px;
        font-size: 0.875rem;
        font-weight: 600;
        text-transform: uppercase;
    }
    
    .role-admin {
        background: linear-gradient(135deg, #dc3545, #c82333);
        color: white;
    }
    
    .role-promoteur {
        background: linear-gradient(135deg, #FF6B35, #E55A2B);
        color: white;
    }
    
    .role-acheteur {
        background: linear-gradient(135deg, #28a745, #20c997);
        color: white;
    }
    
    .filter-card {
        background: white;
        border-radius: 15px;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        border-left: 4px solid #FF6B35;
        padding: 1.5rem;
        margin-bottom: 2rem;
    }
    
    .search-box, .filter-select {
        border: 2px solid #ddd;
        border-radius: 8px;
        padding: 0.75rem;
        transition: all 0.3s ease;
    }
    
    .search-box:focus, .filter-select:focus {
        border-color: #FF6B35;
        outline: none;
        box-shadow: 0 0 0 0.2rem rgba(255, 107, 53, 0.25);
    }
    
    .stats-row {
        margin-bottom: 2rem;
    }
    
    .stat-card {
        background: white;
        border-radius: 15px;
        padding: 1.5rem;
        text-align: center;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        transition: transform 0.3s ease;
    }
    
    .stat-card:hover {
        transform: translateY(-5px);
    }
    
    .stat-card.admin {
        border-left: 4px solid #dc3545;
    }
    
    .stat-card.promoteur {
        border-left: 4px solid #FF6B35;
    }
    
    .stat-card.acheteur {
        border-left: 4px solid #28a745;
    }
    
    .stat-card.total {
        border-left: 4px solid #007bff;
    }
    
    .status-active {
        color: #28a745;
        font-weight: 600;
    }
    
    .status-inactive {
        color: #dc3545;
        font-weight: 600;
    }
    
    .join-date {
        color: #666;
        font-size: 0.9rem;
    }
</style>
@endpush

@section('content')
<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h1 class="mb-2">
                <i class="fas fa-users me-3"></i>
                Gestion des Utilisateurs
            </h1>
            <p class="mb-0 opacity-75">Administration de tous les utilisateurs de la plateforme</p>
        </div>
        <div>
            <a href="#" class="btn btn-black btn-lg">
                <i class="fas fa-user-plus me-2"></i>Nouvel Utilisateur
            </a>
        </div>
    </div>
</div>

<!-- Statistiques -->
<div class="row stats-row">
    <div class="col-md-3">
        <div class="stat-card total">
            <i class="fas fa-users fa-2x text-primary mb-3"></i>
            <h3 class="text-primary">{{ $totalUsers ?? 0 }}</h3>
            <p class="text-muted mb-0">Total Utilisateurs</p>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card admin">
            <i class="fas fa-user-shield fa-2x text-danger mb-3"></i>
            <h3 class="text-danger">{{ $adminUsers ?? 0 }}</h3>
            <p class="text-muted mb-0">Administrateurs</p>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card promoteur">
            <i class="fas fa-user-tie fa-2x" style="color: #FF6B35;" ></i>
            <h3 style="color: #FF6B35;">{{ $promoteurUsers ?? 0 }}</h3>
            <p class="text-muted mb-0">Promoteurs</p>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card acheteur">
            <i class="fas fa-user fa-2x text-success mb-3"></i>
            <h3 class="text-success">{{ $acheteurUsers ?? 0 }}</h3>
            <p class="text-muted mb-0">Acheteurs</p>
        </div>
    </div>
</div>

<!-- Filtres -->
<div class="filter-card">
    <div class="row">
        <div class="col-md-4">
            <label class="form-label fw-bold">Rechercher</label>
            <input type="text" class="form-control search-box" placeholder="Nom, email...">
        </div>
        <div class="col-md-3">
            <label class="form-label fw-bold">Rôle</label>
            <select class="form-select filter-select">
                <option value="">Tous les rôles</option>
                <option value="admin">Administrateur</option>
                <option value="promoteur">Promoteur</option>
                <option value="acheteur">Acheteur</option>
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label fw-bold">Statut</label>
            <select class="form-select filter-select">
                <option value="">Tous les statuts</option>
                <option value="active">Actif</option>
                <option value="inactive">Inactif</option>
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label fw-bold">&nbsp;</label>
            <button class="btn btn-black w-100">
                <i class="fas fa-filter me-2"></i>Filtrer
            </button>
        </div>
    </div>
</div>

<!-- Tableau des utilisateurs -->
<div class="table-container">
    <table class="table table-orange table-hover mb-0">
        <thead>
            <tr>
                <th><i class="fas fa-user me-2"></i>Utilisateur</th>
                <th><i class="fas fa-envelope me-2"></i>Email</th>
                <th><i class="fas fa-user-tag me-2"></i>Rôle</th>
                <th><i class="fas fa-phone me-2"></i>Téléphone</th>
                <th><i class="fas fa-calendar-plus me-2"></i>Inscrit le</th>
                <th><i class="fas fa-circle me-2"></i>Statut</th>
                <th><i class="fas fa-cogs me-2"></i>Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($users as $user)
            <tr>
                <td>
                    <div class="user-info">
                        <div class="user-avatar">
                            {{ strtoupper(substr($user->name, 0, 1)) }}
                        </div>
                        <div>
                            <div class="user-name">{{ $user->name }}</div>
                            @if($user->email_verified_at)
                                <small class="text-success">
                                    <i class="fas fa-check-circle me-1"></i>Vérifié
                                </small>
                            @else
                                <small class="text-warning">
                                    <i class="fas fa-exclamation-circle me-1"></i>Non vérifié
                                </small>
                            @endif
                        </div>
                    </div>
                </td>
                <td>
                    <div class="user-email">{{ $user->email }}</div>
                </td>
                <td>
                    @switch($user->role)
                        @case('admin')
                            <span class="role-badge role-admin">
                                <i class="fas fa-shield-alt me-1"></i>Admin
                            </span>
                            @break
                        @case('promoteur')
                            <span class="role-badge role-promoteur">
                                <i class="fas fa-user-tie me-1"></i>Promoteur
                            </span>
                            @break
                        @case('acheteur')
                            <span class="role-badge role-acheteur">
                                <i class="fas fa-user me-1"></i>Acheteur
                            </span>
                            @break
                        @default
                            <span class="role-badge" style="background: #6c757d;">
                                <i class="fas fa-question me-1"></i>{{ ucfirst($user->role) }}
                            </span>
                    @endswitch
                </td>
                <td>
                    @if($user->phone)
                        <i class="fas fa-phone me-2 text-muted"></i>{{ $user->phone }}
                    @else
                        <span class="text-muted">Non renseigné</span>
                    @endif
                </td>
                <td>
                    <div class="fw-bold">{{ $user->created_at->format('d/m/Y') }}</div>
                    <div class="join-date">{{ $user->created_at->diffForHumans() }}</div>
                </td>
                <td>
                    @if($user->email_verified_at && !$user->deleted_at)
                        <span class="status-active">
                            <i class="fas fa-circle me-1"></i>Actif
                        </span>
                    @else
                        <span class="status-inactive">
                            <i class="fas fa-circle me-1"></i>Inactif
                        </span>
                    @endif
                </td>
                <td>
                    <div class="btn-group" role="group">
                        <a href="#" class="btn btn-sm btn-black" title="Voir le profil">
                            <i class="fas fa-eye"></i>
                        </a>
                        <button class="btn btn-sm btn-outline-dark" title="Modifier">
                            <i class="fas fa-edit"></i>
                        </button>
                        @if($user->role !== 'admin')
                        <button class="btn btn-sm btn-outline-warning" title="Changer le rôle">
                            <i class="fas fa-user-tag"></i>
                        </button>
                        @endif
                        @if($user->id !== auth()->id())
                        <button class="btn btn-sm btn-outline-danger" title="Désactiver">
                            <i class="fas fa-ban"></i>
                        </button>
                        @endif
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="7" class="text-center py-5">
                    <i class="fas fa-users fa-4x text-muted mb-3"></i>
                    <h5>Aucun utilisateur trouvé</h5>
                    <p class="text-muted">Il n'y a pas d'utilisateurs correspondant aux critères.</p>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

@if(isset($users) && $users->hasPages())
<div class="d-flex justify-content-center mt-4">
    {{ $users->links('pagination::bootstrap-4') }}
</div>
@endif
@endsection

@push('scripts')
<script>
    // Recherche en temps réel
    document.querySelector('.search-box').addEventListener('input', function(e) {
        const searchTerm = e.target.value.toLowerCase();
        const rows = document.querySelectorAll('tbody tr');
        
        rows.forEach(row => {
            const name = row.querySelector('.user-name')?.textContent.toLowerCase() || '';
            const email = row.querySelector('.user-email')?.textContent.toLowerCase() || '';
            
            if (name.includes(searchTerm) || email.includes(searchTerm)) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    });
    
    // Confirmation de désactivation
    document.querySelectorAll('.btn-outline-danger').forEach(btn => {
        btn.addEventListener('click', function(e) {
            if (!confirm('Êtes-vous sûr de vouloir désactiver cet utilisateur ?')) {
                e.preventDefault();
            }
        });
    });
</script>
@endpush