@extends('layouts.admin')

@section('title', 'Créer un utilisateur')

@section('content')
<div class="container-fluid px-4">
    <!-- En-tête de la page -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">Créer un utilisateur</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.users') }}">Utilisateurs</a></li>
                    <li class="breadcrumb-item active">Créer</li>
                </ol>
            </nav>
        </div>
        <div>
            <a href="{{ route('admin.users') }}" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-2"></i>Retour
            </a>
        </div>
    </div>

    <!-- Formulaire de création -->
    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-user-plus me-2"></i>Informations de l'utilisateur
                    </h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.users.store') }}" method="POST" enctype="multipart/form-data" id="userForm">
                        @csrf
                        
                        <!-- Informations de base -->
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="name" class="form-label">Nom complet <span class="text-danger">*</span></label>
                                <input type="text" 
                                       class="form-control @error('name') is-invalid @enderror" 
                                       id="name" 
                                       name="name" 
                                       value="{{ old('name') }}" 
                                       placeholder="Ex: Kouame Jean-Claude">
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label for="email" class="form-label">Adresse email <span class="text-danger">*</span></label>
                                <input type="email" 
                                       class="form-control @error('email') is-invalid @enderror" 
                                       id="email" 
                                       name="email" 
                                       value="{{ old('email') }}" 
                                       placeholder="Ex: jean.kouame@email.com">
                                @error('email')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <!-- Téléphone et rôle -->
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="phone" class="form-label">Numéro de téléphone</label>
                                <input type="tel" 
                                       class="form-control @error('phone') is-invalid @enderror" 
                                       id="phone" 
                                       name="phone" 
                                       value="{{ old('phone') }}" 
                                       placeholder="+225 XX XX XX XX XX">
                                @error('phone')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label for="role" class="form-label">Rôle <span class="text-danger">*</span></label>
                                <select class="form-select @error('role') is-invalid @enderror" id="role" name="role">
                                    <option value="">Sélectionner un rôle</option>
                                    <option value="acheteur" {{ old('role') == 'acheteur' ? 'selected' : '' }}>
                                        Acheteur (Client)
                                    </option>
                                    <option value="promoteur" {{ old('role') == 'promoteur' ? 'selected' : '' }}>
                                        Promoteur (Organisateur)
                                    </option>
                                    <option value="admin" {{ old('role') == 'admin' ? 'selected' : '' }}>
                                        Administrateur
                                    </option>
                                </select>
                                @error('role')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <!-- Mot de passe -->
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="password" class="form-label">Mot de passe <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="password" 
                                           class="form-control @error('password') is-invalid @enderror" 
                                           id="password" 
                                           name="password"
                                           placeholder="Minimum 8 caractères">
                                    <button type="button" class="btn btn-outline-secondary" id="togglePassword">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                                @error('password')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="form-text">Doit contenir au moins 8 caractères</div>
                            </div>
                            <div class="col-md-6">
                                <label for="password_confirmation" class="form-label">Confirmer le mot de passe <span class="text-danger">*</span></label>
                                <input type="password" 
                                       class="form-control @error('password_confirmation') is-invalid @enderror" 
                                       id="password_confirmation" 
                                       name="password_confirmation"
                                       placeholder="Confirmer le mot de passe">
                                @error('password_confirmation')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <!-- Statut et options -->
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="status" class="form-label">Statut</label>
                                <select class="form-select" id="status" name="status">
                                    <option value="active" {{ old('status', 'active') == 'active' ? 'selected' : '' }}>
                                        Actif
                                    </option>
                                    <option value="inactive" {{ old('status') == 'inactive' ? 'selected' : '' }}>
                                        Inactif
                                    </option>
                                    <option value="suspended" {{ old('status') == 'suspended' ? 'selected' : '' }}>
                                        Suspendu
                                    </option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="avatar" class="form-label">Photo de profil</label>
                                <input type="file" 
                                       class="form-control @error('avatar') is-invalid @enderror" 
                                       id="avatar" 
                                       name="avatar" 
                                       accept="image/*">
                                @error('avatar')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="form-text">JPG, PNG, GIF. Max: 2MB</div>
                            </div>
                        </div>

                        <!-- Prévisualisation avatar -->
                        <div id="avatarPreview" class="mb-3" style="display: none;">
                            <label class="form-label">Aperçu de la photo</label>
                            <div>
                                <img id="previewImg" src="" alt="Aperçu" class="rounded-circle" style="width: 80px; height: 80px; object-fit: cover;">
                            </div>
                        </div>

                        <!-- Champs spécifiques au promoteur -->
                        <div id="promoteurFields" style="display: none;">
                            <div class="card bg-light">
                                <div class="card-header">
                                    <h6 class="mb-0">
                                        <i class="fas fa-briefcase me-2"></i>Informations promoteur
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <label for="company_name" class="form-label">Nom de l'entreprise</label>
                                            <input type="text" 
                                                   class="form-control @error('company_name') is-invalid @enderror" 
                                                   id="company_name" 
                                                   name="company_name" 
                                                   value="{{ old('company_name') }}" 
                                                   placeholder="Ex: Events & Co">
                                            @error('company_name')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        <div class="col-md-6">
                                            <label for="business_number" class="form-label">Numéro d'entreprise</label>
                                            <input type="text" 
                                                   class="form-control @error('business_number') is-invalid @enderror" 
                                                   id="business_number" 
                                                   name="business_number" 
                                                   value="{{ old('business_number') }}" 
                                                   placeholder="Ex: CI-ABJ-2024-123456">
                                            @error('business_number')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="mt-3">
                                        <label for="bio" class="form-label">Biographie / Description</label>
                                        <textarea class="form-control @error('bio') is-invalid @enderror" 
                                                  id="bio" 
                                                  name="bio" 
                                                  rows="3"
                                                  placeholder="Décrivez l'activité du promoteur...">{{ old('bio') }}</textarea>
                                        @error('bio')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Options de notification -->
                        <div class="card bg-light mt-3">
                            <div class="card-header">
                                <h6 class="mb-0">
                                    <i class="fas fa-bell me-2"></i>Préférences de notification
                                </h6>
                            </div>
                            <div class="card-body">
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" id="send_welcome_email" name="send_welcome_email" value="1" {{ old('send_welcome_email', true) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="send_welcome_email">
                                        Envoyer un email de bienvenue
                                    </label>
                                </div>
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" id="email_notifications" name="email_notifications" value="1" {{ old('email_notifications', true) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="email_notifications">
                                        Notifications par email activées
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="force_password_change" name="force_password_change" value="1" {{ old('force_password_change') ? 'checked' : '' }}>
                                    <label class="form-check-label" for="force_password_change">
                                        Forcer le changement de mot de passe à la première connexion
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- Boutons d'action -->
                        <div class="d-flex justify-content-between mt-4">
                            <button type="button" class="btn btn-outline-secondary" onclick="history.back()">
                                <i class="fas fa-times me-2"></i>Annuler
                            </button>
                            <div>
                                <button type="submit" name="action" value="save" class="btn btn-outline-primary me-2">
                                    <i class="fas fa-save me-2"></i>Enregistrer
                                </button>
                                <button type="submit" name="action" value="save_and_notify" class="btn btn-success">
                                    <i class="fas fa-user-check me-2"></i>Créer et notifier
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Sidebar avec informations -->
        <div class="col-lg-4">
            <!-- Guide des rôles -->
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">
                        <i class="fas fa-info-circle me-2"></i>Guide des rôles
                    </h6>
                </div>
                <div class="card-body">
                    <div class="role-info mb-3">
                        <div class="d-flex align-items-center mb-2">
                            <span class="badge bg-primary me-2">
                                <i class="fas fa-shopping-cart"></i>
                            </span>
                            <strong>Acheteur</strong>
                        </div>
                        <p class="small text-muted mb-0">
                            Peut acheter des billets, consulter ses commandes et gérer son profil.
                        </p>
                    </div>
                    
                    <div class="role-info mb-3">
                        <div class="d-flex align-items-center mb-2">
                            <span class="badge bg-success me-2">
                                <i class="fas fa-calendar-alt"></i>
                            </span>
                            <strong>Promoteur</strong>
                        </div>
                        <p class="small text-muted mb-0">
                            Peut créer et gérer des événements, scanner les billets et consulter ses ventes.
                        </p>
                    </div>
                    
                    <div class="role-info">
                        <div class="d-flex align-items-center mb-2">
                            <span class="badge bg-danger me-2">
                                <i class="fas fa-cog"></i>
                            </span>
                            <strong>Administrateur</strong>
                        </div>
                        <p class="small text-muted mb-0">
                            Accès complet à la plateforme, gestion des utilisateurs et modération.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Conseils de sécurité -->
            <div class="card mt-3">
                <div class="card-header">
                    <h6 class="mb-0">
                        <i class="fas fa-shield-alt me-2"></i>Conseils de sécurité
                    </h6>
                </div>
                <div class="card-body">
                    <div class="alert alert-warning mb-3">
                        <h6><i class="fas fa-key me-2"></i>Mot de passe</h6>
                        <p class="mb-0 small">Utilisez un mot de passe fort avec majuscules, minuscules, chiffres et symboles.</p>
                    </div>
                    
                    <div class="alert alert-info mb-3">
                        <h6><i class="fas fa-envelope me-2"></i>Email unique</h6>
                        <p class="mb-0 small">Chaque adresse email ne peut être utilisée que pour un seul compte.</p>
                    </div>
                    
                    <div class="alert alert-success mb-0">
                        <h6><i class="fas fa-user-shield me-2"></i>Vérification</h6>
                        <p class="mb-0 small">L'utilisateur recevra un email de confirmation pour activer son compte.</p>
                    </div>
                </div>
            </div>

            <!-- Statistiques rapides -->
            <div class="card mt-3">
                <div class="card-header">
                    <h6 class="mb-0">
                        <i class="fas fa-chart-pie me-2"></i>Statistiques
                    </h6>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="small">Total utilisateurs</span>
                        <span class="badge bg-primary">{{ $totalUsers ?? 0 }}</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="small">Acheteurs</span>
                        <span class="badge bg-info">{{ $totalBuyers ?? 0 }}</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="small">Promoteurs</span>
                        <span class="badge bg-success">{{ $totalPromoters ?? 0 }}</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="small">Administrateurs</span>
                        <span class="badge bg-danger">{{ $totalAdmins ?? 0 }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Gestion de l'affichage/masquage du mot de passe
    const togglePassword = document.getElementById('togglePassword');
    const passwordInput = document.getElementById('password');

    togglePassword.addEventListener('click', function() {
        const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
        passwordInput.setAttribute('type', type);
        
        const icon = this.querySelector('i');
        icon.classList.toggle('fa-eye');
        icon.classList.toggle('fa-eye-slash');
    });

    // Prévisualisation de l'avatar
    const avatarInput = document.getElementById('avatar');
    const avatarPreview = document.getElementById('avatarPreview');
    const previewImg = document.getElementById('previewImg');

    avatarInput.addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                previewImg.src = e.target.result;
                avatarPreview.style.display = 'block';
            };
            reader.readAsDataURL(file);
        } else {
            avatarPreview.style.display = 'none';
        }
    });

    // Gestion des champs spécifiques au promoteur
    const roleSelect = document.getElementById('role');
    const promoteurFields = document.getElementById('promoteurFields');

    function togglePromoteurFields() {
        if (roleSelect.value === 'promoteur') {
            promoteurFields.style.display = 'block';
            // Rendre certains champs requis pour les promoteurs
            document.getElementById('company_name').setAttribute('required', '');
        } else {
            promoteurFields.style.display = 'none';
            // Enlever la contrainte required
            document.getElementById('company_name').removeAttribute('required');
        }
    }

    roleSelect.addEventListener('change', togglePromoteurFields);
    togglePromoteurFields(); // Appel initial

    // Validation du mot de passe
    const passwordConfirmation = document.getElementById('password_confirmation');
    
    function validatePasswords() {
        if (passwordInput.value !== passwordConfirmation.value) {
            passwordConfirmation.setCustomValidity('Les mots de passe ne correspondent pas');
        } else {
            passwordConfirmation.setCustomValidity('');
        }
    }

    passwordInput.addEventListener('input', validatePasswords);
    passwordConfirmation.addEventListener('input', validatePasswords);

    // Validation du formulaire
    const form = document.getElementById('userForm');
    form.addEventListener('submit', function(e) {
        const action = e.submitter.value;
        
        if (action === 'save_and_notify') {
            if (!confirm('Créer l\'utilisateur et lui envoyer un email de notification ?')) {
                e.preventDefault();
                return false;
            }
        }

        // Validation côté client
        if (!form.checkValidity()) {
            e.preventDefault();
            e.stopPropagation();
        }
        
        form.classList.add('was-validated');
    });

    // Génération automatique de mot de passe
    const generatePasswordBtn = document.createElement('button');
    generatePasswordBtn.type = 'button';
    generatePasswordBtn.className = 'btn btn-outline-info btn-sm mt-2';
    generatePasswordBtn.innerHTML = '<i class="fas fa-random me-1"></i>Générer un mot de passe';
    
    generatePasswordBtn.addEventListener('click', function() {
        const password = generateSecurePassword();
        passwordInput.value = password;
        passwordConfirmation.value = password;
        
        // Afficher le mot de passe généré
        passwordInput.type = 'text';
        togglePassword.querySelector('i').classList.remove('fa-eye');
        togglePassword.querySelector('i').classList.add('fa-eye-slash');
        
        // Validation
        validatePasswords();
        
        alert('Mot de passe généré: ' + password + '\n\nAssurez-vous de le communiquer à l\'utilisateur de manière sécurisée.');
    });
    
    document.getElementById('password').parentNode.parentNode.appendChild(generatePasswordBtn);

    // Validation de l'email en temps réel
    const emailInput = document.getElementById('email');
    let emailTimeout;

    emailInput.addEventListener('input', function() {
        clearTimeout(emailTimeout);
        const email = this.value;
        
        if (email.length > 5 && email.includes('@')) {
            emailTimeout = setTimeout(() => {
                checkEmailAvailability(email);
            }, 500);
        }
    });

    // Formatage du numéro de téléphone
    const phoneInput = document.getElementById('phone');
    phoneInput.addEventListener('input', function() {
        let value = this.value.replace(/\D/g, ''); // Enlever tout sauf les chiffres
        
        if (value.startsWith('225')) {
            // Format ivoirien: +225 XX XX XX XX XX
            if (value.length <= 13) {
                value = value.replace(/(\d{3})(\d{2})(\d{2})(\d{2})(\d{2})/, '+$1 $2 $3 $4 $5');
            }
        } else if (value.length > 0) {
            // Ajouter le préfixe +225 automatiquement
            value = '225' + value;
            if (value.length <= 13) {
                value = value.replace(/(\d{3})(\d{2})(\d{2})(\d{2})(\d{2})/, '+$1 $2 $3 $4 $5');
            }
        }
        
        this.value = value;
    });
});

// Fonction pour générer un mot de passe sécurisé
function generateSecurePassword(length = 12) {
    const charset = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*";
    let password = "";
    
    // S'assurer qu'on a au moins un caractère de chaque type
    password += "ABCDEFGHIJKLMNOPQRSTUVWXYZ"[Math.floor(Math.random() * 26)]; // Majuscule
    password += "abcdefghijklmnopqrstuvwxyz"[Math.floor(Math.random() * 26)]; // Minuscule
    password += "0123456789"[Math.floor(Math.random() * 10)]; // Chiffre
    password += "!@#$%^&*"[Math.floor(Math.random() * 8)]; // Symbole
    
    // Compléter avec des caractères aléatoires
    for (let i = password.length; i < length; i++) {
        password += charset[Math.floor(Math.random() * charset.length)];
    }
    
    // Mélanger le mot de passe
    return password.split('').sort(() => Math.random() - 0.5).join('');
}

// Fonction pour vérifier la disponibilité de l'email
async function checkEmailAvailability(email) {
    try {
        const response = await fetch('/admin/users/check-email', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({ email: email })
        });
        
        const data = await response.json();
        const emailInput = document.getElementById('email');
        
        if (data.available) {
            emailInput.classList.remove('is-invalid');
            emailInput.classList.add('is-valid');
        } else {
            emailInput.classList.remove('is-valid');
            emailInput.classList.add('is-invalid');
            
            // Afficher un message d'erreur
            let feedback = emailInput.parentNode.querySelector('.invalid-feedback');
            if (!feedback) {
                feedback = document.createElement('div');
                feedback.className = 'invalid-feedback';
                emailInput.parentNode.appendChild(feedback);
            }
            feedback.textContent = 'Cette adresse email est déjà utilisée.';
        }
    } catch (error) {
        console.error('Erreur lors de la vérification de l\'email:', error);
    }
}
</script>
@endpush

@push('styles')
<style>
.role-info {
    padding: 0.75rem;
    border: 1px solid #e9ecef;
    border-radius: 0.375rem;
    background-color: #f8f9fa;
}

.card-header {
    background-color: #f8f9fa;
    border-bottom: 1px solid #dee2e6;
}

.form-label {
    font-weight: 500;
    margin-bottom: 0.5rem;
}

.alert h6 {
    margin-bottom: 0.5rem;
    font-size: 0.875rem;
}

#avatarPreview img {
    border: 3px solid #dee2e6;
}

.input-group .btn {
    border-left: none;
}

.badge {
    font-size: 0.75rem;
}

.was-validated .form-control:valid {
    border-color: #198754;
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 8 8'%3e%3cpath fill='%23198754' d='m2.3 6.73.94-.94 2.94 2.94 2.94-2.94.94.94L5.23 10.67z'/%3e%3c/svg%3e");
}

.was-validated .form-control:invalid {
    border-color: #dc3545;
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 12 12' width='12' height='12' fill='none' stroke='%23dc3545'%3e%3ccircle cx='6' cy='6' r='4.5'/%3e%3cpath d='m5.8 5.8 2.4 2.4 2.4-2.4'/%3e%3c/svg%3e");
}
</style>
@endpush
@endsection