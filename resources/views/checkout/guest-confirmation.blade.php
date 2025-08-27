{{-- resources/views/checkout/guest-confirmation.blade.php --}}

@extends('layouts.app')

@section('title', 'Commande confirmée')

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <!-- Header de confirmation -->
            <div class="text-center mb-4">
                <div class="success-icon mb-3">
                    <i class="fas fa-check-circle text-success" style="font-size: 4rem;"></i>
                </div>
                <h1 class="text-success">Commande confirmée !</h1>
                <p class="lead">Vos billets vous ont été envoyés par email</p>
            </div>

            <!-- Détails de la commande -->
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-receipt me-2"></i>
                        Commande #{{ $order->order_number }}
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6>Événement</h6>
                            <p>{{ $order->event->title }}</p>
                            
                            <h6>Date</h6>
                            <p>{{ $order->event->formatted_event_date ?? 'À confirmer' }}</p>
                            
                            <h6>Lieu</h6>
                            <p>{{ $order->event->venue }}</p>
                        </div>
                        <div class="col-md-6">
                            <h6>Acheteur</h6>
                            <p>{{ $order->user->first_name }} {{ $order->user->last_name }}</p>
                            
                            <h6>Email</h6>
                            <p>{{ $order->billing_email }}</p>
                            
                            <h6>Téléphone</h6>
                            <p>{{ $order->billing_phone }}</p>
                        </div>
                    </div>

                    <!-- Billets -->
                    <h6 class="mt-4">Vos billets</h6>
                    @foreach($order->orderItems as $item)
                        <div class="d-flex justify-content-between py-2 border-bottom">
                            <span>{{ $item->ticketType->name }} × {{ $item->quantity }}</span>
                            <span class="fw-bold">{{ number_format($item->total_price) }} FCFA</span>
                        </div>
                    @endforeach
                    
                    <div class="d-flex justify-content-between py-3 fw-bold fs-5 border-top">
                        <span>Total</span>
                        <span class="text-primary">{{ number_format($order->total_amount) }} FCFA</span>
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="row">
                <div class="col-md-6 mb-3">
                    <div class="card text-center">
                        <div class="card-body">
                            <i class="fas fa-envelope fa-2x text-primary mb-3"></i>
                            <h6>Vérifiez votre email</h6>
                            <p class="text-muted small">Vos billets ont été envoyés à {{ $order->billing_email }}</p>
                        </div>
                    </div>
                </div>
                
                @if($order->user->is_guest)
                <div class="col-md-6 mb-3">
                    <div class="card text-center">
                        <div class="card-body">
                            <i class="fas fa-user-plus fa-2x text-success mb-3"></i>
                            <h6>Créer un compte</h6>
                            <p class="text-muted small">Gérez vos billets facilement</p>
                            <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#createAccountModal">
                                Créer mon compte
                            </button>
                        </div>
                    </div>
                </div>
                @endif
            </div>

            <!-- Navigation -->
            <div class="text-center mt-4">
                <a href="{{ route('home') }}" class="btn btn-outline-primary">
                    <i class="fas fa-home me-2"></i>Retour à l'accueil
                </a>
                <a href="{{ route('events.all') }}" class="btn btn-primary ms-2">
                    <i class="fas fa-calendar-alt me-2"></i>Voir d'autres événements
                </a>
            </div>
        </div>
    </div>
</div>

@if($order->user->is_guest)
<!-- Modal création de compte -->
<div class="modal fade" id="createAccountModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Créer votre compte</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="{{ route('checkout.guest.create-account', $order->guest_token) }}">
                @csrf
                <div class="modal-body">
                    <p>Créez un mot de passe pour accéder à vos billets à tout moment :</p>
                    
                    <div class="mb-3">
                        <label for="password" class="form-label">Mot de passe</label>
                        <input type="password" class="form-control" id="password" name="password" required minlength="6">
                    </div>
                    
                    <div class="mb-3">
                        <label for="password_confirmation" class="form-label">Confirmer le mot de passe</label>
                        <input type="password" class="form-control" id="password_confirmation" name="password_confirmation" required>
                    </div>

                    <div class="alert alert-info">
                        <small>
                            <strong>Avantages du compte :</strong><br>
                            • Accès à l'historique de vos commandes<br>
                            • Réimpression de vos billets<br>
                            • Support client prioritaire
                        </small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Plus tard</button>
                    <button type="submit" class="btn btn-success">Créer mon compte</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif
@endsection