<?php

namespace Tests\Concerns;

use App\Models\Order;
use App\Models\Ticket;
use App\Models\TicketType;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Schéma SQLite minimal (les migrations du projet utilisent du SQL MySQL) et fabriques de test
 * pour les tests de stock et de paiement manuel.
 */
trait BuildsOrderSchema
{
    protected function buildOrderSchema(): void
    {
        config(['app.key' => 'base64:' . base64_encode(str_repeat('k', 32))]);

        Schema::create('users', function ($t) {
            $t->id();
            $t->string('name')->nullable();
            $t->string('email')->nullable()->unique(); // comme la migration users (unique)
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
        Schema::create('events', function ($t) {
            $t->id();
            $t->unsignedBigInteger('promoter_id')->default(1);
            $t->unsignedBigInteger('category_id')->nullable();
            $t->string('title')->default('Concert');
            $t->string('venue')->nullable();
            $t->date('event_date')->nullable();
            $t->string('event_time')->nullable();
            $t->string('status')->default('published');
            $t->timestamps();
        });
        Schema::create('event_categories', function ($t) {
            $t->id();
            $t->string('name')->nullable();
            $t->string('slug')->nullable();
            $t->string('icon')->nullable();
            $t->string('color')->nullable();
            $t->integer('sort_order')->default(0);
            $t->boolean('is_active')->default(true);
            $t->timestamps();
        });
        Schema::create('pages', function ($t) {
            $t->id();
            $t->string('title')->nullable();
            $t->string('slug')->nullable();
            $t->boolean('is_active')->default(true);
            $t->integer('menu_order')->default(0);
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
        Schema::create('commissions', function ($t) {
            $t->id();
            $t->unsignedBigInteger('order_id');
            $t->unsignedBigInteger('promoter_id')->nullable();
            $t->integer('gross_amount')->default(0);
            $t->decimal('commission_rate', 5, 2)->default(0);
            $t->integer('commission_amount')->default(0);
            $t->integer('net_amount')->default(0);
            $t->integer('platform_fee')->default(0);
            $t->integer('payment_processor_fee')->nullable();
            $t->string('status')->default('pending');
            $t->timestamp('paid_at')->nullable();
            $t->text('notes')->nullable();
            $t->timestamps();
        });
        Schema::create('ticket_types', function ($t) {
            $t->id();
            $t->unsignedBigInteger('event_id')->default(1);
            $t->string('name')->default('Std');
            $t->text('description')->nullable();
            $t->integer('price')->default(1000);
            $t->integer('quantity_available')->default(0);
            $t->integer('quantity_sold')->default(0);
            $t->dateTime('sale_start_date')->nullable();
            $t->dateTime('sale_end_date')->nullable();
            $t->integer('max_per_order')->default(10);
            $t->boolean('is_active')->default(true);
            $t->timestamps();
        });
        Schema::create('orders', function ($t) {
            $t->id();
            $t->unsignedBigInteger('user_id')->default(1);
            $t->unsignedBigInteger('event_id')->default(1);
            $t->integer('total_amount');
            $t->string('payment_status')->default('pending');
            $t->string('payment_method')->nullable();
            $t->string('payment_reference')->nullable();
            $t->string('order_number')->unique();
            $t->string('billing_email')->default('a@b.c');
            $t->string('billing_phone')->nullable();
            $t->string('guest_token')->nullable();
            $t->timestamp('paid_at')->nullable();
            $t->timestamp('expires_at')->nullable();
            $t->text('admin_notes')->nullable();
            $t->timestamps();
        });
        Schema::create('order_items', function ($t) {
            $t->id();
            $t->unsignedBigInteger('order_id');
            $t->unsignedBigInteger('ticket_type_id');
            $t->integer('quantity');
            $t->integer('unit_price')->default(0);
            $t->integer('total_price')->default(0);
            $t->timestamps();
        });
        Schema::create('tickets', function ($t) {
            $t->id();
            $t->unsignedBigInteger('ticket_type_id');
            $t->string('ticket_code')->nullable();
            $t->string('status')->default('reserved');
            $t->text('qr_code')->nullable();
            $t->string('qr_code_path')->nullable();
            $t->timestamps();
        });
        Schema::create('order_tickets', function ($t) {
            $t->id();
            $t->unsignedBigInteger('order_id');
            $t->unsignedBigInteger('ticket_id');
            $t->unsignedBigInteger('order_item_id')->nullable();
            $t->timestamps();
        });
        Schema::create('payments', function ($t) {
            $t->id();
            $t->unsignedBigInteger('order_id');
            $t->string('payment_method');
            $t->integer('amount');
            $t->string('status')->default('pending');
            $t->string('provider_transaction_id')->nullable();
            $t->json('provider_response')->nullable();
            $t->timestamp('completed_at')->nullable();
            $t->timestamps();
        });

        DB::table('users')->insert(['id' => 1, 'name' => 'Acheteur', 'email' => 'acheteur@example.test', 'role' => 'acheteur']);
        DB::table('events')->insert(['id' => 1, 'promoter_id' => 2, 'title' => 'Concert', 'venue' => 'Palais', 'event_date' => '2027-01-01', 'event_time' => '20:00']);
    }

    protected function ticketType(int $available, int $sold = 0): TicketType
    {
        return TicketType::create([
            'name' => 'Std', 'price' => 1000,
            'quantity_available' => $available, 'quantity_sold' => $sold,
            'sale_start_date' => now()->subDay(), 'sale_end_date' => now()->addDay(),
            'max_per_order' => 10, 'is_active' => true,
        ]);
    }

    /** Panier de session avec prix FALSIFIÉS (1 FCFA) : le serveur doit les ignorer. */
    protected function tamperedCart(TicketType $type, int $qty, int $eventId = 1): array
    {
        return ['k' => [
            'ticket_type_id' => $type->id, 'event_id' => $eventId, 'quantity' => $qty,
            'ticket_name' => 'Std', 'unit_price' => 1, 'total_price' => 1,
        ]];
    }

    /** Utilisateur créé avec un rôle (id explicite). */
    protected function makeUser(int $id, string $role): \App\Models\User
    {
        DB::table('users')->insert([
            'id' => $id, 'name' => ucfirst($role), 'email' => $role . $id . '@example.test', 'role' => $role,
        ]);

        return \App\Models\User::find($id);
    }

    /** Commande de $qty billets réservés (stock déjà décompté), sans commission. */
    protected function reservedOrder(TicketType $type, int $qty, string $status = 'pending', $expiresAt = null): Order
    {
        $this->assertTrue($type->reserveTickets($qty));

        $order = Order::create([
            'total_amount' => 1000 * $qty, 'payment_status' => $status,
            'payment_method' => 'bank_transfer',
            'order_number' => 'ORD-' . uniqid(), 'expires_at' => $expiresAt,
        ]);
        $item = $order->orderItems()->create(['ticket_type_id' => $type->id, 'quantity' => $qty]);
        for ($i = 0; $i < $qty; $i++) {
            $ticket = Ticket::create([
                'ticket_type_id' => $type->id, 'ticket_code' => uniqid(),
                'status' => $status === 'failed' ? 'cancelled' : 'reserved',
            ]);
            DB::table('order_tickets')->insert([
                'order_id' => $order->id, 'ticket_id' => $ticket->id, 'order_item_id' => $item->id,
            ]);
        }

        return $order;
    }
}
