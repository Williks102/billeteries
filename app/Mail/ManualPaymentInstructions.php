<?php

namespace App\Mail;

use App\Models\Order;
use App\Services\CheckoutService;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class ManualPaymentInstructions extends \App\Mail\Concerns\TransactionalMail
{
    public function __construct(public Order $order) {}

    public function recipientEmail(): ?string
    {
        return $this->order->billing_email ?: $this->order->user?->email;
    }

    protected function logContext(): array
    {
        return ['order_id' => $this->order->id];
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            to: $this->recipients(),
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
