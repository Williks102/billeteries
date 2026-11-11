<?php

namespace Tests\Feature;

use Closure;
use Illuminate\Database\MySqlConnection;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Grammars\MySqlGrammar;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Migration 120300 (renommage des clés étrangères « promoteur ») : SQLite ne nomme pas ses clés et ne peut donc pas
 * l'exercer. On compile ses Blueprints avec la VRAIE grammaire MySQL (sans connexion) sur un schéma simulé et on
 * vérifie l'ORDRE et les NOMS des instructions. Ce que cela ne prouve pas : l'acceptation du DDL par MySQL/TiDB.
 */
class RenamePromoteurConstraintsMigrationTest extends TestCase
{
    /** @var array<int, string> */
    private array $statements = [];

    /** @var array<string, array{fks: array<int, array<string, mixed>>, indexes: array<int, array<string, mixed>>}> */
    private array $state = [];

    private function migration(): object
    {
        return require base_path('database/migrations/2026_11_11_120300_rename_promoteur_constraints_to_promoter.php');
    }

    /** Schéma après les renommages de colonnes d'une base vierge MySQL : FK et index sous l'ancien nom. */
    private function virginState(): void
    {
        foreach (['events', 'commissions', 'commission_settings'] as $table) {
            $this->state[$table] = [
                'fks' => [['name' => "{$table}_promoteur_id_foreign", 'columns' => ['promoter_id'], 'foreign_table' => 'users', 'foreign_columns' => ['id'], 'on_update' => 'no action', 'on_delete' => 'cascade']],
                'indexes' => [['name' => "{$table}_promoteur_id_foreign", 'columns' => ['promoter_id'], 'primary' => false, 'unique' => false]],
            ];
        }
        // commissions possède en plus l'index composite posé par 195133
        $this->state['commissions']['indexes'][] = ['name' => 'commissions_promoter_id_status_index', 'columns' => ['promoter_id', 'status'], 'primary' => false, 'unique' => false];
    }

    /** @param Closure(string): bool|null $failWhen reçoit l'instruction SQL MySQL et renvoie true pour la faire échouer */
    private function fakeSchema(?Closure $failWhen = null): void
    {
        $connection = new MySqlConnection(fn () => null, 'fake');
        $connection->setSchemaGrammar(new MySqlGrammar($connection));

        Schema::shouldReceive('hasTable')->andReturn(true);
        Schema::shouldReceive('hasColumn')->andReturn(true);
        Schema::shouldReceive('getForeignKeys')->andReturnUsing(fn ($t) => $this->state[$t]['fks']);
        Schema::shouldReceive('getIndexes')->andReturnUsing(fn ($t) => $this->state[$t]['indexes']);
        Schema::shouldReceive('hasIndex')->andReturnUsing(
            fn ($t, $name) => collect($this->state[$t]['indexes'])->contains(fn ($i) => $i['name'] === $name)
        );
        Schema::shouldReceive('table')->andReturnUsing(function ($table, Closure $callback) use ($connection, $failWhen) {
            foreach ((new Blueprint($connection, $table, $callback))->toSql() as $sql) {
                if ($failWhen && $failWhen($sql)) {
                    throw new \RuntimeException('échec simulé');
                }
                $this->statements[] = $sql;
                $this->apply($table, $sql);
            }
        });
    }

    private function apply(string $table, string $sql): void
    {
        if (preg_match('/add constraint `(\w+)` foreign key/', $sql, $m)) {
            $this->state[$table]['fks'][] = ['name' => $m[1], 'columns' => ['promoter_id'], 'foreign_table' => 'users', 'foreign_columns' => ['id'], 'on_update' => 'no action', 'on_delete' => 'cascade'];
        } elseif (preg_match('/drop foreign key `(\w+)`/', $sql, $m)) {
            $this->state[$table]['fks'] = array_values(array_filter($this->state[$table]['fks'], fn ($f) => $f['name'] !== $m[1]));
        } elseif (preg_match('/add (?:unique )?index `(\w+)`/', $sql, $m)) {
            $this->state[$table]['indexes'][] = ['name' => $m[1], 'columns' => ['promoter_id'], 'primary' => false, 'unique' => false];
        } elseif (preg_match('/drop index `(\w+)`/', $sql, $m)) {
            $this->state[$table]['indexes'] = array_values(array_filter($this->state[$table]['indexes'], fn ($i) => $i['name'] !== $m[1]));
        }
    }

    private function position(string $needle, string $table): int
    {
        foreach ($this->statements as $i => $sql) {
            if (str_contains($sql, "`$table`") && str_contains($sql, $needle)) {
                return $i;
            }
        }

        return -1;
    }

    public function test_ordre_et_noms_des_instructions_mysql(): void
    {
        $this->virginState();
        $this->fakeSchema();

        $this->migration()->up();

        foreach (['events', 'commissions', 'commission_settings'] as $t) {
            $add = $this->position("add constraint `{$t}_promoter_id_foreign` foreign key (`promoter_id`) references `users` (`id`) on delete cascade", $t);
            $dropFk = $this->position("drop foreign key `{$t}_promoteur_id_foreign`", $t);
            $dropIdx = $this->position("drop index `{$t}_promoteur_id_foreign`", $t);

            $this->assertGreaterThanOrEqual(0, $add, "$t : nouvelle clé non ajoutée");
            $this->assertGreaterThan($add, $dropFk, "$t : l'ancienne clé doit être supprimée APRÈS l'ajout de la nouvelle");
            $this->assertGreaterThan($dropFk, $dropIdx, "$t : l'index « promoteur » se supprime en dernier");

            $this->assertSame(["{$t}_promoter_id_foreign"], array_column($this->state[$t]['fks'], 'name'), "$t : état final");
            $this->assertNotEmpty(array_filter($this->state[$t]['indexes'], fn ($i) => $i['columns'][0] === 'promoter_id'), "$t : la clé doit avoir un index");
            foreach ($this->state[$t]['indexes'] as $i) {
                $this->assertStringNotContainsString('promoteur', $i['name']);
            }
        }

        // L'index explicite est créé AVANT la nouvelle clé quand aucun autre index ne la soutient ; commissions a déjà le composite.
        $this->assertGreaterThanOrEqual(0, $this->position('add index `events_promoter_id_index`', 'events'));
        $this->assertLessThan($this->position('add constraint `events_promoter_id_foreign`', 'events'), $this->position('add index `events_promoter_id_index`', 'events'));
        $this->assertSame(-1, $this->position('add index `commissions_promoter_id_index`', 'commissions'));

        // Jamais de suppression de contrainte sans que la nouvelle existe déjà
        $newExists = [];
        foreach ($this->statements as $sql) {
            preg_match('/^alter table `(\w+)`/', $sql, $m);
            if (str_contains($sql, 'add constraint')) {
                $newExists[$m[1]] = true;
            }
            if (str_contains($sql, 'drop foreign key')) {
                $this->assertTrue($newExists[$m[1]] ?? false, "drop foreign key sans nouvelle clé sur {$m[1]}");
            }
        }
    }

    public function test_une_base_deja_propre_ne_declenche_aucune_instruction(): void
    {
        $this->virginState();
        $this->fakeSchema();
        $migration = $this->migration();

        $migration->up();
        $count = count($this->statements);
        $migration->up(); // rejeu

        $this->assertSame($count, count($this->statements));
    }

    public function test_si_l_ajout_de_la_nouvelle_cle_echoue_tout_reste_intact(): void
    {
        $this->virginState();
        Log::spy();
        $this->fakeSchema(fn (string $sql) => str_contains($sql, 'add constraint'));

        $this->migration()->up(); // aucune exception

        foreach (['events', 'commissions', 'commission_settings'] as $t) {
            $this->assertSame(["{$t}_promoteur_id_foreign"], array_column($this->state[$t]['fks'], 'name'));
        }
        foreach ($this->statements as $sql) {
            $this->assertStringNotContainsString('drop foreign key', $sql);
            $this->assertStringNotContainsString('drop index', $sql);
        }
        Log::shouldHaveReceived('warning')->atLeast()->times(3);
    }

    /** @return array<string, array{string}> */
    public static function etapes(): array
    {
        return ['index explicite' => ['add index'], 'nouvelle clé' => ['add constraint'], 'ancienne clé' => ['drop foreign key'], 'ancien index' => ['drop index']];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('etapes')]
    public function test_chaque_etape_en_echec_est_journalisee_sans_relancer_d_exception(string $step): void
    {
        $this->virginState();
        Log::spy();
        $this->fakeSchema(fn (string $sql) => str_contains($sql, $step));

        $this->migration()->up(); // aucune exception

        Log::shouldHaveReceived('warning')->atLeast()->once();
        foreach (['events', 'commissions', 'commission_settings'] as $t) {
            $this->assertNotEmpty($this->state[$t]['fks'], "étape « $step » : $t sans clé étrangère");
        }
    }

    public function test_une_inspection_qui_leve_ne_relance_rien(): void
    {
        Log::spy();
        Schema::shouldReceive('hasTable')->andThrow(new \RuntimeException('panne'));

        $this->migration()->up();

        Log::shouldHaveReceived('warning')->atLeast()->times(3);
    }
}
