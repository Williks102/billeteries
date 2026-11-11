<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Migration additive et idempotente (audit des migrations, I4).
 *
 * La migration 2025_07_25_003819_add_missing_columns_to_tickets_table.php.back n'est JAMAIS exécutée
 * (le framework ne charge que les fichiers dont le nom se termine par .php). Sur une base neuve,
 * `tickets` n'avait donc ni used_at (écrit par Ticket::markAsUsed), ni qr_code_path (QRCodeService),
 * ni validation_data / holder_phone / order_item_id (Ticket::$fillable, relation orderItem()), ni les
 * colonnes de suivi du .back. On ajoute exactement ces colonnes, nullable, avec les types du .back.
 *
 * Chaque ajout est gardé par Schema::hasColumn / hasIndex : sans effet sur une base qui les possède
 * déjà (base existante créée à l'époque où ces colonnes existaient). Aucune suppression, aucun changement
 * de type. Les types FK (unsignedBigInteger) correspondent à order_items.id (id()).
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach ([
            'qr_code_path' => fn (Blueprint $t) => $t->string('qr_code_path')->nullable(),
            'validation_data' => fn (Blueprint $t) => $t->text('validation_data')->nullable(),
            'used_at' => fn (Blueprint $t) => $t->timestamp('used_at')->nullable(),
            'holder_name' => fn (Blueprint $t) => $t->string('holder_name')->nullable(),
            'holder_email' => fn (Blueprint $t) => $t->string('holder_email')->nullable(),
            'holder_phone' => fn (Blueprint $t) => $t->string('holder_phone')->nullable(),
            'created_ip' => fn (Blueprint $t) => $t->ipAddress('created_ip')->nullable(),
            'sent_at' => fn (Blueprint $t) => $t->timestamp('sent_at')->nullable(),
            'downloaded_at' => fn (Blueprint $t) => $t->timestamp('downloaded_at')->nullable(),
            'download_count' => fn (Blueprint $t) => $t->integer('download_count')->default(0),
        ] as $column => $definition) {
            if (! Schema::hasColumn('tickets', $column)) {
                Schema::table('tickets', function (Blueprint $table) use ($definition) {
                    $definition($table);
                });
            }
        }

        if (! Schema::hasColumn('tickets', 'order_item_id')) {
            Schema::table('tickets', function (Blueprint $table) {
                $table->foreignId('order_item_id')->nullable()->constrained('order_items')->nullOnDelete();
            });
        }

        foreach ([
            'tickets_used_at_index' => ['used_at'],
            'tickets_status_used_at_index' => ['status', 'used_at'],
            'tickets_holder_email_index' => ['holder_email'],
        ] as $name => $columns) {
            if (! Schema::hasIndex('tickets', $name)) {
                Schema::table('tickets', function (Blueprint $table) use ($columns, $name) {
                    $table->index($columns, $name);
                });
            }
        }
    }

    public function down(): void
    {
        // Volontairement vide : on ne supprime jamais de données (billets, traces de scan).
    }
};
