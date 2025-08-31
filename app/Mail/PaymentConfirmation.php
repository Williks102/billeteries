<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use App\Models\Order;

class PaymentConfirmation extends Mailable
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
            subject: '✅ Paiement confirmé - Vos billets #' . $this->order->order_number,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.payment-confirmation',
            with: [
                'order' => $this->order,
                'customer' => $this->order->user,
                'event' => $this->order->event,
                'tickets' => $this->order->tickets,
                'total' => number_format($this->order->total_amount, 0, ',', ' ') . ' FCFA'
            ]
        );
    }

    /**
     * Get the attachments for the message.
     */
    public function attachments(): array
{
    try {
        // Charger les relations pour le PDF
        $this->order->load(['event.category', 'tickets.ticketType', 'orderItems.ticketType', 'user']);
        
        // Générer le PDF avec votre template existant
        $pdf = \PDF::loadView('acheteur.tickets-pdf', ['order' => $this->order]);
        $pdf->setPaper('A4', 'portrait');
        
        // Nom du fichier
        $fileName = 'Billets-' . $this->order->order_number . '.pdf';
        
        // Fichier temporaire
        $tempPath = storage_path('app/temp/' . $fileName);
        
        // Créer le dossier si nécessaire
        if (!file_exists(dirname($tempPath))) {
            mkdir(dirname($tempPath), 0755, true);
        }
        
        // Sauvegarder le PDF
        file_put_contents($tempPath, $pdf->output());
        
        \Log::info("PDF joint à l'email", [
            'order_id' => $this->order->id,
            'file_name' => $fileName,
            'is_guest' => $this->order->user->is_guest
        ]);
        
        return [
            \Illuminate\Mail\Mailables\Attachment::fromPath($tempPath)
                ->as($fileName)
                ->withMime('application/pdf')
        ];
        
    } catch (\Exception $e) {
        \Log::error("Erreur PDF email", [
            'order_id' => $this->order->id,
            'error' => $e->getMessage()
        ]);
        return []; // Pas de PDF en cas d'erreur
    }

}
}