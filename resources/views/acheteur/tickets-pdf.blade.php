<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Billets - {{ $order->event->title }}</title>
    <style>
        @page {
            margin: 15mm;
            size: A4 portrait;
        }
        
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 12px;
            line-height: 1.4;
            color: #333;
            margin: 0;
            padding: 0;
        }
        
        .ticket {
            border: 3px solid #FF6B35;
            border-radius: 10px;
            padding: 20px;
            margin: 20px 0;
            background: white;
            page-break-inside: avoid;
            min-height: 250mm;
        }
        
        .ticket:not(:last-child) {
            page-break-after: always;
        }
        
        .ticket-header {
            background: linear-gradient(135deg, #FF6B35 0%, #1a1a1a 100%);
            color: white;
            padding: 15px;
            margin: -20px -20px 20px -20px;
            border-radius: 7px 7px 0 0;
            text-align: center;
        }
        
        .ticket-header h1 {
            font-size: 18px;
            margin: 0 0 5px 0;
            font-weight: bold;
        }
        
        .category {
            font-size: 12px;
            opacity: 0.9;
            margin: 0;
        }
        
        .ticket-content {
            padding: 10px 0;
        }
        
        .row {
            width: 100%;
            display: table;
            margin-bottom: 15px;
        }
        
        .col-left {
            display: table-cell;
            width: 70%;
            vertical-align: top;
            padding-right: 20px;
        }
        
        .col-right {
            display: table-cell;
            width: 30%;
            vertical-align: top;
            text-align: center;
            border-left: 2px dashed #ddd;
            padding-left: 20px;
        }
        
        .info-group {
            margin-bottom: 15px;
        }
        
        .info-label {
            font-weight: bold;
            color: #FF6B35;
            font-size: 10px;
            text-transform: uppercase;
            margin-bottom: 3px;
            display: block;
        }
        
        .info-value {
            font-size: 13px;
            font-weight: 500;
            line-height: 1.3;
        }
        
        .ticket-code {
            font-family: 'Courier New', monospace;
            font-size: 16px;
            font-weight: bold;
            letter-spacing: 2px;
            color: #1a1a1a;
            background: #f8f9fa;
            padding: 10px;
            border-radius: 5px;
            margin: 15px 0;
            text-align: center;
            border: 2px solid #FF6B35;
        }
        
        .qr-code {
            width: 120px;
            height: 120px;
            margin: 15px auto;
            border: 1px solid #ddd;
            border-radius: 5px;
            display: block;
        }
        
        .qr-placeholder {
            width: 120px;
            height: 120px;
            border: 2px dashed #FF6B35;
            border-radius: 8px;
            background: #f8f9fa;
            margin: 15px auto;
            display: table;
            color: #FF6B35;
            font-size: 10px;
        }
        
        .qr-content {
            display: table-cell;
            vertical-align: middle;
            text-align: center;
            font-weight: bold;
            line-height: 1.2;
        }
        
        .qr-success {
            width: 120px;
            height: 120px;
            border: 2px solid #28a745;
            border-radius: 8px;
            background: #f8fff9;
            margin: 15px auto;
            display: table;
            color: #28a745;
            font-size: 9px;
        }
        
        .price-badge {
            background: #1a1a1a;
            color: white;
            padding: 8px 15px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: bold;
            display: inline-block;
            margin: 10px 0;
        }
        
        .status-badge {
            background: #17a2b8;
            color: white;
            padding: 6px 12px;
            border-radius: 15px;
            font-size: 11px;
            font-weight: bold;
            display: inline-block;
            margin: 10px 0;
        }
        
        .status-valid { background: #28a745; }
        .status-used { background: #6c757d; }
        .status-cancelled { background: #dc3545; }
        
        .instructions {
            background: #e7f3ff;
            border: 1px solid #b3d7ff;
            border-radius: 5px;
            padding: 10px;
            margin: 15px 0;
            font-size: 10px;
            color: #0c5aa6;
        }
        
        .instructions ul {
            margin: 5px 0;
            padding-left: 15px;
        }
        
        .instructions li {
            margin-bottom: 3px;
        }
        
        .text-center { text-align: center; }
        .fw-bold { font-weight: bold; }
        .text-small { font-size: 10px; }
    </style>
</head>

<body>
    @foreach($order->tickets as $index => $ticket)
        <div class="ticket">
            <!-- En-tête du billet -->
            <div class="ticket-header">
                <h1>{{ $order->event->title }}</h1>
                <p class="category">{{ $order->event->category->name ?? 'Événement' }}</p>
            </div>
            
            <!-- Contenu principal -->
            <div class="ticket-content">
                <div class="row">
                    <!-- Informations à gauche -->
                    <div class="col-left">
                        <div class="info-group">
                            <span class="info-label">📅 Date & Heure</span>
                            <div class="info-value fw-bold">
                                {{ $order->event->event_date ? $order->event->event_date->format('d/m/Y') : 'Date TBD' }}<br>
                                {{ $order->event->event_time ? $order->event->event_time->format('H:i') : 'Heure TBD' }}
                            </div>
                        </div>
                        
                        <div class="info-group">
                            <span class="info-label">📍 Lieu</span>
                            <div class="info-value">
                                {{ $order->event->venue ?? 'Lieu TBD' }}<br>
                                @if($order->event->address)
                                    <span class="text-small">{{ $order->event->address }}</span>
                                @endif
                            </div>
                        </div>
                        
                        <div class="info-group">
                            <span class="info-label">🎫 Type de billet</span>
                            <div class="info-value fw-bold">{{ $ticket->ticketType->name ?? 'Billet Standard' }}</div>
                            @if($ticket->ticketType && $ticket->ticketType->description)
                                <div class="text-small">{{ $ticket->ticketType->description }}</div>
                            @endif
                        </div>
                        
                        @if($ticket->seat_number)
                            <div class="info-group">
                                <span class="info-label">💺 Siège</span>
                                <div class="info-value fw-bold">{{ $ticket->seat_number }}</div>
                            </div>
                        @endif
                        
                        <div class="info-group">
                            <span class="info-label">🎟️ Numéro de commande</span>
                            <div class="info-value">{{ $order->order_number }}</div>
                        </div>
                        
                        <div class="info-group">
                            <span class="info-label">👤 Acheteur</span>
                            <div class="info-value">
                                {{ $order->user->name }}<br>
                                <span class="text-small">{{ $order->user->email }}</span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- QR Code et informations à droite -->
                    <div class="col-right">
                        @php
                            // Force l'utilisation du nouveau service QR
                            $qrCodeGenerated = false;
                            $qrCodeBase64 = null;
                            
                            try {
                                // Utiliser explicitement le service QR corrigé
                                $qrService = app(\App\Services\QRCodeService::class);
                                
                                // Log pour debugging
                                \Log::info("Template PDF - Génération QR pour ticket: {$ticket->ticket_code}");
                                
                                $qrCodeBase64 = $qrService->getOrGenerateTicketQR($ticket, 'base64');
                                
                                if ($qrCodeBase64 && strlen($qrCodeBase64) > 100) {
                                    $qrCodeGenerated = true;
                                    \Log::info("Template PDF - QR généré avec succès pour: {$ticket->ticket_code}");
                                } else {
                                    \Log::warning("Template PDF - QR non généré pour: {$ticket->ticket_code}");
                                }
                                
                            } catch (\Exception $e) {
                                \Log::error("Template PDF - Erreur QR pour ticket {$ticket->ticket_code}: " . $e->getMessage());
                                $qrCodeBase64 = null;
                            }
                        @endphp
                        
                        @if($qrCodeGenerated && $qrCodeBase64)
                            <!-- QR Code réussi -->
                            <img src="{{ $qrCodeBase64 }}" alt="QR Code - {{ $ticket->ticket_code }}" class="qr-code">
                            <div class="qr-success">
                                <div class="qr-content">
                                    ✅ QR CODE<br>
                                    GÉNÉRÉ<br>
                                    <small>{{ $ticket->ticket_code }}</small>
                                </div>
                            </div>
                        @else
                            <!-- Placeholder si génération échoue -->
                            <div class="qr-placeholder">
                                <div class="qr-content">
                                    ⚠️ QR CODE<br>
                                    {{ $ticket->ticket_code }}<br>
                                    <small>Code manuel requis</small>
                                </div>
                            </div>
                        @endif
                        
                        <!-- Code du billet toujours affiché -->
                        <div class="ticket-code">{{ $ticket->ticket_code }}</div>
                        
                        <div class="text-small">
                            Présentez ce code à l'entrée
                        </div>
                        
                        <!-- URL de vérification -->
                        <div style="font-size: 8px; margin: 10px 0; color: #666; word-break: break-all;">
                            <strong>Vérification :</strong><br>
                            {{ url("/verify-ticket/{$ticket->ticket_code}") }}
                        </div>
                        
                        <!-- Statut du billet -->
                        @if($ticket->status == 'sold')
                            <div class="status-badge status-valid">✅ Valide</div>
                        @elseif($ticket->status == 'used')
                            <div class="status-badge status-used">✓ Utilisé</div>
                        @elseif($ticket->status == 'cancelled')
                            <div class="status-badge status-cancelled">✗ Annulé</div>
                        @else
                            <div class="status-badge">{{ ucfirst($ticket->status) }}</div>
                        @endif
                        
                        <!-- Prix du billet -->
                        @if($ticket->ticketType && $ticket->ticketType->price)
                            <div class="price-badge">
                                {{ number_format($ticket->ticketType->price, 0, ',', ' ') }} FCFA
                            </div>
                        @endif
                        
                        <!-- Instructions -->
                        <div class="instructions">
                            <strong>Instructions :</strong>
                            <ul>
                                <li>Présentez ce billet à l'entrée</li>
                                @if($qrCodeGenerated)
                                    <li>QR code scannable disponible</li>
                                @else
                                    <li>Code manuel si besoin</li>
                                @endif
                                <li>Gardez votre billet jusqu'à la fin</li>
                                @if($ticket->seat_number)
                                    <li>Siège : {{ $ticket->seat_number }}</li>
                                @endif
                            </ul>
                        </div>
                    </div>
                </div>
                
                <!-- Footer -->
                <div style="border-top: 1px dashed #ddd; padding-top: 10px; margin-top: 20px; font-size: 9px; color: #666;">
                    <div style="float: left; width: 50%;">
                        <strong>Billetterie CI</strong><br>
                        Support : support@billetterie-ci.com
                    </div>
                    <div style="float: right; width: 50%; text-align: right;">
                        Généré le {{ now()->format('d/m/Y à H:i') }}<br>
                        @if($qrCodeGenerated)
                            <span style="color: #28a745;">✅ QR Code intégré</span>
                        @else
                            <span style="color: #dc3545;">⚠️ QR Code non généré</span>
                        @endif
                    </div>
                    <div style="clear: both;"></div>
                </div>
            </div>
        </div>
    @endforeach
</body>
</html>