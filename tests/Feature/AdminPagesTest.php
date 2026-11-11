<?php

namespace Tests\Feature;

use App\Models\EventCategory;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Concerns\BuildsOrderSchema;
use Tests\TestCase;

/**
 * Pages admin (lot 2 « pages manquantes ») rendues via leur vraie route.
 */
class AdminPagesTest extends TestCase
{
    use BuildsOrderSchema;

    private User $admin;

    private EventCategory $category;

    protected function setUp(): void
    {
        parent::setUp();
        $this->buildOrderSchema();

        $this->admin = $this->makeUser(10, 'admin');
        $this->makeUser(2, 'promoteur');

        $this->category = EventCategory::create(['name' => 'Musique', 'slug' => 'musique']);
        DB::table('events')->where('id', 1)->update(['category_id' => $this->category->id]);

        $order = $this->reservedOrder($this->ticketType(10), 2, 'paid');
        DB::table('commissions')->insert([
            'order_id' => $order->id, 'promoter_id' => 2, 'gross_amount' => 2000,
            'commission_rate' => 6, 'commission_amount' => 120, 'net_amount' => 1880,
            'status' => 'pending', 'created_at' => now(), 'updated_at' => now(),
        ]);
        $this->reservedOrder($this->ticketType(10), 1, 'pending');
    }

    private function pendingOrderId(): int
    {
        return (int) DB::table('orders')->where('payment_status', 'pending')->value('id');
    }

    private function assertCleanPage($response): void
    {
        $response->assertOk();
        $this->assertDoesNotMatchRegularExpression(
            '/\b(admin|common|messages|validation)\.[a-z_]+\.[a-z_]+\b/',
            strip_tags($response->getContent())
        );
    }

    /** @return array<string, array{0: string, 1: array<string, mixed>}> */
    public static function pages(): array
    {
        return [
            'categories.index' => ['admin.categories.index', []],
            'categories.create' => ['admin.categories.create', []],
            'emails.dashboard' => ['admin.emails.dashboard', []],
            'emails.templates' => ['admin.emails.templates', []],
            'intervention' => ['admin.intervention-dashboard', []],
            'reports.index' => ['admin.reports.index', []],
            'reports.sales' => ['admin.reports.sales', []],
            'reports.financial' => ['admin.reports.financial', []],
            'reports.events' => ['admin.reports.events', ['period' => 'this_year']],
            'revenues' => ['admin.revenues', []],
            'settings' => ['admin.settings.index', []],
        ];
    }

    #[DataProvider('pages')]
    public function test_admin_page_renders_and_non_admin_is_refused(string $route, array $params): void
    {
        $this->assertCleanPage($this->actingAs($this->admin)->get(route($route, $params)));

        $this->actingAs(User::find(1))->get(route($route, $params))->assertForbidden();
    }

    public function test_category_show_and_edit_render_with_form_method_and_csrf(): void
    {
        $show = $this->actingAs($this->admin)->get(route('admin.categories.show', $this->category));
        $this->assertCleanPage($show);
        $show->assertSee('Concert');

        $edit = $this->actingAs($this->admin)->get(route('admin.categories.edit', $this->category));
        $this->assertCleanPage($edit);
        $edit->assertSee('name="_method" value="PUT"', false)->assertSee('name="_token"', false)->assertSee('Musique');

        $this->actingAs(User::find(1))->get(route('admin.categories.edit', $this->category))->assertForbidden();
    }

    public function test_category_form_shows_validation_errors(): void
    {
        $this->actingAs($this->admin)->from(route('admin.categories.create'))
            ->post(route('admin.categories.store'), ['name' => ''])
            ->assertSessionHasErrors('name');

        $this->actingAs($this->admin)->withSession(['errors' => (new \Illuminate\Support\ViewErrorBag)->put('default', new \Illuminate\Support\MessageBag(['name' => ['Le nom est obligatoire.']]))])
            ->get(route('admin.categories.create'))
            ->assertSee('Le nom est obligatoire.')
            ->assertSee('is-invalid', false);
    }

    public function test_order_edit_never_offers_paid_but_links_to_manual_confirmation(): void
    {
        $id = $this->pendingOrderId();
        $res = $this->actingAs($this->admin)->get(route('admin.orders.edit', $id));
        $this->assertCleanPage($res);

        $html = $res->getContent();
        $this->assertStringNotContainsString('value="paid"', $html);
        $this->assertStringContainsString('name="_method" value="PUT"', $html);
        $this->assertStringContainsString(route('admin.orders.confirm-payment', $id), $html);

        $this->actingAs(User::find(1))->get(route('admin.orders.edit', $id))->assertForbidden();
    }

    public function test_order_edit_of_paid_order_redirects_to_show(): void
    {
        $paid = (int) DB::table('orders')->where('payment_status', 'paid')->value('id');

        $this->actingAs($this->admin)->get(route('admin.orders.edit', $paid))
            ->assertRedirect(route('admin.orders.show', $paid));
    }

    public function test_settings_uses_config_rate_and_exposes_no_secret(): void
    {
        config([
            'payments.default_commission_rate' => 7,
            'app.key' => 'base64:'.base64_encode('SECRETKEYVALUE'.str_repeat('0', 18)),
            'mail.mailers.smtp.password' => 'mot-de-passe-secret',
            'database.connections.sqlite.password' => 'db-secret-pass',
        ]);
        DB::statement('CREATE TABLE IF NOT EXISTS settings (id integer primary key, key varchar, value varchar)');

        $res = $this->actingAs($this->admin)->get(route('admin.settings.index'));
        $this->assertCleanPage($res);
        $res->assertSee('7 %')->assertDontSee('SECRETKEYVALUE')->assertDontSee(base64_encode('SECRETKEYVALUE'))->assertDontSee('mot-de-passe-secret')->assertDontSee('db-secret-pass');
    }

    public function test_financial_report_shows_commission_and_sales_of_paid_orders_only(): void
    {
        $res = $this->actingAs($this->admin)->get(route('admin.reports.financial'));
        $this->assertCleanPage($res);
        $res->assertSee('2 000 FCFA')->assertSee('120 FCFA')->assertSee('1 880 FCFA');
    }
}
