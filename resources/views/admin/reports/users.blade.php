@extends('layouts.admin')

@section('title', 'Rapport Utilisateurs')

@section('content')
<div class="container-fluid py-4">
    
    <!-- Header -->
    <div class="row align-items-center mb-4">
        <div class="col">
            <h1 class="h3 mb-0">👥 Rapport des Utilisateurs</h1>
            <p class="text-muted mb-0">Analyse détaillée des inscriptions et activité</p>
        </div>
        <div class="col-auto">
            <div class="btn-group" role="group">
                <a href="{{ route('admin.reports.index') }}" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-1"></i> Retour
                </a>
                <a href="{{ route('admin.users.export', ['period' => $period]) }}" class="btn btn-primary">
                    <i class="fas fa-download me-1"></i> Export CSV
                </a>
            </div>
        </div>
    </div>

    <!-- Filtres de période -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label small fw-semibold text-muted">PÉRIODE</label>
                    <select name="period" class="form-select" onchange="this.form.submit()">
                        <option value="today" {{ $period === 'today' ? 'selected' : '' }}>Aujourd'hui</option>
                        <option value="this_week" {{ $period === 'this_week' ? 'selected' : '' }}>Cette semaine</option>
                        <option value="this_month" {{ $period === 'this_month' ? 'selected' : '' }}>Ce mois</option>
                        <option value="this_year" {{ $period === 'this_year' ? 'selected' : '' }}>Cette année</option>
                    </select>
                </div>
            </form>
        </div>
    </div>

    <!-- Statistiques principales -->
    <div class="row g-3 mb-4">
        <div class="col-md-2-4">
            <div class="card border-0 bg-primary bg-opacity-10">
                <div class="card-body text-center">
                    <i class="fas fa-user-plus text-primary fa-2x mb-2"></i>
                    <h4 class="mb-1 text-primary">{{ number_format($stats['new_users']) }}</h4>
                    <small class="text-muted">Nouveaux utilisateurs</small>
                </div>
            </div>
        </div>
        <div class="col-md-2-4">
            <div class="card border-0 bg-success bg-opacity-10">
                <div class="card-body text-center">
                    <i class="fas fa-user-tie text-success fa-2x mb-2"></i>
                    <h4 class="mb-1 text-success">{{ number_format($stats['promoteurs']) }}</h4>
                    <small class="text-muted">Promoteurs</small>
                </div>
            </div>
        </div>
        <div class="col-md-2-4">
            <div class="card border-0 bg-info bg-opacity-10">
                <div class="card-body text-center">
                    <i class="fas fa-user text-info fa-2x mb-2"></i>
                    <h4 class="mb-1 text-info">{{ number_format($stats['acheteurs']) }}</h4>
                    <small class="text-muted">Acheteurs</small>
                </div>
            </div>
        </div>
        <div class="col-md-2-4">
            <div class="card border-0 bg-warning bg-opacity-10">
                <div class="card-body text-center">
                    <i class="fas fa-user-check text-warning fa-2x mb-2"></i>
                    <h4 class="mb-1 text-warning">{{ number_format($stats['verified_users']) }}</h4>
                    <small class="text-muted">Vérifiés</small>
                </div>
            </div>
        </div>
        <div class="col-md-2-4">
            <div class="card border-0 bg-secondary bg-opacity-10">
                <div class="card-body text-center">
                    <i class="fas fa-bolt text-secondary fa-2x mb-2"></i>
                    <h4 class="mb-1 text-secondary">{{ number_format($stats['active_users']) }}</h4>
                    <small class="text-muted">Actifs</small>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <!-- Graphique évolution -->
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-transparent border-0">
                    <h5 class="card-title mb-0">📈 Évolution des Inscriptions</h5>
                </div>
                <div class="card-body">
                    <canvas id="registrationChart" height="100"></canvas>
                </div>
            </div>
        </div>

        <!-- Top utilisateurs actifs -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-transparent border-0">
                    <h5 class="card-title mb-0">🏆 Utilisateurs les Plus Actifs</h5>
                </div>
                <div class="card-body">
                    @forelse($usersData->sortByDesc(function($user) { return $user->orders->count() + $user->events->count(); })->take(5) as $user)
                    <div class="d-flex align-items-center mb-3">
                        <div class="flex-shrink-0">
                            <div class="bg-primary bg-opacity-10 rounded-circle d-flex align-items-center justify-content-center" 
                                 style="width: 40px; height: 40px;">
                                <span class="fw-bold text-primary">{{ substr($user->name, 0, 1) }}</span>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <div class="fw-semibold">{{ $user->name }}</div>
                            <div class="text-muted small">{{ $user->role }}</div>
                        </div>
                        <div class="text-end">
                            <div class="fw-bold">{{ $user->orders->count() + $user->events->count() }}</div>
                            <div class="text-muted small">actions</div>
                        </div>
                    </div>
                    @empty
                    <div class="text-center text-muted py-3">
                        <i class="fas fa-users fa-2x mb-2 opacity-50"></i>
                        <p class="mb-0">Aucun utilisateur trouvé</p>
                    </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Graphique des inscriptions
const ctx = document.getElementById('registrationChart').getContext('2d');
new Chart(ctx, {
    type: 'line',
    data: {
        labels: {!! json_encode($registrationEvolution->pluck('date')) !!},
        datasets: [{
            label: 'Inscriptions',
            data: {!! json_encode($registrationEvolution->pluck('count')) !!},
            borderColor: 'rgb(75, 192, 192)',
            backgroundColor: 'rgba(75, 192, 192, 0.1)',
            tension: 0.4,
            fill: true
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    stepSize: 1
                }
            }
        },
        plugins: {
            legend: {
                display: false
            }
        }
    }
});
</script>
@endpush

@push('styles')
<style>
.col-md-2-4 {
    flex: 0 0 auto;
    width: 20%;
}

@media (max-width: 768px) {
    .col-md-2-4 {
        width: 50%;
        margin-bottom: 1rem;
    }
}
</style>
@endpush