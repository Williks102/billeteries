<?php

namespace App\Services;

use App\Exceptions\CheckoutRefusedException;
use App\Models\Commission;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Ticket;
use App\Models\TicketType;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;

/**
 * Logique de commande partagée entre CheckoutController et GuestCheckoutController
 * (prix serveur, réservation de stock, billets réservés, instructions de paiement manuel).
 */
class CheckoutService
{
    /** Message affiché pour toute erreur interne (jamais le message de l'exception). */
    public const GENERIC_ERROR = 'Une erreur est survenue lors de l\'enregistrement de votre commande. Veuillez réessayer.';

    /** Limite PERSONNELLE (email, compte ou connexion) : ne dit pas quel critère a joué. */
    public const LIMIT_ERROR = 'Limite personnelle atteinte : vous avez trop de réservations en attente pour le moment. Réglez ou annulez vos commandes en cours, ou réessayez plus tard.';

    /** Une demande dépasse À ELLE SEULE le plafond par commande (I1) : le vrai plafond est indiqué. */
    public static function perOrderLimitMessage(int $max): string
    {
        return "Maximum {$max} billet" . ($max > 1 ? 's' : '') . ' par commande. Réduisez la quantité demandée.';
    }

    /** Le TOTAL de billets du panier dépasse le plafond par commande (I-a) : même règle que assertWithinOrderLimits. */
    public static function totalLimitMessage(int $max): string
    {
        return "Maximum {$max} billet" . ($max > 1 ? 's' : '') . ' au total dans votre panier. Réduisez la quantité demandée.';
    }

    /**
     * Plafond effectif de billets par commande (I1) = min(max_per_order du type, quota de billets par acheteur).
     * Sans max_per_order (null/0) : le quota par acheteur. Affichage et validation s'appuient sur cette valeur.
     */
    public static function maxTicketsPerOrder(?int $typeMax = null): int
    {
        $quota = max(1, (int) config('payments.max_pending_tickets_per_buyer', 10));

        return ($typeMax !== null && $typeMax > 0) ? min($typeMax, $quota) : $quota;
    }

    /** Limite de l'ÉVÉNEMENT : ne suggère rien à l'acheteur (il n'a peut-être aucune commande). */
    public const EVENT_LIMIT_ERROR = 'Cet événement a momentanément trop de réservations en attente. Réessayez dans quelques heures.';

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

            // I1 : plafond effectif par commande = min(max_per_order du type, quota par acheteur)
            $perOrder = self::maxTicketsPerOrder($ticketType->max_per_order !== null ? (int) $ticketType->max_per_order : null);
            if ($quantity > $perOrder) {
                throw new CheckoutRefusedException(self::perOrderLimitMessage($perOrder));
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

        // N-C : toujours verrouiller les événements par id croissant (évite le deadlock paniers A,B / B,A)
        ksort($groups);

        return $groups;
    }

    /** Nombre total de billets d'un ensemble de groupes. */
    public function countTickets(array $groups): int
    {
        return (int) collect($groups)->sum(fn ($g) => collect($g['items'])->sum('quantity'));
    }

    /**
     * Email normalisé pour les plafonds : minuscules, trim, alias « +tag » retiré de la partie locale ;
     * Gmail (S-1) : points de la partie locale supprimés et googlemail.com traité comme gmail.com.
     * Normalisation à la volée (aucune colonne) ; la vérification d'email reste à faire avant production.
     */
    public static function normalizeEmail(string $email): string
    {
        $email = mb_strtolower(trim($email));
        $at = strrpos($email, '@');
        if ($at === false) {
            return $email;
        }
        $local = substr($email, 0, $at);
        $domain = substr($email, $at + 1);
        $plus = strpos($local, '+');
        if ($plus !== false && $plus > 0) {
            $local = substr($local, 0, $plus);
        }
        if (in_array($domain, ['gmail.com', 'googlemail.com'], true)) {
            $local = str_replace('.', '', $local);
            $domain = 'gmail.com';
        }

        return $local . '@' . $domain;
    }

    /**
     * Commandes pending/processing de l'acteur, une ligne par (commande, article) : order_id, quantity.
     * Connecté : SES commandes (user_id, guest_token NULL) ; une commande créée par un tiers avec son
     * email (guest_token) ne consomme pas son quota (N-D). Invité : email normalisé (alias +tag compris).
     */
    private function buyerPendingRows(string $email, ?int $userId)
    {
        $q = DB::table('orders')
            ->leftJoin('order_items', 'order_items.order_id', '=', 'orders.id')
            ->whereIn('orders.payment_status', ['pending', 'processing'])
            ->select('orders.id as order_id', 'orders.billing_email', DB::raw('coalesce(order_items.quantity, 0) as quantity'));

        if ($userId !== null) {
            return $q->where('orders.user_id', $userId)->whereNull('orders.guest_token')->get();
        }

        $norm = self::normalizeEmail($email);
        $at = strrpos($norm, '@');
        $esc = fn (string $v) => str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $v);
        $local = $at === false ? $norm : substr($norm, 0, $at);
        $domain = $at === false ? '' : substr($norm, $at);

        $rows = $q->where(function ($w) use ($norm, $esc, $local, $domain) {
            if ($domain === '@gmail.com') {
                // Points et googlemail.com non comparables en SQL : on balaye le domaine (lignes pending/processing
                // seulement), le filtre exact est fait en PHP ci-dessous.
                $w->whereRaw("lower(orders.billing_email) like '%@gmail.com'")
                    ->orWhereRaw("lower(orders.billing_email) like '%@googlemail.com'");

                return;
            }
            $w->whereRaw('lower(orders.billing_email) = ?', [$norm])
                ->orWhereRaw("lower(orders.billing_email) like ? escape '!'", [$esc($local) . '+%' . $esc($domain)]);
        })->get();

        return $rows->filter(fn ($r) => self::normalizeEmail((string) $r->billing_email) === $norm)->values();
    }

    /**
     * Plafonds anti-accaparement (E2) : commandes pending/processing par email et par compte,
     * et commandes créées par IP sur la fenêtre de réservation (invités). Messages génériques (LIMIT_ERROR) sauf
     * dépassement du plafond par commande à lui seul (message exact, I1). Quotas lus HORS transaction (S-2, avant production).
     *
     * @throws CheckoutRefusedException
     */
    public function assertWithinOrderLimits(string $email, ?int $userId, ?string $ip, int $newOrders, bool $capByIp = false, int $newTickets = 0): void
    {
        $max = max(1, (int) config('payments.max_pending_orders', 3));
        $rows = $this->buyerPendingRows($email, $userId);

        if ($rows->pluck('order_id')->unique()->count() + $newOrders > $max) {
            Log::warning('Plafond de commandes en attente atteint', ['scope' => 'buyer_orders', 'user_id' => $userId]);
            throw new CheckoutRefusedException(self::LIMIT_ERROR);
        }

        // N-B : quota de BILLETS par acteur (email normalisé / compte), quelle que soit la taille de l'événement
        $maxTickets = max(1, (int) config('payments.max_pending_tickets_per_buyer', 10));
        if ($newTickets > $maxTickets) {
            // I1 : la demande dépasse à elle seule le plafond : message exact, pas « trop de réservations en attente »
            throw new CheckoutRefusedException(self::perOrderLimitMessage($maxTickets));
        }
        if ($newTickets > 0 && (int) $rows->sum('quantity') + $newTickets > $maxTickets) {
            Log::warning('Quota de billets en attente par acheteur atteint', ['scope' => 'buyer_tickets', 'user_id' => $userId]);
            throw new CheckoutRefusedException(self::LIMIT_ERROR);
        }

        if ($capByIp && $ip !== null) {
            $maxIp = max(1, (int) config('payments.max_pending_orders_per_ip', 10));
            if (RateLimiter::attempts($this->ipKey($ip)) + $newOrders > $maxIp) {
                Log::warning('Plafond de commandes par IP atteint', ['scope' => 'ip']);
                throw new CheckoutRefusedException(self::LIMIT_ERROR);
            }

            $maxIpTickets = max(1, (int) config('payments.max_pending_tickets_per_ip', 20));
            if ($newTickets > 0 && RateLimiter::attempts($this->ipTicketsKey($ip)) + $newTickets > $maxIpTickets) {
                Log::warning('Quota de billets par IP atteint', ['scope' => 'ip_tickets']);
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

    /**
     * Compte les commandes et billets créés par cette IP (invités et comptes récents) sur la fenêtre de
     * réservation invité. Compteur par fenêtre : non décrémenté si la commande est annulée (conservateur).
     */
    public function recordGuestOrders(?string $ip, int $count, int $tickets = 0): void
    {
        if ($ip === null) {
            return;
        }

        $decay = max(60, (int) config('payments.guest_manual_reservation_minutes', 1440) * 60);
        for ($i = 0; $i < $count; $i++) {
            RateLimiter::hit($this->ipKey($ip), $decay);
        }
        if ($tickets > 0) {
            RateLimiter::increment($this->ipTicketsKey($ip), $decay, $tickets);
        }
    }

    private function ipTicketsKey(string $ip): string
    {
        return 'guest-tickets:' . sha1(self::normalizeIp($ip));
    }

    private function ipKey(string $ip): string
    {
        return 'guest-orders:' . sha1(self::normalizeIp($ip));
    }

    /**
     * S-1 : IPv6 ramenée à son préfixe /64 (un abonné contrôle tout le /64) ; IPv4 (et IPv4 mappée) inchangée.
     */
    public static function normalizeIp(string $ip): string
    {
        $bin = @inet_pton($ip);
        if ($bin === false || strlen($bin) !== 16) {
            return $ip;
        }
        if (str_starts_with($bin, str_repeat("\0", 10) . "\xff\xff")) {
            return implode('.', array_map('ord', str_split(substr($bin, 12))));
        }

        return bin2hex(substr($bin, 0, 8)) . '/64';
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
            'total_amount' => $group['subtotal'], // prix des billets uniquement : aucun frais pour l'acheteur
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
     * Plafond de billets pending/processing d'un événement : clamp(ceil(ratio x capacité), min, max).
     * Capacité = somme des quantity_available de ses types de billets. Le max est un plafond dur.
     */
    public function eventPendingCap(int $capacity): int
    {
        $ratio = max(0.0, (float) config('payments.pending_ratio', 0.3));
        $min = max(1, (int) config('payments.pending_min', 10));
        $max = max(1, (int) config('payments.max_pending_tickets_per_event', 200));

        return min(max((int) ceil($ratio * $capacity), $min), $max);
    }

    /**
     * Requête de comptage des billets en attente d'un événement. Lecture VERROUILLANTE (FOR UPDATE) :
     * sous MySQL REPEATABLE READ elle lit les dernières données committées et non l'instantané de la
     * transaction (N-A). Elle verrouille les commandes pending de l'événement (ordre events -> orders
     * -> ticket_types, comme releaseStock/confirmation : commande puis types).
     */
    public function eventPendingTicketsQuery(int $eventId): \Illuminate\Database\Query\Builder
    {
        return DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where('orders.event_id', $eventId)
            ->whereIn('orders.payment_status', ['pending', 'processing'])
            ->lockForUpdate();
    }

    /**
     * Plafond de billets pending/processing par ÉVÉNEMENT (N3b/N-B), vérifié SOUS VERROU : ligne de
     * l'événement verrouillée, puis comptage et capacité en lectures verrouillantes dans la même
     * transaction que la réservation. Ordre des verrous : events -> orders/order_items -> ticket_types
     * (id croissant). À appeler DANS une DB::transaction. SQLite ignore lockForUpdate : la preuve de
     * concurrence réelle est tests/MySql/EventPendingCapConcurrencyTest.php.
     *
     * @throws CheckoutRefusedException
     */
    private function assertEventPendingTicketsWithinCap(int $eventId, int $newTickets): void
    {
        DB::table('events')->where('id', $eventId)->lockForUpdate()->first();

        $pending = (int) $this->eventPendingTicketsQuery($eventId)->sum('order_items.quantity');

        $capacity = (int) DB::table('ticket_types')->where('event_id', $eventId)->orderBy('id')->lockForUpdate()->pluck('quantity_available')->sum();
        $cap = $this->eventPendingCap($capacity);

        if ($pending + $newTickets > $cap) {
            Log::warning('Plafond de billets en attente par événement atteint', [
                'scope' => 'event', 'event_id' => $eventId, 'pending' => $pending, 'cap' => $cap,
            ]);
            $this->alertAdminsEventCapReached($eventId, $pending, $cap);
            throw new CheckoutRefusedException(self::EVENT_LIMIT_ERROR);
        }
    }

    /**
     * Alerte email aux admins, au plus 1 fois par heure et par événement.
     * B1 : le créneau (Cache::add atomique) est pris DANS le callback `terminating`, donc APRÈS le rollback de la
     * transaction de commande et hors verrous. Pris dans la transaction, avec CACHE_STORE=database, le refus
     * annulait le marqueur (même connexion PDO) et chaque refus envoyait un email. Un échec d'envoi pose un backoff de
     * 5 min (nouvel essai ensuite) et est journalisé sans PII. Aucune donnée personnelle dans le message.
     */
    private function alertAdminsEventCapReached(int $eventId, int $pending, int $cap): void
    {
        $done = false; // terminate() peut rejouer les callbacks (tests, workers longs) : envoi unique
        app()->terminating(function () use ($eventId, $pending, $cap, &$done) {
            if ($done) {
                return;
            }
            $done = true;
            $key = 'event-pending-cap-alert:' . $eventId;
            try {
                if (!Cache::add($key, 1, 3600)) {
                    return;
                }
                $admins = \App\Models\User::where('role', 'admin')->pluck('email')->filter()->all();
                if ($admins === []) {
                    Cache::forget($key);

                    return;
                }
                try {
                    Mail::raw(
                        "Plafond de réservations en attente atteint pour l'événement #{$eventId} ({$pending} billets, plafond {$cap}). "
                        . 'Vérifiez les commandes en attente (possible accaparement) et annulez les commandes abusives.',
                        fn ($m) => $m->to($admins)->subject("Alerte : événement #{$eventId} saturé de réservations en attente")
                    );
                } catch (\Throwable $e) {
                    // Backoff : panne SMTP => pas de nouvel envoi synchrone à chaque refus pendant 5 min (au lieu de libérer le créneau)
                    Cache::put($key, 1, 300);
                    throw $e;
                }
            } catch (\Throwable $e) {
                Log::error('Échec envoi alerte plafond événement', ['event_id' => $eventId, 'exception' => get_class($e)]);
            }
        });
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

            // I2 : AUCUN QR ici (appels HTTP externes sous verrous) : voir generateQrCodes(), appelé après le commit.

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

    /**
     * I2 : génère les QR des billets des commandes APRÈS le commit (jamais dans la transaction ni sous verrou :
     * QRCodeService appelle des API HTTP externes, timeout 8 s par tentative). Chaque échec est journalisé
     * sans PII et n'affecte pas la commande ; un billet sans QR est régénéré à la demande (Ticket::getReliableQrCode,
     * PDF/page billet) ou par les commandes Artisan existantes.
     *
     * @param  array<int, Order>  $orders
     */
    public function generateQrCodes(array $orders): void
    {
        foreach ($orders as $order) {
            try {
                $tickets = Ticket::whereIn('id', DB::table('order_tickets')->where('order_id', $order->id)->pluck('ticket_id'))->get();
            } catch (\Throwable $e) {
                $this->logFailure('Lecture des billets pour QR impossible', $e, ['order_id' => $order->id]);
                continue;
            }
            foreach ($tickets as $ticket) {
                try {
                    $ticket->generateQrCode();
                } catch (\Throwable $e) {
                    $this->logFailure('Échec génération QR après commit', $e, ['order_id' => $order->id, 'ticket_id' => $ticket->id]);
                }
            }
        }
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
