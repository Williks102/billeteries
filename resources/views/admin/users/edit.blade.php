{{-- resources/views/admin/users/edit.blade.php --}}
@extends('layouts.admin')

@section('title', 'Modifier utilisateur : ' . $user->name . ' - ClicBillet CI')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.users.index') }}">Utilisateurs</a></li>
    <li class="breadcrumb-item"><a href="{{ route('admin.users.show', $user) }}">{{ $user->name }}</a></li>
    <li class="breadcrumb-item active" aria-current="page">Modifier</li>
@endsection

@section('content')
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="fw-bold mb-1">Modifier l'utilisateur</h2>
                    <p class="text-muted mb-0">Modifiez les informations de {{ $user->name }}</p>
                </div>
                <div>
                    <a href="{{ route('admin.users.show', $user) }}" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Retour
                    </a>
                </div>
            </div>

            <!-- Alerte si modification de son propre compte -->
            @if($user->id === auth()->id())
                <div class="alert alert-warning mb-4">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <strong>Attention :</strong> Vous modifiez votre propre compte. Soyez prudent avec les modifications de rôle.
                </div>
            @endif

            <!-- Formulaire -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <div class="d-flex align-items-center">
                        <div class="user-avatar me-3">
                            {{ substr($user->name, 0, 1) }}
                        </div>
                        <div>
                            <h5 class="mb-0">Informations de base</h5>
                            <small class="text-muted">Modifiez les informations personnelles</small>
                        </div>
                    </div>
                </div>

                <form method="POST" action="{{ route('admin.users.update', $user) }}">
                    @csrf
                    @method('PUT')

                    <div class="card-body">
                        <div class="row">
                            <!-- Nom -->
                            <div class="col-md-6 mb-4">
                                <label for="name" class="form-label">
                                    Nom complet <span class="text-danger">*</span>
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="fas fa-user"></i>
                                    </span>
                                    <input type="text" 
                                           class="form-control @error('name') is-invalid @enderror" 
                                           id="name" 
                                           name="name" 
                                           value="{{ old('name', $user->name) }}" 
                                           required>
                                </div>
                                @error('name')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Email -->
                            <div class="col-md-6 mb-4">
                                <label for="email" class="form-label">
                                    Email <span class="text-danger">*</span>
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="fas fa-envelope"></i>
                                    </span>
                                    <input type="email" 
                                           class="form-control @error('email') is-invalid @enderror" 
                                           id="email" 
                                           name="email" 
                                           value="{{ old('email', $user->email) }}" 
                                           required>
                                    @if($user->email_verified_at)
                                        <span class="input-group-text text-success">
                                            <i class="fas fa-check-circle" title="Email vérifié"></i>
                                        </span>
                                    @else
                                        <span class="input-group-text text-warning">
                                            <i class="fas fa-exclamation-triangle" title="Email non vérifié"></i>
                                        </span>
                                    @endif
                                </div>
                                @error('email')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Téléphone -->
                            <div class="col-md-6 mb-4">
                                <label for="phone" class="form-label">Téléphone</label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="fas fa-phone"></i>
                                    </span>
                                    <input type="tel" 
                                           class="form-control @error('phone') is-invalid @enderror" 
                                           id="phone" 
                                           name="phone" 
                                           value="{{ old('phone', $user->phone) }}"
                                           placeholder="+225 XX XX XX XX XX">
                                </div>
                                @error('phone')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Rôle -->
                            <div class="col-md-6 mb-4">
                                <label for="role" class="form-label">
                                    Rôle <span class="text-danger">*</span>
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="fas fa-user-tag"></i>
                                    </span>
                                    <select class="form-select @error('role') is-invalid @enderror" 
                                            id="role" 
                                            name="role" 
                                            required>
                                        <option value="">Choisir un rôle</option>
                                        <option value="acheteur" {{ old('role', $user->role) === 'acheteur' ? 'selected' : '' }}>
                                            👤 Acheteur
                                        </option>
                                        <option value="promoteur" {{ old('role', $user->role) === 'promoteur' ? 'selected' : '' }}>
                                            🎤 Promoteur
                                        </option>
                                        <option value="admin" {{ old('role', $user->role) === 'admin' ? 'selected' : '' }}>
                                            👑 Administrateur
                                        </option>
                                    </select>
                                </div>
                                @error('role')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <!-- Description du rôle sélectionné -->
                        <div class="row">
                            <div class="col-12 mb-4">
                                <div id="role-description" class="role-info-card" style="display: none;">
                                    <div class="role-info-content">
                                        <!-- Contenu dynamique basé sur le rôle -->
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Section mot de passe -->
                    <div class="card-header bg-light border-top">
                        <h6 class="mb-0">
                            <i class="fas fa-key me-2"></i>
                            Modification du mot de passe
                        </h6>
                        <small class="text-muted">Laissez vide pour conserver le mot de passe actuel</small>
                    </div>

                    <div class="card-body">
                        <div class="row">
                            <!-- Nouveau mot de passe -->
                            <div class="col-md-6 mb-4">
                                <label for="password" class="form-label">Nouveau mot de passe</label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="fas fa-lock"></i>
                                    </span>
                                    <input type="password" 
                                           class="form-control @error('password') is-invalid @enderror" 
                                           id="password" 
                                           name="password"
                                           placeholder="Minimum 8 caractères">
                                    <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('password')">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                                @error('password')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                                <div class="form-text">
                                    <i class="fas fa-info-circle me-1"></i>
                                    Le mot de passe doit contenir au moins 8 caractères
                                </div>
                            </div>

                            <!-- Confirmation mot de passe -->
                            <div class="col-md-6 mb-4">
                                <label for="password_confirmation" class="form-label">Confirmer le mot de passe</label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="fas fa-lock"></i>
                                    </span>
                                    <input type="password" 
                                           class="form-control" 
                                           id="password_confirmation" 
                                           name="password_confirmation"
                                           placeholder="Répéter le mot de passe">
                                    <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('password_confirmation')">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Indicateur de force du mot de passe -->
                        <div class="mb-4" id="password-strength" style="display: none;">
                            <div class="d-flex align-items-center">
                                <span class="me-2 small">Force :</span>
                                <div class="progress flex-grow-1 me-2" style="height: 6px;">
                                    <div class="progress-bar" role="progressbar" style="width: 0%"></div>
                                </div>
                                <small class="text-muted" id="strength-text">-</small>
                            </div>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="card-footer bg-white border-top">
                        <div class="row align-items-center">
                            <div class="col-md-6">
                                <div class="form-text">
                                    <i class="fas fa-info-circle text-primary me-1"></i>
                                    Tous les champs marqués d'un <span class="text-danger">*</span> sont obligatoires
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="action-bar mt-0">
                                    <button type="submit" class="btn btn-orange">
                                        <i class="fas fa-save me-2"></i>Enregistrer les modifications
                                    </button>
                                    <a href="{{ route('admin.users.show', $user) }}" 
                                       class="btn btn-outline-secondary action-bar__cancel">
                                        <i class="fas fa-times me-2"></i>Annuler
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Informations supplémentaires -->
            <div class="row mt-4">
                <div class="col-md-6">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white">
                            <h6 class="mb-0">
                                <i class="fas fa-chart-line me-2 text-success"></i>
                                Statistiques utilisateur
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="stats-grid">
                                @if($user->isPromoteur())
                                    <div class="stat-item">
                                        <span class="stat-label">Événements créés</span>
                                        <span class="stat-value">{{ $user->events()->count() }}</span>
                                    </div>
                                    <div class="stat-item">
                                        <span class="stat-label">Revenus générés</span>
                                        <span class="stat-value">{{ number_format($user->totalRevenue() ?? 0) }} FCFA</span>
                                    </div>
                                @else
                                    <div class="stat-item">
                                        <span class="stat-label">Commandes passées</span>
                                        <span class="stat-value">{{ $user->orders()->count() }}</span>
                                    </div>
                                    <div class="stat-item">
                                        <span class="stat-label">Total dépensé</span>
                                        <span class="stat-value">{{ number_format($user->orders()->where('payment_status', 'paid')->sum('total_amount')) }} FCFA</span>
                                    </div>
                                @endif
                                <div class="stat-item">
                                    <span class="stat-label">Membre depuis</span>
                                    <span class="stat-value">{{ $user->created_at->diffForHumans() }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white">
                            <h6 class="mb-0">
                                <i class="fas fa-shield-alt me-2 text-warning"></i>
                                Sécurité et confidentialité
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="security-info">
                                <div class="security-item">
                                    <div class="security-icon">
                                        @if($user->email_verified_at)
                                            <i class="fas fa-check-circle text-success"></i>
                                        @else
                                            <i class="fas fa-exclamation-triangle text-warning"></i>
                                        @endif
                                    </div>
                                    <div class="security-content">
                                        <strong>Email de vérification</strong>
                                        <p class="mb-0">
                                            @if($user->email_verified_at)
                                                Vérifié le {{ $user->email_verified_at->format('d/m/Y') }}
                                            @else
                                                En attente de vérification
                                                <br>
                                                <button class="btn btn-sm btn-outline-primary mt-1" onclick="resendVerification()">
                                                    Renvoyer l'email
                                                </button>
                                            @endif
                                        </p>
                                    </div>
                                </div>

                                <div class="security-item">
                                    <div class="security-icon">
                                        <i class="fas fa-clock text-info"></i>
                                    </div>
                                    <div class="security-content">
                                        <strong>Dernière activité</strong>
                                        <p class="mb-0">{{ $user->updated_at->diffForHumans() }}</p>
                                    </div>
                                </div>

                                <div class="security-item">
                                    <div class="security-icon">
                                        <i class="fas fa-key text-secondary"></i>
                                    </div>
                                    <div class="security-content">
                                        <strong>Mot de passe</strong>
                                        <p class="mb-0">
                                            Dernière modification : {{ $user->updated_at->format('d/m/Y') }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('styles')
<style>
/* Avatar utilisateur */
.user-avatar {
    width: 50px;
    height: 50px;
    background: linear-gradient(135deg, var(--brand), var(--brand-decor));
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.2rem;
    font-weight: 700;
}

/* Cartes d'information sur les rôles */
.role-info-card {
    background: linear-gradient(135deg, rgba(var(--brand-rgb), 0.05), rgba(255, 140, 97, 0.05));
    border: 1px solid rgba(var(--brand-rgb), 0.1);
    border-radius: 12px;
    padding: 20px;
    transition: all 0.3s ease;
}

.role-info-content {
    display: flex;
    align-items: flex-start;
    gap: 15px;
}

.role-icon {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    color: white;
    flex-shrink: 0;
}

.role-icon.admin {
    background: linear-gradient(135deg, #dc3545, #c82333);
}

.role-icon.promoteur {
    background: linear-gradient(135deg, var(--brand), var(--brand-decor));
}

.role-icon.acheteur {
    background: linear-gradient(135deg, #28a745, #20c997);
}

.role-details h6 {
    color: #495057;
    font-weight: 700;
    margin-bottom: 8px;
}

.role-details p {
    color: #6c757d;
    margin-bottom: 12px;
    line-height: 1.5;
}

.role-permissions {
    list-style: none;
    padding: 0;
    margin: 0;
}

.role-permissions li {
    color: #28a745;
    font-size: 0.875rem;
    margin-bottom: 4px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.role-permissions li::before {
    content: "✓";
    color: #28a745;
    font-weight: bold;
}

/* Grille de statistiques */
.stats-grid {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.stat-item {
    display: flex;
    justify-content: between;
    align-items: center;
    padding: 0.75rem 0;
    border-bottom: 1px solid #f8f9fa;
}

.stat-item:last-child {
    border-bottom: none;
}

.stat-label {
    color: #6c757d;
    font-size: 0.875rem;
    flex: 1;
}

.stat-value {
    color: #495057;
    font-weight: 600;
    font-size: 0.875rem;
}

/* Informations de sécurité */
.security-info {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.security-item {
    display: flex;
    align-items: flex-start;
    gap: 15px;
}

.security-icon {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: #f8f9fa;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    font-size: 1.1rem;
}

.security-content {
    flex: 1;
}

.security-content strong {
    display: block;
    color: #495057;
    margin-bottom: 4px;
    font-size: 0.9rem;
}

.security-content p {
    color: #6c757d;
    font-size: 0.825rem;
    line-height: 1.4;
}

/* Indicateur de force de mot de passe */
#password-strength .progress {
    background-color: #e9ecef;
}

.progress-bar.weak {
    background-color: #dc3545;
}

.progress-bar.medium {
    background-color: #ffc107;
}

.progress-bar.strong {
    background-color: #28a745;
}

/* Responsive */
@media (max-width: 768px) {
    .role-info-content {
        flex-direction: column;
        text-align: center;
    }
    
    .stat-item {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.25rem;
    }
    
    .security-item {
        flex-direction: column;
        text-align: center;
        gap: 10px;
    }
}
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Gestion de l'affichage des informations de rôle
    const roleSelect = document.getElementById('role');
    const roleDescription = document.getElementById('role-description');
    const roleContent = roleDescription.querySelector('.role-info-content');
    
    // Descriptions des rôles
    const roleDescriptions = {
        'acheteur': {
            icon: 'acheteur',
            emoji: '👤',
            title: 'Acheteur',
            description: 'Peut acheter des billets pour les événements, gérer ses commandes et son profil.',
            permissions: [
                'Acheter des billets',
                'Gérer ses commandes',
                'Télécharger ses billets',
                'Modifier son profil'
            ]
        },
        'promoteur': {
            icon: 'promoteur',
            emoji: '🎤',
            title: 'Promoteur',
            description: 'Peut créer et gérer des événements, vendre des billets et suivre ses revenus.',
            permissions: [
                'Créer des événements',
                'Gérer les billets',
                'Scanner les QR codes',
                'Suivre les ventes et revenus',
                'Accès au dashboard promoteur'
            ]
        },
        'admin': {
            icon: 'admin',
            emoji: '👑',
            title: 'Administrateur',
            description: 'Accès complet à la plateforme. Peut gérer tous les utilisateurs, événements et configurations.',
            permissions: [
                'Gestion complète des utilisateurs',
                'Modération des événements',
                'Accès aux rapports et statistiques',
                'Configuration de la plateforme',
                'Gestion des commissions'
            ]
        }
    };
    
    function updateRoleDescription() {
        const selectedRole = roleSelect.value;
        
        if (selectedRole && roleDescriptions[selectedRole]) {
            const roleInfo = roleDescriptions[selectedRole];
            
            roleContent.innerHTML = `
                <div class="role-icon ${roleInfo.icon}">
                    ${roleInfo.emoji}
                </div>
                <div class="role-details">
                    <h6>${roleInfo.title}</h6>
                    <p>${roleInfo.description}</p>
                    <ul class="role-permissions">
                        ${roleInfo.permissions.map(permission => `<li>${permission}</li>`).join('')}
                    </ul>
                </div>
            `;
            
            roleDescription.style.display = 'block';
        } else {
            roleDescription.style.display = 'none';
        }
    }
    
    // Mettre à jour la description au changement de rôle
    roleSelect.addEventListener('change', updateRoleDescription);
    
    // Initialiser la description si un rôle est déjà sélectionné
    updateRoleDescription();
    
    // Gestion de la force du mot de passe
    const passwordInput = document.getElementById('password');
    const passwordStrength = document.getElementById('password-strength');
    const strengthBar = passwordStrength.querySelector('.progress-bar');
    const strengthText = document.getElementById('strength-text');
    
    passwordInput.addEventListener('input', function() {
        const password = this.value;
        
        if (password.length === 0) {
            passwordStrength.style.display = 'none';
            return;
        }
        
        passwordStrength.style.display = 'block';
        
        let strength = 0;
        let strengthLabel = '';
        let strengthClass = '';
        
        // Critères de force
        if (password.length >= 8) strength += 25;
        if (password.match(/[a-z]/)) strength += 25;
        if (password.match(/[A-Z]/)) strength += 25;
        if (password.match(/[0-9]/)) strength += 25;
        
        // Déterminer le niveau et la couleur
        if (strength <= 50) {
            strengthLabel = 'Faible';
            strengthClass = 'weak';
        } else if (strength <= 75) {
            strengthLabel = 'Moyen';
            strengthClass = 'medium';
        } else {
            strengthLabel = 'Fort';
            strengthClass = 'strong';
        }
        
        // Mettre à jour l'affichage
        strengthBar.style.width = strength + '%';
        strengthBar.className = `progress-bar ${strengthClass}`;
        strengthText.textContent = strengthLabel;
    });
});

// Fonction pour afficher/masquer le mot de passe
function togglePassword(inputId) {
    const input = document.getElementById(inputId);
    const icon = event.target.closest('button').querySelector('i');
    
    if (input.type === 'password') {
        input.type = 'text';
        icon.className = 'fas fa-eye-slash';
    } else {
        input.type = 'password';
        icon.className = 'fas fa-eye';
    }
}

// Fonction pour renvoyer l'email de vérification
function resendVerification() {
    if (confirm('Renvoyer l\'email de vérification à cet utilisateur ?')) {
        fetch(`/admin/users/{{ $user->id }}/resend-verification`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Content-Type': 'application/json',
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Email de vérification envoyé avec succès');
            } else {
                alert('Erreur lors de l\'envoi de l\'email');
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            alert('Erreur lors de l\'envoi de l\'email');
        });
    }
}

// Validation du formulaire
document.querySelector('form').addEventListener('submit', function(e) {
    const password = document.getElementById('password').value;
    const passwordConfirmation = document.getElementById('password_confirmation').value;
    
    if (password && password !== passwordConfirmation) {
        e.preventDefault();
        alert('Les mots de passe ne correspondent pas');
        document.getElementById('password_confirmation').focus();
    }
});
</script>
@endpush