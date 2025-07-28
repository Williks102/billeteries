{{-- ======================================================= --}}
{{-- resources/views/admin/commissions.blade.php --}}
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

    <!-- Statistiques des commissions -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="stat-card">
                <div class="d-flex align-items-center">
                    <div class="stat-icon warning me-3">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="stat-info">
                        <h4>{{ $stats['pending'] ?? 0 }}</h4>
                        <p class="text-muted mb-0">En attente</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="d-flex align-items-center">
                    <div class="stat-icon success me-3">
                        <i class="fas fa-check"></i>
                    </div>
                    <div class="stat-info">
                        <h4>{{ $stats['paid'] ?? 0 }}</h4>
                        <p class="text-muted mb-0">Payées</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="d-flex align-items-center">
                    <div class="stat-icon info me-3">
                        <i class="fas fa-money-bill-wave"></i>
                    </div>
                    <div class="stat-info">
                        <h4>{{ number_format($stats['total_amount'] ?? 0) }} FCFA</h4>
                        <p class="text-muted mb-0">Total commissions</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="d-flex align-items-center">
                    <div class="stat-icon primary me-3">
                        <i class="fas fa-percentage"></i>
                    </div>
                    <div class="stat-info">
                        <h4>{{ $stats['avg_rate'] ?? 0 }}%</h4>
                        <p class="text-muted mb-0">Taux moyen</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filtres -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.commissions') }}">
                <div class="row align-items-end">
                    <div class="col-md-4">
                        <label class="form-label">Promoteur</label>
                        <input type="text" name="promoter" class="form-control" 
                               placeholder="Nom du promoteur..." value="{{ request('promoter') }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Statut</label>
                        <select name="status" class="form-select">
                            <option value="">Tous</option>
                            <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>En attente</option>
                            <option value="paid" {{ request('status') == 'paid' ? 'selected' : '' }}>Payées</option>
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
        <div class="card-header">
            <h5 class="mb-0"><i class="fas fa-coins me-2"></i>Liste des commissions</h5>
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
                        @forelse($commissions ?? [] as $commission)
                            <tr>
                                <td>
                                    <div class="fw-semibold">{{ $commission->promoter->name ?? 'N/A' }}</div>
                                    <small class="text-muted">{{ $commission->promoter->email ?? 'N/A' }}</small>
                                </td>
                                <td>
                                    <a href="{{ route('admin.orders.show', $commission->order) }}" class="text-decoration-none">
                                        #{{ $commission->order->order_number ?? $commission->order->id }}
                                    </a>
                                </td>
                                <td>{{ number_format($commission->gross_amount ?? 0) }} FCFA</td>
                                <td>{{ $commission->commission_rate ?? 0 }}%</td>
                                <td class="fw-semibold text-primary">{{ number_format($commission->commission_amount ?? 0) }} FCFA</td>
                                <td class="fw-semibold">{{ number_format($commission->net_amount ?? 0) }} FCFA</td>
                                <td>
                                    <span class="badge {{ $commission->status == 'paid' ? 'bg-success' : ($commission->status == 'pending' ? 'bg-warning' : 'bg-danger') }}">
                                        {{ ucfirst($commission->status ?? 'Unknown') }}
                                    </span>
                                </td>
                                <td>{{ $commission->created_at->format('d/m/Y') }}</td>
                                <td>
                                    @if($commission->status == 'pending')
                                        <form method="POST" action="{{ route('admin.commissions.pay', $commission) }}" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-success" 
                                                    onclick="return confirm('Marquer cette commission comme payée ?')">
                                                <i class="fas fa-check"></i> Payer
                                            </button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center p-4">
                                    <i class="fas fa-coins fa-2x text-muted mb-2"></i>
                                    <p class="text-muted">Aucune commission trouvée</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        
        @if(isset($commissions) && $commissions->hasPages())
            <div class="card-footer">
                {{ $commissions->appends(request()->query())->links() }}
            </div>
        @endif
    </div>
@endsection