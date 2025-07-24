@extends('layouts.admin')

@push('styles')
<style>
    .page-header {
        background: linear-gradient(135deg, #FF6B35, #E55A2B);
        color: white;
        padding: 2rem;
        border-radius: 15px;
        margin-bottom: 2rem;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    }
    
    .btn-black {
        background-color: #000;
        border-color: #000;
        color: white;
        border-radius: 8px;
        transition: all 0.3s ease;
    }
    
    .btn-black:hover {
        background-color: #333;
        border-color: #333;
        color: white;
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.2);
    }
    
    .table-container {
        background: white;
        border-radius: 15px;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        border-left: 4px solid #FF6B35;
        overflow: hidden;
    }
    
    .table-orange thead th {
        background: linear-gradient(135deg, #FF6B35, #E55A2B);
        color: white;
        border: none;
        font-weight: 600;
        padding: 1rem;
    }
    
    .table tbody tr {
        transition: all 0.3s ease;
    }
    
    .table tbody tr:hover {
        background-color: rgba(255, 107, 53, 0.05);
        transform: translateX(5px);
    }
    
    .table td {
        padding: 1rem;
        vertical-align: middle;
        border-bottom: 1px solid #eee;
    }
    
    .status-badge {
        padding: 0.5rem 1rem;
        border-radius: 20px;
        font-size: 0.875rem;
        font-weight: 600;
    }
    
    .status-published {
        background-color: rgba(40, 167, 69, 0.1);
        color: #28a745;
        border: 1px solid #28a745;
    }
    
    .status-draft {
        background-color: rgba(255, 193, 7, 0.1);
        color: #ffc107;
        border: 1px solid #ffc107;
    }
    
    .status-cancelled {
        background-color: rgba(220, 53, 69, 0.1);
        color: #dc3545;
        border: 1px solid #dc3545;
    }
    
    .event-title {
        font-weight: 600;
        color: #000;
        text-decoration: none;
    }
    
    .event-title:hover {
        color: #FF6B35;
    }
    
    .promoter-name {
        color: #666;
        font-size: 0.9rem;
    }
    
    .search-box {
        border: 2px solid #FF6B35;
        border-radius: 10px;
        padding: 0.75rem;
    }
    
    .search-box:focus {
        outline: none;
        box-shadow: 0 0 0 0.2rem rgba(255, 107, 53, 0.25);
    }
    
    .filter-select {
        border: 2px solid #ddd;
        border-radius: 8px;
        padding: 0.75rem;
    }
    
    .filter-select:focus {
        border-color: #FF6B35;
        outline: none;
        box-shadow: 0 0 0 0.2rem rgba(255, 107, 53, 0.25);
    }
    
    .tickets-sold {
        background: rgba(255, 107, 53, 0.1);
        color: #FF6B35;
        padding: 0.5rem 1rem;
        border-radius: 20px;
        font-weight: 600;
    }
    
    .no-events {
        text-align: center;
        padding: 3rem;
        color: #666;
    }
    
    .no-events i {
        font-size: 4rem;
        color: #FF6B35;
        margin-bottom: 1rem;
    }
</style>
@endpush

@section('content')
<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h1 class="mb-2">
                <i class="fas fa-calendar-alt me-3"></i>
                Gestion des Événements
            </h1>
            <p class="mb-0 opacity-75">Superviser tous les événements de la plateforme</p>
        </div>
        <div>
            <a href="#" class="btn btn-black btn-lg">
                <i class="fas fa-plus me-2"></i>Nouvel Événement
            </a>
        </div>
    </div>
</div>

<!-- Filtres et recherche -->
<div class="row mb-4">
    <div class="col-md-6">
        <div class="input-group">
            <span class="input-group-text bg-white border-end-0" style="border: 2px solid #FF6B35; border-right: none;">
                <i class="fas fa-search text-muted"></i>
            </span>
            <input type="text" class="form-control search-box border-start-0" 
                   placeholder="Rechercher un événement..." style="border-left: none;">
        </div>
    </div>
    <div class="col-md-3">
        <select class="form-select filter-select">
            <option value="">Tous les statuts</option>
            <option value="published">Publié</option>
            <option value="draft">Brouillon</option>
            <option value="cancelled">Annulé</option>
            <option value="finished">Terminé</option>
        </select>
    </div>
    <div class="col-md-3">
        <select class="form-select filter-select">
            <option value="">Tous les promoteurs</option>
            <option value="promoteur1">Promoteur A</option>
            <option value="promoteur2">Promoteur B</option>
        </select>
    </div>
</div>

<div class="table-container">
    <table class="table table-orange table-hover mb-0">
        <thead>
            <tr>
                <th><i class="fas fa-ticket-alt me-2"></i>Titre</th>
                <th><i class="fas fa-user me-2"></i>Promoteur</th>
                <th><i class="fas fa-calendar me-2"></i>Date</th>
                <th><i class="fas fa-map-marker-alt me-2"></i>Lieu</th>
                <th><i class="fas fa-chart-bar me-2"></i>Billets vendus</th>
                <th><i class="fas fa-info-circle me-2"></i>Statut</th>
                <th><i class="fas fa-cogs me-2"></i>Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($events as $event)
            <tr>
                <td>
                    <a href="#" class="event-title">
                        {{ $event->title }}
                    </a>
                    @if($event->featured)
                        <i class="fas fa-star text-warning ms-2" title="Événement vedette"></i>
                    @endif
                </td>
                <td>
                    <div class="promoter-name">
                        <i class="fas fa-user-circle me-1"></i>
                        {{ $event->promoteur->name ?? 'N/A' }}
                    </div>
                </td>
                <td>
                    @if($event->start_date)
                        <div class="fw-bold">{{ $event->start_date->format('d/m/Y') }}</div>
                        <small class="text-muted">{{ $event->start_date->format('H:i') }}</small>
                    @else
                        <span class="text-muted">Non défini</span>
                    @endif
                </td>
                <td>
                    <i class="fas fa-map-marker-alt text-muted me-2"></i>
                    {{ $event->location ?? 'N/A' }}
                </td>
                <td>
                    <span class="tickets-sold">
                        {{ $event->tickets_sold ?? 0 }} / {{ $event->max_capacity ?? '∞' }}
                    </span>
                </td>
                <td>
                    @switch($event->status ?? 'draft')
                        @case('published')
                            <span class="status-badge status-published">
                                <i class="fas fa-check-circle me-1"></i>Publié
                            </span>
                            @break
                        @case('draft')
                            <span class="status-badge status-draft">
                                <i class="fas fa-edit me-1"></i>Brouillon
                            </span>
                            @break
                        @case('cancelled')
                            <span class="status-badge status-cancelled">
                                <i class="fas fa-times-circle me-1"></i>Annulé
                            </span>
                            @break
                        @default
                            <span class="status-badge status-draft">
                                <i class="fas fa-question-circle me-1"></i>Inconnu
                            </span>
                    @endswitch
                </td>
                <td>
                    <div class="btn-group" role="group">
                        <a href="#" class="btn btn-sm btn-black" title="Voir les détails">
                            <i class="fas fa-eye"></i>
                        </a>
                        <a href="#" class="btn btn-sm btn-outline-dark" title="Modifier">
                            <i class="fas fa-edit"></i>
                        </a>
                        <button class="btn btn-sm btn-outline-danger" title="Supprimer">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="7" class="no-events">
                    <i class="fas fa-calendar-times"></i>
                    <h5>Aucun événement trouvé</h5>
                    <p class="text-muted">Il n'y a pas encore d'événements créés sur la plateforme.</p>
                    <a href="#" class="btn btn-black">
                        <i class="fas fa-plus me-2"></i>Créer le premier événement
                    </a>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

@if(isset($events) && $events->hasPages())
<div class="d-flex justify-content-center mt-4">
    {{ $events->links('pagination::bootstrap-4') }}
</div>
@endif

<!-- Statistiques rapides -->
<div class="row mt-4">
    <div class="col-md-3">
        <div class="card border-0 shadow-sm" style="border-left: 4px solid #28a745 !important;">
            <div class="card-body text-center">
                <i class="fas fa-check-circle fa-2x text-success mb-3"></i>
                <h4 class="text-success">{{ $stats['published'] ?? 0 }}</h4>
                <p class="text-muted mb-0">Événements publiés</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm" style="border-left: 4px solid #ffc107 !important;">
            <div class="card-body text-center">
                <i class="fas fa-edit fa-2x text-warning mb-3"></i>
                <h4 class="text-warning">{{ $stats['draft'] ?? 0 }}</h4>
                <p class="text-muted mb-0">Brouillons</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm" style="border-left: 4px solid #FF6B35 !important;">
            <div class="card-body text-center">
                <i class="fas fa-ticket-alt fa-2x" style="color: #FF6B35;" ></i>
                <h4 style="color: #FF6B35;">{{ $stats['total_tickets'] ?? 0 }}</h4>
                <p class="text-muted mb-0">Billets vendus</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm" style="border-left: 4px solid #dc3545 !important;">
            <div class="card-body text-center">
                <i class="fas fa-times-circle fa-2x text-danger mb-3"></i>
                <h4 class="text-danger">{{ $stats['cancelled'] ?? 0 }}</h4>
                <p class="text-muted mb-0">Annulés</p>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // Recherche en temps réel
    document.querySelector('.search-box').addEventListener('input', function(e) {
        const searchTerm = e.target.value.toLowerCase();
        const rows = document.querySelectorAll('tbody tr');
        
        rows.forEach(row => {
            const title = row.querySelector('.event-title')?.textContent.toLowerCase() || '';
            const promoter = row.querySelector('.promoter-name')?.textContent.toLowerCase() || '';
            
            if (title.includes(searchTerm) || promoter.includes(searchTerm)) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    });
    
    // Confirmation de suppression
    document.querySelectorAll('.btn-outline-danger').forEach(btn => {
        btn.addEventListener('click', function(e) {
            if (!confirm('Êtes-vous sûr de vouloir supprimer cet événement ?')) {
                e.preventDefault();
            }
        });
    });
</script>
@endpush