{{-- resources/views/tickets/authenticated-verify.blade.php --}}
{{-- Vue authentifiée pour promoteurs/admins avec actions de scan --}}

@extends('layouts.app')

@section('title', 'Scanner de Billets - Vérification')

@section('head')
<meta name="csrf-token" content="{{ csrf_token() }}">
@endsection

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="card shadow-lg border-0">
                <div class="card-header bg-gradient-success text-white text-center py-4">
                    <h2 class="mb-0">
                        <i class="fas fa-qrcode me-3"></i>
                        Scanner Authentifié
                    </h2>
                    <p class="mb-0 mt-2 opacity-90">Vérification et validation des billets</p>
                    <div class="mt-2">
                        <span class="badge bg-light text-dark">
                            <i class="fas fa-user me-1"></i>{{ auth()->user()->name }}
                        </span>
                        <span class="badge bg-light text-dark">
                            <i class="fas fa-shield-alt me-1"></i>{{ ucfirst(auth()->user()->role) }}
                        </span>
                    </div>
                </div>
                
                <div class="card-body p-5">
                    @if($ticket && !$error)
                        {{-- BILLET TROUVÉ --}}
                        
                        {{-- Statut principal --}}
                        <div class="text-center mb-4">
                            @if($info['can_be_used'])
                                <div class="alert alert-success border-0 shadow-sm">
                                    <i class="fas fa-check-circle fa-3x mb-3 text-success"></i>
                                    <h4 class="text-success mb-3">✅ Billet Prêt pour Scan</h4>
                                    <p class="mb-0">{{ $info['status_message'] }}</p>
                                </div>
                            @elseif($info['status'] === 'used')
                                <div class="alert alert-info border-0 shadow-sm">
                                    <i class="fas fa-check-double fa-3x mb-3 text-info"></i>
                                    <h4 class="text-info mb-3">ℹ️ Billet Déjà Utilisé</h4>
                                    <p class="mb-0">{{ $info['status_message'] }}</p>
                                </div>
                            @else
                                <div class="alert alert-warning border-0 shadow-sm">
                                    <i class="fas fa-exclamation-triangle fa-3x mb-3 text-warning"></i>
                                    <h4 class="text-warning mb-3">⚠️ Billet Non Utilisable</h4>
                                    <p class="mb-0">{{ $info['status_message'] }}</p>
                                </div>
                            @endif
                        </div>

                        {{-- Informations détaillées --}}
                        <div class="row mb-4">
                            {{-- Informations événement --}}
                            <div class="col-md-6">
                                <div class="card bg-light border-0 h-100">
                                    <div class="card-body">
                                        <h6 class="text-primary mb-3">
                                            <i class="fas fa-calendar-alt me-2"></i>Événement
                                        </h6>
                                        
                                        <div class="mb-2">
                                            <strong>{{ $info['event']['title'] }}</strong>
                                        </div>
                                        
                                        <div class="text-muted small mb-2">
                                            <div><i class="fas fa-clock me-1"></i> {{ $info['event']['date'] }}</div>
                                            <div><i class="fas fa-map-marker-alt me-1"></i> {{ $info['event']['location'] }}</div>
                                        </div>
                                        
                                        <div class="d-flex align-items-center">
                                            <span class="badge bg-secondary me-2">{{ $info['ticket_type'] }}</span>
                                            <span class="badge bg-primary">{{ ucfirst($info['event']['status']) }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            {{-- Informations porteur --}}
                            <div class="col-md-6">
                                <div class="card bg-light border-0 h-100">
                                    <div class="card-body">
                                        <h6 class="text-primary mb-3">
                                            <i class="fas fa-user me-2"></i>Porteur du Billet
                                        </h6>
                                        
                                        <div class="mb-2">
                                            <strong>{{ $info['holder']['name'] }}</strong>
                                        </div>
                                        
                                        <div class="text-muted small mb-2">
                                            <div><i class="fas fa-envelope me-1"></i> {{ $info['holder']['email'] }}</div>
                                        </div>
                                        
                                        @if($info['order'])
                                            <div class="mt-2">
                                                <small class="text-muted">
                                                    Commande #{{ $info['order']['number'] }}<br>
                                                    {{ $info['order']['date'] }}
                                                </small>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Informations de scan --}}
                        @if($info['used_at'])
                            <div class="alert alert-secondary border-0 mb-4">
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-history fa-2x me-3"></i>
                                    <div>
                                        <h6 class="mb-1">Historique d'utilisation</h6>
                                        <p class="mb-0">Utilisé le {{ $info['used_at'] }}</p>
                                    </div>
                                </div>
                            </div>
                        @endif

                        {{-- Actions de scan --}}
                        @if($can_scan)
                            <div class="text-center">
                                <a href="{{ route('promoteur.scanner.index') }}" class="btn btn-outline-secondary me-3">
                                    <i class="fas fa-arrow-left me-2"></i>Retour au Scanner
                                </a>
                                
                                @if($info['can_be_used'])
                                    <button id="scanBtn" class="btn btn-success btn-lg" onclick="scanTicket()">
                                        <i class="fas fa-qrcode me-2"></i>Marquer comme Utilisé
                                    </button>
                                @else
                                    <button class="btn btn-secondary" disabled>
                                        <i class="fas fa-ban me-2"></i>
                                        @if($info['status'] === 'used')
                                            Déjà Utilisé
                                        @else
                                            Non Scannable
                                        @endif
                                    </button>
                                @endif
                            </div>
                        @endif

                    @else
                        {{-- BILLET NON TROUVÉ OU ERREUR --}}
                        <div class="text-center py-5">
                            <div class="alert alert-danger border-0 shadow-sm">
                                <i class="fas fa-times-circle fa-3x mb-3 text-danger"></i>
                                <h4 class="text-danger mb-3">Erreur</h4>
                                <p class="mb-0">{{ $error ?? 'Une erreur est survenue.' }}</p>
                                @if(isset($ticket_code))
                                    <small class="text-muted d-block mt-2">Code recherché: <code>{{ $ticket_code }}</code></small>
                                @endif
                            </div>

                            <a href="{{ route('promoteur.scanner.index') }}" class="btn btn-primary">
                                <i class="fas fa-arrow-left me-2"></i>Retour au Scanner
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

{{-- JavaScript pour les actions de scan --}}
@if($ticket && $info['can_be_used'] && $can_scan)
<script>
function scanTicket() {
    if (!confirm('Confirmer le scan de ce billet ? Cette action est irréversible.')) {
        return;
    }

    const button = document.getElementById('scanBtn');
    const originalHTML = button.innerHTML;
    
    // Désactiver le bouton
    button.disabled = true;
    button.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Scan en cours...';

    // Vérifier le token CSRF
    const csrfToken = document.querySelector('meta[name="csrf-token"]');
    if (!csrfToken) {
        alert('Erreur de configuration (token CSRF manquant)');
        button.disabled = false;
        button.innerHTML = originalHTML;
        return;
    }

    // Envoyer la requête de scan
    fetch('{{ route("api.authenticated-scan") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken.getAttribute('content'),
            'Accept': 'application/json'
        },
        body: JSON.stringify({
            ticket_code: '{{ $ticket->ticket_code }}'
        })
    })
    .then(response => {
        if (!response.ok) {
            return response.json().then(data => {
                throw new Error(data.message || `Erreur HTTP ${response.status}`);
            });
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            // Afficher le succès
            const successAlert = document.createElement('div');
            successAlert.className = 'alert alert-success alert-dismissible fade show mt-3';
            successAlert.innerHTML = `
                <i class="fas fa-check-circle me-2"></i>
                <strong>Scan Réussi !</strong> ${data.message}
                <div class="mt-2 small">
                    Scanné le ${new Date().toLocaleString('fr-FR')}
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            
            // Insérer l'alerte
            const actionsDiv = button.closest('.text-center');
            actionsDiv.parentNode.insertBefore(successAlert, actionsDiv);
            
            // Mettre à jour le bouton
            button.classList.remove('btn-success');
            button.classList.add('btn-secondary');
            button.innerHTML = '<i class="fas fa-check me-2"></i>Déjà Utilisé';
            button.disabled = true;
            
            // Recharger après 3 secondes
            setTimeout(() => {
                location.reload();
            }, 3000);
        } else {
            throw new Error(data.message || 'Erreur de scan');
        }
    })
    .catch(error => {
        console.error('Erreur scan:', error);
        
        // Afficher l'erreur
        const errorAlert = document.createElement('div');
        errorAlert.className = 'alert alert-danger alert-dismissible fade show mt-3';
        errorAlert.innerHTML = `
            <i class="fas fa-exclamation-triangle me-2"></i>
            <strong>Erreur :</strong> ${error.message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        
        // Insérer l'alerte
        const actionsDiv = button.closest('.text-center');
        actionsDiv.parentNode.insertBefore(errorAlert, actionsDiv);
        
        // Auto-supprimer après 5 secondes
        setTimeout(() => {
            errorAlert.remove();
        }, 5000);
    })
    .finally(() => {
        // Réactiver le bouton si pas de succès
        if (!button.disabled || button.innerHTML.includes('spinner')) {
            button.disabled = false;
            button.innerHTML = originalHTML;
        }
    });
}
</script>
@endif

@push('styles')
<style>
.bg-gradient-success {
    background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
}

.card {
    border-radius: 15px;
}

.alert {
    border-radius: 12px;
}

.badge {
    font-size: 0.85em;
}

code {
    background: #f8f9fa;
    padding: 2px 4px;
    border-radius: 3px;
    font-size: 0.9em;
}

.card.bg-light {
    background: #f8f9fa !important;
}

.btn-lg {
    padding: 0.75rem 2rem;
    font-size: 1.1rem;
}

@media (max-width: 768px) {
    .card-body {
        padding: 2rem !important;
    }
    
    .btn-lg {
        padding: 0.5rem 1.5rem;
        font-size: 1rem;
    }
}
</style>
@endpush
@endsection