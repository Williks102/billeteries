<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use App\Models\Order;

class AdminNewOrder extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public Order $order
    ) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
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