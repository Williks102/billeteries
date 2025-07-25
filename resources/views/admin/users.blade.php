@extends('layouts.app')

@section('title', 'Gestion des utilisateurs - Admin')
@section('body-class', 'admin-page')

@section('content')
<div class="container-fluid py-4">
    <div class="row">
        <!-- Sidebar Admin -->
        <div class="col-md-3 col-lg-2">
            <div class="admin-sidebar bg-dark text-white rounded p-3 sticky-top">
                <h5 class="mb-4">
                    <i class="fas fa-shield-alt text-orange me-2"></i>
                    Administration
                </h5>
                
                <nav class="nav flex-column">
                    <a class="nav-link text-light" href="{{ route('admin.dashboard') }}">
                        <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                    </a>
                    <a class="nav-link active text-white" href="{{ route('admin.users') }}">
                        <i class="fas fa-users me-2"></i>Utilisateurs
                    </a>
                    <a class="nav-link text-light" href="{{ route('admin.events') }}">
                        <i class="fas fa-calendar me-2"></i>Événements
                    </a>
                    <a class="nav-link text-light" href="{{ route('admin.orders') }}">
                        <i class="fas fa-shopping-cart me-2"></i>Commandes
                    </a>
                    <a class="nav-link text-light" href="{{ route('admin.commissions') }}">
                        <i class="fas fa-coins me-2"></i>Commissions
                    </a>
                    
                    <hr class="my-3" style="border-color: #444;">
                    
                    <a class="nav-link text-light" href="{{ route('home') }}">
                        <i class="fas fa-eye me-2"></i>Voir le site
                    </a>
                </nav>
            </div>
        </div>
        
        <!-- Contenu principal -->
        <div class="col-md-9 col-lg-10">
            <!-- En-tête avec filtres -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="mb-1">Gestion des utilisateurs</h2>
                    <p class="text-muted mb-0">Gérez tous les utilisateurs de la plateforme</p>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <div class="dropdown">
                        <button class="btn btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                            <i class="fas fa-filter me-2"></i>Filtrer par rôle
                        </button>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="{{ route('admin.users') }}">Tous les rôles</a></li>
                            <li><a class="dropdown-item" href="{{ route('admin.users', ['role' => 'admin']) }}">Administrateurs</a></li>
                            <li><a class="dropdown-item" href="{{ route('admin.users', ['role' => 'promoteur']) }}">Promoteurs</a></li>
                            <li><a class="dropdown-item" href="{{ route('admin.users', ['role' => 'acheteur']) }}">Acheteurs</a></li>
                        </ul>
                    </div>
                    <button class="btn btn-orange" data-bs-toggle="modal" data-bs-target="#createUserModal">
                        <i class="fas fa-plus me-2"></i>Nouvel utilisateur
                    </button>
                </div>
            </div>

            <!-- Statistiques rapides -->
            <div class="row mb-4">
                <div class="col-md-3 mb-3">
                    <div class="stat-card">
                        <div class="d-flex align-items-center">
                            <div class="stat-icon bg-primary">
                                <i class="fas fa-users"></i>
                            </div>
                            <div class="stat-info ms-3">
                                <h4 class="mb-0">{{ $users->total() }}</h4>
                                <p class="text-muted mb-0">Total utilisateurs</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3 mb-3">
                    <div class="stat-card">
                        <div class="d-flex align-items-center">
                            <div class="stat-icon bg-danger">
                                <i class="fas fa-shield-alt"></i>
                            </div>
                            <div class="stat-info ms-3">
                                <h4 class="mb-0">{{ $users->where('role', 'admin')->count() }}</h4>
                                <p class="text-muted mb-0">Administrateurs</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3 mb-3">
                    <div class="stat-card">
                        <div class="d-flex align-items-center">
                            <div class="stat-icon bg-warning">
                                <i class="fas fa-bullhorn"></i>
                            </div>
                            <div class="stat-info ms-3">
                                <h4 class="mb-0">{{ $users->where('role', 'promoteur')->count() }}</h4>
                                <p class="text-muted mb-0">Promoteurs</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3 mb-3">
                    <div class="stat-card">
                        <div class="d-flex align-items-center">
                            <div class="stat-icon bg-success">
                                <i class="fas fa-shopping-cart"></i>
                            </div>
                            <div class="stat-info ms-3">
                                <h4 class="mb-0">{{ $users->where('role', 'acheteur')->count() }}</h4>
                                <p class="text-muted mb-0">Acheteurs</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Barre de recherche -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body">
                    <form method="GET" action="{{ route('admin.users') }}" class="row align-items-end">
                        <div class="col-md-4">
                            <label class="form-label">Rechercher</label>
                            <input type="text" name="search" class="form-control" 
                                   placeholder="Nom, email..." value="{{ request('search') }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Rôle</label>
                            <select name="role" class="form-select">
                                <option value="">Tous les rôles</option>
                                <option value="admin" {{ request('role') == 'admin' ? 'selected' : '' }}>Administrateur</option>
                                <option value="promoteur" {{ request('role') == 'promoteur' ? 'selected' : '' }}>Promoteur</option>
                                <option value="acheteur" {{ request('role') == 'acheteur' ? 'selected' : '' }}>Acheteur</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Statut</label>
                            <select name="status" class="form-select">
                                <option value="">Tous</option>
                                <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Actifs</option>
                                <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Inactifs</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Table des utilisateurs -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="fas fa-users text-orange me-2"></i>
                            Liste des utilisateurs
                        </h5>
                        <div class="dropdown">
                            <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                <i class="fas fa-download me-1"></i>Exporter
                            </button>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="#"><i class="fas fa-file-excel me-2"></i>Excel</a></li>
                                <li><a class="dropdown-item" href="#"><i class="fas fa-file-csv me-2"></i>CSV</a></li>
                                <li><a class="dropdown-item" href="#"><i class="fas fa-file-pdf me-2"></i>PDF</a></li>
                            </ul>
                        </div>
                    </div>
                </div>
                <div class="card-body p-0">
                    @if($users->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Utilisateur</th>
                                        <th>Email</th>
                                        <th>Téléphone</th>
                                        <th>Rôle</th>
                                        <th>Inscription</th>
                                        <th>Statut</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($users as $user)
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="user-avatar me-3">
                                                        @if($user->isAdmin())
                                                            <i class="fas fa-shield-alt text-danger"></i>
                                                        @elseif($user->isPromoteur())
                                                            <i class="fas fa-bullhorn text-warning"></i>
                                                        @else
                                                            <i class="fas fa-user text-primary"></i>
                                                        @endif
                                                    </div>
                                                    <div>
                                                        <h6 class="mb-0">{{ $user->name }}</h6>
                                                        <small class="text-muted">ID: {{ $user->id }}</small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>{{ $user->email }}</td>
                                            <td>{{ $user->phone ?? '-' }}</td>
                                            <td>
                                                <span class="badge bg-{{ $user->isAdmin() ? 'danger' : ($user->isPromoteur() ? 'warning' : 'success') }}">
                                                    {{ ucfirst($user->role) }}
                                                </span>
                                            </td>
                                            <td>
                                                <small>{{ $user->created_at->format('d/m/Y') }}</small><br>
                                                <small class="text-muted">{{ $user->created_at->diffForHumans() }}</small>
                                            </td>
                                            <td>
                                                <span class="badge bg-{{ $user->updated_at > now()->subDays(30) ? 'success' : 'secondary' }}">
                                                    {{ $user->updated_at > now()->subDays(30) ? 'Actif' : 'Inactif' }}
                                                </span>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <button class="btn btn-outline-primary" 
                                                            data-bs-toggle="modal" 
                                                            data-bs-target="#viewUserModal{{ $user->id }}">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                    <button class="btn btn-outline-warning" 
                                                            data-bs-toggle="modal" 
                                                            data-bs-target="#editUserModal{{ $user->id }}">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    @if(!$user->isAdmin() || auth()->user()->id != $user->id)
                                                        <button class="btn btn-outline-danger" 
                                                                onclick="deleteUser({{ $user->id }})">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- Pagination -->
                        <div class="card-footer bg-white">
                            {{ $users->links() }}
                        </div>
                    @else
                        <div class="text-center py-5">
                            <i class="fas fa-users fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">Aucun utilisateur trouvé</h5>
                            <p class="text-muted">Modifiez vos critères de recherche ou créez un nouvel utilisateur</p>
                            <button class="btn btn-orange" data-bs-toggle="modal" data-bs-target="#createUserModal">
                                <i class="fas fa-plus me-2"></i>Créer un utilisateur
                            </button>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Créer utilisateur -->
<div class="modal fade" id="createUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Créer un nouvel utilisateur</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="{{ route('admin.users.store') }}">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nom complet</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Téléphone</label>
                        <input type="tel" name="phone" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Rôle</label>
                        <select name="role" class="form-select" required>
                            <option value="acheteur">Acheteur</option>
                            <option value="promoteur">Promoteur</option>
                            <option value="admin">Administrateur</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Mot de passe</label>
                        <input type="password" name="password" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Confirmer le mot de passe</label>
                        <input type="password" name="password_confirmation" class="form-control" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-orange">Créer l'utilisateur</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('styles')
<style>
.admin-page {
    background-color: #f8f9fa;
}

.admin-sidebar {
    background: linear-gradient(135deg, #2c3e50, #34495e) !important;
    border-radius: 12px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.admin-sidebar .nav-link {
    color: rgba(255,255,255,0.8) !important;
    padding: 12px 16px;
    border-radius: 8px;
    margin-bottom: 5px;
    transition: all 0.3s ease;
}

.admin-sidebar .nav-link:hover,
.admin-sidebar .nav-link.active {
    background: var(--primary-orange) !important;
    color: white !important;
}

.stat-card {
    background: white;
    border-radius: 12px;
    padding: 1.5rem;
    box-shadow: 0 2px 10px rgba(0,0,0,0.08);
    border: none;
    height: 100%;
    transition: all 0.3s ease;
}

.stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 20px rgba(0,0,0,0.12);
}

.stat-icon {
    width: 50px;
    height: 50px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.25rem;
    color: white;
}

.stat-info h4 {
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--dark-blue);
}

.user-avatar {
    width: 35px;
    height: 35px;
    background: rgba(255, 107, 53, 0.1);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
}

.card {
    border: none;
    border-radius: 12px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.08);
}

.card-header {
    border-radius: 12px 12px 0 0 !important;
    border-bottom: 1px solid #e9ecef;
}

.table th {
    border-top: none;
    font-weight: 600;
    color: #495057;
}

.badge {
    font-size: 0.75rem;
    padding: 0.4em 0.8em;
}

@media (max-width: 768px) {
    .admin-sidebar {
        margin-bottom: 2rem;
    }
    
    .stat-card {
        margin-bottom: 1rem;
    }
}
</style>
@endpush

@push('scripts')
<script>
function deleteUser(userId) {
    if (confirm('Êtes-vous sûr de vouloir supprimer cet utilisateur ?')) {
        fetch(`/admin/users/${userId}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Erreur lors de la suppression');
            }
        });
    }
}
</script>
@endpush
@endsection