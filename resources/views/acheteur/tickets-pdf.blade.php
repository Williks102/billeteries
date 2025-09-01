{{-- resources/views/acheteur/tickets-pdf.blade.php - STYLE EVENTPOP CORRIGÉ --}}
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Billets - {{ $order->event->title ?? 'Événement' }}</title>
    <style>
        @page {
            margin: 15mm;
            size: A4 portrait;
        }
        
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            margin: 0;
            padding: 0;
            line-height: 1.4;
            color: #333;
            font-size: 14px;
        }
        
        .ticket {
            page-break-inside: avoid;
            page-break-after: always;
            border: 2px solid #000;
            background: white;
            margin: 0;
            padding: 0;
            height: 240mm;
            position: relative;
        }
        
        .ticket-header {
            padding: 20px 25px;
            border-bottom: 1px solid #ddd;
            background: #fafafa;
            display: table;
            width: 100%;
        }
        
        .header-left {
            display: table-cell;
            vertical-align: middle;
        }
        
        .header-right {
            display: table-cell;
            vertical-align: middle;
            text-align: right;
        }
        
        .logo {
            font-size: 32px;
            font-weight: bold;
            color: #FF6B35;
            letter-spacing: -1px;
        }
        
        .order-number {
            font-size: 16px;
            font-weight: bold;
            color: #666;
        }
        
        .event-section {
            padding: 25px;
            border-bottom: 1px solid #ddd;
            height: 120px;
        }
        
        .event-main {
            display: table;
            width: 100%;
            height: 100%;
        }
        
        .event-info {
            display: table-cell;
            width: 70%;
            vertical-align: top;
            padding-right: 20px;
        }
        
        .ticket-type-info {
            display: table-cell;
            width: 30%;
            vertical-align: top;
            text-align: right;
        }
        
        .event-title {
            font-size: 22px;
            font-weight: bold;
            color: #000;
            margin: 0 0 8px 0;
            text-transform: uppercase;
        }
        
        .event-details {
            font-size: 15px;
            color: #666;
            margin: 3px 0;
        }
        
        .ticket-type {
            font-size: 22px;
            font-weight: bold;
            color: #000;
            margin: 0 0 5px 0;
        }
        
        .ticket-price {
            font-size: 18px;
            font-weight: bold;
            color: #000;
        }
        
        .qr-codes-section {
            text-align: center;
            padding: 15px 25px;
            height: 120px;
        }
        
        .qr-title {
            font-size: 11px;
            color: #666;
            margin-bottom: 8px;
        }
        
        .qr-codes {
            margin: 8px 0;
        }
        
        .qr-code {
            display: inline-block;
            width: 75px;
            height: 75px;
            border: 1px solid #ddd;
            background: white;
            margin: 0 8px;
            vertical-align: top;
        }
        
        .qr-placeholder {
            padding: 15px 5px;
            font-size: 9px;
            color: #999;
            text-align: center;
            font-weight: bold;
        }
        
        .barcode-section {
            margin: 8px 0;
            text-align: center;
        }
        
        .barcode-lines {
            height: 25px;
            background: repeating-linear-gradient(
                90deg,
                #000 0px, #000 1px,
                transparent 1px, transparent 2px,
                #000 2px, #000 3px,
                transparent 3px, transparent 5px
            );
            margin: 3px auto;
            width: 180px;
        }
        
        .reference-code {
            font-size: 13px;
            font-weight: bold;
            color: #000;
            letter-spacing: 1px;
        }
        
        .eticket-label {
            background: #000;
            color: white;
            padding: 6px 12px;
            font-size: 13px;
            font-weight: bold;
            letter-spacing: 1px;
            position: absolute;
            left: 25px;
            bottom: 140px;
        }
        
        .bottom-section {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 120px;
            padding: 20px 25px;
            background: #fafafa;
            border-top: 1px solid #ddd;
        }
        
        .bottom-info {
            display: table;
            width: 100%;
            height: 60px;
        }
        
        .client-info, .order-info {
            display: table-cell;
            width: 50%;
            vertical-align: top;
        }
        
        .order-info {
            text-align: right;
        }
        
        .section-title {
            font-size: 11px;
            font-weight: bold;
            color: #666;
            text-transform: uppercase;
            margin-bottom: 5px;
        }
        
        .section-content {
            font-size: 15px;
            font-weight: bold;
            color: #000;
            margin-bottom: 2px;
        }
        
        .section-sub {
            font-size: 13px;
            color: #666;
        }
        
        .terms-section {
            border-top: 1px solid #ddd;
            padding-top: 10px;
            height: 40px;
            overflow: hidden;
        }
        
        .terms-title {
            font-size: 10px;
            font-weight: bold;
            color: #000;
            margin-bottom: 5px;
        }
        
        .terms-text {
            font-size: 9px;
            color: #666;
            line-height: 1.2;
        }
    </style>
</head>
<body>
    @foreach($order->tickets as $index => $ticket)
        <div class="ticket">
            {{-- Header avec logo et commande --}}
            <div class="ticket-header">
                <div class="header-left">
                    <div class="logo">clicbillet</div>
                </div>
                <div class="header-right">
                    <div class="order-number">COMMANDE #{{ $order->order_number }}</div>
                </div>
            </div>
            
            {{-- Section événement --}}
            <div class="event-section">
                <div class="event-main">
                    <div class="event-info">
                        <div class="event-title">{{ $order->event->title ?? 'ÉVÉNEMENT' }}</div>
                        <div class="event-details">
                            {{ $order->event->formatted_event_date ?? 'Date TBD' }}
                            @if($order->event->formatted_event_time)
                                {{ $order->event->formatted_event_time }}
                            @else
                                - Heure TBD
                            @endif
                        </div>
                        <div class="event-details">{{ $order->event->venue ?? 'Lieu à définir' }}</div>
                    </div>
                    
                    <div class="ticket-type-info">
                        <div class="ticket-type">
                            @if($ticket->ticketType)
                                {{ strtoupper($ticket->ticketType->name) }}
                            @else
                                STANDARD
                            @endif
                        </div>
                        <div class="ticket-price">
                            @if($ticket->ticketType)
                                {{ number_format($ticket->ticketType->price, 0, ',', ' ') }}
                            @else
                                0
                            @endif
                            FCFA
                        </div>
                    </div>
                </div>
            </div>
            
            {{-- QR Codes avec logique de votre ancien template --}}
            <div class="qr-codes-section">
                <div class="qr-title">Code QR d'exemple pour<br>Accès à l'événement</div>
                
                <div class="qr-codes">
                    {{-- QR Code 1 - Utilise votre logique existante --}}
                    <div class="qr-code">
                        @php
                            $qrCodeBase64 = null;
                            try {
                                $qrCodeBase64 = $ticket->getQrCodeBase64();
                            } catch (\Exception $e) {
                                // Fallback silencieux
                            }
                        @endphp
                        
                        @if($qrCodeBase64)
                            <img src="{{ $qrCodeBase64 }}" 
                                 style="width: 73px; height: 73px;" 
                                 alt="QR Code">
                        @else
                            <div class="qr-placeholder">QR<br>CODE</div>
                        @endif
                    </div>
                    
                    {{-- QR Code 2 - Même logique --}}
                    <div class="qr-code">
                        @if($qrCodeBase64)
                            <img src="{{ $qrCodeBase64 }}" 
                                 style="width: 73px; height: 73px;" 
                                 alt="QR Code">
                        @else
                            <div class="qr-placeholder">QR<br>CODE</div>
                        @endif
                    </div>
                </div>
                
                {{-- Code-barres stylisé --}}
                <div class="barcode-section">
                    <div class="barcode-lines"></div>
                    <div class="reference-code">RÉF: {{ $ticket->ticket_code }}</div>
                </div>
            </div>
            
            {{-- Label E-BILLET --}}
            <div class="eticket-label">E-BILLET</div>
            
            {{-- Section inférieure - BLOC COMPACT --}}
            <div class="bottom-section">
                <div class="bottom-info">
                    {{-- Informations client (bloc compact) --}}
                    <div class="client-info">
                        <div class="section-title">Détenteur du billet</div>
                        <div class="section-content">{{ $order->user->name }}</div>
                        @if($order->user->phone)
                            <div class="section-sub">{{ $order->user->phone }}</div>
                        @endif
                        <div class="section-sub">{{ $order->user->email }}</div>
                        @if($order->user->customer_code)
                            <div class="section-sub">Code: {{ $order->user->customer_code }}</div>
                        @endif
                    </div>
                    
                    {{-- Informations commande (bloc compact) --}}
                    <div class="order-info">
                        <div class="section-title">Commande</div>
                        <div class="section-content">#{{ $order->order_number }}</div>
                        <div class="section-sub">{{ $order->created_at->format('d/m/Y H:i:s') }}</div>
                        <div class="section-sub">(UTC +00:00)</div>
                    </div>
                </div>
                
                {{-- Conditions générales compactes --}}
                <div class="terms-section">
                    <div class="terms-title">CONDITIONS GÉNÉRALES POUR LE DÉTENTEUR DU BILLET</div>
                    <div class="terms-text">
                        Ce document contient des informations privées et confidentielles. 
                        Le Code QR et la référence sont secrets - vous en êtes responsable. 
                        <strong>Billet nominatif et incessible. Pièce d'identité requise à l'entrée.</strong>
                        Organisé par <strong>{{ strtoupper($order->event->promoteur->name ?? 'L\'ORGANISATEUR') }}</strong>.
                    </div>
                </div>
            </div>
        </div>
    @endforeach
</body>
</html>