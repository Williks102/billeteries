{{-- resources/views/admin/commissions.blade.php - VERSION CORRIGÉE --}}
@extends('layouts.admin')

@section('title', 'Gestion des commissions - ClicBillet CI')

@section('breadcrumb')
    <li class="breadcrumb-item active">Commissions</li>
@endsection

@section('content')
    <!-- Header avec actions -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-1">Gestion des commissions</h2>
            <p class="text-muted mb-0">Gérez les commissions des promoteurs</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.export.commissions') }}" class="btn btn-outline-orange">
                <i class="fas fa-download me-2"></i>Exporter
            </a>
        </div>
    </div>

    <!-- Statistiques des commissions CORRIGÉES -->
    <div class="row mb-4">
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="stat-card">
                <div class="d-flex align-items-center">
                    <div class="stat-icon warning me-3">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="stat-info">
                        <h4>{{ $stats['pending'] ?? 0 }}</h4>
                        <p class="text-muted mb-0">En attente</p>
                        <small class="text-warning">{{ number_format($stats['total_pending_amount'] ?? 0) }} FCFA</small>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="stat-card">
                <div class="d-flex align-items-center">
                    <div class="stat-icon success me-3">
                        <i class="fas fa-check"></i>
                    </div>
                    <div class="stat-info">
                        <h4>{{ $stats['paid'] ?? 0 }}</h4>
                        <p class="text-muted mb-0">Payées</p>
                        <small class="text-success">{{ number_format($stats['total_paid_amount'] ?? 0) }} FCFA</small>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="stat-card">
                <div class="d-flex align-items-center">
                    <div class="stat-icon info me-3">
                        <i class="fas fa-money-bill-wave"></i>
                    </div>
                    <div class="stat-info">
                        <h4>{{ number_format($stats['total_amount'] ?? 0) }}</h4>
                        <p class="text-muted mb-0">Total commissions</p>
                        <small class="text-info">{{ $stats['avg_rate'] ?? 0 }}% taux moyen</small>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="stat-card">
                <div class="d-flex align-items-center">
                    <div class="stat-icon danger me-3">
                        <i class="fas fa-pause"></i>
                    </div>
                    <div class="stat-info">
                        <h4>{{ $stats['held'] ?? 0 }}</h4>
                        <p class="text-muted mb-0">Suspendues</p>
                        <small class="text-danger">{{ number_format($stats['total_held_amount'] ?? 0) }} FCFA</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filtres -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.commissions') }}">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Recherche</label>
                        <input type="text" name="search" class="form-control" 
                               placeholder="Promoteur, commande..." 
                               value="{{ request('search') }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Statut</label>
                        <select name="status" class="form-select">
                            <option value="">Tous les statuts</option>
                            <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>En attente</option>
                            <option value="paid" {{ request('status') == 'paid' ? 'selected' : '' }}>Payées</option>
                            <option value="held" {{ request('status') == 'held' ? 'selected' : '' }}>Suspendues</option>
                            <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Annulées</option>
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
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-orange w-100">
                            <i class="fas fa-search me-2"></i>Filtrer
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Liste des commissions -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="fas fa-coins me-2"></i>Liste des commissions</h5>
            <div class="text-muted">
                {{ $commissions->total() }} commission{{ $commissions->total() > 1 ? 's' : '' }}
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Promoteur</th>
                            <th>Commande</th>
                            <th>Montant brut</th>
                            <th>Taux</th>
                            <th>Commission</th>
                            <th>Net</th>
                            <th>Statut</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($commissions as $commission)
                            <tr>
                                <td>
                                    <div class="fw-semibold">{{ $commission->promoteur->name ?? 'N/A' }}</div>
                                    <small class="text-muted">{{ $commission->promoteur->email ?? 'N/A' }}</small>
                                </td>
                                <td>
                                    <a href="{{ route('admin.orders.show', $commission->order) }}" class="text-decoration-none">
                                        #{{ $commission->order->order_number ?? $commission->order->id }}
                                    </a>
                                    <br>
                                    <small class="text-muted">{{ $commission->order->event->title ?? 'N/A' }}</small>
                                </td>
                                <td>{{ number_format($commission->gross_amount ?? 0) }} FCFA</td>
                                <td>{{ $commission->commission_rate ?? 0 }}%</td>
                                <td class="fw-bold text-primary">{{ number_format($commission->commission_amount ?? 0) }} FCFA</td>
                                <td class="fw-bold">{{ number_format($commission->net_amount ?? 0) }} FCFA</td>
                                <td>
                                    @switch($commission->status)
                                        @case('pending')
                                            <span class="badge bg-warning">En attente</span>
                                            @break
                                        @case('paid')
                                            <span class="badge bg-success">Payée</span>
                                            @break
                                        @case('held')
                                            <span class="badge bg-danger">Suspendue</span>
                                            @break
                                        @case('cancelled')
                                            <span class="badge bg-secondary">Annulée</span>
                                            @break
                                        @default
                                            <span class="badge bg-light text-dark">{{ $commission->status }}</span>
                                    @endswitch
                                </td>
                                <td>
                                    <div>{{ $commission->created_at->format('d/m/Y') }}</div>
                                    <small class="text-muted">{{ $commission->created_at->format('H:i') }}</small>
                                </td>
                                <td>
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle" 
                                                type="button" data-bs-toggle="dropdown">
                                            <i class="fas fa-cog"></i>
                                        </button>
                                        <ul class="dropdown-menu">
                                            <li>
                                                <a class="dropdown-item" href="{{ route('admin.orders.show', $commission->order) }}">
                                                    <i class="fas fa-eye me-2"></i>Voir commande
                                                </a>
                                            </li>
                                            @if($commission->status === 'pending')
                                                <li>
                                                    <a class="dropdown-item text-success" href="#" 
                                                       onclick="payCommission({{ $commission->id }})">
                                                        <i class="fas fa-check me-2"></i>Marquer payée
                                                    </a>
                                                </li>
                                                <li>
                                                    <a class="dropdown-item text-warning" href="#" 
                                                       onclick="holdCommission({{ $commission->id }})">
                                                        <i class="fas fa-pause me-2"></i>Suspendre
                                                    </a>
                                                </li>
                                            @endif
                                            @if($commission->status === 'held')
                                                <li>
                                                    <a class="dropdown-item text-info" href="#" 
                                                       onclick="releaseCommission({{ $commission->id }})">
                                                        <i class="fas fa-play me-2"></i>Remettre en attente
                                                    </a>
                                                </li>
                                            @endif
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center py-4">
                                    <i class="fas fa-coins fa-2x text-muted mb-2"></i>
                                    <p class="text-muted mb-0">Aucune commission trouvée</p>
                                    @if(request()->hasAny(['search', 'status', 'period']))
                                        <a href="{{ route('admin.commissions') }}" class="btn btn-outline-orange btn-sm mt-2">
                                            <i class="fas fa-times me-2"></i>Effacer les filtres
                                        </a>
                                    @endif
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        
        {{-- PAGINATION CORRIGÉE --}}
        @if($commissions->hasPages())
            <div class="card-footer">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="text-muted">
                        Affichage de {{ $commissions->firstItem() }} à {{ $commissions->lastItem() }} 
                        sur {{ $commissions->total() }} résultats
                    </div>
                    <div>
                        {{ $commissions->appends(request()->query())->links('pagination::bootstrap-4') }}
                    </div>
                </div>
            </div>
        @endif
    </div>
@endsection

@section('scripts')
<script>
function payCommission(id) {
    if (confirm('Confirmer le paiement de cette commission ?')) {
        fetch(`/admin/commissions/${id}/pay`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json',
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Erreur: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            alert('Une erreur est survenue');
        });
    }
}

function holdCommission(id) {
    if (confirm('Suspendre cette commission ?')) {
        fetch(`/admin/commissions/${id}/hold`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json',
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Erreur: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            alert('Une erreur est survenue');
        });
    }
}

function releaseCommission(id) {
    if (confirm('Remettre cette commission en attente ?')) {
        fetch(`/admin/commissions/${id}/release`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json',
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Erreur: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            alert('Une erreur est survenue');
        });
    }
}
</script>
@endsection