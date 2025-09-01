
{{-- ============================================ --}}
{{-- resources/views/auth/passwords/reset.blade.php (amélioré) --}}
{{-- ============================================ --}}

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nouveau mot de passe - ClicBillet CI</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <style>
        body { 
            background: linear-gradient(135deg, #f0f0f0ff 0%, #fafafaff 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
        }
        .auth-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.15);
            overflow: hidden;
            max-width: 500px;
            margin: auto;
        }
        .auth-header {
            background: linear-gradient(45deg, #FF6B35, #F7931E);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .auth-body { padding: 30px; }
        .form-control { 
            border-radius: 10px; 
            padding: 12px 15px;
            border: 2px solid #e9ecef;
        }
        .form-control:focus {
            border-color: #FF6B35;
            box-shadow: 0 0 0 0.2rem rgba(255, 107, 53, 0.25);
        }
        .btn-primary {
            background: linear-gradient(45deg, #FF6B35, #F7931E);
            border: none;
            border-radius: 10px;
            padding: 12px 30px;
            width: 100%;
            font-weight: 600;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255, 107, 53, 0.4);
        }
        .password-requirements {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
            font-size: 14px;
        }
        .password-requirements .title {
            font-weight: bold;
            margin-bottom: 8px;
            color: #495057;
        }
        .requirement {
            color: #6c757d;
            margin-bottom: 4px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="auth-container">
            <div class="auth-header">
                <i class="fas fa-lock fa-3x mb-3"></i>
                <h2>Nouveau mot de passe</h2>
                <p class="mb-0">Choisissez un mot de passe sécurisé pour votre compte.</p>
            </div>
            
            <div class="auth-body">
                <form method="POST" action="{{ route('password.update') }}">
                    @csrf

                    <input type="hidden" name="token" value="{{ $token }}">

                    <div class="mb-3">
                        <label for="email" class="form-label">
                            <i class="fas fa-envelope me-2"></i>Adresse email
                        </label>
                        <input id="email" 
                               type="email" 
                               class="form-control @error('email') is-invalid @enderror" 
                               name="email" 
                               value="{{ $email ?? old('email') }}" 
                               required 
                               autocomplete="email" 
                               readonly>

                        @error('email')
                            <div class="invalid-feedback">
                                <strong>{{ $message }}</strong>
                            </div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label">
                            <i class="fas fa-key me-2"></i>Nouveau mot de passe
                        </label>
                        <input id="password" 
                               type="password" 
                               class="form-control @error('password') is-invalid @enderror" 
                               name="password" 
                               required 
                               autocomplete="new-password"
                               placeholder="Minimum 8 caractères">

                        @error('password')
                            <div class="invalid-feedback">
                                <strong>{{ $message }}</strong>
                            </div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="password-confirm" class="form-label">
                            <i class="fas fa-check-double me-2"></i>Confirmer le mot de passe
                        </label>
                        <input id="password-confirm" 
                               type="password" 
                               class="form-control" 
                               name="password_confirmation" 
                               required 
                               autocomplete="new-password"
                               placeholder="Tapez à nouveau le mot de passe">
                    </div>

                    <!-- Exigences du mot de passe -->
                    <div class="password-requirements">
                        <div class="title">
                            <i class="fas fa-info-circle me-2"></i>Exigences du mot de passe :
                        </div>
                        <div class="requirement">• Au moins 8 caractères</div>
                        <div class="requirement">• Lettres et chiffres recommandés</div>
                        <div class="requirement">• Évitez les mots de passe trop simples</div>
                    </div>

                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>Confirmer le nouveau mot de passe
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Validation en temps réel
        document.addEventListener('DOMContentLoaded', function() {
            const password = document.getElementById('password');
            const passwordConfirm = document.getElementById('password-confirm');
            
            function validatePasswords() {
                if (passwordConfirm.value && password.value !== passwordConfirm.value) {
                    passwordConfirm.setCustomValidity('Les mots de passe ne correspondent pas');
                } else {
                    passwordConfirm.setCustomValidity('');
                }
            }
            
            password.addEventListener('input', validatePasswords);
            passwordConfirm.addEventListener('input', validatePasswords);
        });
    </script>
</body>
</html>