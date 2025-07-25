<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Ticket;

class GenerateQrCodes extends Command
{
    protected $signature = 'tickets:generate-qr {--missing : Générer seulement les QR codes manquants}';
    protected $description = 'Générer les QR codes pour les billets';

    public function handle()
    {
        $this->info('🎫 Génération des QR codes...');
        
        $query = Ticket::query();
        
        if ($this->option('missing')) {
            $query->whereNull('qr_code_path');
            $this->info('Mode: QR codes manquants seulement');
        } else {
            $this->info('Mode: Tous les billets');
        }
        
        $tickets = $query->get();
        $this->info("📊 {$tickets->count()} billets à traiter");
        
        $progressBar = $this->output->createProgressBar($tickets->count());
        $progressBar->start();
        
        $success = 0;
        $errors = 0;
        
        foreach ($tickets as $ticket) {
            try {
                $path = $ticket->generateQrCode();
                if ($path) {
                    $success++;
                } else {
                    $errors++;
                }
            } catch (\Exception $e) {
                $this->error("\nErreur pour le billet {$ticket->ticket_code}: " . $e->getMessage());
                $errors++;
            }
            
            $progressBar->advance();
        }
        
        $progressBar->finish();
        
        $this->newLine(2);
        $this->info("✅ QR codes générés avec succès: {$success}");
        if ($errors > 0) {
            $this->error("❌ Erreurs: {$errors}");
        }
        
        $this->info('🎉 Génération terminée !');
    }
}