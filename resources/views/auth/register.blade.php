<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Inscription - ClicBillet CI</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-orange: #FF6B35;
            --secondary-orange: #ff8c61;
            --dark-blue: #1a237e;
            --light-gray: #f8f9fa;
        }
        
        body {
            background: linear-gradient(135deg, var(--dark-blue) 0%, var(--primary-orange) 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .register-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .register-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            overflow: hidden;
            max-width: 1000px;
            width: 100%;
        }
        
        .register-left {
            background: linear-gradient(135deg, var(--primary-orange) 0%, var(--secondary-orange) 100%);
            color: white;
            padding: 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }
        
        .register-left::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -20%;
            width: 200px;
            height: 200px;
            background: rgba(255,255,255,0.1);
            border-radius: 50%;
        }
        
        .brand-logo {
            font-size: 2.5rem;
            font-weight: bold;
            margin-bottom: 1rem;
            position: relative;
            z-index: 2;
        }
        
        .brand-tagline {
            font-size: 1.1rem;
            opacity: 0.9;
            margin-bottom: 2rem;
            position: relative;
            z-index: 2;
        }
        
        .benefits-list {
            list-style: none;
            padding: 0;
            position: relative;
            z-index: 2;
        }
        
        .benefits-list li {
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
        }
        
        .benefits-list i {
            margin-right: 15px;
            width: 20px;
        }
        
        .register-right {
            padding: 40px;
            max-height: 100vh;
            overflow-y: auto;
        }
        
        .form-title {
            text-align: center;
            margin-bottom: 2rem;
            color: var(--dark-blue);
        }
        
        .form-title h2 {
            font-size: 2rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }
        
        .form-floating {
            margin-bottom: 1.5rem;
        }
        
        .form-floating .form-control,
        .form-floating .form-select {
            border: 2px solid #e9ecef;
            border-radius: 12px;
            padding: 1rem 0.75rem;
            height: auto;
            transition: all 0.3s ease;
        }
        
        .form-floating .form-control:focus,
        .form-floating .form-select:focus {
            border-color: var(--primary-orange);
            box-shadow: 0 0 0 0.2rem rgba(255, 107, 53, 0.25);
        }
        
        .role-selection {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }
        
        .role-option {
            border: 2px solid #e9ecef;
            border-radius: 12px;
            padding: 1rem;
            margin-bottom: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            background: white;
        }
        
        .role-option:hover {
            border-color: var(--primary-orange);
            transform: translateY(-2px);
        }
        
        .role-option.selected {
            border-color: var(--primary-orange);
            background: rgba(255, 107, 53, 0.05);
        }
        
        .role-option:last-child {
            margin-bottom: 0;
        }
        
        .role-header {
            display: flex;
            align-items: center;
            margin-bottom: 0.5rem;
        }
        
        .role-icon {
            font-size: 1.2rem;
            margin-right: 10px;
            width: 25px;
        }
        
        .role-title {
            font-weight: 600;
            margin: 0;
        }
        
        .role-description {
            font-size: 0.9rem;
            color: #666;
            margin: 0;
            margin-left: 35px;
        }
        
        .phone-input-group {
            position: relative;
        }
        
        .phone-prefix {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #666;
            z-index: 10;
            font-weight: 500;
        }
        
        .phone-input {
            padding-left: 60px !important;
        }
        
        .btn-register {
            background: linear-gradient(135deg, var(--primary-orange) 0%, var(--secondary-orange) 100%);
            border: none;
            border-radius: 12px;
            padding: 14px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            transition: all 0.3s ease;
            width: 100%;
            margin-bottom: 1.5rem;
        }
        
        .btn-register:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(255, 107, 53, 0.3);
        }
        
        .login-link {
            text-align: center;
            margin-top: 1.5rem;
        }
        
        .login-link a {
            color: var(--primary-orange);
            text-decoration: none;
            font-weight: 500;
        }
        
        .login-link a:hover {
            text-decoration: underline;
        }
        
        .password-requirements {
            background: #fff3cd;
            border-radius: 8px;
            padding: 0.75rem;
            margin-bottom: 1rem;
            border-left: 4px solid #ffc107;
        }
        
        .password-requirements .title {
            font-size: 0.85rem;
            font-weight: 600;
            color: #856404;
            margin-bottom: 0.5rem;
        }
        
        .password-requirements .requirement {
            font-size: 0.75rem;
            color: #856404;
            margin-bottom: 0.2rem;
        }
        
        .password-requirements .requirement:last-child {
            margin-bottom: 0;
        }
        
        @media (max-width: 768px) {
            .register-left {
                padding: 30px 20px;
                text-align: center;
            }
            
            .register-right {
                padding: 30px 20px;
            }
            
            .brand-logo {
                font-size: 2rem;
            }
            
            .role-option {
                padding: 0.75rem;
            }
        }
    </style>
</head>
<body>
    <div class="register-container">
        <div class="register-card">
            <div class="row g-0">
                <!-- Partie gauche - Branding -->
                <div class="col-lg-5">
                    <div class="register-left">
                        <div>
                            <div class="brand-logo">
                                <i class="fas fa-ticket-alt me-3"></i>ClicBillet
                            </div>
                            <div class="brand-tagline">
                                Rejoignez la communauté ClicBillet Côte d'Ivoire
                            </div>
                            <ul class="benefits-list">
                                <li>
                                    <i class="fas fa-user-friends"></i>
                                    <span>Accès à tous les événements</span>
                                </li>
                                <li>
                                    <i class="fas fa-credit-card"></i>
                                    <span>Paiement mobile sécurisé</span>
                                </li>
                                <li>
                                    <i class="fas fa-history"></i>
                                    <span>Historique de vos achats</span>
                                </li>
                                <li>
                                    <i class="fas fa-bell"></i>
                                    <span>Notifications événements</span>
                                </li>
                                <li>
                                    <i class="fas fa-chart-line"></i>
                                    <span>Promoteurs: Gestion complète</span>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
                
                <!-- Partie droite - Formulaire -->
                <div class="col-lg-7">
                    <div class="register-right">
                        <div class="form-title">
                            <h2>Créer un compte</h2>
                            <p>Choisissez votre type de compte</p>
                        </div>
                        
                        <!-- Sélection du rôle -->
                        <div class="role-selection">
                            <h6 class="mb-3"><i class="fas fa-user-tag me-2"></i>Type de compte</h6>
                            
                            <div class="role-option" onclick="selectRole('acheteur')">
                                <div class="role-header">
                                    <i class="fas fa-shopping-cart role-icon text-success"></i>
                                    <h6 class="role-title">Acheteur</h6>
                                </div>
                                <p class="role-description">
                                    Achetez des billets pour concerts, théâtre, sports et événements
                                </p>
                            </div>
                            
                            <div class="role-option" onclick="selectRole('promoteur')">
                                <div class="role-header">
                                    <i class="fas fa-bullhorn role-icon text-warning"></i>
                                    <h6 class="role-title">Promoteur</h6>
                                </div>
                                <p class="role-description">
                                    Organisez et vendez des billets pour vos événements
                                </p>
                            </div>
                        </div>
                        
                        <form method="POST" action="{{ route('register') }}" id="registerForm">
                            @csrf
                            
                            <input type="hidden" name="role" id="selectedRole" value="acheteur">
                            
                            <div class="form-floating">
                                <input type="text" 
                                       class="form-control @error('name') is-invalid @enderror" 
                                       id="name" 
                                       name="name" 
                                       placeholder="Nom complet"
                                       value="{{ old('name') }}" 
                                       required>
                                <label for="name">Nom complet</label>
                                @error('name')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>
                            
                            <div class="form-floating">
                                <input type="email" 
                                       class="form-control @error('email') is-invalid @enderror" 
                                       id="email" 
                                       name="email" 
                                       placeholder="nom@exemple.com"
                                       value="{{ old('email') }}" 
                                       required>
                                <label for="email">Adresse email</label>
                                @error('email')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>
                            
                            <div class="form-floating phone-input-group">
                                <div class="phone-prefix">+225</div>
                                <input type="tel" 
                                       class="form-control phone-input @error('phone') is-invalid @enderror" 
                                       id="phone" 
                                       name="phone" 
                                       placeholder="07 12 34 56 78"
                                       value="{{ old('phone') }}" 
                                       pattern="[0-9 ]{11,14}"
                                       required>
                                <label for="phone">Numéro de téléphone</label>
                                @error('phone')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>
                            
                            <div class="form-floating">
                                <input type="password" 
                                       class="form-control @error('password') is-invalid @enderror" 
                                       id="password" 
                                       name="password" 
                                       placeholder="Mot de passe"
                                       minlength="8"
                                       required>
                                <label for="password">Mot de passe</label>
                                @error('password')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>
                            
                            <div class="password-requirements">
                                <div class="title">🔒 Exigences du mot de passe :</div>
                                <div class="requirement">• Au moins 8 caractères</div>
                                <div class="requirement">• Combinez lettres et chiffres</div>
                                <div class="requirement">• Évitez les mots de passe simples</div>
                            </div>
                            
                            <div class="form-floating">
                                <input type="password" 
                                       class="form-control" 
                                       id="password_confirmation" 
                                       name="password_confirmation" 
                                       placeholder="Confirmer le mot de passe"
                                       minlength="8"
                                       required>
                                <label for="password_confirmation">Confirmer le mot de passe</label>
                            </div>
                            
                            <div class="form-check mb-3">
                                <input type="checkbox" 
                                       class="form-check-input" 
                                       id="terms" 
                                       required>
                                <label class="form-check-label" for="terms">
                                    J'accepte les <a href="#" class="text-decoration-none">conditions d'utilisation</a> 
                                    et la <a href="#" class="text-decoration-none">politique de confidentialité</a>
                                </label>
                            </div>
                            
                            <button type="submit" class="btn btn-primary btn-register">
                                <i class="fas fa-user-plus me-2"></i>Créer mon compte
                            </button>
                        </form>
                        
                        <div class="login-link">
                            <p>Déjà un compte ? <a href="{{ route('login') }}">Se connecter</a></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let selectedRoleType = 'acheteur';
        
        function selectRole(role) {
            // Retirer la sélection précédente
            document.querySelectorAll('.role-option').forEach(option => {
                option.classList.remove('selected');
            });
            
            // Ajouter la sélection à la nouvelle option
            event.currentTarget.classList.add('selected');
            
            // Mettre à jour le rôle sélectionné
            selectedRoleType = role;
            document.getElementById('selectedRole').value = role;
        }
        
        // Formatage automatique du numéro de téléphone
        document.getElementById('phone').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, ''); // Supprimer tout sauf les chiffres
            
            // Formater avec des espaces
            if (value.length > 0) {
                value = value.match(/.{1,2}/g)?.join(' ') || value;
                if (value.length > 14) value = value.substring(0, 14);
            }
            
            e.target.value = value;
        });
        
        // Validation du mot de passe en temps réel
        document.getElementById('password_confirmation').addEventListener('input', function(e) {
            const password = document.getElementById('password').value;
            const confirmation = e.target.value;
            
            if (password !== confirmation && confirmation.length > 0) {
                e.target.setCustomValidity('Les mots de passe ne correspondent pas');
                e.target.classList.add('is-invalid');
            } else {
                e.target.setCustomValidity('');
                e.target.classList.remove('is-invalid');
            }
        });
        
        // Validation du formulaire avant soumission
        document.getElementById('registerForm').addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const confirmation = document.getElementById('password_confirmation').value;
            const terms = document.getElementById('terms').checked;
            
            if (password !== confirmation) {
                e.preventDefault();
                alert('Les mots de passe ne correspondent pas');
                return false;
            }
            
            if (!terms) {
                e.preventDefault();
                alert('Vous devez accepter les conditions d\'utilisation');
                return false;
            }
            
            // Animation du bouton
            const btn = document.querySelector('.btn-register');
            btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Création en cours...';
            btn.disabled = true;
        });
        
        // Sélectionner le rôle acheteur par défaut
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelector('.role-option').classList.add('selected');
            
            // Animation d'entrée
            const card = document.querySelector('.register-card');
            card.style.opacity = '0';
            card.style.transform = 'translateY(30px)';
            
            setTimeout(() => {
                card.style.transition = 'all 0.8s ease';
                card.style.opacity = '1';
                card.style.transform = 'translateY(0)';
            }, 100);
        });
    </script>
</body>
</html>