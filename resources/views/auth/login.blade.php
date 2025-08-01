<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Connexion - ClicBillet CI</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-orange: #FF6B35;
            --orange-light: #ff8c61;
            --orange-dark: #e55a2b;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: #f8fafc;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #374151;
        }
        
        .auth-container {
            width: 100%;
            max-width: 400px;
            padding: 2rem;
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            border: 1px solid #e5e7eb;
        }
        
        .brand-section {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .brand-logo {
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary-orange);
            margin-bottom: 0.5rem;
            letter-spacing: -0.025em;
        }
        
        .brand-subtitle {
            color: #6b7280;
            font-size: 0.875rem;
            font-weight: 400;
        }
        
        .form-floating {
            margin-bottom: 1rem;
        }
        
        .form-floating > .form-control {
            border: 1px solid #d1d5db;
            border-radius: 8px;
            padding: 1rem 0.75rem 0.25rem;
            height: 58px;
            font-size: 1rem;
            transition: all 0.15s ease-in-out;
            background: #fff;
        }
        
        .form-floating > .form-control:focus {
            border-color: var(--primary-orange);
            box-shadow: 0 0 0 3px rgba(255, 107, 53, 0.1);
            outline: none;
        }
        
        .form-floating > label {
            color: #6b7280;
            font-weight: 400;
            padding: 1rem 0.75rem;
        }
        
        .form-check {
            margin: 1.5rem 0;
        }
        
        .form-check-input:checked {
            background-color: var(--primary-orange);
            border-color: var(--primary-orange);
        }
        
        .form-check-input:focus {
            box-shadow: 0 0 0 0.2rem rgba(255, 107, 53, 0.25);
        }
        
        .form-check-label {
            color: #374151;
            font-size: 0.875rem;
        }
        
        .btn-primary {
            background: var(--primary-orange);
            border: none;
            border-radius: 8px;
            padding: 0.875rem 1.5rem;
            font-weight: 600;
            font-size: 0.875rem;
            letter-spacing: 0.025em;
            transition: all 0.15s ease-in-out;
            width: 100%;
            margin-bottom: 1.5rem;
        }
        
        .btn-primary:hover {
            background: var(--orange-dark);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(255, 107, 53, 0.4);
        }
        
        .btn-primary:active {
            transform: translateY(0);
        }
        
        .auth-footer {
            text-align: center;
            margin-top: 1.5rem;
            padding-top: 1.5rem;
            border-top: 1px solid #e5e7eb;
        }
        
        .auth-footer a {
            color: var(--primary-orange);
            text-decoration: none;
            font-weight: 500;
            font-size: 0.875rem;
        }
        
        .auth-footer a:hover {
            color: var(--orange-dark);
            text-decoration: underline;
        }
        
        .alert {
            border-radius: 8px;
            margin-bottom: 1rem;
            border: none;
        }
        
        .alert-danger {
            background: #fef2f2;
            color: #991b1b;
            border-left: 4px solid #dc2626;
        }
        
        .alert-success {
            background: #f0fdf4;
            color: #166534;
            border-left: 4px solid #16a34a;
        }
        
        /* Animation d'entrée */
        .auth-container {
            animation: slideUp 0.5s ease-out;
        }
        
        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        /* Responsive */
        @media (max-width: 480px) {
            .auth-container {
                margin: 1rem;
                padding: 1.5rem;
            }
            
            .brand-logo {
                font-size: 1.75rem;
            }
        }
        
        /* Focus états pour accessibilité */
        .form-control:focus,
        .btn:focus,
        .form-check-input:focus {
            outline: 2px solid var(--primary-orange);
            outline-offset: 2px;
        }
    </style>
</head>
<body>
    <div class="auth-container">
        <!-- En-tête de la marque -->
        <div class="brand-section">
            <div class="brand-logo">
                <i class="fas fa-ticket-alt me-2"></i>ClicBillet CI
            </div>
            <div class="brand-subtitle">
                Connectez-vous à votre compte
            </div>
        </div>

        <!-- Messages d'erreur -->
        @if ($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- Messages de succès -->
        @if (session('status'))
            <div class="alert alert-success">
                {{ session('status') }}
            </div>
        @endif

        <!-- Formulaire de connexion -->
        <form method="POST" action="{{ route('login') }}">
            @csrf
            
            <div class="form-floating">
                <input type="email" 
                       class="form-control @error('email') is-invalid @enderror" 
                       id="email" 
                       name="email" 
                       placeholder="nom@exemple.com"
                       value="{{ old('email') }}" 
                       required 
                       autocomplete="email" 
                       autofocus>
                <label for="email">Adresse email</label>
            </div>

            <div class="form-floating">
                <input type="password" 
                       class="form-control @error('password') is-invalid @enderror" 
                       id="password" 
                       name="password" 
                       placeholder="Mot de passe"
                       required 
                       autocomplete="current-password">
                <label for="password">Mot de passe</label>
            </div>

            <div class="form-check">
                <input class="form-check-input" 
                       type="checkbox" 
                       name="remember" 
                       id="remember" 
                       {{ old('remember') ? 'checked' : '' }}>
                <label class="form-check-label" for="remember">
                    Se souvenir de moi
                </label>
            </div>

            <button type="submit" class="btn btn-primary">
                <i class="fas fa-sign-in-alt me-2"></i>Se connecter
            </button>
        </form>

        <!-- Pied de page -->
        <div class="auth-footer">
            <p class="mb-0">
                Pas encore de compte ? 
                <a href="{{ route('register') }}">Créer un compte</a>
            </p>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>