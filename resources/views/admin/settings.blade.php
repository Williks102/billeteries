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
    
    .settings-card {
        background: white;
        border-radius: 15px;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        border-left: 4px solid #FF6B35;
        padding: 2rem;
        margin-bottom: 2rem;
    }
    
    .settings-section {
        border-bottom: 1px solid #eee;
        padding-bottom: 2rem;
        margin-bottom: 2rem;
    }
    
    .settings-section:last-child {
        border-bottom: none;
        margin-bottom: 0;
    }
    
    .section-icon {
        width: 50px;
        height: 50px;
        background: linear-gradient(135deg, #FF6B35, #E55A2B);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 1.2rem;
        margin-right: 1rem;
        flex-shrink: 0;
    }
    
    .form-control, .form-select {
        border: 2px solid #ddd;
        border-radius: 8px;
        padding: 0.75rem;
        transition: all 0.3s ease;
    }
    
    .form-control:focus, .form-select:focus {
        border-color: #FF6B35;
        outline: none;
        box-shadow: 0 0 0 0.2rem rgba(255, 107, 53, 0.25);
    }
    
    .form-label {
        font-weight: 600;
        color: #333;
        margin-bottom: 0.5rem;
    }
    
    .help-text {
        font-size: 0.875rem;
        color: #666;
        margin-top: 0.25rem;
    }
    
    .toggle-switch {
        position: relative;
        display: inline-block;
        width: 60px;
        height: 34px;
    }
    
    .toggle-switch input {
        opacity: 0;
        width: 0;
        height: 0;
    }
    
    .slider {
        position: absolute;
        cursor: pointer;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background-color: #ccc;
        transition: .4s;
        border-radius: 34px;
    }
    
    .slider:before {
        position: absolute;
        content: "";
        height: 26px;
        width: 26px;
        left: 4px;
        bottom: 4px;
        background-color: white;
        transition: .4s;
        border-radius: 50%;
    }
    
    input:checked + .slider {
        background-color: #FF6B35;
    }
    
    input:checked + .slider:before {
        transform: translateX(26px);
    }
    
    .current-value {
        font-weight: 600;
        color: #FF6B35;
        font-size: 1.1rem;
    }
    
    .range-labels {
        display: flex;
        justify-content: between;
        font-size: 0.875rem;
        color: #666;
        margin-top: 0.5rem;
    }
    
    .maintenance-mode {
        background: linear-gradient(135deg, #dc3545, #c82333);
        color: white;
        padding: 1rem;
        border-radius: 10px;
        margin-bottom: 1rem;
    }
    
    .backup-item {
        background: #f8f9fa;
        border: 1px solid #dee2e6;
        border-radius: 8px;
        padding: 1rem;
        margin-bottom: 0.5rem;
        display: flex;
        justify-content: between;
        align-items: center;
    }
    
    .backup-size {
        color: #666;
        font-size: 0.875rem;
    }
    
    .notification-preview {
        background: #f8f9fa;
        border: 1px solid #dee2e6;
        border-radius: 8px;
        padding: 1rem;
        margin-top: 1rem;
    }
    
    .email-template {
        font-family: Arial, sans-serif;
        font-size: 0.875rem;
        line-height: 1.4;
    }
</style>
@endpush

@section('content')
<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h1 class="mb-2">
                <i class="fas fa-cogs me-3"></i>
                Paramètres Système
            </h1>
            <p class="mb-0 opacity-75">Configuration générale de la plateforme</p>
        </div>
        <div>
            <button class="btn btn-black btn-lg" onclick="saveAllSettings()">
                <i class="fas fa-save me-2"></i>Sauvegarder Tout
            </button>
        </div>
    </div>
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show" role="alert">
    <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

<!-- Paramètres généraux -->
<div class="settings-card">
    <div class="settings-section">
        <div class="d-flex align-items-start mb-4">
            <div class="section-icon">
                <i class="fas fa-globe"></i>
            </div>
            <div class="flex-grow-1">
                <h5 class="mb-2">Paramètres Généraux</h5>
                <p class="text-muted">Configuration de base de la plateforme</p>
            </div>
        </div>
        
        <form method="POST" action="{{ route('admin.settings.update') }}">
            @csrf
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Email de contact</label>
                        <input type="email" class="form-control" name="contact_email" value="{{ $settings['contact_email'] ?? 'contact@billetterie-ci.com' }}">
                        <div class="help-text">Email principal pour les notifications</div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Téléphone support</label>
                        <input type="tel" class="form-control" name="support_phone" value="{{ $settings['support_phone'] ?? '+225 XX XX XX XX' }}">
                        <div class="help-text">Numéro affiché sur le site</div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Fuseau horaire</label>
                        <select class="form-select" name="timezone">
                            <option value="Africa/Abidjan" selected>Abidjan (GMT+0)</option>
                            <option value="Europe/Paris">Paris (GMT+1)</option>
                            <option value="UTC">UTC (GMT+0)</option>
                        </select>
                    </div>
                </div>
            </div>
            
            <button type="submit" class="btn btn-black">
                <i class="fas fa-save me-2"></i>Enregistrer
            </button>
        </form>
    </div>

    <!-- Paramètres financiers -->
    <div class="settings-section">
        <div class="d-flex align-items-start mb-4">
            <div class="section-icon">
                <i class="fas fa-euro-sign"></i>
            </div>
            <div class="flex-grow-1">
                <h5 class="mb-2">Paramètres Financiers</h5>
                <p class="text-muted">Configuration des commissions et paiements</p>
            </div>
        </div>
        
        <form method="POST" action="{{ route('admin.settings.financial') }}">
            @csrf
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Taux de commission (%)</label>
                        <input type="number" class="form-control" name="commission_rate" 
                               value="{{ $settings['commission_rate'] ?? 10 }}" 
                               min="0" max="50" step="0.1">
                        <div class="current-value">
                            Actuellement : {{ $settings['commission_rate'] ?? 10 }}%
                        </div>
                        <div class="help-text">Pourcentage prélevé sur chaque vente</div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Commission minimale (FCFA)</label>
                        <input type="number" class="form-control" name="min_commission" 
                               value="{{ $settings['min_commission'] ?? 500 }}" min="0">
                        <div class="help-text">Commission minimum par transaction</div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Frais de service (FCFA)</label>
                        <input type="number" class="form-control" name="service_fee" 
                               value="{{ $settings['service_fee'] ?? 200 }}" min="0">
                        <div class="help-text">Frais fixes par billet vendu</div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Devise</label>
                        <select class="form-select" name="currency">
                            <option value="XOF" selected>Franc CFA (FCFA)</option>
                            <option value="EUR">Euro (€)</option>
                            <option value="USD">Dollar US ($)</option>
                        </select>
                    </div>
                </div>
            </div>
            
            <button type="submit" class="btn btn-black">
                <i class="fas fa-save me-2"></i>Enregistrer
            </button>
        </form>
    </div>

    <!-- Paramètres des événements -->
    <div class="settings-section">
        <div class="d-flex align-items-start mb-4">
            <div class="section-icon">
                <i class="fas fa-calendar-alt"></i>
            </div>
            <div class="flex-grow-1">
                <h5 class="mb-2">Paramètres des Événements</h5>
                <p class="text-muted">Règles de validation et limites</p>
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-6">
                <div class="mb-3">
                    <label class="form-label">Validation automatique</label>
                    <div class="d-flex align-items-center">
                        <label class="toggle-switch me-3">
                            <input type="checkbox" name="auto_approve" 
                                   {{ ($settings['auto_approve'] ?? false) ? 'checked' : '' }}>
                            <span class="slider"></span>
                        </label>
                        <span>Approuver automatiquement les nouveaux événements</span>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="mb-3">
                    <label class="form-label">Capacité maximale par défaut</label>
                    <input type="number" class="form-control" name="default_capacity" 
                           value="{{ $settings['default_capacity'] ?? 1000 }}" min="1">
                    <div class="help-text">Limite par défaut si non spécifiée</div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="mb-3">
                    <label class="form-label">Délai d'annulation (heures)</label>
                    <input type="number" class="form-control" name="cancellation_deadline" 
                           value="{{ $settings['cancellation_deadline'] ?? 24 }}" min="1">
                    <div class="help-text">Heures avant l'événement pour annuler</div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="mb-3">
                    <label class="form-label">Limite d'événements par promoteur</label>
                    <input type="number" class="form-control" name="events_limit" 
                           value="{{ $settings['events_limit'] ?? 50 }}" min="1">
                    <div class="help-text">Nombre maximum d'événements simultanés</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Paramètres de notification -->
    <div class="settings-section">
        <div class="d-flex align-items-start mb-4">
            <div class="section-icon">
                <i class="fas fa-bell"></i>
            </div>
            <div class="flex-grow-1">
                <h5 class="mb-2">Notifications</h5>
                <p class="text-muted">Configuration des emails et SMS</p>
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-6">
                <h6>Notifications Email</h6>
                <div class="mb-2">
                    <label class="toggle-switch me-3">
                        <input type="checkbox" name="email_new_order" checked>
                        <span class="slider"></span>
                    </label>
                    <span>Nouvelle commande</span>
                </div>
                <div class="mb-2">
                    <label class="toggle-switch me-3">
                        <input type="checkbox" name="email_new_event" checked>
                        <span class="slider"></span>
                    </label>
                    <span>Nouvel événement</span>
                </div>
                <div class="mb-2">
                    <label class="toggle-switch me-3">
                        <input type="checkbox" name="email_payment_failed">
                        <span class="slider"></span>
                    </label>
                    <span>Paiement échoué</span>
                </div>
            </div>
            <div class="col-md-6">
                <h6>Notifications SMS</h6>
                <div class="mb-2">
                    <label class="toggle-switch me-3">
                        <input type="checkbox" name="sms_ticket_purchased">
                        <span class="slider"></span>
                    </label>
                    <span>Billet acheté</span>
                </div>
                <div class="mb-2">
                    <label class="toggle-switch me-3">
                        <input type="checkbox" name="sms_event_reminder">
                        <span class="slider"></span>
                    </label>
                    <span>Rappel d'événement</span>
                </div>
                <div class="mb-3">
                    <label class="form-label">Fournisseur SMS</label>
                    <select class="form-select" name="sms_provider">
                        <option value="none">Désactivé</option>
                        <option value="orange">Orange SMS API</option>
                        <option value="mtn">MTN SMS</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <!-- Mode maintenance -->
    <div class="settings-section">
        <div class="d-flex align-items-start mb-4">
            <div class="section-icon">
                <i class="fas fa-tools"></i>
            </div>
            <div class="flex-grow-1">
                <h5 class="mb-2">Mode Maintenance</h5>
                <p class="text-muted">Contrôle de l'accès au site</p>
            </div>
        </div>
        
        @if($settings['maintenance_mode'] ?? false)
        <div class="maintenance-mode">
            <i class="fas fa-exclamation-triangle me-2"></i>
            <strong>Le site est actuellement en mode maintenance</strong>
        </div>
        @endif
        
        <div class="row">
            <div class="col-md-6">
                <div class="mb-3">
                    <label class="toggle-switch me-3">
                        <input type="checkbox" name="maintenance_mode" 
                               {{ ($settings['maintenance_mode'] ?? false) ? 'checked' : '' }}>
                        <span class="slider"></span>
                    </label>
                    <span>Activer le mode maintenance</span>
                </div>
            </div>
            <div class="col-md-6">
                <div class="mb-3">
                    <label class="form-label">Message de maintenance</label>
                    <textarea class="form-control" name="maintenance_message" rows="3">{{ $settings['maintenance_message'] ?? 'Site en maintenance. Nous reviendrons bientôt !' }}</textarea>
                </div>
            </div>
        </div>
    </div>

    <!-- Sauvegarde -->
    <div class="settings-section">
        <div class="d-flex align-items-start mb-4">
            <div class="section-icon">
                <i class="fas fa-database"></i>
            </div>
            <div class="flex-grow-1">
                <h5 class="mb-2">Sauvegarde & Données</h5>
                <p class="text-muted">Gestion des sauvegardes automatiques</p>
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-6">
                <button class="btn btn-black mb-3" onclick="createBackup()">
                    <i class="fas fa-download me-2"></i>Créer une sauvegarde
                </button>
                
                <h6>Sauvegardes récentes</h6>
                <div class="backup-item">
                    <div>
                        <strong>backup_2025_07_14.sql</strong>
                        <div class="backup-size">Créée le 14/07/2025 - 2.3 MB</div>
                    </div>
                    <div>
                        <a href="#" class="btn btn-sm btn-outline-dark me-2">
                            <i class="fas fa-download"></i>
                        </a>
                        <button class="btn btn-sm btn-outline-danger">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="mb-3">
                    <label class="form-label">Sauvegarde automatique</label>
                    <select class="form-select" name="auto_backup">
                        <option value="never">Jamais</option>
                        <option value="daily" selected>Quotidienne</option>
                        <option value="weekly">Hebdomadaire</option>
                        <option value="monthly">Mensuelle</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Conserver (nombre de sauvegardes)</label>
                    <input type="number" class="form-control" name="backup_retention" 
                           value="{{ $settings['backup_retention'] ?? 30 }}" min="1" max="365">
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function saveAllSettings() {
        // Sauvegarder tous les formulaires de paramètres
        const forms = document.querySelectorAll('form');
        let saved = 0;
        
        forms.forEach(form => {
            // Ici vous pourriez envoyer chaque formulaire via AJAX
            saved++;
        });
        
        if (saved > 0) {
            alert('Tous les paramètres ont été sauvegardés !');
        }
    }
    
    function createBackup() {
        if (confirm('Créer une nouvelle sauvegarde ? Cette opération peut prendre quelques minutes.')) {
            // Appel AJAX pour créer la sauvegarde
            fetch('{{ route("admin.backup.create") }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Content-Type': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Sauvegarde créée avec succès !');
                    location.reload();
                } else {
                    alert('Erreur lors de la création de la sauvegarde.');
                }
            });
        }
    }
    
    // Mise à jour en temps réel du taux de commission
    document.querySelector('input[name="commission_rate"]').addEventListener('input', function(e) {
        document.querySelector('.current-value').textContent = `Actuellement : ${e.target.value}%`;
    });
</script>
@endpushmb-3">
                        <label class="form-label">Nom de la plateforme</label>
                        <input type="text" class="form-control" name="app_name" value="{{ config('app.name') }}">
                        <div class="help-text">Le nom affiché sur le site et dans les emails</div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="