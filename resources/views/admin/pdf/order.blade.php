{{-- resources/views/admin/pdf/order.blade.php --}}
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Commande #{{ $order->order_number ?? $order->id }}</title>
    <style>
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 12px;
            line-height: 1.4;
            color: #333;
            margin: 0;
            padding: 20px;
        }
        
        .header {
            border-bottom: 2px solid #ff6b35;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        
        .logo {
            font-size: 24px;
            font-weight: bold;
            color: #ff6b35;
            margin-bottom: 5px;
        }
        
        .company-info {
            font-size: 10px;
            color: #666;
        }
        
        .order-header {
            display: table;
            width: 100%;
            margin-bottom: 30px;
        }
        
        .order-info, .customer-info {
            display: table-cell;
            width: 48%;
            vertical-align: top;
        }
        
        .customer-info {
            text-align: right;
        }
        
        .section-title {
            font-size: 14px;
            font-weight: bold;
            color: #ff6b35;
            margin-bottom: 10px;
            border-bottom: 1px solid #ddd;
            padding-bottom: 5px;
        }
        
        .info-row {
            margin-bottom: 5px;
        }
        
        .label {
            font-weight: bold;
            display: inline-block;
            width: 120px;
        }
        
        .table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        
        .table th,
        .table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        
        .table th {
            background-color: #f8f9fa;
            font-weight: bold;
            color: #333;
        }
        
        .table-total {
            margin-top: 20px;
            float: right;
            width: 300px;
        }
        
        .table-total td {
            border: none;
            padding: 5px 0;
        }
        
        .total-label {
            font-weight: bold;
            text-align: right;
            padding-right: 20px;
        }
        
        .total-amount {
            text-align: right;
            font-weight: bold;
        }
        
        .final-total {
            border-top: 2px solid #ff6b35;
            font-size: 14px;
            color: #ff6b35;
        }
        
        .status-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
        }
        
        .status-paid {
            background-color: #d4edda;
            color: #155724;
        }
        
        .status-pending {
            background-color: #fff3cd;
            color: #856404;
        }
        
        .status-failed {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        .footer {
            margin-top: 50px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            font-size: 10px;
            color: #666;
            text-align: center;
        }
        
        .page-break {
            page-break-after: always;
        }
        
        @media print {
            body {
                margin: 0;
                padding: 15px;
            }
        }
    </style>
</head>
<body>
    {{-- En-tête --}}
    <div class="header">
        <div class="logo">ClicBillet CI</div>
        <div class="company-info">
            Plateforme de billetterie en ligne<br>
            Email: support@clicbillet.ci | Tél: +225 XX XX XX XX XX
        </div>
    </div>

    {{-- Informations commande et client --}}
    <div class="order-header">
        <div class="order-info">
            <div class="section-title">Informations de la commande</div>
            <div class="info-row">
                <span class="label">Numéro:</span> 
                #{{ $order->order_number ?? $order->id }}
            </div>
            <div class="info-row">
                <span class="label">Date:</span> 
                {{ $order->created_at->format('d/m/Y à H:i') }}
            </div>
            <div class="info-row">
                <span class="label">Statut:</span>
                @switch($order->payment_status)
                    @case('paid')
                        <span class="status-badge status-paid">Payée</span>
                        @break
                    @case('pending')
                        <span class="status-badge status-pending">En attente</span>
                        @break
                    @case('failed')
                        <span class="status-badge status-failed">Échouée</span>
                        @break
                    @default
                        <span class="status-badge">{{ $order->payment_status }}</span>
                @endswitch
            </div>
            @if($order->payment_method)
                <div class="info-row">
                    <span class="label">Méthode:</span> 
                    {{ ucfirst($order->payment_method) }}
                </div>
            @endif
        </div>

        <div class="customer-info">
            <div class="section-title">Informations client</div>
            @if($order->user)
                <div class="info-row">
                    <span class="label">Nom:</span> 
                    {{ $order->user->name }}
                </div>
                <div class="info-row">
                    <span class="label">Email:</span> 
                    {{ $order->user->email }}
                </div>
                @if($order->user->phone)
                    <div class="info-row">
                        <span class="label">Téléphone:</span> 
                        {{ $order->user->phone }}
                    </div>
                @endif
            @else
                <div class="info-row">Client non identifié</div>
            @endif
        </div>
    </div>

    {{-- Détails de l'événement --}}
    @if($order->event)
        <div class="section-title">Événement</div>
        <div class="info-row">
            <span class="label">Titre:</span> 
            {{ $order->event->title }}
        </div>
        <div class="info-row">
            <span class="label">Date:</span> 
            {{ $order->event->start_date ? $order->event->start_date->format('d/m/Y à H:i') : 'Non définie' }}
        </div>
        @if($order->event->venue)
            <div class="info-row">
                <span class="label">Lieu:</span> 
                {{ $order->event->venue }}
            </div>
        @endif
    @endif

    {{-- Détails des articles commandés --}}
    <div class="section-title">Articles commandés</div>
    <table class="table">
        <thead>
            <tr>
                <th>Type de billet</th>
                <th>Prix unitaire</th>
                <th>Quantité</th>
                <th>Sous-total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($order->orderItems as $item)
                <tr>
                    <td>
                        {{ $item->ticketType->name ?? 'Type non défini' }}
                        @if($item->ticketType->description)
                            <br><small style="color: #666;">{{ $item->ticketType->description }}</small>
                        @endif
                    </td>
                    <td>{{ number_format($item->unit_price) }} FCFA</td>
                    <td>{{ $item->quantity }}</td>
                    <td>{{ number_format($item->total_price) }} FCFA</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    {{-- Récapitulatif des totaux --}}
    <table class="table-total">
        <tr>
            <td class="total-label">Sous-total:</td>
            <td class="total-amount">{{ number_format($order->subtotal ?? 0) }} FCFA</td>
        </tr>
        @if($order->platform_fee > 0)
            <tr>
                <td class="total-label">Frais de plateforme:</td>
                <td class="total-amount">{{ number_format($order->platform_fee) }} FCFA</td>
            </tr>
        @endif
        @if($order->payment_fee > 0)
            <tr>
                <td class="total-label">Frais de paiement:</td>
                <td class="total-amount">{{ number_format($order->payment_fee) }} FCFA</td>
            </tr>
        @endif
        @if($order->discount > 0)
            <tr>
                <td class="total-label">Remise:</td>
                <td class="total-amount">-{{ number_format($order->discount) }} FCFA</td>
            </tr>
        @endif
        <tr class="final-total">
            <td class="total-label">TOTAL:</td>
            <td class="total-amount">{{ number_format($order->total_amount) }} FCFA</td>
        </tr>
    </table>

    <div style="clear: both;"></div>

    {{-- Liste des billets --}}
    @if($order->tickets->count() > 0)
        <div style="margin-top: 40px;">
            <div class="section-title">Billets générés</div>
            <table class="table">
                <thead>
                    <tr>
                        <th>Code du billet</th>
                        <th>Type</th>
                        <th>Statut</th>
                        <th>Date d'utilisation</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($order->tickets as $ticket)
                        <tr>
                            <td>{{ $ticket->ticket_code }}</td>
                            <td>{{ $ticket->orderItem->ticketType->name ?? 'N/A' }}</td>
                            <td>
                                @switch($ticket->status)
                                    @case('available')
                                        <span class="status-badge status-pending">Disponible</span>
                                        @break
                                    @case('sold')
                                        <span class="status-badge status-paid">Vendu</span>
                                        @break
                                    @case('used')
                                        <span class="status-badge status-paid">Utilisé</span>
                                        @break
                                    @case('cancelled')
                                        <span class="status-badge status-failed">Annulé</span>
                                        @break
                                    @default
                                        <span class="status-badge">{{ $ticket->status }}</span>
                                @endswitch
                            </td>
                            <td>
                                {{ $ticket->used_at ? $ticket->used_at->format('d/m/Y H:i') : '-' }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    {{-- Pied de page --}}
    <div class="footer">
        <p>
            <strong>ClicBillet CI</strong> - Votre plateforme de billetterie de confiance<br>
            Document généré le {{ now()->format('d/m/Y à H:i') }}
        </p>
        <p style="font-size: 9px; margin-top: 10px;">
            Ce document constitue un justificatif officiel de votre commande. 
            Conservez-le précieusement pour vos dossiers.
        </p>
    </div>
</body>
</html>