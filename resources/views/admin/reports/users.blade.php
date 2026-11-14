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
        <div class="col-12 col-md-auto mt-2 mt-md-0">
            <div class="btn-group" role="group">
                <a href="{{ route('admin.reports.index') }}" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-1"></i> Retour
                </a>
                <a href="{{ route('admin.users.export', request()->only(['period', 'start_date', 'end_date'])) }}" class="btn btn-primary">
                    <i class="fas fa-download me-1"></i> Export CSV
                </a>
            </div>
        </div>
    </div>

    <x-period-filter :action="route('admin.reports.users')" :period="$period" />

    <x-stat-grid label="Utilisateurs inscrits sur la période">
        <x-stat-card label="Nouveaux utilisateurs" :value="$stats['new']" :hint="$period->label" />
        <x-stat-card label="Promoteurs" :value="$stats['new_promoteurs']" hint="Inscrits sur la période" />
        <x-stat-card label="Acheteurs" :value="$stats['new_acheteurs']" hint="Inscrits sur la période" />
        <x-stat-card label="Vérifiés" :value="$stats['new_verified']" hint="Email vérifié" />
        <x-stat-card label="Actifs" :value="$stats['new_active']" hint="Au moins une commande ou un événement" />
    </x-stat-grid>

    <div class="row g-4">
        <!-- Graphique évolution -->
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-transparent border-0">
                    <h2 class="h5 card-title mb-0">Évolution des inscriptions ({{ $registrations['unit'] === 'month' ? 'par mois' : 'par jour' }})</h2>
                </div>
                <div class="card-body">
                    <canvas id="registrationChart" height="100" role="img" aria-label="Inscriptions sur la période"></canvas>
                </div>
            </div>
        </div>

        <!-- Top utilisateurs actifs -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-transparent border-0">
                    <h2 class="h5 card-title mb-0">Nouveaux utilisateurs les plus actifs</h2>
                </div>
                <div class="card-body">
                    @forelse($activeUsers as $user)
                    <div class="d-flex align-items-center mb-3">
                        <div class="flex-shrink-0">
                            <div class="bg-primary bg-opacity-10 rounded-circle d-flex align-items-center justify-content-center"
                                 style="width: 40px; height: 40px;" aria-hidden="true">
                                <span class="fw-bold text-primary">{{ mb_substr($user['name'], 0, 1) }}</span>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <div class="fw-semibold text-break">{{ $user['name'] }}</div>
                            <div class="text-muted small">{{ $user['role'] }}</div>
                        </div>
                        <div class="text-end">
                            <div class="fw-bold">{{ $user['actions'] }}</div>
                            <div class="text-muted small">commandes + événements</div>
                        </div>
                    </div>
                    @empty
                    <div class="text-center text-muted py-3">
                        <i class="fas fa-users fa-2x mb-2 opacity-50" aria-hidden="true"></i>
                        <p class="mb-0">Aucun utilisateur actif sur la période</p>
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
<script @cspNonce>
// Graphique des inscriptions
const ctx = document.getElementById('registrationChart').getContext('2d');
new Chart(ctx, {
    type: 'line',
    data: {
        labels: {!! json_encode(array_column($registrations['rows'], 'date')) !!},
        datasets: [{
            label: 'Inscriptions',
            data: {!! json_encode(array_column($registrations['rows'], 'count')) !!},
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
