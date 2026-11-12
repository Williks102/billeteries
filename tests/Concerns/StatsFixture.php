<?php

namespace Tests\Concerns;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Jeu canonique des statistiques (SANS frais de service, commission 6 % de la somme des lignes).
 * Date « maintenant » figée : 2026-11-15 12:00 Africa/Abidjan (mois civil en cours = novembre 2026).
 *
 * Événement E (id 1, promoteur P = user 2) : types A (id 1, 10 000 F, capacité 100), B (id 2, 5 000 F, cap. 50),
 *   C (id 3, GRATUIT 0 F, cap. 20). Événement E2 (id 2, promoteur P2 = user 3) : type D (id 4, 20 000 F, cap. 10).
 * Acheteur 1 (commandes de P et historiques), acheteur 4 (commande de P2).
 *
 * | Cmd | Statut     | Date de vente    | Lignes          | Total  | Billets                  | Commission 6 %            |
 * | O1  | paid       | 2026-11-03       | 2×A             | 20 000 | 1 sold + 1 used (pivot)  | 1 200 / net 18 800 pending|
 * | O2  | pending    | 2026-11-05 (cr.) | 1×A             | 10 000 | 1 reserved               | aucune (créée au paiement)|
 * | O3  | refunded   | 2026-11-06       | 1×A             | 10 000 | 1 cancelled              | 600 / net 9 400 held      |
 * | O4  | paid       | 2026-11-10       | 1×A + 1×B       | 15 000 | 2 sold (order_item_id)   | 900 / net 14 100 PAID     |
 * | O5  | paid GRATUIT| 2026-11-12      | 1×C (0 F)       |      0 | 1 sold (pivot + item)    | aucune                    |
 * | O6  | failed     | 2026-11-07 (cr.) | 1×A             | 10 000 | 1 cancelled              | 600 cancelled             |
 * | O7  | paid (P2)  | 2026-11-08       | 1×D             | 20 000 | 1 sold                   | 1 200 / net 18 800 pending|
 * | O8  | paid       | 2026-10-20 (mois précédent) | 1×A  | 10 000 | 1 sold                   | 600 / net 9 400 pending   |
 * | O9  | paid       | 2025-11-20 (année précédente)| 1×B | 5 000  | 1 sold                   | 300 / net 4 700 pending   |
 * quantity_sold : A = 5 (O1 2 + O2 1 + O4 1 + O8 1), B = 2, C = 1, D = 1.
 */
trait StatsFixture
{
    use BuildsOrderSchema;

    public const P = 2;   // promoteur de l'événement 1

    public const P2 = 3;  // autre promoteur (événement 2)

    public const BUYER = 1;

    public const BUYER2 = 4;

    /** @var array<string,int> id des commandes par clé (O1..O9) */
    protected array $o = [];

    protected function buildStatsFixture(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-15 12:00:00', 'Africa/Abidjan'));
        $this->buildOrderSchema();

        DB::table('users')->insert([
            ['id' => self::P, 'name' => 'Promoteur P', 'email' => 'p@example.test', 'role' => 'promoteur'],
            ['id' => self::P2, 'name' => 'Promoteur P2', 'email' => 'p2@example.test', 'role' => 'promoteur'],
            ['id' => self::BUYER2, 'name' => 'Acheteur 2', 'email' => 'b2@example.test', 'role' => 'acheteur'],
        ]);
        DB::table('events')->insert(['id' => 2, 'promoter_id' => self::P2, 'title' => 'Festival P2', 'venue' => 'Parc', 'event_date' => '2027-02-01', 'event_time' => '18:00']);

        foreach ([
            [1, 1, 'A', 10000, 100, 5],
            [2, 1, 'B', 5000, 50, 2],
            [3, 1, 'C gratuit', 0, 20, 1],
            [4, 2, 'D', 20000, 10, 1],
        ] as [$id, $event, $name, $price, $cap, $sold]) {
            DB::table('ticket_types')->insert([
                'id' => $id, 'event_id' => $event, 'name' => $name, 'price' => $price, 'quantity_available' => $cap,
                'quantity_sold' => $sold, 'is_active' => true, 'created_at' => '2026-10-01 08:00:00', 'updated_at' => '2026-10-01 08:00:00',
            ]);
        }

        // O1 : 2×A payée, 1 billet sold + 1 used (rattachés par le pivot order_tickets)
        $this->makeOrder('O1', 'paid', 1, 20000, '2026-11-03 09:00:00', '2026-11-03 10:00:00', [[1, 2, 20000]], ['sold', 'used'], 'pivot',
            ['commission' => [1200, 18800, 'pending']]);
        // O2 : en attente, billet réservé, aucune commission
        $this->makeOrder('O2', 'pending', 1, 10000, '2026-11-05 09:00:00', null, [[1, 1, 10000]], ['reserved'], 'pivot');
        // O3 : remboursée (billet annulé, commission held)
        $this->makeOrder('O3', 'refunded', 1, 10000, '2026-11-06 09:00:00', '2026-11-06 10:00:00', [[1, 1, 10000]], ['cancelled'], 'pivot',
            ['commission' => [600, 9400, 'held'], 'updated_at' => '2026-11-07 09:00:00']);
        // O4 : A + B (deux lignes), billets liés par order_item_id seulement, commission déjà versée
        $this->makeOrder('O4', 'paid', 1, 15000, '2026-11-10 09:00:00', '2026-11-10 09:30:00', [[1, 1, 10000], [2, 1, 5000]], ['sold', 'sold'], 'item',
            ['commission' => [900, 14100, 'paid']]);
        // O5 : GRATUITE payée à 0 F (billet lié par le pivot ET order_item_id : ne doit pas être compté deux fois)
        $this->makeOrder('O5', 'paid', 1, 0, '2026-11-12 09:00:00', '2026-11-12 09:00:00', [[3, 1, 0]], ['sold'], 'both',
            ['payment_method' => 'free', 'payment_reference' => 'GRATUIT']);
        // O6 : échouée (libérée), commission cancelled
        $this->makeOrder('O6', 'failed', 1, 10000, '2026-11-07 09:00:00', null, [[1, 1, 10000]], ['cancelled'], 'pivot',
            ['commission' => [600, 9400, 'cancelled']]);
        // O7 : promoteur P2 (isolation)
        $this->makeOrder('O7', 'paid', self::BUYER2, 20000, '2026-11-08 09:00:00', '2026-11-08 09:10:00', [[4, 1, 20000]], ['sold'], 'pivot',
            ['event' => 2, 'commission' => [1200, 18800, 'pending']]);
        // O8 : mois précédent (octobre 2026)
        $this->makeOrder('O8', 'paid', 1, 10000, '2026-10-20 09:00:00', '2026-10-20 09:10:00', [[1, 1, 10000]], ['sold'], 'pivot',
            ['commission' => [600, 9400, 'pending']]);
        // O9 : année précédente (novembre 2025 : piège du whereMonth sans année)
        $this->makeOrder('O9', 'paid', 1, 5000, '2025-11-20 09:00:00', '2025-11-20 09:10:00', [[2, 1, 5000]], ['sold'], 'pivot',
            ['commission' => [300, 4700, 'pending']]);
    }

    /**
     * @param  array<int, array{0:int,1:int,2:int}>  $lines  [ticket_type_id, quantité, total ligne]
     * @param  list<string>  $ticketStatuses  un statut par billet
     * @param  'pivot'|'item'|'both'  $link
     */
    private function makeOrder(string $key, string $status, int $userId, int $total, string $created, ?string $paidAt, array $lines, array $ticketStatuses, string $link, array $o = []): void
    {
        $orderId = DB::table('orders')->insertGetId([
            'user_id' => $userId, 'event_id' => $o['event'] ?? 1, 'total_amount' => $total, 'payment_status' => $status,
            'payment_method' => $o['payment_method'] ?? 'bank_transfer',
            'payment_reference' => $o['payment_reference'] ?? null,
            'order_number' => 'ORD-STATS-'.$key, 'billing_email' => 'x@example.test',
            'paid_at' => $paidAt, 'created_at' => $created, 'updated_at' => $o['updated_at'] ?? ($paidAt ?? $created),
        ]);
        $this->o[$key] = $orderId;

        $itemIds = [];
        foreach ($lines as [$typeId, $qty, $lineTotal]) {
            $itemIds[] = [DB::table('order_items')->insertGetId([
                'order_id' => $orderId, 'ticket_type_id' => $typeId, 'quantity' => $qty,
                'unit_price' => $qty > 0 ? intdiv($lineTotal, $qty) : 0, 'total_price' => $lineTotal,
                'created_at' => $created, 'updated_at' => $created,
            ]), $typeId, $qty];
        }

        // un billet par entrée de $ticketStatuses, réparti sur les lignes dans l'ordre
        $slots = [];
        foreach ($itemIds as [$itemId, $typeId, $qty]) {
            for ($i = 0; $i < $qty; $i++) {
                $slots[] = [$itemId, $typeId];
            }
        }
        foreach ($ticketStatuses as $i => $ticketStatus) {
            [$itemId, $typeId] = $slots[$i];
            $ticketId = DB::table('tickets')->insertGetId([
                'ticket_type_id' => $typeId, 'order_item_id' => $link === 'pivot' ? null : $itemId,
                'ticket_code' => 'TKT-'.$key.'-'.$i, 'status' => $ticketStatus,
                'created_at' => $created, 'updated_at' => $created,
            ]);
            if ($link !== 'item') {
                DB::table('order_tickets')->insert(['order_id' => $orderId, 'ticket_id' => $ticketId, 'order_item_id' => $itemId]);
            }
        }

        if (isset($o['commission'])) {
            [$commission, $net, $cStatus] = $o['commission'];
            DB::table('commissions')->insert([
                'order_id' => $orderId, 'promoter_id' => ($o['event'] ?? 1) === 2 ? self::P2 : self::P,
                'gross_amount' => $total, 'commission_rate' => 6, 'commission_amount' => $commission, 'net_amount' => $net,
                'platform_fee' => 0, 'status' => $cStatus, 'paid_at' => $cStatus === 'paid' ? $paidAt : null,
                'created_at' => $paidAt ?? $created, 'updated_at' => $paidAt ?? $created,
            ]);
        }
    }
}
