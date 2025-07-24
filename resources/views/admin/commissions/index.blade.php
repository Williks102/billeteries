<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Gestion des Commissions - Admin</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            --primary-orange: #FF6B35;
            --primary-dark: #E55A2B;
            --black-primary: #1a1a1a;
        }
        
        .admin-header {
            background: linear-gradient(135deg, var(--primary-orange) 0%, var(--black-primary) 100%);
            color: white;
            padding: 40px 0;
        }
        
        .stat-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            text-align: center;
            margin-bottom: 30px;
        }
        
        .table-custom {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        
        .table-custom th {
            background: var(--primary-orange);
            color: white;
            border: none;
            padding: 15px;
        }
        
        .table-custom td {
            padding: 12px 15px;
            border-bottom: 1px solid #f8f9fa;
        }
        
        .btn-orange {
            background: var(--primary-orange);
            border: none;
            color: white;
            border-radius: 25px;
            padding: 8px 20px;
            font-weight: 600;
        }
        
        .btn-orange:hover {
            background: var(--primary-dark);
            color: white;
        }
        
        .sidebar {
            background: #f8f9fa;
            min-height: calc(100vh - 56px);
            padding: 20px;
        }
        
        .sidebar .nav-link {
            color: #6c757d;
            padding: 12px 20px;
            border-radius: 10px;
            margin-bottom: 5px;
            transition: all 0.3s ease;
        }
        
        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            background: var(--primary-orange);
            color: white;
        }
        
        .status-filter {
            background: white;
            border-radius: 10px;
            padding: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
    </style>
</head>

<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
        <div class="container-fluid">
            <a class="navbar-brand fw-bold" href="{{ route('admin.dashboard') }}">
                <i class="fas fa-shield-alt me-2" style="color: var(--primary-orange);"></i>
                Admin - Billetterie CI
            </a>
            
            <div class="navbar-nav ms-auto">
                <div class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                        <i class="fas fa-user-shield me-1"></i>{{ Auth::user()->name }}
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="{{ route('home') }}"><i class="fas fa-home me-2"></i>Voir le site</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item" href="{{ route('logout') }}"
                               onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                                <i class="fas fa-sign-out-alt me-2"></i>Déconnexion
                            </a>
                        </li>
                    </ul>
                </div>
                <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                    @csrf
                </form>
            </div>
        </div>
    </nav>

    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar Admin -->
            <div class="col-md-3 col-lg-2 sidebar">
                <nav class="nav flex-column">
                    <a class="nav-link" href="{{ route('admin.dashboard') }}">
                        <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                    </a>
                    <a class="nav-link" href="{{ route('admin.users') }}">
                        <i class="fas fa-users me-2"></i>Utilisateurs
                    </a>
                    <a class="nav-link" href="{{ route('admin.events') }}">
                        <i class="fas fa-calendar-alt me-2"></i>Événements
                    </a>
                    <a class="nav-link active" href="{{ route('admin.commissions') }}">
                        <i class="fas fa-money-bill-wave me-2"></i>Commissions
                    </a>
                    <a class="nav-link" href="{{ route('admin.orders') }}">
                        <i class="fas fa-shopping-cart me-2"></i>Commandes
                    </a>
                    <a class="nav-link" href="{{ route('admin.reports') }}">
                        <i class="fas fa-chart-bar me-2"></i>Rapports
                    </a>
                    <a class="nav-link" href="{{ route('admin.settings') }}">
                        <i class="fas fa-cog me-2"></i>Paramètres
                    </a>
                </nav>
            </div>

            <!-- Contenu principal -->
            <div class="col-md-9 col-lg-10 p-0">
                <!-- Header -->
                <section class="admin-header">
                    <div class="container-fluid">
                        <div class="row align-items-center">
                            <div class="col-lg-8">
                                <h1 class="mb-2">💰 Gestion des Commissions</h1>
                                <p class="lead mb-0">Suivi et paiement des commissions aux promoteurs</p>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Messages de succès -->
                @if(session('success'))
                    <div class="container-fluid mt-4">
                        <div class="alert alert-success alert-dismissible fade show">
                            <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    </div>
                @endif

                <!-- Résumé des commissions -->
                <div class="container-fluid mt-4">
                    <div class="row">
                        <div class="col-lg-3 col-md-6">
                            <div class="stat-card">
                                <i class="fas fa-clock fa-2x text-warning mb-3"></i>
                                <h4 class="fw-bold">{{ \App\Helpers\CurrencyHelper::formatFCFA($summary['total_pending']) }}</h4>
                                <p class="text-muted mb-0">En attente</p>
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-6">
                            <div class="stat-card">
                                <i class="fas fa-check-circle fa-2x text-success mb-3"></i>
                                <h4 class="fw-bold">{{ \App\Helpers\CurrencyHelper::formatFCFA($summary['total_paid']) }}</h4>
                                <p class="text-muted mb-0">Payées</p>
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-6">
                            <div class="stat-card">
                                <i class="fas fa-pause-circle fa-2x text-danger mb-3"></i>
                                <h4 class="fw-bold">{{ \App\Helpers\CurrencyHelper::formatFCFA($summary['total_held']) }}</h4>
                                <p class="text-muted mb-0">En attente</p>
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-6">
                            <div class="stat-card">
                                <i class="fas fa-coins fa-2x text-primary mb-3"></i>
                                <h4 class="fw-bold">{{ \App\Helpers\CurrencyHelper::formatFCFA($summary['platform_total']) }}</h4>
                                <p class="text-muted mb-0">Revenus plateforme</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filtres -->
                <div class="container-fluid">
                    <div class="status-filter">
                        <form method="GET" action="{{ route('admin.commissions') }}" class="row align-items-center">
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Filtrer par statut :</label>
                                <select name="status" class="form-select" onchange="this.form.submit()">
                                    <option value="all" {{ $status == 'all' ? 'selected' : '' }}>Toutes les commissions</option>
                                    <option value="pending" {{ $status == 'pending' ? 'selected' : '' }}>En attente</option>
                                    <option value="paid" {{ $status == 'paid' ? 'selected' : '' }}>Payées</option>
                                    <option value="held" {{ $status == 'held' ? 'selected' : '' }}>Suspendues</option>
                                </select>
                            </div>
                            <div class="col-md-8 text-end">
                                @if($status == 'pending')
                                    <button type="button" class="btn btn-success" onclick="payAllPending()">
                                        <i class="fas fa-money-bill-wave me-2"></i>Payer toutes les commissions en attente
                                    </button>
                                @endif
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Liste des commissions -->
                <div class="container-fluid mb-5">
                    <div class="table-custom">
                        <div class="table-responsive">
                            <table class="table table-custom mb-0">
                                <thead>
                                    <tr>
                                        <th>Commande</th>
                                        <th>Promoteur</th>
                                        <th>Événement</th>
                                        <th>Revenus bruts</th>
                                        <th>Commission ({{ number_format($commissions->first()->commission_rate ?? 10, 1) }}%)</th>
                                        <th>Net promoteur</th>
                                        <th>Statut</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($commissions as $commission)
                                        <tr>
                                            <td>
                                                <strong>#{{ $commission->order->order_number }}</strong><br>
                                                <small class="text-muted">{{ $commission->created_at->format('d/m/Y H:i') }}</small>
                                            </td>
                                            <td>
                                                <strong>{{ $commission->promoteur->name }}</strong><br>
                                                <small class="text-muted">{{ $commission->promoteur->email }}</small>
                                            </td>
                                            <td>
                                                {{ Str::limit($commission->order->event->title, 40) }}<br>
                                                <small class="text-muted">{{ $commission->order->event->formatted_event_date }}</small>
                                            </td>
                                            <td>
                                                <strong>{{ \App\Helpers\CurrencyHelper::formatFCFA($commission->gross_amount) }}</strong>
                                            </td>
                                            <td>
                                                <strong class="text-primary">{{ \App\Helpers\CurrencyHelper::formatFCFA($commission->commission_amount) }}</strong>
                                            </td>
                                            <td>
                                                <strong class="text-success">{{ \App\Helpers\CurrencyHelper::formatFCFA($commission->net_amount) }}</strong>
                                            </td>
                                            <td>
                                                @if($commission->status == 'pending')
                                                    <span class="badge bg-warning">En attente</span>
                                                @elseif($commission->status == 'paid')
                                                    <span class="badge bg-success">Payée</span>
                                                    @if($commission->paid_at)
                                                        <br><small class="text-muted">{{ $commission->paid_at->format('d/m/Y') }}</small>
                                                    @endif
                                                @elseif($commission->status == 'held')
                                                    <span class="badge bg-danger">Suspendue</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if($commission->status == 'pending')
                                                    <form method="POST" action="{{ route('admin.commissions.pay', $commission) }}" style="display: inline;">
                                                        @csrf
                                                        <button type="submit" class="btn btn-sm btn-success" 
                                                                onclick="return confirm('Confirmer le paiement de {{ \App\Helpers\CurrencyHelper::formatFCFA($commission->net_amount) }} à {{ $commission->promoteur->name }} ?')">
                                                            <i class="fas fa-check"></i> Payer
                                                        </button>
                                                    </form>
                                                @else
                                                    <button class="btn btn-sm btn-outline-secondary" disabled>
                                                        <i class="fas fa-check-circle"></i> {{ ucfirst($commission->status) }}
                                                    </button>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="8" class="text-center py-4">
                                                <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                                <p class="text-muted">Aucune commission trouvée.</p>
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        
                        @if($commissions->hasPages())
                            <div class="p-3">
                                {{ $commissions->appends(request()->query())->links() }}
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        function payAllPending() {
            if (confirm('Êtes-vous sûr de vouloir payer toutes les commissions en attente ?')) {
                // À implémenter: route pour payer toutes les commissions en attente
                alert('Fonctionnalité en cours de développement');
            }
        }
    </script>
</body>
</html>