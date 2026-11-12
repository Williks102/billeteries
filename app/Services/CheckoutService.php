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

    /** Plafond de commandes GRATUITES atteint (IP ou email) : message unique, ne dit pas quel critère a joué. */
    public const FREE_LIMIT_ERROR = 'Limite de réservations gratuites atteinte pour le moment. Réessayez plus tard.';

    /** Échec de finalisation d'une commande gratuite : tout le checkout est annulé (tout-ou-rien). */
    public const FREE_FAILURE_ERROR = 'Votre réservation n\'a pas pu être finalisée. Aucune commande n\'a été enregistrée et aucune place n\'est retenue : veuillez réessayer.';

    /** Référence de paiement et méthode des commandes gratuites (total serveur = 0 FCFA). */
    public const FREE_REFERENCE = 'GRATUIT';
    public const FREE_METHOD = 'free';

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

        return $this->rowsForNormalizedEmail($q, $email);
    }

    /**
     * Applique le filtre « même email normalisé » (alias +tag, points/googlemail de Gmail) à une requête sur `orders` :
     * préfiltre SQL large puis égalité exacte en PHP. Partagé par les plafonds « en attente » et « gratuites ».
     */
    private function rowsForNormalizedEmail($q, string $email)
    {
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
     *
     * Commande GRATUITE (sous-total serveur = 0) : méthode `free`, pas d'expiration, pas de commission
     * (aucun revenu à partager), empreinte HMAC de l'IP pour le plafond ; elle reste `pending` jusqu'à
     * finalizeFreeOrders() (après commit), qui passe par OrderPaymentService::confirmPayment.
     * Pas de plafond « en attente » : elle est payée immédiatement, le plafond gratuit s'applique en amont.
     */
    public function createOrder(int $userId, int $eventId, array $group, array $attributes, ?string $ip = null): Order
    {
        $free = self::isFreeGroup($group);

        if ($free) {
            $attributes['payment_method'] = self::FREE_METHOD;
            $attributes['checkout_ip_hash'] = $ip !== null ? self::ipHash($ip) : null;
        } else {
            $this->assertEventPendingTicketsWithinCap($eventId, (int) collect($group['items'])->sum('quantity'));
        }

        $order = Order::create($attributes + [
            'user_id' => $userId,
            'event_id' => $eventId,
            'total_amount' => $group['subtotal'], // prix des billets uniquement : aucun frais pour l'acheteur
            'payment_status' => 'pending',
            'order_number' => Order::generateOrderNumber(),
            'expires_at' => $free
                ? now()->addMinutes($this->reservationMinutes(null))
                : now()->addMinutes($this->reservationMinutes($attributes['payment_method'] ?? null, !empty($attributes['guest_token']))),
        ]);

        // Ordre d'id croissant des types de billets : évite les deadlocks entre commandes concurrentes
        $items = collect($group['items'])->sortBy('ticket_type_id');
        foreach ($items as $item) {
            $this->createOrderItemWithTickets($order, $item);
        }

        if (!$free) {
            $this->createCommission($order);
        }

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
                'order_item_id' => $orderItem->id,
                'ticket_code' => Ticket::generateTicketCode(),
                'status' => 'reserved', // vendu seulement après paiement confirmé
            ]);

            // Aucun QR à la réservation : le QR n'est émis qu'après confirmation du paiement (OrderPaymentService).

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

    /** Référence de paiement des commandes confirmées automatiquement par le mode démo. */
    public const DEMO_REFERENCE = 'DEMO-AUTO';

    /** Le mode démo (confirmation automatique des paiements) est-il actif ? Désactivé par défaut. */
    public static function demoAutoConfirm(): bool
    {
        return (bool) config('payments.demo_auto_confirm', false);
    }

    /**
     * MODE DÉMO : confirme chaque commande créée via OrderPaymentService::confirmPayment (mêmes verrous,
     * idempotence, commission unique, billets `sold`, QR, email). Appelé APRÈS le commit et APRÈS la
     * validation et les plafonds du checkout : le flag ne contourne rien. Sans effet si le mode est inactif.
     * Un échec de confirmation est journalisé sans PII et laisse la commande `pending` (jamais d'exception).
     *
     * @param  array<int, Order>  $orders
     * @return array<int, Order>
     */
    public function autoConfirmDemo(array $orders): array
    {
        if (!self::demoAutoConfirm()) {
            return $orders;
        }

        static $warnedFor = null;
        $request = request();
        if ($warnedFor !== $request) {
            $warnedFor = $request;
            Log::warning('MODE DÉMO actif : les paiements sont confirmés automatiquement (DEMO_AUTO_CONFIRM_PAYMENTS).');
        }

        $payments = app(OrderPaymentService::class);

        return array_map(function (Order $order) use ($payments) {
            if ($order->isFree()) {
                return $order; // gratuite : déjà confirmée (référence GRATUIT), jamais « paiement simulé »
            }
            try {
                return $payments->confirmPayment($order, self::DEMO_REFERENCE, null);
            } catch (\Throwable $e) {
                $this->logFailure('Échec confirmation automatique (mode démo)', $e, ['order_id' => $order->id]);

                return $order;
            }
        }, $orders);
    }

    // ===== Commandes gratuites (total serveur = 0 FCFA) =====

    /** Un groupe (= une commande) est gratuit si son sous-total RECALCULÉ côté serveur vaut 0. Jamais un champ client. */
    public static function isFreeGroup(array $group): bool
    {
        return (int) ($group['subtotal'] ?? 0) === 0;
    }

    /** `payment_method` n'est exigé que si au moins une commande a un total serveur > 0. */
    public function requiresPaymentMethod(array $groups): bool
    {
        return collect($groups)->contains(fn ($g) => !self::isFreeGroup($g));
    }

    /** Total serveur (prix actuels de TicketType) d'un panier de session, pour l'affichage : jamais la session. */
    public function serverCartTotal(array $cart): int
    {
        $total = 0;
        foreach ($cart as $item) {
            $type = TicketType::find($item['ticket_type_id'] ?? 0);
            $total += $type ? (int) $type->price * max(0, (int) ($item['quantity'] ?? 0)) : 0;
        }

        return $total;
    }

    /** Empreinte HMAC (clé de l'application) de l'IP normalisée : l'IP en clair n'est jamais stockée. */
    public static function ipHash(string $ip): string
    {
        return hash_hmac('sha256', self::normalizeIp($ip), (string) config('app.key'));
    }

    /**
     * Plafonds « en attente » : ne concernent que les commandes PAYANTES (une commande gratuite est payée
     * immédiatement). Sans commande payante, aucun contrôle (et aucun compteur) n'est touché.
     *
     * @return array{orders:int, tickets:int} nombre de commandes et de billets payants (compteurs IP)
     */
    public function assertPendingLimitsForPaidGroups(array $groups, string $email, ?int $userId, ?string $ip, bool $capByIp): array
    {
        $paid = array_filter($groups, fn ($g) => !self::isFreeGroup($g));
        $tickets = $this->countTickets($paid);

        if ($paid !== []) {
            $this->assertWithinOrderLimits($email, $userId, $ip, count($paid), $capByIp, $tickets);
        }

        return ['orders' => count($paid), 'tickets' => $tickets];
    }

    /**
     * Plafonds des commandes gratuites, comptés EN BASE (commandes `free` créées, tous statuts) : par IP sur 1 h
     * (empreinte HMAC) et par email normalisé sur 24 h ; billets par commande gratuite = plafond effectif par commande.
     * Refus avant toute réservation de stock. À appeler sous withFreeOrderLocks() pour la concurrence.
     *
     * @throws CheckoutRefusedException
     */
    public function assertWithinFreeOrderLimits(array $groups, string $email, ?string $ip, ?int $userId = null): void
    {
        $free = array_filter($groups, fn ($g) => self::isFreeGroup($g));
        if ($free === []) {
            return;
        }

        $perOrder = self::maxTicketsPerOrder();
        foreach ($free as $g) {
            if ($this->countTickets([$g]) > $perOrder) {
                throw new CheckoutRefusedException(self::perOrderLimitMessage($perOrder));
            }
        }

        $new = count($free);

        $maxEmail = max(1, (int) config('payments.free_orders_per_email_per_day', 5));
        $q = DB::table('orders')->where('payment_method', self::FREE_METHOD)->where('created_at', '>=', now()->subDay())
            ->select('orders.id', 'orders.billing_email');
        if ($this->rowsForNormalizedEmail($q, $email)->count() + $new > $maxEmail) {
            Log::warning('Plafond de commandes gratuites par email atteint', ['scope' => 'free_email']);
            throw new CheckoutRefusedException(self::FREE_LIMIT_ERROR);
        }

        // Compte connecté : plafond par user_id EN PLUS de l'email (changer d'email ne le contourne pas)
        if ($userId !== null) {
            $maxUser = max(1, (int) config('payments.free_orders_per_user_per_day', 5));
            $count = DB::table('orders')->where('payment_method', self::FREE_METHOD)->where('user_id', $userId)
                ->where('created_at', '>=', now()->subDay())->count();
            if ($count + $new > $maxUser) {
                Log::warning('Plafond de commandes gratuites par compte atteint', ['scope' => 'free_user']);
                throw new CheckoutRefusedException(self::FREE_LIMIT_ERROR);
            }
        }

        if ($ip !== null) {
            // Billets gratuits par ÉVÉNEMENT et par IP sur 24 h (commandes non annulées) : limite l'épuisement du stock
            $maxEvent = max(1, (int) config('payments.free_tickets_per_event_per_ip_per_day', 10));
            foreach ($free as $eventId => $g) {
                $taken = (int) DB::table('order_items')->join('orders', 'orders.id', '=', 'order_items.order_id')
                    ->where('orders.payment_method', self::FREE_METHOD)->where('orders.checkout_ip_hash', self::ipHash($ip))
                    ->where('orders.event_id', $eventId)->where('orders.created_at', '>=', now()->subDay())
                    ->whereNotIn('orders.payment_status', ['failed', 'cancelled', 'expired', 'refunded'])
                    ->sum('order_items.quantity');
                if ($taken + $this->countTickets([$g]) > $maxEvent) {
                    Log::warning('Plafond de billets gratuits par événement et par IP atteint', ['scope' => 'free_event_ip', 'event_id' => $eventId]);
                    throw new CheckoutRefusedException(self::FREE_LIMIT_ERROR);
                }
            }

            $maxIp = max(1, (int) config('payments.free_orders_per_ip_per_hour', 5));
            $count = DB::table('orders')->where('payment_method', self::FREE_METHOD)
                ->where('checkout_ip_hash', self::ipHash($ip))->where('created_at', '>=', now()->subHour())->count();
            if ($count + $new > $maxIp) {
                Log::warning('Plafond de commandes gratuites par IP atteint', ['scope' => 'free_ip']);
                throw new CheckoutRefusedException(self::FREE_LIMIT_ERROR);
            }
        }
    }

    /**
     * Exécute $fn en sérialisant les commandes gratuites d'une même IP / d'un même email (verrous atomiques du cache,
     * pris HORS transaction, clés triées : pas d'interblocage). Sans groupe gratuit : exécution directe.
     * Le comptage (assertWithinFreeOrderLimits) et la création de commande doivent être DANS $fn : deux requêtes
     * simultanées ne peuvent pas passer toutes deux sous le plafond.
     *
     * @template T
     * @param  \Closure(): T  $fn
     * @return T
     */
    public function withFreeOrderLocks(array $groups, string $email, ?string $ip, \Closure $fn, ?int $userId = null)
    {
        if (!collect($groups)->contains(fn ($g) => self::isFreeGroup($g))) {
            return $fn();
        }

        $keys = ['free-orders-lock:email:' . sha1(self::normalizeEmail($email))];
        if ($userId !== null) {
            $keys[] = 'free-orders-lock:user:' . $userId;
        }
        if ($ip !== null) {
            $keys[] = 'free-orders-lock:ip:' . sha1(self::normalizeIp($ip));
        }
        sort($keys);

        $held = [];
        try {
            foreach ($keys as $key) {
                $lock = Cache::lock($key, 30);
                try {
                    $lock->block(5);
                } catch (\Illuminate\Contracts\Cache\LockTimeoutException) {
                    throw new CheckoutRefusedException(self::GENERIC_ERROR);
                }
                $held[] = $lock;
            }

            return $fn();
        } finally {
            foreach (array_reverse($held) as $lock) {
                $lock->release();
            }
        }
    }

    /**
     * APRÈS le commit : confirme chaque commande gratuite via OrderPaymentService::confirmPayment (mêmes verrous,
     * idempotence, billets `sold`, QR après commit, email), référence FREE_REFERENCE. Jamais d'update direct du statut.
     *
     * TOUT-OU-RIEN : si une confirmation échoue, TOUTES les commandes créées par ce checkout sont libérées
     * (pending/processing : fail() = stock rendu ; déjà confirmées gratuites : refund()), le checkout est refusé
     * et aucune commande n'est laissée en suspens (le panier n'est pas vidé : un renvoi repart de zéro).
     *
     * @param  array<int, Order>  $orders
     * @return array<int, Order>
     * @throws CheckoutRefusedException
     */
    public function finalizeFreeOrders(array $orders): array
    {
        $payments = app(OrderPaymentService::class);

        foreach ($orders as $i => $order) {
            if (!$order->isFree() || $order->payment_method !== self::FREE_METHOD) {
                continue;
            }

            try {
                $orders[$i] = $payments->confirmPayment($order, self::FREE_REFERENCE, null);
            } catch (\Throwable $e) {
                $this->logFailure('Échec confirmation commande gratuite', $e, ['order_id' => $order->id]);
                $this->releaseAll($orders);

                throw new CheckoutRefusedException(self::FREE_FAILURE_ERROR);
            }
        }

        return $orders;
    }

    /** Libère toutes les commandes d'un checkout (via OrderPaymentService, jamais d'update direct). Échecs journalisés. */
    private function releaseAll(array $orders): void
    {
        $payments = app(OrderPaymentService::class);
        foreach ($orders as $o) {
            try {
                $fresh = $o->fresh();
                if ($fresh === null) {
                    continue;
                }
                if ($fresh->isAwaitingPayment()) {
                    $payments->fail($fresh);
                } elseif ($fresh->isPaid()) {
                    $payments->refund($fresh);
                }
            } catch (\Throwable $e) {
                $this->logFailure('Échec libération commande après échec du checkout', $e, ['order_id' => $o->id]);
            }
        }
    }

    /** Page de destination : la première commande EN ATTENTE s'il y en a une (c'est là que l'acheteur agit), sinon la première. */
    public function confirmationTarget(array $orders): Order
    {
        return collect($orders)->first(fn ($o) => $o->isAwaitingPayment()) ?? $orders[0];
    }

    /**
     * Données de flash pour la page de confirmation : autres commandes (numéros) et, si la page cible attend un
     * paiement, liens vers les commandes gratuites déjà émises.
     *
     * @return array{numbers: array<int,string>, free: array<int, array{number:string, url:string}>}
     */
    public function otherOrdersFlash(array $orders, Order $target): array
    {
        $awaiting = $target->isAwaitingPayment();
        $others = collect($orders)->reject(fn ($o) => $o->is($target));

        return [
            'numbers' => $others->reject(fn ($o) => $awaiting && $o->isFree())->pluck('order_number')->values()->all(),
            'free' => $awaiting
                ? $others->filter(fn ($o) => $o->isFree() && $o->isPaid())->map(fn ($o) => [
                    'number' => $o->order_number,
                    'url' => $o->guest_token ? route('checkout.guest.confirmation', $o->guest_token) : route('checkout.confirmation', $o),
                ])->values()->all()
                : [],
        ];
    }

    /**
     * Message de succès du checkout. Aucune promesse d'email (le transport peut être « log »).
     * Panier mixte : les billets gratuits sont émis, la commande payante reste à régler.
     */
    public static function successMessage(int $orderCount, bool $allFree = false, bool $mixedAwaiting = false): string
    {
        if ($allFree) {
            return $orderCount > 1
                ? 'Réservations confirmées. Vos billets sont émis.'
                : 'Réservation confirmée. Vos billets sont émis.';
        }

        if ($mixedAwaiting) {
            return 'Vos billets gratuits sont émis ; réglez la commande payante ci-dessous.';
        }

        if (self::demoAutoConfirm()) {
            return $orderCount > 1
                ? 'MODE DÉMO : paiement simulé. Vos commandes sont confirmées automatiquement.'
                : 'MODE DÉMO : paiement simulé. Votre commande est confirmée automatiquement.';
        }

        return $orderCount > 1
            ? 'Commandes enregistrées. Les instructions de paiement figurent ci-dessous.'
            : 'Commande enregistrée. Les instructions de paiement figurent ci-dessous.';
    }

    /** Message de succès pour un ensemble de commandes créées (gère le panier mixte). */
    public function messageFor(array $orders): string
    {
        $allFree = collect($orders)->every(fn ($o) => $o->isFree());
        $mixed = !$allFree && collect($orders)->contains(fn ($o) => $o->isFree() && $o->isPaid())
            && collect($orders)->contains(fn ($o) => $o->isAwaitingPayment());

        return self::successMessage(count($orders), $allFree, $mixed);
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
            if (!$order->isAwaitingPayment()) {
                continue; // déjà payée (mode démo) : l'email de confirmation de paiement a été envoyé
            }
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
