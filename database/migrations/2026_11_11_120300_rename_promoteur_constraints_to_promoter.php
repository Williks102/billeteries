<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Migration corrective idempotente : plus AUCUN nom « promoteur » ne doit subsister après la chaîne.
 *
 * Les renommages de colonnes (promoteur_id -> promoter_id) laissent les clés étrangères d'origine sous leur ancien
 * nom (events_promoteur_id_foreign, commissions_promoteur_id_foreign, commission_settings_promoteur_id_foreign) et,
 * sur MySQL/TiDB, l'index implicite du même nom. Pour chaque table : si une clé étrangère dont le NOM contient
 * « promoteur » couvre promoter_id, on la supprime, on supprime l'index implicite homonyme, puis on recrée la clé
 * sous le nom Laravel standard <table>_promoter_id_foreign avec les mêmes règles ON DELETE / ON UPDATE.
 * (Suppression de l'index AVANT la recréation : sinon MySQL refuse, la clé en dépendant encore.)
 * Sans effet (aucune requête de modification) si aucun nom ne contient « promoteur » : base déjà propre, SQLite
 * (clés sans nom), rejeu. Les index nommés *promoteur* sont aussi remplacés s'ils existent encore.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (['events', 'commissions', 'commission_settings'] as $table) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'promoter_id')) {
                continue;
            }

            try {
                $foreignKeys = Schema::getForeignKeys($table);
                $indexes = Schema::getIndexes($table);
            } catch (\Throwable $e) {
                continue; // inspection impossible : on ne touche à rien (renommage purement cosmétique)
            }

            foreach ($foreignKeys as $fk) {
                $name = (string) ($fk['name'] ?? '');
                if ($fk['columns'] !== ['promoter_id'] || ! str_contains($name, 'promoteur')) {
                    continue;
                }

                $newName = $table.'_promoter_id_foreign';
                $legacyIndex = collect($indexes)->contains(fn ($i) => ($i['name'] ?? null) === $name);

                Schema::table($table, function (Blueprint $t) use ($name, $legacyIndex, $newName, $fk) {
                    $t->dropForeign($name);
                    if ($legacyIndex) {
                        $t->dropIndex($name);
                    }
                    $t->foreign('promoter_id', $newName)
                        ->references($fk['foreign_columns'][0] ?? 'id')
                        ->on($fk['foreign_table'])
                        ->onDelete($fk['on_delete'] ?: 'no action')
                        ->onUpdate($fk['on_update'] ?: 'no action');
                });
            }

            // Index composite éventuellement resté sous l'ancien nom (jamais créé par la chaîne actuelle).
            foreach ($indexes as $index) {
                $name = (string) ($index['name'] ?? '');
                if (str_contains($name, 'promoteur') && ! $index['primary'] && ! $index['unique']
                    && Schema::hasIndex($table, $name) && ! in_array('promoteur_id', $index['columns'], true)) {
                    $newIndex = str_replace('promoteur', 'promoter', $name);
                    if (! Schema::hasIndex($table, $newIndex)) {
                        Schema::table($table, function (Blueprint $t) use ($name, $newIndex, $index) {
                            $t->dropIndex($name);
                            $t->index($index['columns'], $newIndex);
                        });
                    }
                }
            }
        }
    }

    public function down(): void
    {
        // Volontairement vide : renommage cosmétique de contraintes, rien à restaurer.
    }
};
