<?php

namespace Tests\Fakes;

use App\Models\Ticket;
use App\Services\QRCodeService;

/**
 * QRCodeService espion : enregistre les billets pour lesquels un QR est généré et le niveau de transaction DB (sans disque).
 * Depuis S-6 le vrai service est local (aucun réseau) : ce faux ne sert plus qu'à observer les appels.
 */
class FakeQRCodeService extends QRCodeService
{
    /** @var array<int, string> codes des billets pour lesquels un QR a été demandé */
    public array $requested = [];

    /** @var array<int, int> niveau de transaction DB au moment de chaque demande (doit être 0 : hors transaction) */
    public array $transactionLevels = [];

    /** 1x1 PNG transparent. */
    private const PNG = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==';

    public function generateTicketQRBase64(Ticket $ticket, $size = 200)
    {
        $this->requested[] = (string) $ticket->ticket_code;
        $this->transactionLevels[] = \Illuminate\Support\Facades\DB::transactionLevel();

        return self::PNG;
    }

    public function generateStyledQR(Ticket $ticket, $options = [])
    {
        return $this->generateTicketQRBase64($ticket);
    }

    public function generateAdvancedTicketQR(Ticket $ticket, $includeEventInfo = false)
    {
        return $this->generateTicketQRBase64($ticket);
    }
}
