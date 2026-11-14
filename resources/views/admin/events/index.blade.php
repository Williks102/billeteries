@extends('layouts.admin')

@section('title', 'Gestion des Événements')

@section('content')
<div class="container-fluid py-4">
    
    <!-- Header avec filtres et actions -->
    <div class="row align-items-center mb-4">
        <div class="col">
            <h1 class="h3 mb-0">🎫 Gestion des Événements</h1>
            <p class="text-muted mb-0">{{ $events->total() }} événement(s) créé(s) · Période : {{ $period->label }}</p>
        </div>
        <div class="col-auto">
            <div class="btn-group" role="group">
                <a href="{{ route('admin.events.create') }}" class="btn btn-primary">
                    <i class="fas fa-plus me-1"></i> Nouvel Événement
                </a>
                <button type="button" class="btn btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown">
                    <i class="fas fa-download me-1"></i> Exporter
                </button>
                <ul class="dropdown-menu">
                    <li><a class="dropdown-item" href="{{ route('admin.events.export', request()->query()) }}">
                        <i class="fas fa-file-csv me-2"></i> Export CSV
                    </a></li>
                    <li><a class="dropdown-item" href="#" data-on-click="print">
                        <i class="fas fa-print me-2"></i> Imprimer
                    </a></li>
                </ul>
            </div>
        </div>
    </div>

    <x-period-filter :action="route('admin.events.index')" :period="$period" :keep="request()->only(['status', 'category', 'search', 'promoteur'])" />

    <x-stat-grid label="Chiffres des événements">
        <x-stat-card label="Événements publiés" :value="$stats['published']" hint="Total actuel" />
        <x-stat-card label="Brouillons" :value="$stats['draft']" hint="Total actuel" />
        <x-stat-card label="Ventes de la période (billets, hors frais)" unit="FCFA" :value="$periodSales" hint="Commandes payées, tous événements" />
    </x-stat-grid>

    <!-- Filtres -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.events.index') }}" class="row g-3">
                @foreach(request()->only(['period', 'start_date', 'end_date']) as $k => $v)
                <input type="hidden" name="{{ $k }}" value="{{ $v }}">
                @endforeach
                <div class="col-md-3">
                    <label class="form-label small fw-semibold text-muted" for="search">RECHERCHE</label>
                    <input id="search" type="text" class="form-control" name="search" value="{{ request('search') }}" 
                           placeholder="Titre, description, promoteur...">
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold text-muted" for="status">STATUT</label>
                    <select id="status" class="form-select" name="status">
                        <option value="">Tous les statuts</option>
                        <option value="published" {{ request('status') === 'published' ? 'selected' : '' }}>Publié</option>
                        <option value="draft" {{ request('status') === 'draft' ? 'selected' : '' }}>Brouillon</option>
                        <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>Annulé</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold text-muted" for="category">CATÉGORIE</label>
                    <select id="category" class="form-select" name="category">
                        <option value="">Toutes catégories</option>
                        @foreach($categories as $category)
                        <option value="{{ $category->id }}" {{ request('category') == $category->id ? 'selected' : '' }}>
                            {{ $category->name }}
                        </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold text-muted" for="promoteur">PROMOTEUR</label>
                    <select id="promoteur" class="form-select" name="promoteur">
                        <option value="">Tous promoteurs</option>
                        @foreach($promoteurs as $promoteur)
                        <option value="{{ $promoteur->id }}" {{ request('promoteur') == $promoteur->id ? 'selected' : '' }}>
                            {{ $promoteur->name }}
                        </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-semibold text-muted" for="action">ACTIONS</label>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-filter me-1"></i> Filtrer
                        </button>
                        <a href="{{ route('admin.events.index') }}" class="btn btn-outline-secondary">
                            <i class="fas fa-times me-1"></i> Reset
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Actions en lot -->
    <div class="card border-0 shadow-sm mb-4" id="bulk-actions" style="display: none;">
        <div class="card-body bg-light">
            <form id="bulk-form" action="{{ route('admin.events.bulk-action') }}" method="POST" class="row g-3 align-items-end">
                @csrf
                <div class="col-md-3">
                    <label class="form-label small fw-semibold">ACTIONS EN LOT</label>
                    <select id="action" class="form-select" name="action" required>
                        <option value="">Choisir une action...</option>
                        <option value="publish">Publier les événements</option>
                        <option value="delete">Supprimer les événements</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <span id="selected-count" class="text-muted">0 événement(s) sélectionné(s)</span>
                </div>
                <div class="col-md-3 text-end">
                    <button type="submit" class="btn btn-warning me-2">
                        <i class="fas fa-bolt me-1"></i> Exécuter
                    </button>
                    <button type="button" class="btn btn-outline-secondary" data-on-click="events-clear-selection">
                        Annuler
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Liste des événements -->
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            @if($events->isNotEmpty())
            <div class="table-responsive">
                <table class="table table-hover mb-0 table-cards">
                    <thead class="bg-light">
                        <tr>
                            <th width="40">
                                <input type="checkbox" class="form-check-input" id="select-all" aria-label="Tout sélectionner">
                            </th>
                            <th>Événement</th>
                            <th>Promoteur</th>
                            <th>Catégorie</th>
                            <th>Date</th>
                            <th>Statut</th>
                            <th>Billets vendus</th>
                            <th>Ventes (hors frais)</th>
                            <th width="120">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($events as $event)
                        <tr>
                            <td>
                                <input type="checkbox" class="form-check-input row-checkbox" 
                                       name="events[]" aria-label="Sélectionner l'événement {{ $event->title }}" value="{{ $event->id }}">
                            </td>
                            <td data-label="Événement">
                                <div class="d-flex align-items-center">
                                    @if($event->image)
                                    <img src="{{ \App\Support\Media::url($event->image) }}" alt="{{ $event->title }}" 
                                         class="rounded me-3" style="width: 50px; height: 50px; object-fit: cover;">
                                    @else
                                    <div class="bg-secondary bg-opacity-10 rounded me-3 d-flex align-items-center justify-content-center" 
                                         style="width: 50px; height: 50px;">
                                        <i class="fas fa-calendar-alt text-secondary"></i>
                                    </div>
                                    @endif
                                    <div>
                                        <div class="fw-semibold">{{ $event->title }}</div>
                                        <div class="text-muted small">{{ Str::limit($event->description, 50) }}</div>
                                        <div class="text-muted small">
                                            <i class="fas fa-map-marker-alt me-1"></i>{{ $event->venue }}
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td data-label="Promoteur">
                                <div class="d-flex align-items-center">
                                    <div class="bg-primary bg-opacity-10 rounded-circle me-2 d-flex align-items-center justify-content-center" 
                                         style="width: 35px; height: 35px;">
                                        <span class="fw-bold text-primary small">
                                            {{ substr($event->promoteur->name ?? 'N/A', 0, 1) }}
                                        </span>
                                    </div>
                                    <div>
                                        <div class="fw-semibold">{{ $event->promoteur->name ?? 'N/A' }}</div>
                                        <div class="text-muted small">{{ $event->promoteur->email ?? '' }}</div>
                                    </div>
                                </div>
                            </td>
                            <td data-label="Catégorie">
                                @if($event->category)
                                <span class="badge rounded-pill" style="background-color: {{ $event->category->color ?? '#6c757d' }}20; color: {{ $event->category->color ?? '#6c757d' }};">
                                    @if($event->category->icon)
                                    <i class="{{ $event->category->icon }} me-1"></i>
                                    @endif
                                    {{ $event->category->name }}
                                </span>
                                @else
                                <span class="text-muted">Non catégorisé</span>
                                @endif
                            </td>
                            <td data-label="Date">
                                <div class="fw-semibold">{{ \Carbon\Carbon::parse($event->event_date)->format('d/m/Y') }}</div>
                                <div class="text-muted small">{{ $event->event_time }}</div>
                                <div class="text-muted small">
                                    @if(\Carbon\Carbon::parse($event->event_date)->isPast())
                                        <i class="fas fa-history me-1 text-warning"></i>Passé
                                    @else
                                        <i class="fas fa-clock me-1 text-success"></i>À venir
                                    @endif
                                </div>
                            </td>
                            <td data-label="Statut">
                                @switch($event->status)
                                    @case('published')
                                        <span class="badge bg-success">
                                            <i class="fas fa-check-circle me-1"></i>Publié
                                        </span>
                                        @break
                                    @case('draft')
                                        <span class="badge bg-secondary">
                                            <i class="fas fa-file-alt me-1"></i>Brouillon
                                        </span>
                                        @break
                                    @case('cancelled')
                                        <span class="badge bg-danger">
                                            <i class="fas fa-times-circle me-1"></i>Annulé
                                        </span>
                                        @break
                                @endswitch
                            </td>
                            <td data-label="Billets vendus">
                                @php $row = $eventStats[$event->id]; @endphp
                                <div class="fw-semibold">{{ number_format($row['sold'], 0, ',', ' ') }} / {{ number_format($row['capacity'], 0, ',', ' ') }}</div>
                                <div class="progress mt-1" style="height: 4px;" role="progressbar" aria-label="Taux de remplissage" aria-valuenow="{{ $row['fill_rate'] ?? 0 }}" aria-valuemin="0" aria-valuemax="100">
                                    <div class="progress-bar bg-success" style="width: {{ $row['fill_rate'] ?? 0 }}%"></div>
                                </div>
                                <div class="text-muted small">{{ $row['fill_rate'] === null ? '—' : number_format($row['fill_rate'], 1, ',', ' ').' %' }} vendus (billets payés)</div>
                            </td>
                            <td data-label="Ventes">
                                <div class="fw-semibold text-success">{{ \App\Helpers\CurrencyHelper::formatFCFA($row['gmv']) }}</div>
                                <div class="text-muted small">{{ $row['orders_paid'] }} commande(s) payée(s) sur {{ $row['orders_total'] }}</div>
                            </td>
                            <td data-label="Actions">
                                <div class="btn-group" role="group">
                                    <a href="{{ route('admin.events.show', $event) }}" class="btn btn-sm btn-outline-primary" 
                                       title="Voir détails">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <button type="button" class="btn btn-sm btn-outline-secondary dropdown-toggle dropdown-toggle-split" 
                                            data-bs-toggle="dropdown"></button>
                                    <ul class="dropdown-menu">
                                        <li>
                                            <a class="dropdown-item" href="{{ route('admin.events.edit', $event) }}">
                                                <i class="fas fa-edit me-2"></i>Modifier
                                            </a>
                                        </li>
                                        <li>
                                            <form action="{{ route('admin.events.duplicate', $event) }}" method="POST">
                                                @csrf
                                                <button type="submit" class="dropdown-item">
                                                    <i class="fas fa-copy me-2"></i>Dupliquer
                                                </button>
                                            </form>
                                        </li>
                                        <li><hr class="dropdown-divider"></li>
                                                                                @if($event->status === 'published')
                                        <li>
                                            <form action="{{ route('admin.events.update-status', $event) }}" method="POST" class="d-inline">
                                                @csrf
                                                @method('PATCH')
                                                <input type="hidden" name="status" value="draft">
                                                <button type="submit" class="dropdown-item text-secondary">
                                                    <i class="fas fa-file-alt me-2"></i>Dépublier
                                                </button>
                                            </form>
                                        </li>
                                        @endif
                                        <li><hr class="dropdown-divider"></li>
                                        <li>
                                            <form action="{{ route('admin.events.destroy', $event) }}" method="POST" 
                                                  data-confirm="Êtes-vous sûr de vouloir supprimer cet événement ?" class="d-inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="dropdown-item text-danger">
                                                    <i class="fas fa-trash me-2"></i>Supprimer
                                                </button>
                                            </form>
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
                <i class="fas fa-calendar-times fa-3x text-muted mb-3"></i>
                <h5 class="text-muted">Aucun événement trouvé</h5>
                <p class="text-muted mb-3">
                    @if(request()->hasAny(['search', 'status', 'category', 'promoteur']))
                        Aucun événement ne correspond à vos critères de recherche.
                    @else
                        Il n'y a pas encore d'événement dans la plateforme.
                    @endif
                </p>
                <a href="{{ route('admin.events.create') }}" class="btn btn-primary">
                    <i class="fas fa-plus me-1"></i> Créer le premier événement
                </a>
            </div>
            @endif
        </div>

        @if($events->hasPages())
        <div class="card-footer bg-transparent border-0">
            <div class="d-flex justify-content-between align-items-center">
                <div class="text-muted small">
                    Affichage de {{ $events->firstItem() }} à {{ $events->lastItem() }} 
                    sur {{ $events->total() }} événement(s)
                </div>
                {{ $events->appends(request()->query())->links() }}
            </div>
        </div>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/admin-events-index.js') }}" defer></script>
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

.progress {
    background-color: #e9ecef;
}

.btn-group .dropdown-toggle-split {
    padding-left: 0.375rem;
    padding-right: 0.375rem;
}

.card {
    transition: all 0.2s ease-in-out;
}

.form-check-input:indeterminate {
    background-color: #0d6efd;
    border-color: #0d6efd;
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 20 20'%3e%3cpath fill='none' stroke='%23fff' stroke-linecap='round' stroke-linejoin='round' stroke-width='3' d='M6 10h8'/%3e%3c/svg%3e");
}

.bg-opacity-10 {
    background-color: rgba(var(--bs-primary-rgb), 0.1) !important;
}

.text-truncate-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

@media (max-width: 768px) {
    .table-responsive {
        font-size: 0.875rem;
    }
    
    .btn-group .btn {
        padding: 0.25rem 0.5rem;
    }
}
</style>
@endpush