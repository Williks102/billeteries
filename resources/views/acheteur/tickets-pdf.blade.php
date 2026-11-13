{{-- PDF des billets (DomPDF) : un billet par page A4, mise en page par tableaux (pas de flex/grid/ombre).
     Données préparées par App\Services\TicketPdfService : aucune requête ici. --}}
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>E-Billet - {{ $sheets[0]['order_number'] ?? '' }}</title>
    <style>
        @page { margin: 32pt 36pt; size: A4; }
        body { margin: 0; padding: 0; font-family: Helvetica, Arial, sans-serif; font-size: 11pt; color: #0f172a; }
        table { border-collapse: collapse; }
        .page-break { page-break-before: always; }
    </style>
</head>
<body>
    @foreach($sheets as $index => $sheet)
        <div class="{{ $index > 0 ? 'page-break' : '' }}">
            <x-ticket-sheet :sheet="$sheet" />
        </div>
    @endforeach
</body>
</html>
