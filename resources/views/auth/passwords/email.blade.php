{{-- ============================================ --}}
{{-- resources/views/auth/passwords/email.blade.php (amélioré) --}}
{{-- ============================================ --}}

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mot de passe oublié - ClicBillet CI</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <style>
        body { 
            background: linear-gradient(135deg, #ffffffff 0%, #ffffffff 100%);
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
        .form-control { border-radius: 10px; padding: 12px 15px; }
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
        .back-link { text-align: center; margin-top: 20px; }
        .back-link a { color: #666; text-decoration: none; }
        .back-link a:hover { color: #FF6B35; }
    </style>
</head>
<body>
    <div class="container">
        <div class="auth-container">
            <div class="auth-header">
                <i class="fas fa-key fa-3x mb-3"></i>
                <h2>Mot de passe oublié ?</h2>
                <p class="mb-0">Pas de problème ! Entrez votre email pour recevoir un lien de réinitialisation.</p>
            </div>
            
            <div class="auth-body">
                @if (session('status'))
                    <div class="alert alert-success" role="alert">
                        <i class="fas fa-check-circle me-2"></i>
                        {{ session('status') }}
                    </div>
                @endif

                <form method="POST" action="{{ route('password.email') }}">
                    @csrf

                    <div class="mb-3">
                        <label for="email" class="form-label">
                            <i class="fas fa-envelope me-2"></i>Adresse email
                        </label>
                        <input id="email" 
                               type="email" 
                               class="form-control @error('email') is-invalid @enderror" 
                               name="email" 
                               value="{{ old('email') }}" 
                               required 
                               autocomplete="email" 
                               autofocus
                               placeholder="votre-email@exemple.com">
                        
                        @error('email')
                            <div class="invalid-feedback">
                                <strong>{{ $message }}</strong>
                            </div>
                        @enderror
                    </div>

                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-paper-plane me-2"></i>Envoyer le lien de réinitialisation
                    </button>
                </form>

                <div class="back-link">
                    <a href="{{ route('login') }}">
                        <i class="fas fa-arrow-left me-1"></i>Retour à la connexion
                    </a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
