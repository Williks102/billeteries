@extends('layouts.promoteur')
@section('title', 'Scanner de Billets - ClicBillet CI')

@push('styles')
<style>
    .stat-card {
        background: white;
        border-radius: 15px;
        padding: 1.5rem;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        margin-bottom: 1.5rem;
        border-left: 1px solid #f1eeed81;
        transition: transform 0.3s ease;
    }
    
    .stat-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 15px rgba(0,0,0,0.15);
    }
    
    .stat-card h4 {
        color: #FF6B35;
        font-weight: bold;
        margin-bottom: 0;
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
    }
    
    .card-orange {
        border-left: 4px solid #FF6B35;
        border-radius: 15px;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    }
    
    .event-card {
        transition: transform 0.3s ease;
        border-radius: 15px;
        overflow: hidden;
    }
    
    .event-card:hover {
        transform: translateY(-5px);
    }
    
    .status-active {
        background: linear-gradient(135deg, #28a745, #20c997);
        color: white;
        padding: 0.25rem 0.75rem;
        border-radius: 15px;
        font-size: 0.75rem;
    }
    
    .status-draft {
        background: linear-gradient(135deg, #ffc107, #fd7e14);
        color: white;
        padding: 0.25rem 0.75rem;
        border-radius: 15px;
        font-size: 0.75rem;
    }
    
    .status-finished {
        background: linear-gradient(135deg, #6c757d, #495057);
        color: white;
        padding: 0.25rem 0.75rem;
        border-radius: 15px;
        font-size: 0.75rem;
    }
    
    .quick-action {
        background: white;
        border-radius: 15px;
        padding: 1.5rem;
        text-align: center;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        transition: all 0.3s ease;
        text-decoration: none;
        color: inherit;
    }
    
    .quick-action:hover {
        transform: translateY(-5px);
        color: inherit;
        box-shadow: 0 8px 15px rgba(0,0,0,0.15);
    }
    
    .quick-action-icon {
        width: 60px;
        height: 60px;
        background: linear-gradient(135deg, #FF6B35, #E55A2B);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 1.5rem;
        margin: 0 auto 1rem;
    }
</style>
@endpush

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="fw-bold" style="color: #FF6B35;">Tableau de Bord Promoteur</h2>
        <p class="text-muted">Bonjour {{ auth()->user()->name }}, voici un aperçu de vos activités</p>
    </div>
    <div>
        <a href="{{ route('promoteur.events.create') }}" class="btn btn-black btn-lg">
            <i class="fas fa-plus me-2"></i>Créer un Événement
        </a>
    </div>
</div>

<!-- Statistiques principales -->
<div class="row">
    <div class="col-md-3">
        <div class="stat-card text-center">
            <div class="mb-2">
                <i class="fas fa-calendar-alt fa-2x" style="color: #FF6B35;"></i>
            </div>
            <h4>{{ $stats['total_events'] ?? 0 }}</h4>
            <p class="text-muted mb-0">Événements créés</p>
            <small class="text-success">
                <i class="fas fa-arrow-up"></i> {{ $stats['events_this_month'] ?? 0 }} ce mois
            </small>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="stat-card text-center">
            <div class="mb-2">
                <i class="fas fa-ticket-alt fa-2x" style="color: #FF6B35;"></i>
            </div>
            <h4>{{ $stats['total_tickets_sold'] ?? 0 }}</h4>
            <p class="text-muted mb-0">Billets vendus</p>
            <small class="text-info">
                {{ $stats['tickets_this_week'] ?? 0 }} cette semaine
            </small>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="stat-card text-center">
            <div class="mb-2">
                <i class="fas fa-euro-sign fa-2x" style="color: #FF6B35;"></i>
            </div>
            <h4>{{ number_format($stats['total_revenue'] ?? 0) }} F</h4>
            <p class="text-muted mb-0">Revenus générés</p>
            <small class="text-success">
                <i class="fas fa-arrow-up"></i> +{{ $stats['revenue_growth'] ?? 0 }}%
            </small>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="stat-card text-center">
            <div class="mb-2">
                <i class="fas fa-chart-line fa-2x" style="color: #FF6B35;"></i>
            </div>
            <h4>{{ number_format($stats['monthly_revenue'] ?? 0) }} F</h4>
            <p class="text-muted mb-0">Revenus ce mois</p>
            <small class="text-info">
                <i class="fas fa-calendar me-1"></i>{{ date('F Y') }}
            </small>
        </div>
    </div>
</div>

<!-- Actions rapides -->
<div class="row mb-4">
    <div class="col-12">
        <h5 class="mb-3"><i class="fas fa-bolt me-2" style="color: #FF6B35;"></i>Actions Rapides</h5>
    </div>
    <div class="col-md-3">
        <a href="{{ route('promoteur.events.create') }}" class="quick-action">
            <div class="quick-action-icon">
                <i class="fas fa-plus"></i>
            </div>
            <h6>Nouvel Événement</h6>
            <p class="small text-muted mb-0">Créer un nouvel événement</p>
        </a>
    </div>
    <div class="col-md-3">
        <a href="{{ route('promoteur.scanner.index') }}" class="quick-action">
            <div class="quick-action-icon">
                <i class="fas fa-qrcode"></i>
            </div>
            <h6>Scanner QR</h6>
            <p class="small text-muted mb-0">Valider les billets</p>
        </a>
    </div>
    <div class="col-md-3">
        <a href="{{ route('promoteur.sales') }}" class="quick-action">
            <div class="quick-action-icon">
                <i class="fas fa-chart-line"></i>
            </div>
            <h6>Voir les Ventes</h6>
            <p class="small text-muted mb-0">Analyses détaillées</p>
        </a>
    </div>
    <div class="col-md-3">
        <a href="{{ route('promoteur.reports') }}" class="quick-action">
            <div class="quick-action-icon">
                <i class="fas fa-file-alt"></i>
            </div>
            <h6>Rapports</h6>
            <p class="small text-muted mb-0">Exporter les données</p>
        </a>
    </div>
</div>

<div class="row">
    <!-- Mes événements récents -->
    <div class="col-md-8">
        <div class="card card-orange">
            <div class="card-header" style="background: linear-gradient(135deg, #FF6B35, #E55A2B); color: white;">
                <h6 class="mb-0">
                    <i class="fas fa-calendar-alt me-2"></i>
                    Mes Événements Récents
                </h6>
            </div>
            <div class="card-body">
                @forelse($recentEvents ?? [] as $event)
                <div class="d-flex justify-content-between align-items-center border-bottom py-3">
                    <div class="flex-grow-1">
                        <h6 class="mb-1">{{ $event->title }}</h6>
                        <div class="small text-muted">
                            <i class="fas fa-calendar me-1"></i>{{ $event->event_date->format('d/m/Y') }}
                            <i class="fas fa-map-marker-alt ms-3 me-1"></i>{{ $event->venue }}
                        </div>
                        <div class="small">
                            <i class="fas fa-ticket-alt me-1"></i>
                            {{ $event->tickets_sold ?? 0 }} / {{ $event->max_capacity }} billets
                        </div>
                    </div>
                    <div class="text-end">
                        @switch($event->status)
                            @case('published')
                                <span class="status-active">Actif</span>
                                @break
                            @case('draft')
                                <span class="status-draft">Brouillon</span>
                                @break
                            @case('finished')
                                <span class="status-finished">Terminé</span>
                                @break
                        @endswitch
                        <div class="mt-2">
                            <a href="{{ route('promoteur.events.show', $event) }}" class="btn btn-sm btn-black me-1">
                                <i class="fas fa-eye"></i>
                            </a>
                            <a href="{{ route('promoteur.events.edit', $event) }}" class="btn btn-sm btn-outline-dark">
                                <i class="fas fa-edit"></i>
                            </a>
                        </div>
                    </div>
                </div>
                @empty
                <div class="text-center py-4">
                    <i class="fas fa-calendar-plus fa-3x text-muted mb-3"></i>
                    <h6>Aucun événement créé</h6>
                    <p class="text-muted">Commencez par créer votre premier événement</p>
                    <a href="{{ route('promoteur.events.create') }}" class="btn btn-black">
                        <i class="fas fa-plus me-2"></i>Créer un événement
                    </a>
                </div>
                @endforelse
            </div>
            @if(count($recentEvents ?? []) > 0)
            <div class="card-footer bg-light">
                <a href="{{ route('promoteur.events.index') }}" class="btn btn-outline-dark">
                    <i class="fas fa-list me-2"></i>Voir tous mes événements
                </a>
            </div>
            @endif
        </div>
    </div>
    
    <!-- Statistiques latérales -->
    <div class="col-md-4">
        <!-- Ventes du mois -->
        <div class="card card-orange mb-3">
            <div class="card-header" style="background: linear-gradient(135deg, #28a745, #20c997); color: white;">
                <h6 class="mb-0">
                    <i class="fas fa-chart-line me-2"></i>
                    Ventes du Mois
                </h6>
            </div>
            <div class="card-body">
                <div class="text-center">
                    <h4 style="color: #28a745;">{{ number_format($stats['monthly_revenue'] ?? 0) }} FCFA</h4>
                    <p class="text-muted mb-2">Revenus de {{ date('F Y') }}</p>
                    <small class="text-success">
                        <i class="fas fa-arrow-up"></i> +{{ $stats['monthly_growth'] ?? 0 }}% vs mois dernier
                    </small>
                </div>
                <hr>
                <div class="row text-center">
                    <div class="col-6">
                        <strong>{{ $stats['monthly_tickets'] ?? 0 }}</strong>
                        <div class="small text-muted">Billets</div>
                    </div>
                    <div class="col-6">
                        <strong>{{ $stats['monthly_orders'] ?? 0 }}</strong>
                        <div class="small text-muted">Commandes</div>
                    </div>
                </div>
            </div>
        </div>
    
    </div>
</div>
@endsection

@push('scripts')
<script>
    // Graphiques simples ou animations peuvent être ajoutés ici
    document.addEventListener('DOMContentLoaded', function() {
        // Animation des chiffres
        const statCards = document.querySelectorAll('.stat-card h4');
        statCards.forEach(card => {
            const finalValue = parseInt(card.textContent.replace(/\D/g, ''));
            if (finalValue > 0) {
                animateValue(card, 0, finalValue, 1000);
            }
        });
    });
    
    function animateValue(element, start, end, duration) {
        let startTimestamp = null;
        const step = (timestamp) => {
            if (!startTimestamp) startTimestamp = timestamp;
            const progress = Math.min((timestamp - startTimestamp) / duration, 1);
            const current = Math.floor(progress * (end - start) + start);
            
            if (element.textContent.includes('F')) {
                element.textContent = current.toLocaleString() + ' F';
            } else {
                element.textContent = current;
            }
            
            if (progress < 1) {
                window.requestAnimationFrame(step);
            }
        };
        window.requestAnimationFrame(step);
    }
</script>
@endpush