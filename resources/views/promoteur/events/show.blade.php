@extends('layouts.promoteur')

@php
use Illuminate\Support\Facades\Storage;
@endphp

@push('styles')
<style>
    .event-header {
        background: linear-gradient(135deg, rgba(0,0,0,0.7), rgba(0,0,0,0.5)), 
                    url('{{ $event->image ? Storage::url($event->image) : "" }}') center/cover;
        color: white;
        padding: 3rem 0;
        border-radius: 15px;
        margin-bottom: 2rem;
        min-height: 300px;
        display: flex;
        align-items: center;
    }
    
    .event-header-default {
        background: linear-gradient(135deg, #FF6B35, #E55A2B);
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
    
    .stat-card {
        background: white;
        border-radius: 15px;
        padding: 1.5rem;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        margin-bottom: 1.5rem;
        border-left: 4px solid #FF6B35;
        text-align: center;
        transition: transform 0.3s ease;
    }
    
    .stat-card:hover {
        transform: translateY(-5px);
    }
    
    .info-card {
        background: white;
        border-radius: 15px;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        border-left: 4px solid #FF6B35;
        padding: 2rem;
        margin-bottom: 2rem;
    }
    
    .orders-table {
        background: white;
        border-radius: 15px;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        overflow: hidden;
    }
    
    .table-header {
        background: linear-gradient(135deg, #FF6B35, #E55A2B);
        color: white;
        padding: 1rem;
    }
    
    .status-badge {
        padding: 0.5rem 1rem;
        border-radius: 20px;
        font-size: 0.875rem;
        font-weight: 600;
        text-transform: uppercase;
    }
    
    .status-published {
        background: linear-gradient(135deg, #28a745, #20c997);
        color: white;
    }
    
    .status-draft {
        background: linear-gradient(135deg, #ffc107, #fd7e14);
        color: white;
    }
    
    .progress-circle {
        width: 100px;
        height: 100px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 1rem;
        font-size: 1.5rem;
        font-weight: bold;
        color: white;
    }
    
    .quick-actions {
        position: sticky;
        top: 2rem;
        z-index: 100;
    }
    
    .ticket-type-card {
        border: 2px solid #e9ecef;
        border-radius: 10px;
        padding: 1rem;
        margin-bottom: 1rem;
        transition: all 0.3s ease;
    }
    
    .ticket-type-card:hover {
        border-color: #FF6B35;
        box-shadow: 0 4px 8px rgba(255, 107, 53, 0.2);
    }
    
    .chart-container {
        position: relative;
        height: 300px;
        margin: 2rem 0;
    }
</style>
@endpush

@section('content')
<!-- Header de l'événement -->
<div class="event-header {{ !$event->image ? 'event-header-default' : '' }}">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-8">
                <!-- Breadcrumb -->
                <nav aria-label="breadcrumb" class="mb-3">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item">
                            <a href="{{ route('promoteur.dashboard') }}" class="text-white-50">Dashboard</a>
                        </li>
                        <li class="breadcrumb-item">
                            <a href="{{ route('promoteur.events.index') }}" class="text-white-50">Événements</a>
                        </li>
                        <li class="breadcrumb-item active text-white">{{ $event->title }}</li>
                    </ol>
                </nav>
                
                <!-- Statut -->
                @switch($event->status)
                    @case('published')
                        <span class="status-badge status-published mb-3 d-inline-block">
                            <i class="fas fa-check-circle me-1"></i>Publié
                        </span>
                        @break
                    @case('draft')
                        <span class="status-badge status-draft mb-3 d-inline-block">
                            <i class="fas fa-edit me-1"></i>Brouillon
                        </span>
                        @break
                @endswitch
                
                <!-- Titre et infos -->
                <h1 class="display-5 fw-bold mb-3">{{ $event->title }}</h1>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-2">
                            <i class="fas fa-calendar-alt me-2"></i>
                            <strong>{{ $event->event_date ? $event->event_date->format('d/m/Y') : 'Date non définie' }}</strong>
                            @if($event->event_time)
                                à {{ $event->event_time->format('H:i') }}
                            @endif
                        </div>
                        <div class="mb-2">
                            <i class="fas fa-map-marker-alt me-2"></i>
                            {{ $event->venue }}
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-2">
                            <i class="fas fa-tag me-2"></i>
                            {{ $event->category->name }}
                        </div>
                        <div class="mb-2">
                            <i class="fas fa-clock me-2"></i>
                            Créé {{ $event->created_at->diffForHumans() }}
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-4 text-lg-end">
                <div class="d-flex flex-column gap-2">
                   @if($event->status === 'draft')
    @if($event->ticketTypes->where('is_active', true)->count() > 0)
        <form action="{{ route('promoteur.events.publish', $event) }}" method="POST" class="d-inline">
            @csrf
            <button type="submit" class="btn btn-success btn-lg" 
                    onclick="return confirm('Êtes-vous sûr de vouloir publier cet événement ? Il sera visible par le public.')">
                <i class="fas fa-paper-plane me-2"></i>Publier l'événement
            </button>
        </form>
    @else
        <a href="{{ route('promoteur.events.tickets.create', $event) }}" class="btn btn-warning btn-lg">
            <i class="fas fa-exclamation-triangle me-2"></i>Configurer les billets d'abord
        </a>
    @endif
@elseif($event->status === 'published')
    <form action="{{ route('promoteur.events.unpublish', $event) }}" method="POST" class="d-inline">
        @csrf
        <button type="submit" class="btn btn-secondary btn-lg" 
                onclick="return confirm('Voulez-vous retirer cet événement de la publication ?')">
            <i class="fas fa-eye-slash me-2"></i>Dépublier
        </button>
    </form>
@endif
                    <a href="{{ route('promoteur.events.edit', $event) }}" class="btn btn-black btn-lg">
                        <i class="fas fa-edit me-2"></i>Modifier
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Contenu principal -->
    <div class="col-lg-8">
        <!-- Statistiques principales -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="progress-circle" style="background: linear-gradient(135deg, #FF6B35, #E55A2B);">
                        {{ number_format(($stats['tickets_sold'] / max($event->totalTicketsAvailable(), 1)) * 100, 0) }}%
                    </div>
                    <h5 style="color: #FF6B35;">{{ $stats['tickets_sold'] }}</h5>
                    <p class="text-muted mb-0">Billets vendus</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="progress-circle" style="background: linear-gradient(135deg, #28a745, #20c997);">
                        <i class="fas fa-euro-sign"></i>
                    </div>
                    <h5 class="text-success">{{ number_format($stats['total_revenue']) }} F</h5>
                    <p class="text-muted mb-0">Revenus</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="progress-circle" style="background: linear-gradient(135deg, #007bff, #0056b3);">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                    <h5 class="text-primary">{{ $stats['orders_count'] }}</h5>
                    <p class="text-muted mb-0">Commandes</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="progress-circle" style="background: linear-gradient(135deg, #6c757d, #495057);">
                        {{ $event->totalTicketsAvailable() - $stats['tickets_sold'] }}
                    </div>
                    <h5 class="text-secondary">Restants</h5>
                    <p class="text-muted mb-0">Billets</p>
                </div>
            </div>
        </div>
        
        <!-- Description -->
        <div class="info-card">
            <h5 class="mb-3">
                <i class="fas fa-info-circle me-2" style="color: #FF6B35;"></i>
                Description
            </h5>
            <p class="mb-0">{{ $event->description }}</p>
            
            @if($event->terms_conditions)
                <hr>
                <h6><i class="fas fa-file-contract me-2"></i>Conditions particulières</h6>
                <p class="small text-muted mb-0">{{ $event->terms_conditions }}</p>
            @endif
        </div>
        
        <!-- Types de billets -->
        <div class="info-card">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h5 class="mb-0">
                    <i class="fas fa-ticket-alt me-2" style="color: #FF6B35;"></i>
                    Types de Billets
                </h5>
                <a href="{{ route('promoteur.events.tickets.create', $event) }}" class="btn btn-black">
                    <i class="fas fa-plus me-2"></i>Ajouter un type
                </a>
            </div>
            
            @forelse($event->ticketTypes as $ticketType)
                <div class="ticket-type-card">
                    <div class="row align-items-center">
                        <div class="col-md-4">
                            <h6 class="mb-1">{{ $ticketType->name }}</h6>
                            <p class="text-muted small mb-0">{{ $ticketType->description }}</p>
                        </div>
                        <div class="col-md-2">
                            <strong style="color: #FF6B35;">{{ number_format($ticketType->price) }} F</strong>
                        </div>
                        <div class="col-md-3">
                            <div class="text-center">
                                <div class="fw-bold">{{ $ticketType->quantity_sold }} / {{ $ticketType->quantity_available }}</div>
                                <div class="progress mt-1" style="height: 6px;">
                                    <div class="progress-bar" style="background: #FF6B35; width: {{ ($ticketType->quantity_sold / max($ticketType->quantity_available, 1)) * 100 }}%"></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 text-end">
                            <a href="{{ route('promoteur.events.tickets.edit', [$event, $ticketType]) }}" class="btn btn-sm btn-outline-dark">
                                <i class="fas fa-edit"></i> Modifier
                            </a>
                        </div>
                    </div>
                </div>
            @empty
                <div class="text-center py-4">
                    <i class="fas fa-ticket-alt fa-3x text-muted mb-3"></i>
                    <h6>Aucun type de billet configuré</h6>
                    <p class="text-muted">Ajoutez des types de billets pour commencer à vendre</p>
                    <a href="{{ route('promoteur.events.tickets.create', $event) }}" class="btn btn-black">
                        <i class="fas fa-plus me-2"></i>Créer le premier type
                    </a>
                </div>
            @endforelse
        </div>
        
        <!-- Graphique des ventes -->
        @if($stats['tickets_sold'] > 0)
        <div class="info-card">
            <h5 class="mb-3">
                <i class="fas fa-chart-line me-2" style="color: #FF6B35;"></i>
                Évolution des Ventes
            </h5>
            <div class="chart-container">
                <canvas id="salesChart"></canvas>
            </div>
        </div>
        @endif
    </div>
    
    <!-- Sidebar -->
    <div class="col-lg-4">
        <!-- Actions rapides -->
        <div class="quick-actions">
            <div class="info-card">
                <h6 class="mb-3">
                    <i class="fas fa-bolt me-2"></i>
                    Actions Rapides
                </h6>
                
                <div class="d-grid gap-2">
                    <a href="{{ route('promoteur.scanner') }}" class="btn btn-black">
                        <i class="fas fa-qrcode me-2"></i>Scanner QR
                    </a>
                    
                    @if($event->status === 'published')
                        <a href="{{ route('events.show', $event) }}" class="btn btn-outline-dark" target="_blank">
                            <i class="fas fa-external-link-alt me-2"></i>Voir sur le site
                        </a>
                    @endif
                    
                    <a href="{{ route('promoteur.sales') }}" class="btn btn-outline-dark">
                        <i class="fas fa-chart-bar me-2"></i>Analyser les ventes
                    </a>
                    
                    <a href="{{ route('promoteur.reports.export') }}?event={{ $event->id }}" class="btn btn-outline-dark">
                        <i class="fas fa-download me-2"></i>Exporter les données
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Informations détaillées -->
        <div class="info-card">
            <h6 class="mb-3">
                <i class="fas fa-info me-2"></i>
                Informations
            </h6>
            
            <div class="row">
                <div class="col-12 mb-3">
                    <strong>Adresse complète :</strong>
                    <div class="text-muted">{{ $event->address }}</div>
                </div>
                
                @if($event->end_time)
                <div class="col-12 mb-3">
                    <strong>Heure de fin :</strong>
                    <div class="text-muted">{{ $event->end_time->format('H:i') }}</div>
                </div>
                @endif
                
                <div class="col-12 mb-3">
                    <strong>Dernière modification :</strong>
                    <div class="text-muted">{{ $event->updated_at->diffForHumans() }}</div>
                </div>
                
                <div class="col-12">
                    <strong>ID de l'événement :</strong>
                    <div class="text-muted">#{{ $event->id }}</div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Commandes récentes -->
@if($recentOrders && $recentOrders->count() > 0)
<div class="orders-table mt-4">
    <div class="table-header">
        <h6 class="mb-0">
            <i class="fas fa-shopping-cart me-2"></i>
            Commandes Récentes
        </h6>
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>Client</th>
                    <th>Email</th>
                    <th>Billets</th>
                    <th>Montant</th>
                    <th>Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($recentOrders as $order)
                <tr>
                    <td>{{ $order->user->name }}</td>
                    <td>{{ $order->billing_email }}</td>
                    <td>{{ $order->orderItems->sum('quantity') }}</td>
                    <td class="fw-bold" style="color: #FF6B35;">{{ number_format($order->total_amount) }} F</td>
                    <td>{{ $order->created_at->format('d/m/Y H:i') }}</td>
                    <td>
                        <a href="{{ route('admin.orders.show', $order) }}" class="btn btn-sm btn-black">
                            <i class="fas fa-eye"></i>
                        </a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    function publishEvent() {
        if (confirm('Êtes-vous sûr de vouloir publier cet événement ? Il sera visible par le public.')) {
            fetch('{{ route("promoteur.events.publish", $event) }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Content-Type': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Erreur lors de la publication : ' + data.message);
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                alert('Une erreur est survenue lors de la publication.');
            });
        }
    }
    
    // Graphique des ventes (si des données existent)
    @if($stats['tickets_sold'] > 0)
    document.addEventListener('DOMContentLoaded', function() {
        const ctx = document.getElementById('salesChart').getContext('2d');
        
        // Données simulées - à remplacer par de vraies données
        const salesData = {
            labels: ['Il y a 7j', 'Il y a 6j', 'Il y a 5j', 'Il y a 4j', 'Il y a 3j', 'Il y a 2j', 'Hier'],
            datasets: [{
                label: 'Billets vendus',
                data: [2, 5, 8, 12, 15, 18, {{ $stats['tickets_sold'] }}],
                borderColor: '#FF6B35',
                backgroundColor: 'rgba(255, 107, 53, 0.1)',
                tension: 0.4,
                fill: true
            }]
        };
        
        new Chart(ctx, {
            type: 'line',
            data: salesData,
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });
    });
    @endif
</script>
@endpush