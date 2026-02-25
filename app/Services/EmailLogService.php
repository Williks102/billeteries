namespace App\Services;

use Illuminate\Support\Facades\DB;

class EmailLogService
{
    /**
     * Logger un email envoyé
     */
    public static function logEmail($toEmail, $toName, $subject, $template, $data = [], $status = 'sent', $error = null)
    {
        try {
            DB::table('mail_logs')->insert([
                'to_email' => $toEmail,
                'to_name' => $toName,
                'subject' => $subject,
                'template' => $template,
                'data' => json_encode($data),
                'status' => $status,
                'error_message' => $error,
                'sent_at' => $status === 'sent' ? now() : null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Exception $e) {
            \Log::error('Erreur logging email: ' . $e->getMessage());
        }
    }

    /**
     * Statistiques des emails
     */
    public static function getEmailStats($days = 30)
    {
        $since = now()->subDays($days);
        
        return [
            'total_sent' => DB::table('mail_logs')
                ->where('created_at', '>=', $since)
                ->where('status', 'sent')
                ->count(),
            'total_failed' => DB::table('mail_logs')
                ->where('created_at', '>=', $since)  
                ->where('status', 'failed')
                ->count(),
            'by_template' => DB::table('mail_logs')
                ->where('created_at', '>=', $since)
                ->select('template', DB::raw('count(*) as count'))
                ->groupBy('template')
                ->get(),
            'success_rate' => 0 // Calculé après
        ];
    }
}