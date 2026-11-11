<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Ticket;
use App\Models\TicketType;
use App\Exceptions\CheckoutRefusedException;
use App\Services\CheckoutService;
use App\Services\QRCodeService;
use BaconQrCode\Common\ErrorCorrectionLevel;
use BaconQrCode\Encoder\Encoder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\BuildsOrderSchema;
use Tests\TestCase;

/**
 * Dernier cycle : QR local (S-6/S-7/S-10), total de billets du panier (I-a), échappement (S-9),
 * throttle verify-ticket, backoff de l'alerte SMTP.
 */
class CheckoutFinalFixesTest extends TestCase
{
    use BuildsOrderSchema;

    protected function setUp(): void
    {
        parent::setUp();
        $this->buildOrderSchema();
        Mail::fake();
        config(['payments.max_pending_orders' => 1000, 'payments.max_pending_orders_per_ip' => 1000]);
    }

    private function realQr(): QRCodeService
    {
        $qr = new QRCodeService();
        $this->app->instance(QRCodeService::class, $qr);

        return $qr;
    }

    private function makeTicket(string $code = 'TKT-ABCD1234'): Ticket
    {
        $type = $this->ticketType(10);
        $id = DB::table('tickets')->insertGetId(['ticket_type_id' => $type->id, 'ticket_code' => $code, 'status' => 'reserved']);

        return Ticket::find($id);
    }

    /** Billet ÉMIS : vendu, rattaché (pivot) à une commande payée. */
    private function makeIssuedTicket(string $code = 'TKT-ABCD1234'): Ticket
    {
        $ticket = $this->makeTicket($code);
        $ticket->update(['status' => 'sold']);
        $orderId = DB::table('orders')->insertGetId(['order_number' => 'ORD-' . $code, 'total_amount' => 1000, 'payment_status' => 'paid']);
        DB::table('order_tickets')->insert(['order_id' => $orderId, 'ticket_id' => $ticket->id]);

        return $ticket->fresh();
    }

    // ===== S-6 / S-7 : QR 100 % local =====

    public function test_the_real_service_generates_a_valid_local_png_that_encodes_the_verification_url(): void
    {
        $ticket = $this->makeTicket();
        $uri = $this->realQr()->generateTicketQRBase64($ticket, 200);

        $this->assertStringStartsWith('data:image/png;base64,', $uri);
        $png = base64_decode(substr($uri, strlen('data:image/png;base64,')), true);
        $this->assertNotFalse($png);
        $this->assertSame("\x89PNG\r\n\x1a\n", substr($png, 0, 8));

        // Relit le PNG pixel par pixel et le compare à la matrice QR attendue pour l'URL de vérification :
        // le contenu est donc bien celui du billet (équivalent d'un décodage).
        $matrix = Encoder::encode(url('/verify-ticket/TKT-ABCD1234'), ErrorCorrectionLevel::M(), 'UTF-8')->getMatrix();
        $img = imagecreatefromstring($png);
        $modules = $matrix->getWidth();
        $total = $modules + 8; // zone calme de 4 modules de chaque côté (ISO/IEC 18004)
        $scale = intdiv(imagesx($img), $total);
        $this->assertGreaterThanOrEqual(2, $scale);
        $this->assertSame($total * $scale, imagesx($img));
        for ($y = 0; $y < $modules; $y++) {
            for ($x = 0; $x < $modules; $x++) {
                $rgb = imagecolorat($img, ($x + 4) * $scale + intdiv($scale, 2), ($y + 4) * $scale + intdiv($scale, 2));
                $isDark = (($rgb >> 16) & 0xFF) < 128;
                $this->assertSame($matrix->get($x, $y) === 1, $isDark, "module {$x},{$y}");
            }
        }
        $this->assertSame(0xFFFFFF, imagecolorat($img, 0, 0) & 0xFFFFFF, 'zone calme blanche');
        // R-1 : toute la marge de 4 modules est blanche (aucun pixel sombre avant le premier module).
        $margin = 4 * $scale;
        for ($i = 0; $i < imagesx($img); $i++) {
            for ($j = 0; $j < $margin; $j++) {
                $this->assertSame(0xFFFFFF, imagecolorat($img, $i, $j) & 0xFFFFFF, "marge haute {$i},{$j}");
                $this->assertSame(0xFFFFFF, imagecolorat($img, $j, $i) & 0xFFFFFF, "marge gauche {$j},{$i}");
            }
        }
    }

    public function test_qr_size_is_clamped_to_safe_bounds(): void
    {
        $qr = $this->realQr();
        $dim = fn (int $size) => getimagesizefromstring($qr->renderPng('https://x.test/verify-ticket/TKT-ABCD1234', $size))[0];

        $this->assertLessThanOrEqual(QRCodeService::MAX_SIZE, $dim(10_000_000));
        $this->assertGreaterThan(QRCodeService::MAX_SIZE - 60, $dim(10_000_000));
        $this->assertSame($dim(QRCodeService::MAX_SIZE), $dim(PHP_INT_MAX));
        $this->assertSame($dim(QRCodeService::MIN_SIZE), $dim(-5));
        $this->assertSame($dim(QRCodeService::MIN_SIZE), $dim(0));
        $this->assertSame($dim(QRCodeService::MIN_SIZE), $dim(1));
        $this->assertGreaterThan($dim(1), $dim(400));
        $this->assertGreaterThan($dim(400), $dim(10_000_000));
    }

    public function test_qr_generation_sends_no_network_request_of_any_kind(): void
    {
        Http::fake();
        $spy = new class {
            public static array $calls = [];
            public $context;
            public function stream_open($path) { self::$calls[] = $path; return false; }
            public function url_stat($path, $flags) { self::$calls[] = $path; return false; }
        };
        $class = get_class($spy);
        foreach (['http', 'https'] as $scheme) {
            stream_wrapper_unregister($scheme);
            stream_wrapper_register($scheme, $class);
        }
        try {
            $qr = $this->realQr();
            $ticket = $this->makeIssuedTicket();
            Storage::fake();
            $qr->generateTicketQRBase64($ticket);
            $qr->getOrGenerateTicketQR($ticket, 'url');
            $qr->generateStyledQR($ticket, ['size' => 150, 'color' => 'ff0000']);
            $qr->generateTestQR('x');
            $qr->testAllMethods();
            $qr->validateQRGeneration();
            $ticket->getReliableQrCode();
            $ticket->getQrCodeBase64();
            $ticket->generateQrCode();
            $this->assertStringStartsWith('data:image/png;base64,', (string) $ticket->qrDataUri());
        } finally {
            foreach (['http', 'https'] as $scheme) {
                stream_wrapper_restore($scheme);
            }
        }

        Http::assertNothingSent();
        $this->assertSame([], $class::$calls, 'aucun flux http(s) (file_get_contents, fopen...) ouvert');
    }

    public function test_the_qr_service_source_contains_no_third_party_call(): void
    {
        $src = file_get_contents(app_path('Services/QRCodeService.php'));
        foreach (['chart.googleapis', 'qrserver', 'file_get_contents', 'Http::', 'curl_', 'fopen('] as $needle) {
            $this->assertStringNotContainsString($needle, $src, $needle);
        }
    }

    public function test_the_whole_guest_checkout_works_with_the_real_local_service_without_network(): void
    {
        Http::fake();
        Storage::fake();
        $this->realQr();
        $type = $this->ticketType(100);

        $start = microtime(true);
        $this->withSession(['cart' => $this->tamperedCart($type, 3)])
            ->post(route('checkout.guest.process'), [
                'email' => 'real@example.test', 'first_name' => 'Awa', 'last_name' => 'Kone',
                'phone' => '0102030405', 'terms_accepted' => '1', 'payment_method' => 'cash',
            ])->assertRedirect();
        $this->assertLessThan(3.0, microtime(true) - $start, 'QR local : rapide');

        // Décision : pas de QR avant paiement (commande en attente) ; QR émis à la confirmation, sans réseau ni fichier.
        $this->assertSame(0, DB::table('tickets')->whereNotNull('qr_code')->count(), 'aucun QR tant que la commande est en attente');
        app(\App\Services\OrderPaymentService::class)->confirmPayment(\App\Models\Order::firstOrFail(), 'REF-X');
        $this->assertSame(3, DB::table('tickets')->where('qr_code', 'like', 'data:image/png;base64,%')->count(), 'un QR (data-URI) par billet payé');
        $this->assertSame([], Storage::allFiles(), 'aucun fichier écrit');
        Http::assertNothingSent();
    }

    // ===== S-10 : plus de ticket_code dans les logs =====

    public function test_logs_never_contain_the_ticket_code_nor_the_raw_exception_message(): void
    {
        $tmp = tempnam(sys_get_temp_dir(), 'qrlog');
        Log::swap(Log::build(['driver' => 'single', 'path' => $tmp, 'level' => 'debug']));
        Storage::fake();

        $ticket = $this->makeIssuedTicket('TKT-SECRET99');
        $this->realQr();
        $ticket->generateQrCode();
        $ticket->update(['qr_code' => null]); // force la régénération avec le service qui échoue ci-dessous
        $ticket->getReliableQrCode();
        $ticket->getQrCodeBase64();

        $this->app->instance(QRCodeService::class, new class extends QRCodeService {
            public function generateTicketQRBase64(Ticket $ticket, $size = 200)
            {
                throw new \RuntimeException('echec https://x/verify-ticket/' . $ticket->ticket_code);
            }

            public function getOrGenerateTicketQR(Ticket $ticket, $format = 'base64')
            {
                throw new \RuntimeException('echec https://x/verify-ticket/' . $ticket->ticket_code);
            }
        });
        $ticket->generateQrCode();
        $ticket->getReliableQrCode();
        $ticket->getQrCodeBase64();
        $ticket->qr_code_url;
        $ticket->markAsUsed(1);

        $logs = (string) file_get_contents($tmp);
        @unlink($tmp);
        $this->assertNotSame('', $logs, 'des logs sont bien produits');
        $this->assertStringNotContainsString('TKT-SECRET99', $logs);
        $this->assertStringNotContainsString('verify-ticket', $logs);
    }

    // ===== Throttle verify-ticket =====

    public function test_verify_ticket_routes_share_a_per_ip_throttle(): void
    {
        for ($i = 0; $i < 30; $i++) {
            $this->get('/verify-ticket/TKT-NOPE0001')->assertStatus(200);
            $this->getJson('/api/verify-ticket/TKT-NOPE0001')->assertStatus(404);
        }
        $this->get('/verify-ticket/TKT-NOPE0001')->assertStatus(429);
        $this->getJson('/api/verify-ticket/TKT-NOPE0001')->assertStatus(429);
    }

    // ===== I-a : total de billets du panier =====

    private function secondType(): TicketType
    {
        return TicketType::create([
            'name' => 'VIP', 'price' => 5000, 'quantity_available' => 500, 'quantity_sold' => 0,
            'sale_start_date' => now()->subDay(), 'sale_end_date' => now()->addDay(), 'max_per_order' => 10, 'is_active' => true,
        ]);
    }

    private function twoLineCart(TicketType $a, TicketType $b, int $qa, int $qb): array
    {
        return [
            'a' => ['ticket_type_id' => $a->id, 'event_id' => 1, 'quantity' => $qa, 'ticket_name' => 'Std', 'unit_price' => 1, 'total_price' => 1],
            'b' => ['ticket_type_id' => $b->id, 'event_id' => 1, 'quantity' => $qb, 'ticket_name' => 'VIP', 'unit_price' => 1, 'total_price' => 1],
        ];
    }

    public function test_cart_refuses_a_total_above_the_per_order_cap_across_types_in_one_request(): void
    {
        $a = $this->ticketType(500);
        $b = $this->secondType();

        $this->postJson(route('cart.add'), ['event_id' => 1, 'tickets' => [
            ['ticket_type_id' => $a->id, 'quantity' => 6], ['ticket_type_id' => $b->id, 'quantity' => 6],
        ]])->assertStatus(400)->assertJsonPath('message', CheckoutService::totalLimitMessage(10));

        $this->assertStringContainsString('Maximum 10 billets au total dans votre panier', CheckoutService::totalLimitMessage(10));
        $this->assertSame([], session('cart', []), 'rien n\'est enregistré');
    }

    public function test_cart_refuses_a_total_above_the_cap_across_successive_adds_and_update(): void
    {
        $a = $this->ticketType(500);
        $b = $this->secondType();

        $this->postJson(route('cart.add'), ['event_id' => 1, 'tickets' => [['ticket_type_id' => $a->id, 'quantity' => 6]]])->assertOk();
        $this->postJson(route('cart.add'), ['event_id' => 1, 'tickets' => [['ticket_type_id' => $b->id, 'quantity' => 6]]])
            ->assertStatus(400)->assertJsonPath('message', CheckoutService::totalLimitMessage(10));
        // ancien format (un seul billet)
        $this->postJson(route('cart.add'), ['ticket_type_id' => $b->id, 'quantity' => 5])
            ->assertStatus(400)->assertJsonPath('message', CheckoutService::totalLimitMessage(10));
        $this->postJson(route('cart.add'), ['ticket_type_id' => $b->id, 'quantity' => 4])->assertOk();
        // update : 6 + 4, passer le premier à 7 dépasserait 10
        $this->patchJson(route('cart.update'), ['cart_key' => 'ticket_' . $a->id, 'quantity' => 7])
            ->assertStatus(400)->assertJsonPath('message', CheckoutService::totalLimitMessage(10));
        $this->patchJson(route('cart.update'), ['cart_key' => 'ticket_' . $a->id, 'quantity' => 6])->assertOk();
    }

    public function test_checkout_refuses_6_plus_6_with_the_exact_message(): void
    {
        $a = $this->ticketType(500);
        $b = $this->secondType();

        $this->withSession(['cart' => $this->twoLineCart($a, $b, 6, 6)])
            ->post(route('checkout.guest.process'), [
                'email' => 'twelve@example.test', 'first_name' => 'Awa', 'last_name' => 'Kone',
                'phone' => '0102030405', 'terms_accepted' => '1', 'payment_method' => 'cash',
            ])->assertSessionHas('error', CheckoutService::perOrderLimitMessage(10));
        $this->assertSame(0, DB::table('orders')->count());
    }

    public function test_assert_within_order_limits_refuses_a_total_above_the_cap_with_the_exact_message(): void
    {
        // Échoue si le contrôle `$newTickets > $maxTickets` est retiré : le repli (quota en attente) lèverait
        // le message générique LIMIT_ERROR au lieu du plafond exact.
        try {
            app(CheckoutService::class)->assertWithinOrderLimits('t@example.test', null, '10.0.0.1', 1, false, 11);
            $this->fail('11 billets doivent être refusés');
        } catch (CheckoutRefusedException $e) {
            $this->assertSame(CheckoutService::perOrderLimitMessage(10), $e->getMessage());
        }
        app(CheckoutService::class)->assertWithinOrderLimits('t@example.test', null, '10.0.0.1', 1, false, 10);
        $this->addToAssertionCount(1);
    }

    public function test_booking_forms_display_the_effective_maximum_not_the_raw_type_value(): void
    {
        $type = $this->ticketType(500);
        $type->update(['max_per_order' => 20, 'name' => 'Std']);
        $event = Event::with('ticketTypes')->find(1);

        foreach (['desktop', 'mobile'] as $form) {
            $html = view("events.partials.booking-form-{$form}", ['event' => $event])->render();
            $this->assertStringContainsString('data-max="10"', $html, $form);
            $this->assertStringNotContainsString('data-max="20"', $html, $form);
        }
        $desktop = view('events.partials.booking-form-desktop', ['event' => $event])->render();
        $this->assertStringContainsString('Max 10/commande', $desktop);
        $this->assertStringContainsString('Maximum 10 billets au total dans votre panier', $desktop);
    }

    // ===== S-9 : échappement du nom de type de billet =====

    public function test_a_malicious_ticket_type_name_is_never_rendered_unescaped(): void
    {
        $payload = '<img src=x onerror=alert(1)>';
        $type = $this->ticketType(500);
        $type->update(['name' => $payload, 'max_per_order' => 2]);
        $event = Event::with('ticketTypes')->find(1);

        foreach (['desktop', 'mobile'] as $form) {
            $html = view("events.partials.booking-form-{$form}", ['event' => $event])->render();
            $this->assertStringNotContainsString($payload, $html, $form);
            $this->assertStringContainsString('&lt;img src=x onerror=alert(1)&gt;', $html, $form);
        }

        // Le message de refus du panier (JSON) porte le nom comme DONNÉE : le JS l'insère en texte (voir ci-dessous).
        $res = $this->postJson(route('cart.add'), ['event_id' => 1, 'tickets' => [['ticket_type_id' => $type->id, 'quantity' => 3]]])
            ->assertStatus(400);
        $this->assertSame('application/json', $res->headers->get('Content-Type'));
        $this->assertStringContainsString($payload, $res->json('message'));
    }

    public function test_the_front_scripts_never_inject_server_messages_or_ticket_names_as_html(): void
    {
        // Le DOM JS n'est pas testable ici (pas de navigateur) : garde de non-régression sur le source.
        $show = file_get_contents(resource_path('views/events/show.blade.php'));
        $this->assertStringNotContainsString('${message}', $show);
        $this->assertStringContainsString('createTextNode(String(message', $show);

        $mobile = file_get_contents(resource_path('views/events/partials/booking-form-mobile.blade.php'));
        $this->assertStringNotContainsString('summaryDetails.innerHTML = summaryItems', $mobile);
        $this->assertStringContainsString('summaryDetails.textContent = summaryItems', $mobile);
        $this->assertStringContainsString('div.textContent = String(message', $mobile);

        $cart = file_get_contents(resource_path('views/cart/show.blade.php'));
        $this->assertStringNotContainsString('${message}', $cart);
        $this->assertStringContainsString('escaper.textContent = String(message', $cart);
    }

    // ===== Backoff de l'alerte SMTP =====

    public function test_a_smtp_failure_backs_off_instead_of_retrying_on_every_refusal(): void
    {
        $this->makeUser(9, 'admin');
        $type = $this->ticketType(20);
        $this->reservedOrder($type, 10, 'pending');

        $attempts = 0;
        Mail::shouldReceive('raw')->andReturnUsing(function () use (&$attempts) {
            $attempts++;
            throw new \RuntimeException('smtp down');
        });

        for ($i = 0; $i < 4; $i++) {
            $this->withSession(['cart' => $this->tamperedCart($type, 1)])
                ->post(route('checkout.guest.process'), [
                    'email' => "smtp{$i}@example.test", 'first_name' => 'Awa', 'last_name' => 'Kone',
                    'phone' => '0102030405', 'terms_accepted' => '1', 'payment_method' => 'cash',
                ])->assertSessionHas('error', CheckoutService::EVENT_LIMIT_ERROR);
        }

        $this->assertSame(1, $attempts, 'une seule tentative d\'envoi pendant le backoff de 5 min');
    }
}
