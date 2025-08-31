{{-- resources/views/tickets/public-verify.blade.php --}}
{{-- Vue publique LECTURE SEULE pour vérification de validité --}}

@extends('layouts.app')

@section('title', 'Vérification de Billet')

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow-lg border-0">
                <div class="card-header bg-gradient-primary text-white text-center py-4">
                    <h2 class="mb-0">
                        <i class="fas fa-shield-alt me-3"></i>
                        Vérification Publique
                    </h2>
                    <p class="mb-0 mt-2 opacity-90">Validation de l'authenticité du billet</p>
                </div>
                
                <div class="card-body p-5">
                    @if($ticket && !$error)
                        {{-- BILLET TROUVÉ --}}
                        
                        {{-- Statut principal --}}
                        <div class="text-center mb-4">
                            @if($info['is_valid'] && $info['status'] === 'sold')
                                <div class="alert alert-success border-0 shadow-sm">
                                    <i class="fas fa-check-circle fa-3x mb-3 text-success"></i>
                                    <h4 class="text-success mb-3">✅ Billet Authentique</h4>
                                    <p class="mb-0">{{ $info['status_message'] }}</p>
                                </div>
                            @elseif($info['status'] === 'used')
                                <div class="alert alert-warning border-0 shadow-sm">
                                    <i class="fas fa-check-double fa-3x mb-3 text-warning"></i>
                                    <h4 class="text-warning mb-3">✅ Billet Authentique (Utilisé)</h4>
                                    <p class="mb-0">{{ $info['status_message'] }}</p>
                                </div>
                            @else
                                <div class="alert alert-danger border-0 shadow-sm">
                                    <i class="fas fa-times-circle fa-3x mb-3 text-danger"></i>
                                    <h4 class="text-danger mb-3">❌ Billet Non Valide</h4>
                                    <p class="mb-0">{{ $info['status_message'] }}</p>
                                </div>
                            @endif
                        </div>

                        {{-- Informations publiques de l'événement --}}
                        <div class="row mb-4">
                            <div class="col-md-8 mx-auto">
                                <div class="card bg-light border-0">
                                    <div class="card-body">
                                        <h5 class="card-title text-primary mb-3">
                                            <i class="fas fa-calendar-alt me-2"></i>Événement
                                        </h5>
                                        
                                        <div class="mb-2">
                                            <h6 class="fw-bold">{{ $info['event']['title'] }}</h6>
                                        </div>
                                        
                                        <div class="row text-muted small">
                                            <div class="col-sm-6">
                                                <i class="fas fa-clock me-1"></i>
                                                {{ $info['event']['date'] }}
                                            </div>
                                            <div class="col-sm-6">
                                                <i class="fas fa-map-marker-alt me-1"></i>
                                                {{ $info['event']['location'] }}
                                            </div>
                                        </div>
                                        
                                        <div class="mt-2">
                                            <span class="badge bg-secondary">{{ $info['ticket_type'] }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Notice sécurité --}}
                        <div class="alert alert-info border-0">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-info-circle fa-2x me-3"></i>
                                <div>
                                    <h6 class="mb-1">Information</h6>
                                    <p class="mb-0 small">Cette page vérifie uniquement l'authenticité du billet. 
                                    Pour les opérations de scan et validation, utilisez l'interface dédiée aux organisateurs.</p>
                                </div>
                            </div>
                        </div>

                    @else
                        {{-- BILLET NON TROUVÉ OU ERREUR --}}
                        <div class="text-center py-5">
                            <div class="alert alert-danger border-0 shadow-sm">
                                <i class="fas fa-search fa-3x mb-3 text-danger"></i>
                                <h4 class="text-danger mb-3">Billet Non Trouvé</h4>
                                <p class="mb-0">{{ $error ?? 'Le billet demandé n\'existe pas.' }}</p>
                                @if(isset($ticket_code))
                                    <small class="text-muted d-block mt-2">Code recherché: <code>{{ $ticket_code }}</code></small>
                                @endif
                            </div>

                            <div class="mt-4">
                                <h6 class="text-muted mb-3">Que faire ?</h6>
                                <ul class="list-unstyled text-start" style="max-width: 400px; margin: 0 auto;">
                                    <li class="mb-2"><i class="fas fa-check text-success me-2"></i> Vérifiez que le code est correct</li>
                                    <li class="mb-2"><i class="fas fa-check text-success me-2"></i> Scannez à nouveau le QR code</li>
                                    <li class="mb-2"><i class="fas fa-check text-success me-2"></i> Contactez l'organisateur si nécessaire</li>
                                </ul>
                            </div>
                        </div>
                    @endif
                    
                    {{-- Actions --}}
                    <div class="text-center mt-4">
                        <a href="{{ route('home') }}" class="btn btn-outline-secondary">
                            <i class="fas fa-home me-2"></i>Retour à l'accueil
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
.bg-gradient-primary {
    background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
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

@media (max-width: 768px) {
    .card-body {
        padding: 2rem !important;
    }
}
</style>
@endpush
@endsection