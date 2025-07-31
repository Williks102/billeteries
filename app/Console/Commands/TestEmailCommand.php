<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\EmailService;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class TestEmailCommand extends Command
{
    protected $signature = 'email:test {email?} {--debug : Afficher les détails de configuration}';
    protected $description = 'Tester l\'envoi d\'emails';

    public function handle()
    {
        $email = $this->argument('email') ?? config('mail.from.address');
        
        // Afficher la configuration si --debug
        if ($this->option('debug')) {
            $this->info("🔧 Configuration email actuelle :");
            $this->line("   Mailer par défaut : " . config('mail.default'));
            $this->line("   Host SMTP : " . config('mail.mailers.smtp.host'));
            $this->line("   Port SMTP : " . config('mail.mailers.smtp.port'));
            $this->line("   Adresse FROM : " . config('mail.from.address'));
            $this->line("   Nom FROM : " . config('mail.from.name'));
            $this->line("");
        }
        
        $this->info("📧 Envoi d'un email de test à : {$email}");
        
        try {
            // Test direct sans service pour plus de simplicité
            Mail::raw('✅ Test email depuis ClicBillet CI - ' . now(), function ($message) use ($email) {
                $message->to($email)
                        ->subject('🎫 Test Email - ClicBillet CI - ' . now()->format('H:i:s'));
            });
            
            $this->info("✅ Email envoyé avec succès !");
            
            // Si on utilise le driver 'log', indiquer où voir le résultat
            if (config('mail.default') === 'log') {
                $this->warn("ℹ️  Votre configuration utilise le driver 'log'.");
                $this->line("   Vérifiez le fichier : storage/logs/laravel.log");
            }
            
            return Command::SUCCESS;
            
        } catch (\Exception $e) {
            $this->error("❌ Erreur lors de l'envoi de l'email :");
            $this->error("   Message : " . $e->getMessage());
            $this->error("   Fichier : " . $e->getFile() . " ligne " . $e->getLine());
            
            // Log l'erreur complète
            Log::error("Erreur test email", [
                'email' => $email,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return Command::FAILURE;
        }
    }
}