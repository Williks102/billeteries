{{-- resources/views/tickets/verify.blade.php --}}
{{-- Page publique de vérification des billets --}}
{{-- =============================================== --}}
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Vérification Billet - ClicBillet CI</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .verify-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .verify-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            overflow: hidden;
            max-width: 500px;
            width: 100%;
        }
        .verify-header {
            background: linear-gradient(135deg, #1a237e, #FF6B35);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .verify-body {
            padding: 30px;
        }
        .status-valid {
            color: #28a745;
            background: #d4edda;
            border: 1px solid #c3e6cb;
            border-radius: 10px;
            padding: 20px;
            text-align: center;
            margin: 20px 0;
        }
        .status-invalid {
            color: #dc3545;
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            border-radius: 10px;
            padding: 20px;
            text-align: center;
            margin: 20px 0;
        }
        .status-used {
            color: #856404;
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 10px;
            padding: 20px;
            text-align: center;
            margin: 20px 0;
        }
        .ticket-info {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin: 20px 0;
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            padding-bottom: 10px;
            border-bottom: 1px solid #dee2e6;
        }
        .info-row:last-child {
            border-bottom: none;
            margin-bottom: 0;
        }
        .info-label {
            font-weight: 600;
            color: #495057;
        }
        .info-value {
            color: #212529;
        }
        .qr-code-display {
            text-align: center;
            margin: 20px 0;
        }
        .ticket-code-display {
            background: #1a237e;
            color: white;
            padding: 15px;
            border-radius: 10px;
            font-family: 'Courier New', monospace;
            font-size: 18px;
            font-weight: bold;
            letter-spacing: 2px;
            text-align: center;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <div class="verify-container">
        <div class="verify-card">
            <div class="verify-header">
                <h1 class="mb-2">
                    <i class="fas fa-ticket-alt me-2"></i>
                    Vérification Billet
                </h1>
                <p class="mb-0">ClicBillet CI</p>
            </div>
            
            <div class="verify-body">
                @if($error)
                    {{-- Erreur --}}
                    <div class="status-invalid">
                        <i class="fas fa-times-circle fa-3x mb-3"></i>
                        <h4>Billet Non Valide</h4>
                        <p class="mb-0">{{ $error }}</p>
                        @if(isset($ticket_code))
                            <div class="ticket-code-display mt-3">
                                {{ $ticket_code }}
                            </div>
                        @endif
                    </div>
                @elseif($ticket && isset($info))
                    {{-- Ticket trouvé --}}
                    <div class="ticket-code-display">
                        {{ $ticket->ticket_code }}
                    </div>
                    
                    @if($ticket->status === 'used')
                        {{-- Billet déjà utilisé --}}
                        <div class="status-used">
                            <i class="fas fa-exclamation-triangle fa-3x mb-3"></i>
                            <h4>Billet Déjà Utilisé</h4>
                            <p class="mb-2">Ce billet a déjà été scanné et utilisé.</p>
                            @if($ticket->used_at)
                                <small>Utilisé le : {{ $ticket->used_at->format('d/m/Y à H:i') }}</small>
                            @endif
                        </div>
                    @elseif($info['is_valid'])
                        {{-- Billet valide --}}
                        <div class="status-valid">
                            <i class="fas fa-check-circle fa-3x mb-3"></i>
                            <h4>Billet Valide</h4>
                            <p class="mb-0">Ce billet est authentique et valide pour l'entrée.</p>
                        </div>
                    @else
                        {{-- Billet invalide --}}
                        <div class="status-invalid">
                            <i class="fas fa-times-circle fa-3x mb-3"></i>
                            <h4>Billet Non Valide</h4>
                            <p class="mb-0">Ce billet ne peut pas être utilisé.</p>
                        </div>
                    @endif
                    
                    {{-- Informations du billet --}}
                    <div class="ticket-info">
                        <h5 class="mb-3">
                            <i class="fas fa-info-circle me-2"></i>
                            Informations du Billet
                        </h5>
                        
                        <div class="info-row">
                            <span class="info-label">Événement :</span>
                            <span class="info-value">{{ $info['event']['title'] }}</span>
                        </div>
                        
                        <div class="info-row">
                            <span class="info-label">Date :</span>
                            <span class="info-value">{{ $info['event']['date'] }}</span>
                        </div>
                        
                        <div class="info-row">
                            <span class="info-label">Heure :</span>
                            <span class="info-value">{{ $info['event']['time'] }}</span>
                        </div>
                        
                        <div class="info-row">
                            <span class="info-label">Lieu :</span>
                            <span class="info-value">{{ $info['event']['venue'] }}</span>
                        </div>
                        
                        <div class="info-row">
                            <span class="info-label">Type :</span>
                            <span class="info-value">{{ $info['ticket_type'] }}</span>
                        </div>
                        
                        <div class="info-row">
                            <span class="info-label">Porteur :</span>
                            <span class="info-value">{{ $info['holder']['name'] }}</span>
                        </div>
                        
                        @if($info['order'])
                            <div class="info-row">
                                <span class="info-label">Commande :</span>
                                <span class="info-value">#{{ $info['order']['number'] }}</span>
                            </div>
                        @endif
                    </div>
                @else
                    {{-- État inconnu --}}
                    <div class="status-invalid">
                        <i class="fas fa-question-circle fa-3x mb-3"></i>
                        <h4>État Inconnu</h4>
                        <p class="mb-0">Impossible de déterminer l'état de ce billet.</p>
                    </div>
                @endif
                
                {{-- Actions --}}
                <div class="text-center mt-4">
                    <a href="{{ route('home') }}" class="btn btn-primary">
                        <i class="fas fa-home me-2"></i>
                        Retour à l'accueil
                    </a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>