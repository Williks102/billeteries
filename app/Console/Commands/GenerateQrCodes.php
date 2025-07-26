<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Ticket;

class GenerateQrCodes extends Command
{
    protected $signature = 'tickets:generate-qr-codes {--force : Régénérer même les QR codes existants}';
    protected $description = 'Générer les QR codes manquants pour tous les billets';

    public function handle()
    {
        $this->info('🎫 Génération des QR codes...');
        
        // Trouver les billets sans QR code
        $query = Ticket::where('status', 'sold');
        
        if (!$this->option('force')) {
            $query->whereNull('qr_code_path');
        }
        
        $tickets = $query->get();
        
        if ($tickets->count() === 0) {
            $this->info('✅ Tous les billets ont déjà un QR code !');
            return 0;
        }
        
        $this->info("📋 Trouvé {$tickets->count()} billets à traiter");
        
        $progressBar = $this->output->createProgressBar($tickets->count());
        $progressBar->start();
        
        $success = 0;
        $errors = 0;
        
        foreach ($tickets as $ticket) {
            try {
                if ($ticket->generateQrCode()) {
                    $success++;
                } else {
                    $errors++;
                }
            } catch (\Exception $e) {
                $errors++;
                $this->newLine();
                $this->error("❌ Erreur pour {$ticket->ticket_code}: " . $e->getMessage());
            }
            
            $progressBar->advance();
        }
        
        $progressBar->finish();
        $this->newLine(2);
        
        // Résumé
        $this->info("🎉 Génération terminée !");
        $this->line("✅ Succès : {$success}");
        
        if ($errors > 0) {
            $this->warn("⚠️  Erreurs : {$errors}");
        }
        
        return 0;
    }
}