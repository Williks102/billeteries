<?php

namespace Tests\Feature\Stats;

use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Promoteur\PromoteurController;
use App\Http\Controllers\Promoteur\ReportsController;
use App\Http\Controllers\Promoteur\ScannerController;
use App\Models\Event;
use App\Models\User;
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
 * L4 FAIT : les écrans promoteur (dashboard, rapports, scanner, événement) sont retournés vers la cible.
 * L3 FAIT : le dashboard admin (test_admin_dashboard_now_shows_target_values_...) est retourné vers la cible.
 *
 * Aucun contrôleur n'est modifié par ce lot. Les contrôleurs sont appelés directement et la vue renvoyée est
 * lue (getData) sans être rendue.
 *
 * Non figé (non testable ici) : AnalyticsController (HOUR(), DATE() propres à MySQL), ReportsController::getDailySales
 * (DATE_FORMAT) : SQLite ne les exécute pas, ils ne sont pas réécrits. Admin\TicketController::index (taux
 * d'utilisation « admin tickets ») : réparé (mission admin-billets), assertion retournée vers la cible.
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

    /**
     * L4 : rapport promoteur branché sur PromoterStats. Ce test, qui figeait les valeurs FAUSSES (JOIN order_items
     * + SUM(orders.total_amount) : 50 000, panier 16 667, billets 4, commissions tous statuts 3 300), est retourné
     * vers la CIBLE. Il ne doit plus jamais repasser « faux ».
     */
    public function test_promoter_report_now_shows_target_values_instead_of_the_duplicating_join(): void
    {
        $this->actingAs(User::find(self::P));
        $stats = app(ReportsController::class)->index(Request::create('/promoteur/reports'))->getData()['stats'];

        // Ventes = GMV de novembre : O1 20 000 + O4 15 000 (2 lignes, comptée UNE fois) + O5 0 = 35 000 (avant : 50 000).
        $this->assertSame(35000, $stats['gmv']);
        // Panier moyen = 35 000 / 3 = 11 667 (avant : 16 667 = 50 000 / 3).
        $this->assertSame(11667, $stats['average_basket']);
        // Billets vendus = sold + used d'une commande payée : O1 2, O4 2, O5 1 = 5 (avant : 4, `sold` seulement).
        $this->assertSame(5, $stats['tickets_sold']);
        $this->assertSame(3, $stats['orders_paid']);
        // Commission = commandes payées seulement (pending + paid) : O1 1 200 + O4 900 = 2 100 (avant : 3 300, tous statuts).
        $this->assertSame(2100, $stats['platform_revenue']);
    }

    /**
     * L4 : dashboard promoteur branché sur PromoterStats (avant : 10 billets « != available », revenu = net `paid` seul 14 100).
     */
    public function test_promoter_dashboard_now_shows_target_values(): void
    {
        $this->actingAs(User::find(self::P));

        $summary = app(PromoteurController::class)->dashboard(Request::create('/promoteur/dashboard', 'GET', ['period' => 'all']))->getData()['summary'];

        // Billets vendus (toutes dates) = sold + used de commandes payées de P : O1 2, O4 2, O5 1, O8 1, O9 1 = 7 (avant : 10).
        $this->assertSame(7, $summary['tickets_sold']);
        // Revenu net de P = 47 000 : à recevoir 32 900 (O1 18 800 + O8 9 400 + O9 4 700) + versé 14 100 (O4). Avant : 14 100.
        $this->assertSame(47000, $summary['promoter_net']);
        $this->assertSame(32900, $summary['pending_payout']);
        $this->assertSame(14100, $summary['paid_out']);
    }

    public function test_event_ticket_counts_and_commission_earned(): void
    {
        $event = Event::find(1);

        $this->assertSame(7, $event->getTicketsSoldCount());
        // quantity_sold reste un COMPTEUR DE STOCK (réservation O2 incluse : 8) ; il n'est plus affiché côté promoteur
        // (écrans : 7 vendus = sold + used d'une commande payée, voir PromoteurScreensTest). Colonne inchangée.
        $this->assertSame(8, (int) $event->ticketTypes()->sum('quantity_sold'));
        // L4 : getProgressPercentage délègue à TicketStats::fillRate (7 / 170 = 4,1 ; avant : 4.12).
        $this->assertSame(4.1, (float) $event->getProgressPercentage());
        // L4 : getCommissionEarned délègue à CommissionStats::platformRevenue de E, commandes payées pending + paid,
        // toutes dates : 1 200 + 900 + 600 + 300 = 3 000 (avant : 900, statut `paid` seul).
        $this->assertSame(3000, (int) $event->getCommissionEarned());
    }

    /**
     * L4 : taux d'utilisation du scanner = scannés / (sold + used d'une commande payée). Toute la période, événement E :
     * 1 scanné / 7 vendus (O1 2 dont 1 used, O4 2, O5 1, O8 1, O9 1) = 14,3 % (avant : 1 / 6 = 16,7 %, `used` hors dénominateur).
     */
    public function test_usage_rate_now_counts_used_tickets_in_the_denominator(): void
    {
        $this->actingAs(User::find(self::P));

        $data = app(ScannerController::class)->stats(Request::create('/promoteur/scanner/stats', 'GET', ['period' => 'all']))->getData();

        $this->assertSame(7, $data['summary']['sold']);
        $this->assertSame(1, $data['summary']['scanned']);
        $this->assertSame(14.3, (float) $data['summary']['usage_rate']);
        $this->assertSame(14.3, (float) $data['eventStats']->first()['usage_rate']);
    }

    public function test_admin_tickets_index_now_shows_target_values(): void
    {
        $this->actingAs($this->makeUser(10, 'admin'));

        // Retourné vers la cible (mission admin-billets) : Ticket::orderTickets n'est plus utilisé, l'écran passe par
        // Ticket::linkedOrder() (order_item_id + pivot) et TicketStats. Vendus 6 (sold + used), scannés 1, taux 1 / 6 = 16,7 %
        // sur novembre 2026 (détail : tests/Feature/AdminTicketsTest.php).
        $data = app(\App\Http\Controllers\Admin\TicketController::class)->index(\Illuminate\Http\Request::create('/'))->getData();

        $this->assertSame(6, $data['summary']['sold']);
        $this->assertSame(1, $data['summary']['scanned']);
        $this->assertSame(16.7, $data['summary']['usage_rate']);
    }
}
