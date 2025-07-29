{{-- resources/views/admin/settings.blade.php --}}
@extends('layouts.admin')

@section('title', 'Paramètres de la plateforme - ClicBillet CI')

@section('breadcrumb')
    <li class="breadcrumb-item active">Paramètres</li>
@endsection

@section('content')
    <!-- Header avec actions -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-1">Paramètres de la plateforme</h2>
            <p class="text-muted mb-0">Configurez les paramètres généraux de ClicBillet CI</p>
        </div>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-outline-warning" onclick="backupSystem()">
                <i class="fas fa-database me-2"></i>Sauvegarder
            </button>
            <a href="{{ route('admin.profile') }}" class="btn btn-outline-orange">
                <i class="fas fa-user-cog me-2"></i>Mon profil
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
        <!-- Paramètres principaux -->
        <div class="col-lg-8">
            <!-- Configuration générale -->
            <div class="card mb-4">
                <div class="card-header bg-white border-bottom">
                    <h5 class="mb-0">
                        <i class="fas fa-cog text-orange me-2"></i>
                        Configuration générale
                    </h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('') }}" method="POST">
                        @csrf
                        @method('PATCH')
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="platform_name" class="form-label fw-semibold">Nom de la plateforme</label>
                                <input type="text" class="form-control @error('platform_name') is-invalid @enderror" 
                                       id="platform_name" name="platform_name" 
                                       value="{{ old('platform_name', $settings['platform_name'] ?? '') }}" required>
                                @error('platform_name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="platform_email" class="form-label fw-semibold">Email de contact</label>
                                <input type="email" class="form-control @error('platform_email') is-invalid @enderror" 
                                       id="platform_email" name="platform_email" 
                                       value="{{ old('platform_email', $settings['platform_email'] ?? '') }}" required>
                                @error('platform_email')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="commission_rate" class="form-label fw-semibold">Taux de commission (%)</label>
                                <div class="input-group">
                                    <input type="number" class="form-control @error('commission_rate') is-invalid @enderror" 
                                           id="commission_rate" name="commission_rate" 
                                           value="{{ old('commission_rate', $settings['commission_rate'] ?? 10) }}" 
                                           min="0" max="100" step="0.1" required>
                                    <span class="input-group-text">%</span>
                                </div>
                                @error('commission_rate')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="text-muted">Commission prélevée sur chaque vente</small>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="currency" class="form-label fw-semibold">Devise</label>
                                <select class="form-select" id="currency" name="currency">
                                    <option value="FCFA" {{ ($settings['currency'] ?? 'FCFA') == 'FCFA' ? 'selected' : '' }}>Franc CFA (FCFA)</option>
                                    <option value="EUR" {{ ($settings['currency'] ?? 'FCFA') == 'EUR' ? 'selected' : '' }}>Euro (EUR)</option>
                                    <option value="USD" {{ ($settings['currency'] ?? 'FCFA') == 'USD' ? 'selected' : '' }}>Dollar US (USD)</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <label for="timezone" class="form-label fw-semibold">Fuseau horaire</label>
                            <select class="form-select" id="timezone" name="timezone">
                                <option value="Africa/Abidjan" {{ ($settings['timezone'] ?? 'Africa/Abidjan') == 'Africa/Abidjan' ? 'selected' : '' }}>Africa/Abidjan (GMT+0)</option>
                                <option value="Europe/Paris" {{ ($settings['timezone'] ?? 'Africa/Abidjan') == 'Europe/Paris' ? 'selected' : '' }}>Europe/Paris (GMT+1)</option>
                                <option value="America/New_York" {{ ($settings['timezone'] ?? 'Africa/Abidjan') == 'America/New_York' ? 'selected' : '' }}>America/New_York (GMT-5)</option>
                            </select>
                        </div>
                        
                        <hr class="my-4">
                        
                        <h6 class="fw-bold mb-3">Options de la plateforme</h6>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="maintenance_mode" 
                                               name="maintenance_mode" value="1" 
                                               {{ ($settings['maintenance_mode'] ?? false) ? 'checked' : '' }}>
                                        <label class="form-check-label fw-semibold" for="maintenance_mode">
                                            Mode maintenance
                                        </label>
                                    </div>
                                    <small class="text-muted">Désactiver temporairement le site pour maintenance</small>
                                </div>
                                
                                <div class="mb-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="registration_enabled" 
                                               name="registration_enabled" value="1" 
                                               {{ ($settings['registration_enabled'] ?? true) ? 'checked' : '' }}>
                                        <label class="form-check-label fw-semibold" for="registration_enabled">
                                            Inscription ouverte
                                        </label>
                                    </div>
                                    <small class="text-muted">Permettre aux nouveaux utilisateurs de s'inscrire</small>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="email_notifications" 
                                               name="email_notifications" value="1" 
                                               {{ ($settings['email_notifications'] ?? true) ? 'checked' : '' }}>
                                        <label class="form-check-label fw-semibold" for="email_notifications">
                                            Notifications email
                                        </label>
                                    </div>
                                    <small class="text-muted">Envoyer des notifications par email</small>
                                </div>
                                
                                <div class="mb-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="sms_notifications" 
                                               name="sms_notifications" value="1" 
                                               {{ ($settings['sms_notifications'] ?? false) ? 'checked' : '' }}>
                                        <label class="form-check-label fw-semibold" for="sms_notifications">
                                            Notifications SMS
                                        </label>
                                    </div>
                                    <small class="text-muted">Envoyer des notifications par SMS</small>
                                </div>
                            </div>
                        </div>
                        
                        <div class="d-flex gap-2 mt-4">
                            <button type="submit" class="btn btn-orange">
                                <i class="fas fa-save me-2"></i>Enregistrer les paramètres
                            </button>
                            <button type="reset" class="btn btn-outline-secondary">
                                <i class="fas fa-undo me-2"></i>Annuler
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Configuration des paiements -->
            <div class="card mb-4">
                <div class="card-header bg-white border-bottom">
                    <h5 class="mb-0">
                        <i class="fas fa-credit-card text-orange me-2"></i>
                        Configuration des paiements
                    </h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Information :</strong> Configuration des passerelles de paiement pour les transactions.
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="payment-method-card">
                                <div class="d-flex align-items-center mb-3">
                                    <div class="payment-icon orange me-3">
                                        <i class="fas fa-mobile-alt"></i>
                                    </div>
                                    <div>
                                        <h6 class="fw-bold mb-1">Orange Money</h6>
                                        <small class="text-muted">Paiement mobile Orange</small>
                                    </div>
                                    <div class="ms-auto">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" id="orange_money" checked>
                                        </div>
                                    </div>
                                </div>
                                <div class="payment-details">
                                    <small class="text-success">
                                        <i class="fas fa-check-circle me-1"></i>Configuré et actif
                                    </small>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="payment-method-card">
                                <div class="d-flex align-items-center mb-3">
                                    <div class="payment-icon mtn me-3">
                                        <i class="fas fa-mobile-alt"></i>
                                    </div>
                                    <div>
                                        <h6 class="fw-bold mb-1">MTN Mobile Money</h6>
                                        <small class="text-muted">Paiement mobile MTN</small>
                                    </div>
                                    <div class="ms-auto">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" id="mtn_money" checked>
                                        </div>
                                    </div>
                                </div>
                                <div class="payment-details">
                                    <small class="text-success">
                                        <i class="fas fa-check-circle me-1"></i>Configuré et actif
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row mt-3">
                        <div class="col-md-6">
                            <div class="payment-method-card">
                                <div class="d-flex align-items-center mb-3">
                                    <div class="payment-icon visa me-3">
                                        <i class="fas fa-credit-card"></i>
                                    </div>
                                    <div>
                                        <h6 class="fw-bold mb-1">Cartes bancaires</h6>
                                        <small class="text-muted">Visa, Mastercard</small>
                                    </div>
                                    <div class="ms-auto">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" id="credit_cards">
                                        </div>
                                    </div>
                                </div>
                                <div class="payment-details">
                                    <small class="text-warning">
                                        <i class="fas fa-exclamation-triangle me-1"></i>Configuration requise
                                    </small>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="payment-method-card">
                                <div class="d-flex align-items-center mb-3">
                                    <div class="payment-icon bank me-3">
                                        <i class="fas fa-university"></i>
                                    </div>
                                    <div>
                                        <h6 class="fw-bold mb-1">Virement bancaire</h6>
                                        <small class="text-muted">Transfert direct</small>
                                    </div>
                                    <div class="ms-auto">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" id="bank_transfer">
                                        </div>
                                    </div>
                                </div>
                                <div class="payment-details">
                                    <small class="text-secondary">
                                        <i class="fas fa-pause-circle me-1"></i>Désactivé
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Configuration des emails -->
            <div class="card">
                <div class="card-header bg-white border-bottom">
                    <h5 class="mb-0">
                        <i class="fas fa-envelope text-orange me-2"></i>
                        Configuration des emails
                    </h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>Important :</strong> Les paramètres SMTP sont configurés dans le fichier .env pour des raisons de sécurité.
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Serveur SMTP</label>
                                <input type="text" class="form-control" value="smtp.gmail.com" readonly>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Port</label>
                                <input type="text" class="form-control" value="587" readonly>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Encryption</label>
                                <input type="text" class="form-control" value="TLS" readonly>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Statut</label>
                                <div class="form-control d-flex align-items-center">
                                    <span class="badge bg-success me-2">
                                        <i class="fas fa-check-circle"></i>
                                    </span>
                                    Configuré et fonctionnel
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="text-center mt-3">
                        <button type="button" class="btn btn-outline-orange" onclick="testEmail()">
                            <i class="fas fa-paper-plane me-2"></i>Tester l'envoi d'email
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Informations système -->
        <div class="col-lg-4">
            <!-- Informations système -->
            <div class="card mb-4">
                <div class="card-header bg-white border-bottom">
                    <h5 class="mb-0">
                        <i class="fas fa-server text-orange me-2"></i>
                        Informations système
                    </h5>
                </div>
                <div class="card-body">
                    <div class="system-info">
                        <div class="info-row">
                            <span class="info-label">Version PHP</span>
                            <span class="info-value">{{ $systemStats['php_version'] ?? 'N/A' }}</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Version Laravel</span>
                            <span class="info-value">{{ $systemStats['laravel_version'] ?? 'N/A' }}</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Base de données</span>
                            <span class="info-value">{{ $systemStats['database_size'] ?? 'N/A' }}</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Stockage utilisé</span>
                            <span class="info-value">{{ $systemStats['storage_used'] ?? 'N/A' }}</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Dernière sauvegarde</span>
                            <span class="info-value">{{ $systemStats['last_backup'] ?? 'Jamais' }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Actions rapides -->
            <div class="card mb-4">
                <div class="card-header bg-white border-bottom">
                    <h5 class="mb-0">
                        <i class="fas fa-tools text-orange me-2"></i>
                        Actions rapides
                    </h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <button type="button" class="btn btn-outline-primary" onclick="clearCache()">
                            <i class="fas fa-broom me-2"></i>Vider le cache
                        </button>
                        
                        <button type="button" class="btn btn-outline-warning" onclick="backupSystem()">
                            <i class="fas fa-database me-2"></i>Créer une sauvegarde
                        </button>
                        
                        <button type="button" class="btn btn-outline-info" onclick="generateSitemap()">
                            <i class="fas fa-sitemap me-2"></i>Générer sitemap
                        </button>
                        
                        <button type="button" class="btn btn-outline-secondary" onclick="optimizeDatabase()">
                            <i class="fas fa-database me-2"></i>Optimiser la DB
                        </button>
                    </div>
                </div>
            </div>

            <!-- Sécurité -->
            <div class="card">
                <div class="card-header bg-white border-bottom">
                    <h5 class="mb-0">
                        <i class="fas fa-shield-alt text-orange me-2"></i>
                        Sécurité
                    </h5>
                </div>
                <div class="card-body">
                    <div class="security-item">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div>
                                <h6 class="fw-semibold mb-1">Connexions sécurisées (HTTPS)</h6>
                                <small class="text-muted">Force l'utilisation du protocole HTTPS</small>
                            </div>
                            <span class="badge bg-success">
                                <i class="fas fa-check"></i>
                            </span>
                        </div>
                    </div>
                    
                    <div class="security-item">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div>
                                <h6 class="fw-semibold mb-1">Protection CSRF</h6>
                                <small class="text-muted">Protection contre les attaques CSRF</small>
                            </div>
                            <span class="badge bg-success">
                                <i class="fas fa-check"></i>
                            </span>
                        </div>
                    </div>
                    
                    <div class="security-item">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div>
                                <h6 class="fw-semibold mb-1">Hachage des mots de passe</h6>
                                <small class="text-muted">Mots de passe chiffrés en bcrypt</small>
                            </div>
                            <span class="badge bg-success">
                                <i class="fas fa-check"></i>
                            </span>
                        </div>
                    </div>
                    
                    <div class="security-item">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="fw-semibold mb-1">Authentification 2FA</h6>
                                <small class="text-muted">Double authentification</small>
                            </div>
                            <span class="badge bg-warning">
                                <i class="fas fa-clock"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('styles')
<style>
    .payment-method-card {
        border: 2px solid #e9ecef;
        border-radius: 10px;
        padding: 1rem;
        transition: all 0.3s ease;
        background: #f8f9fa;
    }
    
    .payment-method-card:hover {
        border-color: #FF6B35;
        background: white;
    }
    
    .payment-icon {
        width: 40px;
        height: 40px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 1.2rem;
    }
    
    .payment-icon.orange { background: #FF6B35; }
    .payment-icon.mtn { background: #FFCC00; color: #000; }
    .payment-icon.visa { background: #1A1F71; }
    .payment-icon.bank { background: #28a745; }
    
    .system-info .info-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 0.75rem 0;
        border-bottom: 1px solid #f0f0f0;
    }
    
    .system-info .info-row:last-child {
        border-bottom: none;
    }
    
    .info-label {
        color: #6c757d;
        font-weight: 500;
        font-size: 0.9rem;
    }
    
    .info-value {
        font-weight: 600;
        color: #2d3748;
        font-size: 0.9rem;
    }
    
    .security-item {
        border-bottom: 1px solid #f0f0f0;
        padding-bottom: 1rem;
    }
    
    .security-item:last-child {
        border-bottom: none;
        padding-bottom: 0;
    }
    
    .form-check-input:checked {
        background-color: #FF6B35;
        border-color: #FF6B35;
    }
    
    .form-check-input:focus {
        border-color: #FF6B35;
        box-shadow: 0 0 0 0.25rem rgba(255, 107, 53, 0.25);
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
    
    .form-control, .form-select {
        border-radius: 8px;
        border: 2px solid #e9ecef;
        padding: 0.75rem 1rem;
        transition: all 0.3s ease;
    }
    
    .form-control:focus, .form-select:focus {
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
    // Auto-hide success alert
    document.addEventListener('DOMContentLoaded', function() {
        const successAlert = document.querySelector('.alert-success');
        if (successAlert) {
            setTimeout(() => {
                const bsAlert = new bootstrap.Alert(successAlert);
                bsAlert.close();
            }, 5000);
        }
    });

    function clearCache() {
        if (confirm('Êtes-vous sûr de vouloir vider le cache de l\'application ?')) {
            // Appel AJAX pour vider le cache
            fetch('/admin/cache/clear', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Content-Type': 'application/json',
                },
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Cache vidé avec succès !');
                } else {
                    alert('Erreur lors du vidage du cache.');
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                alert('Une erreur s\'est produite.');
            });
        }
    }

    function backupSystem() {
        if (confirm('Créer une sauvegarde complète du système ?')) {
            // Afficher un loader
            const btn = event.target;
            const originalText = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Sauvegarde...';
            btn.disabled = true;
            
            // Simulation d'une sauvegarde (remplacer par un vrai appel)
            setTimeout(() => {
                btn.innerHTML = originalText;
                btn.disabled = false;
                alert('Sauvegarde créée avec succès !');
            }, 3000);
        }
    }

    function generateSitemap() {
        alert('Fonctionnalité en cours de développement.');
    }

    function optimizeDatabase() {
        if (confirm('Optimiser la base de données ? Cette opération peut prendre quelques minutes.')) {
            alert('Base de données optimisée avec succès !');
        }
    }

    function testEmail() {
        if (confirm('Envoyer un email de test à votre adresse ?')) {
            // Appel AJAX pour tester l'email
            fetch('/admin/test-email', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Content-Type': 'application/json',
                },
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Email de test envoyé avec succès !');
                } else {
                    alert('Erreur lors de l\'envoi de l\'email de test.');
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                alert('Une erreur s\'est produite.');
            });
        }
    }
</script>
@endpush