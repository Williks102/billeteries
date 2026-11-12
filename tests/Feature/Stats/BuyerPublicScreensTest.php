<?php

namespace Tests\Feature\Stats;

use App\Helpers\CurrencyHelper;
use App\Models\User;
use App\Services\Stats\PublicStats;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\StatsFixture;
use Tests\TestCase;

/**
 * Écrans ACHETEUR et PUBLIC branchés sur les services Stats (L5), jeu canonique (StatsFixture), « maintenant » = 2026-11-15.
 *
 * Acheteur 1 (fixture) : O1 paid 20 000 (2 billets dont 1 scanné), O2 pending, O3 refunded 10 000, O4 paid 15 000,
 * O5 paid GRATUITE 0, O6 failed, O8 paid 10 000 (octobre), O9 paid 5 000 (2025). Ajoutés ici :
 *   événement 3 (hier, 2026-11-14) : O10 paid 8 000 + O13 paid 9 000 (MÊME événement : compté une fois) ;
 *   événement 4 (aujourd'hui) : O11 paid 6 000 ; événement 5 (passé) : O12 PENDING seulement.
 * Valeurs cibles de l'acheteur 1 (calculées à la main) :
 *   commandes 12 = 5 payées (O1 O4 O5 O8 O9) + 3 payées (O10 O11 O13) = 8 payées, 2 en attente (O2 O12), 1 remboursée (O3), 1 échouée (O6) ;
 *   billets 10 = 2 + 2 + 1 + 1 + 1 + (1 + 1 + 1) ; dépensé 73 000 = 20 000 + 15 000 + 0 + 10 000 + 5 000 + 8 000 + 6 000 + 9 000
 *   (O3 remboursée 10 000 : hors dépensé, affichée à part) ; à venir 2 (événement 1 du 2027-01-01, événement 4 d'aujourd'hui) ;
 *   passés 1 (événement 3 : hier ; l'événement 5 n'a qu'une commande en attente).
 * Acheteur 4 : une commande payée de 7 777 sur l'événement 2 (+ O7 20 000) : 2 commandes, 2 billets, 27 777 dépensés.
 * Acheteur 5 : aucune commande.
 * Public : événements à venir 3 (1, 2, 4 ; le brouillon 6 est exclu), billets vendus 12 (sold + used des commandes payées :
 *   8 du jeu canonique dont 1 `used` + 4 ajoutés (O10 O11 O13 O14) ; réservé / remboursé / échoué exclus), organisateurs 2.
 */
class BuyerPublicScreensTest extends TestCase
{
    use StatsFixture;

    private const BUYER_PRIVATE = 7777; // montant propre à l'acheteur 4 (ne doit jamais apparaître chez l'acheteur 1)

    protected function setUp(): void
    {
        parent::setUp();
        $this->buildStatsFixture();
        $this->makeUser(10, 'admin');
        $this->makeUser(5, 'acheteur');
        DB::table('users')->update(['created_at' => '2026-10-01 08:00:00', 'updated_at' => '2026-10-01 08:00:00']);
        DB::table('events')->update(['created_at' => '2026-11-02 08:00:00', 'updated_at' => '2026-11-02 08:00:00']);

        DB::table('event_categories')->insert(['id' => 1, 'name' => 'Concert', 'slug' => 'concert']);
        DB::table('events')->update(['category_id' => 1]);

        foreach ([[3, '2026-11-14', 'published'], [4, '2026-11-15', 'published'], [5, '2026-09-01', 'published'], [6, '2027-03-01', 'draft']] as [$id, $date, $status]) {
            DB::table('events')->insert(['id' => $id, 'promoter_id' => self::P, 'title' => "Evenement {$id}", 'venue' => 'Salle', 'event_date' => $date, 'event_time' => '19:00', 'category_id' => 1, 'status' => $status, 'created_at' => '2026-11-02 08:00:00', 'updated_at' => '2026-11-02 08:00:00']);
        }
        foreach ([[5, 3, 8000, 20, 2], [6, 4, 6000, 20, 1], [7, 5, 4000, 20, 1]] as [$id, $event, $price, $cap, $sold]) {
            DB::table('ticket_types')->insert(['id' => $id, 'event_id' => $event, 'name' => "T{$id}", 'price' => $price, 'quantity_available' => $cap, 'quantity_sold' => $sold, 'is_active' => true, 'created_at' => '2026-10-01 08:00:00', 'updated_at' => '2026-10-01 08:00:00']);
        }

        $this->extraOrder('O10', 'paid', 1, 8000, 3, 5, '2026-11-10 09:00:00', 'sold');
        $this->extraOrder('O13', 'paid', 1, 9000, 3, 5, '2026-11-11 09:00:00', 'sold');
        $this->extraOrder('O11', 'paid', 1, 6000, 4, 6, '2026-11-12 09:00:00', 'sold');
        $this->extraOrder('O12', 'pending', 1, 4000, 5, 7, null, 'reserved');
        $this->extraOrder('O14', 'paid', self::BUYER2, self::BUYER_PRIVATE, 2, 4, '2026-11-13 09:00:00', 'sold');
    }

    private function extraOrder(string $key, string $status, int $user, int $total, int $event, int $type, ?string $paidAt, string $ticket): void
    {
        $this->makeOrder($key, $status, $user, $total, $paidAt ?? '2026-11-13 08:00:00', $paidAt, [[$type, 1, $total]], [$ticket], 'pivot', ['event' => $event]);
    }

    private function as(int $id): static
    {
        return $this->actingAs(User::find($id));
    }

    private function card(string $html, string $slug): string
    {
        $this->assertSame(1, preg_match('/data-stat="'.preg_quote($slug, '/').'".*?kpi-card__value">(.*?)<\/p>/s', $html, $m), "Carte {$slug} introuvable");

        return trim(preg_replace('/\s+/u', ' ', str_replace(CurrencyHelper::NBSP, ' ', html_entity_decode(strip_tags($m[1])))));
    }

    private function fcfa(int $v): string
    {
        return number_format($v, 0, ',', ' ').' FCFA';
    }

    private function text(string $html): string
    {
        return str_replace(CurrencyHelper::NBSP, ' ', html_entity_decode(strip_tags($html)));
    }

    // ---------- acheteur : valeurs cibles ----------

    public function test_buyer_dashboard_shows_exact_target_figures(): void
    {
        $html = $this->as(self::BUYER)->get(route('acheteur.dashboard'))->assertOk()
            ->assertSee('toutes dates')->getContent();

        $this->assertSame('12', $this->card($html, 'commandes'));
        $this->assertSame('8', $this->card($html, 'commandes-payees'));
        $this->assertSame('2', $this->card($html, 'en-attente-de-paiement'));
        $this->assertSame('1', $this->card($html, 'remboursees'));
        $this->assertSame('10', $this->card($html, 'billets-achetes'));
        $this->assertSame($this->fcfa(73000), $this->card($html, 'depense'));
        $this->assertSame('2', $this->card($html, 'evenements-a-venir'));
        $this->assertSame('1', $this->card($html, 'evenements-passes')); // était toujours 0 (builder mutable)
        $this->assertStringContainsString('Dont 1 échouée(s)', $html);
    }

    public function test_buyer_orders_page_and_profile_show_the_same_figures(): void
    {
        $orders = $this->as(self::BUYER)->get(route('acheteur.orders.index'))->assertOk()->getContent();
        $profile = $this->as(self::BUYER)->get(route('acheteur.profile.show'))->assertOk()->getContent();

        $this->assertSame('12', $this->card($orders, 'commandes'));
        $this->assertSame('8', $this->card($orders, 'commandes-payees'));
        $this->assertSame('10', $this->card($orders, 'billets-achetes'));
        $this->assertSame($this->fcfa(73000), $this->card($orders, 'depense'));

        $this->assertSame('12', $this->card($profile, 'commandes'));
        $this->assertSame('8', $this->card($profile, 'commandes-payees'));
        $this->assertSame('10', $this->card($profile, 'billets'));
        $this->assertSame($this->fcfa(73000), $this->card($profile, 'depense'));
    }

    public function test_refunded_pending_and_failed_orders_never_count_as_spent(): void
    {
        $html = $this->as(self::BUYER)->get(route('acheteur.dashboard'))->getContent();

        // O3 (10 000 remboursée), O2/O12 (en attente), O6 (échouée) : si l'une comptait, le total serait supérieur à 73 000
        $this->assertNotSame($this->fcfa(83000), $this->card($html, 'depense'));
        $this->assertSame($this->fcfa(73000), $this->card($html, 'depense'));
    }

    public function test_free_paid_order_counts_as_order_and_ticket_for_zero_francs(): void
    {
        DB::table('orders')->where('user_id', 1)->where('id', '!=', $this->o['O5'])->delete();
        DB::table('orders')->where('user_id', 1)->update(['payment_status' => 'paid']);
        // seule O5 (gratuite, 1 billet) reste pour l'acheteur 1
        $html = $this->as(self::BUYER)->get(route('acheteur.dashboard'))->getContent();

        $this->assertSame('1', $this->card($html, 'commandes'));
        $this->assertSame('1', $this->card($html, 'commandes-payees'));
        $this->assertSame('1', $this->card($html, 'billets-achetes'));
        $this->assertSame('0 FCFA', $this->card($html, 'depense'));
    }

    public function test_buyer_without_order_sees_zeros_not_dashes_or_errors(): void
    {
        $html = $this->as(5)->get(route('acheteur.dashboard'))->assertOk()->getContent();

        foreach (['commandes', 'commandes-payees', 'en-attente-de-paiement', 'remboursees', 'billets-achetes', 'evenements-a-venir', 'evenements-passes'] as $slug) {
            $this->assertSame('0', $this->card($html, $slug), $slug);
        }
        $this->assertSame('0 FCFA', $this->card($html, 'depense'));
    }

    public function test_event_dated_today_is_upcoming_and_yesterday_is_past(): void
    {
        $service = app(\App\Services\Stats\BuyerStats::class);

        $at = fn (string $when) => $service->overview(self::BUYER, \Carbon\Carbon::parse($when, 'Africa/Abidjan'));

        // 15/11 : événement 4 (15/11) à venir, événement 3 (14/11) passé
        $this->assertSame([2, 1], [$at('2026-11-15 12:00')['events_upcoming'], $at('2026-11-15 12:00')['events_past']]);
        $this->assertSame([2, 1], [$at('2026-11-15 23:59')['events_upcoming'], $at('2026-11-15 23:59')['events_past']]);
        // le lendemain, l'événement du 15 est passé
        $this->assertSame([1, 2], [$at('2026-11-16 00:01')['events_upcoming'], $at('2026-11-16 00:01')['events_past']]);
    }

    // ---------- acheteur : isolation ----------

    public function test_a_buyer_never_sees_another_buyers_figures_on_any_screen(): void
    {
        $mine = $this->as(self::BUYER2);
        $dashboard = $mine->get(route('acheteur.dashboard'))->assertOk()->getContent();
        $orders = $mine->get(route('acheteur.orders.index'))->assertOk()->getContent();
        $profile = $mine->get(route('acheteur.profile.show'))->assertOk()->getContent();

        foreach ([$dashboard, $orders, $profile] as $html) {
            $this->assertSame('2', $this->card($html, 'commandes'));
            $this->assertStringNotContainsString('73', $this->card($html, 'depense'));
            $this->assertSame($this->fcfa(20000 + self::BUYER_PRIVATE), $this->card($html, 'depense'));
        }
        $this->assertSame('2', $this->card($dashboard, 'billets-achetes'));

        // la liste ne contient QUE ses commandes
        $this->assertStringContainsString('ORD-STATS-O14', $orders);
        $this->assertStringContainsString('ORD-STATS-O7', $orders);
        foreach (['O1', 'O2', 'O3', 'O4', 'O5', 'O6', 'O8', 'O9', 'O10', 'O11', 'O12', 'O13'] as $other) {
            $this->assertDoesNotMatchRegularExpression('/ORD-STATS-'.$other.'\b/', $orders);
        }

        // et inversement : l'acheteur 1 ne voit jamais les 7 777 de l'acheteur 4
        $theirs = $this->as(self::BUYER)->get(route('acheteur.dashboard'))->getContent();
        $this->assertStringNotContainsString('7 777', $this->text($theirs));
        $this->assertDoesNotMatchRegularExpression('/ORD-STATS-O14\b/', $this->as(self::BUYER)->get(route('acheteur.orders.index'))->getContent());
    }

    public function test_buyer_service_scope_is_the_only_source(): void
    {
        $service = app(\App\Services\Stats\BuyerStats::class);

        $a = $service->overview(self::BUYER);
        $b = $service->overview(self::BUYER2);
        $this->assertSame([12, 8, 10, 73000], [$a['orders_total'], $a['orders_paid'], $a['tickets'], $a['spent']]);
        $this->assertSame([2, 2, 2, 27777], [$b['orders_total'], $b['orders_paid'], $b['tickets'], $b['spent']]);
        $this->assertSame(10000, $a['refunded_amount']);
        $this->assertSame(0, $b['refunded_amount']);

        $this->assertSame([self::BUYER], $service->recentOrders(self::BUYER, 50)->pluck('user_id')->unique()->all());
        $this->assertSame([self::BUYER], $service->upcomingOrders(self::BUYER, 50)->pluck('user_id')->unique()->all());
    }

    public function test_guest_is_redirected_to_login_on_buyer_screens(): void
    {
        foreach (['acheteur.dashboard', 'acheteur.orders.index', 'acheteur.profile.show'] as $route) {
            $this->get(route($route))->assertRedirect();
        }
    }

    // ---------- budget de requêtes ----------

    public function test_buyer_screens_use_a_constant_number_of_queries(): void
    {
        $measure = function (): array {
            $out = [];
            foreach (['acheteur.dashboard', 'acheteur.orders.index', 'acheteur.profile.show'] as $route) {
                DB::flushQueryLog();
                DB::enableQueryLog();
                $this->as(self::BUYER)->get(route($route))->assertOk();
                $out[$route] = count(DB::getQueryLog());
            }

            return $out;
        };

        $before = $measure();

        for ($i = 0; $i < 12; $i++) {
            $this->extraOrder('X'.$i, $i % 3 === 0 ? 'pending' : 'paid', self::BUYER, 1000 + $i, 1 + ($i % 2 ? 2 : 0) + ($i % 3 === 0 ? 1 : 0), 1, '2026-11-13 09:00:00', 'sold');
        }

        $this->assertSame($before, $measure(), 'Le nombre de requêtes dépend du nombre de commandes (N+1)');
    }

    // ---------- public ----------

    public function test_home_public_counters_are_the_target_values(): void
    {
        $html = $this->get(route('home'))->assertOk()->getContent();

        $this->assertSame('3', $this->card($html, 'evenements-a-venir'));   // 1, 2, 4 ; brouillon 6 exclu
        $this->assertSame('12', $this->card($html, 'billets-vendus'));      // sold + used des commandes payées
        $this->assertSame('2', $this->card($html, 'organisateurs'));
    }

    public function test_a_used_ticket_counts_and_unpaid_or_refunded_orders_do_not(): void
    {
        $stats = app(PublicStats::class);
        $this->assertSame(12, $stats->homeCounters()['tickets_sold']);

        // le billet scanné de O1 reste vendu ; le retirer ferait tomber le total à 11
        $this->assertSame(1, DB::table('tickets')->where('status', 'used')->count());

        // une commande remboursée (O3), en attente (O2, O12) ou échouée (O6) n'ajoute rien, même avec des billets « sold »
        DB::table('tickets')->whereIn('ticket_code', ['TKT-O2-0', 'TKT-O3-0', 'TKT-O6-0', 'TKT-O12-0'])->update(['status' => 'sold']);
        $this->assertSame(12, $stats->homeCounters()['tickets_sold']);
    }

    public function test_home_and_event_pages_expose_no_per_user_data(): void
    {
        $home = $this->get(route('home'))->assertOk()->getContent();
        DB::table('ticket_types')->where('event_id', 1)->update(['sale_start_date' => '2026-10-01 00:00:00', 'sale_end_date' => '2027-01-01 00:00:00']);
        $event = $this->get(route('events.show', 1))->assertOk()->getContent();

        foreach ([$home, $event] as $html) {
            foreach (['acheteur@example.test', 'b2@example.test', 'p2@example.test', 'ORD-STATS', 'x@example.test', 'TKT-O'] as $needle) {
                $this->assertStringNotContainsString($needle, $html, "« {$needle} » ne doit jamais apparaître sur une page publique");
            }
            $this->assertStringNotContainsString('7 777', $this->text($html));
            $this->assertStringNotContainsString('73 000', $this->text($html));
        }
        $this->assertStringNotContainsStringIgnoringCase('visiteur', $this->text($home));
    }

    public function test_public_event_page_shows_real_remaining_places_and_no_sales_figures(): void
    {
        // types actifs et en vente ; B presque complet (45 vendus + réservés sur 50 => 5 restants)
        DB::table('ticket_types')->where('event_id', 1)->update(['sale_start_date' => '2026-10-01 00:00:00', 'sale_end_date' => '2027-01-01 00:00:00']);
        DB::table('ticket_types')->where('id', 2)->update(['quantity_sold' => 45]);

        $html = $this->get(route('events.show', 1))->assertOk()->getContent();

        // A : 100 - quantity_sold (5, réservation O2 non payée comprise) = 95 (et non 96 en ne comptant que les ventes payées)
        $this->assertStringContainsString('data-available="95"', $html);
        $this->assertStringContainsString('data-available="5"', $html);
        $this->assertStringContainsString('data-available="19"', $html);
        // total 95 + 5 + 19 = 119 places restantes (fiche mobile)
        $this->assertStringContainsString('119', $this->text($html));
        $this->assertStringContainsString('places restantes', $html);
        // les badges continuent de fonctionner
        $this->assertStringContainsString('Bientôt complet · 5 restants', $this->text($html));
        $this->assertStringContainsString('Disponible', $this->text($html));
        // pas de chiffres de vente détaillés au public
        foreach (['Billets vendus', 'billets vendus', 'Revenu', "Chiffre d'affaires", 'Taux de remplissage'] as $needle) {
            $this->assertStringNotContainsString($needle, $html);
        }
        $this->assertDoesNotMatchRegularExpression('/\d+(,\d)?\s?%\s*(vendu|rempli)/i', $this->text($html));
    }

    public function test_remaining_places_never_negative_and_do_not_load_orders(): void
    {
        $stats = app(PublicStats::class);
        $type = \App\Models\TicketType::find(1);
        $type->quantity_sold = 130; // surplus (cas d'incohérence de données)
        $this->assertSame(0, $stats->remainingForType($type));

        DB::table('ticket_types')->where('event_id', 1)->update(['sale_start_date' => '2026-10-01 00:00:00', 'sale_end_date' => '2027-01-01 00:00:00']);
        DB::flushQueryLog();
        DB::enableQueryLog();
        $this->get(route('events.show', 1))->assertOk();
        $this->assertEmpty(array_filter(DB::getQueryLog(), fn ($q) => str_contains($q['query'], 'from "orders"') || str_contains($q['query'], 'from `orders`')), 'La fiche publique ne doit lire aucune commande');
    }
}
