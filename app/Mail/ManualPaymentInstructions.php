<?php

namespace App\Mail;

use App\Models\Order;
use App\Services\CheckoutService;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ManualPaymentInstructions extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Order $order) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Instructions de paiement - commande #' . $this->order->order_number,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.manual-payment-instructions',
            with: [
                'order' => $this->order,
                'event' => $this->order->event,
                'info' => CheckoutService::manualPaymentInfo($this->order->payment_method),
                'total' => number_format($this->order->total_amount, 0, ',', ' ') . ' FCFA',
            ]
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
