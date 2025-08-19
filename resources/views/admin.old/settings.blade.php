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

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i>{{ session('error') }}
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
                    <form action="{{ route('admin.settings.update') }}" method="POST">
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
                                <select class="form-select @error('currency') is-invalid @enderror" 
                                        id="currency" name="currency" required>
                                    <option value="FCFA" {{ ($settings['currency'] ?? 'FCFA') == 'FCFA' ? 'selected' : '' }}>Franc CFA (FCFA)</option>
                                    <option value="EUR" {{ ($settings['currency'] ?? 'FCFA') == 'EUR' ? 'selected' : '' }}>Euro (EUR)</option>
                                    <option value="USD" {{ ($settings['currency'] ?? 'FCFA') == 'USD' ? 'selected' : '' }}>Dollar US (USD)</option>
                                </select>
                                @error('currency')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <label for="timezone" class="form-label fw-semibold">Fuseau horaire</label>
                            <select class="form-select @error('timezone') is-invalid @enderror" 
                                    id="timezone" name="timezone" required>
                                <option value="Africa/Abidjan" {{ ($settings['timezone'] ?? 'Africa/Abidjan') == 'Africa/Abidjan' ? 'selected' : '' }}>Africa/Abidjan (GMT+0)</option>
                                <option value="Europe/Paris" {{ ($settings['timezone'] ?? 'Africa/Abidjan') == 'Europe/Paris' ? 'selected' : '' }}>Europe/Paris (GMT+1)</option>
                                <option value="America/New_York" {{ ($settings['timezone'] ?? 'Africa/Abidjan') == 'America/New_York' ? 'selected' : '' }}>America/New_York (GMT-5)</option>
                            </select>
                            @error('timezone')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
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
                                
                                <div class="mb-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="auto_approval_events" 
                                               name="auto_approval_events" value="1" 
                                               {{ ($settings['auto_approval_events'] ?? false) ? 'checked' : '' }}>
                                        <label class="form-check-label fw-semibold" for="auto_approval_events">
                                            Approbation automatique des événements
                                        </label>
                                    </div>
                                    <small class="text-muted">Publier automatiquement les nouveaux événements</small>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="email_notifications" 
                                               name="email_notifications" value="1" 
                                               {{ ($settings['email_notifications'] ?? true) ? 'checked' : '' }}>
                                        <label class="form-check-label fw-semibold" for="email_notifications">
                                            Notifications Email
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
                        <div class="col-md-4 text-center mb-3">
                            <div class="payment-provider p-3 border rounded">
                                <i class="fab fa-cc-visa fa-2x text-primary mb-2"></i>
                                <h6>Carte bancaire</h6>
                                <span class="badge bg-success">Activé</span>
                            </div>
                        </div>
                        
                        <div class="col-md-4 text-center mb-3">
                            <div class="payment-provider p-3 border rounded">
                                <i class="fas fa-mobile-alt fa-2x text-orange mb-2"></i>
                                <h6>Mobile Money</h6>
                                <span class="badge bg-warning">En attente</span>
                            </div>
                        </div>
                        
                        <div class="col-md-4 text-center mb-3">
                            <div class="payment-provider p-3 border rounded">
                                <i class="fas fa-university fa-2x text-info mb-2"></i>
                                <h6>Virement bancaire</h6>
                                <span class="badge bg-secondary">Désactivé</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Configuration Email -->
            <div class="card mb-4">
                <div class="card-header bg-white border-bottom">
                    <h5 class="mb-0">
                        <i class="fas fa-envelope text-orange me-2"></i>
                        Configuration Email
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Serveur SMTP</label>
                                <input type="text" class="form-control" value="{{ env('MAIL_HOST', 'smtp.gmail.com') }}" readonly>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Port</label>
                                <input type="text" class="form-control" value="{{ env('MAIL_PORT', '587') }}" readonly>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Encryption</label>
                                <input type="text" class="form-control" value="{{ env('MAIL_ENCRYPTION', 'TLS') }}" readonly>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Statut</label>
                                <div class="form-control d-flex align-items-center">
                                    <span class="badge {{ $systemStats['mail_configured']['status'] == 'configured' ? 'bg-success' : 'bg-warning' }} me-2">
                                        <i class="{{ $systemStats['mail_configured']['icon'] }}"></i>
                                    </span>
                                    {{ $systemStats['mail_configured']['message'] }}
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
                        <div class="info-row d-flex justify-content-between mb-2">
                            <span class="info-label">Version PHP</span>
                            <span class="info-value badge bg-light text-dark">{{ $systemStats['php_version'] ?? 'N/A' }}</span>
                        </div>
                        
                        <div class="info-row d-flex justify-content-between mb-2">
                            <span class="info-label">Version Laravel</span>
                            <span class="info-value badge bg-light text-dark">{{ $systemStats['laravel_version'] ?? 'N/A' }}</span>
                        </div>
                        
                        <div class="info-row d-flex justify-content-between mb-2">
                            <span class="info-label">Limite mémoire</span>
                            <span class="info-value badge bg-light text-dark">{{ $systemStats['memory_limit'] ?? 'N/A' }}</span>
                        </div>
                        
                        <div class="info-row d-flex justify-content-between mb-2">
                            <span class="info-label">Upload max</span>
                            <span class="info-value badge bg-light text-dark">{{ $systemStats['upload_max_filesize'] ?? 'N/A' }}</span>
                        </div>
                        
                        <hr>
                        
                        <div class="info-row d-flex justify-content-between mb-2">
                            <span class="info-label">Base de données</span>
                            <span class="info-value">
                                <i class="{{ $systemStats['database_connection']['icon'] }}"></i>
                                {{ $systemStats['database_connection']['message'] }}
                            </span>
                        </div>
                        
                        <div class="info-row d-flex justify-content-between mb-2">
                            <span class="info-label">Storage</span>
                            <span class="info-value">
                                <i class="{{ $systemStats['storage_writable']['icon'] }}"></i>
                                {{ $systemStats['storage_writable']['message'] }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Statistiques rapides -->
            <div class="card mb-4">
                <div class="card-header bg-white border-bottom">
                    <h5 class="mb-0">
                        <i class="fas fa-chart-bar text-orange me-2"></i>
                        Statistiques rapides
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-6 mb-3">
                            <div class="stat-item">
                                <div class="stat-number h4 mb-1 text-orange">{{ number_format($systemStats['total_users']) }}</div>
                                <div class="stat-label text-muted small">Utilisateurs</div>
                            </div>
                        </div>
                        
                        <div class="col-6 mb-3">
                            <div class="stat-item">
                                <div class="stat-number h4 mb-1 text-orange">{{ number_format($systemStats['total_events']) }}</div>
                                <div class="stat-label text-muted small">Événements</div>
                            </div>
                        </div>
                        
                        <div class="col-6 mb-3">
                            <div class="stat-item">
                                <div class="stat-number h4 mb-1 text-orange">{{ number_format($systemStats['total_orders']) }}</div>
                                <div class="stat-label text-muted small">Commandes</div>
                            </div>
                        </div>
                        
                        <div class="col-6 mb-3">
                            <div class="stat-item">
                                <div class="stat-number h4 mb-1 text-orange">{{ number_format($systemStats['total_revenue']) }}</div>
                                <div class="stat-label text-muted small">Revenus (FCFA)</div>
                            </div>
                        </div>
                    </div>
                    
                    @if($systemStats['pending_commissions'] > 0)
                        <div class="alert alert-warning alert-sm mt-3">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <strong>{{ $systemStats['pending_commissions'] }}</strong> commission(s) en attente
                        </div>
                    @endif
                </div>
            </div>

            <!-- Utilisation du disque -->
            <div class="card mb-4">
                <div class="card-header bg-white border-bottom">
                    <h5 class="mb-0">
                        <i class="fas fa-hdd text-orange me-2"></i>
                        Utilisation du disque
                    </h5>
                </div>
                <div class="card-body">
                    <div class="mb-2">
                        <div class="d-flex justify-content-between">
                            <span>Espace utilisé</span>
                            <span class="fw-bold">{{ $systemStats['disk_usage']['used_percentage'] }}%</span>
                        </div>
                        <div class="progress" style="height: 8px;">
                            <div class="progress-bar 
                                {{ $systemStats['disk_usage']['used_percentage'] > 80 ? 'bg-danger' : 
                                   ($systemStats['disk_usage']['used_percentage'] > 60 ? 'bg-warning' : 'bg-success') }}" 
                                 style="width: {{ $systemStats['disk_usage']['used_percentage'] }}%"></div>
                        </div>
                    </div>
                    
                    <div class="small text-muted">
                        <div>Libre: {{ $systemStats['disk_usage']['free'] }}</div>
                        <div>Total: {{ $systemStats['disk_usage']['total'] }}</div>
                    </div>
                </div>
            </div>

            <!-- Actions rapides -->
            <div class="card">
                <div class="card-header bg-white border-bottom">
                    <h5 class="mb-0">
                        <i class="fas fa-tools text-orange me-2"></i>
                        Actions rapides
                    </h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <button type="button" class="btn btn-outline-info btn-sm" onclick="clearCache()">
                            <i class="fas fa-broom me-2"></i>Vider le cache
                        </button>
                        
                        <button type="button" class="btn btn-outline-success btn-sm" onclick="optimizeDatabase()">
                            <i class="fas fa-database me-2"></i>Optimiser la BDD
                        </button>
                        
                        <button type="button" class="btn btn-outline-warning btn-sm" onclick="generateReport()">
                            <i class="fas fa-file-alt me-2"></i>Rapport système
                        </button>
                        
                        <hr class="my-2">
                        
                        <a href="{{ route('admin.dashboard') }}" class="btn btn-outline-primary btn-sm">
                            <i class="fas fa-arrow-left me-2"></i>Retour au dashboard
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    /**
     * Test d'envoi d'email
     */
    function testEmail() {
        const btn = event.target.closest('button');
        const originalText = btn.innerHTML;
        
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Envoi en cours...';
        
        fetch('{{ route("admin.settings.test-email") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showAlert('success', data.message);
            } else {
                showAlert('danger', data.message);
            }
        })
        .catch(error => {
            showAlert('danger', 'Erreur lors du test d\'email');
            console.error('Error:', error);
        })
        .finally(() => {
            btn.disabled = false;
            btn.innerHTML = originalText;
        });
    }
    
    /**
     * Sauvegarde système
     */
    function backupSystem() {
        const btn = event.target.closest('button');
        const originalText = btn.innerHTML;
        
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Sauvegarde...';
        
        fetch('{{ route("admin.settings.backup") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showAlert('success', data.message);
            } else {
                showAlert('danger', data.message);
            }
        })
        .catch(error => {
            showAlert('danger', 'Erreur lors de la sauvegarde');
            console.error('Error:', error);
        })
        .finally(() => {
            btn.disabled = false;
            btn.innerHTML = originalText;
        });
    }
    
    /**
     * Vider le cache
     */
    function clearCache() {
        const btn = event.target.closest('button');
        const originalText = btn.innerHTML;
        
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Nettoyage...';
        
        fetch('{{ route("admin.settings.clear-cache") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showAlert('success', 'Cache vidé avec succès');
            } else {
                showAlert('danger', 'Erreur lors du vidage du cache');
            }
        })
        .catch(error => {
            showAlert('danger', 'Erreur lors du vidage du cache');
            console.error('Error:', error);
        })
        .finally(() => {
            btn.disabled = false;
            btn.innerHTML = originalText;
        });
    }
    
    /**
     * Optimiser la base de données
     */
    function optimizeDatabase() {
        if (!confirm('Êtes-vous sûr de vouloir optimiser la base de données ?')) return;
        
        const btn = event.target.closest('button');
        const originalText = btn.innerHTML;
        
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Optimisation...';
        
        // Simulation - remplacez par votre endpoint réel
        setTimeout(() => {
            showAlert('success', 'Base de données optimisée avec succès');
            btn.disabled = false;
            btn.innerHTML = originalText;
        }, 2000);
    }
    
    /**
     * Générer un rapport système
     */
    function generateReport() {
        const btn = event.target.closest('button');
        const originalText = btn.innerHTML;
        
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Génération...';
        
        // Simulation - remplacez par votre endpoint réel
        setTimeout(() => {
            showAlert('info', 'Rapport système généré et envoyé par email');
            btn.disabled = false;
            btn.innerHTML = originalText;
        }, 1500);
    }
    
    /**
     * Afficher une alerte
     */
    function showAlert(type, message) {
        const alertHtml = `
            <div class="alert alert-${type} alert-dismissible fade show" role="alert">
                <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'danger' ? 'exclamation-circle' : 'info-circle'} me-2"></i>
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `;
        
        // Insérer l'alerte en haut de la page
        const content = document.querySelector('.container-fluid');
        const tempDiv = document.createElement('div');
        tempDiv.innerHTML = alertHtml;
        content.insertBefore(tempDiv.firstElementChild, content.firstElementChild);
        
        // Auto-remove après 5 secondes
        setTimeout(() => {
            const alert = content.querySelector('.alert');
            if (alert) {
                alert.remove();
            }
        }, 5000);
    }
</script>
@endpush

@push('styles')
<style>
    .payment-provider {
        transition: all 0.3s ease;
        cursor: pointer;
    }
    
    .payment-provider:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    }
    
    .system-info .info-row {
        padding: 8px 0;
        border-bottom: 1px solid #f0f0f0;
    }
    
    .system-info .info-row:last-child {
        border-bottom: none;
    }
    
    .stat-item {
        padding: 10px;
        background: #f8f9fa;
        border-radius: 8px;
        transition: all 0.3s ease;
    }
    
    .stat-item:hover {
        background: #e9ecef;
        transform: translateY(-1px);
    }
    
    .btn-orange {
        background-color: #FF6B35;
        border-color: #FF6B35;
        color: white;
    }
    
    .btn-orange:hover {
        background-color: #e55a2b;
        border-color: #e55a2b;
        color: white;
    }
    
    .btn-outline-orange {
        border-color: #FF6B35;
        color: #FF6B35;
    }
    
    .btn-outline-orange:hover {
        background-color: #FF6B35;
        border-color: #FF6B35;
        color: white;
    }
    
    .text-orange {
        color: #FF6B35 !important;
    }
    
    .alert-sm {
        padding: 0.5rem 0.75rem;
        font-size: 0.875rem;
    }
    
    .progress {
        background-color: #e9ecef;
        border-radius: 0.375rem;
    }
    
    .progress-bar {
        transition: width 0.6s ease;
    }
    
    .badge {
        font-size: 0.75em;
    }
    
    .card-header {
        background-color: #fff !important;
        border-bottom: 1px solid #dee2e6;
    }
    
    .form-check-input:checked {
        background-color: #FF6B35;
        border-color: #FF6B35;
    }
    
    .form-check-input:focus {
        border-color: #FF6B35;
        box-shadow: 0 0 0 0.25rem rgba(255, 107, 53, 0.25);
    }
    
    .info-label {
        font-weight: 500;
        color: #6c757d;
    }
    
    .info-value {
        font-weight: 600;
    }
    
    .system-info hr {
        margin: 0.75rem 0;
        opacity: 0.5;
    }
    
    /* Animations */
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(-10px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    .alert {
        animation: fadeIn 0.3s ease-out;
    }
    
    /* Responsive adjustments */
    @media (max-width: 768px) {
        .d-flex.gap-2 {
            flex-direction: column;
        }
        
        .d-flex.gap-2 .btn {
            margin-bottom: 0.5rem;
        }
        
        .stat-item {
            margin-bottom: 1rem;
        }
        
        .info-row {
            flex-direction: column;
            text-align: center;
        }
        
        .info-row .info-value {
            margin-top: 0.25rem;
        }
    }
    
    /* Loading states */
    .btn:disabled {
        opacity: 0.7;
        cursor: not-allowed;
    }
    
    .fa-spinner {
        animation: spin 1s linear infinite;
    }
    
    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
    
    /* Custom scrollbar for system info */
    .system-info {
        max-height: 300px;
        overflow-y: auto;
    }
    
    .system-info::-webkit-scrollbar {
        width: 4px;
    }
    
    .system-info::-webkit-scrollbar-track {
        background: #f1f1f1;
        border-radius: 2px;
    }
    
    .system-info::-webkit-scrollbar-thumb {
        background: #FF6B35;
        border-radius: 2px;
    }
    
    .system-info::-webkit-scrollbar-thumb:hover {
        background: #e55a2b;
    }
</style>
@endpush