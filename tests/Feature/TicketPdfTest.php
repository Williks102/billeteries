<?php

namespace Tests\Feature;

use App\Mail\PaymentConfirmation;
use App\Models\Order;
use App\Models\Ticket;
use App\Models\User;
use App\Services\TicketPdfService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Tests\Concerns\BuildsOrderSchema;
use Tests\TestCase;

/**
 * PDF des billets (gabarit unique, une page A4 par billet) : contenu, vie privée, DomPDF et autorisation
 * de la route d'un seul billet (tickets.download).
 */
class TicketPdfTest extends TestCase
{
    use BuildsOrderSchema;

    private const PNG = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==';

    protected function setUp(): void
    {
        parent::setUp();
        $this->buildOrderSchema();
        Mail::fake();
        View::share('demoMode', false);
        Schema::table('events', function ($t) {
            $t->string('address')->nullable();
            $t->string('end_time')->nullable();
        });
        DB::table('events')->where('id', 1)->update([
            'address' => 'Treichville, Abidjan', 'event_date' => '2026-11-15', 'event_time' => '2026-11-15 12:00:00',
            'end_time' => '2026-11-15 15:00:00',
        ]);
        DB::table('users')->where('id', 1)->update(['name' => 'Aïcha Koné', 'phone' => '0700112233', 'email' => 'aicha.prive@example.test']);
        DB::table('users')->insert(['id' => 2, 'name' => 'Organisateur Lagune', 'email' => 'orga@example.test', 'role' => 'promoteur']);
    }

    /** Commande de $qty billets, payée ou non (billets sold + QR en base quand payée). */
    private function order(bool $paid = true, int $qty = 3, int $price = 1000): Order
    {
        $type = $this->ticketType(10);
        $type->update(['price' => $price]);
        $order = $this->reservedOrder($type, $qty, 'pending', now()->addHours(5));
        $order->update(['user_id' => 1, 'payment_status' => $paid ? 'paid' : 'pending', 'paid_at' => $paid ? now() : null]);
        $item = $order->orderItems()->first();
        foreach (Ticket::all() as $ticket) {
            $ticket->update(['order_item_id' => $item->id, 'ticket_code' => 'TKT-ABC' . $ticket->id]
                + ($paid ? ['status' => 'sold', 'qr_code' => self::PNG] : []));
        }

        return Order::find($order->id);
    }

    private function user(int $id, string $role): User
    {
        return User::find($id) ?? $this->makeUser($id, $role);
    }

    private function html(Order $order, ?Ticket $only = null): string
    {
        return view('acheteur.tickets-pdf', ['sheets' => app(TicketPdfService::class)->sheets($order, $only)])->render();
    }

    // ===== Contenu du gabarit =====

    public function test_template_shows_reference_long_date_organizer_and_numbering(): void
    {
        $html = $this->html($this->order());

        $this->assertStringContainsString('TKT-ABC1', $html);
        $this->assertStringContainsString('Dimanche 15 novembre 2026', $html);
        $this->assertStringContainsString('12h00 – 15h00', $html);
        $this->assertStringContainsString('Organisé par Organisateur Lagune', $html);
        $this->assertStringContainsString('Billet 1 sur 3', $html);
        $this->assertStringContainsString('Billet 3 sur 3', $html);
        $this->assertStringContainsString(\App\Helpers\CurrencyHelper::formatPrice(1000), $html);
        $this->assertStringContainsString('Aïcha Koné', $html);
        $this->assertStringContainsString('Billet nominatif et incessible. Pièce d&#039;identité requise à l&#039;entrée.', str_replace("'", '&#039;', $html));
    }

    public function test_template_never_prints_buyer_email_or_phone(): void
    {
        $html = $this->html($this->order());

        $this->assertStringNotContainsString('aicha.prive@example.test', $html);
        $this->assertStringNotContainsString('0700112233', $html);
    }

    public function test_free_ticket_says_gratuit_and_no_service_fee_wording(): void
    {
        $html = $this->html($this->order(true, 1, 0));

        $this->assertStringContainsString('Gratuit', $html);
        $this->assertStringNotContainsString('0 FCFA', $html);
        $this->assertStringNotContainsStringIgnoringCase('frais de service', $html);
    }

    public function test_template_has_no_layout_features_dompdf_cannot_render(): void
    {
        foreach (['resources/views/acheteur/tickets-pdf.blade.php', 'resources/views/components/ticket-sheet.blade.php'] as $file) {
            $source = file_get_contents(base_path($file));
            $this->assertDoesNotMatchRegularExpression('/display\s*:\s*(flex|grid|inline-flex)|\bgap\s*:|box-shadow|<script|position\s*:\s*absolute/i', $source, $file);
        }
    }

    public function test_titles_are_escaped(): void
    {
        DB::table('events')->where('id', 1)->update(['title' => '<script>alert(1)</script>']);

        $this->assertStringNotContainsString('<script>alert', $this->html($this->order()));
    }

    public function test_unissued_ticket_has_no_qr_and_says_so(): void
    {
        $order = $this->order(true, 1);
        Ticket::query()->update(['status' => 'reserved']);

        $html = $this->html(Order::find($order->id));

        $this->assertStringContainsString('QR indisponible', $html);
        $this->assertStringNotContainsString('data:image/png', $html);
        // Le code du billet (secret) n'est pas imprimé tant que le billet n'est pas émis
        $this->assertStringNotContainsString('TKT-ABC', $html);
        $this->assertStringNotContainsString('Référence du billet', $html);
    }

    // ===== Génération réelle DomPDF =====

    public function test_order_pdf_is_generated(): void
    {
        $order = $this->order();
        $bytes = app(TicketPdfService::class)->forOrder($order)->output();

        $this->assertStringStartsWith('%PDF', $bytes);
        $this->assertLessThan(2_000_000, strlen($bytes));
        $this->assertSame(3, preg_match_all('/\/Type\s*\/Page\b/', $bytes));
    }

    public function test_single_ticket_pdf_is_generated_with_order_numbering(): void
    {
        $order = $this->order();
        $ticket = $order->tickets->sortBy('id')->values()[1];
        $service = app(TicketPdfService::class);

        $this->assertStringStartsWith('%PDF', $service->forTicket($order, $ticket)->output());
        $this->assertStringContainsString('Billet 2 sur 3', $this->html($order, $ticket));
        $this->assertStringNotContainsString('Billet 1 sur 3', $this->html($order, $ticket));
    }

    public function test_payment_confirmation_still_attaches_the_pdf(): void
    {
        $attachments = (new PaymentConfirmation($this->order()))->attachments();

        $this->assertCount(1, $attachments);
    }

    public function test_payment_confirmation_writes_no_file_to_disk_and_skips_unpaid_orders(): void
    {
        $dir = storage_path('app/temp');
        $before = is_dir($dir) ? glob($dir.'/*') : [];

        $this->assertCount(1, (new PaymentConfirmation($this->order()))->attachments());
        $this->assertSame($before, is_dir($dir) ? glob($dir.'/*') : [], 'Aucun PDF (avec des QR) ne doit rester sur le disque.');

        $this->assertSame([], (new PaymentConfirmation($this->order(false)))->attachments(), 'Jamais de billets joints avant paiement.');
    }

    // ===== Route d'un seul billet =====

    public function test_download_route_is_get_behind_auth(): void
    {
        $route = app('router')->getRoutes()->getByName('tickets.download');

        $this->assertSame(['GET', 'HEAD'], $route->methods());
        $this->assertContains('auth', $route->gatherMiddleware());
    }

    private function firstTicket(Order $order): Ticket
    {
        return $order->tickets->sortBy('id')->first();
    }

    public function test_owner_downloads_a_single_ticket_pdf(): void
    {
        $order = $this->order(true, 2);

        $response = $this->actingAs($this->user(1, 'acheteur'))->get(route('tickets.download', $this->firstTicket($order)));

        $response->assertOk();
        $this->assertStringStartsWith('%PDF', $response->getContent());
        $this->assertStringContainsString('attachment; filename=billet-tkt-abc', strtolower($response->headers->get('content-disposition')));
    }

    public function test_admin_can_download(): void
    {
        $order = $this->order(true, 1);

        $this->actingAs($this->user(9, 'admin'))->get(route('tickets.download', $this->firstTicket($order)))->assertOk();
    }

    public function test_another_buyer_is_refused(): void
    {
        $order = $this->order(true, 1);

        $this->actingAs($this->user(5, 'acheteur'))->get(route('tickets.download', $this->firstTicket($order)))->assertForbidden();
    }

    public function test_the_events_promoter_is_refused(): void
    {
        $order = $this->order(true, 1);

        $this->actingAs($this->user(2, 'promoteur'))->get(route('tickets.download', $this->firstTicket($order)))->assertForbidden();
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $order = $this->order(true, 1);

        $this->get(route('tickets.download', $this->firstTicket($order)))->assertRedirect();
    }

    public function test_unpaid_order_gives_no_pdf_even_to_its_owner(): void
    {
        $order = $this->order(false, 1);

        $response = $this->actingAs($this->user(1, 'acheteur'))->get(route('tickets.download', $this->firstTicket($order)));

        $response->assertRedirect()->assertSessionHas('error');
        $this->assertStringNotContainsString('%PDF', (string) $response->getContent());
    }

    public function test_cancelled_ticket_of_a_paid_order_gives_no_pdf(): void
    {
        $order = $this->order(true, 1);
        $ticket = $this->firstTicket($order);
        $ticket->update(['status' => 'cancelled']);

        $this->actingAs($this->user(1, 'acheteur'))->get(route('tickets.download', $ticket))->assertRedirect();
    }

    public function test_ticket_detail_page_links_to_single_ticket_pdf_and_all_tickets(): void
    {
        $order = $this->order(true, 1);

        $this->actingAs($this->user(1, 'acheteur'))->get(route('acheteur.tickets.show', $this->firstTicket($order)))
            ->assertOk()
            ->assertSee(route('tickets.download', $this->firstTicket($order)), false)
            ->assertSee(route('acheteur.orders.download', $order), false);
    }
}
