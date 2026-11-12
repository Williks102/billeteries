<?php

namespace Tests\Feature\Stats;

use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Promoteur\PromoteurController;
use App\Http\Controllers\Promoteur\ReportsController;
use App\Http\Controllers\Promoteur\ScannerController;
use App\Models\Event;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use PHPUnit\Framework\Attributes\Group;
use Tests\Concerns\StatsFixture;
use Tests\TestCase;

/**
 * Caractérisation des écrans HÉRITÉS sur le jeu canonique (tests/Concerns/StatsFixture.php) : ces tests figent
 * les valeurs ACTUELLES, y compris les valeurs fausses. Chaque test documente la valeur CIBLE (celle des services
 * App\Services\Stats, prouvée dans Sales/Ticket/Commission/OrderStatsTest) et la raison de l'écart.
 * Aux lots L3 (admin), L4 (promoteur) et L5 (acheteur/public), l'écran est branché sur les services et
 * l'assertion correspondante doit être retournée vers la valeur CIBLE (ne PAS les supprimer).
 * L3 FAIT : le dashboard admin (test_admin_dashboard_now_shows_target_values_...) est retourné vers la cible.
 *
 * Aucun contrôleur n'est modifié par ce lot. Les contrôleurs sont appelés directement et la vue renvoyée est
 * lue (getData) sans être rendue.
 *
 * Non figé (non testable ici) : AnalyticsController (HOUR(), DATE() propres à MySQL), ReportsController::getDailySales
 * (DATE_FORMAT) : SQLite ne les exécute pas, ils ne sont pas réécrits. Admin\TicketController::index (taux
 * d'utilisation « admin tickets ») lève RelationNotFoundException sur main (relation Ticket::orderTickets
 * inexistante) : écran cassé, voir test_admin_tickets_index_is_broken_on_main.
 */
#[Group('legacy')]
class LegacyCharacterizationTest extends TestCase
{
    use StatsFixture;

    protected function setUp(): void
    {
        parent::setUp();
        $this->buildStatsFixture();
    }

    /**
     * L3 : le dashboard admin est branché sur App\Services\Stats\* ; ce test, qui figeait les valeurs FAUSSES
     * (70 000 / 60 000 / 9 commandes), est retourné vers les valeurs CIBLE. Il ne doit plus jamais repasser « faux ».
     */
    public function test_admin_dashboard_now_shows_target_values_instead_of_legacy_wrong_ones(): void
    {
        $this->actingAs($this->makeUser(10, 'admin'));

        // Toute la période : GMV = 70 000 (6 commandes payées) ; plus de Order::count() (9, tous statuts).
        $all = app(AdminController::class)->dashboard(Request::create('/admin/dashboard', 'GET', ['period' => 'all']))->getData()['summary'];
        $this->assertSame(70000, $all['gmv']);
        $this->assertSame(6, $all['orders_paid']);

        // Période par défaut = mois civil en cours (novembre 2026) : 55 000 (O1 20 000 + O4 15 000 + O5 0 + O7 20 000).
        // Ancienne valeur fausse : 60 000 (whereMonth sans année cumulait novembre 2025, O9 5 000).
        $month = app(AdminController::class)->dashboard(Request::create('/admin/dashboard'))->getData()['summary'];
        $this->assertSame(55000, $month['gmv']);
        $this->assertSame(4, $month['orders_paid']);
    }

    public function test_promoter_report_revenue_is_inflated_by_the_join_on_order_items(): void
    {
        $stats = $this->reportsMainStats(self::P);

        // total_revenue = SUM(orders.total_amount) après JOIN order_items : O4 (2 lignes) comptée 2 fois => 50 000.
        // CIBLE : 35 000 (GMV de P en novembre = O1 20 000 + O4 15 000 + O5 0).
        $this->assertSame(50000, (int) $stats['total_revenue']);
        $this->assertSame(50000, (int) $stats['period_revenue']);
        // Panier moyen = revenu gonflé / commandes : 50 000 / 3. CIBLE : 11 667 (35 000 / 3).
        $this->assertEqualsWithDelta(16666.67, $stats['average_order'], 0.01);
        // Billets vendus = tickets.status = 'sold' créés dans la période : le billet scanné (used) disparaît => 4.
        // CIBLE : 5 (sold + used d'une commande payée : O1 2, O4 2, O5 1).
        $this->assertSame(4, $stats['tickets_sold']);
        // total_orders (DISTINCT) est correct : 3 (identique à la cible).
        $this->assertSame(3, $stats['total_orders']);
        // total_commissions = commissions de TOUS statuts créées dans la période (pending, paid, held, cancelled) :
        // 1 200 + 600 (held) + 900 + 600 (cancelled) = 3 300. CIBLE : 2 100 (O1 1 200 + O4 900 ; held à part, cancelled exclue).
        $this->assertSame(3300, (int) $stats['total_commissions']);
    }

    public function test_promoter_dashboard_counts_every_non_available_ticket_and_only_paid_out_net(): void
    {
        $this->actingAs(User::find(self::P));

        $stats = app(PromoteurController::class)->dashboard()->getData()['stats'];

        // total_tickets_sold = status != 'available' : sold + used + reserved + cancelled = 10.
        // CIBLE : 7 (sold + used de commandes payées de P, toutes périodes : O1 2, O4 2, O5 1, O8 1, O9 1).
        $this->assertSame(10, $stats['total_tickets_sold']);
        // total_revenue = net des commissions au statut `paid` seulement : 14 100 (O4). CIBLE : 47 000
        // (net de toutes les commandes payées : 14 100 versé + 32 900 en attente de versement).
        $this->assertSame(14100, (int) $stats['total_revenue']);
    }

    public function test_event_ticket_counts_and_commission_earned(): void
    {
        $event = Event::find(1);

        // getTicketsSoldCount = sold + used SANS vérifier la commande : 7 (= cible ici, aucun billet vendu hors commande payée).
        $this->assertSame(7, $event->getTicketsSoldCount());
        // quantity_sold (page publique, admin événements) inclut la réservation non payée O2 : 8. CIBLE : 7 (décision :
        // réservations non payées = disponibles, remplissage = billets vendus seulement).
        $this->assertSame(8, (int) $event->ticketTypes()->sum('quantity_sold'));
        // getProgressPercentage : 7 / 170 arrondi à 2 décimales = 4.12. CIBLE : 4.1 (TicketStats::fillRate, 1 décimale).
        $this->assertSame(4.12, (float) $event->getProgressPercentage());
        // getCommissionEarned = commissions au statut `paid` : 900 (O4 seule). CIBLE : 3 000 (CommissionStats::platformRevenue
        // de E, commandes payées pending + paid : 1 200 + 900 + 600 + 300).
        $this->assertSame(900, (int) $event->getCommissionEarned());
    }

    public function test_usage_rate_excludes_used_tickets_from_the_denominator(): void
    {
        $this->actingAs(User::find(self::P));

        $row = app(ScannerController::class)->stats()->getData()['eventStats']->first();

        // sold_tickets compte `sold` SANS `used` : 6 ; usage_rate = 1 / 6 = 16.7 %.
        // CIBLE : 1 / 7 = 14.3 % (TicketStats::usageRate : scannés / (sold + used)).
        $this->assertSame(6, $row['sold_tickets']);
        $this->assertSame(1, $row['used_tickets']);
        $this->assertSame(16.7, (float) $row['usage_rate']);
    }

    public function test_admin_tickets_index_is_broken_on_main(): void
    {
        $this->actingAs($this->makeUser(10, 'admin'));

        // L'écran admin « billets » (usage_rate = used / sold hors used, valeur_billets au prix courant) ne s'affiche
        // plus : Ticket::orderTickets n'existe pas. Signalé, non corrigé ici (hors périmètre L0).
        $this->expectException(\Illuminate\Database\Eloquent\RelationNotFoundException::class);
        app(\App\Http\Controllers\Admin\TicketController::class)->index(\Illuminate\Http\Request::create('/'));
    }

    private function reportsMainStats(int $promoterId): array
    {
        $method = new \ReflectionMethod(ReportsController::class, 'getMainStats');
        $method->setAccessible(true);

        return $method->invoke(
            app(ReportsController::class),
            $promoterId,
            Carbon::parse('2026-11-01 00:00:00'),
            Carbon::parse('2026-11-30 23:59:59'),
        );
    }
}
