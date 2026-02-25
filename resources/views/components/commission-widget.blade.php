<div class="commission-widget">
    <div class="card border-success">
        <div class="card-header bg-success text-white">
            <h6 class="mb-0">
                <i class="fas fa-calculator me-2"></i>
                Vos gains sur cette vente
            </h6>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="text-muted small">Prix de vente</label>
                        <div class="h5 mb-0">{{ $summary['price_display'] }}</div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="text-muted small">Vos gains ({{ $summary['promoter_percentage'] }})</label>
                        <div class="h5 mb-0 text-success">{{ $summary['promoter_earnings'] }}</div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="text-muted small">Commission plateforme ({{ $summary['platform_percentage'] }})</label>
                        <div class="text-primary">{{ $summary['platform_commission'] }}</div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="text-muted small">Taux de commission</label>
                        <div class="text-info">{{ $summary['commission_rate'] }}</div>
                    </div>
                </div>
            </div>

            <div class="progress mb-2" style="height: 20px;">
                <div class="progress-bar bg-success" role="progressbar"
                     style="width: {{ $summary['promoter_percentage'] }}%">
                    {{ $summary['promoter_percentage'] }}
                </div>
                <div class="progress-bar bg-primary" role="progressbar"
                     style="width: {{ $summary['platform_percentage'] }}%">
                    {{ $summary['platform_percentage'] }}
                </div>
            </div>
            <div class="d-flex justify-content-between small text-muted">
                <span>Vos gains</span>
                <span>Commission plateforme</span>
            </div>
        </div>
    </div>
</div>

<style>
.commission-widget .progress-bar {
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: bold;
    font-size: 0.8rem;
}
</style>
