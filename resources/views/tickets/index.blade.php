{{-- resources/views/promoteur/events/tickets/index.blade.php --}}
@extends('layouts.promoteur')

@php
use Illuminate\Support\Facades\Storage;
@endphp

@section('title', 'Gestion des billets - ' . $event->title)

@section('content')
<div class="container-fluid">
    <!-- En-tête -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="{{ route('promoteur.dashboard') }}">Dashboard</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('promoteur.events.index') }}">Événements</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('promoteur.events.show', $event) }}">{{ $event->title }}</a></li>
                            <li class="breadcrumb-item active">Billets</li>
                        </ol>
                    </nav>
                    
                    <h1 class="h2 mb-1" style="color: #FF6B35;">
                        <i class="fas fa-ticket-alt me-2"></i>
                        Gestion des billets
                    </h1>
                    <p class="text-muted mb-0">{{ $event->title }}</p>
                </div>
                
                <div>
                    <a href="{{ route('promoteur.events.tickets.create', $event) }}" class="btn btn-orange">
                        <i class="fas fa-plus me-2"></i>Ajouter un type de billet
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Informations de l'événement -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-2">
                            @if($event->image)
                                <img src="{{ Storage::url( $event->image) }}" 
                                     class="img-fluid rounded" 
                                     alt="{{ $event->title }}"
                                     style="height: 80px; width: 80px; object-fit: cover;">
                            @else
                                <div class="bg-light rounded d-flex align-items-center justify-content-center" 
                                     style="height: 80px; width: 80px;">
                                    <i class="fas fa-calendar-alt text-muted fa-2x"></i>
                                </div>
                            @endif
                        </div>
                        <div class="col-md-6">
                            <h5 class="mb-1">{{ $event->title }}</h5>
                            <div class="text-muted">
                                <i class="fas fa-map-marker-alt me-1"></i>
                                {{ $event->venue }}, {{ $event->address }}
                            </div>
                            <div class="text-muted">
                                <i class="fas fa-calendar me-1"></i>
                                {{ $event->event_date ? $event->event_date->format('d/m/Y') : 'Date non définie' }}
                                @if($event->event_time)
                                    à {{ $event->event_time->format('H:i') }}
                                @endif
                            </div>
                        </div>
                        <div class="col-md-4 text-end">
                            <span class="badge {{ $event->status === 'published' ? 'bg-success' : 'bg-warning' }} fs-6">
                                {{ ucfirst($event->status) }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Liste des types de billets -->
    <div class="row">
        <div class="col-12">
            @if($ticketTypes->count() > 0)
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-bottom-0">
                        <h5 class="mb-0">
                            <i class="fas fa-list me-2"></i>
                            Types de billets ({{ $ticketTypes->count() }})
                        </h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Type de billet</th>
                                        <th>Prix</th>
                                        <th>Disponibilité</th>
                                        <th>Période de vente</th>
                                        <th>Max/commande</th>
                                        <th>Statut</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($ticketTypes as $ticketType)
                                        <tr>
                                            <!-- Type de billet -->
                                            <td>
                                                <div class="fw-semibold">{{ $ticketType->name }}</div>
                                                @if($ticketType->description)
                                                    <small class="text-muted">{{ $ticketType->description }}</small>
                                                @endif
                                            </td>

                                            <!-- Prix -->
                                            <td>
                                                <span class="fw-bold" style="color: #FF6B35;">
                                                    {{ number_format($ticketType->price) }} FCFA
                                                </span>
                                            </td>

                                            <!-- Disponibilité -->
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="me-2">
                                                        <div class="fw-semibold">{{ $ticketType->remainingTickets() }} / {{ $ticketType->quantity_available }}</div>
                                                        <small class="text-muted">{{ $ticketType->quantity_sold }} vendus</small>
                                                    </div>
                                                    <div style="width: 60px;">
                                                        <div class="progress" style="height: 6px;">
                                                            <div class="progress-bar bg-success" 
                                                                 style="width: {{ $ticketType->getProgressPercentage() }}%"></div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>

                                            <!-- Période de vente -->
                                            <td>
                                                <div class="small">
                                                    <div>Du {{ $ticketType->sale_start_date->format('d/m/Y') }}</div>
                                                    <div>Au {{ $ticketType->sale_end_date->format('d/m/Y') }}</div>
                                                    
                                                    @if($ticketType->sale_start_date > now())
                                                        <span class="badge bg-info">Prochainement</span>
                                                    @elseif($ticketType->sale_end_date < now())
                                                        <span class="badge bg-secondary">Terminé</span>
                                                    @else
                                                        <span class="badge bg-success">En cours</span>
                                                    @endif
                                                </div>
                                            </td>

                                            <!-- Max par commande -->
                                            <td>
                                                <span class="badge bg-light text-dark">
                                                    {{ $ticketType->max_per_order }}
                                                </span>
                                            </td>

                                            <!-- Statut -->
                                            <td>
                                                @if($ticketType->is_active)
                                                    <span class="badge bg-success">
                                                        <i class="fas fa-check-circle me-1"></i>Actif
                                                    </span>
                                                @else
                                                    <span class="badge bg-danger">
                                                        <i class="fas fa-times-circle me-1"></i>Inactif
                                                    </span>
                                                @endif
                                            </td>

                                            <!-- Actions -->
                                            <td>
                                                <div class="btn-group btn-group-sm" role="group">
                                                    <!-- Modifier -->
                                                    <a href="{{ route('promoteur.events.tickets.edit', [$event, $ticketType]) }}" 
                                                       class="btn btn-outline-primary" 
                                                       data-bs-toggle="tooltip" 
                                                       title="Modifier">
                                                        <i class="fas fa-edit"></i>
                                                    </a>

                                                    <!-- Activer/Désactiver -->
                                                    <form method="POST" 
                                                          action="{{ route('promoteur.events.tickets.toggle', [$event, $ticketType]) }}" 
                                                          class="d-inline">
                                                        @csrf
                                                        @method('PATCH')
                                                        <button type="submit" 
                                                                class="btn {{ $ticketType->is_active ? 'btn-outline-warning' : 'btn-outline-success' }}"
                                                                data-bs-toggle="tooltip" 
                                                                title="{{ $ticketType->is_active ? 'Désactiver' : 'Activer' }}"
                                                                onclick="return confirm('Êtes-vous sûr de vouloir {{ $ticketType->is_active ? 'désactiver' : 'activer' }} ce type de billet ?')">
                                                            <i class="fas {{ $ticketType->is_active ? 'fa-eye-slash' : 'fa-eye' }}"></i>
                                                        </button>
                                                    </form>

                                                    <!-- Supprimer -->
                                                    @if($ticketType->quantity_sold == 0)
                                                        <form method="POST" 
                                                              action="{{ route('promoteur.events.tickets.destroy', [$event, $ticketType]) }}" 
                                                              class="d-inline">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" 
                                                                    class="btn btn-outline-danger"
                                                                    data-bs-toggle="tooltip" 
                                                                    title="Supprimer"
                                                                    onclick="return confirm('Êtes-vous sûr de vouloir supprimer ce type de billet ?')">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                        </form>
                                                    @else
                                                        <button class="btn btn-outline-secondary" 
                                                                disabled 
                                                                data-bs-toggle="tooltip" 
                                                                title="Impossible de supprimer (billets vendus)">
                                                            <i class="fas fa-lock"></i>
                                                        </button>
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Statistiques résumées -->
                <div class="row mt-4">
                    <div class="col-md-3">
                        <div class="card border-0 bg-primary text-white">
                            <div class="card-body text-center">
                                <h3 class="mb-1">{{ $ticketTypes->count() }}</h3>
                                <p class="mb-0 small">Types de billets</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card border-0 bg-success text-white">
                            <div class="card-body text-center">
                                <h3 class="mb-1">{{ $ticketTypes->sum('quantity_available') }}</h3>
                                <p class="mb-0 small">Billets disponibles</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card border-0 bg-warning text-white">
                            <div class="card-body text-center">
                                <h3 class="mb-1">{{ $ticketTypes->sum('quantity_sold') }}</h3>
                                <p class="mb-0 small">Billets vendus</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card border-0 text-white" style="background-color: #FF6B35;">
                            <div class="card-body text-center">
                                <h3 class="mb-1">{{ number_format($ticketTypes->sum(function($t) { return $t->quantity_sold * $t->price; })) }} F</h3>
                                <p class="mb-0 small">Revenus générés</p>
                            </div>
                        </div>
                    </div>
                </div>

            @else
                <!-- État vide -->
                <div class="card border-0 shadow-sm">
                    <div class="card-body text-center py-5">
                        <div class="mb-4">
                            <i class="fas fa-ticket-alt fa-4x text-muted"></i>
                        </div>
                        <h4>Aucun type de billet configuré</h4>
                        <p class="text-muted mb-4">
                            Vous devez configurer au moins un type de billet pour que les utilisateurs puissent acheter des billets pour votre événement.
                        </p>
                        <a href="{{ route('promoteur.events.tickets.create', $event) }}" class="btn btn-orange btn-lg">
                            <i class="fas fa-plus me-2"></i>
                            Créer votre premier type de billet
                        </a>
                    </div>
                </div>
            @endif
        </div>
    </div>

    <!-- Actions rapides -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card border-0 bg-light">
                <div class="card-body">
                    <h6 class="fw-bold mb-3">
                        <i class="fas fa-bolt me-2"></i>Actions rapides
                    </h6>
                    <div class="d-flex gap-2 flex-wrap">
                        <a href="{{ route('promoteur.events.show', $event) }}" class="btn btn-outline-secondary btn-sm">
                            <i class="fas fa-eye me-1"></i>Voir l'événement
                        </a>
                        <a href="{{ route('promoteur.events.edit', $event) }}" class="btn btn-outline-secondary btn-sm">
                            <i class="fas fa-edit me-1"></i>Modifier l'événement
                        </a>
                        @if($event->status === 'draft' && $ticketTypes->count() > 0)
                            <form method="POST" action="{{ route('promoteur.events.publish', $event) }}" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-success btn-sm">
                                    <i class="fas fa-rocket me-1"></i>Publier l'événement
                                </button>
                            </form>
                        @endif
                        @if($event->status === 'published')
                            <form method="POST" action="{{ route('promoteur.events.unpublish', $event) }}" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-warning btn-sm" 
                                        onclick="return confirm('Êtes-vous sûr de vouloir dépublier cet événement ?')">
                                    <i class="fas fa-eye-slash me-1"></i>Dépublier l'événement
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    // Initialiser les tooltips
    document.addEventListener('DOMContentLoaded', function() {
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    });
</script>
@endpush

@push('styles')
<style>
    .btn-orange {
        background-color: #FF6B35;
        border-color: #FF6B35;
        color: white;
    }
    
    .btn-orange:hover {
        background-color: #E55A2B;
        border-color: #E55A2B;
        color: white;
    }
    
    .progress {
        border-radius: 10px;
    }
    
    .table th {
        font-weight: 600;
        font-size: 0.875rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .card {
        transition: transform 0.2s ease-in-out;
    }
    
    .card:hover {
        transform: translateY(-2px);
    }
</style>
@endpush
@endsection