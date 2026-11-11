<?php

namespace App\Services;

use App\Exceptions\CheckoutRefusedException;
use App\Models\Commission;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Ticket;
use App\Models\TicketType;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

/**
 * Logique de commande partagée entre CheckoutController et GuestCheckoutController
 * (prix serveur, réservation de stock, billets réservés, instructions de paiement manuel).
 */
class CheckoutService
{
    public const SERVICE_FEE = 500;

    /** Message affiché pour toute erreur interne (jamais le message de l'exception). */
    public const GENERIC_ERROR = 'Une erreur est survenue lors de l\'enregistrement de votre commande. Veuillez réessayer.';

    /** Message du plafond de commandes (volontairement neutre : ne dit pas quel critère a joué). */
    public const LIMIT_ERROR = 'Impossible d\'enregistrer une nouvelle commande pour le moment. Réglez vos commandes en attente ou réessayez plus tard.';

    /**
     * Durée de réservation : longue (48 h par défaut) pour un paiement manuel, courte sinon.
     */
    private function reservationMinutes(?string $method, bool $guest = false): int
    {
        if ($method !== null && in_array($method, self::manualMethodKeys(), true)) {
            // Invité non vérifié : réservation plus courte (anti-accaparement de stock)
            if ($guest) {
                return (int) config('payments.guest_manual_reservation_minutes', 1440);
            }

            return (int) config('payments.manual_reservation_minutes', 2880);
        }

        return (int) config('payments.reservation_minutes', 30);
    }

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
     * Plafonds anti-accaparement (E2) : commandes pending/processing par email et par compte,
     * et commandes créées par IP sur la fenêtre de réservation (invités). Message neutre.
     *
     * @throws CheckoutRefusedException
     */
    public function assertWithinOrderLimits(string $email, ?int $userId, ?string $ip, int $newOrders, bool $capByIp = false): void
    {
        $max = max(1, (int) config('payments.max_pending_orders', 3));

        $pending = Order::whereIn('payment_status', ['pending', 'processing'])
            ->where(function ($q) use ($email, $userId) {
                $q->whereRaw('lower(billing_email) = ?', [mb_strtolower(trim($email))]);
                if ($userId !== null) {
                    $q->orWhere('user_id', $userId);
                }
            })->count();

        if ($pending + $newOrders > $max) {
            Log::warning('Plafond de commandes en attente atteint', ['scope' => 'email_or_user', 'user_id' => $userId]);
            throw new CheckoutRefusedException(self::LIMIT_ERROR);
        }

        if ($capByIp && $ip !== null) {
            $maxIp = max(1, (int) config('payments.max_pending_orders_per_ip', 10));
            if (RateLimiter::attempts($this->ipKey($ip)) + $newOrders > $maxIp) {
                Log::warning('Plafond de commandes par IP atteint', ['scope' => 'ip']);
                throw new CheckoutRefusedException(self::LIMIT_ERROR);
            }
        }
    }

    /**
     * Le plafond par IP s'applique aux invités et aux comptes de moins de 24 h (N3c : comptes jetables).
     */
    public function shouldCapByIp(?\App\Models\User $user): bool
    {
        return $user === null || ($user->created_at !== null && $user->created_at->gt(now()->subDay()));
    }

    /** Compte les commandes créées par cette IP (invités et comptes récents) (fenêtre = réservation invité). */
    public function recordGuestOrders(?string $ip, int $count): void
    {
        if ($ip === null) {
            return;
        }

        $decay = max(60, (int) config('payments.guest_manual_reservation_minutes', 1440) * 60);
        for ($i = 0; $i < $count; $i++) {
            RateLimiter::hit($this->ipKey($ip), $decay);
        }
    }

    private function ipKey(string $ip): string
    {
        return 'guest-orders:' . sha1($ip);
    }

    /**
     * Journalise une erreur interne SANS PII ni message brut (les messages SQL contiennent des valeurs).
     */
    public function logFailure(string $context, \Throwable $e, array $extra = []): void
    {
        Log::error($context, $extra + [
            'exception' => get_class($e),
            'where' => basename($e->getFile()) . ':' . $e->getLine(),
        ]);
    }

    /**
     * Crée la commande (pending, avec expiration) + items + billets réservés + commission.
     * À appeler DANS une DB::transaction.
     */
    public function createOrder(int $userId, int $eventId, array $group, array $attributes): Order
    {
        $this->assertEventPendingTicketsWithinCap($eventId, (int) collect($group['items'])->sum('quantity'));

        $order = Order::create($attributes + [
            'user_id' => $userId,
            'event_id' => $eventId,
            'total_amount' => $group['subtotal'] + self::SERVICE_FEE,
            'payment_status' => 'pending',
            'order_number' => Order::generateOrderNumber(),
            'expires_at' => now()->addMinutes($this->reservationMinutes($attributes['payment_method'] ?? null, !empty($attributes['guest_token']))),
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
     * Plafond de billets pending/processing par ÉVÉNEMENT (N3b), vérifié SOUS VERROU : la ligne de
     * l'événement est verrouillée (lockForUpdate) puis les billets en attente sont comptés dans la même
     * transaction que la réservation, ce qui sérialise les checkouts concurrents d'un même événement
     * (10 requêtes parallèles ne passent pas toutes). À appeler DANS une DB::transaction.
     * Note : SQLite ignore lockForUpdate ; la preuve de concurrence reste à faire sur MySQL.
     *
     * @throws CheckoutRefusedException
     */
    private function assertEventPendingTicketsWithinCap(int $eventId, int $newTickets): void
    {
        $cap = max(1, (int) config('payments.max_pending_tickets_per_event', 50));

        DB::table('events')->where('id', $eventId)->lockForUpdate()->first();

        $pending = (int) DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where('orders.event_id', $eventId)
            ->whereIn('orders.payment_status', ['pending', 'processing'])
            ->sum('order_items.quantity');

        if ($pending + $newTickets > $cap) {
            Log::warning('Plafond de billets en attente par événement atteint', ['scope' => 'event', 'event_id' => $eventId]);
            throw new CheckoutRefusedException(self::LIMIT_ERROR);
        }
    }

    /**
     * Réserve le stock (atomique, verrou + revérification dans TicketType::reserveTickets)
     * puis crée item + billets 'reserved'. Seul endroit qui incrémente quantity_sold.
     */
    private function createOrderItemWithTickets(Order $order, array $item): OrderItem
    {
        $ticketType = TicketType::findOrFail($item['ticket_type_id']);

        if (!$ticketType->reserveTickets($item['quantity'])) {
            throw new CheckoutRefusedException("Le billet '{$item['ticket_name']}' n'est plus disponible en quantité demandée.");
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
     * Clés des méthodes de paiement manuelles autorisées (config payments.manual_methods).
     *
     * @return array<int, string>
     */
    public static function manualMethodKeys(): array
    {
        return array_keys((array) config('payments.manual_methods', []));
    }

    /**
     * Règle de validation de `payment_method` (partagée par les deux checkouts).
     */
    public static function paymentMethodRule(): string
    {
        return 'required|in:' . implode(',', self::manualMethodKeys());
    }

    /**
     * Libellé, consigne et coordonnées d'une méthode manuelle (valeurs issues de la config / de l'env).
     *
     * @return array{label:string, instructions:string, details:array<string,string>}
     */
    public static function manualPaymentInfo(?string $method): array
    {
        $conf = (array) config('payments.manual_methods.' . $method, []);

        $details = [];
        foreach ((array) ($conf['details'] ?? []) as $label => $value) {
            $details[$label] = filled($value) ? (string) $value : 'communiqué par l\'organisateur';
        }

        return [
            'label' => (string) ($conf['label'] ?? 'Paiement manuel'),
            'instructions' => (string) ($conf['instructions'] ?? 'Contactez l\'organisateur pour régler votre commande.'),
            'details' => $details,
        ];
    }

    /**
     * Après création des commandes en paiement manuel : envoie les instructions par email
     * (un échec d'envoi ne casse jamais la commande, il est seulement journalisé).
     *
     * @param  array<int, Order>  $orders
     */
    public function sendManualInstructions(array $orders): void
    {
        foreach ($orders as $order) {
            try {
                app(EmailService::class)->sendManualPaymentInstructions($order);
            } catch (\Throwable $e) {
                Log::error('Erreur envoi instructions de paiement manuel', [
                    'order_id' => $order->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Preuve de possession d'une commande invité : le guest_token est mémorisé en session
     * au checkout ; il n'est jamais déduit d'un identifiant fourni dans la requête.
     */
    public function rememberGuestOrder(Order $order): void
    {
        if ($order->guest_token) {
            session()->put('guest_order_tokens.' . $order->id, $order->guest_token);
        }
    }

    public function ownsGuestOrder(Order $order, ?string $token = null): bool
    {
        $known = session('guest_order_tokens.' . $order->id);

        return $order->guest_token !== null
            && is_string($known)
            && hash_equals($order->guest_token, $known)
            && ($token === null || hash_equals($order->guest_token, $token));
    }
}
