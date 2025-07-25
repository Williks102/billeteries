@extends('layouts.app')

@section('title', 'Gestion des événements - Admin')
@section('body-class', 'admin-page')

@section('content')
<div class="container-fluid py-4">
    <div class="row">
        <!-- Sidebar Admin -->
        <div class="col-md-3 col-lg-2">
            <div class="admin-sidebar bg-dark text-white rounded p-3 sticky-top">
                <h5 class="mb-4">
                    <i class="fas fa-shield-alt text-orange me-2"></i>
                    Administration
                </h5>
                
                <nav class="nav flex-column">
                    <a class="nav-link text-light" href="{{ route('admin.dashboard') }}">
                        <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                    </a>
                    <a class="nav-link text-light" href="{{ route('admin.users') }}">
                        <i class="fas fa-users me-2"></i>Utilisateurs
                    </a>
                    <a class="nav-link active text-white" href="{{ route('admin.events') }}">
                        <i class="fas fa-calendar me-2"></i>Événements
                    </a>
                    <a class="nav-link text-light" href="{{ route('admin.orders') }}">
                        <i class="fas fa-shopping-cart me-2"></i>Commandes
                    </a>
                    <a class="nav-link text-light" href="{{ route('admin.commissions') }}">
                        <i class="fas fa-coins me-2"></i>Commissions
                    </a>
                    
                    <hr class="my-3" style="border-color: #444;">
                    
                    <a class="nav-link text-light" href="{{ route('home') }}">
                        <i class="fas fa-eye me-2"></i>Voir le site
                    </a>
                </nav>
            </div>
        </div>
        
        <!-- Contenu principal -->
        <div class="col-md-9 col-lg-10">
            <!-- En-tête -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="mb-1">Gestion des événements</h2>
                    <p class="text-muted mb-0">Gérez tous les événements de la plateforme</p>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <div class="dropdown">
                        <button class="btn btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                            <i class="fas fa-filter me-2"></i>Actions rapides
                        </button>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="#"><i class="fas fa-eye me-2"></i>Événements publiés</a></li>
                            <li><a class="dropdown-item" href="#"><i class="fas fa-clock me-2"></i>En attente</a></li>
                            <li><a class="dropdown-item" href="#"><i class="fas fa-ban me-2"></i>Événements rejetés</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="#"><i class="fas fa-download me-2"></i>Exporter la liste</a></li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Statistiques rapides -->
            <div class="row mb-4">
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="stat-card">
                        <div class="d-flex align-items-center">
                            <div class="stat-icon bg-primary">
                                <i class="fas fa-calendar"></i>
                            </div>
                            <div class="stat-info ms-3">
                                <h4 class="mb-0">{{ $events->total() }}</h4>
                                <p class="text-muted mb-0">Total événements</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="stat-card">
                        <div class="d-flex align-items-center">
                            <div class="stat-icon bg-success">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <div class="stat-info ms-3">
                                <h4 class="mb-0">{{ $events->where('status', 'published')->count() }}</h4>
                                <p class="text-muted mb-0">Publiés</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="stat-card">
                        <div class="d-flex align-items-center">
                            <div class="stat-icon bg-warning">
                                <i class="fas fa-clock"></i>
                            </div>
                            <div class="stat-info ms-3">
                                <h4 class="mb-0">{{ $events->where('status', 'draft')->count() }}</h4>
                                <p class="text-muted mb-0">Brouillons</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="stat-card">
                        <div class="d-flex align-items-center">
                            <div class="stat-icon bg-info">
                                <i class="fas fa-calendar-check"></i>
                            </div>
                            <div class="stat-info ms-3">
                                <h4 class="mb-0">{{ $events->where('event_date', '>=', now())->count() }}</h4>
                                <p class="text-muted mb-0">À venir</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filtres et recherche -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body">
                    <form method="GET" action="{{ route('admin.events') }}" class="row align-items-end">
                        <div class="col-md-3">
                            <label class="form-label">Rechercher</label>
                            <input type="text" name="search" class="form-control" 
                                   placeholder="Titre, lieu..." value="{{ request('search') }}">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Statut</label>
                            <select name="status" class="form-select">
                                <option value="">Tous</option>
                                <option value="published" {{ request('status') == 'published' ? 'selected' : '' }}>Publié</option>
                                <option value="draft" {{ request('status') == 'draft' ? 'selected' : '' }}>Brouillon</option>
                                <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Annulé</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Catégorie</label>
                            <select name="category" class="form-select">
                                <option value="">Toutes</option>
                                @if(isset($categories))
                                    @foreach($categories as $category)
                                        <option value="{{ $category->id }}" {{ request('category') == $category->id ? 'selected' : '' }}>
                                            {{ $category->name }}
                                        </option>
                                    @endforeach
                                @endif
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Période</label>
                            <select name="period" class="form-select">
                                <option value="">Toutes</option>
                                <option value="upcoming" {{ request('period') == 'upcoming' ? 'selected' : '' }}>À venir</option>
                                <option value="past" {{ request('period') == 'past' ? 'selected' : '' }}>Passés</option>
                                <option value="today" {{ request('period') == 'today' ? 'selected' : '' }}>Aujourd'hui</option>
                                <option value="this_week" {{ request('period') == 'this_week' ? 'selected' : '' }}>Cette semaine</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-search"></i> Filtrer
                            </button>
                        </div>
                        <div class="col-md-1">
                            <a href="{{ route('admin.events') }}" class="btn btn-outline-secondary w-100">
                                <i class="fas fa-refresh"></i>
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Liste des événements -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="fas fa-calendar text-orange me-2"></i>
                            Liste des événements ({{ $events->total() }})
                        </h5>
                        <div class="dropdown">
                            <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                <i class="fas fa-download me-1"></i>Exporter
                            </button>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="#"><i class="fas fa-file-excel me-2"></i>Excel</a></li>
                                <li><a class="dropdown-item" href="#"><i class="fas fa-file-csv me-2"></i>CSV</a></li>
                                <li><a class="dropdown-item" href="#"><i class="fas fa-file-pdf me-2"></i>PDF</a></li>
                            </ul>
                        </div>
                    </div>
                </div>
                <div class="card-body p-0">
                    @if($events->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Événement</th>
                                        <th>Promoteur</th>
                                        <th>Catégorie</th>
                                        <th>Date</th>
                                        <th>Billets</th>
                                        <th>Revenus</th>
                                        <th>Statut</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($events as $event)
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    @if($event->image)
                                                        <img src="{{ asset('storage/' . $event->image) }}" 
                                                             class="event-thumbnail me-3" alt="{{ $event->title }}">
                                                    @else
                                                        <div class="event-thumbnail-placeholder me-3">
                                                            <i class="fas fa-calendar-alt"></i>
                                                        </div>
                                                    @endif
                                                    <div>
                                                        <h6 class="mb-0">{{ Str::limit($event->title, 40) }}</h6>
                                                        <small class="text-muted">{{ $event->venue }}</small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <div>
                                                    <span class="fw-medium">{{ $event->promoteur->name ?? 'N/A' }}</span><br>
                                                    <small class="text-muted">{{ $event->promoteur->email ?? '' }}</small>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="badge bg-secondary">{{ $event->category->name ?? 'N/A' }}</span>
                                            </td>
                                            <td>
                                                <div>
                                                    <strong>{{ $event->event_date ? $event->event_date->format('d/m/Y') : 'N/A' }}</strong><br>
                                                    <small class="text-muted">
                                                        {{ $event->event_time ? $event->event_time->format('H:i') : 'Heure non définie' }}
                                                    </small>
                                                </div>
                                            </td>
                                            <td>
                                                @php
                                                    $totalTickets = $event->ticketTypes->sum('quantity_available');
                                                    $soldTickets = $event->ticketTypes->sum('quantity_sold');
                                                @endphp
                                                <div>
                                                    <strong class="text-success">{{ $soldTickets }}</strong> / {{ $totalTickets }}<br>
                                                    <small class="text-muted">
                                                        {{ $totalTickets > 0 ? round(($soldTickets / $totalTickets) * 100) : 0 }}% vendus
                                                    </small>
                                                </div>
                                            </td>
                                            <td>
                                                @php
                                                    $revenue = $event->orders()->where('payment_status', 'paid')->sum('total_amount');
                                                @endphp
                                                <strong class="text-orange">{{ number_format($revenue, 0, ',', ' ') }} FCFA</strong>
                                            </td>
                                            <td>
                                                @switch($event->status)
                                                    @case('published')
                                                        <span class="badge bg-success">Publié</span>
                                                        @break
                                                    @case('draft')
                                                        <span class="badge bg-warning">Brouillon</span>
                                                        @break
                                                    @case('cancelled')
                                                        <span class="badge bg-danger">Annulé</span>
                                                        @break
                                                    @default
                                                        <span class="badge bg-secondary">{{ ucfirst($event->status) }}</span>
                                                @endswitch
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <a href="{{ route('events.show', $event) }}" 
                                                       class="btn btn-outline-primary" 
                                                       target="_blank" title="Voir sur le site">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <a href="{{ route('admin.events.detail', $event->id) }}" 
                                                       class="btn btn-outline-info" 
                                                       title="Détails admin">
                                                        <i class="fas fa-info-circle"></i>
                                                    </a>
                                                    <div class="dropdown">
                                                        <button class="btn btn-outline-secondary dropdown-toggle" 
                                                                type="button" data-bs-toggle="dropdown">
                                                            <i class="fas fa-cog"></i>
                                                        </button>
                                                        <ul class="dropdown-menu">
                                                            @if($event->status == 'draft')
                                                                <li>
                                                                    <button class="dropdown-item" onclick="updateEventStatus({{ $event->id }}, 'published')">
                                                                        <i class="fas fa-check text-success me-2"></i>Publier
                                                                    </button>
                                                                </li>
                                                            @endif
                                                            @if($event->status == 'published')
                                                                <li>
                                                                    <button class="dropdown-item" onclick="updateEventStatus({{ $event->id }}, 'draft')">
                                                                        <i class="fas fa-eye-slash text-warning me-2"></i>Dépublier
                                                                    </button>
                                                                </li>
                                                            @endif
                                                            <li>
                                                                <button class="dropdown-item" onclick="updateEventStatus({{ $event->id }}, 'cancelled')">
                                                                    <i class="fas fa-ban text-danger me-2"></i>Annuler
                                                                </button>
                                                            </li>
                                                            <li><hr class="dropdown-divider"></li>
                                                            <li>
                                                                <button class="dropdown-item text-danger" onclick="deleteEvent({{ $event->id }})">
                                                                    <i class="fas fa-trash me-2"></i>Supprimer
                                                                </button>
                                                            </li>
                                                        </ul>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- Pagination -->
                        <div class="card-footer bg-white">
                            {{ $events->links() }}
                        </div>
                    @else
                        <div class="text-center py-5">
                            <i class="fas fa-calendar fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">Aucun événement trouvé</h5>
                            <p class="text-muted">Modifiez vos critères de recherche ou attendez que les promoteurs créent des événements</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
.admin-page {
    background-color: #f8f9fa;
}

.admin-sidebar {
    background: linear-gradient(135deg, #2c3e50, #34495e) !important;
    border-radius: 12px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.admin-sidebar .nav-link {
    color: rgba(255,255,255,0.8) !important;
    padding: 12px 16px;
    border-radius: 8px;
    margin-bottom: 5px;
    transition: all 0.3s ease;
}

.admin-sidebar .nav-link:hover,
.admin-sidebar .nav-link.active {
    background: var(--primary-orange) !important;
    color: white !important;
}

.stat-card {
    background: white;
    border-radius: 12px;
    padding: 1.5rem;
    box-shadow: 0 2px 10px rgba(0,0,0,0.08);
    border: none;
    height: 100%;
    transition: all 0.3s ease;
}

.stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 20px rgba(0,0,0,0.12);
}

.stat-icon {
    width: 50px;
    height: 50px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.25rem;
    color: white;
}

.stat-info h4 {
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--dark-blue);
}

.event-thumbnail {
    width: 50px;
    height: 50px;
    object-fit: cover;
    border-radius: 8px;
}

.event-thumbnail-placeholder {
    width: 50px;
    height: 50px;
    background: #f8f9fa;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #6c757d;
}

.card {
    border: none;
    border-radius: 12px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.08);
}

.card-header {
    border-radius: 12px 12px 0 0 !important;
    border-bottom: 1px solid #e9ecef;
}

.table th {
    border-top: none;
    font-weight: 600;
    color: #495057;
    font-size: 0.9rem;
}

.table td {
    vertical-align: middle;
    font-size: 0.9rem;
}

.badge {
    font-size: 0.75rem;
    padding: 0.4em 0.8em;
}

@media (max-width: 768px) {
    .admin-sidebar {
        margin-bottom: 2rem;
    }
    
    .stat-card {
        margin-bottom: 1rem;
    }
    
    .table-responsive {
        font-size: 0.8rem;
    }
}
</style>
@endpush

@push('scripts')
<script>
function updateEventStatus(eventId, status) {
    const statusLabels = {
        'published': 'publier',
        'draft': 'dépublier', 
        'cancelled': 'annuler'
    };
    
    const label = statusLabels[status] || status;
    
    if (confirm(`Êtes-vous sûr de vouloir ${label} cet événement ?`)) {
        fetch(`/admin/events/${eventId}/status`, {
            method: 'PATCH',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({ status: status })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification(`Événement ${label} avec succès`, 'success');
                setTimeout(() => location.reload(), 1000);
            } else {
                showNotification('Erreur lors de la mise à jour', 'error');
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            showNotification('Erreur lors de la mise à jour', 'error');
        });
    }
}

function deleteEvent(eventId) {
    if (confirm('Êtes-vous sûr de vouloir supprimer définitivement cet événement ? Cette action est irréversible.')) {
        fetch(`/admin/events/${eventId}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification('Événement supprimé avec succès', 'success');
                setTimeout(() => location.reload(), 1000);
            } else {
                showNotification('Erreur lors de la suppression', 'error');
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            showNotification('Erreur lors de la suppression', 'error');
        });
    }
}
</script>
@endpush
@endsection