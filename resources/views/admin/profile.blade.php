{{-- resources/views/admin/profile.blade.php --}}
@extends('layouts.admin')

@section('title', 'Mon profil administrateur - ClicBillet CI')

@section('breadcrumb')
    <li class="breadcrumb-item active">Mon profil</li>
@endsection

@section('content')
    <!-- Header avec actions -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-1">Mon profil administrateur</h2>
            <p class="text-muted mb-0">Gérez vos informations personnelles et paramètres de compte</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.settings') }}" class="btn btn-outline-orange">
                <i class="fas fa-cog me-2"></i>Paramètres généraux
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row">
        <!-- Profil utilisateur -->
        <div class="col-lg-4">
            <!-- Informations personnelles -->
            <div class="card mb-4">
                <div class="card-header bg-white border-bottom">
                    <h5 class="mb-0">
                        <i class="fas fa-user-shield text-orange me-2"></i>
                        Profil administrateur
                    </h5>
                </div>
                <div class="card-body text-center">
                    <div class="user-avatar-xl mx-auto mb-3">
                        {{ substr($user->name, 0, 2) }}
                    </div>
                    <h4 class="fw-bold mb-2">{{ $user->name }}</h4>
                    <span class="badge bg-danger fs-6 mb-3">
                        <i class="fas fa-shield-alt me-1"></i>Administrateur
                    </span>
                    
                    <div class="text-start mt-4">
                        <div class="info-item">
                            <i class="fas fa-envelope text-muted me-3"></i>
                            <span>{{ $user->email }}</span>
                        </div>
                        
                        @if($user->phone)
                            <div class="info-item">
                                <i class="fas fa-phone text-muted me-3"></i>
                                <span>{{ $user->phone }}</span>
                            </div>
                        @endif
                        
                        <div class="info-item">
                            <i class="fas fa-calendar text-muted me-3"></i>
                            <span>Administrateur depuis le {{ $user->created_at->format('d/m/Y') }}</span>
                        </div>
                        
                        <div class="info-item">
                            <i class="fas fa-clock text-muted me-3"></i>
                            <span>Dernière activité : {{ $user->updated_at->format('d/m/Y H:i') }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Statistiques rapides -->
            <div class="card">
                <div class="card-header bg-white border-bottom">
                    <h5 class="mb-0">
                        <i class="fas fa-chart-line text-orange me-2"></i>
                        Statistiques du compte
                    </h5>
                </div>
                <div class="card-body">
                    <div class="stat-row">
                        <span class="stat-label">Compte créé</span>
                        <span class="stat-value">{{ $stats['account_created'] ?? 'N/A' }}</span>
                    </div>
                    <div class="stat-row">
                        <span class="stat-label">Dernière activité</span>
                        <span class="stat-value">{{ $stats['last_activity'] ?? 'N/A' }}</span>
                    </div>
                    <div class="stat-row">
                        <span class="stat-label">Total connexions</span>
                        <span class="stat-value">{{ $stats['total_logins'] ?? 'N/A' }}</span>
                    </div>
                    <div class="stat-row">
                        <span class="stat-label">Actions effectuées</span>
                        <span class="stat-value">{{ $stats['total_actions'] ?? 'N/A' }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Formulaire de modification -->
        <div class="col-lg-8">
            <!-- Modification du profil -->
            <div class="card mb-4">
                <div class="card-header bg-white border-bottom">
                    <h5 class="mb-0">
                        <i class="fas fa-edit text-orange me-2"></i>
                        Modifier mes informations
                    </h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.profile.update') }}" method="POST">
                        @csrf
                        @method('PATCH')
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="name" class="form-label fw-semibold">Nom complet</label>
                                <input type="text" class="form-control @error('name') is-invalid @enderror" 
                                       id="name" name="name" value="{{ old('name', $user->name) }}" required>
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="email" class="form-label fw-semibold">Adresse email</label>
                                <input type="email" class="form-control @error('email') is-invalid @enderror" 
                                       id="email" name="email" value="{{ old('email', $user->email) }}" required>
                                @error('email')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="phone" class="form-label fw-semibold">Téléphone</label>
                            <input type="text" class="form-control @error('phone') is-invalid @enderror" 
                                   id="phone" name="phone" value="{{ old('phone', $user->phone) }}">
                            @error('phone')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <hr class="my-4">
                        
                        <h6 class="fw-bold mb-3">Changer le mot de passe</h6>
                        <p class="text-muted small mb-3">Laissez vide si vous ne souhaitez pas changer votre mot de passe</p>
                        
                        <div class="mb-3">
                            <label for="current_password" class="form-label fw-semibold">Mot de passe actuel</label>
                            <input type="password" class="form-control @error('current_password') is-invalid @enderror" 
                                   id="current_password" name="current_password">
                            @error('current_password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="password" class="form-label fw-semibold">Nouveau mot de passe</label>
                                <input type="password" class="form-control @error('password') is-invalid @enderror" 
                                       id="password" name="password">
                                @error('password')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="password_confirmation" class="form-label fw-semibold">Confirmer le mot de passe</label>
                                <input type="password" class="form-control" 
                                       id="password_confirmation" name="password_confirmation">
                            </div>
                        </div>
                        
                        <div class="d-flex gap-2 mt-4">
                            <button type="submit" class="btn btn-orange">
                                <i class="fas fa-save me-2"></i>Enregistrer les modifications
                            </button>
                            <button type="reset" class="btn btn-outline-secondary">
                                <i class="fas fa-undo me-2"></i>Annuler
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Activité récente -->
            @if(isset($recentActivity) && count($recentActivity) > 0)
                <div class="card">
                    <div class="card-header bg-white border-bottom">
                        <h5 class="mb-0">
                            <i class="fas fa-history text-orange me-2"></i>
                            Activité récente
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="timeline-simple">
                            @foreach($recentActivity as $activity)
                                <div class="timeline-item-simple">
                                    <div class="timeline-marker-simple"></div>
                                    <div class="timeline-content-simple">
                                        <div class="fw-semibold">{{ $activity['action'] }}</div>
                                        <small class="text-muted">{{ $activity['date']->format('d/m/Y H:i') }}</small>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        
                        <div class="text-center mt-3">
                            <a href="#" class="btn btn-outline-orange btn-sm">
                                Voir toute l'activité
                            </a>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
@endsection

@push('styles')
<style>
    .user-avatar-xl {
        width: 100px;
        height: 100px;
        border-radius: 50%;
        background: linear-gradient(135deg, #dc3545, #c82333);
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-weight: bold;
        font-size: 2.2rem;
        box-shadow: 0 4px 12px rgba(220, 53, 69, 0.3);
    }
    
    .info-item {
        display: flex;
        align-items: center;
        padding: 0.75rem 0;
        border-bottom: 1px solid #f0f0f0;
        font-size: 0.9rem;
    }
    
    .info-item:last-child {
        border-bottom: none;
    }
    
    .stat-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 0.75rem 0;
        border-bottom: 1px solid #f0f0f0;
    }
    
    .stat-row:last-child {
        border-bottom: none;
    }
    
    .stat-label {
        color: #6c757d;
        font-weight: 500;
        font-size: 0.9rem;
    }
    
    .stat-value {
        font-weight: 600;
        color: #2d3748;
        font-size: 0.9rem;
    }
    
    .timeline-simple {
        position: relative;
        padding-left: 30px;
    }
    
    .timeline-simple:before {
        content: '';
        position: absolute;
        left: 15px;
        top: 0;
        bottom: 0;
        width: 2px;
        background: #e9ecef;
    }
    
    .timeline-item-simple {
        position: relative;
        margin-bottom: 20px;
    }
    
    .timeline-item-simple:last-child {
        margin-bottom: 0;
    }
    
    .timeline-marker-simple {
        position: absolute;
        left: -23px;
        top: 4px;
        width: 12px;
        height: 12px;
        border-radius: 50%;
        background: #FF6B35;
        border: 2px solid white;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    
    .timeline-content-simple {
        background: #f8f9fa;
        padding: 12px 16px;
        border-radius: 8px;
        border-left: 3px solid #FF6B35;
    }
    
    .card {
        border: none;
        box-shadow: 0 2px 4px rgba(0,0,0,0.08);
        border-radius: 12px;
    }
    
    .card-header {
        border-radius: 12px 12px 0 0 !important;
        padding: 1rem 1.25rem;
    }
    
    .form-control {
        border-radius: 8px;
        border: 2px solid #e9ecef;
        padding: 0.75rem 1rem;
        transition: all 0.3s ease;
    }
    
    .form-control:focus {
        border-color: #FF6B35;
        box-shadow: 0 0 0 0.2rem rgba(255, 107, 53, 0.25);
    }
    
    .btn {
        border-radius: 8px;
        padding: 0.75rem 1.5rem;
        font-weight: 600;
        transition: all 0.3s ease;
    }
    
    .btn-orange:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(255, 107, 53, 0.4);
    }
</style>
@endpush

@push('scripts')
<script>
    // Auto-hide success alert after 5 seconds
    document.addEventListener('DOMContentLoaded', function() {
        const successAlert = document.querySelector('.alert-success');
        if (successAlert) {
            setTimeout(() => {
                const bsAlert = new bootstrap.Alert(successAlert);
                bsAlert.close();
            }, 5000);
        }
        
        // Password strength indicator (optionnel)
        const passwordInput = document.getElementById('password');
        if (passwordInput) {
            passwordInput.addEventListener('input', function() {
                // Ajoutez ici la logique pour indiquer la force du mot de passe
            });
        }
    });
</script>
@endpush