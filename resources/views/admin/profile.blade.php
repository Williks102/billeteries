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
            <p class="text-muted mb-0">Gérez vos informations personnelles et préférences</p>
        </div>
        <div class="d-flex gap-2">
            <button class="btn btn-outline-orange" data-bs-toggle="modal" data-bs-target="#changePasswordModal">
                <i class="fas fa-key me-2"></i>Changer mot de passe
            </button>
            <button class="btn btn-orange" onclick="saveProfile()">
                <i class="fas fa-save me-2"></i>Sauvegarder
            </button>
        </div>
    </div>

    <div class="row">
        <!-- Informations personnelles -->
        <div class="col-lg-8">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-user me-2"></i>Informations personnelles
                    </h5>
                </div>
                <div class="card-body">
                    <form id="profile-form" action="{{ route('admin.profile.update') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        @method('PATCH')
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Nom complet</label>
                                    <input type="text" name="name" class="form-control" 
                                           value="{{ old('name', $user->name) }}" required>
                                    @error('name')
                                        <div class="text-danger small mt-1">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Adresse email</label>
                                    <input type="email" name="email" class="form-control" 
                                           value="{{ old('email', $user->email) }}" required>
                                    @error('email')
                                        <div class="text-danger small mt-1">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Téléphone</label>
                                    <input type="tel" name="phone" class="form-control" 
                                           value="{{ old('phone', $user->phone) }}" 
                                           placeholder="+225 XX XX XX XX XX">
                                    @error('phone')
                                        <div class="text-danger small mt-1">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Langue</label>
                                    <select name="language" class="form-select">
                                        <option value="fr" {{ old('language', $user->language ?? 'fr') == 'fr' ? 'selected' : '' }}>Français</option>
                                        <option value="en" {{ old('language', $user->language ?? 'fr') == 'en' ? 'selected' : '' }}>English</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-12">
                                <div class="mb-3">
                                    <label class="form-label">Adresse</label>
                                    <input type="text" name="address" class="form-control" 
                                           value="{{ old('address', $user->address) }}" 
                                           placeholder="Votre adresse complète">
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-12">
                                <div class="mb-3">
                                    <label class="form-label">Bio / Description</label>
                                    <textarea name="bio" class="form-control" rows="4" 
                                              placeholder="Parlez-nous de vous...">{{ old('bio', $user->bio) }}</textarea>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Préférences -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-cog me-2"></i>Préférences
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Fuseau horaire</label>
                                <select name="timezone" class="form-select" form="profile-form">
                                    <option value="Africa/Abidjan" {{ old('timezone', $user->timezone ?? 'Africa/Abidjan') == 'Africa/Abidjan' ? 'selected' : '' }}>
                                        Abidjan (GMT+0)
                                    </option>
                                    <option value="Europe/Paris" {{ old('timezone', $user->timezone ?? 'Africa/Abidjan') == 'Europe/Paris' ? 'selected' : '' }}>
                                        Paris (GMT+1)
                                    </option>
                                    <option value="America/New_York" {{ old('timezone', $user->timezone ?? 'Africa/Abidjan') == 'America/New_York' ? 'selected' : '' }}>
                                        New York (GMT-5)
                                    </option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Format de date</label>
                                <select name="date_format" class="form-select" form="profile-form">
                                    <option value="d/m/Y" {{ old('date_format', $user->date_format ?? 'd/m/Y') == 'd/m/Y' ? 'selected' : '' }}>
                                        31/12/2024
                                    </option>
                                    <option value="Y-m-d" {{ old('date_format', $user->date_format ?? 'd/m/Y') == 'Y-m-d' ? 'selected' : '' }}>
                                        2024-12-31
                                    </option>
                                    <option value="m/d/Y" {{ old('date_format', $user->date_format ?? 'd/m/Y') == 'm/d/Y' ? 'selected' : '' }}>
                                        12/31/2024
                                    </option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Notifications -->
                    <div class="row">
                        <div class="col-12">
                            <h6 class="fw-bold mb-3">Notifications</h6>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-check mb-3">
                                        <input type="checkbox" name="notifications[email_orders]" 
                                               class="form-check-input" id="email_orders" 
                                               {{ old('notifications.email_orders', $user->notifications['email_orders'] ?? true) ? 'checked' : '' }}
                                               form="profile-form">
                                        <label class="form-check-label" for="email_orders">
                                            Nouvelles commandes par email
                                        </label>
                                    </div>
                                    <div class="form-check mb-3">
                                        <input type="checkbox" name="notifications[email_reports]" 
                                               class="form-check-input" id="email_reports"
                                               {{ old('notifications.email_reports', $user->notifications['email_reports'] ?? true) ? 'checked' : '' }}
                                               form="profile-form">
                                        <label class="form-check-label" for="email_reports">
                                            Rapports hebdomadaires par email
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-check mb-3">
                                        <input type="checkbox" name="notifications[sms_alerts]" 
                                               class="form-check-input" id="sms_alerts"
                                               {{ old('notifications.sms_alerts', $user->notifications['sms_alerts'] ?? false) ? 'checked' : '' }}
                                               form="profile-form">
                                        <label class="form-check-label" for="sms_alerts">
                                            Alertes SMS urgentes
                                        </label>
                                    </div>
                                    <div class="form-check mb-3">
                                        <input type="checkbox" name="notifications[browser_notifications]" 
                                               class="form-check-input" id="browser_notifications"
                                               {{ old('notifications.browser_notifications', $user->notifications['browser_notifications'] ?? true) ? 'checked' : '' }}
                                               form="profile-form">
                                        <label class="form-check-label" for="browser_notifications">
                                            Notifications navigateur
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="col-lg-4">
            <!-- Photo de profil -->
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="mb-0">
                        <i class="fas fa-camera me-2"></i>Photo de profil
                    </h6>
                </div>
                <div class="card-body text-center">
                    <div class="profile-avatar mb-3">
                        @if($user->avatar)
                            <img src="{{ asset('storage/' . $user->avatar) }}" alt="Avatar" class="avatar-img">
                        @else
                            <div class="avatar-placeholder">
                                {{ substr($user->name, 0, 2) }}
                            </div>
                        @endif
                    </div>
                    
                    <input type="file" name="avatar" id="avatar-input" class="d-none" 
                           accept="image/*" onchange="previewAvatar()" form="profile-form">
                    <button type="button" class="btn btn-outline-orange btn-sm" onclick="document.getElementById('avatar-input').click()">
                        <i class="fas fa-upload me-2"></i>Changer photo
                    </button>
                    
                    @if($user->avatar)
                        <button type="button" class="btn btn-outline-danger btn-sm ms-2" onclick="removeAvatar()">
                            <i class="fas fa-trash me-2"></i>Supprimer
                        </button>
                    @endif
                </div>
            </div>

            <!-- Informations du compte -->
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="mb-0">
                        <i class="fas fa-info-circle me-2"></i>Informations du compte
                    </h6>
                </div>
                <div class="card-body">
                    <div class="info-item">
                        <span class="info-label">Rôle :</span>
                        <span class="badge bg-danger">Administrateur</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Membre depuis :</span>
                        <span>{{ $user->created_at->format('d/m/Y') }}</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Dernière connexion :</span>
                        <span>{{ $user->last_login_at?->format('d/m/Y H:i') ?? 'Jamais' }}</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Statut :</span>
                        <span class="badge bg-success">Actif</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Email vérifié :</span>
                        @if($user->email_verified_at)
                            <span class="text-success">
                                <i class="fas fa-check-circle me-1"></i>Vérifié
                            </span>
                        @else
                            <span class="text-warning">
                                <i class="fas fa-exclamation-triangle me-1"></i>Non vérifié
                            </span>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Activité récente -->
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">
                        <i class="fas fa-history me-2"></i>Activité récente
                    </h6>
                </div>
                <div class="card-body">
                    <div class="activity-list">
                        <div class="activity-item">
                            <div class="activity-icon">
                                <i class="fas fa-sign-in-alt"></i>
                            </div>
                            <div class="activity-content">
                                <div class="activity-title">Connexion</div>
                                <div class="activity-time">{{ $user->last_login_at?->diffForHumans() ?? 'Première connexion' }}</div>
                            </div>
                        </div>
                        
                        @if($user->updated_at != $user->created_at)
                        <div class="activity-item">
                            <div class="activity-icon">
                                <i class="fas fa-edit"></i>
                            </div>
                            <div class="activity-content">
                                <div class="activity-title">Profil modifié</div>
                                <div class="activity-time">{{ $user->updated_at->diffForHumans() }}</div>
                            </div>
                        </div>
                        @endif
                        
                        <div class="activity-item">
                            <div class="activity-icon">
                                <i class="fas fa-user-plus"></i>
                            </div>
                            <div class="activity-content">
                                <div class="activity-title">Compte créé</div>
                                <div class="activity-time">{{ $user->created_at->diffForHumans() }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal changement mot de passe -->
    <div class="modal fade" id="changePasswordModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Changer le mot de passe</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="{{ route('admin.profile.password') }}" method="POST">
                    @csrf
                    @method('PATCH')
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Mot de passe actuel</label>
                            <input type="password" name="current_password" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Nouveau mot de passe</label>
                            <input type="password" name="password" class="form-control" required>
                            <div class="form-text">Au moins 8 caractères</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Confirmer le nouveau mot de passe</label>
                            <input type="password" name="password_confirmation" class="form-control" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-orange">Changer mot de passe</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('styles')
<style>
    .profile-avatar {
        position: relative;
        display: inline-block;
    }
    
    .avatar-img {
        width: 120px;
        height: 120px;
        border-radius: 50%;
        object-fit: cover;
        border: 4px solid #FF6B35;
    }
    
    .avatar-placeholder {
        width: 120px;
        height: 120px;
        border-radius: 50%;
        background: linear-gradient(135deg, #FF6B35, #fd7e14);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2.5rem;
        font-weight: bold;
        color: white;
        margin: 0 auto;
    }
    
    .info-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 8px 0;
        border-bottom: 1px solid #f0f0f0;
    }
    
    .info-item:last-child {
        border-bottom: none;
    }
    
    .info-label {
        font-weight: 600;
        color: #4a5568;
    }
    
    .activity-list {
        max-height: 300px;
        overflow-y: auto;
    }
    
    .activity-item {
        display: flex;
        align-items: flex-start;
        padding: 12px 0;
        border-bottom: 1px solid #f0f0f0;
    }
    
    .activity-item:last-child {
        border-bottom: none;
    }
    
    .activity-icon {
        width: 35px;
        height: 35px;
        border-radius: 50%;
        background: #f8fafc;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-right: 12px;
        color: #FF6B35;
    }
    
    .activity-title {
        font-weight: 600;
        color: #2d3748;
        margin-bottom: 2px;
    }
    
    .activity-time {
        font-size: 0.85rem;
        color: #718096;
    }
    
    .card {
        border: none;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        border-radius: 12px;
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
    
    .form-check-input:checked {
        background-color: #FF6B35;
        border-color: #FF6B35;
    }
    
    .form-check-input:focus {
        box-shadow: 0 0 0 0.2rem rgba(255, 107, 53, 0.25);
    }
</style>
@endpush

@push('scripts')
<script>
    function saveProfile() {
        document.getElementById('profile-form').submit();
    }
    
    function previewAvatar() {
        const input = document.getElementById('avatar-input');
        const file = input.files[0];
        
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                const avatarContainer = document.querySelector('.profile-avatar');
                avatarContainer.innerHTML = `
                    <img src="${e.target.result}" alt="Avatar" class="avatar-img">
                `;
            };
            reader.readAsDataURL(file);
        }
    }
    
    function removeAvatar() {
        if (confirm('Êtes-vous sûr de vouloir supprimer votre photo de profil ?')) {
            fetch('{{ route("admin.profile.avatar.remove") }}', {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const avatarContainer = document.querySelector('.profile-avatar');
                    const userName = '{{ $user->name }}';
                    const initials = userName.split(' ').map(n => n[0]).join('').substring(0, 2);
                    
                    avatarContainer.innerHTML = `
                        <div class="avatar-placeholder">
                            ${initials}
                        </div>
                    `;
                    
                    // Masquer le bouton supprimer
                    document.querySelector('.btn-outline-danger').style.display = 'none';
                    
                    alert('Photo de profil supprimée avec succès');
                } else {
                    alert('Erreur lors de la suppression');
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                alert('Erreur lors de la suppression');
            });
        }
    }
    
    // Validation du formulaire
    document.getElementById('profile-form').addEventListener('submit', function(e) {
        const email = document.querySelector('input[name="email"]').value;
        const name = document.querySelector('input[name="name"]').value;
        
        if (!email || !name) {
            e.preventDefault();
            alert('Veuillez remplir tous les champs obligatoires');
            return;
        }
        
        // Validation email
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(email)) {
            e.preventDefault();
            alert('Veuillez saisir une adresse email valide');
            return;
        }
    });
    
    // Auto-sauvegarde des préférences
    const preferences = document.querySelectorAll('select[name^="notifications"], input[type="checkbox"][name^="notifications"]');
    preferences.forEach(input => {
        input.addEventListener('change', function() {
            // Débounce pour éviter trop de requêtes
            clearTimeout(this.saveTimeout);
            this.saveTimeout = setTimeout(() => {
                savePreferences();
            }, 1000);
        });
    });
    
    function savePreferences() {
        const formData = new FormData(document.getElementById('profile-form'));
        
        fetch('{{ route("admin.profile.preferences") }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Afficher une notification discrète
                showNotification('Préférences sauvegardées', 'success');
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
        });
    }
    
    function showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = `alert alert-${type} position-fixed`;
        notification.style.cssText = `
            top: 20px;
            right: 20px;
            z-index: 9999;
            min-width: 300px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        `;
        notification.innerHTML = `
            <i class="fas fa-check-circle me-2"></i>
            ${message}
            <button type="button" class="btn-close" onclick="this.parentElement.remove()"></button>
        `;
        
        document.body.appendChild(notification);
        
        // Auto-suppression après 3 secondes
        setTimeout(() => {
            if (notification.parentElement) {
                notification.remove();
            }
        }, 3000);
    }
    
    // Gestion des messages flash
    @if(session('success'))
        showNotification('{{ session("success") }}', 'success');
    @endif
    
    @if(session('error'))
        showNotification('{{ session("error") }}', 'danger');
    @endif
</script>
@endpush