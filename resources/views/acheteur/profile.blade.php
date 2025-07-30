{{-- resources/views/acheteur/profile.blade.php --}}
@extends('layouts.acheteur')

@section('title', 'Mon Profil - ClicBillet CI')

@section('breadcrumb')
    <li class="breadcrumb-item active">Mon Profil</li>
@endsection

@section('content')
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-1">
                <i class="fas fa-user-circle text-primary me-2"></i>
                Mon Profil
            </h2>
            <p class="text-muted mb-0">Gérez vos informations personnelles</p>
        </div>
    </div>

    <!-- Messages de feedback -->
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i>
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i>
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row">
        <!-- Informations du profil -->
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-edit me-2"></i>Informations personnelles
                    </h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('acheteur.profile.update') }}">
                        @csrf
                        @method('PATCH')

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="name" class="form-label">Nom complet</label>
                                <input type="text" 
                                       class="form-control @error('name') is-invalid @enderror" 
                                       id="name" 
                                       name="name" 
                                       value="{{ old('name', $user->name) }}" 
                                       required>
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="email" class="form-label">Adresse email</label>
                                <input type="email" 
                                       class="form-control @error('email') is-invalid @enderror" 
                                       id="email" 
                                       name="email" 
                                       value="{{ old('email', $user->email) }}" 
                                       required>
                                @error('email')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="phone" class="form-label">Téléphone</label>
                                <input type="tel" 
                                       class="form-control @error('phone') is-invalid @enderror" 
                                       id="phone" 
                                       name="phone" 
                                       value="{{ old('phone', $user->phone) }}" 
                                       placeholder="+225 XX XX XX XX">
                                @error('phone')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Rôle</label>
                                <input type="text" 
                                       class="form-control" 
                                       value="Acheteur" 
                                       readonly>
                                <small class="form-text text-muted">Votre rôle ne peut pas être modifié.</small>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end">
                            <button type="submit" class="btn btn-acheteur">
                                <i class="fas fa-save me-2"></i>Enregistrer les modifications
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Sidebar avec statistiques -->
        <div class="col-md-4">
            <!-- Statistiques du compte -->
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="mb-0">
                        <i class="fas fa-chart-line me-2"></i>Statistiques du compte
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-6 mb-3">
                            <div class="stat-item">
                                <h4 class="text-primary mb-1">{{ $user->orders()->count() }}</h4>
                                <small class="text-muted">Commandes</small>
                            </div>
                        </div>
                        <div class="col-6 mb-3">
                            <div class="stat-item">
                                <h4 class="text-success mb-1">{{ $user->orders()->where('payment_status', 'paid')->count() }}</h4>
                                <small class="text-muted">Payées</small>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="stat-item">
                                @php
                                    $totalSpent = $user->orders()->where('payment_status', 'paid')->sum('total_amount');
                                @endphp
                                <h4 class="text-info mb-1">{{ number_format($totalSpent) }}</h4>
                                <small class="text-muted">FCFA dépensés</small>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="stat-item">
                                @php
                                    $totalTickets = $user->orders()
                                        ->where('payment_status', 'paid')
                                        ->with('orderItems')
                                        ->get()
                                        ->sum(function($order) {
                                            return $order->orderItems->sum('quantity');
                                        });
                                @endphp
                                <h4 class="text-warning mb-1">{{ $totalTickets }}</h4>
                                <small class="text-muted">Billets</small>
                            </div>
                        </div>
                    </div>
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
                    <div class="mb-3">
                        <small class="text-muted d-block">Membre depuis</small>
                        <strong>{{ $user->created_at->format('d/m/Y') }}</strong>
                    </div>
                    
                    <div class="mb-3">
                        <small class="text-muted d-block">Dernière modification</small>
                        <strong>{{ $user->updated_at->format('d/m/Y H:i') }}</strong>
                    </div>

                    <div class="mb-3">
                        <small class="text-muted d-block">Statut</small>
                        <span class="badge bg-success">
                            <i class="fas fa-check-circle me-1"></i>Actif
                        </span>
                    </div>

                    @if($user->email_verified_at)
                        <div class="mb-3">
                            <small class="text-muted d-block">Email vérifié</small>
                            <span class="badge bg-success">
                                <i class="fas fa-check-circle me-1"></i>Vérifié
                            </span>
                        </div>
                    @else
                        <div class="mb-3">
                            <small class="text-muted d-block">Email</small>
                            <span class="badge bg-warning">
                                <i class="fas fa-exclamation-triangle me-1"></i>Non vérifié
                            </span>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Actions rapides -->
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">
                        <i class="fas fa-bolt me-2"></i>Actions rapides
                    </h6>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="{{ route('acheteur.orders') }}" class="btn btn-outline-primary">
                            <i class="fas fa-shopping-bag me-2"></i>Mes commandes
                        </a>
                        
                        <a href="{{ route('acheteur.tickets') }}" class="btn btn-outline-success">
                            <i class="fas fa-ticket-alt me-2"></i>Mes billets
                        </a>
                        
                        <a href="{{ route('home') }}" class="btn btn-outline-info">
                            <i class="fas fa-search me-2"></i>Découvrir des événements
                        </a>
                        
                        <a href="{{ route('cart.show') }}" class="btn btn-outline-warning">
                            <i class="fas fa-shopping-cart me-2"></i>Mon panier
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('styles')
<style>
    .stat-item {
        padding: 10px;
        border-radius: 8px;
        background: #f8f9fa;
        transition: all 0.3s ease;
    }
    
    .stat-item:hover {
        background: #e9ecef;
        transform: translateY(-2px);
    }
    
    .card {
        transition: all 0.3s ease;
    }
    
    .card:hover {
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    }
</style>
@endpush