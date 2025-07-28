{{-- ======================================================= --}}
{{-- resources/views/admin/orders.blade.php --}}
@extends('layouts.admin')

@section('title', 'Gestion des commandes - ClicBillet CI')

@section('breadcrumb')
    <li class="breadcrumb-item active">Commandes</li>
@endsection

@section('content')
    <!-- Header avec actions -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-1">Gestion des commandes</h2>
            <p class="text-muted mb-0">Suivez toutes les commandes de la plateforme</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.export.orders') }}" class="btn btn-outline-orange">
                <i class="fas fa-download me-2"></i>Exporter
            </a>
        </div>
    </div>

    <!-- Filtres -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.orders') }}">
                <div class="row align-items-end">
                    <div class="col-md-3">
                        <label class="form-label">Recherche</label>
                        <input type="text" name="search" class="form-control" 
                               placeholder="N° commande, client..." value="{{ request('search') }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Statut</label>
                        <select name="status" class="form-select">
                            <option value="">Tous</option>
                            <option value="paid" {{ request('status') == 'paid' ? 'selected' : '' }}>Payé</option>
                            <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>En attente</option>
                            <option value="failed" {{ request('status') == 'failed' ? 'selected' : '' }}>Échoué</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Période</label>
                        <select name="period" class="form-select">
                            <option value="">Toutes</option>
                            <option value="today" {{ request('period') == 'today' ? 'selected' : '' }}>Aujourd'hui</option>
                            <option value="week" {{ request('period') == 'week' ? 'selected' : '' }}>Cette semaine</option>
                            <option value="month" {{ request('period') == 'month' ? 'selected' : '' }}>Ce mois</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-orange w-100">
                            <i class="fas fa-search me-2"></i>Filtrer
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Liste des commandes -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0"><i class="fas fa-shopping-cart me-2"></i>Liste des commandes</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>N° Commande</th>
                            <th>Client</th>
                            <th>Événement</th>
                            <th>Montant</th>
                            <th>Statut</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($orders ?? [] as $order)
                            <tr>
                                <td>
                                    <a href="{{ route('admin.orders.show', $order) }}" class="text-decoration-none fw-semibold">
                                        #{{ $order->order_number ?? $order->id }}
                                    </a>
                                </td>
                                <td>
                                    <div>
                                        <div class="fw-semibold">{{ $order->user->name ?? 'N/A' }}</div>
                                        <small class="text-muted">{{ $order->billing_email ?? $order->user->email }}</small>
                                    </div>
                                </td>
                                <td>{{ $order->event->title ?? 'N/A' }}</td>
                                <td class="fw-semibold">{{ number_format($order->total_amount ?? 0) }} FCFA</td>
                                <td>
                                    <span class="badge {{ $order->payment_status == 'paid' ? 'bg-success' : ($order->payment_status == 'pending' ? 'bg-warning' : 'bg-danger') }}">
                                        {{ ucfirst($order->payment_status ?? 'Unknown') }}
                                    </span>
                                </td>
                                <td>{{ $order->created_at->format('d/m/Y H:i') }}</td>
                                <td>
                                    <a href="{{ route('admin.orders.show', $order) }}" 
                                       class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center p-4">
                                    <i class="fas fa-shopping-cart fa-2x text-muted mb-2"></i>
                                    <p class="text-muted">Aucune commande trouvée</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        
        @if(isset($orders) && $orders->hasPages())
            <div class="card-footer">
                {{ $orders->appends(request()->query())->links() }}
            </div>
        @endif
    </div>
@endsection
