<?php

namespace App\Services;

use App\Models\Commission;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Ticket;
use App\Models\TicketType;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Logique de commande partagée entre CheckoutController et GuestCheckoutController
 * (prix serveur, réservation de stock, billets réservés, init paiement avec libération en cas d'échec).
 */
class CheckoutService
{
    public const SERVICE_FEE = 500;

    /**
     * Regroupe le panier par événement et RECALCULE prix/total depuis TicketType
     * (la session n'est jamais source de vérité pour les montants).
     *
     * @return array<int, array{items: array<int, array{ticket_type_id:int, quantity:int, unit_price:int, total_price:int, ticket_name:string}>, subtotal:int}>
     */
    public function buildEventGroups(array $cart): array
    {
        $groups = [];

        foreach ($cart as $item) {
            $quantity = (int) ($item['quantity'] ?? 0);
            if ($quantity < 1) {
                throw new \InvalidArgumentException('Quantité invalide dans le panier.');
            }

            $ticketType = TicketType::findOrFail($item['ticket_type_id']);

            if (isset($item['event_id']) && (int) $item['event_id'] !== (int) $ticketType->event_id) {
                throw new \InvalidArgumentException('Panier incohérent.');
            }

            $unit = (int) $ticketType->price;
            $line = [
                'ticket_type_id' => $ticketType->id,
                'quantity' => $quantity,
                'unit_price' => $unit,
                'total_price' => $unit * $quantity,
                'ticket_name' => $ticketType->name,
            ];

            $groups[$ticketType->event_id]['items'][] = $line;
            $groups[$ticketType->event_id]['subtotal'] = ($groups[$ticketType->event_id]['subtotal'] ?? 0) + $line['total_price'];
        }

        return $groups;
    }

    /**
     * Crée la commande (pending, avec expiration) + items + billets réservés + commission.
     * À appeler DANS une DB::transaction.
     */
    public function createOrder(int $userId, int $eventId, array $group, array $attributes): Order
    {
        $order = Order::create($attributes + [
            'user_id' => $userId,
            'event_id' => $eventId,
            'total_amount' => $group['subtotal'] + self::SERVICE_FEE,
            'payment_status' => 'pending',
            'order_number' => Order::generateOrderNumber(),
            'expires_at' => now()->addMinutes((int) config('payments.reservation_minutes', 30)),
        ]);

        // Ordre d'id croissant des types de billets : évite les deadlocks entre commandes concurrentes
        $items = collect($group['items'])->sortBy('ticket_type_id');
        foreach ($items as $item) {
            $this->createOrderItemWithTickets($order, $item);
        }

        $this->createCommission($order);

        return $order;
    }

    /**
     * Réserve le stock (atomique, verrou + revérification dans TicketType::reserveTickets)
     * puis crée item + billets 'reserved'. Seul endroit qui incrémente quantity_sold.
     */
    private function createOrderItemWithTickets(Order $order, array $item): OrderItem
    {
        $ticketType = TicketType::findOrFail($item['ticket_type_id']);

        if (!$ticketType->reserveTickets($item['quantity'])) {
            throw new \RuntimeException("Le billet '{$item['ticket_name']}' n'est plus disponible en quantité demandée.");
        }

        $orderItem = OrderItem::create([
            'order_id' => $order->id,
            'ticket_type_id' => $ticketType->id,
            'quantity' => $item['quantity'],
            'unit_price' => $item['unit_price'],
            'total_price' => $item['total_price'],
        ]);

        for ($i = 0; $i < $item['quantity']; $i++) {
            $ticket = Ticket::create([
                'ticket_type_id' => $ticketType->id,
                'ticket_code' => Ticket::generateTicketCode(),
                'status' => 'reserved', // vendu seulement après paiement confirmé
            ]);

            if (method_exists($ticket, 'generateQRCode')) {
                $ticket->generateQRCode();
            }

            DB::table('order_tickets')->insert([
                'order_id' => $order->id,
                'ticket_id' => $ticket->id,
                'order_item_id' => $orderItem->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        Log::info('Billets réservés pour commande', [
            'order_id' => $order->id,
            'ticket_type_id' => $ticketType->id,
            'quantity' => $item['quantity'],
        ]);

        return $orderItem;
    }

    private function createCommission(Order $order): void
    {
        $commissionData = $order->calculateCommission();

        Commission::create([
            'order_id' => $order->id,
            'promoter_id' => $order->event->promoter_id,
            'gross_amount' => $commissionData['gross_amount'],
            'commission_rate' => $commissionData['commission_rate'],
            'commission_amount' => $commissionData['commission_amount'],
            'net_amount' => $commissionData['net_amount'],
            'platform_fee' => $commissionData['platform_fee'] ?? 0,
            'status' => 'pending',
        ]);
    }

    /**
     * Initialise le paiement PaiementPro. En cas d'échec, libère le stock de la commande
     * (statut failed) et lève une exception.
     *
     * @return string URL de redirection PaiementPro
     */
    public function initPaiementPro(Order $order, ?string $channel): string
    {
        $order->update(['payment_status' => 'processing']);

        try {
            $result = app(PaiementProService::class)->initTransaction($order, ['channel' => $channel]);
        } catch (\Throwable $e) {
            $result = ['success' => false, 'error' => $e->getMessage()];
        }

        if (!empty($result['success'])) {
            return $result['redirectUrl'];
        }

        Log::error('Échec init PaiementPro : stock libéré', ['order_id' => $order->id]);
        $order->releaseStock();

        throw new \RuntimeException('Erreur PaiementPro : ' . ($result['error'] ?? 'inconnue'));
    }
}
