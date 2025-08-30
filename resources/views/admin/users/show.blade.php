{{-- resources/views/admin/users/show.blade.php --}}
@extends('layouts.admin')

@section('title', 'Utilisateur : ' . $user->name . ' - ClicBillet CI')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.users.index') }}">Utilisateurs</a></li>
    <li class="breadcrumb-item active">{{ $user->name }}</li>
@endsection

@section('content')
    <!-- Header avec informations utilisateur -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom-0 pb-0">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <div class="d-flex align-items-center">
                                <div class="user-avatar-lg me-3">
                                    {{ substr($user->name, 0, 1) }}
                                </div>
                                <div>
                                    <h1 class="h3 mb-1">{{ $user->name }}</h1>
                                    <div class="d-flex align-items-center gap-3">
                                        <span class="badge badge-{{ $user->role }}">
                                            @if($user->isAdmin())
                                                <i class="fas fa-crown me-1"></i>Administrateur
                                            @elseif($user->isPromoteur())
                                                <i class="fas fa-microphone me-1"></i>Promoteur
                                            @else
                                                <i class="fas fa-user me-1"></i>Acheteur
                                            @endif
                                        </span>
                                        
                                        @if($user->email_verified_at)
                                            <span class="badge bg-success">
                                                <i class="fas fa-check-circle me-1"></i>Vérifié
                                            </span>
                                        @else
                                            <span class="badge bg-warning text-dark">
                                                <i class="fas fa-exclamation-triangle me-1"></i>Non vérifié
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 text-md-end">
                            <div class="btn-group">
                                @if($user->id !== auth()->id())
                                    <a href="{{ route('admin.users.edit', $user) }}" 
                                       class="btn btn-orange">
                                        <i class="fas fa-edit me-2"></i>Modifier
                                    </a>
                                    <button class="btn btn-outline-danger" 
                                            onclick="confirmDelete()">
                                        <i class="fas fa-trash me-2"></i>Supprimer
                                    </button>
                                @else
                                    <span class="text-muted">
                                        <i class="fas fa-info-circle me-1"></i>
                                        Votre compte
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="card-body pt-3">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="info-group">
                                <label class="info-label">Email</label>
                                <div class="info-value">
                                    <i class="fas fa-envelope text-muted me-2"></i>
                                    {{ $user->email }}
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-group">
                                <label class="info-label">Téléphone</label>
                                <div class="info-value">
                                    <i class="fas fa-phone text-muted me-2"></i>
                                    {{ $user->phone ?: 'Non renseigné' }}
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-group">
                                <label class="info-label">Membre depuis</label>
                                <div class="info-value">
                                    <i class="fas fa-calendar text-muted me-2"></i>
                                    {{ $user->created_at->format('d F Y') }}
                                    <small class="text-muted">({{ $user->created_at->diffForHumans() }})</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-group">
                                <label class="info-label">Dernière connexion</label>
                                <div class="info-value">
                                    <i class="fas fa-clock text-muted me-2"></i>
                                    {{ $user->updated_at->diffForHumans() }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistiques -->
    <div class="row mb-4">
        @if($user->isPromoteur())
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card stat-card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="stat-icon bg-primary">
                            <i class="fas fa-calendar-alt"></i>
                        </div>
                        <div class="stat-content">
                            <h3 class="stat-number">{{ $stats['events_count'] ?? 0 }}</h3>
                            <p class="stat-label">Événements créés</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card stat-card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="stat-icon bg-success">
                            <i class="fas fa-ticket-alt"></i>
                        </div>
                        <div class="stat-content">
                            <h3 class="stat-number">{{ $stats['tickets_sold'] ?? 0 }}</h3>
                            <p class="stat-label">Billets vendus</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card stat-card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="stat-icon bg-warning">
                            <i class="fas fa-money-bill-wave"></i>
                        </div>
                        <div class="stat-content">
                            <h3 class="stat-number">{{ number_format($stats['total_revenue'] ?? 0) }}</h3>
                            <p class="stat-label">Revenus (FCFA)</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card stat-card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="stat-icon bg-info">
                            <i class="fas fa-hourglass-half"></i>
                        </div>
                        <div class="stat-content">
                            <h3 class="stat-number">{{ number_format($stats['pending_revenue'] ?? 0) }}</h3>
                            <p class="stat-label">Revenus en attente</p>
                        </div>
                    </div>
                </div>
            </div>
        @else
            <div class="col-xl-4 col-md-6 mb-4">
                <div class="card stat-card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="stat-icon bg-primary">
                            <i class="fas fa-shopping-cart"></i>
                        </div>
                        <div class="stat-content">
                            <h3 class="stat-number">{{ $stats['orders_count'] ?? 0 }}</h3>
                            <p class="stat-label">Commandes passées</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-xl-4 col-md-6 mb-4">
                <div class="card stat-card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="stat-icon bg-success">
                            <i class="fas fa-coins"></i>
                        </div>
                        <div class="stat-content">
                            <h3 class="stat-number">{{ number_format($stats['total_spent'] ?? 0) }}</h3>
                            <p class="stat-label">Total dépensé (FCFA)</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-xl-4 col-md-6 mb-4">
                <div class="card stat-card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="stat-icon bg-info">
                            <i class="fas fa-calendar-check"></i>
                        </div>
                        <div class="stat-content">
                            <h3 class="stat-number">{{ $stats['member_since'] ?? 'N/A' }}</h3>
                            <p class="stat-label">Membre depuis</p>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>

    <div class="row">
        <!-- Activité récente -->
        <div class="col-lg-8 mb-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0">
                        <i class="fas fa-history me-2 text-primary"></i>
                        Activité récente
                    </h5>
                </div>
                <div class="card-body">
                    @if($recentActivity && $recentActivity->count() > 0)
                        <div class="timeline">
                            @foreach($recentActivity as $activity)
                                <div class="timeline-item">
                                    <div class="timeline-marker bg-{{ $activity['color'] }}">
                                        <i class="{{ $activity['icon'] }}"></i>
                                    </div>
                                    <div class="timeline-content">
                                        <h6 class="mb-1">{{ $activity['message'] }}</h6>
                                        <small class="text-muted">
                                            {{ $activity['date']->format('d/m/Y à H:i') }}
                                        </small>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-4">
                            <i class="fas fa-clock fa-3x text-muted mb-3"></i>
                            <p class="text-muted mb-0">Aucune activité récente</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Informations système -->
        <div class="col-lg-4 mb-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0">
                        <i class="fas fa-cog me-2 text-secondary"></i>
                        Informations système
                    </h5>
                </div>
                <div class="card-body">
                    <div class="info-list">
                        <div class="info-item">
                            <span class="info-label">ID utilisateur</span>
                            <span class="info-value">#{{ $user->id }}</span>
                        </div>
                        
                        <div class="info-item">
                            <span class="info-label">Date de création</span>
                            <span class="info-value">{{ $user->created_at->format('d/m/Y H:i') }}</span>
                        </div>
                        
                        <div class="info-item">
                            <span class="info-label">Dernière modification</span>
                            <span class="info-value">{{ $user->updated_at->format('d/m/Y H:i') }}</span>
                        </div>
                        
                        <div class="info-item">
                            <span class="info-label">Email vérifié</span>
                            <span class="info-value">
                                @if($user->email_verified_at)
                                    <span class="text-success">
                                        <i class="fas fa-check me-1"></i>
                                        {{ $user->email_verified_at->format('d/m/Y') }}
                                    </span>
                                @else
                                    <span class="text-warning">
                                        <i class="fas fa-times me-1"></i>
                                        Non vérifié
                                    </span>
                                @endif
                            </span>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Actions rapides -->
            <div class="card border-0 shadow-sm mt-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0">
                        <i class="fas fa-bolt me-2 text-warning"></i>
                        Actions rapides
                    </h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        @if(!$user->email_verified_at)
                            <button class="btn btn-outline-success btn-sm" onclick="verifyEmail()">
                                <i class="fas fa-check me-2"></i>Vérifier l'email
                            </button>
                        @endif
                        
                        @if($user->id !== auth()->id())
                            <button class="btn btn-outline-warning btn-sm" onclick="resetPassword()">
                                <i class="fas fa-key me-2"></i>Réinitialiser mot de passe
                            </button>
                            
                            <button class="btn btn-outline-danger btn-sm" onclick="suspendAccount()">
                                <i class="fas fa-ban me-2"></i>Suspendre le compte
                            </button>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('styles')
<style>
/* Avatar utilisateur */
.user-avatar-lg {
    width: 80px;
    height: 80px;
    background: linear-gradient(135deg, #FF6B35, #ff8c61);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 2rem;
    font-weight: 700;
}

/* Badges personnalisés */
.badge-admin {
    background: linear-gradient(135deg, #dc3545, #c82333);
    color: white;
}

.badge-promoteur {
    background: linear-gradient(135deg, #FF6B35, #ff8c61);
    color: white;
}

.badge-acheteur {
    background: linear-gradient(135deg, #28a745, #20c997);
    color: white;
}

/* Groupes d'information */
.info-group {
    margin-bottom: 1.5rem;
}

.info-label {
    display: block;
    font-size: 0.875rem;
    font-weight: 600;
    color: #6c757d;
    margin-bottom: 0.5rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.info-value {
    display: flex;
    align-items: center;
    font-size: 1rem;
    color: #495057;
}

/* Cartes de statistiques */
.stat-card {
    transition: all 0.3s ease;
    cursor: pointer;
}

.stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
}

.stat-icon {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.5rem;
    float: left;
    margin-right: 1rem;
}

.stat-content {
    overflow: hidden;
}

.stat-number {
    font-size: 2rem;
    font-weight: 700;
    color: #495057;
    margin: 0;
    line-height: 1;
}

.stat-label {
    font-size: 0.875rem;
    color: #6c757d;
    margin: 0;
    margin-top: 0.25rem;
}

/* Timeline */
.timeline {
    position: relative;
    padding-left: 30px;
}

.timeline::before {
    content: '';
    position: absolute;
    left: 15px;
    top: 0;
    bottom: 0;
    width: 2px;
    background: #e9ecef;
}

.timeline-item {
    position: relative;
    margin-bottom: 2rem;
}

.timeline-marker {
    position: absolute;
    left: -22px;
    top: 0;
    width: 30px;
    height: 30px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 0.875rem;
}

.timeline-content h6 {
    color: #495057;
    margin-bottom: 0.25rem;
}

/* Liste d'informations */
.info-list {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.info-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.75rem 0;
    border-bottom: 1px solid #f8f9fa;
}

.info-item:last-child {
    border-bottom: none;
}

.info-item .info-label {
    font-size: 0.875rem;
    font-weight: 500;
    color: #6c757d;
    margin: 0;
}

.info-item .info-value {
    font-size: 0.875rem;
    font-weight: 600;
    color: #495057;
}

/* Responsive */
@media (max-width: 768px) {
    .user-avatar-lg {
        width: 60px;
        height: 60px;
        font-size: 1.5rem;
    }
    
    .stat-number {
        font-size: 1.5rem;
    }
    
    .info-item {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.25rem;
    }
}
</style>
@endpush

@push('scripts')
<script>
// Fonction de confirmation de suppression
function confirmDelete() {
    if (confirm('Êtes-vous sûr de vouloir supprimer cet utilisateur ? Cette action est irréversible.')) {
        // Ici vous pouvez ajouter une requête AJAX ou un form de suppression
        window.location.href = "{{ route('admin.users.destroy', $user) }}";
    }
}

// Vérifier l'email
function verifyEmail() {
    if (confirm('Marquer cet email comme vérifié ?')) {
        // Requête AJAX pour vérifier l'email
        fetch(`/admin/users/{{ $user->id }}/verify-email`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Content-Type': 'application/json',
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Erreur lors de la vérification');
            }
        });
    }
}

// Réinitialiser mot de passe
function resetPassword() {
    if (confirm('Envoyer un email de réinitialisation de mot de passe à cet utilisateur ?')) {
        // Requête AJAX pour reset password
        fetch(`/admin/users/{{ $user->id }}/reset-password`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Content-Type': 'application/json',
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Email de réinitialisation envoyé avec succès');
            } else {
                alert('Erreur lors de l\'envoi');
            }
        });
    }
}

// Suspendre le compte
function suspendAccount() {
    if (confirm('Suspendre ce compte utilisateur ? L\'utilisateur ne pourra plus se connecter.')) {
        // Requête AJAX pour suspendre
        console.log('Fonctionnalité à implémenter');
    }
}
</script>
@endpush