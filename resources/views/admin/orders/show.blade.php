{{-- resources/views/admin/order-detail.blade.php --}}
@extends('layouts.admin')

@section('title', 'Détail Commande #{{ $order->order_number ?? $order->id }} - ClicBillet CI')

@section('breadcrumb')
    <li class="breadcrumb-item">
        <a href="{{ route('admin.orders') }}">Commandes</a>
    </li>
    <li class="breadcrumb-item active">Commande #{{ $order->order_number ?? $order->id }}</li>
@endsection

@section('content')
    <!-- Header avec actions -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-1">Commande #{{ $order->order_number ?? $order->id }}</h2>
            <p class="text-muted mb-0">
                Passée le {{ $order->created_at->format('d/m/Y à H:i') }}
                @if($order->user)
                    par {{ $order->user->name }}
                @endif
            </p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.orders.export', $order) }}" class="btn btn-outline-orange">
                <i class="fas fa-file-pdf me-2"></i>PDF
            </a>
            <div class="dropdown">
                <button class="btn btn-orange dropdown-toggle" type="button" data-bs-toggle="dropdown">
                    <i class="fas fa-cog me-2"></i>Actions
                </button>
                <ul class="dropdown-menu">
                    <li><a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#statusModal">
                        <i class="fas fa-edit me-2"></i>Changer statut
                    </a></li>
                    <li><a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#refundModal">
                        <i class="fas fa-undo me-2"></i>Rembourser
                    </a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item text-danger" href="#" onclick="deleteOrder({{ $order->id }})">
                        <i class="fas fa-trash me-2"></i>Supprimer
                    </a></li>
                </ul>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Informations principales -->
        <div class="col-lg-8">
            <!-- Détails de la commande -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-info-circle me-2"></i>Détails de la commande
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <td class="fw-bold">Numéro :</td>
                                    <td>#{{ $order->order_number ?? $order->id }}</td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">Statut :</td>
                                    <td>
                                        <span class="badge {{ $order->status == 'completed' ? 'bg-success' : ($order->status == 'pending' ? 'bg-warning' : 'bg-danger') }}">
                                            {{ ucfirst($order->status) }}
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">Total :</td>
                                    <td class="fw-bold text-primary">{{ number_format($order->total_amount) }} FCFA</td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">Méthode de paiement :</td>
                                    <td>{{ $order->payment_method ?? 'N/A' }}</td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <td class="fw-bold">Date de commande :</td>
                                    <td>{{ $order->created_at->format('d/m/Y H:i') }}</td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">Dernière mise à jour :</td>
                                    <td>{{ $order->updated_at->format('d/m/Y H:i') }}</td>
                                </tr>
                                @if($order->event)
                                <tr>
                                    <td class="fw-bold">Événement :</td>
                                    <td>
                                        <a href="{{ route('admin.events.show', $order->event) }}" class="text-decoration-none">
                                            {{ $order->event->title }}
                                        </a>
                                    </td>
                                </tr>
                                @endif
                                @if($order->discount_amount > 0)
                                <tr>
                                    <td class="fw-bold">Réduction :</td>
                                    <td class="text-success">-{{ number_format($order->discount_amount) }} FCFA</td>
                                </tr>
                                @endif
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Billets de la commande -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-ticket-alt me-2"></i>Billets commandés
                    </h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Billet</th>
                                    <th>Catégorie</th>
                                    <th>Prix unitaire</th>
                                    <th>Quantité</th>
                                    <th>Total</th>
                                    <th>Statut</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($order->tickets ?? [] as $ticket)
                                <tr>
                                    <td>
                                        <div class="fw-semibold">{{ $ticket->reference ?? 'N/A' }}</div>
                                        <small class="text-muted">{{ $ticket->created_at->format('d/m/Y') }}</small>
                                    </td>
                                    <td>{{ $ticket->category ?? 'Standard' }}</td>
                                    <td>{{ number_format($ticket->price) }} FCFA</td>
                                    <td>1</td>
                                    <td class="fw-semibold">{{ number_format($ticket->price) }} FCFA</td>
                                    <td>
                                        <span class="badge {{ $ticket->status == 'valid' ? 'bg-success' : ($ticket->status == 'used' ? 'bg-info' : 'bg-secondary') }}">
                                            {{ ucfirst($ticket->status) }}
                                        </span>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="6" class="text-center py-4">
                                        <i class="fas fa-ticket-alt fa-2x text-muted mb-2"></i>
                                        <p class="text-muted">Aucun billet trouvé</p>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Historique des transactions -->
            @if($order->transactions && $order->transactions->count() > 0)
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-history me-2"></i>Historique des transactions
                    </h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Type</th>
                                    <th>Montant</th>
                                    <th>Statut</th>
                                    <th>Référence</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($order->transactions as $transaction)
                                <tr>
                                    <td>{{ $transaction->created_at->format('d/m/Y H:i') }}</td>
                                    <td>{{ $transaction->type }}</td>
                                    <td>{{ number_format($transaction->amount) }} FCFA</td>
                                    <td>
                                        <span class="badge {{ $transaction->status == 'completed' ? 'bg-success' : 'bg-warning' }}">
                                            {{ ucfirst($transaction->status) }}
                                        </span>
                                    </td>
                                    <td>{{ $transaction->reference }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            @endif
        </div>

        <!-- Sidebar avec infos client -->
        <div class="col-lg-4">
            <!-- Informations client -->
            @if($order->user)
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="mb-0">
                        <i class="fas fa-user me-2"></i>Informations client
                    </h6>
                </div>
                <div class="card-body">
                    <div class="text-center mb-3">
                        <div class="avatar-circle">
                            {{ substr($order->user->name, 0, 2) }}
                        </div>
                        <h6 class="mt-2 mb-1">{{ $order->user->name }}</h6>
                        <small class="text-muted">{{ $order->user->email }}</small>
                    </div>
                    
                    <div class="border-top pt-3">
                        <small class="text-muted d-block">Client depuis</small>
                        <span>{{ $order->user->created_at->format('d/m/Y') }}</span>
                    </div>
                    
                    <div class="mt-2">
                        <small class="text-muted d-block">Total commandes</small>
                        <span>{{ $order->user->orders->count() }} commandes</span>
                    </div>
                    
                    <div class="mt-3 d-grid">
                        <a href="{{ route('admin.users.show', $order->user) }}" class="btn btn-outline-orange btn-sm">
                            <i class="fas fa-eye me-2"></i>Voir profil
                        </a>
                    </div>
                </div>
            </div>
            @endif

            <!-- Actions rapides -->
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">
                        <i class="fas fa-bolt me-2"></i>Actions rapides
                    </h6>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <button class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#emailModal">
                            <i class="fas fa-envelope me-2"></i>Envoyer email
                        </button>
                        
                        <button class="btn btn-outline-info btn-sm" onclick="printOrder()">
                            <i class="fas fa-print me-2"></i>Imprimer
                        </button>
                        
                        @if($order->status == 'pending')
                        <button class="btn btn-outline-success btn-sm" onclick="confirmOrder({{ $order->id }})">
                            <i class="fas fa-check me-2"></i>Confirmer
                        </button>
                        @endif
                        
                        <button class="btn btn-outline-warning btn-sm" data-bs-toggle="modal" data-bs-target="#noteModal">
                            <i class="fas fa-sticky-note me-2"></i>Ajouter note
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de changement de statut -->
    <div class="modal fade" id="statusModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Changer le statut</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="{{ route('admin.orders.update-status', $order) }}" method="POST">
                    @csrf
                    @method('PATCH')
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Nouveau statut</label>
                            <select name="status" class="form-select" required>
                                <option value="pending" {{ $order->status == 'pending' ? 'selected' : '' }}>En attente</option>
                                <option value="completed" {{ $order->status == 'completed' ? 'selected' : '' }}>Terminée</option>
                                <option value="cancelled" {{ $order->status == 'cancelled' ? 'selected' : '' }}>Annulée</option>
                                <option value="refunded" {{ $order->status == 'refunded' ? 'selected' : '' }}>Remboursée</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Commentaire (optionnel)</label>
                            <textarea name="comment" class="form-control" rows="3" placeholder="Raison du changement..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-orange">Confirmer</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('styles')
<style>
    .avatar-circle {
        width: 60px;
        height: 60px;
        border-radius: 50%;
        background: linear-gradient(135deg, #FF6B35, #F7931E);
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-weight: bold;
        font-size: 1.2rem;
        margin: 0 auto;
    }
</style>
@endpush

@push('scripts')
<script>
    function deleteOrder(orderId) {
        if (confirm('Êtes-vous sûr de vouloir supprimer cette commande ?')) {
            // Logique de suppression
            console.log('Suppression commande:', orderId);
        }
    }
    
    function confirmOrder(orderId) {
        if (confirm('Confirmer cette commande ?')) {
            // Logique de confirmation
            console.log('Confirmation commande:', orderId);
        }
    }
    
    function printOrder() {
        window.print();
    }
</script>
@endpush