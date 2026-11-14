<?php

namespace App\Mail;

use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use App\Models\Order;

class OrderConfirmation extends \App\Mail\Concerns\TransactionalMail
{
    /**
     * Create a new message instance.
     */
    public function __construct(
        public Order $order
    ) {}

    public function recipientEmail(): ?string
    {
        return $this->order->user?->email;
    }

    protected function logContext(): array
    {
        return ['order_id' => $this->order->id];
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            to: $this->recipients(),
            subject: '🎫 Confirmation de votre commande #' . $this->order->order_number,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.order-confirmation',
            with: [
                'order' => $this->order,
                'customer' => $this->order->user,
                'event' => $this->order->event,
                'total' => number_format($this->order->total_amount, 0, ',', ' ') . ' FCFA'
            ]
        );
    }

    /**
     * Get the attachments for the message.
     */
    public function attachments(): array
    {
        return [];
    }
}