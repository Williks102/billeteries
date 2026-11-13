<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\CommissionRate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class SettingsController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('admin');
    }

    /**
     * Affichage des paramètres système
     */
    public function index(Request $request)
    {
        $tab = $request->get('tab', 'general');

        // Informations système
        $systemInfo = [
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
            'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Inconnu',
            'memory_limit' => ini_get('memory_limit'),
            'upload_max_filesize' => ini_get('upload_max_filesize'),
            'max_execution_time' => ini_get('max_execution_time'),
            'disk_space' => $this->formatBytes(disk_free_space('/')),
            'database_size' => $this->getDatabaseSize(),
        ];

        // Statistiques globales
        $globalStats = [
            'total_users' => \App\Models\User::count(),
            'total_events' => \App\Models\Event::count(),
            'total_orders' => \App\Models\Order::count(),
            'total_revenue' => \App\Models\Order::where('payment_status', 'paid')->sum('total_amount'),
            'cache_entries' => $this->getCacheEntries(),
            'log_files_count' => count(File::files(storage_path('logs'))),
            'storage_used' => $this->storageUsedLabel(),
        ];

        // Configuration actuelle
        $currentConfig = [
            'app_name' => config('app.name'),
            'app_env' => config('app.env'),
            'app_debug' => config('app.debug'),
            'app_url' => config('app.url'),
            'mail_driver' => config('mail.default'),
            'mail_host' => config('mail.mailers.smtp.host'),
            'mail_from' => config('mail.from.address'),
            'queue_driver' => config('queue.default'),
            'cache_driver' => config('cache.default'),
            'session_driver' => config('session.driver'),
            'timezone' => config('app.timezone'),
        ];

        return view('admin.settings.index', compact('tab', 'systemInfo', 'globalStats', 'currentConfig'));
    }

    /**
     * Sauvegarde des paramètres
     */
    public function store(Request $request)
    {
        $section = $request->get('section', 'general');

        switch ($section) {
            case 'general':
                return $this->updateGeneralSettings($request);
            case 'commission':
                return $this->updateCommissionSettings($request);
            case 'email':
                return $this->updateEmailSettings($request);
            case 'payment':
                return $this->updatePaymentSettings($request);
            case 'security':
                return $this->updateSecuritySettings($request);
            case 'maintenance':
                return $this->updateMaintenanceSettings($request);
            default:
                return redirect()->back()->with('error', 'Section inconnue');
        }
    }

    private const COMMISSION_RULES = ['required', 'numeric', 'min:0', 'max:100', 'decimal:0,2'];

    private const COMMISSION_MESSAGES = [
        'commission_rate.required' => 'Indiquez le taux de commission.',
        'commission_rate.numeric' => 'Le taux de commission doit être un nombre (exemple : 6 ou 7.5).',
        'commission_rate.min' => 'Le taux de commission ne peut pas être négatif.',
        'commission_rate.max' => 'Le taux de commission ne peut pas dépasser 100 %.',
        'commission_rate.decimal' => 'Le taux de commission ne peut avoir que 2 décimales au maximum.',
    ];

    /**
     * Taux de commission par défaut : enregistrement ou retour à la valeur du serveur.
     */
    private function updateCommissionSettings(Request $request)
    {
        if ($request->boolean('reset')) {
            $hadStored = CommissionRate::stored() !== null;
            $from = CommissionRate::default();
            CommissionRate::reset();
            $to = CommissionRate::default();
            if ($hadStored) {
                $this->logCommissionChange($from, $to, 'Taux de commission : retour à la valeur par défaut du serveur');
            }

            return redirect()->route('admin.settings.index')->with('success', 'Taux de commission remis à la valeur par défaut du serveur.');
        }

        $request->validate(['commission_rate' => self::COMMISSION_RULES], self::COMMISSION_MESSAGES);

        $from = CommissionRate::default();
        $to = (float) $request->input('commission_rate');
        CommissionRate::save($to);
        if ($from !== $to) {
            $this->logCommissionChange($from, $to, sprintf('Taux de commission modifié : %s %% vers %s %%', $from, $to));
        }

        return redirect()->route('admin.settings.index')->with('success', 'Taux de commission enregistré. Il s\'applique aux nouvelles commandes.');
    }

    private function logCommissionChange(float $from, float $to, string $description, array $extra = []): void
    {
        \App\Services\ActivityLogger::record(
            \App\Models\ActivityLog::SETTINGS_UPDATED,
            null,
            $description,
            ['section' => 'general', 'commission_rate' => ['from' => $from, 'to' => $to]] + $extra
        );
    }

    /**
     * Paramètres généraux
     */
    private function updateGeneralSettings(Request $request)
    {
        $request->validate([
            'app_name' => 'required|string|max:255',
            'app_url' => 'required|url',
            'timezone' => 'required|string',
            'commission_rate' => self::COMMISSION_RULES,
            'max_tickets_per_order' => 'required|integer|min:1|max:50',
            'allow_refunds' => 'boolean',
            'maintenance_mode' => 'boolean',
        ], self::COMMISSION_MESSAGES);

        try {
            // Valeur effective AVANT (base ou défaut du serveur)
            $oldCommission = CommissionRate::default();

            // Mettre à jour le fichier .env (simulation - en réalité il faudrait utiliser un package comme vlucas/phpdotenv)
            $this->updateEnvFile([
                'APP_NAME' => $request->app_name,
                'APP_URL' => $request->app_url,
                'APP_TIMEZONE' => $request->timezone,
            ]);

            // Mettre à jour les paramètres en base de données (si vous avez une table settings)
            CommissionRate::save((float) $request->commission_rate);
            $this->updateDatabaseSettings([
                'max_tickets_per_order' => $request->max_tickets_per_order,
                'allow_refunds' => $request->boolean('allow_refunds'),
                'maintenance_mode' => $request->boolean('maintenance_mode'),
            ]);

            $newCommission = (float) $request->commission_rate;
            if ($oldCommission !== $newCommission) {
                \App\Services\ActivityLogger::record(
                    \App\Models\ActivityLog::SETTINGS_UPDATED,
                    null,
                    sprintf('Taux de commission modifié : %s %% vers %s %%', $oldCommission, $newCommission),
                    [
                        'section' => 'general',
                        'commission_rate' => ['from' => $oldCommission, 'to' => $newCommission],
                        'max_tickets_per_order' => (int) $request->max_tickets_per_order,
                        'allow_refunds' => $request->boolean('allow_refunds'),
                        'maintenance_mode' => $request->boolean('maintenance_mode'),
                    ]
                );
            }

            \Log::info('Paramètres généraux modifiés par admin', [
                'admin_id' => auth()->id(),
                'settings' => $request->only(['app_name', 'commission_rate', 'max_tickets_per_order'])
            ]);

            return redirect()->back()->with('success', 'Paramètres généraux mis à jour avec succès !');

        } catch (\Exception $e) {
            \Log::error('Erreur mise à jour paramètres généraux: ' . $e->getMessage());
            
            return redirect()->back()
                ->with('error', 'Erreur lors de la mise à jour des paramètres')
                ->withInput();
        }
    }

    /**
     * Paramètres email
     */
    private function updateEmailSettings(Request $request)
    {
        $request->validate([
            'mail_driver' => 'required|in:smtp,sendmail,mailgun,ses,log',
            'mail_host' => 'required_if:mail_driver,smtp|nullable|string',
            'mail_port' => 'required_if:mail_driver,smtp|nullable|integer',
            'mail_username' => 'required_if:mail_driver,smtp|nullable|string',
            'mail_password' => 'required_if:mail_driver,smtp|nullable|string',
            'mail_encryption' => 'nullable|in:tls,ssl',
            'mail_from_address' => 'required|email',
            'mail_from_name' => 'required|string|max:255',
        ]);

        try {
            $this->updateEnvFile([
                'MAIL_MAILER' => $request->mail_driver,
                'MAIL_HOST' => $request->mail_host,
                'MAIL_PORT' => $request->mail_port,
                'MAIL_USERNAME' => $request->mail_username,
                'MAIL_PASSWORD' => $request->mail_password,
                'MAIL_ENCRYPTION' => $request->mail_encryption,
                'MAIL_FROM_ADDRESS' => $request->mail_from_address,
                'MAIL_FROM_NAME' => $request->mail_from_name,
            ]);

            \App\Services\ActivityLogger::record(\App\Models\ActivityLog::SETTINGS_UPDATED, null, 'Paramètres e-mail modifiés', ['section' => 'email']);

            \Log::info('Paramètres email modifiés par admin', [
                'admin_id' => auth()->id(),
                'mail_driver' => $request->mail_driver,
                'mail_from' => $request->mail_from_address
            ]);

            return redirect()->back()->with('success', 'Paramètres email mis à jour avec succès !');

        } catch (\Exception $e) {
            \Log::error('Erreur mise à jour paramètres email: ' . $e->getMessage());
            
            return redirect()->back()
                ->with('error', 'Erreur lors de la mise à jour des paramètres email')
                ->withInput();
        }
    }

    // ================= MÉTHODES UTILITAIRES =================

    /**
     * Mettre à jour le fichier .env (simulation)
     */
    private function updateEnvFile(array $data)
    {
        // En production, utilisez un package comme vlucas/phpdotenv
        // ou une solution plus robuste pour modifier le .env
        foreach ($data as $key => $value) {
            // Simulation - en réalité il faut modifier le fichier .env
            Config::set(strtolower(str_replace('_', '.', $key)), $value);
        }
    }

    /**
     * Mettre à jour les paramètres en base de données
     */
    private function updateDatabaseSettings(array $settings)
    {
        // Si vous avez une table settings, sinon utilisez les config
        foreach ($settings as $key => $value) {
            // DB::table('settings')->updateOrInsert(['key' => $key], ['value' => $value]);
            Config::set("app.{$key}", $value);
        }
    }

    /**
     * Obtenir la taille de la base de données
     */
    private function getDatabaseSize()
    {
        try {
            $size = DB::select('
                SELECT SUM(data_length + index_length) as size 
                FROM information_schema.tables 
                WHERE table_schema = ?
            ', [config('database.connections.mysql.database')]);
            
            return $this->formatBytes($size[0]->size ?? 0);
        } catch (\Exception $e) {
            return 'Inconnu';
        }
    }

    /**
     * Obtenir le nombre d'entrées en cache
     */
    private function getCacheEntries()
    {
        try {
            // Dépend du driver de cache utilisé
            return Cache::getStore()->getRedis()->dbSize() ?? 'Inconnu';
        } catch (\Throwable $e) { // getRedis() inexistant hors Redis = \Error, pas \Exception
            return 'Inconnu';
        }
    }

    /**
     * Taille des images stockées sur le disque LOCAL du serveur. Sur un disque distant (R2, Cloudinary) elle n'est
     * pas mesurée ici : on le dit au lieu d'afficher un 0 trompeur.
     */
    private function storageUsedLabel(): string
    {
        $driver = config('filesystems.disks.'.\App\Support\Media::diskName().'.driver');
        if (! in_array($driver, ['local', null], true)) {
            return 'Hébergé hors du serveur (non mesuré)';
        }

        $dir = storage_path('app/public');
        if (! is_dir($dir)) {
            return $this->formatBytes(0);
        }

        try {
            $size = 0;
            foreach (File::allFiles($dir) as $file) {
                $size += $file->getSize();
            }

            return $this->formatBytes($size);
        } catch (\Throwable $e) {
            return 'Inconnu';
        }
    }

    /**
     * Formater les bytes en unités lisibles
     */
    private function formatBytes($size, $precision = 2)
    {
        if ($size === 0) return '0 B';
        
        $base = log($size, 1024);
        $suffixes = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        return round(pow(1024, $base - floor($base)), $precision) . ' ' . $suffixes[floor($base)];
    }
}