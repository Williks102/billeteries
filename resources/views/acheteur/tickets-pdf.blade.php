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
        
        .footer {
            border-top: 1px dashed #ddd;
            padding-top: 10px;
            margin-top: 20px;
            font-size: 9px;
            color: #666;
        }
        
        .footer-left {
            float: left;
            width: 50%;
        }
        
        .footer-right {
            float: right;
            width: 50%;
            text-align: right;
        }
        
        .clearfix::after {
            content: "";
            display: table;
            clear: both;
        }
        
        .text-center { text-align: center; }
        .fw-bold { font-weight: bold; }
        .text-small { font-size: 10px; }
        
        .highlight {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 4px;
            padding: 8px;
            margin: 10px 0;
            font-size: 10px;
            color: #856404;
        }
        
        .summary-page {
            page-break-before: always;
            padding: 30px;
            text-align: center;
        }
        
        .summary-box {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin: 20px 0;
            text-align: left;
        }
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
                                {{ $order->event->formatted_event_date }}<br>
                                {{ $order->event->formatted_event_time }}
                                @if($order->event->end_time)
                                    - {{ $order->event->end_time->format('H:i') }}
                                @endif
                            </div>
                        </div>
                        
                        <div class="info-group">
                            <span class="info-label">📍 Lieu</span>
                            <div class="info-value">
                                {{ $order->event->venue }}<br>
                                @if($order->event->address)
                                    <span class="text-small">{{ $order->event->address }}</span>
                                @endif
                            </div>
                        </div>
                        
                        <div class="info-group">
                            <span class="info-label">🎫 Type de billet</span>
                            <div class="info-value fw-bold">{{ $ticket->ticketType->name }}</div>
                            @if($ticket->ticketType->description)
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
                            <span class="info-label">👤 Organisateur</span>
                            <div class="info-value">{{ $order->event->promoteur->name }}</div>
                        </div>
                        
                        <div class="price-badge">
                            {{ \App\Helpers\CurrencyHelper::formatFCFA($ticket->ticketType->price) }}
                        </div>
                        
                        @if(stripos($ticket->ticketType->name, 'étudiant') !== false)
                            <div class="highlight">
                                <strong>⚠️ Attention :</strong> Carte étudiant obligatoire à l'entrée
                            </div>
                        @endif
                        
                        <div class="instructions">
                            <strong>Instructions importantes :</strong>
                            <ul>
                                <li>Présentez ce billet à l'entrée</li>
                                <li>Arrivez 30 minutes avant le début</li>
                                <li>Pièce d'identité requise</li>
                                <li>Conservez pendant tout l'événement</li>
                            </ul>
                        </div>
                    </div>
                    
                    <!-- QR Code à droite -->
                    <div class="col-right">
                        <div class="info-group">
                            <span class="info-label">🔐 Code d'entrée</span>
                        </div>
                        
                        @php
                            // Générer le QR code réel avec la nouvelle méthode
                            $qrService = app(\App\Services\QRCodeService::class);
                            $qrCodeBase64 = $qrService->getOrGenerateTicketQR($ticket, 'base64');
                        @endphp
                        
                        @if($qrCodeBase64)
                            <!-- QR Code réel -->
                            <img src="{{ $qrCodeBase64 }}" alt="QR Code - {{ $ticket->ticket_code }}" class="qr-code">
                        @else
                            <!-- Fallback si erreur -->
                            <div class="qr-placeholder">
                                <div class="qr-content">
                                    QR CODE<br>
                                    SCAN ME
                                </div>
                            </div>
                        @endif
                        
                        <div class="ticket-code">{{ $ticket->ticket_code }}</div>
                        
                        <div class="text-small">
                            Présentez ce code à l'entrée
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
                        
                        <div class="text-small" style="margin-top: 15px;">
                            <strong>Acheteur :</strong><br>
                            {{ $order->user->name }}<br>
                            <span class="text-small">{{ $order->billing_email }}</span>
                        </div>
                        
                        <div class="text-small" style="margin-top: 10px;">
                            <strong>URL de vérification :</strong><br>
                            <span class="text-small">{{ url("/verify-ticket/{$ticket->ticket_code}") }}</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Pied de page -->
            <div class="footer clearfix">
                <div class="footer-left">
                    <strong>Billetterie CI</strong><br>
                    Commande : {{ $order->order_number }}<br>
                    Émis le : {{ $order->created_at->format('d/m/Y H:i') }}
                </div>
                <div class="footer-right">
                    Support : +225 01 02 03 04 05<br>
                    Email : support@billetterie-ci.com<br>
                    www.billetterie-ci.com
                </div>
            </div>
        </div>
    @endforeach
    
    <!-- Page de récapitulatif -->
    <div class="summary-page">
        <h2 style="color: #FF6B35; margin-bottom: 20px;">📋 Récapitulatif de commande</h2>
        
        <div class="summary-box">
            <div style="display: table; width: 100%;">
                <div style="display: table-cell; width: 50%; padding-right: 20px;">
                    <h4 style="color: #FF6B35; margin-bottom: 10px;">Détails</h4>
                    <p><strong>Commande :</strong> {{ $order->order_number }}</p>
                    <p><strong>Date :</strong> {{ $order->created_at->format('d/m/Y H:i') }}</p>
                    <p><strong>Statut :</strong> 
                        @if($order->payment_status == 'paid')
                            <span style="background: #28a745; color: white; padding: 2px 8px; border-radius: 10px; font-size: 10px;">PAYÉ</span>
                        @else
                            <span style="background: #ffc107; color: #000; padding: 2px 8px; border-radius: 10px; font-size: 10px;">{{ strtoupper($order->payment_status) }}</span>
                        @endif
                    </p>
                    <p><strong>Email :</strong> {{ $order->billing_email }}</p>
                </div>
                
                <div style="display: table-cell; width: 50%;">
                    <h4 style="color: #FF6B35; margin-bottom: 10px;">Billets</h4>
                    @foreach($order->orderItems as $item)
                        <div style="display: table; width: 100%; margin-bottom: 5px;">
                            <div style="display: table-cell;">{{ $item->quantity }}× {{ $item->ticketType->name }}</div>
                            <div style="display: table-cell; text-align: right;">{{ \App\Helpers\CurrencyHelper::formatFCFA($item->total_price) }}</div>
                        </div>
                    @endforeach
                    
                    <div style="border-top: 1px solid #ddd; margin: 10px 0; padding-top: 10px;">
                        <div style="display: table; width: 100%;">
                            <div style="display: table-cell; font-weight: bold;">Total :</div>
                            <div style="display: table-cell; text-align: right; font-weight: bold; color: #FF6B35;">{{ \App\Helpers\CurrencyHelper::formatFCFA($order->total_amount) }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div style="background: #e7f3ff; border: 1px solid #b3d7ff; border-radius: 8px; padding: 15px; margin: 20px 0;">
            <p style="margin: 0; color: #0c5aa6; text-align: center;">
                <strong>💝 Merci d'avoir choisi Billetterie CI !</strong><br>
                <span style="font-size: 10px;">Support : +225 01 02 03 04 05 • support@billetterie-ci.com</span>
            </p>
        </div>
    </div>
</body>
</html>