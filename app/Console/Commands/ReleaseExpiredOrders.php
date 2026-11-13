<?php

namespace App\Console\Commands;

use App\Models\Order;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ReleaseExpiredOrders extends Command
{
    protected $signature = 'orders:release-expired';

    protected $description = 'Libère le stock des commandes pending/processing dont la réservation a expiré';

    public function handle(): int
    {
        $released = 0;

        Order::expiredReservations()->orderBy('id')->chunkById(100, function ($orders) use (&$released) {
            foreach ($orders as $order) {
                // releaseStock() relit la commande sous verrou : une commande payée entre-temps n'est pas touchée
                if ($order->releaseStock(true)) {
                    $released++;
                    Log::info('Réservation expirée libérée', ['order_id' => $order->id]);
                }
            }
        });

        $this->info("Commandes libérées : {$released}");

        return self::SUCCESS;
    }
}
