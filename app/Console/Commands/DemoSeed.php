<?php

namespace App\Console\Commands;

use App\Models\CommissionSetting;
use App\Models\EventCategory;
use App\Models\User;
use Database\Seeders\CommissionSettingSeeder;
use Database\Seeders\EventCategorySeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Initialisation minimale d'une instance de démo (appelée par docker/entrypoint.sh quand DEMO_SEED=true).
 * Idempotent : ne fait RIEN si la table users contient déjà une ligne. Le mot de passe admin vient exclusivement de
 * l'environnement (ADMIN_PASSWORD, 12 caractères minimum), n'est jamais passé en argument ni affiché.
 */
class DemoSeed extends Command
{
    public const MIN_PASSWORD_LENGTH = 12;

    protected $signature = 'demo:seed';

    protected $description = 'Crée catégories, réglages de commission et compte admin (ADMIN_EMAIL / ADMIN_PASSWORD) si la base est vide';

    public function handle(): int
    {
        if (User::query()->exists()) {
            $this->info('demo:seed : la table users n\'est pas vide, rien à faire.');

            return self::SUCCESS;
        }

        $email = trim((string) env('ADMIN_EMAIL'));
        $password = (string) env('ADMIN_PASSWORD');

        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            $this->error('demo:seed : ADMIN_EMAIL est absent ou invalide. Aucun compte créé.');

            return self::FAILURE;
        }

        if (mb_strlen($password) < self::MIN_PASSWORD_LENGTH) {
            $this->error('demo:seed : ADMIN_PASSWORD est absent ou fait moins de '.self::MIN_PASSWORD_LENGTH.' caractères. Aucun compte créé.');

            return self::FAILURE;
        }

        DB::transaction(function () use ($email, $password) {
            if (! EventCategory::query()->exists()) {
                app(EventCategorySeeder::class)->run();
            }

            if (! CommissionSetting::query()->exists()) {
                app(CommissionSettingSeeder::class)->run();
            }

            $admin = new User([
                'name' => 'Administrateur',
                'email' => $email,
                'password' => $password, // haché par le cast 'hashed' du modèle
                'role' => 'admin',
            ]);
            $admin->forceFill(['email_verified_at' => now()])->save();
        });

        $this->info('demo:seed : catégories, commissions et compte administrateur créés.');

        return self::SUCCESS;
    }
}
