{{-- resources/views/acheteur/tickets-pdf.blade.php --}}
{{-- Version complète avec polices plus grandes et QR codes plus grands --}}
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Billets - {{ $order->event->title ?? 'Événement' }}</title>
    <style>
        @page {
            margin: 15mm;
            size: A4;
        }
        
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            margin: 0;
            padding: 0;
            line-height: 1.4;
            color: #333;
            font-size: 16px; /* Augmenté de 14px à 16px */
        }
        
        .ticket {
            page-break-inside: avoid;
            margin-bottom: 40px; /* Augmenté de 30px à 40px */
            border: 3px solid #FF6B35; /* Augmenté de 2px à 3px */
            border-radius: 15px; /* Augmenté de 12px à 15px */
            background: white;
            overflow: hidden;
            position: relative;
        }
        
        .ticket-header {
            background: linear-gradient(135deg, #1a237e, #2c3e50);
            color: white;
            padding: 25px; /* Augmenté de 20px à 25px */
            text-align: center;
            position: relative;
        }
        
        .event-title {
            font-size: 28px; /* Augmenté de 24px à 28px */
            font-weight: bold;
            margin: 0 0 10px 0;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .event-subtitle {
            font-size: 18px; /* Augmenté de 16px à 18px */
            margin: 0;
            opacity: 0.9;
        }
        
        .ticket-body {
            display: table;
            width: 100%;
            min-height: 200px; /* Augmenté de 180px à 200px */
        }
        
        .col-left {
            display: table-cell;
            width: 65%;
            vertical-align: top;
            padding: 25px; /* Augmenté de 20px à 25px */
            border-right: 3px dashed #ddd; /* Augmenté de 2px à 3px */
        }
        
        .col-right {
            display: table-cell;
            width: 35%;
            vertical-align: top;
            padding: 25px; /* Augmenté de 20px à 25px */
            text-align: center;
        }
        
        .info-group {
            margin-bottom: 20px; /* Augmenté de 15px à 20px */
        }
        
        .info-label {
            font-weight: bold;
            color: #FF6B35;
            font-size: 14px; /* Augmenté de 12px à 14px */
            text-transform: uppercase;
            margin-bottom: 5px; /* Augmenté de 3px à 5px */
            display: block;
            letter-spacing: 0.5px;
        }
        
        .info-value {
            font-size: 18px; /* Augmenté de 16px à 18px */
            font-weight: 600; /* Augmenté de 500 à 600 */
            line-height: 1.3;
            color: #1a1a1a;
        }
        
        .text-small {
            font-size: 15px; /* Augmenté de 13px à 15px */
            color: #666;
            line-height: 1.2;
        }
        
        .fw-bold {
            font-weight: bold;
        }
        
        .ticket-code {
            font-family: 'Courier New', monospace;
            font-size: 20px; /* Augmenté de 18px à 20px */
            font-weight: bold;
            letter-spacing: 3px; /* Augmenté de 2px à 3px */
            color: #1a1a1a;
            background: #f8f9fa;
            padding: 15px; /* Augmenté de 12px à 15px */
            border-radius: 8px; /* Augmenté de 6px à 8px */
            margin: 20px 0; /* Augmenté de 15px à 20px */
            text-align: center;
            border: 3px solid #FF6B35; /* Augmenté de 2px à 3px */
        }
        
        .qr-code {
            width: 160px; /* Augmenté de 140px à 160px */
            height: 160px; /* Augmenté de 140px à 160px */
            margin: 20px auto; /* Augmenté de 15px à 20px */
            border: 2px solid #ddd;
            border-radius: 8px; /* Augmenté de 6px à 8px */
            display: block;
        }
        
        .qr-placeholder {
            width: 160px; /* Augmenté de 140px à 160px */
            height: 160px; /* Augmenté de 140px à 160px */
            border: 3px dashed #FF6B35; /* Augmenté de 2px à 3px */
            border-radius: 10px; /* Augmenté de 8px à 10px */
            background: #f8f9fa;
            margin: 20px auto; /* Augmenté de 15px à 20px */
            display: table;
            color: #FF6B35;
            font-size: 14px; /* Augmenté de 12px à 14px */
        }
        
        .qr-content {
            display: table-cell;
            vertical-align: middle;
            text-align: center;
            font-weight: bold;
            line-height: 1.2;
            padding: 10px;
        }
        
        .qr-success {
            width: 160px; /* Augmenté de 140px à 160px */
            height: 160px; /* Augmenté de 140px à 160px */
            border: 3px solid #28a745; /* Augmenté de 2px à 3px */
            border-radius: 10px; /* Augmenté de 8px à 10px */
            background: #f8fff9;
            margin: 20px auto; /* Augmenté de 15px à 20px */
            display: table;
            color: #28a745;
            font-size: 14px; /* Augmenté de 12px à 14px */
        }
        
        .footer-info {
            text-align: center;
            margin-top: 25px; /* Augmenté de 20px à 25px */
            padding-top: 20px; /* Augmenté de 15px à 20px */
            border-top: 2px dashed #ddd;
            font-size: 14px; /* Augmenté de 12px à 14px */
            color: #666;
        }
        
        .verification-note {
            background: #e3f2fd;
            border: 2px solid #2196f3; /* Augmenté de 1px à 2px */
            border-radius: 8px; /* Augmenté de 6px à 8px */
            padding: 15px; /* Augmenté de 12px à 15px */
            margin-top: 20px; /* Augmenté de 15px à 20px */
            font-size: 15px; /* Augmenté de 13px à 15px */
            color: #1976d2;
            text-align: center;
        }
        
        .company-info {
            text-align: center;
            margin-top: 30px; /* Augmenté de 25px à 30px */
            padding: 20px; /* Augmenté de 15px à 20px */
            background: #f8f9fa;
            border-radius: 8px; /* Augmenté de 6px à 8px */
            font-size: 14px; /* Augmenté de 12px à 14px */
            color: #666;
        }
        
        .warning-box {
            background: #fff3cd;
            border: 2px solid #ffc107; /* Augmenté de 1px à 2px */
            border-radius: 8px; /* Augmenté de 6px à 8px */
            padding: 15px; /* Augmenté de 12px à 15px */
            margin: 20px 0; /* Augmenté de 15px à 20px */
            font-size: 15px; /* Augmenté de 13px à 15px */
            color: #856404;
        }
        
        .summary-page {
            page-break-before: always;
            padding: 30px;
            background: white;
        }
        
        .summary-header {
            background: linear-gradient(135deg, #1a237e, #2c3e50);
            color: white;
            padding: 25px;
            text-align: center;
            border-radius: 15px;
            margin-bottom: 30px;
        }
        
        .summary-title {
            font-size: 32px;
            font-weight: bold;
            margin: 0 0 10px 0;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .summary-subtitle {
            font-size: 20px;
            margin: 0;
            opacity: 0.9;
        }
        
        .summary-section {
            margin-bottom: 30px;
            padding: 20px;
            border: 2px solid #e9ecef;
            border-radius: 10px;
            background: #f8f9fa;
        }
        
        .summary-section h3 {
            color: #FF6B35;
            font-size: 20px;
            margin-bottom: 15px;
            border-bottom: 2px solid #FF6B35;
            padding-bottom: 10px;
        }
        
        .summary-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        
        .summary-table td {
            padding: 12px;
            border-bottom: 1px solid #ddd;
            font-size: 16px;
        }
        
        .summary-table .label {
            font-weight: bold;
            color: #333;
            width: 40%;
        }
        
        .summary-table .value {
            color: #666;
        }
        
        .summary-total {
            background: #28a745;
            color: white;
            font-weight: bold;
            font-size: 18px;
        }
        
        .contact-section {
            background: #e3f2fd;
            border: 2px solid #2196f3;
            border-radius: 10px;
            padding: 20px;
            text-align: center;
            font-size: 16px;
            color: #1976d2;
        }
        
        .contact-section h4 {
            color: #1976d2;
            margin-bottom: 15px;
            font-size: 20px;
        }
        
        /* Print styles */
        @media print {
            .ticket {
                page-break-inside: avoid;
                margin-bottom: 0;
            }
            
            .ticket:not(:last-child) {
                page-break-after: always;
            }
            
            .summary-page {
                page-break-before: always;
            }
        }
    </style>
</head>
<body>
    {{-- Boucle pour chaque billet --}}
    @foreach($order->tickets as $index => $ticket)
        <div class="ticket">
            <!-- En-tête du billet -->
            <div class="ticket-header">
                <div class="event-title">{{ $order->event->title ?? 'Événement' }}</div>
                <div class="event-subtitle">Billet d'entrée officiel</div>
            </div>
            
            <!-- Corps du billet -->
            <div class="ticket-body">
                <!-- Informations à gauche -->
                <div class="col-left">
                    <div class="info-group">
                        <span class="info-label">📅 Date et heure</span>
                        <div class="info-value fw-bold">
                            {{ $order->event->formatted_event_date ?? 'Date TBD' }}
                            @if($order->event->formatted_event_time)
                                à {{ $order->event->formatted_event_time }}
                            @endif
                        </div>
                    </div>
                    
                    <div class="info-group">
                        <span class="info-label">📍 Lieu</span>
                        <div class="info-value fw-bold">
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
                        // Force l'utilisation du nouveau service QR avec taille augmentée
                        $qrCodeGenerated = false;
                        $qrCodeBase64 = null;
                        
                        try {
                            // Utiliser explicitement le service QR corrigé avec taille plus grande
                            $qrService = app(\App\Services\QRCodeService::class);
                            
                            // Log pour debugging
                            \Log::info("Template PDF - Génération QR pour ticket: {$ticket->ticket_code}");
                            
                            // Essayer d'abord la méthode stylée avec taille plus grande
                            if (method_exists($qrService, 'generateStyledQR')) {
                                $qrCodeBase64 = $qrService->generateStyledQR($ticket, [
                                    'size' => 200,
                                    'margin' => 1,
                                    'color' => '1a1a1a',
                                    'bg_color' => 'ffffff'
                                ]);
                            }
                            
                            // Fallback avec service standard
                            if (!$qrCodeBase64 || strlen($qrCodeBase64) < 100) {
                                $qrCodeBase64 = $qrService->getOrGenerateTicketQR($ticket, 'base64');
                            }
                            
                            // Dernier fallback
                            if (!$qrCodeBase64 || strlen($qrCodeBase64) < 100) {
                                $qrCodeBase64 = $qrService->generateTicketQRBase64($ticket);
                            }
                            
                            if ($qrCodeBase64 && strlen($qrCodeBase64) > 100) {
                                $qrCodeGenerated = true;
                                \Log::info("Template PDF - QR généré avec succès pour: {$ticket->ticket_code}");
                            } else {
                                \Log::warning("Template PDF - QR non généré pour: {$ticket->ticket_code}");
                            }
                            
                        } catch (\Exception $e) {
                            \Log::error("Template PDF - Erreur QR pour ticket {$ticket->ticket_code}: " . $e->getMessage());
                        }
                    @endphp
                    
                    @if($qrCodeGenerated && $qrCodeBase64)
                        <!-- QR Code généré avec succès - SEULEMENT LE QR CODE -->
                        <img src="{{ $qrCodeBase64 }}" alt="QR Code" class="qr-code">
                    @else
                        <!-- Fallback si QR non généré -->
                        <div class="qr-placeholder">
                            <div class="qr-content">
                                📱 QR CODE<br>
                                EN COURS DE<br>
                                GÉNÉRATION
                            </div>
                        </div>
                    @endif
                    
                    <!-- Code du billet (plus grand) -->
                    <div class="ticket-code">{{ $ticket->ticket_code }}</div>
                    
                    <!-- Statut du billet -->
                    <div class="info-group">
                        <span class="info-label">📊 Statut</span>
                        <div class="info-value" style="color: #28a745;">
                            {{ $ticket->status === 'sold' ? '✅ VALIDE' : '⏳ EN ATTENTE' }}
                        </div>
                    </div>
                    
                    <!-- Prix (si disponible) -->
                    @if($ticket->ticketType && $ticket->ticketType->price)
                        <div class="info-group">
                            <span class="info-label">💰 Prix</span>
                            <div class="info-value">{{ number_format($ticket->ticketType->price) }} FCFA</div>
                        </div>
                    @endif
                </div>
            </div>
            
            <!-- Note de vérification -->
            <div class="verification-note">
                <strong>🔍 Vérification :</strong> Scannez le QR code à l'entrée ou visitez<br>
                <strong>{{ url('/verify-ticket/' . $ticket->ticket_code) }}</strong>
            </div>
            
            <!-- Informations importantes -->
            <div class="warning-box">
                <strong>⚠️ Important :</strong> 
                • Présentez ce billet à l'entrée
                • Un seul scan autorisé
                • Billet nominatif et incessible
                • Conservez ce document jusqu'à la fin de l'événement
            </div>
            
            <!-- Footer avec infos de l'entreprise -->
            <div class="footer-info">
                <strong>ClicBillet CI</strong> - Votre plateforme de billetterie en ligne<br>
                🌐 www.clicbillet.ci | 📧 contact@clicbillet.ci | 📞 +225 XX XX XX XX<br>
                <em>Billet généré le {{ now()->format('d/m/Y à H:i') }}</em>
            </div>
        </div>
        
        @if(!$loop->last)
            <div style="page-break-after: always;"></div>
        @endif
    @endforeach
    
    {{-- Page finale avec récapitulatif de la commande --}}
    <div class="summary-page">
        <div class="summary-header">
            <div class="summary-title">Récapitulatif de commande</div>
            <div class="summary-subtitle">{{ $order->order_number }}</div>
        </div>
        
        <div class="summary-section">
            <h3>📋 Informations de la commande</h3>
            <table class="summary-table">
                <tr>
                    <td class="label">Numéro de commande :</td>
                    <td class="value">{{ $order->order_number }}</td>
                </tr>
                <tr>
                    <td class="label">Date de commande :</td>
                    <td class="value">{{ $order->created_at->format('d/m/Y à H:i') }}</td>
                </tr>
                <tr>
                    <td class="label">Statut de paiement :</td>
                    <td class="value">
                        @if($order->payment_status === 'paid')
                            <span style="color: #28a745; font-weight: bold;">✅ PAYÉ</span>
                        @else
                            <span style="color: #ffc107; font-weight: bold;">⏳ EN ATTENTE</span>
                        @endif
                    </td>
                </tr>
                <tr>
                    <td class="label">Méthode de paiement :</td>
                    <td class="value">{{ $order->payment_method ?? 'Non définie' }}</td>
                </tr>
            </table>
        </div>
        
        <div class="summary-section">
            <h3>🎭 Événement</h3>
            <table class="summary-table">
                <tr>
                    <td class="label">Titre :</td>
                    <td class="value"><strong>{{ $order->event->title ?? 'N/A' }}</strong></td>
                </tr>
                <tr>
                    <td class="label">Date :</td>
                    <td class="value">{{ $order->event->formatted_event_date ?? 'Date TBD' }}</td>
                </tr>
                <tr>
                    <td class="label">Heure :</td>
                    <td class="value">{{ $order->event->formatted_event_time ?? 'Heure TBD' }}</td>
                </tr>
                <tr>
                    <td class="label">Lieu :</td>
                    <td class="value">{{ $order->event->venue ?? 'Lieu TBD' }}</td>
                </tr>
                @if($order->event->address)
                <tr>
                    <td class="label">Adresse :</td>
                    <td class="value">{{ $order->event->address }}</td>
                </tr>
                @endif
            </table>
        </div>
        
        <div class="summary-section">
            <h3>🎫 Billets commandés</h3>
            <table class="summary-table">
                @foreach($order->orderItems as $item)
                <tr>
                    <td class="label">{{ $item->ticketType->name ?? 'Billet' }} (x{{ $item->quantity }}) :</td>
                    <td class="value">{{ number_format($item->total_price) }} FCFA</td>
                </tr>
                @endforeach
                <tr class="summary-total">
                    <td class="label">TOTAL :</td>
                    <td class="value">{{ number_format($order->total_amount) }} FCFA</td>
                </tr>
            </table>
        </div>
        
        <div class="summary-section">
            <h3>👤 Informations client</h3>
            <table class="summary-table">
                <tr>
                    <td class="label">Nom :</td>
                    <td class="value">{{ $order->user->name }}</td>
                </tr>
                <tr>
                    <td class="label">Email :</td>
                    <td class="value">{{ $order->user->email }}</td>
                </tr>
                @if($order->user->phone)
                <tr>
                    <td class="label">Téléphone :</td>
                    <td class="value">{{ $order->user->phone }}</td>
                </tr>
                @endif
                <tr>
                    <td class="label">Client depuis :</td>
                    <td class="value">{{ $order->user->created_at->format('d/m/Y') }}</td>
                </tr>
            </table>
        </div>
        
        <div class="contact-section">
            <h4>📞 Besoin d'aide ?</h4>
            <p><strong>Contactez notre service client :</strong></p>
            <p>
                📧 <strong>support@clicbillet.ci</strong><br>
                📞 <strong>+225 XX XX XX XX</strong><br>
                💬 <strong>Chat en ligne sur www.clicbillet.ci</strong>
            </p>
            <p><em>Notre équipe est disponible du lundi au vendredi de 8h à 18h</em></p>
        </div>
        
        <div class="company-info">
            <strong>ClicBillet CI</strong><br>
            Votre partenaire de confiance pour tous vos événements<br>
            <em>Merci de votre confiance !</em><br><br>
            <small>Document généré automatiquement le {{ now()->format('d/m/Y à H:i') }}</small>
        </div>
    </div>
</body>
</html>