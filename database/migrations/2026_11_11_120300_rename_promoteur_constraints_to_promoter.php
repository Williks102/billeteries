<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/*
 * Migration corrective idempotente : plus AUCUN nom « promoteur » ne doit subsister après la chaîne.
 *
 * Les renommages de colonnes (promoteur_id -> promoter_id) laissent les clés étrangères d'origine sous leur ancien
 * nom (events_promoteur_id_foreign, commissions_promoteur_id_foreign, commission_settings_promoteur_id_foreign) et,
 * sur MySQL/TiDB, l'index implicite du même nom. Elle SE DÉCLENCHE sur une base vierge (ces noms existent encore).
 *
 * Le DDL MySQL/TiDB n'est pas transactionnel : l'ordre est donc conçu pour qu'une panne à n'importe quelle étape
 * laisse TOUJOURS la table avec une clé étrangère valide (jamais sans clé), par table :
 *   (a) un index sur promoter_id existe (la FK en exige un) ; sinon on en crée un `<table>_promoter_id_index` ;
 *   (b) ajout de la NOUVELLE clé `<table>_promoter_id_foreign` (mêmes références, ON DELETE, ON UPDATE) ;
 *   (c) SEULEMENT si (b) a réussi : suppression de l'ANCIENNE clé ;
 *   (d) SEULEMENT si (c) a réussi : suppression de l'ancien index nommé « promoteur ».
 * Chaque étape a son propre try/catch : un échec est journalisé (WARNING, table et nom de contrainte, sans donnée
 * personnelle) et n'est JAMAIS relancé : un renommage cosmétique ne doit jamais empêcher le démarrage. Si (b) échoue,
 * tout reste intact. Rejouable : rien n'est fait quand aucun nom « promoteur » ne subsiste (ni sur SQLite, dont les
 * clés n'ont pas de nom).
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (['events', 'commissions', 'commission_settings'] as $table) {
            try {
                if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'promoter_id')) {
                    continue;
                }
                $foreignKeys = Schema::getForeignKeys($table);
            } catch (\Throwable $e) {
                $this->warn($table, '-', 'inspection impossible');

                continue;
            }

            foreach ($foreignKeys as $fk) {
                $old = (string) ($fk['name'] ?? '');
                if (($fk['columns'] ?? null) !== ['promoter_id'] || ! str_contains($old, 'promoteur')) {
                    continue;
                }

                $this->renameForeignKey($table, $old, $fk);
            }
        }
    }

    /** @param array<string, mixed> $fk */
    private function renameForeignKey(string $table, string $old, array $fk): void
    {
        $new = $table.'_promoter_id_foreign';

        // (a) index requis par la nouvelle clé : un index dont la première colonne est promoter_id, autre que l'index
        // implicite de l'ancienne clé (qui sera supprimé en (d)).
        try {
            $hasOtherIndex = collect(Schema::getIndexes($table))->contains(
                fn ($i) => ($i['name'] ?? null) !== $old && ($i['columns'][0] ?? null) === 'promoter_id'
            );
            if (! $hasOtherIndex) {
                Schema::table($table, fn (Blueprint $t) => $t->index('promoter_id', $table.'_promoter_id_index'));
            }
        } catch (\Throwable $e) {
            $this->warn($table, $old, 'index sur promoter_id');

            return; // sans index sûr, ne rien faire (erreur 1553 sinon)
        }

        // (b) nouvelle clé : si elle échoue, tout reste intact.
        try {
            if (! $this->hasForeignKey($table, $new)) {
                Schema::table($table, fn (Blueprint $t) => $t->foreign('promoter_id', $new)
                    ->references($fk['foreign_columns'][0] ?? 'id')
                    ->on($fk['foreign_table'])
                    ->onDelete($fk['on_delete'] ?: 'no action')
                    ->onUpdate($fk['on_update'] ?: 'no action'));
            }
        } catch (\Throwable $e) {
            $this->warn($table, $new, 'ajout de la nouvelle clé');

            return;
        }

        // (c) seulement si (b) a réussi : ancienne clé.
        try {
            Schema::table($table, fn (Blueprint $t) => $t->dropForeign($old));
        } catch (\Throwable $e) {
            $this->warn($table, $old, 'suppression de l\'ancienne clé');

            return;
        }

        // (d) seulement si (c) a réussi : ancien index implicite.
        try {
            if (Schema::hasIndex($table, $old)) {
                Schema::table($table, fn (Blueprint $t) => $t->dropIndex($old));
            }
        } catch (\Throwable $e) {
            $this->warn($table, $old, 'suppression de l\'ancien index');
        }
    }

    private function hasForeignKey(string $table, string $name): bool
    {
        return collect(Schema::getForeignKeys($table))->contains(fn ($fk) => ($fk['name'] ?? null) === $name);
    }

    private function warn(string $table, string $constraint, string $step): void
    {
        try {
            Log::warning("Migration 120300 (renommage cosmétique) : étape « {$step} » ignorée.", ['table' => $table, 'constraint' => $constraint]);
        } catch (\Throwable $e) {
            // la journalisation ne doit jamais faire échouer la migration
        }
    }

    public function down(): void
    {
        // Volontairement vide : renommage cosmétique de contraintes, rien à restaurer.
    }
};
