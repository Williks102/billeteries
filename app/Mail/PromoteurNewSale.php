<?php

namespace App\Mail;

use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use App\Models\Order;

class PromoteurNewSale extends \App\Mail\Concerns\TransactionalMail
{
    /**
     * Create a new message instance.
     */
    public function __construct(
        public Order $order
    ) {}

    public function recipientEmail(): ?string
    {
        return $this->order->event?->promoteur?->email;
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
            subject: '💰 Nouvelle vente pour votre événement : ' . $this->order->event->title,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        $commission = $this->order->commission;
        
        return new Content(
            view: 'emails.promoteur-new-sale',
            with: [
                'order' => $this->order,
                'promoteur' => $this->order->event->promoteur,
                'event' => $this->order->event,
                'customer' => $this->order->user,
                'commission' => $commission,
                'total' => number_format($this->order->total_amount, 0, ',', ' ') . ' FCFA',
                'commission_amount' => $commission ? number_format($commission->commission_amount, 0, ',', ' ') . ' FCFA' : '0 FCFA',
                'net_amount' => $commission ? number_format($commission->net_amount, 0, ',', ' ') . ' FCFA' : '0 FCFA'
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