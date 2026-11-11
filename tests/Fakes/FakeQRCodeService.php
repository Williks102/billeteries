<?php

namespace Tests\Fakes;

use App\Models\Ticket;
use App\Services\QRCodeService;

/**
 * QRCodeService de test (I2) : aucun réseau, aucun disque. Le vrai service appelle des API HTTP
 * externes (Google Charts, QR Server, timeout 8 s) une fois par billet, ce qui rendait la suite
 * 25 fois plus lente (2 min 27 s contre 5 s) et dépendante du réseau.
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

    public function generateAndSaveTicketQR(Ticket $ticket, $size = 200)
    {
        $this->requested[] = (string) $ticket->ticket_code;
        $this->transactionLevels[] = \Illuminate\Support\Facades\DB::transactionLevel();

        return '/fake-qr/' . $ticket->ticket_code . '.png';
    }

    public function getTicketQRFromCache(Ticket $ticket)
    {
        return null;
    }

    public function getOrGenerateTicketQR(Ticket $ticket, $format = 'base64')
    {
        return $this->generateTicketQRBase64($ticket);
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
