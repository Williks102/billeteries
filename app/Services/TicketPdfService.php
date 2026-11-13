<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Ticket;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;

/**
 * PDF des billets : une page A4 par billet, gabarit unique `acheteur.tickets-pdf` (composant x-ticket-sheet).
 * Utilisé par le téléchargement d'une commande, celui d'un seul billet et la pièce jointe de PaymentConfirmation.
 * Les requêtes (chargement des relations, QR) sont faites ici : la vue ne fait qu'afficher.
 * Règle : jamais de QR ni de PDF pour un billet non émis / une commande non payée.
 */
class TicketPdfService
{
    public const RELATIONS = ['event.promoteur', 'user', 'tickets.ticketType', 'tickets.orderItem.order'];

    /** PDF de tous les billets (émis) d'une commande payée. */
    public function forOrder(Order $order)
    {
        return $this->render($order, null);
    }

    /** PDF d'un seul billet ; « Billet N sur M » garde la numérotation de la commande. */
    public function forTicket(Order $order, Ticket $ticket)
    {
        return $this->render($order, $ticket);
    }

    public function orderFileName(Order $order): string
    {
        return Str::slug('billets-' . $order->order_number . '-' . ($order->event->title ?? '')) . '.pdf';
    }

    public function ticketFileName(Order $order, Ticket $ticket): string
    {
        return Str::slug('billet-' . $ticket->ticket_code . '-' . ($order->event->title ?? '')) . '.pdf';
    }

    /**
     * Données de chaque fiche billet, prêtes à afficher (aucune requête dans la vue).
     *
     * @return list<array<string, mixed>>
     */
    public function sheets(Order $order, ?Ticket $only = null): array
    {
        $order->loadMissing(self::RELATIONS);
        $event = $order->event;
        $all = $order->tickets->sortBy('id')->values();
        $total = $all->count();
        $orderedAt = $order->created_at?->copy()->setTimezone('Africa/Abidjan')->locale('fr');

        $sheets = [];
        foreach ($all as $i => $ticket) {
            if ($only !== null && $ticket->id !== $only->id) {
                continue;
            }

            $issued = $order->isPaid() && $ticket->isIssued();
            $type = $ticket->ticketType;
            $sheets[] = [
                'order_number' => $order->order_number,
                'ticket_code' => $ticket->ticket_code,
                'position' => $i + 1,
                'total' => $total,
                'qr' => $issued ? $ticket->qrDataUri(400) : null,
                'event_title' => (string) ($event->title ?? ''),
                'date' => $event?->long_date_label ?? '',
                'time' => $event?->time_range_label ?? '',
                'venue' => trim((string) ($event->venue ?? '')),
                'address' => trim((string) ($event->address ?? '')),
                'organizer' => trim((string) ($event->promoteur->name ?? '')),
                'type_name' => $type ? trim((string) $type->name) : '',
                'price' => $type ? \App\Helpers\CurrencyHelper::formatPrice($type->price) : '',
                'holder' => trim((string) ($order->user->name ?? '')),
                'ordered_at' => $orderedAt ? $orderedAt->translatedFormat('j F Y \à H\hi') : '',
            ];
        }

        return $sheets;
    }

    private function render(Order $order, ?Ticket $only)
    {
        return Pdf::loadView('acheteur.tickets-pdf', ['sheets' => $this->sheets($order, $only)])
            ->setPaper('A4', 'portrait')
            ->setOptions(['dpi' => 96, 'defaultFont' => 'sans-serif']);
    }
}
