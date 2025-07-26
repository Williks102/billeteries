<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Billet {{ $ticket->ticket_code }} - Billetterie CI</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            --primary-orange: #FF6B35;
            --secondary-orange: #ff8c61;
        }
        
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .ticket-container {
            max-width: 900px;
            margin: 50px auto;
            background: white;
            border-radius: 25px;
            overflow: hidden;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            animation: slideUp 0.8s ease-out;
        }
        
        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(50px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        /* En-tête ticket */
        .ticket-header {
            background: linear-gradient(135deg, var(--primary-orange), var(--secondary-orange));
            color: white;
            padding: 40px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        
        .ticket-header::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 100%;
            height: 200%;
            background: rgba(255,255,255,0.1);
            transform: rotate(45deg);
            animation: shimmer 3s infinite;
        }
        
        @keyframes shimmer {
            0%, 100% { transform: rotate(45deg) translateX(-100%); }
            50% { transform: rotate(45deg) translateX(100%); }
        }
        
        .event-title {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 10px;
            position: relative;
            z-index: 2;
        }
        
        .event-subtitle {
            font-size: 1.2rem;
            opacity: 0.9;
            position: relative;
            z-index: 2;
        }
        
        /* Corps du ticket */
        .ticket-body {
            padding: 40px;
        }
        
        .ticket-main {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 40px;
            align-items: start;
        }
        
        /* Section QR optimisée */
        .qr-main-section {
            text-align: center;
            padding: 30px;
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            border-radius: 20px;
            border: 3px solid var(--primary-orange);
            position: relative;
        }
        
        .qr-title {
            color: var(--primary-orange);
            font-weight: 700;
            font-size: 1.3rem;
            margin-bottom: 20px;
        }
        
        .qr-code-large {
            width: 250px;
            height: 250px;
            margin: 0 auto 20px;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            transition: transform 0.3s ease;
            cursor: pointer;
        }
        
        .qr-code-large:hover {
            transform: scale(1.05);
        }
        
        .qr-image-large {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .qr-fallback {
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-direction: column;
            border: 3px dashed var(--primary-orange);
            border-radius: 15px;
            color: var(--primary-orange);
        }
        
        .qr-fallback i {
            font-size: 4rem;
            margin-bottom: 15px;
        }
        
        .ticket-code-large {
            background: var(--primary-orange);
            color: white;
            padding: 15px 25px;
            border-radius: 15px;
            font-family: 'Courier New', monospace;
            font-weight: 700;
            font-size: 1.5rem;
            letter-spacing: 3px;
            margin-bottom: 20px;
            display: inline-block;
            box-shadow: 0 5px 15px rgba(255, 107, 53, 0.3);
        }
        
        .scan-instructions {
            background: rgba(var(--primary-orange), 0.1);
            border: 1px solid rgba(var(--primary-orange), 0.3);
            border-radius: 10px;
            padding: 15px;
            margin-top: 20px;
        }
        
        .scan-steps {
            display: flex;
            justify-content: space-around;
            margin-top: 15px;
        }
        
        .scan-step {
            text-align: center;
            flex: 1;
        }
        
        .scan-step i {
            font-size: 2rem;
            margin-bottom: 10px;
            display: block;
        }
        
        /* Informations du ticket */
        .ticket-info-section {
            padding: 20px;
        }
        
        .info-card {
            background: #f8f9fa;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
            border-left: 5px solid var(--primary-orange);
        }
        
        .info-title {
            color: var(--primary-orange);
            font-weight: 600;
            font-size: 1.1rem;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
        }
        
        .info-title i {
            margin-right: 10px;
        }
        
        .info-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid #e9ecef;
        }
        
        .info-item:last-child {
            border-bottom: none;
        }
        
        .info-label {
            font-weight: 500;
            color: #6c757d;
        }
        
        .info-value {
            font-weight: 600;
            color: #333;
            text-align: right;
        }
        
        .status-badge-large {
            padding: 12px 25px;
            border-radius: 25px;
            font-weight: 600;
            font-size: 1.1rem;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .status-valid { 
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
        }
        .status-used { 
            background: linear-gradient(135deg, #6c757d, #495057);
            color: white;
        }
        .status-cancelled { 
            background: linear-gradient(135deg, #dc3545, #c82333);
            color: white;
        }
        
        /* Actions */
        .ticket-actions {
            display: flex;
            gap: 15px;
            margin-top: 30px;
            flex-wrap: wrap;
        }
        
        .btn-action {
            flex: 1;
            min-width: 200px;
            padding: 15px 20px;
            border-radius: 25px;
            font-weight: 600;
            border: none;
            transition: all 0.3s ease;
            text-decoration: none;
            text-align: center;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        
        .btn-download {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
        }
        
        .btn-download:hover {
            color: white;
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(40, 167, 69, 0.3);
        }
        
        .btn-verify {
            background: linear-gradient(135deg, #17a2b8, #138496);
            color: white;
        }
        
        .btn-verify:hover {
            color: white;
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(23, 162, 184, 0.3);
        }
        
        .btn-back {
            background: linear-gradient(135deg, #6c757d, #495057);
            color: white;
        }
        
        .btn-back:hover {
            color: white;
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(108, 117, 125, 0.3);
        }
        
        /* Modal fullscreen */
        .fullscreen-qr {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.95);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 9999;
            cursor: pointer;
        }
        
        .fullscreen-qr img {
            max-width: 80vmin;
            max-height: 80vmin;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(255,255,255,0.3);
        }
        
        .close-fullscreen {
            position: absolute;
            top: 30px;
            right: 30px;
            color: white;
            font-size: 3rem;
            cursor: pointer;
            z-index: 10000;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .ticket-container {
                margin: 20px;
                border-radius: 20px;
            }
            
            .ticket-main {
                grid-template-columns: 1fr;
                gap: 20px;
            }
            
            .qr-code-large {
                width: 200px;
                height: 200px;
            }
            
            .event-title {
                font-size: 2rem;
            }
            
            .ticket-actions {
                flex-direction: column;
            }
            
            .btn-action {
                min-width: auto;
            }
        }
        
        /* Effets visuels */
        .floating {
            animation: floating 3s ease-in-out infinite;
        }
        
        @keyframes floating {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
        }
        
        .pulse {
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0% { box-shadow: 0 0 0 0 rgba(255, 107, 53, 0.7); }
            70% { box-shadow: 0 0 0 20px rgba(255, 107, 53, 0); }
            100% { box-shadow: 0 0 0 0 rgba(255, 107, 53, 0); }
        }
    </style>
</head>

<body>
    <!-- Container principal -->
    <div class="ticket-container">
        <!-- En-tête -->
        <div class="ticket-header">
            <h1 class="event-title">{{ $ticket->ticketType->event->title }}</h1>
            <p class="event-subtitle">
                <i class="fas fa-calendar me-2"></i>
                {{ $ticket->ticketType->event->event_date ? $ticket->ticketType->event->event_date->format('d F Y') : 'Date à confirmer' }}
                @if($ticket->ticketType->event->event_time)
                    à {{ $ticket->ticketType->event->event_time->format('H:i') }}
                @endif
            </p>
        </div>

        <!-- Corps du ticket -->
        <div class="ticket-body">
            <div class="ticket-main">
                <!-- Informations détaillées -->
                <div class="ticket-info-section">
                    <!-- Statut du billet -->
                    <div class="text-center mb-4">
                        @if($ticket->status == 'sold')
                            <span class="status-badge-large status-valid">
                                <i class="fas fa-check-circle me-2"></i>Billet Valide
                            </span>
                        @elseif($ticket->status == 'used')
                            <span class="status-badge-large status-used">
                                <i class="fas fa-check me-2"></i>Billet Utilisé
                            </span>
                        @else
                            <span class="status-badge-large status-cancelled">
                                <i class="fas fa-times me-2"></i>Billet {{ ucfirst($ticket->status) }}
                            </span>
                        @endif
                    </div>

                    <!-- Détails de l'événement -->
                    <div class="info-card">
                        <div class="info-title">
                            <i class="fas fa-info-circle"></i>
                            Détails de l'événement
                        </div>
                        <div class="info-item">
                            <span class="info-label">Lieu</span>
                            <span class="info-value">{{ $ticket->ticketType->event->venue ?? 'À confirmer' }}</span>
                        </div>
                        @if($ticket->ticketType->event->address)
                            <div class="info-item">
                                <span class="info-label">Adresse</span>
                                <span class="info-value">{{ $ticket->ticketType->event->address }}</span>
                            </div>
                        @endif
                        <div class="info-item">
                            <span class="info-label">Catégorie</span>
                            <span class="info-value">{{ $ticket->ticketType->event->category->name ?? 'Général' }}</span>
                        </div>
                    </div>

                    <!-- Détails du billet -->
                    <div class="info-card">
                        <div class="info-title">
                            <i class="fas fa-ticket-alt"></i>
                            Détails du billet
                        </div>
                        <div class="info-item">
                            <span class="info-label">Type</span>
                            <span class="info-value">{{ $ticket->ticketType->name ?? 'Standard' }}</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Prix</span>
                            <span class="info-value">{{ number_format($ticket->ticketType->price ?? 0) }} FCFA</span>
                        </div>
                        @if($ticket->seat_number)
                            <div class="info-item">
                                <span class="info-label">Siège</span>
                                <span class="info-value">{{ $ticket->seat_number }}</span>
                            </div>
                        @endif
                        <div class="info-item">
                            <span class="info-label">Acheté le</span>
                            <span class="info-value">{{ $ticket->created_at->format('d/m/Y à H:i') }}</span>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="ticket-actions">
                        @php
                            $order = $ticket->order;
                        @endphp
                        
                        @if($order && $order->canDownloadTickets())
                            <a href="{{ route('acheteur.order.download', $order) }}" class="btn-action btn-download">
                                <i class="fas fa-file-pdf me-2"></i>Télécharger PDF
                            </a>
                        @endif
                        
                        <a href="{{ url("/verify-ticket/{$ticket->ticket_code}") }}" 
                           target="_blank" class="btn-action btn-verify">
                            <i class="fas fa-external-link-alt me-2"></i>Vérifier en ligne
                        </a>
                        
                        <a href="{{ route('acheteur.tickets') }}" class="btn-action btn-back">
                            <i class="fas fa-arrow-left me-2"></i>Retour aux billets
                        </a>
                    </div>
                </div>

                <!-- Section QR Code principale -->
                <div class="qr-main-section floating">
                    <div class="qr-title">
                        <i class="fas fa-qrcode me-2"></i>
                        Code QR à scanner
                    </div>

                    @php
                        try {
                            $qrService = app(\App\Services\QRCodeService::class);
                            $qrBase64 = $qrService->generateTicketQRBase64($ticket, 300);
                            $hasValidQR = $qrBase64 && strlen($qrBase64) > 100;
                        } catch (\Exception $e) {
                            $qrBase64 = null;
                            $hasValidQR = false;
                            \Log::error("QR Error for {$ticket->ticket_code}: " . $e->getMessage());
                        }
                    @endphp

                    <div class="qr-code-large pulse" onclick="openFullscreen('{{ $qrBase64 ?? '' }}')">
                        @if($hasValidQR)
                            <img src="{{ $qrBase64 }}" alt="QR Code" class="qr-image-large">
                        @else
                            <div class="qr-fallback">
                                <i class="fas fa-qrcode"></i>
                                <div class="mt-3">
                                    <strong>QR Code indisponible</strong>
                                    <div class="small">Utilisez le code ci-dessous</div>
                                </div>
                            </div>
                        @endif
                    </div>

                    <!-- Code du billet -->
                    <div class="ticket-code-large">{{ $ticket->ticket_code }}</div>

                    @if($hasValidQR)
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle me-2"></i>
                            <strong>QR Code prêt !</strong> Cliquez pour l'agrandir
                        </div>
                    @else
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <strong>Code manuel requis</strong> - Présentez le code ci-dessus
                        </div>
                    @endif

                    <!-- Instructions de scan -->
                    <div class="scan-instructions">
                        <div class="fw-bold mb-2">
                            <i class="fas fa-mobile-alt me-2"></i>
                            Comment scanner ce billet
                        </div>
                        <div class="scan-steps">
                            <div class="scan-step">
                                <i class="fas fa-camera text-primary"></i>
                                <div class="small">Ouvrez l'appareil photo</div>
                            </div>
                            <div class="scan-step">
                                <i class="fas fa-crosshairs text-warning"></i>
                                <div class="small">Visez le QR code</div>
                            </div>
                            <div class="scan-step">
                                <i class="fas fa-check text-success"></i>
                                <div class="small">Validation automatique</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal fullscreen pour QR -->
    <div class="fullscreen-qr" id="fullscreenQR" onclick="closeFullscreen()">
        <div class="close-fullscreen">&times;</div>
        <img id="fullscreenImage" src="" alt="QR Code">
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        function openFullscreen(qrData) {
            if (!qrData) return;
            
            document.getElementById('fullscreenImage').src = qrData;
            document.getElementById('fullscreenQR').style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }
        
        function closeFullscreen() {
            document.getElementById('fullscreenQR').style.display = 'none';
            document.body.style.overflow = 'auto';
        }
        
        // Fermer avec Escape
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeFullscreen();
            }
        });
        
        // Empêcher la fermeture quand on clique sur l'image
        document.getElementById('fullscreenImage').addEventListener('click', function(e) {
            e.stopPropagation();
        });
    </script>
</body>
</html>