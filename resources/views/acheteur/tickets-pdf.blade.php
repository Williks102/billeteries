<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>E-Billet - {{ $order->order_number }}</title>
    <style>
        @page {
            margin: 0;
            size: A4;
        }
        
        body {
            margin: 0;
            padding: 0;
            font-family: 'Arial', sans-serif;
            background: white;
            color: #333;
        }

        .ticket-container {
            width: 100%;
            max-width: 800px;
            margin: 20px auto;
            background: white;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }

        /* Header avec logo et numéro de commande */
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 30px 40px;
            border-bottom: 3px solid #6366f1;
        }

        .logo {
            font-size: 32px;
            font-weight: bold;
            color: #6366f1;
            text-transform: lowercase;
        }

        .order-number {
            font-size: 16px;
            font-weight: bold;
            color: #333;
        }

        /* Section principale de l'événement */
        .event-section {
            display: flex;
            padding: 40px;
            border-bottom: 2px solid #e5e7eb;
        }

        .event-info {
            flex: 1;
            padding-right: 30px;
        }

        .event-title {
            font-size: 24px;
            font-weight: bold;
            color: #111;
            margin-bottom: 15px;
            line-height: 1.2;
        }

        .event-datetime {
            font-size: 16px;
            color: #666;
            margin-bottom: 8px;
        }

        .event-venue {
            font-size: 16px;
            color: #666;
            margin-bottom: 25px;
        }

        .ticket-type-price {
            display: flex;
            align-items: center;
            gap: 30px;
        }

        .ticket-type {
            background: #111;
            color: white;
            padding: 8px 20px;
            font-weight: bold;
            font-size: 14px;
            text-transform: uppercase;
        }

        .ticket-price {
            font-size: 20px;
            font-weight: bold;
            color: #111;
        }

        /* Section QR Code */
        .qr-section {
            flex: none;
            width: 200px;
            text-align: center;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
        }

        .qr-title {
            font-size: 12px;
            color: #666;
            margin-bottom: 15px;
            line-height: 1.3;
        }

        .qr-codes {
            display: flex;
            justify-content: center;
            margin-bottom: 20px;
        }

        .qr-code img {
            width: 120px;
            height: 120px;
        }

        .qr-placeholder {
            width: 80px;
            height: 80px;
            background: #e5e7eb;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 10px;
            color: #666;
            text-align: center;
            line-height: 1.2;
        }

        .reference-code {
            font-size: 14px;
            font-weight: bold;
            color: #111;
            font-family: 'Courier New', monospace;
            background: white;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        /* Label E-TICKET */
        .eticket-label {
            background: #111;
            color: white;
            padding: 8px 20px;
            font-size: 14px;
            font-weight: bold;
            letter-spacing: 1px;
            position: absolute;
            left: 40px;
            margin-top: -20px;
        }

        /* Section détails client et commande */
        .details-section {
            display: flex;
            padding: 30px 40px;
            gap: 60px;
            background: #f8f9fa;
        }

        .client-details, .order-details {
            flex: 1;
        }

        .section-title {
            font-size: 14px;
            font-weight: bold;
            color: #111;
            margin-bottom: 10px;
            text-transform: uppercase;
        }

        .detail-name {
            font-size: 18px;
            font-weight: bold;
            color: #111;
            margin-bottom: 5px;
        }

        .detail-info {
            font-size: 14px;
            color: #666;
            margin-bottom: 3px;
        }

        .detail-order {
            font-size: 16px;
            font-weight: bold;
            color: #111;
            margin-bottom: 5px;
        }

        /* Section conditions */
        .terms-section {
            padding: 25px 40px;
            border-top: 1px solid #e5e7eb;
        }

        .terms-title {
            font-size: 12px;
            font-weight: bold;
            color: #111;
            margin-bottom: 8px;
            text-transform: uppercase;
        }

        .terms-text {
            font-size: 11px;
            color: #666;
            line-height: 1.4;
        }

        /* Responsive pour impression */
        @media print {
            .ticket-container {
                box-shadow: none;
                margin: 0;
            }
        }

        /* Page break entre billets */
        .page-break {
            page-break-before: always;
        }
    </style>
</head>
<body>
    @foreach($order->tickets as $index => $ticket)
        <div class="ticket-container {{ $index > 0 ? 'page-break' : '' }}">
            
            <!-- Header -->
            <div class="header">
                <div class="logo">clicbillet</div>
                <div class="order-number">COMMANDE #{{ $order->order_number }}</div>
            </div>

            <!-- Section principale -->
            <div class="event-section">
                <div class="event-info">
                    <div class="event-title">
                        {{ $order->event->title }}
                    </div>
                    
                    <div class="event-datetime">
                        {{ $order->event->formatted_event_date ?? 'Date à déterminer' }}
                        @if($order->event->formatted_event_time)
                            {{ $order->event->formatted_event_time }}
                        @endif
                    </div>
                    
                    <div class="event-venue">
                        {{ $order->event->venue ?? 'Lieu à déterminer' }}
                    </div>

                    <div class="ticket-type-price">
                        <div class="ticket-type">
                            @if($ticket->ticketType)
                                {{ $ticket->ticketType->name }}
                            @else
                                STANDARD
                            @endif
                        </div>
                        <div class="ticket-price">
                            @if($ticket->ticketType)
                                {{ number_format($ticket->ticketType->price, 0, ',', ' ') }} FCFA
                            @else
                                0 FCFA
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Section QR Code -->
                <div class="qr-section">
                    <div class="qr-title">
                        Code QR d'exemple pour<br>Accès à l'événement
                    </div>
                    
                    <div class="qr-codes">
                        @php
                            $qrCodeBase64 = null;
                            try {
                                $qrCodeBase64 = $ticket->getQrCodeBase64();
                            } catch (\Exception $e) {
                                // Fallback silencieux
                            }
                        @endphp
                        
                        <!-- QR Code unique -->
                        <div class="qr-code">
                            @if($qrCodeBase64)
                                <img src="{{ $qrCodeBase64 }}" alt="QR Code" style="width: 120px; height: 120px;">
                            @else
                                <div class="qr-placeholder" style="width: 120px; height: 120px; font-size: 12px;">QR<br>CODE</div>
                            @endif
                        </div>
                    </div>
                    
                    <div class="reference-code">
                        RÉF: {{ $ticket->ticket_code }}
                    </div>
                </div>
            </div>

            <!-- Label E-TICKET -->
            <div class="eticket-label">E-BILLET</div>

            <!-- Section détails -->
            <div class="details-section">
                <!-- Détails client -->
                <div class="client-details">
                    <div class="section-title">Détenteur du billet</div>
                    <div class="detail-name">{{ $order->user->name }}</div>
                    @if($order->user->phone)
                        <div class="detail-info">{{ $order->user->phone }}</div>
                    @endif
                    <div class="detail-info">{{ $order->user->email }}</div>
                </div>
                
                <!-- Détails commande -->
                <div class="order-details">
                    <div class="section-title">Commande</div>
                    <div class="detail-order">#{{ $order->order_number }}</div>
                    <div class="detail-info">{{ $order->created_at->format('d/m/Y H:i:s') }}</div>
                    <div class="detail-info">(UTC +00:00)</div>
                </div>
            </div>

            <!-- Conditions générales -->
            <div class="terms-section">
                <div class="terms-title">Conditions générales pour le détenteur du billet</div>
                <div class="terms-text">
                    Ce document contient des informations privées et confidentielles incluant des informations personnelles, 
                    des informations de contact personnel, et toute autre information demandée par 
                    <strong>{{ strtoupper($order->event->promoteur->name ?? 'L\'ORGANISATEUR') }}</strong>, 
                    l'organisateur de cet événement. Le Code QR (code-barres bidimensionnel), la référence (Code de référence) 
                    sont secrets, vous comprenez que vous, le propriétaire / acheteur du billet, 
                    détenez la seule responsabilité de la confidentialité de ce code. 
                    <strong>Billet nominatif et incessible. Pièce d'identité requise à l'entrée.</strong>
                </div>
            </div>
        </div>
    @endforeach
</body>
</html>