<?php

namespace App\Mail;

use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use App\Models\Order;
use App\Models\User;

class AdminNewOrder extends \App\Mail\Concerns\TransactionalMail
{
    /**
     * Create a new message instance.
     */
    public function __construct(
        public Order $order,
        public ?User $admin = null,
    ) {}

    public function recipientEmail(): ?string
    {
        return $this->admin?->email;
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
            subject: '🔔 [ClicBillet] Nouvelle commande #' . $this->order->order_number,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        // Statistiques rapides pour l'admin
        $todayOrders = \App\Models\Order::whereDate('created_at', today())->count();
        $todayRevenue = \App\Models\Order::where('payment_status', 'paid')
                                        ->whereDate('created_at', today())
                                        ->sum('total_amount');
        
        return new Content(
            view: 'emails.admin-new-order',
            with: [
                'order' => $this->order,
                'customer' => $this->order->user,
                'event' => $this->order->event,
                'promoteur' => $this->order->event->promoteur,
                'total' => number_format($this->order->total_amount, 0, ',', ' ') . ' FCFA',
                'stats' => [
                    'today_orders' => $todayOrders,
                    'today_revenue' => number_format($todayRevenue, 0, ',', ' ') . ' FCFA'
                ]
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