<?php

namespace App\Mail;

use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use App\Models\Order;

class PaymentConfirmation extends \App\Mail\Concerns\TransactionalMail
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
            subject: ($this->order->isFree() ? '✅ Réservation confirmée' : '✅ Paiement confirmé') . ' - Vos billets #' . $this->order->order_number,
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

    /** « Paiement confirmé » n'a de sens que si la commande est encore payée au moment de l'envoi (état frais). */
    protected function shouldSend(): bool
    {
        $fresh = \App\Models\Order::find($this->order->id);

        return $fresh !== null && $fresh->isPaid();
    }

    /**
     * Pièce jointe : le PDF des billets (jamais avant paiement). Une panne de génération n'est PAS avalée : elle remonte
     * (TransactionalMail la convertit en échec au message expurgé), donc le job est retenté au lieu de partir sans billets.
     */
    public function attachments(): array
    {
        // Jamais de billets (QR) joints avant la confirmation du paiement.
        if (! $this->order->isPaid()) {
            return [];
        }

        // Charger les relations pour le PDF
        $this->order->loadMissing(\App\Services\TicketPdfService::RELATIONS);

        // Même gabarit que le téléchargement (une page A4 par billet)
        $pdf = app(\App\Services\TicketPdfService::class)->forOrder($this->order);
        $fileName = 'Billets-' . $this->order->order_number . '.pdf';

        \Log::info("PDF joint à l'email", [
            'order_id' => $this->order->id,
            'file_name' => $fileName,
            'is_guest' => $this->order->user->is_guest,
        ]);

        // Pièce jointe en mémoire : aucun fichier (avec des QR valides) n'est écrit sur le disque.
        return [
            \Illuminate\Mail\Mailables\Attachment::fromData(fn () => $pdf->output(), $fileName)
                ->withMime('application/pdf'),
        ];
    }
}
