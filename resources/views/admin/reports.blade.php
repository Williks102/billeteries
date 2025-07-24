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
    
    .report-card {
        background: white;
        border-radius: 15px;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        border-left: 4px solid #FF6B35;
        padding: 2rem;
        margin-bottom: 2rem;
        transition: transform 0.3s ease;
    }
    
    .report-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 15px rgba(0,0,0,0.15);
    }
    
    .report-icon {
        width: 60px;
        height: 60px;
        background: linear-gradient(135deg, #FF6B35, #E55A2B);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 1.5rem;
        margin-bottom: 1rem;
    }
    
    .export-section {
        background: white;
        border-radius: 15px;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        border-left: 4px solid #28a745;
        padding: 2rem;
        margin-bottom: 2rem;
    }
    
    .period-selector {
        background: white;
        border-radius: 15px;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        border-left: 4px solid #007bff;
        padding: 2rem;
        margin-bottom: 2rem;
    }
    
    .form-select, .form-control {
        border: 2px solid #ddd;
        border-radius: 8px;
        padding: 0.75rem;
        transition: all 0.3s ease;
    }
    
    .form-select:focus, .form-control:focus {
        border-color: #FF6B35;
        outline: none;
        box-shadow: 0 0 0 0.2rem rgba(255, 107, 53, 0.25);
    }
    
    .quick-stats {
        background: white;
        border-radius: 15px;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        border-left: 4px solid #ffc107;
        padding: 2rem;
        margin-bottom: 2rem;
    }
    
    .stat-item {
        text-align: center;
        padding: 1rem;
        border-radius: 10px;
        background: #f8f9fa;
        margin-bottom: 1rem;
    }
    
    .stat-item h4 {
        color: #FF6B35;
        font-weight: bold;
        margin-bottom: 0.5rem;
    }
    
    .stat-item .icon {
        font-size: 2rem;
        color: #FF6B35;
        margin-bottom: 0.5rem;
    }
    
    .download-history {
        background: white;
        border-radius: 15px;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        border-left: 4px solid #6f42c1;
        padding: 2rem;
    }
    
    .history-item {
        border-bottom: 1px solid #eee;
        padding: 1rem 0;
        display: flex;
        justify-content: between;
        align-items: center;
    }
    
    .history-item:last-child {
        border-bottom: none;
    }
    
    .file-type-badge {
        padding: 0.25rem 0.75rem;
        border-radius: 15px;
        font-size: 0.75rem;
        font-weight: 600;
        text-transform: uppercase;
    }
    
    .badge-excel {
        background: #d4edda;
        color: #155724;
    }
    
    .badge-pdf {
        background: #f8d7da;
        color: #721c24;
    }
    
    .badge-csv {
        background: #d1ecf1;
        color: #0c5460;
    }
</style>
@endpush

@section('content')
<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h1 class="mb-2">
                <i class="fas fa-chart-bar me-3"></i>
                Rapports & Analyses
            </h1>
            <p class="mb-0 opacity-75">Générez et exportez vos rapports de données</p>
        </div>
        <div>
            <button class="btn btn-black btn-lg" onclick="exportAll()">
                <i class="fas fa-download me-2"></i>Export Complet
            </button>
        </div>
    </div>
</div>

<!-- Sélection de période -->
<div class="period-selector">
    <h5 class="mb-4">
        <i class="fas fa-calendar-alt text-primary me-2"></i>
        Sélectionner la période
    </h5>
    <form id="periodForm" class="row">
        <div class="col-md-3">
            <label class="form-label fw-bold">Période prédéfinie</label>
            <select class="form-select" name="period" onchange="updatePeriod()">
                <option value="today">Aujourd'hui</option>
                <option value="yesterday">Hier</option>
                <option value="this_week">Cette semaine</option>
                <option value="last_week">Semaine dernière</option>
                <option value="this_month" selected>Ce mois</option>
                <option value="last_month">Mois dernier</option>
                <option value="this_year">Cette année</option>
                <option value="custom">Période personnalisée</option>
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label fw-bold">Date de début</label>
            <input type="date" class="form-control" name="start_date" value="{{ date('Y-m-01') }}">
        </div>
        <div class="col-md-3">
            <label class="form-label fw-bold">Date de fin</label>
            <input type="date" class="form-control" name="end_date" value="{{ date('Y-m-d') }}">
        </div>
        <div class="col-md-3">
            <label class="form-label fw-bold">&nbsp;</label>
            <button type="button" class="btn btn-black w-100" onclick="refreshReports()">
                <i class="fas fa-sync me-2"></i>Actualiser
            </button>
        </div>
    </form>
</div>

<!-- Statistiques rapides -->
<div class="quick-stats">
    <h5 class="mb-4">
        <i class="fas fa-tachometer-alt text-warning me-2"></i>
        Aperçu rapide - Ce mois
    </h5>
    <div class="row">
        <div class="col-md-3">
            <div class="stat-item">
                <div class="icon">
                    <i class="fas fa-euro-sign"></i>
                </div>
                <h4>{{ number_format($monthlyRevenue ?? 0) }} F</h4>
                <p class="text-muted mb-0">Chiffre d'affaires</p>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-item">
                <div class="icon">
                    <i class="fas fa-shopping-cart"></i>
                </div>
                <h4>{{ $monthlyOrders ?? 0 }}</h4>
                <p class="text-muted mb-0">Commandes</p>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-item">
                <div class="icon">
                    <i class="fas fa-calendar-alt"></i>
                </div>
                <h4>{{ $monthlyEvents ?? 0 }}</h4>
                <p class="text-muted mb-0">Événements</p>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-item">
                <div class="icon">
                    <i class="fas fa-users"></i>
                </div>
                <h4>{{ $monthlyUsers ?? 0 }}</h4>
                <p class="text-muted mb-0">Nouveaux utilisateurs</p>
            </div>
        </div>
    </div>
</div>

<!-- Types de rapports -->
<div class="row">
    <div class="col-md-6">
        <div class="report-card">
            <div class="report-icon">
                <i class="fas fa-chart-line"></i>
            </div>
            <h5>Rapport Financier</h5>
            <p class="text-muted mb-4">Revenus, commissions, transactions et analyse financière détaillée.</p>
            <div class="d-flex gap-2">
                <a href="{{ route('admin.export.financial', ['format' => 'excel']) }}" class="btn btn-black">
                    <i class="fas fa-file-excel me-2"></i>Excel
                </a>
                <a href="{{ route('admin.export.financial', ['format' => 'pdf']) }}" class="btn btn-outline-dark">
                    <i class="fas fa-file-pdf me-2"></i>PDF
                </a>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="report-card">
            <div class="report-icon">
                <i class="fas fa-users"></i>
            </div>
            <h5>Rapport Utilisateurs</h5>
            <p class="text-muted mb-4">Statistiques des utilisateurs, inscriptions et activité.</p>
            <div class="d-flex gap-2">
                <a href="{{ route('admin.export.users', ['format' => 'excel']) }}" class="btn btn-black">
                    <i class="fas fa-file-excel me-2"></i>Excel
                </a>
                <a href="{{ route('admin.export.users', ['format' => 'csv']) }}" class="btn btn-outline-dark">
                    <i class="fas fa-file-csv me-2"></i>CSV
                </a>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="report-card">
            <div class="report-icon">
                <i class="fas fa-calendar-alt"></i>
            </div>
            <h5>Rapport Événements</h5>
            <p class="text-muted mb-4">Performance des événements, ventes de billets et promoteurs.</p>
            <div class="d-flex gap-2">
                <a href="{{ route('admin.export.events', ['format' => 'excel']) }}" class="btn btn-black">
                    <i class="fas fa-file-excel me-2"></i>Excel
                </a>
                <a href="{{ route('admin.export.events', ['format' => 'pdf']) }}" class="btn btn-outline-dark">
                    <i class="fas fa-file-pdf me-2"></i>PDF
                </a>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="report-card">
            <div class="report-icon">
                <i class="fas fa-shopping-cart"></i>
            </div>
            <h5>Rapport Commandes</h5>
            <p class="text-muted mb-4">Détail des commandes, statuts de paiement et historique.</p>
            <div class="d-flex gap-2">
                <a href="{{ route('admin.export.orders', ['format' => 'excel']) }}" class="btn btn-black">
                    <i class="fas fa-file-excel me-2"></i>Excel
                </a>
                <a href="{{ route('admin.export.orders', ['format' => 'csv']) }}" class="btn btn-outline-dark">
                    <i class="fas fa-file-csv me-2"></i>CSV
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Section export personnalisé -->
<div class="export-section">
    <h5 class="mb-4">
        <i class="fas fa-cogs text-success me-2"></i>
        Export Personnalisé
    </h5>
    <form class="row">
        <div class="col-md-4">
            <label class="form-label fw-bold">Type de données</label>
            <select class="form-select" name="data_type">
                <option value="all">Toutes les données</option>
                <option value="financial">Données financières</option>
                <option value="users">Utilisateurs</option>
                <option value="events">Événements</option>
                <option value="orders">Commandes</option>
                <option value="tickets">Billets</option>
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label fw-bold">Format</label>
            <select class="form-select" name="format">
                <option value="excel">Excel (.xlsx)</option>
                <option value="csv">CSV (.csv)</option>
                <option value="pdf">PDF (.pdf)</option>
                <option value="json">JSON (.json)</option>
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label fw-bold">Compression</label>
            <select class="form-select" name="compression">
                <option value="none">Aucune</option>
                <option value="zip">ZIP</option>
                <option value="gzip">GZIP</option>
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label fw-bold">&nbsp;</label>
            <button type="submit" class="btn btn-black w-100">
                <i class="fas fa-download me-2"></i>Exporter
            </button>
        </div>
    </form>
</div>

<!-- Historique des téléchargements -->
<div class="download-history">
    <h5 class="mb-4">
        <i class="fas fa-history text-purple me-2"></i>
        Historique des exports
    </h5>
    <div class="history-list">
        <div class="history-item">
            <div class="d-flex align-items-center">
                <i class="fas fa-file-excel text-success me-3 fa-2x"></i>
                <div class="flex-grow-1">
                    <h6 class="mb-1">Rapport financier complet</h6>
                    <p class="text-muted mb-0">Exporté le 13/07/2025 à 15:30</p>
                </div>
                <span class="file-type-badge badge-excel me-3">EXCEL</span>
                <a href="#" class="btn btn-sm btn-black">
                    <i class="fas fa-download"></i>
                </a>
            </div>
        </div>
        
        <div class="history-item">
            <div class="d-flex align-items-center">
                <i class="fas fa-file-pdf text-danger me-3 fa-2x"></i>
                <div class="flex-grow-1">
                    <h6 class="mb-1">Liste des utilisateurs</h6>
                    <p class="text-muted mb-0">Exporté le 12/07/2025 à 09:15</p>
                </div>
                <span class="file-type-badge badge-pdf me-3">PDF</span>
                <a href="#" class="btn btn-sm btn-black">
                    <i class="fas fa-download"></i>
                </a>
            </div>
        </div>
        
        <div class="history-item">
            <div class="d-flex align-items-center">
                <i class="fas fa-file-csv text-info me-3 fa-2x"></i>
                <div class="flex-grow-1">
                    <h6 class="mb-1">Données des événements</h6>
                    <p class="text-muted mb-0">Exporté le 10/07/2025 à 14:45</p>
                </div>
                <span class="file-type-badge badge-csv me-3">CSV</span>
                <a href="#" class="btn btn-sm btn-black">
                    <i class="fas fa-download"></i>
                </a>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function updatePeriod() {
        const period = document.querySelector('select[name="period"]').value;
        const startDate = document.querySelector('input[name="start_date"]');
        const endDate = document.querySelector('input[name="end_date"]');
        
        const today = new Date();
        let start = new Date();
        let end = new Date();
        
        switch(period) {
            case 'today':
                start = end = today;
                break;
            case 'yesterday':
                start = end = new Date(today.getTime() - 24*60*60*1000);
                break;
            case 'this_week':
                start = new Date(today.getTime() - today.getDay()*24*60*60*1000);
                end = today;
                break;
            case 'this_month':
                start = new Date(today.getFullYear(), today.getMonth(), 1);
                end = today;
                break;
            // ... autres cas
        }
        
        if (period !== 'custom') {
            startDate.value = start.toISOString().split('T')[0];
            endDate.value = end.toISOString().split('T')[0];
        }
    }
    
    function refreshReports() {
        // Actualiser les statistiques avec la nouvelle période
        const formData = new FormData(document.getElementById('periodForm'));
        // Ici vous pourriez faire un appel AJAX pour actualiser les données
        console.log('Actualisation des rapports...');
    }
    
    function exportAll() {
        if (confirm('Êtes-vous sûr de vouloir exporter toutes les données ? Cela peut prendre du temps.')) {
            window.location.href = "{{ route('admin.export.all') }}";
        }
    }
</script>
@endpush