@extends('layouts.admin')

@section('title', 'Gestion des Commissions')

@section('content')
<div class="container-fluid py-4">
    
    <!-- Header avec actions -->
    <div class="row align-items-center mb-4">
        <div class="col">
            <h1 class="h3 mb-0">💰 Gestion des Commissions</h1>
            <p class="text-muted mb-0">{{ $commissions->total() }} commission(s) au total</p>
        </div>
        <div class="col-auto">
            <div class="btn-group" role="group">
                <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#bulkPayModal">
                    <i class="fas fa-credit-card me-1"></i> Paiement en lot
                </button>
                <button type="button" class="btn btn-outline-primary dropdown-toggle" data-bs-toggle="dropdown">
                    <i class="fas fa-download me-1"></i> Exporter
                </button>
                <ul class="dropdown-menu">
                    <li><a class="dropdown-item" href="{{ route('admin.reports.export', 'commissions') }}">
                        <i class="fas fa-file-csv me-2"></i> Export CSV
                    </a></li>
                    <li><a class="dropdown-item" href="#" onclick="window.print()">
                        <i class="fas fa-print me-2"></i> Imprimer
                    </a></li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Statistiques des commissions -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3">
            <div class="card border-0 bg-success bg-opacity-10">
                <div class="card-body text-center">
                    <i class="fas fa-check-circle text-success fa-2x mb-2"></i>
                    <h4 class="mb-1 text-success">{{ number_format($stats['paid_commissions']) }} F</h4>
                    <small class="text-muted">Commissions payées</small>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card border-0 bg-warning bg-opacity-10">
                <div class="card-body text-center">
                    <i class="fas fa-clock text-warning fa-2x mb-2"></i>
                    <h4 class="mb-1 text-warning">{{ number_format($stats['pending_commissions']) }} F</h4>
                    <small class="text-muted">En attente</small>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card border-0 bg-info bg-opacity-10">
                <div class="card-body text-center">
                    <i class="fas fa-pause-circle text-info fa-2x mb-2"></i>
                    <h4 class="mb-1 text-info">{{ number_format($stats['held_commissions']) }} F</h4>
                    <small class="text-muted">En attente</small>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card border-0 bg-primary bg-opacity-10">
                <div class="card-body text-center">
                    <i class="fas fa-calendar text-primary fa-2x mb-2"></i>
                    <h4 class="mb-1 text-primary">{{ number_format($stats['this_month_commissions']) }} F</h4>
                    <small class="text-muted">Ce mois</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Filtres -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.commissions') }}" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label small fw-semibold text-muted">PROMOTEUR</label>
                    <select class="form-select" name="promoter">
                        <option value="">Tous les promoteurs</option>
                        @foreach($promoteurs as $promoteur)
                        <option value="{{ $promoteur->id }}" {{ request('promoter') == $promoteur->id ? 'selected' : '' }}>
                            {{ $promoteur->name }}
                        </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold text-muted">STATUT</label>
                    <select class="form-select" name="status">
                        <option value="">Tous les statuts</option>
                        <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>En attente</option>
                        <option value="paid" {{ request('status') === 'paid' ? 'selected' : '' }}>Payé</option>
                        <option value="held" {{ request('status') === 'held' ? 'selected' : '' }}>Suspendu</option>
                        <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>Annulé</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-semibold text-muted">PÉRIODE</label>
                    <div class="row g-2">
                        <div class="col-6">
                            <input type="date" class="form-control form-control-sm" name="date_from" 
                                   value="{{ request('date_from') }}" placeholder="Du">
                        </div>
                        <div class="col-6">
                            <input type="date" class="form-control form-control-sm" name="date_to" 
                                   value="{{ request('date_to') }}" placeholder="Au">
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-semibold text-muted">ACTIONS</label>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-filter me-1"></i> Filtrer
                        </button>
                        <a href="{{ route('admin.commissions') }}" class="btn btn-outline-secondary">
                            <i class="fas fa-times me-1"></i> Reset
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Liste des commissions -->
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            @if($commissions->count() > 0)
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th width="40">
                                <input type="checkbox" class="form-check-input" id="select-all">
                            </th>
                            <th>Commission</th>
                            <th>Promoteur</th>
                            <th>Commande</th>
                            <th>Événement</th>
                            <th>Montant</th>
                            <th>Statut</th>
                            <th>Date</th>
                            <th width="120">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($commissions as $commission)
                        <tr>
                            <td>
                                <input type="checkbox" class="form-check-input commission-checkbox" 
                                       name="commissions[]" value="{{ $commission->id }}"
                                       data-status="{{ $commission->status }}">
                            </td>
                            <td>
                                <div>
                                    <div class="fw-bold">COM-{{ str_pad($commission->id, 6, '0', STR_PAD_LEFT) }}</div>
                                    <div class="text-muted small">
                                        ID: {{ $commission->id }}
                                    </div>
                                    @if($commission->admin_notes)
                                    <div class="text-info small">
                                        <i class="fas fa-sticky-note me-1"></i>
                                        {{ Str::limit($commission->admin_notes, 25) }}
                                    </div>
                                    @endif
                                </div>
                            </td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="bg-primary bg-opacity-10 rounded-circle me-2 d-flex align-items-center justify-content-center" 
                                         style="width: 35px; height: 35px;">
                                        <span class="fw-bold text-primary small">
                                            {{ substr($commission->promoteur->name, 0, 1) }}
                                        </span>
                                    </div>
                                    <div>
                                        <div class="fw-semibold">{{ $commission->promoteur->name }}</div>
                                        <div class="text-muted small">{{ $commission->promoteur->email }}</div>
                                        @php
                                            $totalCommissions = $commission->promoteur->commissions()
                                                ->where('status', 'paid')->sum('commission_amount');
                                        @endphp
                                        <div class="text-success small">
                                            Total: {{ number_format($totalCommissions) }} F
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div>
                                    <div class="fw-semibold">{{ $commission->order->order_number }}</div>
                                    <div class="text-muted small">
                                        {{ number_format($commission->order->total_amount) }} F
                                    </div>
                                    <div class="text-muted small">
                                        {{ $commission->order->created_at->format('d/m/Y H:i') }}
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div>
                                    <div class="fw-semibold">{{ Str::limit($commission->order->event->title, 20) }}</div>
                                    <div class="text-muted small">
                                        <i class="fas fa-calendar me-1"></i>
                                        {{ \Carbon\Carbon::parse($commission->order->event->event_date)->format('d/m/Y') }}
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="fw-bold text-success h5 mb-1">{{ number_format($commission->amount) }} F</div>
                                @if($commission->net_amount)
                                <div class="text-muted small">
                                    Net: {{ number_format($commission->net_amount) }} F
                                </div>
                                @endif
                                <div class="text-muted small">
                                    {{ round(($commission->amount / $commission->order->total_amount) * 100, 1) }}% du total
                                </div>
                            </td>
                            <td>
                                @switch($commission->status)
                                    @case('paid')
                                        <span class="badge bg-success">
                                            <i class="fas fa-check-circle me-1"></i>Payé
                                        </span>
                                        @if($commission->paid_at)
                                        <div class="text-success small mt-1">
                                            {{ $commission->paid_at->format('d/m/Y') }}
                                        </div>
                                        @endif
                                        @break
                                    @case('pending')
                                        <span class="badge bg-warning">
                                            <i class="fas fa-clock me-1"></i>En attente
                                        </span>
                                        @break
                                    @case('held')
                                        <span class="badge bg-info">
                                            <i class="fas fa-pause-circle me-1"></i>Suspendu
                                        </span>
                                        @break
                                    @case('cancelled')
                                        <span class="badge bg-danger">
                                            <i class="fas fa-times-circle me-1"></i>Annulé
                                        </span>
                                        @break
                                @endswitch
                            </td>
                            <td>
                                <div class="fw-semibold">{{ $commission->created_at->format('d/m/Y') }}</div>
                                <div class="text-muted small">{{ $commission->created_at->format('H:i') }}</div>
                                <div class="text-muted small">{{ $commission->created_at->diffForHumans() }}</div>
                            </td>
                            <td>
                                <div class="btn-group" role="group">
                                    <button type="button" class="btn btn-sm btn-outline-secondary dropdown-toggle" 
                                            data-bs-toggle="dropdown">
                                        <i class="fas fa-cog"></i>
                                    </button>
                                    <ul class="dropdown-menu">
                                        @if($commission->status === 'pending')
                                        <li>
                                            <form action="{{ route('admin.finances.commissions.update-status', $commission) }}" method="POST" class="d-inline">
                                                @csrf
                                                @method('PATCH')
                                                <input type="hidden" name="status" value="paid">
                                                <button type="submit" class="dropdown-item text-success">
                                                    <i class="fas fa-check-circle me-2"></i>Marquer payé
                                                </button>
                                            </form>
                                        </li>
                                        <li>
                                            <form action="{{ route('admin.finances.commissions.update-status', $commission) }}" method="POST" class="d-inline">
                                                @csrf
                                                @method('PATCH')
                                                <input type="hidden" name="status" value="held">
                                                <button type="submit" class="dropdown-item text-info">
                                                    <i class="fas fa-pause-circle me-2"></i>Suspendre
                                                </button>
                                            </form>
                                        </li>
                                        @endif
                                        @if($commission->status === 'held')
                                        <li>
                                            <form action="{{ route('admin.finances.commissions.update-status', $commission) }}" method="POST" class="d-inline">
                                                @csrf
                                                @method('PATCH')
                                                <input type="hidden" name="status" value="pending">
                                                <button type="submit" class="dropdown-item text-warning">
                                                    <i class="fas fa-play-circle me-2"></i>Réactiver
                                                </button>
                                            </form>
                                        </li>
                                        @endif
                                        <li><hr class="dropdown-divider"></li>
                                        <li>
                                            <button type="button" class="dropdown-item text-secondary" 
                                                    onclick="showNotesModal('{{ $commission->id }}', '{{ addslashes($commission->admin_notes ?? '') }}')">
                                                <i class="fas fa-sticky-note me-2"></i>Ajouter note
                                            </button>
                                        </li>
                                    </ul>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @else
            <div class="text-center py-5">
                <i class="fas fa-coins fa-3x text-muted mb-3"></i>
                <h5 class="text-muted">Aucune commission trouvée</h5>
                <p class="text-muted mb-0">
                    @if(request()->hasAny(['promoter', 'status', 'date_from', 'date_to']))
                        Aucune commission ne correspond à vos critères de recherche.
                    @else
                        Il n'y a pas encore de commission dans la plateforme.
                    @endif
                </p>
            </div>
            @endif
        </div>

        @if($commissions->hasPages())
        <div class="card-footer bg-transparent border-0">
            <div class="d-flex justify-content-between align-items-center">
                <div class="text-muted small">
                    Affichage de {{ $commissions->firstItem() }} à {{ $commissions->lastItem() }} 
                    sur {{ $commissions->total() }} commission(s)
                </div>
                {{ $commissions->appends(request()->query())->links() }}
            </div>
        </div>
        @endif
    </div>
</div>

<!-- Modal paiement en lot -->
<div class="modal fade" id="bulkPayModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">💳 Paiement en lot des commissions</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('admin.finances.commissions.bulk-pay') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        Sélectionnez les commissions à payer dans la liste, puis cliquez sur "Confirmer le paiement".
                    </div>
                    
                    <div id="selected-commissions-summary" class="mb-3">
                        <strong>Commissions sélectionnées :</strong>
                        <ul id="commission-list" class="list-unstyled mt-2"></ul>
                        <hr>
                        <div class="d-flex justify-content-between">
                            <span>Total à payer :</span>
                            <strong id="total-amount">0 F</strong>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-success" id="confirm-payment" disabled>
                        <i class="fas fa-credit-card me-1"></i> Confirmer le paiement
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal notes admin -->
<div class="modal fade" id="notesModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">📝 Notes administratives</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="notesForm" method="POST">
                @csrf
                @method('PATCH')
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Notes internes</label>
                        <textarea class="form-control" name="admin_notes" id="admin_notes" rows="4"
                                  placeholder="Ajouter des notes internes pour cette commission..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i> Enregistrer
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/admin-commissions.js') }}" defer></script>
@endpush

@push('styles')
<style>
.table th {
    border-top: none;
    font-weight: 600;
    font-size: 0.875rem;
    color: #6c757d;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.table td {
    vertical-align: middle;
    border-top: 1px solid #f0f0f0;
}

.table tbody tr:hover {
    background-color: #f8f9fa;
}

.badge {
    font-size: 0.75rem;
    font-weight: 600;
}

.card {
    transition: all 0.2s ease-in-out;
}

.card:hover {
    transform: translateY(-1px);
}

.bg-opacity-10 {
    background-color: rgba(var(--bs-primary-rgb), 0.1) !important;
}

.commission-checkbox[data-status="paid"], 
.commission-checkbox[data-status="cancelled"] {
    opacity: 0.5;
    cursor: not-allowed;
}

#selected-commissions-summary {
    max-height: 200px;
    overflow-y: auto;
    border: 1px solid #dee2e6;
    border-radius: 0.375rem;
    padding: 1rem;
    background-color: #f8f9fa;
}

@media (max-width: 768px) {
    .table-responsive {
        font-size: 0.875rem;
    }
    
    .h5 {
        font-size: 1rem;
    }
}
</style>
@endpush