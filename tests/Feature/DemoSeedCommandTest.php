<?php

namespace Tests\Feature;

use App\Models\CommissionSetting;
use App\Models\EventCategory;
use App\Models\User;
use Illuminate\Support\Env;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/** demo:seed (appelée par docker/entrypoint.sh) : idempotente, lit l'environnement, refuse un mot de passe faible. */
class DemoSeedCommandTest extends TestCase
{
    private const STRONG = 'Correct-Horse-Battery-9';

    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('users', function ($t) {
            $t->id();
            $t->string('name')->nullable();
            $t->string('email')->nullable()->unique();
            $t->string('phone')->nullable();
            $t->string('password')->nullable();
            $t->string('role')->default('acheteur');
            $t->string('customer_code')->nullable();
            $t->boolean('is_guest')->default(false);
            $t->timestamp('guest_converted_at')->nullable();
            $t->timestamp('email_verified_at')->nullable();
            $t->rememberToken();
            $t->timestamps();
        });
        Schema::create('event_categories', function ($t) {
            $t->id();
            $t->string('name')->nullable();
            $t->string('slug')->nullable();
            $t->text('description')->nullable();
            $t->string('icon')->nullable();
            $t->string('color')->nullable();
            $t->integer('sort_order')->default(0);
            $t->boolean('is_active')->default(true);
            $t->timestamps();
        });
        Schema::create('commission_settings', function ($t) {
            $t->id();
            $t->unsignedBigInteger('category_id')->nullable();
            $t->unsignedBigInteger('promoter_id')->nullable();
            $t->decimal('commission_rate', 5, 2)->default(10);
            $t->integer('platform_fee_fixed')->nullable();
            $t->integer('min_commission')->nullable();
            $t->boolean('is_active')->default(true);
            $t->date('valid_from')->nullable();
            $t->date('valid_until')->nullable();
            $t->timestamps();
        });

        $this->setEnv('ADMIN_EMAIL', 'admin@demo.test');
        $this->setEnv('ADMIN_PASSWORD', self::STRONG);
    }

    protected function tearDown(): void
    {
        $this->setEnv('ADMIN_EMAIL', null);
        $this->setEnv('ADMIN_PASSWORD', null);
        parent::tearDown();
    }

    private function setEnv(string $key, ?string $value): void
    {
        $repository = Env::getRepository();
        $repository->clear($key);
        if ($value !== null) {
            $repository->set($key, $value);
        }
    }

    public function test_seeds_categories_commissions_and_admin_on_an_empty_database(): void
    {
        $this->artisan('demo:seed')->assertExitCode(0);

        $admin = User::where('email', 'admin@demo.test')->firstOrFail();
        $this->assertSame('admin', $admin->role);
        $this->assertNotNull($admin->email_verified_at);
        $this->assertTrue(Hash::check(self::STRONG, $admin->password));
        $this->assertGreaterThan(0, EventCategory::count());
        $this->assertGreaterThan(0, CommissionSetting::count());
        $this->assertSame(1, User::count());
    }

    public function test_is_idempotent_when_users_exist(): void
    {
        $this->artisan('demo:seed')->assertExitCode(0);
        $categories = EventCategory::count();
        $commissions = CommissionSetting::count();

        $this->artisan('demo:seed')->assertExitCode(0);

        $this->assertSame(1, User::count());
        $this->assertSame($categories, EventCategory::count());
        $this->assertSame($commissions, CommissionSetting::count());
    }

    public function test_does_nothing_when_a_user_already_exists_even_with_a_weak_password(): void
    {
        User::create(['name' => 'Existant', 'email' => 'existant@demo.test', 'password' => 'x', 'role' => 'acheteur']);
        $this->setEnv('ADMIN_PASSWORD', 'court');

        $this->artisan('demo:seed')->assertExitCode(0);

        $this->assertSame(1, User::count());
        $this->assertSame(0, EventCategory::count());
    }

    public function test_refuses_a_password_shorter_than_12_characters(): void
    {
        $this->setEnv('ADMIN_PASSWORD', '12345678901'); // 11 caractères

        $this->artisan('demo:seed')->assertExitCode(1);

        $this->assertSame(0, User::count());
        $this->assertSame(0, EventCategory::count());
        $this->assertSame(0, CommissionSetting::count());
    }

    public function test_accepts_exactly_12_characters(): void
    {
        $this->setEnv('ADMIN_PASSWORD', '123456789012');

        $this->artisan('demo:seed')->assertExitCode(0);

        $this->assertSame(1, User::count());
    }

    public function test_refuses_a_missing_password(): void
    {
        $this->setEnv('ADMIN_PASSWORD', null);

        $this->artisan('demo:seed')->assertExitCode(1);

        $this->assertSame(0, User::count());
    }

    public function test_refuses_a_missing_or_invalid_email(): void
    {
        $this->setEnv('ADMIN_EMAIL', 'pas-un-email');

        $this->artisan('demo:seed')->assertExitCode(1);

        $this->assertSame(0, User::count());
    }

    public function test_never_prints_the_password(): void
    {
        $this->artisan('demo:seed')->doesntExpectOutputToContain(self::STRONG)->assertExitCode(0);

        User::query()->delete();
        $this->setEnv('ADMIN_PASSWORD', 'court');
        $this->artisan('demo:seed')->doesntExpectOutputToContain('court')->assertExitCode(1);
    }
}
