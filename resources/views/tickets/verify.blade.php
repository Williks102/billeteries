@extends('layouts.app')

@section('title')
    @if($ticket)
        Vérification - {{ $ticket->ticket_code }}
    @else
        Billet non trouvé
    @endif
@endsection

@section('content')
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow-lg border-0 rounded-3">
                {{-- Header --}}
                <div class="card-header text-center py-4" style="background: linear-gradient(135deg, #FF6B35, #E55A2B); color: white;">
                    <h2 class="mb-0">
                        <i class="fas fa-qrcode me-2"></i>
                        Vérification de Billet
                    </h2>
                    <p class="mb-0 mt-2 opacity-90">Système de validation ClicBillet CI</p>
                </div>

                <div class="card-body p-4">
                    @if($ticket && !$error)
                        {{-- BILLET TROUVÉ --}}
                        
                        {{-- Statut du billet --}}
                        <div class="text-center mb-4">
                            @if($info['is_valid'])
                                <div class="alert alert-success border-0 shadow-sm">
                                    <i class="fas fa-check-circle fa-2x mb-2 text-success"></i>
                                    <h4 class="text-success mb-2">✅ Billet Valide</h4>
                                    <p class="mb-0">{{ $info['status_message'] }}</p>
                                </div>
                            @elseif($ticket->used_at)
                                <div class="alert alert-warning border-0 shadow-sm">
                                    <i class="fas fa-exclamation-triangle fa-2x mb-2 text-warning"></i>
                                    <h4 class="text-warning mb-2">⚠️ Billet Déjà Utilisé</h4>
                                    <p class="mb-0">{{ $info['status_message'] }}</p>
                                </div>
                            @else
                                <div class="alert alert-danger border-0 shadow-sm">
                                    <i class="fas fa-times-circle fa-2x mb-2 text-danger"></i>
                                    <h4 class="text-danger mb-2">❌ Billet Non Valide</h4>
                                    <p class="mb-0">{{ $info['status_message'] }}</p>
                                </div>
                            @endif
                        </div>

                        {{-- Code du billet --}}
                        <div class="text-center mb-4">
                            <div class="bg-light rounded-3 p-3">
                                <label class="fw-bold text-muted small text-uppercase">Code Billet</label>
                                <div class="h4 fw-bold text-primary font-monospace">{{ $ticket->ticket_code }}</div>
                            </div>
                        </div>

                        {{-- INFORMATIONS DE L'ÉVÉNEMENT --}}
                        <div class="card bg-light border-0 mb-4">
                            <div class="card-body">
                                <h5 class="card-title text-primary mb-3">
                                    <i class="fas fa-calendar-event me-2"></i>
                                    {{ $info['event']['title'] }}
                                </h5>
                                
                                <div class="row g-3">
                                    {{-- Date et heure --}}
                                    <div class="col-md-6">
                                        <div class="d-flex align-items-center">
                                            <i class="fas fa-calendar text-muted me-2"></i>
                                            <div>
                                                <small class="text-muted">Date</small>
                                                <div class="fw-semibold">{{ $info['event']['date'] }}</div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <div class="d-flex align-items-center">
                                            <i class="fas fa-clock text-muted me-2"></i>
                                            <div>
                                                <small class="text-muted">Heure</small>
                                                <div class="fw-semibold">{{ $info['event']['time'] }}</div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    {{-- Lieu --}}
                                    <div class="col-12">
                                        <div class="d-flex align-items-start">
                                            <i class="fas fa-map-marker-alt text-muted me-2 mt-1"></i>
                                            <div>
                                                <small class="text-muted">Lieu</small>
                                                <div class="fw-semibold">{{ $info['event']['venue'] }}</div>
                                                @if($info['event']['address'])
                                                    <small class="text-muted">{{ $info['event']['address'] }}</small>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                    
                                    {{-- Catégorie --}}
                                    @if($info['event']['category'])
                                        <div class="col-12">
                                            <span class="badge bg-primary">{{ $info['event']['category'] }}</span>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>

                        {{-- DÉTAILS DU BILLET --}}
                        <div class="row g-3 mb-4">
                            {{-- Type de billet --}}
                            <div class="col-md-6">
                                <div class="border rounded-3 p-3">
                                    <small class="text-muted">Type de billet</small>
                                    <div class="fw-semibold">{{ $info['ticket_type'] }}</div>
                                    @if($info['price'] > 0)
                                        <small class="text-success">{{ number_format($info['price']) }} FCFA</small>
                                    @endif
                                </div>
                            </div>
                            
                            {{-- Siège (si applicable) --}}
                            @if($info['seat_number'])
                                <div class="col-md-6">
                                    <div class="border rounded-3 p-3">
                                        <small class="text-muted">Siège</small>
                                        <div class="fw-semibold">{{ $info['seat_number'] }}</div>
                                    </div>
                                </div>
                            @endif
                        </div>

                        {{-- INFORMATIONS DU DÉTENTEUR --}}
                        <div class="card border-0 bg-light mb-4">
                            <div class="card-body">
                                <h6 class="card-title text-muted mb-3">
                                    <i class="fas fa-user me-2"></i>
                                    Détenteur du billet
                                </h6>
                                
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <small class="text-muted">Nom</small>
                                        <div class="fw-semibold">{{ $info['holder']['name'] }}</div>
                                    </div>
                                    <div class="col-md-6">
                                        <small class="text-muted">Email</small>
                                        <div class="fw-semibold">{{ $info['holder']['email'] }}</div>
                                    </div>
                                </div>
                                
                                @if($info['order'])
                                    <div class="mt-2">
                                        <small class="text-muted">Commande #{{ $info['order']['number'] }}</small>
                                        <div class="small text-muted">Achetée le {{ $info['order']['date'] }}</div>
                                    </div>
                                @endif
                            </div>
                        </div>

                        {{-- ACTIONS --}}
                        <div class="text-center">
                            <a href="{{ route('home') }}" class="btn btn-outline-secondary me-2">
                                <i class="fas fa-home me-2"></i>Retour à l'accueil
                            </a>
                            
                            @if($info['can_be_used'])
                                <button class="btn btn-success" onclick="markAsUsed()">
                                    <i class="fas fa-check me-2"></i>Marquer comme utilisé
                                </button>
                            @endif
                        </div>

                    @else
                        {{-- BILLET NON TROUVÉ OU ERREUR --}}
                        <div class="text-center py-5">
                            <div class="alert alert-danger border-0 shadow-sm">
                                <i class="fas fa-times-circle fa-3x mb-3 text-danger"></i>
                                <h4 class="text-danger mb-3">❌ Billet Non Trouvé</h4>
                                <p class="mb-0">{{ $error ?? 'Le billet demandé n\'existe pas.' }}</p>
                                @if(isset($ticket_code))
                                    <small class="text-muted d-block mt-2">Code recherché: <code>{{ $ticket_code }}</code></small>
                                @endif
                            </div>

                            <div class="mt-4">
                                <h6 class="text-muted mb-3">Que faire ?</h6>
                                <ul class="list-unstyled">
                                    <li class="mb-2"><i class="fas fa-check text-success me-2"></i> Vérifiez que le code est correct</li>
                                    <li class="mb-2"><i class="fas fa-check text-success me-2"></i> Scannez à nouveau le QR code</li>
                                    <li class="mb-2"><i class="fas fa-check text-success me-2"></i> Contactez l'organisateur si nécessaire</li>
                                </ul>
                            </div>
                            
                            <a href="{{ route('home') }}" class="btn btn-primary mt-3">
                                <i class="fas fa-home me-2"></i>Retour à l'accueil
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

{{-- JavaScript pour marquer comme utilisé --}}
@if($ticket && $info['can_be_used'])
<script>
function markAsUsed() {
    if (!confirm('Êtes-vous sûr de vouloir marquer ce billet comme utilisé ?')) {
        return;
    }

    // Désactiver le bouton
    const button = event.target;
    button.disabled = true;
    button.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Traitement...';

    fetch('/api/scan-ticket', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({
            ticket_code: '{{ $ticket->ticket_code }}'
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('✅ Billet marqué comme utilisé avec succès !');
            location.reload();
        } else {
            alert('❌ Erreur: ' + (data.error || 'Erreur inconnue'));
            button.disabled = false;
            button.innerHTML = '<i class="fas fa-check me-2"></i>Marquer comme utilisé';
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        alert('❌ Erreur de communication');
        button.disabled = false;
        button.innerHTML = '<i class="fas fa-check me-2"></i>Marquer comme utilisé';
    });
}
</script>
@endif
@endsection