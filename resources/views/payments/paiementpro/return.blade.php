{{-- ================================================ --}}
{{-- resources/views/payments/paiementpro/return.blade.php --}}
{{-- ================================================ --}}

@extends('layouts.app')

@section('title', 'Résultat du paiement')

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            @if($status === 'success')
                {{-- Paiement réussi --}}
                <div class="card border-success">
                    <div class="card-header bg-success text-white text-center">
                        <i class="fas fa-check-circle fa-3x mb-3"></i>
                        <h3 class="mb-0">Paiement réussi !</h3>
                    </div>
                    <div class="card-body p-4">
                        @if($order)
                            <div class="alert alert-success">
                                <h5><i class="fas fa-ticket-alt me-2"></i>Vos billets sont prêts !</h5>
                                <p class="mb-0">Votre paiement a été confirmé. Vous recevrez vos billets par email sous peu.</p>
                            </div>

                            <div class="order-summary mb-4">
                                <h6 class="text-muted mb-3">Détails de votre commande</h6>
                                <div class="row">
                                    <div class="col-md-6">
                                        <p><strong>Commande :</strong> #{{ $order->order_number }}</p>
                                        <p><strong>Événement :</strong> {{ $order->event->title }}</p>
                                        <p><strong>Date :</strong> {{ $order->event->formatted_event_date ?? 'TBD' }}</p>
                                    </div>
                                    <div class="col-md-6">
                                        <p><strong>Montant payé :</strong> {{ number_format($order->total_amount) }} FCFA</p>
                                        <p><strong>Nombre de billets :</strong> {{ $order->tickets_count ?? $order->tickets->count() }}</p>
                                        <p><strong>Date paiement :</strong> {{ now()->format('d/m/Y à H:i') }}</p>
                                    </div>
                                </div>
                            </div>

                            <div class="d-grid gap-2 d-md-flex justify-content-md-center">
                                <a href="{{ route('orders.show', $order) }}" class="btn btn-orange btn-lg">
                                    <i class="fas fa-eye me-2"></i>Voir ma commande
                                </a>
                                <a href="{{ route('events.show', $order->event) }}" class="btn btn-outline-orange btn-lg">
                                    <i class="fas fa-calendar me-2"></i>Voir l'événement
                                </a>
                            </div>
                        @else
                            <div class="alert alert-info">
                                <p class="mb-0">Votre paiement a été confirmé. Vous pouvez maintenant accéder à vos billets.</p>
                            </div>
                            <div class="text-center">
                                <a href="{{ route('dashboard') }}" class="btn btn-orange btn-lg">
                                    <i class="fas fa-tachometer-alt me-2"></i>Aller au tableau de bord
                                </a>
                            </div>
                        @endif
                    </div>
                </div>

            @elseif($status === 'failed')
                {{-- Paiement échoué --}}
                <div class="card border-danger">
                    <div class="card-header bg-danger text-white text-center">
                        <i class="fas fa-times-circle fa-3x mb-3"></i>
                        <h3 class="mb-0">Paiement échoué</h3>
                    </div>
                    <div class="card-body p-4">
                        <div class="alert alert-danger">
                            <h5><i class="fas fa-exclamation-triangle me-2"></i>Problème de paiement</h5>
                            <p class="mb-0">Votre paiement n'a pas pu être traité. Veuillez réessayer ou utiliser un autre moyen de paiement.</p>
                        </div>

                        @if($order)
                            <div class="order-info mb-4">
                                <h6 class="text-muted mb-3">Informations de la commande</h6>
                                <p><strong>Commande :</strong> #{{ $order->order_number }}</p>
                                <p><strong>Événement :</strong> {{ $order->event->title }}</p>
                                <p><strong>Montant :</strong> {{ number_format($order->total_amount) }} FCFA</p>
                            </div>

                            <div class="d-grid gap-2 d-md-flex justify-content-md-center">
                                <a href="{{ route('orders.show', $order) }}" class="btn btn-orange btn-lg">
                                    <i class="fas fa-redo me-2"></i>Réessayer le paiement
                                </a>
                                <a href="{{ route('events.show', $order->event) }}" class="btn btn-outline-secondary btn-lg">
                                    <i class="fas fa-arrow-left me-2"></i>Retour à l'événement
                                </a>
                            </div>
                        @else
                            <div class="text-center">
                                <a href="{{ route('home') }}" class="btn btn-orange btn-lg">
                                    <i class="fas fa-home me-2"></i>Retour à l'accueil
                                </a>
                            </div>
                        @endif
                    </div>
                </div>

            @else
                {{-- Statut en attente ou erreur --}}
                <div class="card border-warning">
                    <div class="card-header bg-warning text-dark text-center">
                        <i class="fas fa-clock fa-3x mb-3"></i>
                        <h3 class="mb-0">Paiement en cours</h3>
                    </div>
                    <div class="card-body p-4">
                        <div class="alert alert-warning">
                            <h5><i class="fas fa-hourglass-half me-2"></i>Traitement en cours</h5>
                            <p class="mb-0">Votre paiement est en cours de traitement. Vous recevrez une confirmation par email dès que le paiement sera validé.</p>
                        </div>

                        @if($order)
                            <div class="order-info mb-4">
                                <h6 class="text-muted mb-3">Informations de la commande</h6>
                                <p><strong>Commande :</strong> #{{ $order->order_number }}</p>
                                <p><strong>Événement :</strong> {{ $order->event->title }}</p>
                                <p><strong>Montant :</strong> {{ number_format($order->total_amount) }} FCFA</p>
                            </div>

                            <div class="d-grid gap-2 d-md-flex justify-content-md-center">
                                <a href="{{ route('orders.show', $order) }}" class="btn btn-orange btn-lg">
                                    <i class="fas fa-eye me-2"></i>Suivre ma commande
                                </a>
                                <button type="button" class="btn btn-outline-orange btn-lg" onclick="checkPaymentStatus()">
                                    <i class="fas fa-sync me-2"></i>Vérifier le statut
                                </button>
                            </div>
                        @else
                            <div class="text-center">
                                <a href="{{ route('dashboard') }}" class="btn btn-orange btn-lg">
                                    <i class="fas fa-tachometer-alt me-2"></i>Aller au tableau de bord
                                </a>
                            </div>
                        @endif
                    </div>
                </div>
            @endif

            {{-- Informations de support --}}
            <div class="card mt-4">
                <div class="card-body text-center">
                    <h6 class="mb-3">Besoin d'aide ?</h6>
                    <p class="text-muted mb-3">Si vous rencontrez des difficultés ou avez des questions, notre équipe support est là pour vous aider.</p>
                    <a href="{{ route('contact') }}" class="btn btn-outline-orange">
                        <i class="fas fa-headset me-2"></i>Contacter le support
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

@if($order)
<script>
function checkPaymentStatus() {
    const btn = event.target;
    const originalText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Vérification...';

    fetch(`{{ route('payments.paiementpro.status') }}?order_id={{ $order->id }}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                if (data.order_status === 'paid') {
                    location.reload();
                } else {
                    alert('Le paiement est toujours en cours de traitement.');
                }
            } else {
                alert('Erreur lors de la vérification du statut.');
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            alert('Erreur lors de la vérification du statut.');
        })
        .finally(() => {
            btn.disabled = false;
            btn.innerHTML = originalText;
        });
}
</script>
@endif
@endsection
