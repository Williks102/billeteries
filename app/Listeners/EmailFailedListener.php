<?php

namespace App\Listeners;

use Illuminate\Queue\Events\JobFailed;
use App\Services\EmailLogService;

class EmailFailedListener
{
    public function handle(JobFailed $event)
    {
        // Si c'est un job d'email qui a échoué
        if (str_contains($event->payload, 'mail')) {
            // Logger l'échec
            EmailLogService::logEmail(
                'unknown@email.com',
                'Unknown',
                'Email failed',
                'unknown',
                [],
                'failed',
                $event->exception->getMessage()
            );

            // Optionnel : Alerter les admins si trop d'échecs
            $this->checkFailureThreshold();
        }
    }

    private function checkFailureThreshold()
    {
        $recentFailures = \DB::table('mail_logs')
            ->where('status', 'failed')
            ->where('created_at', '>=', now()->subHour())
            ->count();

        if ($recentFailures > 10) {
            // Alerter les admins
            \Log::critical("Trop d'emails échoués : {$recentFailures} en 1h");
        }
    }
}