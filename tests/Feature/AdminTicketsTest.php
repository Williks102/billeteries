<?php

namespace Tests\Feature;

use App\Helpers\CurrencyHelper;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Concerns\StatsFixture;
use Tests\TestCase;

/**
 * Onglet « Billets » de l'admin sur le jeu canonique (StatsFixture), « maintenant » = 2026-11-15.
 * Période par défaut (novembre 2026) : 9 billets listés (O1 x2, O2, O3, O4 x2, O5, O6, O7) ;
 * vendus 6 (sold + used de commandes payées : O1 x2, O4 x2, O5, O7), scannés 1, taux 1/6 = 16,7 %, réservés 1.
 * Billets anciens = rattachés par le pivot order_tickets seul (O1) ; récents = order_item_id (O4).
 */
class AdminTicketsTest extends TestCase
{
    use StatsFixture;

    protected function setUp(): void
    {
        parent::setUp();
        $this->buildStatsFixture();
        $this->makeUser(10, 'admin');
        $this->makeUser(11, 'acheteur');
        // le schéma partagé n'a pas used_at (la migration réelle l'ajoute) : on l'ajoute ici
        \Illuminate\Support\Facades\Schema::table('tickets', fn ($t) => $t->timestamp('used_at')->nullable());
        DB::table('tickets')->where('ticket_code', 'TKT-O1-1')->update(['used_at' => '2026-11-14 18:30:00']);
    }

    protected function tearDown(): void
    {
        \Carbon\Carbon::setTestNow();
        parent::tearDown();
    }

    private function admin(): static
    {
        return $this->actingAs(User::find(10));
    }

    private function card(string $html, string $slug): string
    {
        $this->assertSame(1, preg_match('/data-stat="'.preg_quote($slug, '/').'".*?kpi-card__value">(.*?)<\/p>/s', $html, $m), "Carte {$slug} introuvable");

        return trim(preg_replace('/\s+/u', ' ', str_replace(CurrencyHelper::NBSP, ' ', html_entity_decode(strip_tags($m[1])))));
    }

    private function id(string $code): int
    {
        return (int) DB::table('tickets')->where('ticket_code', $code)->value('id');
    }

    // ---------- liste ----------

    public function test_index_lists_sold_and_used_tickets_with_order_and_masked_buyer(): void
    {
        $res = $this->admin()->get(route('admin.tickets.index'))->assertOk();
        $res->assertSee('TKT-O1-0')->assertSee('TKT-O1-1')->assertSee('TKT-O4-0')
            ->assertSee('Vendu')->assertSee('Utilisé')->assertSee('Réservé')->assertSee('Annulé')
            ->assertSee('ORD-STATS-O1')->assertSee('ORD-STATS-O4')
            ->assertSee('14/11/2026 18:30')           // date de scan
            ->assertSee('ac***@example.test')         // e-mail masqué en liste
            ->assertDontSee('acheteur@example.test')  // jamais en clair en liste
            ->assertDontSee('TKT-O8-0')               // octobre : hors période
            ->assertDontSee('data:image');            // aucun QR dans la liste
    }

    public function test_summary_cards_show_target_values(): void
    {
        $html = $this->admin()->get(route('admin.tickets.index'))->assertOk()->getContent();

        $this->assertSame('6', $this->card($html, 'billets-vendus'));
        $this->assertSame('1', $this->card($html, 'billets-scannes'));
        $this->assertSame('16,7 %', $this->card($html, 'taux-dutilisation'));
        $this->assertSame('1', $this->card($html, 'billets-reserves'));
    }

    public function test_status_filter_keeps_only_the_requested_status(): void
    {
        $this->admin()->get(route('admin.tickets.index', ['status' => 'used']))->assertOk()
            ->assertSee('TKT-O1-1')->assertDontSee('TKT-O1-0')->assertDontSee('TKT-O4-0');
        $this->admin()->get(route('admin.tickets.index', ['status' => 'cancelled']))->assertOk()
            ->assertSee('TKT-O3-0')->assertSee('TKT-O6-0')->assertDontSee('TKT-O1-1');
    }

    public function test_invalid_status_is_a_validation_error_not_a_500(): void
    {
        $this->admin()->get(route('admin.tickets.index', ['status' => 'nope']))
            ->assertRedirect(route('admin.tickets.index'))->assertSessionHasErrors('status');
    }

    public function test_event_search_and_period_filters(): void
    {
        $this->admin()->get(route('admin.tickets.index', ['event' => 2]))->assertOk()
            ->assertSee('TKT-O7-0')->assertDontSee('TKT-O4-0');
        $this->admin()->get(route('admin.tickets.index', ['search' => 'O4']))->assertOk()
            ->assertSee('TKT-O4-0')->assertSee('TKT-O4-1')->assertDontSee('TKT-O1-0');
        $this->admin()->get(route('admin.tickets.index', ['period' => 'last_month']))->assertOk()
            ->assertSee('TKT-O8-0')->assertDontSee('TKT-O1-0');
        $this->admin()->get(route('admin.tickets.index', ['period' => 'all']))->assertOk()
            ->assertSee('TKT-O9-0')->assertSee('TKT-O8-0')->assertSee('TKT-O1-0');
    }

    public function test_pagination_keeps_the_query_string(): void
    {
        $html = $this->admin()->get(route('admin.tickets.index', ['period' => 'all', 'status' => 'sold']))->assertOk()->getContent();
        $this->assertStringNotContainsString('page=2', $html); // moins de 20 résultats : pas de pagination
        for ($i = 0; $i < 25; $i++) {
            DB::table('tickets')->insert(['ticket_type_id' => 1, 'ticket_code' => 'TKT-PAG-'.$i, 'status' => 'available', 'created_at' => '2026-11-01', 'updated_at' => '2026-11-01']);
        }
        $html = $this->admin()->get(route('admin.tickets.index', ['period' => 'all', 'status' => 'available']))->assertOk()->getContent();
        $this->assertStringContainsString('status=available', $html);
        $this->assertMatchesRegularExpression('/page=2[^"]*"/', $html);
        $this->assertStringContainsString('period=all', substr($html, strpos($html, 'page=2') - 300, 600));
    }

    // ---------- fiche ----------

    public function test_old_pivot_ticket_and_recent_item_ticket_both_find_their_order_and_buyer(): void
    {
        $this->admin()->get(route('admin.tickets.show', $this->id('TKT-O1-0')))->assertOk()   // pivot seul
            ->assertSee('ORD-STATS-O1')->assertSee('acheteur@example.test');
        $this->admin()->get(route('admin.tickets.show', $this->id('TKT-O4-0')))->assertOk()   // order_item_id
            ->assertSee('ORD-STATS-O4')->assertSee('acheteur@example.test');
        $this->admin()->get(route('admin.tickets.show', $this->id('TKT-O7-0')))->assertOk()
            ->assertSee('ORD-STATS-O7')->assertSee('b2@example.test');
    }

    public function test_qr_is_shown_only_for_an_issued_ticket(): void
    {
        // émis : vendu + commande payée
        $this->admin()->get(route('admin.tickets.show', $this->id('TKT-O1-0')))->assertOk()->assertSee('data:image/png;base64,', false);
        // réservé (commande en attente) : pas de QR
        $this->admin()->get(route('admin.tickets.show', $this->id('TKT-O2-0')))->assertOk()->assertDontSee('data:image', false)->assertSee('Billet non émis');
        // vendu mais commande non payée : pas de QR non plus
        DB::table('tickets')->where('ticket_code', 'TKT-O2-0')->update(['status' => 'sold']);
        $this->admin()->get(route('admin.tickets.show', $this->id('TKT-O2-0')))->assertOk()->assertDontSee('data:image', false);
        // annulé (commande payée) : pas de QR
        DB::table('tickets')->where('ticket_code', 'TKT-O1-0')->update(['status' => 'cancelled']);
        $this->admin()->get(route('admin.tickets.show', $this->id('TKT-O1-0')))->assertOk()->assertDontSee('data:image', false);
    }

    // ---------- édition ----------

    public function test_edit_renders_and_update_marks_a_paid_ticket_used(): void
    {
        $id = $this->id('TKT-O4-0');
        $this->admin()->get(route('admin.tickets.edit', $id))->assertOk()->assertSee('name="status"', false)->assertSee('action-bar', false);

        $this->admin()->put(route('admin.tickets.update', $id), ['status' => 'used'])->assertRedirect(route('admin.tickets.show', $id));
        $ticket = Ticket::find($id);
        $this->assertSame('used', $ticket->status);
        $this->assertNotNull($ticket->used_at);
    }

    public function test_ticket_of_an_unpaid_order_cannot_be_used(): void
    {
        DB::table('tickets')->where('ticket_code', 'TKT-O2-0')->update(['status' => 'sold']);
        $id = $this->id('TKT-O2-0');

        $this->admin()->from(route('admin.tickets.edit', $id))->put(route('admin.tickets.update', $id), ['status' => 'used'])->assertSessionHasErrors('status');
        $this->admin()->patchJson(route('admin.tickets.mark-used', $id))->assertStatus(400);
        $this->assertSame('sold', Ticket::find($id)->status);
        $this->admin()->get(route('admin.tickets.show', $id))->assertOk()->assertDontSee('Marquer comme utilisé');
    }

    public function test_used_ticket_is_frozen_and_cancelled_can_be_reactivated(): void
    {
        $used = $this->id('TKT-O1-1');
        $this->admin()->get(route('admin.tickets.edit', $used))->assertOk()->assertSee('ne peut plus être modifié');
        $this->admin()->put(route('admin.tickets.update', $used), ['status' => 'cancelled'])->assertSessionHasErrors('status');
        $this->assertSame('used', Ticket::find($used)->status);

        $this->admin()->patch(route('admin.tickets.cancel', $used))->assertSessionHas('error');
        $cancelled = $this->id('TKT-O3-0');
        $this->admin()->patch(route('admin.tickets.reactivate', $cancelled))->assertSessionHas('success');
        $this->assertSame('sold', Ticket::find($cancelled)->status);
    }

    public function test_bulk_regenerate_qr_skips_non_issued_tickets(): void
    {
        $paid = $this->id('TKT-O1-0');
        $reserved = $this->id('TKT-O2-0');
        $this->admin()->post(route('admin.tickets.bulk-action'), ['action' => 'regenerate_qr', 'tickets' => [$paid, $reserved]])->assertSessionHas('success', '1 ticket(s) traité(s) avec succès !');
        $this->assertNull(Ticket::find($reserved)->qr_code);
    }

    // ---------- accès ----------

    #[DataProvider('forbiddenRoutes')]
    public function test_non_admin_gets_403(string $role, string $route): void
    {
        $user = $this->makeUser(20, $role);
        $id = $this->id('TKT-O1-0');
        $this->actingAs($user)->get(route($route, in_array($route, ['admin.tickets.index', 'admin.tickets.export'], true) ? [] : $id))->assertForbidden();
    }

    /** @return array<string, array{0:string,1:string}> */
    public static function forbiddenRoutes(): array
    {
        $out = [];
        foreach (['acheteur', 'promoteur'] as $role) {
            foreach (['admin.tickets.index', 'admin.tickets.show', 'admin.tickets.edit', 'admin.tickets.export'] as $r) {
                $out["$role $r"] = [$role, $r];
            }
        }

        return $out;
    }

    // ---------- performances ----------

    private function queryCount(string $url): int
    {
        DB::flushQueryLog();
        DB::enableQueryLog();
        $this->admin()->get($url)->assertOk();
        $n = count(DB::getQueryLog());
        DB::disableQueryLog();

        return $n;
    }

    public function test_query_budget_is_constant_with_more_tickets(): void
    {
        $url = route('admin.tickets.index', ['period' => 'all']);
        $this->queryCount($url); // chauffe (cache de config, session)
        $before = $this->queryCount($url);

        // 12 billets de plus : anciens (pivot) et récents (order_item_id), commandes et acheteurs distincts
        foreach (range(1, 12) as $i) {
            $user = $this->makeUser(100 + $i, 'acheteur');
            $orderId = DB::table('orders')->insertGetId([
                'user_id' => $user->id, 'event_id' => 1, 'total_amount' => 1000, 'payment_status' => 'paid', 'order_number' => 'ORD-X-'.$i,
                'paid_at' => '2026-11-04 10:00:00', 'created_at' => '2026-11-04 09:00:00', 'updated_at' => '2026-11-04 10:00:00',
            ]);
            $itemId = DB::table('order_items')->insertGetId(['order_id' => $orderId, 'ticket_type_id' => 1, 'quantity' => 1, 'unit_price' => 1000, 'total_price' => 1000]);
            $ticketId = DB::table('tickets')->insertGetId([
                'ticket_type_id' => 1, 'order_item_id' => $i % 2 ? $itemId : null, 'ticket_code' => 'TKT-X-'.$i, 'status' => 'sold',
                'created_at' => '2026-11-04 09:00:00', 'updated_at' => '2026-11-04 09:00:00',
            ]);
            if ($i % 2 === 0) {
                DB::table('order_tickets')->insert(['order_id' => $orderId, 'ticket_id' => $ticketId, 'order_item_id' => $itemId]);
            }
        }

        $this->assertSame($before, $this->queryCount($url), 'Le nombre de requêtes de la liste dépend du nombre de billets (N+1)');
    }

    // ---------- garde-fou : aucune requête / calcul dans les vues ----------

    #[DataProvider('views')]
    public function test_ticket_views_contain_no_query_or_aggregate(string $view): void
    {
        $source = file_get_contents(dirname(__DIR__, 2).'/resources/views/admin/tickets/'.$view.'.blade.php');
        foreach (['->sum(', '->count(', 'selectRaw', 'DB::', '::where', '->avg(', 'whereMonth', 'round(', 'number_format($'] as $needle) {
            $this->assertStringNotContainsString($needle, $source, "tickets/{$view} : « {$needle} » interdit dans une vue");
        }
    }

    /** @return array<string, array{0:string}> */
    public static function views(): array
    {
        return ['index' => ['index'], 'show' => ['show'], 'edit' => ['edit']];
    }

    public function test_index_view_never_generates_a_qr(): void
    {
        $source = file_get_contents(dirname(__DIR__, 2).'/resources/views/admin/tickets/index.blade.php');
        foreach (['qrDataUri', 'qr_code', 'getQrCode', 'generateQr', 'data:image'] as $needle) {
            $this->assertStringNotContainsString($needle, $source);
        }
    }
}
