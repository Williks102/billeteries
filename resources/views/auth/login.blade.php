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
            --secondary-orange: #ff8c61;
            --dark-blue: #1a237e;
            --light-gray: #f8f9fa;
        }
        
        body {
            background: linear-gradient(135deg, var(--dark-blue) 0%, var(--primary-orange) 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .login-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .login-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            overflow: hidden;
            max-width: 1200px;
            width: 100%;
        }
        
        .login-left {
            background: linear-gradient(135deg, var(--primary-orange) 0%, var(--secondary-orange) 100%);
            color: white;
            padding: 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }
        
        .login-left::before {
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
        
        .feature-list {
            list-style: none;
            padding: 0;
            position: relative;
            z-index: 2;
        }
        
        .feature-list li {
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
        }
        
        .feature-list i {
            margin-right: 15px;
            width: 20px;
        }
        
        .login-right {
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
        
        .form-floating .form-control {
            border: 2px solid #e9ecef;
            border-radius: 12px;
            padding: 1rem 0.75rem;
            height: auto;
            transition: all 0.3s ease;
        }
        
        .form-floating .form-control:focus {
            border-color: var(--primary-orange);
            box-shadow: 0 0 0 0.2rem rgba(255, 107, 53, 0.25);
        }
        
        .btn-login {
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
        
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(255, 107, 53, 0.3);
        }
        
        .divider {
            text-align: center;
            margin: 1.5rem 0;
            position: relative;
        }
        
        .divider::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 0;
            right: 0;
            height: 1px;
            background: #e9ecef;
        }
        
        .divider span {
            background: white;
            padding: 0 1rem;
            color: #666;
            font-size: 0.9rem;
        }
        
        .test-accounts {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            max-height: 400px;
            overflow-y: auto;
        }
        
        .test-accounts h6 {
            color: var(--dark-blue);
            margin-bottom: 1rem;
            font-weight: 600;
            position: sticky;
            top: 0;
            background: #f8f9fa;
            padding-bottom: 0.5rem;
        }
        
        .account-section {
            margin-bottom: 1.5rem;
        }
        
        .account-section h7 {
            font-size: 0.9rem;
            font-weight: 600;
            color: var(--dark-blue);
            margin-bottom: 0.5rem;
            display: block;
        }
        
        .test-account {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.75rem;
            background: white;
            border-radius: 8px;
            margin-bottom: 0.5rem;
            border: 1px solid #e9ecef;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        
        .test-account:hover {
            border-color: var(--primary-orange);
            transform: translateX(5px);
        }
        
        .account-info {
            flex: 1;
        }
        
        .account-name {
            font-weight: 500;
            font-size: 0.9rem;
            margin-bottom: 2px;
        }
        
        .account-email {
            font-size: 0.8rem;
            color: #666;
            margin: 0;
        }
        
        .account-phone {
            font-size: 0.75rem;
            color: #999;
            margin: 0;
        }
        
        .account-role {
            font-size: 0.75rem;
            padding: 4px 10px;
            border-radius: 20px;
            font-weight: 500;
            text-align: center;
            min-width: 80px;
        }
        
        .role-admin {
            background: #dc3545;
            color: white;
        }
        
        .role-promoteur {
            background: #ffc107;
            color: #000;
        }
        
        .role-acheteur {
            background: #28a745;
            color: white;
        }
        
        .password-hint {
            font-size: 0.7rem;
            color: #666;
            margin-top: 2px;
        }
        
        .register-link {
            text-align: center;
            margin-top: 1.5rem;
        }
        
        .register-link a {
            color: var(--primary-orange);
            text-decoration: none;
            font-weight: 500;
        }
        
        .register-link a:hover {
            text-decoration: underline;
        }
        
        @media (max-width: 768px) {
            .login-left {
                padding: 30px 20px;
                text-align: center;
            }
            
            .login-right {
                padding: 30px 20px;
            }
            
            .brand-logo {
                font-size: 2rem;
            }
            
            .test-accounts {
                max-height: 300px;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="row g-0">
                <!-- Partie gauche - Branding -->
                <div class="col-lg-5">
                    <div class="login-left">
                        <div>
                            <div class="brand-logo">
                                <i class="fas fa-ticket-alt me-3"></i>ClicBillet
                            </div>
                            <div class="brand-tagline">
                                Plateforme de billetterie #1 en Côte d'Ivoire
                            </div>
                            <ul class="feature-list">
                                <li>
                                    <i class="fas fa-calendar-check"></i>
                                    <span>Concerts, Théâtre, Sports</span>
                                </li>
                                <li>
                                    <i class="fas fa-qrcode"></i>
                                    <span>Billets QR sécurisés</span>
                                </li>
                                <li>
                                    <i class="fas fa-mobile-alt"></i>
                                    <span>Scanner mobile intégré</span>
                                </li>
                                <li>
                                    <i class="fas fa-map-marker-alt"></i>
                                    <span>Événements à Abidjan</span>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
                
                <!-- Partie droite - Formulaire -->
                <div class="col-lg-7">
                    <div class="login-right">
                        <div class="form-title">
                            <h2>Connexion</h2>
                            <p>Accédez à votre compte ClicBillet</p>
                        </div>
                        
                        <form method="POST" action="{{ route('login') }}">
                            @csrf
                            
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
                            
                            <div class="form-floating">
                                <input type="password" 
                                       class="form-control @error('password') is-invalid @enderror" 
                                       id="password" 
                                       name="password" 
                                       placeholder="Mot de passe"
                                       required>
                                <label for="password">Mot de passe</label>
                                @error('password')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>
                            
                            <div class="form-check mb-3">
                                <input type="checkbox" 
                                       class="form-check-input" 
                                       id="remember" 
                                       name="remember" 
                                       {{ old('remember') ? 'checked' : '' }}>
                                <label class="form-check-label" for="remember">
                                    Se souvenir de moi
                                </label>
                            </div>
                            
                            <button type="submit" class="btn btn-primary btn-login">
                                <i class="fas fa-sign-in-alt me-2"></i>Se connecter
                            </button>
                        </form>
                        
                        <div class="divider">
                            <span>💡 Comptes de démonstration</span>
                        </div>
                        
                        <div class="test-accounts">
                            <h6><i class="fas fa-users me-2"></i>Cliquez pour tester avec ces comptes</h6>
                            
                            <!-- ADMIN -->
                            <div class="account-section">
                                <h7>👨‍💼 Administrateur</h7>
                                <div class="test-account" onclick="fillCredentials('admin@billetterie-ci.com', 'admin123')">
                                    <div class="account-info">
                                        <div class="account-name">Admin Principal</div>
                                        <div class="account-email">admin@billetterie-ci.com</div>
                                        <div class="account-phone">+225 01 02 03 04 05</div>
                                        <div class="password-hint">📋 Mot de passe: admin123</div>
                                    </div>
                                    <span class="account-role role-admin">Admin</span>
                                </div>
                            </div>
                            
                            <!-- PROMOTEURS -->
                            <div class="account-section">
                                <h7>🎭 Promoteurs d'événements</h7>
                                
                                <div class="test-account" onclick="fillCredentials('kouadio@productions.ci', 'password123')">
                                    <div class="account-info">
                                        <div class="account-name">Kouadio Productions</div>
                                        <div class="account-email">kouadio@productions.ci</div>
                                        <div class="account-phone">+225 07 12 34 56 78</div>
                                        <div class="password-hint">📋 Mot de passe: password123</div>
                                    </div>
                                    <span class="account-role role-promoteur">Promoteur</span>
                                </div>
                                
                                <div class="test-account" onclick="fillCredentials('contact@abidjan-events.ci', 'password123')">
                                    <div class="account-info">
                                        <div class="account-name">Abidjan Events</div>
                                        <div class="account-email">contact@abidjan-events.ci</div>
                                        <div class="account-phone">+225 05 67 89 01 23</div>
                                        <div class="password-hint">📋 Mot de passe: password123</div>
                                    </div>
                                    <span class="account-role role-promoteur">Promoteur</span>
                                </div>
                                
                                <div class="test-account" onclick="fillCredentials('info@culture-spectacles.ci', 'password123')">
                                    <div class="account-info">
                                        <div class="account-name">Culture & Spectacles CI</div>
                                        <div class="account-email">info@culture-spectacles.ci</div>
                                        <div class="account-phone">+225 01 45 67 89 01</div>
                                        <div class="password-hint">📋 Mot de passe: password123</div>
                                    </div>
                                    <span class="account-role role-promoteur">Promoteur</span>
                                </div>
                                
                                <div class="test-account" onclick="fillCredentials('sports@abidjan.ci', 'password123')">
                                    <div class="account-info">
                                        <div class="account-name">Sports Events Abidjan</div>
                                        <div class="account-email">sports@abidjan.ci</div>
                                        <div class="account-phone">+225 07 98 76 54 32</div>
                                        <div class="password-hint">📋 Mot de passe: password123</div>
                                    </div>
                                    <span class="account-role role-promoteur">Promoteur</span>
                                </div>
                                
                                <div class="test-account" onclick="fillCredentials('promoteur@test.com', 'password')">
                                    <div class="account-info">
                                        <div class="account-name">Test Promoteur</div>
                                        <div class="account-email">promoteur@test.com</div>
                                        <div class="account-phone">+225 01 23 45 67 89</div>
                                        <div class="password-hint">📋 Mot de passe: password</div>
                                    </div>
                                    <span class="account-role role-promoteur">Promoteur</span>
                                </div>
                            </div>
                            
                            <!-- ACHETEURS -->
                            <div class="account-section">
                                <h7>🎫 Acheteurs</h7>
                                
                                <div class="test-account" onclick="fillCredentials('aminata@gmail.com', 'password123')">
                                    <div class="account-info">
                                        <div class="account-name">Aminata Traoré</div>
                                        <div class="account-email">aminata@gmail.com</div>
                                        <div class="account-phone">+225 05 11 22 33 44</div>
                                        <div class="password-hint">📋 Mot de passe: password123</div>
                                    </div>
                                    <span class="account-role role-acheteur">Acheteur</span>
                                </div>
                                
                                <div class="test-account" onclick="fillCredentials('kofi@yahoo.fr', 'password123')">
                                    <div class="account-info">
                                        <div class="account-name">Kofi Asante</div>
                                        <div class="account-email">kofi@yahoo.fr</div>
                                        <div class="account-phone">+225 07 55 66 77 88</div>
                                        <div class="password-hint">📋 Mot de passe: password123</div>
                                    </div>
                                    <span class="account-role role-acheteur">Acheteur</span>
                                </div>
                                
                                <div class="test-account" onclick="fillCredentials('marie.brou@outlook.com', 'password123')">
                                    <div class="account-info">
                                        <div class="account-name">Marie-Claire Brou</div>
                                        <div class="account-email">marie.brou@outlook.com</div>
                                        <div class="account-phone">+225 01 99 88 77 66</div>
                                        <div class="password-hint">📋 Mot de passe: password123</div>
                                    </div>
                                    <span class="account-role role-acheteur">Acheteur</span>
                                </div>
                                
                                <div class="test-account" onclick="fillCredentials('ibrahim.sanogo@gmail.com', 'password123')">
                                    <div class="account-info">
                                        <div class="account-name">Ibrahim Sanogo</div>
                                        <div class="account-email">ibrahim.sanogo@gmail.com</div>
                                        <div class="account-phone">+225 05 44 33 22 11</div>
                                        <div class="password-hint">📋 Mot de passe: password123</div>
                                    </div>
                                    <span class="account-role role-acheteur">Acheteur</span>
                                </div>
                                
                                <div class="test-account" onclick="fillCredentials('fatoumata@hotmail.fr', 'password123')">
                                    <div class="account-info">
                                        <div class="account-name">Fatoumata Keita</div>
                                        <div class="account-email">fatoumata@hotmail.fr</div>
                                        <div class="account-phone">+225 07 77 88 99 00</div>
                                        <div class="password-hint">📋 Mot de passe: password123</div>
                                    </div>
                                    <span class="account-role role-acheteur">Acheteur</span>
                                </div>
                                
                                <div class="test-account" onclick="fillCredentials('acheteur@test.com', 'password')">
                                    <div class="account-info">
                                        <div class="account-name">Test Acheteur</div>
                                        <div class="account-email">acheteur@test.com</div>
                                        <div class="account-phone">+225 07 89 01 23 45</div>
                                        <div class="password-hint">📋 Mot de passe: password</div>
                                    </div>
                                    <span class="account-role role-acheteur">Acheteur</span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="register-link">
                            <p>Nouveau sur ClicBillet ? <a href="{{ route('register') }}">Créer un compte</a></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function fillCredentials(email, password) {
            const emailInput = document.getElementById('email');
            const passwordInput = document.getElementById('password');
            
            // Animation de remplissage
            emailInput.style.background = 'linear-gradient(90deg, #e3f2fd 0%, #fff 100%)';
            passwordInput.style.background = 'linear-gradient(90deg, #e3f2fd 0%, #fff 100%)';
            
            emailInput.value = email;
            passwordInput.value = password;
            
            // Reset background after animation
            setTimeout(() => {
                emailInput.style.background = '';
                passwordInput.style.background = '';
            }, 1000);
            
            // Focus sur le bouton de connexion
            document.querySelector('.btn-login').focus();
        }
        
        // Animation d'entrée
        document.addEventListener('DOMContentLoaded', function() {
            const card = document.querySelector('.login-card');
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