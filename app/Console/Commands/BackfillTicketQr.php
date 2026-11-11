<?php

namespace App\Console\Commands;

use App\Models\Ticket;
use Illuminate\Console\Command;

/**
 * Renseigne tickets.qr_code (data-URI PNG) pour les billets ÉMIS (vendus/utilisés, commande payée) qui n'en ont pas
 * encore, ou dont la valeur n'est pas une data-URI (anciennes URL). Idempotent : sans effet une fois à jour.
 */
class BackfillTicketQr extends Command
{
    protected $signature = 'tickets:generate-qr';

    protected $description = 'Génère le QR (data-URI) des billets émis qui n\'en ont pas (idempotent)';

    public function handle(): int
    {
        $done = 0;
        $failed = 0;

        Ticket::whereIn('status', ['sold', 'used'])
            ->where(function ($q) {
                $q->whereNull('qr_code')->orWhere('qr_code', 'not like', 'data:image/png;base64,%');
            })
            ->chunkById(200, function ($tickets) use (&$done, &$failed) {
                foreach ($tickets as $ticket) {
                    if (!$ticket->isIssued()) {
                        continue; // commande non payée : pas de QR
                    }
                    $ticket->qrDataUri() !== null ? $done++ : $failed++;
                }
            });

        $this->info("QR générés : {$done}");
        if ($failed > 0) {
            $this->warn("Échecs : {$failed}");
        }

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}
