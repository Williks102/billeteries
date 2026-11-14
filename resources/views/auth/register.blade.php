<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Inscription - ClicBillet CI</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/charte.css') }}">
    <style>
        :root {
            --primary-orange: var(--brand);
            --orange-light: var(--brand-decor);
            --orange-dark: var(--brand-hover);
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: var(--font-sans);
            background: #f8fafc;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #374151;
            padding: 2rem 0;
        }
        
        .auth-container {
            width: 100%;
            max-width: 480px;
            padding: 2rem;
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            border: 1px solid #e5e7eb;
            margin: 1rem;
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
        
        .auth-field {
            margin-bottom: 1rem;
        }
        
        .auth-field > .form-control,
        .auth-field > .form-select {
            border: 1px solid #d1d5db;
            border-radius: 8px;
            padding: 0.5rem 0.75rem;
            min-height: 44px;
            font-size: 1rem;
            transition: all 0.15s ease-in-out;
            background: #fff;
        }
        
        .auth-field > .form-control:focus,
        .auth-field > .form-select:focus {
            border-color: var(--primary-orange);
            box-shadow: 0 0 0 3px rgba(var(--brand-rgb), 0.1);
            outline: none;
        }
        
        .auth-field > .form-label {
            color: var(--ink);
            font-weight: 500;
            padding: 0;
        }
        
        .role-selection {
            margin: 1.5rem 0;    
        }
        
        .role-selection .form-label {
            color: #374151;
            font-weight: 600;
            margin-bottom: 0.75rem;
            font-size: 0.875rem;
        }
        
        .role-options {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.75rem;
        }
        
        .role-option {
            position: relative;
        }
        
        .role-option input[type="radio"] {
            position: absolute;
            opacity: 0;
            width: 100%;
            height: 100%;
            margin: 0;
            cursor: pointer;
        }
        
        .role-card {
            display: block;
            padding: 1rem;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            text-align: center;
            cursor: pointer;
            transition: all 0.15s ease-in-out;
            background: #fff;
        }
        
        .role-card:hover {
            border-color: var(--orange-light);
            background: #fef3f0;
        }
        
        .role-option input[type="radio"]:checked + .role-card {
            border-color: var(--primary-orange);
            background: #fef3f0;
            color: var(--primary-orange);
        }
        
        .role-icon {
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
            display: block;
        }
        
        .role-title {
            font-weight: 600;
            font-size: 0.875rem;
            margin-bottom: 0.25rem;
        }
        
        .role-description {
            font-size: 0.75rem;
            color: #6b7280;
            line-height: 1.3;
        }
        
        .phone-input-group {
            position: relative;
        }
        
        
        
        .phone-input {
            padding-left: 75px !important;
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
            margin: 1.5rem 0;
        }
        
        .btn-primary:hover {
            background: var(--orange-dark);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(var(--brand-rgb), 0.4);
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
        
        .password-requirements {
            background: #f0f9ff;
            border-radius: 8px;
            padding: 0.75rem;
            margin-bottom: 1rem;
            border-left: 4px solid #0ea5e9;
        }
        
        .password-requirements .title {
            font-size: 0.75rem;
            font-weight: 600;
            color: #0369a1;
            margin-bottom: 0.5rem;
        }
        
        .password-requirements .requirement {
            font-size: 0.7rem;
            color: #0284c7;
            margin-bottom: 0.125rem;
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
        @media (max-width: 520px) {
            .auth-container {
                margin: 0.5rem;
                padding: 1.5rem;
            }
            
            .brand-logo {
                font-size: 1.75rem;
            }
            
            .role-options {
                grid-template-columns: 1fr;
            }
        }
        
        /* Focus états pour accessibilité */
        .form-control:focus,
        .form-select:focus,
        .btn:focus {
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
                Créez votre compte gratuitement
            </div>
        </div>

        <!-- Messages d'erreur -->
        @if ($errors->any())
            <div class="alert alert-danger" role="alert">
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- Formulaire d'inscription -->
        <form method="POST" action="{{ route('register') }}">
            @csrf
            
            <div class="auth-field ">
                <label for="name" class="form-label">Nom complet</label>
                <input type="text" 
                       class="form-control @error('name') is-invalid @enderror" 
                       id="name" 
                       name="name" 
                       placeholder="Nom complet"
                       value="{{ old('name') }}" 
                       required 
                       autocomplete="name" 
                       autofocus
                       aria-describedby="@error('name') name-error @enderror" @error('name') aria-invalid="true" @enderror>
                <x-field-error name="name" />
            </div>

            <div class="auth-field ">
                <label for="email" class="form-label">Adresse email</label>
                <input type="email" inputmode="email" autocapitalize="none" spellcheck="false" 
                       class="form-control @error('email') is-invalid @enderror" 
                       id="email" 
                       name="email" 
                       placeholder="nom@exemple.com"
                       value="{{ old('email') }}" 
                       required 
                       autocomplete="email"
                       aria-describedby="@error('email') email-error @enderror" @error('email') aria-invalid="true" @enderror>
                <x-field-error name="email" />
            </div>

            <div class="auth-field phone-input-group">
                <label for="phone" class="form-label">Téléphone</label>
                <input type="tel" inputmode="tel" 
                       class="form-control @error('phone') is-invalid @enderror" 
                       id="phone" 
                       name="phone" 
                       placeholder="01 23 45 67 89"
                       value="{{ old('phone') }}" 
                       required 
                       pattern="[0-9\s]{10,}"
                       autocomplete="tel"
                       aria-describedby="phone-help @error('phone') phone-error @enderror" @error('phone') aria-invalid="true" @enderror>
                <div id="phone-help" class="form-text">Ex : 07 12 34 56 78</div>
                <x-field-error name="phone" />
            </div>

            <!-- Sélection du rôle -->
            <div class="role-selection">
                <label class="form-label">Je souhaite :</label>
                <div class="role-options">
                    <div class="role-option">
                        <input type="radio" 
                               name="role" 
                               value="acheteur" 
                               id="role_acheteur" 
                               {{ old('role', 'acheteur') == 'acheteur' ? 'checked' : '' }} 
                               required>
                        <label for="role_acheteur" class="role-card">
                            <span class="role-icon">🎫</span>
                            <div class="role-title">Acheter des billets</div>
                            <div class="role-description">Découvrir et acheter des billets pour des événements</div>
                        </label>
                    </div>
                    
                    <div class="role-option">
                        <input type="radio" 
                               name="role" 
                               value="promoteur" 
                               id="role_promoteur" 
                               {{ old('role') == 'promoteur' ? 'checked' : '' }}>
                        <label for="role_promoteur" class="role-card">
                            <span class="role-icon">🎭</span>
                            <div class="role-title">Organiser des événements</div>
                            <div class="role-description">Créer et vendre des billets pour mes événements</div>
                        </label>
                    </div>
                </div>
            </div>

            <div class="auth-field ">
                <label for="password" class="form-label">Mot de passe</label>
                <input type="password" 
                       class="form-control @error('password') is-invalid @enderror" 
                       id="password" 
                       name="password"
                       required 
                       autocomplete="new-password"
                       minlength="8"
                       aria-describedby="password-help @error('password') password-error @enderror" @error('password') aria-invalid="true" @enderror>
                <div id="password-help" class="form-text">Au moins 8 caractères</div>
                <x-field-error name="password" />
            </div>

            <div class="auth-field ">
                <label for="password_confirmation" class="form-label">Confirmer le mot de passe</label>
                <input type="password" 
                       class="form-control" 
                       id="password_confirmation" 
                       name="password_confirmation"
                       required
                       aria-describedby="@error('password_confirmation') password_confirmation-error @enderror" @error('password_confirmation') aria-invalid="true" @enderror>
                <x-field-error name="password_confirmation" />
            </div>

            <!-- Exigences du mot de passe -->
            <div class="password-requirements">
                <div class="title">Exigences du mot de passe :</div>
                <div class="requirement">• Au moins 8 caractères</div>
                <div class="requirement">• Lettres et chiffres recommandés</div>
            </div>

            <button type="submit" class="btn btn-primary">
                <i class="fas fa-user-plus me-2"></i>Créer mon compte
            </button>
        </form>

        <!-- Pied de page -->
        <div class="auth-footer">
            <p class="mb-0">
                Déjà un compte ? 
                <a href="{{ route('login') }}">Se connecter</a>
            </p>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script @cspNonce>
        // Validation en temps réel du téléphone
        document.getElementById('phone').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length >= 2) {
                value = value.match(/.{1,2}/g).join(' ');
                if (value.length > 14) value = value.substr(0, 14);
                e.target.value = value;
            }
        });

        // Validation du mot de passe
        document.getElementById('password').addEventListener('input', function(e) {
            const requirements = document.querySelector('.password-requirements');
            if (e.target.value.length >= 8) {
                requirements.style.borderColor = '#16a34a';
                requirements.style.background = '#f0fdf4';
            } else {
                requirements.style.borderColor = '#0ea5e9';
                requirements.style.background = '#f0f9ff';
            }
        });
    </script>
</body>
</html>