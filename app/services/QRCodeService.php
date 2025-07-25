namespace App\Services;

class QrCodeService
{
    /**
     * Générer QR code pour un billet
     */
    public function generateForTicket(Ticket $ticket)
    {
        return $ticket->generateQrCode();
    }

    /**
     * Générer QR codes pour tous les billets d'une commande
     */
    public function generateForOrder(Order $order)
    {
        $tickets = $order->tickets;
        $results = [];
        
        foreach ($tickets as $ticket) {
            $results[$ticket->id] = $ticket->generateQrCode();
        }
        
        return $results;
    }

    /**
     * Régénérer tous les QR codes manquants
     */
    public function regenerateMissing()
    {
        $tickets = Ticket::whereNull('qr_code_path')
                        ->orWhere(function($query) {
                            $query->whereNotNull('qr_code_path')
                                  ->whereRaw('NOT EXISTS (SELECT 1 FROM storage WHERE path = qr_code_path)');
                        })
                        ->get();
        
        $count = 0;
        foreach ($tickets as $ticket) {
            if ($ticket->generateQrCode()) {
                $count++;
            }
        }
        
        return $count;
    }
}