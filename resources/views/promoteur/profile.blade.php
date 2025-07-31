@extends('layouts.promoteur')
@section('title', 'Page profile - ClicBillet CI')

@push('styles')
<style>
    .profile-card {
        background: white;
        border-radius: 15px;
        padding: 2rem;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        border-left: 4px solid #FF6B35;
        margin-bottom: 2rem;
        transition: transform 0.3s ease;
    }
    
    .profile-card:hover {
        transform: translateY(-5px);
    }
    
    .profile-header {
        background: linear-gradient(135deg, #FF6B35, #E55A2B);
        border-radius: 15px;
        padding: 2rem;
        color: white;
        margin-bottom: 2rem;
        text-align: center;
    }
    
    .profile-avatar {
        width: 120px;
        height: 120px;
        border-radius: 50%;
        border: 4px solid white;
        margin: 0 auto 1rem;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 3rem;
        background: rgba(255, 255, 255, 0.2);
        backdrop-filter: blur(10px);
    }
    
    .stat-item {
        text-align: center;
        padding: 1.5rem;
        background: rgba(255, 255, 255, 0.1);
        border-radius: 10px;
        margin: 0.5rem;
        backdrop-filter: blur(10px);
    }
    
    .btn-orange {
        background: linear-gradient(135deg, #FF6B35, #E55A2B);
        border: none;
        color: white;
        padding: 0.75rem 2rem;
        border-radius: 10px;
        font-weight: 600;
        transition: all 0.3s ease;
    }
    
    .btn-orange:hover {
        background: linear-gradient(135deg, #E55A2B, #D4491F);
        color: white;
        transform: translateY(-2px);
    }
    
    .form-control:focus, .form-select:focus {
        border-color: #FF6B35;
        box-shadow: 0 0 0 0.2rem rgba(255, 107, 53, 0.25);
    }
    
    .badge-role {
        background: linear-gradient(135deg, #28a745, #20c997);
        color: white;
        padding: 0.5rem 1rem;
        border-radius: 20px;
        font-size: 0.875rem;
        font-weight: 600;
    }
    
    .activity-item {
        border-left: 3px solid #FF6B35;
        padding-left: 1rem;
        margin-bottom: 1rem;
        padding-bottom: 1rem;
        border-bottom: 1px solid #e9ecef;
    }
    
    .activity-item:last-child {
        border-bottom: none;
    }
    
    .security-section {
        background: linear-gradient(135deg, #f8f9fa, #e9ecef);
        border-radius: 15px;
        padding: 2rem;
        border: 2px dashed #dee2e6;
    }
</style>
@endpush

@section('content')
<div class="container-fluid">
    <!-- En-tête du profil -->
    <div class="profile-header">
        <div class="profile-avatar">
            <i class="fas fa-user"></i>
        </div>
        
        <h2 class="fw-bold mb-2">{{ $user->name }}</h2>
        <p class="mb-3 opacity-75">{{ $user->email }}</p>
        
        <span class="badge-role">
            <i class="fas fa-microphone me-2"></i>Promoteur
        </span>
        
        <div class="row mt-4">
            <div class="col-md-3">
                <div class="stat-item">
                    <h3 class="fw-bold mb-1">{{ $user->events()->count() }}</h3>
                    <small>Événements</small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-item">
                    <h3 class="fw-bold mb-1">{{ $user->events()->where('status', 'published')->count() }}</h3>
                    <small>Publiés</small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-item">
                    <h3 class="fw-bold mb-1">{{ number_format($user->totalRevenue()) }} F</h3>
                    <small>Revenus</small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-item">
                    <h3 class="fw-bold mb-1">{{ $user->created_at->diffInDays() }}</h3>
                    <small>Jours d'ancienneté</small>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Informations personnelles -->
        <div class="col-lg-8">
            <div class="profile-card">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h5 class="fw-bold mb-0">
                        <i class="fas fa-user-edit me-2" style="color: #FF6B35;"></i>
                        Informations Personnelles
                    </h5>
                    <button class="btn btn-outline-secondary btn-sm" onclick="toggleEdit()">
                        <i class="fas fa-edit me-2"></i>Modifier
                    </button>
                </div>
                
                <form id="profileForm" method="POST" action="{{ route('promoteur.profile.update') }}">
                    @csrf
                    @method('PATCH')
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Nom complet</label>
                            <input type="text" name="name" class="form-control" 
                                   value="{{ $user->name }}" readonly id="nameInput">
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control" 
                                   value="{{ $user->email }}" readonly id="emailInput">
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Téléphone</label>
                            <input type="tel" name="phone" class="form-control" 
                                   value="{{ $user->phone }}" readonly id="phoneInput"
                                   placeholder="Ex: +225 07 08 09 10 11">
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Date d'inscription</label>
                            <input type="text" class="form-control" 
                                   value="{{ $user->created_at->format('d/m/Y') }}" readonly>
                        </div>
                        
                        <div class="col-12 mb-3">
                            <label class="form-label">Biographie / Présentation</label>
                            <textarea name="bio" class="form-control" rows="4" readonly id="bioInput"
                                      placeholder="Présentez-vous et décrivez votre activité d'organisateur d'événements...">{{ $user->bio ?? '' }}</textarea>
                        </div>
                    </div>
                    
                    <div class="text-end d-none" id="saveButtons">
                        <button type="button" class="btn btn-secondary me-2" onclick="cancelEdit()">
                            Annuler
                        </button>
                        <button type="submit" class="btn btn-orange">
                            <i class="fas fa-save me-2"></i>Sauvegarder
                        </button>
                    </div>
                </form>
            </div>

            <!-- Sécurité -->
            <div class="profile-card">
                <h5 class="fw-bold mb-4">
                    <i class="fas fa-shield-alt me-2" style="color: #FF6B35;"></i>
                    Sécurité du Compte
                </h5>
                
                <div class="security-section">
                    <div class="row">
                        <div class="col-md-8">
                            <h6 class="fw-bold mb-2">Mot de passe</h6>
                            <p class="text-muted mb-3">
                                Dernière modification : {{ $user->updated_at->diffForHumans() }}
                            </p>
                            <small class="text-muted">
                                <i class="fas fa-info-circle me-1"></i>
                                Utilisez un mot de passe fort avec au moins 8 caractères
                            </small>
                        </div>
                        <div class="col-md-4 text-end">
                            <button class="btn btn-outline-secondary" onclick="changePassword()">
                                <i class="fas fa-key me-2"></i>Changer
                            </button>
                        </div>
                    </div>
                </div>
                
                <div class="mt-4">
                    <h6 class="fw-bold mb-3">Sessions Actives</h6>
                    <div class="d-flex justify-content-between align-items-center p-3 bg-light rounded">
                        <div>
                            <strong>Session actuelle</strong>
                            <br>
                            <small class="text-muted">
                                <i class="fas fa-desktop me-1"></i>
                                {{ request()->userAgent() ? 'Navigateur Web' : 'Application' }} - 
                                {{ request()->ip() }}
                            </small>
                        </div>
                        <span class="badge bg-success">Active</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="col-lg-4">
            <!-- Activité récente -->
            <div class="profile-card">
                <h5 class="fw-bold mb-4">
                    <i class="fas fa-clock me-2" style="color: #FF6B35;"></i>
                    Activité Récente
                </h5>
                
                <div class="activity-item">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <h6 class="mb-1">Connexion</h6>
                            <small class="text-muted">Dernière connexion réussie</small>
                        </div>
                        <small class="text-muted">{{ now()->diffForHumans() }}</small>
                    </div>
                </div>
                
                @if($user->events()->latest()->first())
                <div class="activity-item">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <h6 class="mb-1">Événement créé</h6>
                            <small class="text-muted">{{ Str::limit($user->events()->latest()->first()->title, 30) }}</small>
                        </div>
                        <small class="text-muted">{{ $user->events()->latest()->first()->created_at->diffForHumans() }}</small>
                    </div>
                </div>
                @endif
                
                <div class="activity-item">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <h6 class="mb-1">Profil consulté</h6>
                            <small class="text-muted">Vous avez consulté votre profil</small>
                        </div>
                        <small class="text-muted">Maintenant</small>
                    </div>
                </div>
            </div>
            
            <!-- Liens utiles -->
            <div class="profile-card">
                <h5 class="fw-bold mb-4">
                    <i class="fas fa-link me-2" style="color: #FF6B35;"></i>
                    Liens Utiles
                </h5>
                
                <div class="d-grid gap-2">
                    <a href="{{ route('promoteur.events.create') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-plus me-2"></i>Créer un événement
                    </a>
                    
                    <a href="{{ route('promoteur.sales') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-chart-line me-2"></i>Voir mes ventes
                    </a>
                    
                    <a href="{{ route('promoteur.commissions') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-coins me-2"></i>Mes commissions
                    </a>
                    
                    <a href="{{ route('promoteur.scanner') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-qrcode me-2"></i>Scanner QR
                    </a>
                </div>
                
                <hr>
                
                <div class="text-center">
                    <h6 class="fw-bold mb-2">Besoin d'aide ?</h6>
                    <p class="small text-muted mb-3">
                        Contactez notre support pour toute question
                    </p>
                    <a href="mailto:support@billetterie-ci.com" class="btn btn-orange btn-sm">
                        <i class="fas fa-envelope me-2"></i>Contacter le Support
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal changement de mot de passe -->
<div class="modal fade" id="passwordModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Changer le mot de passe</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="passwordForm" method="POST" action="{{ route('promoteur.password.update') }}">
                @csrf
                @method('PATCH')
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Mot de passe actuel</label>
                        <input type="password" name="current_password" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nouveau mot de passe</label>
                        <input type="password" name="password" class="form-control" required minlength="8">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Confirmer le nouveau mot de passe</label>
                        <input type="password" name="password_confirmation" class="form-control" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-orange">Mettre à jour</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function toggleEdit() {
    const inputs = ['nameInput', 'emailInput', 'phoneInput', 'bioInput'];
    const saveButtons = document.getElementById('saveButtons');
    
    inputs.forEach(id => {
        const input = document.getElementById(id);
        input.readOnly = !input.readOnly;
        if (!input.readOnly) {
            input.focus();
        }
    });
    
    saveButtons.classList.toggle('d-none');
}

function cancelEdit() {
    location.reload(); // Simple way to cancel changes
}

function changePassword() {
    new bootstrap.Modal(document.getElementById('passwordModal')).show();
}

// Gestion des messages flash
document.addEventListener('DOMContentLoaded', function() {
    @if(session('success'))
        showAlert('success', '{{ session('success') }}');
    @endif
    
    @if(session('error'))
        showAlert('error', '{{ session('error') }}');
    @endif
});

function showAlert(type, message) {
    const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
    const icon = type === 'success' ? 'check-circle' : 'exclamation-triangle';
    
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert ${alertClass} alert-dismissible fade show position-fixed`;
    alertDiv.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
    alertDiv.innerHTML = `
        <i class="fas fa-${icon} me-2"></i>
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    document.body.appendChild(alertDiv);
    
    setTimeout(() => {
        alertDiv.remove();
    }, 5000);
}
</script>
@endpush