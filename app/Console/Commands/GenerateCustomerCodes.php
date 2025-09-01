<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;

class GenerateCustomerCodes extends Command
{
    protected $signature = 'users:generate-codes {--force : Régénérer même pour les utilisateurs qui ont déjà un code}';
    protected $description = 'Générer les codes clients manquants';

    public function handle()
    {
        $force = $this->option('force');
        
        $query = User::query();
        if (!$force) {
            $query->whereNull('customer_code');
        }
        
        $users = $query->get();
        $generated = 0;

        foreach ($users as $user) {
            if ($force || !$user->customer_code) {
                $oldCode = $user->customer_code;
                $user->assignCustomerCode();
                
                $this->line(sprintf(
                    "✅ %s: %s → %s", 
                    $user->email, 
                    $oldCode ?? 'AUCUN', 
                    $user->customer_code
                ));
                
                $generated++;
            }
        }

        $this->info("\n🎉 {$generated} code(s) client générés !");
        
        return Command::SUCCESS;
    }
}