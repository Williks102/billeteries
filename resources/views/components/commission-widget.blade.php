<!-- resources/views/components/commission-widget.blade.php -->

<div class="commission-widget">
    <div class="card border-success mb-3">
        <div class="card-header text-white" style="background: linear-gradient(135deg, #28a745 0%, #20c997 100%);">
            <div class="d-flex justify-content-between align-items-center">
                <h6 class="mb-0">
                    <i class="fas fa-money-bill-wave me-2"></i>
                    Vos gains sur cette vente
                </h6>
                <small class="badge bg-light text-dark">{{ $summary['commission_rate'] }}</small>
            </div>
        </div>
        <div class="card-body">
            <!-- Résumé principal -->
            <div class="row mb-3">
                <div class="col-6">
                    <div class="text-center">
                        <div class="text-muted small">Prix de vente</div>
                        <div class="h5 mb-0 text-primary">{{ $summary['price_display'] }}</div>
                    </div>
                </div>
                <div class="col-6">
                    <div class="text-center">
                        <div class="text-muted small">Vos gains</div>
                        <div class="h5 mb-0 text-success">{{ $summary['promoter_earnings'] }}</div>
                    </div>
                </div>
            </div>
            
            <!-- Barre de progression visuelle -->
            <div class="progress mb-3" style="height: 25px; border-radius: 15px;">
                <div class="progress-bar bg-success" role="progressbar" 
                     style="width: {{ $summary['promoter_percentage'] }}%"
                     title="Vos gains : {{ $summary['promoter_percentage'] }}">
                    <span class="fw-bold">{{ $summary['promoter_percentage'] }}</span>
                </div>
                <div class="progress-bar bg-warning" role="progressbar" 
                     style="width: {{ $summary['platform_percentage'] }}%"
                     title="Commission plateforme : {{ $summary['platform_percentage'] }}">
                    <span class="fw-bold text-white">{{ $summary['platform_percentage'] }}</span>
                </div>
            </div>
            
            <!-- Détails de la répartition -->
            <div class="breakdown-details">
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">💰 Prix de vente :</span>
                    <strong>{{ $summary['price_display'] }}</strong>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">📝 Commission plateforme :</span>
                    <span class="text-warning">- {{ $summary['platform_commission'] }}</span>
                </div>
                <hr class="my-2">
                <div class="d-flex justify-content-between">
                    <span class="fw-bold text-success">💵 Vos gains nets :</span>
                    <strong class="text-success">{{ $summary['promoter_earnings'] }}</strong>
                </div>
            </div>
            
            @if($quantity > 1)
                <div class="mt-3 p-2 bg-light rounded">
                    <small class="text-muted">
                        <i class="fas fa-info-circle me-1"></i>
                        Pour {{ $quantity }} billet(s) × {{ \App\Helpers\CurrencyHelper::formatFCFA($price) }}
                    </small>
                </div>
            @endif
        </div>
    </div>
</div>

<style>
.commission-widget .progress-bar {
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.8rem;
    font-weight: 600;
    min-width: 30px;
}

.commission-widget .breakdown-details {
    font-size: 0.9rem;
}

.commission-widget .card {
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.commission-widget .card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(40, 167, 69, 0.15);
}
</style>

<!-- Version simplifiée pour checkout -->
@if(isset($simple) && $simple)
<div class="commission-summary-simple">
    <div class="alert alert-success d-flex justify-content-between align-items-center">
        <div>
            <strong>💰 Gain promoteur :</strong> {{ $summary['promoter_earnings'] }}
        </div>
        <div>
            <small class="text-muted">Commission {{ $summary['commission_rate'] }}</small>
        </div>
    </div>
</div>
@endif