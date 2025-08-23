{{-- resources/views/admin/events/intervention-dashboard.blade.php --}}
@extends('layouts.admin')

@section('title', 'Interventions Requises')

@section('content')
<div class="container-fluid px-4">
    <!-- En-tête -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">
                <i class="fas fa-exclamation-triangle me-2 text-danger"></i>
                Interventions Requises
            </h1>
            <p class="text-muted mb-0">Événements nécessitant une assistance administrative</p>
        </div>
        <div>
            <a href="{{ route('admin.events.create-hybrid') }}" class="btn btn-warning me-2">
                <i class="fas fa-magic me-2"></i>Création Hybride
            </a>
            <a href="{{ route('admin.events.index') }}" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-2"></i>Retour
            </a>
        </div>
    </div>

    <!-- Alertes de priorité -->
    @php
        $urgentEvents = $eventsNeedingIntervention->filter(function($event) {
            return $event->event_date <= now()->addDays(2);
        });
        $criticalEvents = $eventsNeedingIntervention->filter(function($event) {
            return $event->status === 'published' && $event->ticketTypes->count() === 0;
        });
    @endphp

    @if($urgentEvents->count() > 0)
        <div class="alert alert-danger d-flex align-items-center mb-4">
            <i class="fas fa-fire fa-2x me-3"></i>
            <div>
                <h5 class="alert-heading mb-1">🚨 URGENT - {{ $urgentEvents->count() }} événements dans moins de 48h</h5>
                <p class="mb-0">Ces événements ont lieu très bientôt et nécessitent une intervention immédiate !</p>
            </div>
        </div>
    @endif

    @if($criticalEvents->count() > 0)
        <div class="alert alert-warning d-flex align-items-center mb-4">
            <i class="fas fa-exclamation-circle fa-2x me-3"></i>
            <div>
                <h5 class="alert-heading mb-1">⚠️ CRITIQUE - {{ $criticalEvents->count() }} événements publiés sans billets</h5>
                <p class="mb-0">Ces événements sont visibles par le public mais n'ont aucun billet disponible à la vente.</p>
            </div>
        </div>
    @endif

    <!-- Statistiques rapides -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card text-center border-danger">
                <div class="card-body">
                    <h3 class="text-danger">{{ $eventsNeedingIntervention->count() }}</h3>
                    <p class="mb-0">Total Interventions</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center border-warning">
                <div class="card-body">
                    <h3 class="text-warning">{{ $urgentEvents->count() }}</h3>
                    <p class="mb-0">Urgents (< 48h)</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center border-info">
                <div class="card-body">
                    <h3 class="text-info">{{ $criticalEvents->count() }}</h3>
                    <p class="mb-0">Publiés sans billets</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center border-success">
                <div class="card-body">
                    <h3 class="text-success">
                        {{ $eventsNeedingIntervention->filter(function($event) {
                            return $event->management_mode === 'admin';
                        })->count() }}
                    </h3>
                    <p class="mb-0">Déjà en mode admin</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Liste des événements -->
    @if($eventsNeedingIntervention->count() > 0)
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-list me-2"></i>Événements Nécessitant une Intervention
                </h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Événement</th>
                                <th>Promoteur</th>
                                <th>Date</th>
                                <th>Problème(s)</th>
                                <th>Priorité</th>
                                <th>Mode Actuel</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($eventsNeedingIntervention as $event)
                                @php
                                    $issues = [];
                                    $priorityLevel = 'normal';
                                    $priorityColor = 'secondary';
                                    
                                    // Analyser les problèmes
                                    if ($event->status === 'published' && $event->ticketTypes->count() === 0) {
                                        $issues[] = 'Publié sans billets';
                                        $priorityLevel = 'critique';
                                        $priorityColor = 'danger';
                                    }
                                    
                                    if ($event->event_date <= now()->addDays(2)) {
                                        $issues[] = 'Événement imminent';
                                        if ($priorityLevel !== 'critique') {
                                            $priorityLevel = 'urgent';
                                            $priorityColor = 'warning';
                                        }
                                    }
                                    
                                    if (!$event->ticketTypes->where('is_active', true)->count()) {
                                        $issues[] = 'Aucun billet actif';
                                    }
                                    
                                    if ($event->promoteur && $event->promoteur->last_login_at < now()->subDays(7)) {
                                        $issues[] = 'Promoteur inactif (7j+)';
                                    }
                                @endphp
                                
                                <tr class="@if($priorityLevel === 'critique') table-danger @elseif($priorityLevel === 'urgent') table-warning @endif">
                                    <td>
                                        <div class="d-flex align-items-center">
                                            @if($event->image)
                                                <img src="{{ Storage::url($event->image) }}" 
                                                     alt="{{ $event->title }}" 
                                                     class="rounded me-3" 
                                                     style="width: 50px; height: 50px; object-fit: cover;">
                                            @else
                                                <div class="bg-light rounded me-3 d-flex align-items-center justify-content-center" 
                                                     style="width: 50px; height: 50px;">
                                                    <i class="fas fa-calendar text-muted"></i>
                                                </div>
                                            @endif
                                            <div>
                                                <h6 class="mb-1">{{ $event->title }}</h6>
                                                <small class="text-muted">{{ $event->category->name ?? 'Sans catégorie' }}</small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div>
                                            <strong>{{ $event->promoteur->name ?? 'N/A' }}</strong><br>
                                            <small class="text-muted">{{ $event->promoteur->email ?? 'N/A' }}</small>
                                            @if($event->promoteur && $event->promoteur->last_login_at)
                                                <br><small class="text-muted">
                                                    Dernière connexion : {{ $event->promoteur->last_login_at->diffForHumans() }}
                                                </small>
                                            @endif
                                        </div>
                                    </td>
                                    <td>
                                        <div>
                                            <strong>{{ $event->event_date->format('d/m/Y') }}</strong><br>
                                            <small class="text-muted">{{ $event->event_date->format('H:i') }}</small>
                                            <br><small class="badge badge-sm bg-{{ $event->event_date->diffInDays(now()) <= 2 ? 'danger' : 'secondary' }}">
                                                {{ $event->event_date->diffForHumans() }}
                                            </small>
                                        </div>
                                    </td>
                                    <td>
                                        @foreach($issues as $issue)
                                            <span class="badge bg-danger mb-1 d-block">{{ $issue }}</span>
                                        @endforeach
                                    </td>
                                    <td>
                                        <span class="badge bg-{{ $priorityColor }} fs-6">
                                            @if($priorityLevel === 'critique')
                                                🔴 CRITIQUE
                                            @elseif($priorityLevel === 'urgent') 
                                                🟠 URGENT
                                            @else
                                                🟡 NORMAL
                                            @endif
                                        </span>
                                    </td>
                                    <td>
                                        @php
                                            $modeConfig = match($event->management_mode) {
                                                'admin' => ['color' => 'danger', 'text' => 'Admin'],
                                                'collaborative' => ['color' => 'warning', 'text' => 'Collaboratif'],
                                                'promoter' => ['color' => 'success', 'text' => 'Promoteur'],
                                                default => ['color' => 'secondary', 'text' => 'Inconnu']
                                            };
                                        @endphp
                                        <span class="badge bg-{{ $modeConfig['color'] }}">{{ $modeConfig['text'] }}</span>
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <!-- Action principale selon le problème -->
                                            @if($event->management_mode !== 'admin')
                                                <button class="btn btn-sm btn-danger" 
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#takeControlModal"
                                                        data-event-id="{{ $event->id }}"
                                                        data-event-title="{{ $event->title }}">
                                                    <i class="fas fa-user-shield"></i> Prendre le contrôle
                                                </button>
                                            @endif
                                            
                                            @if($event->ticketTypes->count() === 0)
                                                <a href="{{ route('admin.events.manage-tickets', $event) }}" 
                                                   class="btn btn-sm btn-primary">
                                                    <i class="fas fa-ticket-alt"></i> Créer billets
                                                </a>
                                            @endif
                                            
                                            <a href="{{ route('admin.events.manage-hybrid', $event) }}" 
                                               class="btn btn-sm btn-info">
                                                <i class="fas fa-cogs"></i> Gérer
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Pagination -->
        <div class="d-flex justify-content-center mt-4">
            {{ $eventsNeedingIntervention->links() }}
        </div>
    @else
        <!-- Aucune intervention nécessaire -->
        <div class="card">
            <div class="card-body text-center py-5">
                <i class="fas fa-check-circle fa-4x text-success mb-3"></i>
                <h3>Excellent ! Aucune intervention requise</h3>
                <p class="text-muted">Tous les événements sont correctement configurés et gérés par leurs promoteurs.</p>
                <a href="{{ route('admin.events.index') }}" class="btn btn-primary">
                    <i class="fas fa-eye me-2"></i>Voir tous les événements
                </a>
            </div>
        </div>
    @endif
</div>

<!-- Modal : Prendre le contrôle -->
<div class="modal fade" id="takeControlModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="takeControlForm" method="POST">
                @csrf
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title">
                        <i class="fas fa-user-shield me-2"></i>Prendre le Contrôle - Mode Admin
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>Attention :</strong> Vous allez prendre le contrôle complet de l'événement 
                        "<span id="eventTitleInModal"></span>". Le promoteur sera notifié de cette action.
                    </div>

                    <input type="hidden" name="management_mode" value="admin">

                    <div class="mb-3">
                        <label class="form-label">Raison de l'intervention <span class="text-danger">*</span></label>
                        <select class="form-select mb-2" id="reasonPreset" onchange="updateReasonText()">
                            <option value="">Sélectionner une raison prédéfinie...</option>
                            <option value="technical_help">Aide technique - Promoteur en difficulté</option>
                            <option value="urgent_event">Événement urgent - Intervention nécessaire</option>
                            <option value="published_no_tickets">Événement publié sans billets</option>
                            <option value="inactive_promoter">Promoteur inactif depuis plusieurs jours</option>
                            <option value="customer_complaint">Suite à une plainte client</option>
                            <option value="system_error">Correction d'erreur système</option>
                            <option value="custom">Autre raison (personnalisée)</option>
                        </select>
                        
                        <textarea name="reason" id="reasonText" class="form-control" rows="3" required 
                                  placeholder="Décrivez précisément pourquoi vous prenez le contrôle..."></textarea>
                        <small class="text-muted">Cette information sera visible dans l'historique et envoyée au promoteur</small>
                    </div>

                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="notifyPromoter" checked>
                        <label class="form-check-label" for="notifyPromoter">
                            Notifier le promoteur par email
                        </label>
                    </div>

                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="createTicketsImmediately">
                        <label class="form-check-label" for="createTicketsImmediately">
                            Créer immédiatement des billets après la prise de contrôle
                        </label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-user-shield me-2"></i>Confirmer la Prise de Contrôle
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Actions groupées (si plusieurs événements sélectionnés) -->
<div class="modal fade" id="bulkActionsModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Actions Groupées</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Appliquer une action à <span id="selectedCount">0</span> événements sélectionnés :</p>
                
                <div class="d-grid gap-2">
                    <button class="btn btn-danger" onclick="bulkTakeControl()">
                        <i class="fas fa-user-shield me-2"></i>Prendre le contrôle de tous
                    </button>
                    <button class="btn btn-primary" onclick="bulkCreateTickets()">
                        <i class="fas fa-ticket-alt me-2"></i>Créer des billets pour tous
                    </button>
                    <button class="btn btn-warning" onclick="bulkNotifyPromoters()">
                        <i class="fas fa-envelope me-2"></i>Notifier tous les promoteurs
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.table-danger td {
    border-color: #f5c6cb !important;
}

.table-warning td {
    border-color: #ffeaa7 !important;
}

.badge-sm {
    font-size: 0.7rem;
}

.btn-group .btn {
    white-space: nowrap;
}

@media (max-width: 768px) {
    .btn-group {
        flex-direction: column;
    }
    
    .btn-group .btn {
        margin-bottom: 0.25rem;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Configuration du modal de prise de contrôle
    const takeControlModal = document.getElementById('takeControlModal');
    const takeControlForm = document.getElementById('takeControlForm');
    const eventTitleSpan = document.getElementById('eventTitleInModal');

    takeControlModal.addEventListener('show.bs.modal', function(event) {
        const button = event.relatedTarget;
        const eventId = button.getAttribute('data-event-id');
        const eventTitle = button.getAttribute('data-event-title');
        
        eventTitleSpan.textContent = eventTitle;
        takeControlForm.action = `/admin/events/${eventId}/change-management-mode`;
    });

    // Soumission du formulaire de prise de contrôle
    takeControlForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        const createTickets = document.getElementById('createTicketsImmediately').checked;
        
        fetch(this.action, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Fermer le modal
                bootstrap.Modal.getInstance(takeControlModal).hide();
                
                // Rediriger selon l'option choisie
                if (createTickets) {
                    window.location.href = data.redirect_create_tickets;
                } else {
                    window.location.href = data.redirect_manage;
                }
            } else {
                alert('Erreur : ' + (data.message || 'Une erreur est survenue'));
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            alert('Une erreur est survenue lors de la prise de contrôle');
        });
    });
});

// Fonction pour mettre à jour le texte de raison selon le preset
function updateReasonText() {
    const preset = document.getElementById('reasonPreset').value;
    const textArea = document.getElementById('reasonText');
    
    const presetTexts = {
        'technical_help': 'Intervention pour aider le promoteur qui rencontre des difficultés techniques dans la configuration de son événement.',
        'urgent_event': 'Prise de contrôle d\'urgence car l\'événement a lieu très prochainement et nécessite une configuration immédiate.',
        'published_no_tickets': 'L\'événement est publié et visible par le public mais aucun billet n\'est disponible à la vente.',
        'inactive_promoter': 'Le promoteur est inactif depuis plusieurs jours et ne répond pas à nos sollicitations.',
        'customer_complaint': 'Intervention suite à une plainte client concernant la disponibilité des billets.',
        'system_error': 'Correction d\'une erreur système qui empêche le bon fonctionnement de l\'événement.',
        'custom': ''
    };
    
    if (presetTexts[preset] !== undefined) {
        textArea.value = presetTexts[preset];
    }
}

// Actions groupées (futures fonctionnalités)
function bulkTakeControl() {
    alert('Fonctionnalité en développement : Prise de contrôle groupée');
}

function bulkCreateTickets() {
    alert('Fonctionnalité en développement : Création de billets groupée');
}

function bulkNotifyPromoters() {
    alert('Fonctionnalité en développement : Notification groupée des promoteurs');
}

// Auto-refresh de la page toutes les 5 minutes pour voir les nouveaux problèmes
setInterval(function() {
    if (document.visibilityState === 'visible') {
        location.reload();
    }
}, 300000); // 5 minutes
</script>
@endsection