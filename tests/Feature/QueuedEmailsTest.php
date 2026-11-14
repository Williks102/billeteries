<?php

namespace Tests\Feature;

use App\Mail\AdminNewOrder;
use App\Mail\Concerns\TransactionalMail;
use App\Mail\ManualPaymentInstructions;
use App\Mail\OrderConfirmation;
use App\Mail\PaymentConfirmation;
use App\Mail\PromoteurNewSale;
use App\Models\Order;
use App\Models\User;
use App\Services\CheckoutService;
use App\Services\EmailService;
use App\Services\OrderPaymentService;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Symfony\Component\Mime\RawMessage;
use Tests\Concerns\BuildsOrderSchema;
use Tests\TestCase;

/**
 * E-mails transactionnels en file d'attente : la requête de l'acheteur n'attend ni le SMTP ni le PDF, et un échec
 * d'e-mail ne change jamais une commande. PHPUnit reste en `sync` ; les tests de fond passent en `database`.
 */
class QueuedEmailsTest extends TestCase
{
    use BuildsOrderSchema;

    /** Nombre d'appels au transport d'e-mail en panne. */
    public static int $transportCalls = 0;

    protected function setUp(): void
    {
        parent::setUp();
        self::$transportCalls = 0;
        $this->buildOrderSchema();
        $this->makeUser(2, 'promoteur');
        $this->makeUser(10, 'admin');
        View::share('demoMode', false);
        // Les migrations du projet ne tournent pas sous SQLite de test : tables de file minimales.
        Schema::create('jobs', function ($t) {
            $t->id();
            $t->string('queue')->index();
            $t->longText('payload');
            $t->unsignedTinyInteger('attempts');
            $t->unsignedInteger('reserved_at')->nullable();
            $t->unsignedInteger('available_at');
            $t->unsignedInteger('created_at');
        });
        Schema::create('failed_jobs', function ($t) {
            $t->id();
            $t->string('uuid')->unique();
            $t->text('connection');
            $t->text('queue');
            $t->longText('payload');
            $t->longText('exception');
            $t->timestamp('failed_at')->useCurrent();
        });
    }

    private function useDatabaseQueueAndFailingSmtp(): void
    {
        config(['queue.default' => 'database', 'mail.default' => 'failing', 'mail.mailers.failing' => ['transport' => 'failing']]);
        Mail::extend('failing', fn () => new class implements TransportInterface {
            public function send(RawMessage $message, ?Envelope $envelope = null): ?SentMessage
            {
                QueuedEmailsTest::$transportCalls++;
                throw new \RuntimeException('Connection could not be established with host smtp://compte:TOPSECRET@smtp.test:587 for acheteur@example.test');
            }

            public function __toString(): string
            {
                return 'failing://';
            }
        });
    }

    private function pendingOrder(): Order
    {
        $order = $this->reservedOrder($this->ticketType(10), 1, 'pending', now()->addHours(5));
        $order->update(['user_id' => 1, 'event_id' => 1]);

        return Order::find($order->id);
    }

    private function paidOrder(): Order
    {
        $order = $this->pendingOrder();
        $order->update(['payment_status' => 'paid', 'paid_at' => now()]);

        return Order::find($order->id);
    }

    // (a) file d'attente et non envoi direct ------------------------------------------------------------------

    public function test_payment_confirmation_is_queued_not_sent(): void
    {
        Mail::fake();
        $order = $this->pendingOrder();

        app(OrderPaymentService::class)->confirmPayment($order, 'VIR-1', $this->makeUser(11, 'admin'));

        Mail::assertQueued(PaymentConfirmation::class, fn ($m) => $m->order->is($order));
        Mail::assertNotSent(PaymentConfirmation::class);
    }

    public function test_all_order_emails_are_queued(): void
    {
        Mail::fake();
        $order = $this->paidOrder();

        app(EmailService::class)->sendAllOrderEmails($order);

        Mail::assertQueued(OrderConfirmation::class, 1);
        Mail::assertQueued(PaymentConfirmation::class, 1);
        Mail::assertQueued(PromoteurNewSale::class, 1);
        Mail::assertQueued(AdminNewOrder::class, 1);
        Mail::assertNothingSent();
    }

    public function test_manual_instructions_are_queued_at_order_creation(): void
    {
        Mail::fake();
        $order = $this->pendingOrder();

        app(CheckoutService::class)->sendManualInstructions([$order]);

        Mail::assertQueued(ManualPaymentInstructions::class, 1);
        Mail::assertNothingSent();
    }

    public function test_mailables_are_reliable_queue_jobs_without_recipient_in_the_payload(): void
    {
        foreach ([OrderConfirmation::class, PaymentConfirmation::class, ManualPaymentInstructions::class, PromoteurNewSale::class, AdminNewOrder::class, \App\Mail\WelcomeEmail::class] as $class) {
            $this->assertTrue(is_subclass_of($class, TransactionalMail::class), $class);
            $this->assertInstanceOf(\Illuminate\Contracts\Queue\ShouldQueueAfterCommit::class, (new \ReflectionClass($class))->newInstanceWithoutConstructor());
            $defaults = (new \ReflectionClass($class))->getDefaultProperties();
            $this->assertSame(3, $defaults['tries']);
            $this->assertSame([60, 300], $defaults['backoff']);
            $this->assertSame(120, $defaults['timeout']);
        }

        config(['queue.default' => 'database']);
        app(EmailService::class)->sendAllOrderEmails($this->paidOrder());
        $this->assertSame(4, DB::table('jobs')->count());
        foreach (DB::table('jobs')->pluck('payload') as $payload) {
            $this->assertStringNotContainsString('@example.test', $payload, 'aucune adresse e-mail dans la charge utile du job');
        }
    }

    // (b) panne SMTP : jamais d'effet sur la commande ------------------------------------------------------------

    public function test_smtp_failure_never_blocks_nor_changes_the_order_and_ends_in_failed_jobs(): void
    {
        $this->useDatabaseQueueAndFailingSmtp();
        $logs = [];
        Log::listen(function ($e) use (&$logs) {
            $logs[] = $e->message.' '.json_encode($e->context);
        });
        $order = $this->pendingOrder();

        $started = microtime(true);
        $result = app(OrderPaymentService::class)->confirmPayment($order, 'VIR-1', $this->makeUser(11, 'admin'));

        // Confirmation synchrone terminée, sans toucher au transport ; le job attend dans la file.
        $this->assertLessThan(5, microtime(true) - $started);
        $this->assertSame('paid', $result->payment_status);
        $this->assertSame(0, self::$transportCalls);
        $this->assertSame(1, DB::table('jobs')->count());
        $this->assertSame(0, DB::table('failed_jobs')->count());

        // Le worker échoue 3 fois (tentatives du Mailable) ; le délai de reprise est avancé artificiellement.
        for ($attempt = 1; $attempt <= 3; $attempt++) {
            Artisan::call('queue:work', ['--once' => true, '--stop-when-empty' => true]);
            DB::table('jobs')->update(['available_at' => time() - 1, 'reserved_at' => null]);
        }

        $this->assertSame(3, self::$transportCalls);
        $this->assertSame(0, DB::table('jobs')->count());
        $this->assertSame(1, DB::table('failed_jobs')->count());
        $this->assertSame('paid', $order->fresh()->payment_status, 'un e-mail en échec ne change jamais le statut');
        $this->assertSame(1, DB::table('tickets')->where('status', 'sold')->count());

        $failedLog = collect($logs)->first(fn ($l) => str_contains($l, 'Échec définitif'));
        $this->assertNotNull($failedLog, 'failed() journalise');
        $this->assertStringContainsString('"order_id":'.$order->id, $failedLog);
        // Ni le journal (y compris l'exception rapportée par le worker) ni failed_jobs ne contiennent l'adresse ou l'identifiant.
        foreach ($logs as $line) {
            $this->assertStringNotContainsString('TOPSECRET', $line);
            $this->assertStringNotContainsString('acheteur@example.test', $line);
        }
        $failed = DB::table('failed_jobs')->first();
        $this->assertStringNotContainsString('TOPSECRET', $failed->exception);
        $this->assertStringNotContainsString('acheteur@example.test', $failed->exception);
        $this->assertStringNotContainsString('acheteur@example.test', $failed->payload);
    }

    // (c) renvoi admin ---------------------------------------------------------------------------------------

    public function test_admin_resend_answers_queued_json_and_enqueues_a_job(): void
    {
        config(['queue.default' => 'database']);
        $order = $this->paidOrder();

        $response = $this->actingAs(User::find(10))->postJson(route('admin.orders.resend-email', $order))
            ->assertOk()->assertJson(['success' => true]);

        $this->assertStringContainsString("file d'attente", $response->json('message'));
        $this->assertStringNotContainsStringIgnoringCase('succès', $response->json('message'));
        $this->assertSame(1, DB::table('jobs')->count());
    }

    public function test_admin_resend_reports_an_error_when_the_job_cannot_be_recorded(): void
    {
        config(['queue.default' => 'database']);
        Schema::drop('jobs');
        $order = $this->paidOrder();

        $this->actingAs(User::find(10))->postJson(route('admin.orders.resend-email', $order))
            ->assertStatus(500)->assertJson(['success' => false]);
    }

    // (d) afterCommit ------------------------------------------------------------------------------------------

    public function test_a_rolled_back_transaction_queues_nothing_and_a_committed_one_queues_once(): void
    {
        config(['queue.default' => 'database']);
        $order = $this->paidOrder();

        DB::beginTransaction();
        app(EmailService::class)->sendPaymentConfirmation($order);
        $this->assertSame(0, DB::table('jobs')->count(), 'rien avant la validation');
        DB::rollBack();
        $this->assertSame(0, DB::table('jobs')->count(), 'rollback : rien en file');

        DB::beginTransaction();
        app(EmailService::class)->sendPaymentConfirmation($order);
        DB::commit();
        $this->assertSame(1, DB::table('jobs')->count());
    }

    // (e) délai SMTP -------------------------------------------------------------------------------------------

    public function test_smtp_timeout_comes_from_mail_timeout_with_a_default_of_10_seconds(): void
    {
        $this->assertSame(10, (require config_path('mail.php'))['mailers']['smtp']['timeout']);

        putenv('MAIL_TIMEOUT=25');
        try {
            $this->assertSame(25, (require config_path('mail.php'))['mailers']['smtp']['timeout']);
        } finally {
            putenv('MAIL_TIMEOUT');
        }
    }

    // (f) compteurs de la page Emails -------------------------------------------------------------------------

    public function test_emails_page_shows_pending_and_failed_counters(): void
    {
        DB::table('jobs')->insert([
            ['queue' => 'default', 'payload' => '{}', 'attempts' => 0, 'available_at' => time(), 'created_at' => time()],
            ['queue' => 'default', 'payload' => '{}', 'attempts' => 0, 'available_at' => time(), 'created_at' => time()],
        ]);
        DB::table('failed_jobs')->insert(['uuid' => 'u1', 'connection' => 'database', 'queue' => 'default', 'payload' => '{}', 'exception' => 'x']);

        $html = $this->actingAs(User::find(10))->get(route('admin.emails.dashboard'))->assertOk()
            ->assertSee('ne bloque jamais une commande')->getContent();

        $this->assertMatchesRegularExpression('/id="queue-pending">\s*2\s*</', $html);
        $this->assertMatchesRegularExpression('/id="queue-failed">\s*1\s*</', $html);
    }

    public function test_emails_page_shows_dashes_when_queue_tables_are_missing(): void
    {
        Schema::drop('jobs');
        Schema::drop('failed_jobs');

        $html = $this->actingAs(User::find(10))->get(route('admin.emails.dashboard'))->assertOk()->getContent();

        $this->assertMatchesRegularExpression('/id="queue-pending">\s*—\s*</u', $html);
        $this->assertMatchesRegularExpression('/id="queue-failed">\s*—\s*</u', $html);
        $this->actingAs(User::find(2))->get(route('admin.emails.dashboard'))->assertForbidden();
    }

    // (g) purge planifiée --------------------------------------------------------------------------------------

    public function test_failed_jobs_are_pruned_daily_after_72_hours(): void
    {
        $commands = collect(app(Schedule::class)->events())->map(fn ($e) => $e->command);

        $this->assertTrue($commands->contains(fn ($c) => str_contains((string) $c, 'queue:prune-failed --hours=72')));
    }

    // Le diagnostic admin reste synchrone ------------------------------------------------------------------------

    public function test_admin_test_email_stays_synchronous(): void
    {
        Mail::shouldReceive('raw')->once();
        Mail::shouldReceive('queue')->never();

        $this->actingAs(User::find(10))->postJson(route('admin.emails.test'), ['email' => 'a@b.test'])->assertOk();
    }
}
