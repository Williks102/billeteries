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
        
        .seat-info {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            padding: 8px;
            margin: 5px 0;
            text-align: center;
        }
        
        .ticket-perforations {
            border-top: 2px dashed #ddd;
            margin: 20px 0;
            height: 1px;
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
                                {{ $order->event->formatted_event_date ?? $order->event->event_date }}<br>
                                {{ $order->event->formatted_event_time ?? $order->event->event_time }}
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
                            <div class="info-value fw-bold">{{ $ticket->ticketType->name ?? 'Billet Standard' }}</div>
                            @if($ticket->ticketType && $ticket->ticketType->description)
                                <div class="text-small">{{ $ticket->ticketType->description }}</div>
                            @endif
                        </div>
                        
                        @if($ticket->seat_number)
                            <div class="info-group">
                                <span class="info-label">💺 Siège</span>
                                <div class="seat-info">
                                    <strong>{{ $ticket->seat_number }}</strong>
                                </div>
                            </div>
                        @endif
                        
                        <div class="info-group">
                            <span class="info-label">🎟️ Numéro de commande</span>
                            <div class="info-value">{{ $order->order_number }}</div>
                        </div>
                        
                        @if($order->event->description)
                            <div class="info-group">
                                <span class="info-label">ℹ️ Description</span>
                                <div class="info-value text-small">
                                    {{ Str::limit($order->event->description, 200) }}
                                </div>
                            </div>
                        @endif
                    </div>
                    
                    <!-- QR Code et informations à droite -->
                    <div class="col-right">
                        @php
                            try {
                                // Utiliser le service QR code unifié
                                $qrService = app(\App\Services\QRCodeService::class);
                                $qrCodeBase64 = $qrService->getOrGenerateTicketQR($ticket, 'base64');
                                
                                // Fallback si le service échoue
                                if (!$qrCodeBase64) {
                                    // Essayer directement avec Google Charts
                                    $verificationUrl = url("/verify-ticket/{$ticket->ticket_code}");
                                    $qrUrl = "https://chart.googleapis.com/chart?" . http_build_query([
                                        'chs' => '200x200',
                                        'cht' => 'qr',
                                        'chl' => $verificationUrl,
                                        'choe' => 'UTF-8',
                                        'chld' => 'M|2'
                                    ]);
                                    
                                    $response = \Illuminate\Support\Facades\Http::timeout(10)->get($qrUrl);
                                    
                                    if ($response->successful()) {
                                        $qrCodeBase64 = 'data:image/png;base64,' . base64_encode($response->body());
                                    }
                                }
                                
                            } catch (\Exception $e) {
                                \Log::error('Erreur QR PDF pour ticket ' . $ticket->ticket_code . ': ' . $e->getMessage());
                                $qrCodeBase64 = null;
                            }
                        @endphp
                        
                        @if($qrCodeBase64)
                            <!-- QR Code réel généré -->
                            <img src="{{ $qrCodeBase64 }}" alt="QR Code - {{ $ticket->ticket_code }}" class="qr-code">
                        @else
                            <!-- Placeholder si génération échoue -->
                            <div class="qr-placeholder">
                                <div class="qr-content">
                                    <strong>QR CODE</strong><br>
                                    <small>{{ $ticket->ticket_code }}</small><br>
                                    <small>SCAN ME</small>
                                </div>
                            </div>
                        @endif
                        
                        <!-- Code du billet toujours affiché -->
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
                        
                        <!-- Prix du billet -->
                        @if($ticket->ticketType && $ticket->ticketType->price)
                            <div class="price-badge">
                                {{ number_format($ticket->ticketType->price, 0, ',', ' ') }} FCFA
                            </div>
                        @endif
                        
                        <!-- Instructions d'utilisation -->
                        <div class="instructions">
                            <strong>Instructions :</strong>
                            <ul>
                                <li>Présentez ce billet à l'entrée</li>
                                <li>Le QR code sera scanné</li>
                                <li>Gardez votre billet jusqu'à la fin</li>
                                @if($ticket->seat_number)
                                    <li>Rendez-vous à votre siège : {{ $ticket->seat_number }}</li>
                                @endif
                            </ul>
                        </div>
                    </div>
                </div>
                
                <!-- Séparateur perforé -->
                <div class="ticket-perforations"></div>
                
                <!-- Section acheteur -->
                <div class="info-group">
                    <span class="info-label">👤 Informations Acheteur</span>
                    <div class="info-value">
                        <strong>Nom :</strong> {{ $order->user->name }}<br>
                        <strong>Email :</strong> {{ $order->user->email }}<br>
                        @if($order->user->phone)
                            <strong>Téléphone :</strong> {{ $order->user->phone }}<br>
                        @endif
                        <strong>Date d'achat :</strong> {{ $order->created_at->format('d/m/Y à H:i') }}
                    </div>
                </div>
                
                <!-- Informations importantes -->
                @if($order->event->important_info)
                    <div class="highlight">
                        <strong>⚠️ Information importante :</strong><br>
                        {{ $order->event->important_info }}
                    </div>
                @endif
                
                <!-- Footer du billet -->
                <div class="footer clearfix">
                    <div class="footer-left">
                        <strong>Billetterie CI</strong><br>
                        Support : support@billetterie-ci.com<br>
                        Tél : +225 XX XX XX XX
                    </div>
                    <div class="footer-right">
                        Billet généré le {{ now()->format('d/m/Y à H:i') }}<br>
                        ID Commande : {{ $order->id }}<br>
                        Version PDF v1.2
                    </div>
                </div>
            </div>
        </div>
    @endforeach
    
    <!-- Page de résumé si plusieurs billets -->
    @if($order->tickets->count() > 1)
        <div class="summary-page">
            <h2>Résumé de votre commande</h2>
            
            <div class="summary-box">
                <h4>{{ $order->event->title }}</h4>
                <p><strong>Date :</strong> {{ $order->event->formatted_event_date ?? $order->event->event_date }}</p>
                <p><strong>Lieu :</strong> {{ $order->event->venue }}</p>
                <p><strong>Commande N° :</strong> {{ $order->order_number }}</p>
                <p><strong>Acheteur :</strong> {{ $order->user->name }}</p>
                
                <h5 style="margin-top: 20px;">Billets achetés :</h5>
                @foreach($order->tickets as $ticket)
                    <div style="margin: 10px 0; padding: 8px; background: white; border-radius: 5px;">
                        <strong>{{ $ticket->ticketType->name ?? 'Billet' }}</strong> - {{ $ticket->ticket_code }}
                        @if($ticket->seat_number)
                            <br><small>Siège : {{ $ticket->seat_number }}</small>
                        @endif
                        @if($ticket->ticketType && $ticket->ticketType->price)
                            <br><small>Prix : {{ number_format($ticket->ticketType->price, 0, ',', ' ') }} FCFA</small>
                        @endif
                    </div>
                @endforeach
                
                <div style="margin-top: 20px; padding-top: 15px; border-top: 2px solid #FF6B35; text-align: center;">
                    <h5>Total : {{ number_format($order->total_amount ?? 0, 0, ',', ' ') }} FCFA</h5>
                </div>
            </div>
            
            <div class="highlight" style="margin-top: 30px;">
                <strong>Important :</strong> Chaque billet doit être présenté individuellement à l'entrée. 
                Gardez tous vos billets en sécurité jusqu'à l'événement.
            </div>
        </div>
    @endif
</body>
</html>