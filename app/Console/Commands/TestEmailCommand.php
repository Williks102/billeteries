namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\EmailService;

class TestEmailCommand extends Command
{
    protected $signature = 'email:test {email?}';
    protected $description = 'Tester l\'envoi d\'emails';

    public function handle()
    {
        $email = $this->argument('email') ?? config('mail.from.address');
        $emailService = app(\App\Services\EmailService::class);
        
        $this->info("Envoi d'un email de test à : {$email}");
        
        if ($emailService->sendTestEmail($email)) {
            $this->info("✅ Email envoyé avec succès !");
        } else {
            $this->error("❌ Erreur lors de l'envoi de l'email.");
        }
    }
}