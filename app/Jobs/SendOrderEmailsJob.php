<?php

namespace App\Jobs;

use App\Models\Order;
use App\Services\EmailService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendOrderEmailsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(public int $orderId)
    {
        $this->onQueue('emails');
    }

    public function handle(EmailService $emailService): void
    {
        $order = Order::with(['user', 'event.promoteur', 'tickets.ticketType', 'orderItems.ticketType'])
            ->find($this->orderId);

        if (!$order) {
            Log::warning('SendOrderEmailsJob ignoré : commande introuvable', [
                'order_id' => $this->orderId,
            ]);

            return;
        }

        $emailService->sendAllOrderEmails($order);
    }
}
