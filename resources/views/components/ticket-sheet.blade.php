@props(['sheet'])
{{-- Fiche d'un billet pour le PDF (DomPDF) : tableaux et styles simples, polices intégrées, couleurs de la charte. --}}
<table width="100%" cellpadding="0" cellspacing="0" style="border-bottom: 3pt solid #C2410C;">
    <tr>
        <td style="padding: 0 0 10pt 0; font-size: 26pt; font-weight: bold;">
            <span style="color: #C2410C;">Clic</span><span style="color: #0f172a;">Billet</span>
        </td>
        <td align="right" style="padding: 0 0 10pt 0; font-size: 11pt; color: #475569;">
            Commande <strong style="color: #0f172a;">#{{ $sheet['order_number'] }}</strong>
        </td>
    </tr>
</table>

<table cellpadding="0" cellspacing="0" style="margin-top: 14pt;">
    <tr>
        <td style="background: #C2410C; color: #ffffff; font-size: 12pt; font-weight: bold; letter-spacing: 2pt; padding: 6pt 16pt;">E-BILLET</td>
    </tr>
</table>

<table width="100%" cellpadding="0" cellspacing="0" style="border: 1pt solid #cbd5e1; border-top: 4pt solid #0f172a; margin-top: 0;">
    <tr>
        <td style="padding: 18pt 20pt 6pt 20pt; font-size: {{ mb_strlen($sheet['event_title']) > 70 ? '17' : '24' }}pt; font-weight: bold; line-height: 1.2; color: #0f172a;">
            {{ $sheet['event_title'] }}
        </td>
    </tr>
    @if($sheet['date'] !== '' || $sheet['time'] !== '')
        <tr>
            <td style="padding: 4pt 20pt 0 20pt; font-size: 14pt; font-weight: bold; color: #C2410C;">
                {{ $sheet['date'] }}@if($sheet['time'] !== '') &nbsp;&middot;&nbsp; {{ $sheet['time'] }}@endif
            </td>
        </tr>
    @endif
    @if($sheet['venue'] !== '' || $sheet['address'] !== '')
        <tr>
            <td style="padding: 6pt 20pt 0 20pt; font-size: 12pt; color: #0f172a;">
                {{ $sheet['venue'] }}@if($sheet['venue'] !== '' && $sheet['address'] !== '') <br> @endif
                <span style="color: #475569;">{{ $sheet['address'] }}</span>
            </td>
        </tr>
    @endif
    @if($sheet['organizer'] !== '')
        <tr>
            <td style="padding: 6pt 20pt 0 20pt; font-size: 11pt; color: #475569;">Organisé par {{ $sheet['organizer'] }}</td>
        </tr>
    @endif
    <tr>
        <td style="padding: 14pt 20pt 18pt 20pt;">
            <table width="100%" cellpadding="0" cellspacing="0" style="border-top: 1pt solid #e2e8f0;">
                <tr>
                    <td style="padding-top: 10pt; font-size: 13pt; font-weight: bold; color: #0f172a;">
                        @if($sheet['type_name'] !== '') {{ $sheet['type_name'] }} @endif
                        @if($sheet['price'] !== '') <span style="color: #C2410C;">@if($sheet['type_name'] !== '')&nbsp;&middot;&nbsp;@endif{{ $sheet['price'] }}</span> @endif
                    </td>
                    <td align="right" style="padding-top: 10pt; font-size: 12pt; color: #475569;">
                        Billet {{ $sheet['position'] }} sur {{ $sheet['total'] }}
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>

<table width="100%" cellpadding="0" cellspacing="0" style="margin-top: 18pt;">
    <tr>
        <td align="center">
            @if($sheet['qr'])
                <table align="center" cellpadding="0" cellspacing="0" style="background: #ffffff; border: 1pt solid #cbd5e1;">
                    <tr>
                        <td style="padding: 18pt; background: #ffffff;">
                            <img src="{{ $sheet['qr'] }}" alt="QR code du billet {{ $sheet['ticket_code'] }}" width="240" height="240" style="width: 240px; height: 240px;">
                        </td>
                    </tr>
                </table>
                <div style="font-size: 12pt; margin-top: 8pt; color: #0f172a;">Présentez ce QR code à l'entrée</div>
            @else
                <table align="center" cellpadding="0" cellspacing="0" style="border: 1pt dashed #94a3b8;">
                    <tr>
                        <td align="center" style="width: 250pt; height: 120pt; font-size: 14pt; font-weight: bold; color: #475569;">QR indisponible</td>
                    </tr>
                </table>
            @endif
            @if($sheet['qr'])
            <div style="font-size: 10pt; color: #475569; margin-top: 10pt;">Référence du billet</div>
            <div style="font-family: 'Courier New', monospace; font-size: 15pt; font-weight: bold; color: #0f172a; letter-spacing: 1pt;">{{ $sheet['ticket_code'] }}</div>
            @endif
        </td>
    </tr>
</table>

<table width="100%" cellpadding="0" cellspacing="0" style="margin-top: 18pt; background: #f1f5f9;">
    <tr>
        <td width="50%" valign="top" style="padding: 12pt 16pt;">
            <div style="font-size: 9pt; font-weight: bold; color: #475569; letter-spacing: 1pt;">DÉTENTEUR DU BILLET</div>
            <div style="font-size: 14pt; font-weight: bold; color: #0f172a; margin-top: 3pt;">{{ $sheet['holder'] }}</div>
        </td>
        <td width="50%" valign="top" style="padding: 12pt 16pt;">
            <div style="font-size: 9pt; font-weight: bold; color: #475569; letter-spacing: 1pt;">COMMANDE</div>
            <div style="font-size: 14pt; font-weight: bold; color: #0f172a; margin-top: 3pt;">#{{ $sheet['order_number'] }}</div>
            @if($sheet['ordered_at'] !== '')
                <div style="font-size: 10pt; color: #475569; margin-top: 2pt;">Passée le {{ $sheet['ordered_at'] }}</div>
            @endif
        </td>
    </tr>
</table>

<table width="100%" cellpadding="0" cellspacing="0" style="margin-top: 14pt;">
    <tr>
        <td style="font-size: 10pt; line-height: 1.5; color: #334155;">
            <strong style="color: #0f172a;">Conditions.</strong>
            Ce billet est valable une seule fois, pour l'événement indiqué ci-dessus.
            <strong>Billet nominatif et incessible. Pièce d'identité requise à l'entrée.</strong>
        </td>
    </tr>
</table>

<table width="100%" cellpadding="0" cellspacing="0" style="margin-top: 16pt; border-top: 1pt solid #e2e8f0;">
    <tr>
        <td style="padding-top: 8pt; font-size: 10pt; color: #475569;">Ce billet est personnel. Ne partagez pas ce QR code.</td>
        <td align="right" style="padding-top: 8pt; font-size: 10pt; font-weight: bold; color: #C2410C;">ClicBillet</td>
    </tr>
</table>
