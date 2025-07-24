{{-- resources/views/welcome.blade.php - Inspiré de Tikerama --}}
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('app.name') }} - N°1 de la billetterie événementielle</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            --primary-red: #ee5c07ff;
            --primary-blue: #ff6b35;
            --gradient-bg: linear-gradient(135deg, var(--primary-blue) 0%, var(--primary-red) 100%);
            --orange-gradient: linear-gradient(135deg, #ff6b35, #f7931e);
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        /* Header */
        .navbar {
            background: white;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 1rem 0;
        }
        
        .navbar-brand {
            font-weight: bold;
            font-size: 1.5rem;
            color: var(--primary-red) !important;
        }
        
        .city-nav {
            background: #f8f9fa;
            padding: 0.5rem 0;
            font-size: 0.9rem;
        }
        
        .city-link {
            color: #6c757d;
            text-decoration: none;
            margin: 0 1rem;
            transition: color 0.3s;
        }
        
        .city-link:hover {
            color: var(--primary-red);
        }
        
        /* Hero Section */
        .hero-section {
            background: var(--gradient-bg);
            color: white;
            padding: 4rem 0;
            position: relative;
            overflow: hidden;
        }
        
        .hero-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.1);
        }
        
        .hero-content {
            position: relative;
            z-index: 2;
        }
        
        .hero-title {
            font-size: 3rem;
            font-weight: bold;
            margin-bottom: 1rem;
            line-height: 1.2;
        }
        
        .hero-subtitle {
            font-size: 1.25rem;
            margin-bottom: 2rem;
            opacity: 0.9;
        }
        
        .search-container {
            background: white;
            border-radius: 50px;
            padding: 0.5rem;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        
        .search-input {
            border: none;
            padding: 1rem 1.5rem;
            border-radius: 50px;
            font-size: 1.1rem;
        }
        
        .search-input:focus {
            outline: none;
            box-shadow: none;
        }
        
        .search-btn {
            background: var(--primary-red);
            border: none;
            border-radius: 50px;
            padding: 1rem 2rem;
            color: white;
            font-weight: 600;
        }
        
        .category-pills {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
            justify-content: center;
        }
        
        .category-pill {
            background: rgba(255,255,255,0.2);
            border: 1px solid rgba(255,255,255,0.3);
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 25px;
            text-decoration: none;
            transition: all 0.3s;
            backdrop-filter: blur(10px);
        }
        
        .category-pill:hover {
            background: white;
            color: var(--primary-blue);
            text-decoration: none;
        }
        
        /* Purchase Options */
        .purchase-options {
            background: white;
            padding: 2rem 0;
        }
        
        .purchase-item {
            text-align: center;
            padding: 2rem;
            border-radius: 15px;
            transition: transform 0.3s;
        }
        
        .purchase-item:hover {
            transform: translateY(-5px);
        }
        
        .purchase-icon {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: var(--gradient-bg);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            color: white;
            font-size: 2rem;
        }
        
        /* Events Grid */
        .events-section {
            padding: 4rem 0;
            background: #f8f9fa;
        }
        
        .event-card {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            position: relative;
        }
        
        .event-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }
        
        .event-image {
            height: 250px;
            background: var(--gradient-bg);
            position: relative;
            overflow: hidden;
        }
        
        .event-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .verified-badge {
            position: absolute;
            top: 10px;
            left: 10px;
            background: #28a745;
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .event-date {
            position: absolute;
            top: 10px;
            right: 10px;
            background: rgba(0,0,0,0.8);
            color: white;
            padding: 0.5rem;
            border-radius: 8px;
            text-align: center;
            font-size: 0.9rem;
        }
        
        .event-info {
            padding: 1.5rem;
        }
        
        .event-title {
            font-size: 1.25rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
            color: #333;
        }
        
        .event-details {
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 1rem;
        }
        
        .event-price {
            font-size: 1.1rem;
            font-weight: bold;
            color: var(--primary-red);
        }
        
        .btn-buy {
            background: var(--primary-red);
            border: none;
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 25px;
            font-weight: 600;
            width: 100%;
            margin-top: 1rem;
            transition: all 0.3s;
        }
        
        .btn-buy:hover {
            background: #d32f2f;
            color: white;
        }
        
        /* Categories Section */
        .categories-section {
            padding: 4rem 0;
        }
        
        .category-card {
            height: 200px;
            border-radius: 15px;
            position: relative;
            overflow: hidden;
            cursor: pointer;
            transition: transform 0.3s;
        }
        
        .category-card:hover {
            transform: scale(1.05);
        }
        
        .category-autre {
            background: linear-gradient(135deg, #ff6b35, #f7931e);
        }
        
        .category-concert {
            background: linear-gradient(135deg, #667eea, #764ba2);
        }
        
        .category-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.3);
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .category-title {
            color: white;
            font-size: 1.5rem;
            font-weight: bold;
            text-align: center;
        }
        
        /* Stats Section */
        .stats-section {
            background: var(--orange-gradient);
            color: white;
            padding: 3rem 0;
        }
        
        .stat-item {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .stat-number {
            font-size: 3rem;
            font-weight: bold;
            display: block;
        }
        
        .stat-label {
            font-size: 1.1rem;
            opacity: 0.9;
        }
        
        /* Footer */
        .footer {
            background: #2c3e50;
            color: white;
            padding: 3rem 0 1rem;
        }
        
        .footer-section {
            margin-bottom: 2rem;
        }
        
        .footer-title {
            font-size: 1.1rem;
            font-weight: bold;
            margin-bottom: 1rem;
        }
        
        .footer-link {
            color: #bdc3c7;
            text-decoration: none;
            display: block;
            padding: 0.25rem 0;
            transition: color 0.3s;
        }
        
        .footer-link:hover {
            color: white;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .hero-title {
                font-size: 2rem;
            }
            
            .category-pills {
                justify-content: center;
            }
            
            .search-container {
                margin-bottom: 1rem;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation des villes -->
    <div class="city-nav">
        <div class="container">
            <div class="d-flex justify-content-center flex-wrap">
                <a href="#" class="city-link">ABIDJAN</a>
                <a href="#" class="city-link">BOUAKÉ</a>
                <a href="#" class="city-link">DALOA</a>
                <a href="#" class="city-link">YAMOUSSOUKRO</a>
                <a href="#" class="city-link">SAN-PÉDRO</a>
                <a href="#" class="city-link">KORHOGO</a>
            </div>
        </div>
    </div>

    <!-- Header -->
    <nav class="navbar navbar-expand-lg">
        <div class="container">
            <a class="navbar-brand" href="#">
                {{ config('app.name', 'BILLETTERIE') }}
                <small class="text-muted" style="font-size: 0.7rem;">.com</small>
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <div class="navbar-nav ms-auto">
                    <a class="nav-link" href="#"><i class="fas fa-search"></i></a>
                    @if(auth()->check())
                        <div class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                                <i class="fas fa-user"></i> {{ auth()->user()->name }}
                            </a>
                            <ul class="dropdown-menu">
                                @if(auth()->user()->isPromoteur())
                                    <li><a class="dropdown-item" href="{{ route('promoteur.dashboard') }}">Dashboard</a></li>
                                @elseif(auth()->user()->isAcheteur())
                                    <li><a class="dropdown-item" href="{{ route('acheteur.dashboard') }}">Mes billets</a></li>
                                @elseif(auth()->user()->isAdmin())
                                    <li><a class="dropdown-item" href="{{ route('admin.dashboard') }}">Administration</a></li>
                                @endif
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <a class="dropdown-item" href="{{ route('logout') }}"
                                       onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                                        Déconnexion
                                    </a>
                                </li>
                            </ul>
                        </div>
                        <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">@csrf</form>
                    @else
                        <a class="nav-link" href="{{ route('login') }}"><i class="fas fa-user"></i></a>
                    @endif
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6 hero-content">
                    <h1 class="hero-title">N°1 DE LA BILLETTERIE ÉVÉNEMENTIELLE</h1>
                    <p class="hero-subtitle">Achetez vos tickets :</p>
                    
                    <div class="mb-4">
                        <div class="d-flex align-items-center mb-2">
                            <i class="fas fa-globe me-3"></i>
                            <span>En ligne</span>
                        </div>
                        <div class="d-flex align-items-center mb-2">
                            <i class="fas fa-store me-3"></i>
                            <span>En point de vente physique</span>
                        </div>
                        <div class="d-flex align-items-center">
                            <i class="fas fa-phone me-3"></i>
                            <span>Par téléphone ou whatsapp</span>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-6">
                    <div class="search-container">
                        <form method="GET" action="{{ route('home') }}" class="d-flex">
                            <input type="text" 
                                   name="search" 
                                   class="form-control search-input" 
                                   placeholder="Recherchez un événement..."
                                   value="{{ request('search') }}">
                            <button type="submit" class="btn search-btn">
                                <i class="fas fa-search"></i>
                            </button>
                        </form>
                    </div>
                    
                    <div class="category-pills">
                        <a href="{{ route('home') }}" class="category-pill">TOUS LES ÉVÉNEMENTS</a>
                        <a href="{{ route('home', ['category' => 'concert']) }}" class="category-pill">CONCERTS</a>
                        <a href="{{ route('home', ['category' => 'spectacle']) }}" class="category-pill">SPECTACLES</a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Section "Y-a quels mouvements ?" -->
    @if(isset($events) && $events->count() > 0)
    <section class="events-section">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="fw-bold">Y-A QUELS MOUVEMENTS ?</h2>
                <p class="text-muted">Découvrez les événements du moment et achetez vos places</p>
            </div>
            
            <div class="row">
                @foreach($events->take(6) as $event)
                    <div class="col-lg-4 col-md-6 mb-4">
                        <div class="event-card">
                            <div class="event-image">
                                @if($event->image)
                                    <img src="{{ Storage::url($event->image) }}" alt="{{ $event->title }}">
                                @endif
                                
                                <div class="verified-badge">
                                    <i class="fas fa-check"></i> vérifié
                                </div>
                                
                                <div class="event-date">
                                    <div style="font-weight: bold;">{{ $event->event_date->format('d') }}</div>
                                    <div style="font-size: 0.8rem;">{{ $event->event_date->format('M') }}</div>
                                </div>
                            </div>
                            
                            <div class="event-info">
                                <h5 class="event-title">{{ Str::limit($event->title, 30) }}</h5>
                                
                                <div class="event-details">
                                    <div class="mb-1">
                                        <i class="fas fa-calendar me-2"></i>
                                        {{ $event->event_date->format('d/m/Y') }} à {{ $event->event_time ? $event->event_time->format('H:i') : '20h00' }}
                                    </div>
                                    <div class="mb-1">
                                        <i class="fas fa-map-marker-alt me-2"></i>
                                        {{ $event->venue }}
                                    </div>
                                    <div class="mb-2">
                                        <i class="fas fa-tag me-2"></i>
                                        {{ $event->category->name ?? 'Événement' }}
                                    </div>
                                </div>
                                
                                <div class="d-flex justify-content-between align-items-center">
                                    <div class="event-price">
                                        À partir de {{ number_format($event->getLowestPrice()) }} FCFA
                                    </div>
                                </div>
                                
                                <a href="{{ route('events.show', $event) }}" class="btn btn-buy">
                                    ACHETER TICKETS
                                </a>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>
    @endif

    <!-- Trouvez par catégorie -->
    <section class="categories-section">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="fw-bold">TROUVEZ PAR CATÉGORIE</h2>
                <p class="text-muted">En un clin d'œil, accédez à tous les événements correspondant à votre mood actuel</p>
            </div>
            
            <div class="row">
                <div class="col-md-6 mb-4">
                    <div class="category-card category-autre">
                        <div class="category-overlay">
                            <h3 class="category-title">Autre</h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 mb-4">
                    <div class="category-card category-concert">
                        <div class="category-overlay">
                            <h3 class="category-title">Concert-Spectacle</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Section Stats -->
    <section class="stats-section">
        <div class="container">
            <div class="text-center mb-4">
                <h2 class="fw-bold">EN CE MOMENT</h2>
            </div>
            
            <div class="row">
                <div class="col-md-3 col-6">
                    <div class="stat-item">
                        <span class="stat-number">{{ isset($stats['total_events']) ? $stats['total_events'] : 240 }}</span>
                        <span class="stat-label">ÉVÉNEMENTS</span>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="stat-item">
                        <span class="stat-number">{{ isset($categories) ? $categories->count() : 10 }}</span>
                        <span class="stat-label">CATÉGORIES</span>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="stat-item">
                        <span class="stat-number">28</span>
                        <span class="stat-label">VILLES</span>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="stat-item">
                        <span class="stat-number">{{ isset($stats['upcoming_events']) ? $stats['upcoming_events'] : 3 }}</span>
                        <span class="stat-label">ÉVÉNEMENTS AUJOURD'HUI</span>
                    </div>
                </div>
            </div>
            
            <div class="text-center mt-4">
                <a href="{{ route('home') }}" class="btn btn-light btn-lg">
                    VOIR LES ÉVÉNEMENTS
                </a>
            </div>
        </div>
    </section>

    <!-- Section Comment acheter -->
    <section class="purchase-options">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="fw-bold">VOUS SOUHAITEZ ACHETER DES TICKETS ?</h2>
                <p class="text-muted">Achetez en 3 étapes faciles !</p>
            </div>
            
            <div class="row">
                <div class="col-md-4">
                    <div class="purchase-item">
                        <div class="purchase-icon">
                            <i class="fas fa-search"></i>
                        </div>
                        <h5>1. Trouvez l'événement</h5>
                        <p class="text-muted">Explorez une grande variété d'événements sur notre plateforme. Utilisez la barre de recherche pour découvrir ou trouver des événements.</p>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="purchase-item">
                        <div class="purchase-icon">
                            <i class="fas fa-shopping-cart"></i>
                        </div>
                        <h5>2. Achetez vos places</h5>
                        <p class="text-muted">Après avoir choisi votre événement et les tickets nécessaires, procédez au paiement via Mobile Money ou carte bancaire.</p>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="purchase-item">
                        <div class="purchase-icon">
                            <i class="fas fa-ticket-alt"></i>
                        </div>
                        <h5>3. Profitez</h5>
                        <p class="text-muted">Pour accéder à l'événement, vous devez scanner le code QR présent sur votre ticket à l'entrée avec les contrôleurs.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="row">
                <div class="col-md-4">
                    <div class="footer-section">
                        <h5 class="footer-title">{{ config('app.name') }}</h5>
                        <p>Achetez et vendez des tickets d'événements.</p>
                    </div>
                </div>
                
                <div class="col-md-2">
                    <div class="footer-section">
                        <h6 class="footer-title">Pages</h6>
                        <a href="#" class="footer-link">Événements</a>
                        <a href="#" class="footer-link">FAQ</a>
                        <a href="#" class="footer-link">Contact</a>
                    </div>
                </div>
                
                <div class="col-md-3">
                    <div class="footer-section">
                        <h6 class="footer-title">Catégories</h6>
                        <a href="#" class="footer-link">Concert-Spectacle</a>
                        <a href="#" class="footer-link">Sport</a>
                        <a href="#" class="footer-link">Formation</a>
                    </div>
                </div>
                
                <div class="col-md-3">
                    <div class="footer-section">
                        <h6 class="footer-title">Organisateurs</h6>
                        @if(!auth()->check())
                            <a href="{{ route('register') }}" class="footer-link">Créer un compte</a>
                        @endif
                        <a href="{{ route('login') }}" class="footer-link">Se connecter</a>
                    </div>
                </div>
            </div>
            
            <hr style="border-color: #34495e;">
            
            <div class="text-center">
                <p>&copy; {{ date('Y') }} {{ config('app.name') }}, tous droits réservés.</p>
            </div>
        </div>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>