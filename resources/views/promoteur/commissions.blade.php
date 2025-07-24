{{-- resources/views/promoteur/commissions.blade.php - VERSION CORRIGÉE --}}
@extends('layouts.promoteur')

@push('styles')
<style>
    .commission-card {
        background: white;
        border-radius: 15px;
        padding: 2rem;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        border-left: 4px solid #FF6B35;
        margin-bottom: 1.5rem;
        transition: transform 0.3s ease;
    }
    
    .commission-card:hover {
        transform: translateY(-5px);
    }
    
    .summary-card {
        background: white;
        border-radius: 15px;
        padding: 1.5rem;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        margin-bottom: 1.5rem;
        text-align: center;
    }
    
    .status-badge {
        padding: 0.5rem 1rem;
        border-radius: 20px;
        font-size: 0.875rem;
        font-weight: 600;
        text-transform: uppercase;
    }
    
    .status-paid {
        background: linear-gradient(135deg, #28a745, #20c997);
        color: white;
    }
    
    .status-pending {
        background: linear-gradient(135deg, #ffc107, #fd7e14);
        color: white;
    }
    
    .status-held {
        background: linear-gradient(135deg, #dc3545, #c82333);
        color: white;
    }
    
    .filter-card {
        background: white;
        border-radius: 15px;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        border-left: 4px solid #FF6B35;
        padding: 1.5rem;
        margin-bottom: 2rem;
    }
    
    .commission-item {
        border: 2px solid #e9ecef;
        border-radius: 10px;
        padding: 1.5rem;
        margin-bottom: 1rem;
        transition: all 0.3s ease;
        background: white;
    }
    
    .commission-item:hover {
        border-color: #FF6B35;
        box-shadow: 0 4px 8px rgba(255, 107, 53, 0.2);
    }
    
    .btn-orange {
        background: linear-gradient(135deg, #FF6B35, #E55A2B);
        border: none;
        color: white;
    }
    
    .btn-orange:hover {
        background: linear-gradient(135deg, #E55A2B, #D4491F);
        color: white;
    }
</style>
@endpush

@section('content')
<div class="container-fluid">
    <!-- En-tête -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h2 mb-1" style="color: #FF6B35;">
                        <i class="fas fa-coins me-2"></i>
                        Mes Commissions
                    </h1>
                    <p class="text-muted">Suivez vos revenus et paiements</p>
                </div>
                <div>
                    <a href="{{ route('promoteur.reports.export', ['type' => 'commissions']) }}" class="btn btn-orange">
                        <i class="fas fa-download me-2"></i>Exporter
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Résumé des commissions -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="summary-card" style="border-left: 4px solid #28a745;">
                <div class="text-success mb-2">
                    <i class="fas fa-check-circle fa-2x"></i>
                </div>
                <h3 class="fw-bold text-success">{{ isset($totals['paid']) ? number_format($totals['paid']) : 0 }} F</h3>
                <p class="text-muted mb-0">Commissions Payées</p>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="summary-card" style="border-left: 4px solid #ffc107;">
                <div class="text-warning mb-2">
                    <i class="fas fa-clock fa-2x"></i>
                </div>
                <h3 class="fw-bold text-warning">{{ isset($totals['pending']) ? number_format($totals['pending']) : 0 }} F</h3>
                <p class="text-muted mb-0">En Attente</p>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="summary-card" style="border-left: 4px solid #dc3545;">
                <div class="text-danger mb-2">
                    <i class="fas fa-pause-circle fa-2x"></i>
                </div>
                <h3 class="fw-bold text-danger">{{ isset($totals['held']) ? number_format($totals['held']) : 0 }} F</h3>
                <p class="text-muted mb-0">Suspendues</p>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="summary-card" style="border-left: 4px solid #FF6B35;">
                <div class="mb-2" style="color: #FF6B35;">
                    <i class="fas fa-chart-line fa-2x"></i>
                </div>
                <h3 class="fw-bold" style="color: #FF6B35;">{{ isset($totals['total']) ? number_format($totals['total']) : 0 }} F</h3>
                <p class="text-muted mb-0">Total Général</p>
            </div>
        </div>
    </div>

    @if(isset($readyForPayout) && $readyForPayout > 0)
    <!-- Alerte paiement -->
    <div class="alert alert-info border-0 rounded-3 shadow-sm mb-4">
        <div class="d-flex align-items-center">
            <i class="fas fa-info-circle fa-2x me-3"></i>
            <div>
                <h5 class="alert-heading mb-1">Paiement Disponible</h5>
                <p class="mb-0">
                    Vous avez <strong>{{ number_format($readyForPayout) }} FCFA</strong> prêt à être versé 
                    (commissions de plus de 7 jours).
                </p>
            </div>
        </div>
    </div>
    @endif

    <!-- Filtres -->
    <div class="filter-card">
        <form method="GET" action="{{ route('promoteur.commissions') }}" class="row g-3">
            <div class="col-md-3">
                <label class="form-label">Statut</label>
                <select name="status" class="form-select">
                    <option value="">Tous les statuts</option>
                    <option value="paid" {{ request('status') === 'paid' ? 'selected' : '' }}>Payées</option>
                    <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>En attente</option>
                    <option value="held" {{ request('status') === 'held' ? 'selected' : '' }}>Suspendues</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Mois</label>
                <select name="month" class="form-select">
                    <option value="">Tous les mois</option>
                    @for($i = 1; $i <= 12; $i++)
                        <option value="{{ $i }}" {{ request('month') == $i ? 'selected' : '' }}>
                            {{ \Carbon\Carbon::createFromDate(null, $i, 1)->format('F') }}
                        </option>
                    @endfor
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Année</label>
                <select name="year" class="form-select">
                    <option value="">Toutes les années</option>
                    @for($year = now()->year; $year >= 2020; $year--)
                        <option value="{{ $year }}" {{ request('year') == $year ? 'selected' : '' }}>
                            {{ $year }}
                        </option>
                    @endfor
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">&nbsp;</label>
                <button type="submit" class="btn btn-orange d-block w-100">
                    <i class="fas fa-filter me-2"></i>Filtrer
                </button>
            </div>
        </form>
    </div>

    <!-- Liste des commissions -->
    <div class="commission-card">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h5 class="fw-bold mb-0">
                <i class="fas fa-list me-2" style="color: #FF6B35;"></i>
                Historique des Commissions
            </h5>
        </div>
        
        @if(isset($commissions) && $commissions->count() > 0)
            @foreach($commissions as $commission)
                <div class="commission-item">
                    <div class="row align-items-center">
                        <div class="col-md-3">
                            <h6 class="fw-bold mb-1">{{ $commission->order->event->title ?? 'Événement supprimé' }}</h6>
                            <small class="text-muted">
                                <i class="fas fa-calendar me-1"></i>
                                {{ $commission->created_at->format('d/m/Y H:i') }}
                            </small>
                        </div>
                        
                        <div class="col-md-2">
                            <div class="text-muted small">Client</div>
                            <div class="fw-medium">{{ $commission->order->user->name ?? 'Client supprimé' }}</div>
                        </div>
                        
                        <div class="col-md-2">
                            <div class="text-muted small">Revenus Bruts</div>
                            <div class="fw-bold">{{ number_format($commission->gross_amount) }} F</div>
                        </div>
                        
                        <div class="col-md-2">
                            <div class="text-muted small">Commission ({{ $commission->commission_rate }}%)</div>
                            <div class="fw-bold text-primary">{{ number_format($commission->commission_amount) }} F</div>
                        </div>
                        
                        <div class="col-md-2">
                            <div class="text-muted small">Net à recevoir</div>
                            <div class="fw-bold" style="color: #FF6B35;">{{ number_format($commission->net_amount) }} F</div>
                        </div>
                        
                        <div class="col-md-1">
                            <span class="status-badge status-{{ $commission->status }}">
                                @switch($commission->status)
                                    @case('paid')
                                        <i class="fas fa-check me-1"></i>Payé
                                        @break
                                    @case('pending')
                                        <i class="fas fa-clock me-1"></i>Attente
                                        @break
                                    @case('held')
                                        <i class="fas fa-pause me-1"></i>Suspendu
                                        @break
                                    @default
                                        <i class="fas fa-question me-1"></i>{{ ucfirst($commission->status) }}
                                @endswitch
                            </span>
                            
                            @if($commission->paid_at)
                                <div class="text-muted small mt-1">
                                    Payé le {{ $commission->paid_at->format('d/m/Y') }}
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
            
            <!-- Pagination -->
            @if(method_exists($commissions, 'links'))
                <div class="d-flex justify-content-center mt-4">
                    {{ $commissions->appends(request()->query())->links() }}
                </div>
            @endif
        @else
            <div class="text-center py-5">
                <i class="fas fa-coins fa-4x text-muted mb-3"></i>
                <h5 class="text-muted">Aucune commission trouvée</h5>
                <p class="text-muted">
                    @if(request()->hasAny(['status', 'month', 'year']))
                        Aucune commission ne correspond aux filtres sélectionnés.
                        <br>
                        <a href="{{ route('promoteur.commissions') }}" class="btn btn-outline-secondary mt-2">
                            <i class="fas fa-times me-2"></i>Effacer les filtres
                        </a>
                    @else
                        Les commissions apparaîtront ici dès que vos événements commenceront à générer des ventes.
                        <br>
                        <a href="{{ route('promoteur.events.create') }}" class="btn btn-orange mt-2">
                            <i class="fas fa-plus me-2"></i>Créer un événement
                        </a>
                    @endif
                </p>
            </div>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Animation des cartes
    const cards = document.querySelectorAll('.commission-item');
    cards.forEach((card, index) => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(20px)';
        
        setTimeout(() => {
            card.style.transition = 'all 0.5s ease';
            card.style.opacity = '1';
            card.style.transform = 'translateY(0)';
        }, index * 100);
    });
    
    // Auto-submit du formulaire de filtre
    const filterForm = document.querySelector('form[action="{{ route("promoteur.commissions") }}"]');
    if (filterForm) {
        const selects = filterForm.querySelectorAll('select');
        selects.forEach(select => {
            select.addEventListener('change', function() {
                // Optionnel : auto-submit quand on change un filtre
                // filterForm.submit();
            });
        });
    }
});
</script>
@endpush