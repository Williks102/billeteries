{{-- resources/views/acheteur/dashboard.blade.php --}}
{{-- VERSION CORRIGÉE : Votre dashboard existant avec le nouveau layout --}}
@extends('layouts.acheteur')

@php
use Illuminate\Support\Facades\Storage;
@endphp

@section('title', 'Mon tableau de bord - ClicBillet CI')

@section('breadcrumb')
    <li class="breadcrumb-item active" aria-current="page">Tableau de bord</li>
@endsection

@section('content')
    <!-- En-tête de la page -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-1">Bonjour, {{ Auth::user()->name }} 👋</h2>
            <p class="text-muted mb-0">Voici un aperçu de votre activité</p>
        </div>
        <a href="{{ route('home') }}" class="btn btn-acheteur">
            <i class="fas fa-plus me-2"></i>Découvrir des événements
        </a>
    </div>
    
    <!-- Statistiques (cumulées, toutes dates) -->
    @include('acheteur._stats', ['stats' => $stats])

    <!-- Événements à venir -->
    @if($upcomingEvents && $upcomingEvents->isNotEmpty())
    <div class="row mb-4">
        <div class="col-12">
            <div class="content-card">
                <div class="card-header bg-white border-bottom">
                    <h5 class="mb-0">
                        <i class="fas fa-calendar-plus text-primary me-2"></i>
                        Mes prochains événements
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        @foreach($upcomingEvents->take(3) as $order)
                        <div class="col-lg-4 mb-3">
                            <div class="event-card border rounded p-3">
                                <div class="d-flex">
                                    @if($order->event && $order->event->image)
                                        <img src="{{ \App\Support\Media::url($order->event->image) }}" 
                                             class="event-image me-3" 
                                             style="width: 60px; height: 60px; object-fit: cover; border-radius: 8px;">
                                    @else
                                        <div class="event-placeholder me-3 d-flex align-items-center justify-content-center bg-light rounded"
                                             style="width: 60px; height: 60px;">
                                            <i class="fas fa-calendar text-muted"></i>
                                        </div>
                                    @endif
                                    
                                    <div class="flex-grow-1">
                                        <h6 class="mb-1 text-truncate">{{ $order->event->title ?? 'Événement' }}</h6>
                                        <p class="text-muted small mb-1">
                                            <i class="fas fa-calendar me-1"></i>
                                            @if($order->event && $order->event->event_date)
                                                {{ \Carbon\Carbon::parse($order->event->event_date)->format('d/m/Y') }}
                                            @else
                                                Date à confirmer
                                            @endif
                                        </p>
                                        <p class="text-muted small mb-0">
                                            <i class="fas fa-map-marker-alt me-1"></i>
                                            {{ $order->event->venue ?? 'Lieu à confirmer' }}
                                        </p>
                                        <div class="mt-2">
                                            <a href="{{ route('acheteur.orders.show', $order) }}" 
                                               class="btn btn-sm btn-outline-acheteur">
                                                Voir mes billets
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                    
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Historique récent -->
    @if($recentOrders && $recentOrders->isNotEmpty())
    <div class="row mb-4">
        <div class="col-12">
            <div class="content-card">
                <div class="card-header bg-white border-bottom">
                    <h5 class="mb-0">
                        <i class="fas fa-history text-info me-2"></i>
                        Activité récente
                    </h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Commande</th>
                                    <th>Événement</th>
                                    <th>Date d'achat</th>
                                    <th>Montant</th>
                                    <th>Statut</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($recentOrders as $order)
                                <tr>
                                    <td>
                                        <span class="fw-semibold">#{{ $order->order_number ?? $order->id }}</span>
                                    </td>
                                    <td>
                                        <div class="fw-semibold">{{ $order->event->title ?? 'Événement supprimé' }}</div>
                                        <small class="text-muted">{{ $order->event->venue ?? '' }}</small>
                                    </td>
                                    <td>
                                        <div>{{ $order->created_at->format('d/m/Y') }}</div>
                                        <small class="text-muted">{{ $order->created_at->format('H:i') }}</small>
                                    </td>
                                    <td>
                                        <span class="fw-semibold">{{ \App\Helpers\CurrencyHelper::formatFCFA($order->total_amount) }}</span>
                                    </td>
                                    <td>
                                        @if($order->payment_status === 'paid')
                                            <span class="badge bg-success">
                                                <i class="fas fa-check me-1"></i>Payé
                                            </span>
                                        @elseif($order->payment_status === 'pending')
                                            <span class="badge bg-warning">
                                                <i class="fas fa-clock me-1"></i>En attente
                                            </span>
                                        @else
                                            <span class="badge bg-danger">
                                                <i class="fas fa-times me-1"></i>{{ ucfirst($order->payment_status) }}
                                            </span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="{{ route('acheteur.orders.show', $order) }}" 
                                               class="btn btn-outline-primary" title="Voir les détails">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            @if($order->payment_status === 'paid')
                                            <a href="{{ route('acheteur.orders.download', $order) }}" 
                                               class="btn btn-outline-success" title="Télécharger les billets">
                                                <i class="fas fa-download"></i>
                                            </a>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="text-center mt-3">
                        <a href="{{ route('acheteur.orders.index') }}" class="btn btn-outline-acheteur">
                            Voir toutes mes commandes
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Message si aucune activité -->
    @if((!$upcomingEvents || $upcomingEvents->isEmpty()) && (!$recentOrders || $recentOrders->isEmpty()))
    <div class="row">
        <div class="col-12">
            <div class="content-card text-center py-5">
                <div class="mb-4">
                    <i class="fas fa-ticket-alt fa-4x text-muted mb-3"></i>
                    <h4 class="text-muted">Aucune activité pour le moment</h4>
                    <p class="text-muted">Découvrez nos événements et achetez vos premiers billets !</p>
                </div>
                
                <div class="d-flex justify-content-center gap-3">
                    <a href="{{ route('home') }}" class="btn btn-acheteur">
                        <i class="fas fa-search me-2"></i>Découvrir des événements
                    </a>
                    <a href="{{ route('events.all') }}" class="btn btn-outline-acheteur">
                        <i class="fas fa-calendar-alt me-2"></i>Tous les événements
                    </a>
                </div>
            </div>
        </div>
    </div>
    @endif
@endsection

@push('styles')
<style>
    .event-card {
        transition: all 0.3s ease;
        border: 1px solid #e9ecef !important;
    }
    
    .event-card:hover {
        border-color: var(--primary-orange) !important;
        box-shadow: 0 4px 12px rgba(var(--brand-rgb), 0.15);
        transform: translateY(-2px);
    }
    
    .stat-card {
        background: white;
        border-radius: 12px;
        padding: 20px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        border-left: 4px solid var(--primary-orange);
        transition: all 0.3s ease;
        border: 1px solid #e9ecef;
    }
    
    .stat-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 6px 20px rgba(0,0,0,0.1);
    }
    
    .stat-icon {
        width: 50px;
        height: 50px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.2rem;
        color: white;
    }
    
    .stat-icon.primary {
        background: linear-gradient(135deg, var(--primary-orange), var(--brand-decor));
    }
    
    .stat-icon.success {
        background: linear-gradient(135deg, #28a745, #34d058);
    }
    
    .stat-icon.info {
        background: linear-gradient(135deg, var(--primary-blue), #0066cc);
    }
    
    .content-card {
        background: white;
        border-radius: 12px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        border: 1px solid #e9ecef;
        overflow: hidden;
    }
    
    .table tr:hover {
        background-color: rgba(var(--brand-rgb), 0.05);
    }
    
    .btn-group-sm .btn {
        padding: 0.25rem 0.5rem;
        font-size: 0.75rem;
    }

    .card-header {
        background: white !important;
        border-bottom: 1px solid #e9ecef !important;
        padding: 1rem 1.25rem;
    }
    
    .card-body {
        padding: 1.25rem;
    }
</style>
@endpush