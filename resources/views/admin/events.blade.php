{{-- resources/views/admin/events.blade.php --}}
@extends('layouts.admin')

@php
use Illuminate\Support\Facades\Storage;
@endphp

@section('title', 'Gestion des événements - ClicBillet CI')

@section('breadcrumb')
    <li class="breadcrumb-item active">Événements</li>
@endsection

@section('content')
    <!-- Header avec actions -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-1">Gestion des événements</h2>
            <p class="text-muted mb-0">Supervisez tous les événements de la plateforme</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.export.events') }}" class="btn btn-outline-orange">
                <i class="fas fa-download me-2"></i>Exporter
            </a>
            <button class="btn btn-orange" data-bs-toggle="modal" data-bs-target="#createEventModal">
                <i class="fas fa-plus me-2"></i>Nouvel événement
            </button>
        </div>
    </div>

    <!-- Statistiques rapides -->
    <div class="row mb-4">
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="stat-card">
                <div class="d-flex align-items-center">
                    <div class="stat-icon primary me-3">
                        <i class="fas fa-calendar-alt"></i>
                    </div>
                    <div class="stat-info">
                        <h4>{{ $stats['total_events'] ?? 0 }}</h4>
                        <p class="text-muted mb-0">Total événements</p>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="stat-card">
                <div class="d-flex align-items-center">
                    <div class="stat-icon success me-3">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stat-info">
                        <h4>{{ $stats['published_events'] ?? 0 }}</h4>
                        <p class="text-muted mb-0">Publiés</p>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="stat-card">
                <div class="d-flex align-items-center">
                    <div class="stat-icon warning me-3">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="stat-info">
                        <h4>{{ $stats['pending_events'] ?? 0 }}</h4>
                        <p class="text-muted mb-0">En attente</p>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="stat-card">
                <div class="d-flex align-items-center">
                    <div class="stat-icon info me-3">
                        <i class="fas fa-ticket-alt"></i>
                    </div>
                    <div class="stat-info">
                        <h4>{{ $stats['tickets_sold'] ?? 0 }}</h4>
                        <p class="text-muted mb-0">Billets vendus</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filtres et recherche -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0"><i class="fas fa-filter me-2"></i>Filtres</h5>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('admin.events') }}">
                <div class="row align-items-end">
                    <div class="col-md-3">
                        <label class="form-label">Recherche</label>
                        <input type="text" name="search" class="form-control" 
                               placeholder="Titre, promoteur..." value="{{ request('search') }}">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Statut</label>
                        <select name="status" class="form-select">
                            <option value="">Tous</option>
                            <option value="published" {{ request('status') == 'published' ? 'selected' : '' }}>Publié</option>
                            <option value="draft" {{ request('status') == 'draft' ? 'selected' : '' }}>Brouillon</option>
                            <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>En attente</option>
                            <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Annulé</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Catégorie</label>
                        <select name="category" class="form-select">
                            <option value="">Toutes</option>
                            @foreach($categories ?? [] as $category)
                                <option value="{{ $category->id }}" {{ request('category') == $category->id ? 'selected' : '' }}>
                                    {{ $category->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Promoteur</label>
                        <select name="promoter" class="form-select">
                            <option value="">Tous</option>
                            @foreach($promoters ?? [] as $promoter)
                                <option value="{{ $promoter->id }}" {{ request('promoter') == $promoter->id ? 'selected' : '' }}>
                                    {{ $promoter->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Période</label>
                        <select name="period" class="form-select">
                            <option value="">Toutes</option>
                            <option value="upcoming" {{ request('period') == 'upcoming' ? 'selected' : '' }}>À venir</option>
                            <option value="ongoing" {{ request('period') == 'ongoing' ? 'selected' : '' }}>En cours</option>
                            <option value="past" {{ request('period') == 'past' ? 'selected' : '' }}>Passés</option>
                        </select>
                    </div>
                    <div class="col-md-1">
                        <button type="submit" class="btn btn-orange w-100">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Liste des événements -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="fas fa-calendar-alt me-2"></i>Liste des événements</h5>
            <div class="d-flex gap-2">
                <button class="btn btn-sm btn-outline-secondary" onclick="toggleView('grid')">
                    <i class="fas fa-th"></i>
                </button>
                <button class="btn btn-sm btn-secondary" onclick="toggleView('list')">
                    <i class="fas fa-list"></i>
                </button>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>
                                <input type="checkbox" id="select-all" class="form-check-input">
                            </th>
                            <th>Événement</th>
                            <th>Promoteur</th>
                            <th>Catégorie</th>
                            <th>Date</th>
                            <th>Statut</th>
                            <th>Ventes</th>
                            <th>Revenus</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($events ?? [] as $event)
                            <tr>
                                <td>
                                    <input type="checkbox" class="form-check-input event-checkbox" value="{{ $event->id }}">
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        @if($event->image)
                                            <img src="{{ Storage::url( $event->image) }}" 
                                                 alt="{{ $event->title }}" 
                                                 class="rounded me-3" 
                                                 style="width: 50px; height: 50px; object-fit: cover;">
                                        @else
                                            <div class="bg-light rounded me-3 d-flex align-items-center justify-content-center" 
                                                 style="width: 50px; height: 50px;">
                                                <i class="fas fa-calendar-alt text-muted"></i>
                                            </div>
                                        @endif
                                        <div>
                                            <div class="fw-semibold">{{ $event->title }}</div>
                                            <small class="text-muted">{{ Str::limit($event->description, 50) }}</small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="user-avatar me-2">
                                            <i class="fas fa-user"></i>
                                        </div>
                                        <div>
                                            <div class="fw-semibold">{{ $event->promoteur->name ?? 'N/A' }}</div>
                                            <small class="text-muted">{{ $event->promoteur->email ?? '' }}</small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge" style="background-color: {{ $event->category->color ?? '#6c757d' }}">
                                        {{ $event->category->name ?? 'Non catégorisé' }}
                                    </span>
                                </td>
                                <td>
                                    <div>
                                        <div class="fw-semibold">{{ $event->event_date->format('d/m/Y') }}</div>
                                        <small class="text-muted">{{ $event->event_date->format('H:i') }}</small>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge {{ 
                                        $event->status == 'published' ? 'bg-success' : 
                                        ($event->status == 'pending' ? 'bg-warning' : 
                                        ($event->status == 'cancelled' ? 'bg-danger' : 'bg-secondary'))
                                    }}">
                                        {{ ucfirst($event->status) }}
                                    </span>
                                </td>
                                <td>
                                    <div class="text-center">
                                        <div class="fw-semibold">{{ $event->tickets_sold ?? 0 }}</div>
                                        <small class="text-muted">/ {{ $event->total_capacity ?? 'Illimité' }}</small>
                                    </div>
                                </td>
                                <td class="fw-semibold text-success">
                                    {{ number_format($event->total_revenue ?? 0) }} FCFA
                                </td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <a href="{{ route('admin.events', $event) }}" 
                                           class="btn btn-sm btn-outline-primary" 
                                           title="Voir détails">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="{{ route('admin.events.edit', $event) }}" 
                                           class="btn btn-sm btn-outline-warning"
                                           title="Modifier">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <div class="btn-group" role="group">
                                            <button class="btn btn-sm btn-outline-secondary dropdown-toggle" 
                                                    data-bs-toggle="dropdown">
                                                <i class="fas fa-ellipsis-v"></i>
                                            </button>
                                            <ul class="dropdown-menu">
                                                @if($event->status == 'pending')
                                                    <li>
                                                        <button class="dropdown-item" 
                                                                onclick="updateEventStatus({{ $event->id }}, 'published')">
                                                            <i class="fas fa-check me-2"></i>Publier
                                                        </button>
                                                    </li>
                                                @endif
                                                @if($event->status == 'published')
                                                    <li>
                                                        <button class="dropdown-item" 
                                                                onclick="updateEventStatus({{ $event->id }}, 'cancelled')">
                                                            <i class="fas fa-ban me-2"></i>Annuler
                                                        </button>
                                                    </li>
                                                @endif
                                                <li><hr class="dropdown-divider"></li>
                                                <li>
                                                    <a class="dropdown-item" href="{{ route('events.show', $event) }}" target="_blank">
                                                        <i class="fas fa-external-link-alt me-2"></i>Voir sur le site
                                                    </a>
                                                </li>
                                                <li>
                                                    <button class="dropdown-item text-danger" 
                                                            onclick="deleteEvent({{ $event->id }})">
                                                        <i class="fas fa-trash me-2"></i>Supprimer
                                                    </button>
                                                </li>
                                            </ul>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center p-4">
                                    <i class="fas fa-calendar-alt fa-3x text-muted mb-3"></i>
                                    <p class="text-muted mb-0">Aucun événement trouvé</p>
                                    <button class="btn btn-orange mt-2" data-bs-toggle="modal" data-bs-target="#createEventModal">
                                        <i class="fas fa-plus me-2"></i>Créer le premier événement
                                    </button>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        
        @if(isset($events) && $events->hasPages())
            <div class="card-footer">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="text-muted">
                        Affichage de {{ $events->firstItem() ?? 0 }} à {{ $events->lastItem() ?? 0 }} 
                        sur {{ $events->total() ?? 0 }} événements
                    </div>
                    {{ $events->appends(request()->query())->links() }}
                </div>
            </div>
        @endif
    </div>

    <!-- Actions groupées -->
    <div class="card mt-4" id="bulk-actions" style="display: none;">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <strong id="selected-count">0</strong> événement(s) sélectionné(s)
                </div>
                <div class="btn-group">
                    <button class="btn btn-success" onclick="bulkUpdateStatus('published')">
                        <i class="fas fa-check me-2"></i>Publier
                    </button>
                    <button class="btn btn-warning" onclick="bulkUpdateStatus('pending')">
                        <i class="fas fa-clock me-2"></i>Mettre en attente
                    </button>
                    <button class="btn btn-danger" onclick="bulkUpdateStatus('cancelled')">
                        <i class="fas fa-ban me-2"></i>Annuler
                    </button>
                    <button class="btn btn-outline-danger" onclick="bulkDelete()">
                        <i class="fas fa-trash me-2"></i>Supprimer
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
// Gestion des sélections
document.addEventListener('DOMContentLoaded', function() {
    const selectAll = document.getElementById('select-all');
    const checkboxes = document.querySelectorAll('.event-checkbox');
    const bulkActions = document.getElementById('bulk-actions');
    const selectedCount = document.getElementById('selected-count');

    // Sélectionner tout
    selectAll.addEventListener('change', function() {
        checkboxes.forEach(checkbox => {
            checkbox.checked = this.checked;
        });
        updateBulkActions();
    });

    // Sélection individuelle
    checkboxes.forEach(checkbox => {
        checkbox.addEventListener('change', updateBulkActions);
    });

    function updateBulkActions() {
        const selected = document.querySelectorAll('.event-checkbox:checked');
        selectedCount.textContent = selected.length;
        bulkActions.style.display = selected.length > 0 ? 'block' : 'none';
    }
});

// Mettre à jour le statut d'un événement
function updateEventStatus(eventId, status) {
    if (confirm(`Confirmer le changement de statut vers "${status}" ?`)) {
        ajaxRequest(`/admin/events/${eventId}/status`, 'PATCH', { status: status })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Erreur lors de la mise à jour');
                }
            });
    }
}

// Supprimer un événement
function deleteEvent(eventId) {
    if (confirm('Êtes-vous sûr de vouloir supprimer cet événement ? Cette action est irréversible.')) {
        ajaxRequest(`/admin/events/${eventId}`, 'DELETE')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Erreur lors de la suppression');
                }
            });
    }
}

// Actions groupées
function bulkUpdateStatus(status) {
    const selected = Array.from(document.querySelectorAll('.event-checkbox:checked')).map(cb => cb.value);
    
    if (selected.length === 0) return;
    
    if (confirm(`Mettre à jour ${selected.length} événement(s) vers "${status}" ?`)) {
        ajaxRequest('/admin/events/bulk-update', 'POST', { 
            event_ids: selected, 
            status: status 
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Erreur lors de la mise à jour groupée');
            }
        });
    }
}

function bulkDelete() {
    const selected = Array.from(document.querySelectorAll('.event-checkbox:checked')).map(cb => cb.value);
    
    if (selected.length === 0) return;
    
    if (confirm(`Supprimer définitivement ${selected.length} événement(s) ? Cette action est irréversible.`)) {
        ajaxRequest('/admin/events/bulk-delete', 'POST', { 
            event_ids: selected 
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Erreur lors de la suppression groupée');
            }
        });
    }
}

// Basculer la vue
function toggleView(viewType) {
    // Implementation pour changer entre vue grille et liste
    console.log('Basculer vers vue:', viewType);
}
</script>
@endpush