<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Mail;
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
            'storage_used' => $this->formatBytes($this->getStorageSize()),
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

    /**
     * Paramètres généraux
     */
    private function updateGeneralSettings(Request $request)
    {
        $request->validate([
            'app_name' => 'required|string|max:255',
            'app_url' => 'required|url',
            'timezone' => 'required|string',
            'commission_rate' => 'required|numeric|min:0|max:100',
            'max_tickets_per_order' => 'required|integer|min:1|max:50',
            'allow_refunds' => 'boolean',
            'maintenance_mode' => 'boolean',
        ]);

        try {
            // Mettre à jour le fichier .env (simulation - en réalité il faudrait utiliser un package comme vlucas/phpdotenv)
            $this->updateEnvFile([
                'APP_NAME' => $request->app_name,
                'APP_URL' => $request->app_url,
                'APP_TIMEZONE' => $request->timezone,
            ]);

            // Mettre à jour les paramètres en base de données (si vous avez une table settings)
            $this->updateDatabaseSettings([
                'commission_rate' => $request->commission_rate,
                'max_tickets_per_order' => $request->max_tickets_per_order,
                'allow_refunds' => $request->boolean('allow_refunds'),
                'maintenance_mode' => $request->boolean('maintenance_mode'),
            ]);

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

    /**
     * Test d'envoi d'email
     */
    public function testEmail(Request $request)
    {
        $request->validate([
            'test_email' => 'required|email'
        ]);

        try {
            $testEmail = $request->test_email;
            
            Mail::raw('🎫 Email de test depuis ClicBillet CI - ' . now(), function ($message) use ($testEmail) {
                $message->to($testEmail)
                        ->subject('Test Email - ClicBillet CI - ' . now()->format('H:i:s'));
            });

            \Log::info('Email de test envoyé par admin', [
                'admin_id' => auth()->id(),
                'test_email' => $testEmail
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Email de test envoyé avec succès !'
            ]);

        } catch (\Exception $e) {
            \Log::error('Erreur envoi email de test: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'envoi : ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Sauvegarde de la base de données
     */
    public function backup(Request $request)
    {
        try {
            $fileName = 'backup-' . now()->format('Y-m-d-H-i-s') . '.sql';
            $filePath = storage_path('app/backups/' . $fileName);

            // Créer le dossier si inexistant
            if (!File::exists(dirname($filePath))) {
                File::makeDirectory(dirname($filePath), 0755, true);
            }

            // Exécuter mysqldump (à adapter selon votre configuration)
            $command = sprintf(
                'mysqldump -u%s -p%s %s > %s',
                config('database.connections.mysql.username'),
                config('database.connections.mysql.password'),
                config('database.connections.mysql.database'),
                $filePath
            );

            exec($command, $output, $returnVar);

            if ($returnVar === 0 && File::exists($filePath)) {
                \Log::info('Sauvegarde créée par admin', [
                    'admin_id' => auth()->id(),
                    'backup_file' => $fileName,
                    'file_size' => File::size($filePath)
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Sauvegarde créée avec succès !',
                    'file' => $fileName,
                    'size' => $this->formatBytes(File::size($filePath))
                ]);
            } else {
                throw new \Exception('Échec de la commande mysqldump');
            }

        } catch (\Exception $e) {
            \Log::error('Erreur création sauvegarde: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la sauvegarde : ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Vider le cache
     */
    public function clearCache(Request $request)
    {
        try {
            $cacheTypes = $request->get('cache_types', ['config', 'route', 'view']);
            $clearedCaches = [];

            if (in_array('config', $cacheTypes)) {
                Artisan::call('config:clear');
                $clearedCaches[] = 'Configuration';
            }

            if (in_array('route', $cacheTypes)) {
                Artisan::call('route:clear');
                $clearedCaches[] = 'Routes';
            }

            if (in_array('view', $cacheTypes)) {
                Artisan::call('view:clear');
                $clearedCaches[] = 'Vues';
            }

            if (in_array('application', $cacheTypes)) {
                Cache::flush();
                $clearedCaches[] = 'Application';
            }

            if (in_array('opcache', $cacheTypes) && function_exists('opcache_reset')) {
                opcache_reset();
                $clearedCaches[] = 'OPCache';
            }

            \Log::info('Cache vidé par admin', [
                'admin_id' => auth()->id(),
                'cache_types' => $clearedCaches
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Cache vidé avec succès : ' . implode(', ', $clearedCaches)
            ]);

        } catch (\Exception $e) {
            \Log::error('Erreur vidage cache: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du vidage du cache : ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Optimisation de la base de données
     */
    public function optimizeDatabase(Request $request)
    {
        try {
            // Optimiser les tables
            $tables = DB::select('SHOW TABLES');
            $optimizedTables = [];

            foreach ($tables as $table) {
                $tableName = array_values((array) $table)[0];
                DB::statement("OPTIMIZE TABLE `{$tableName}`");
                $optimizedTables[] = $tableName;
            }

            \Log::info('Base de données optimisée par admin', [
                'admin_id' => auth()->id(),
                'tables_optimized' => count($optimizedTables)
            ]);

            return response()->json([
                'success' => true,
                'message' => count($optimizedTables) . ' table(s) optimisée(s) avec succès !'
            ]);

        } catch (\Exception $e) {
            \Log::error('Erreur optimisation base de données: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'optimisation : ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Nettoyage des fichiers temporaires
     */
    public function cleanupFiles(Request $request)
    {
        try {
            $cleanupTypes = $request->get('cleanup_types', ['logs', 'temp']);
            $deletedFiles = 0;
            $freedSpace = 0;

            if (in_array('logs', $cleanupTypes)) {
                $logFiles = File::files(storage_path('logs'));
                $oldLogs = array_filter($logFiles, function($file) {
                    return $file->getMTime() < strtotime('-30 days');
                });
                
                foreach ($oldLogs as $file) {
                    $freedSpace += $file->getSize();
                    File::delete($file->getPathname());
                    $deletedFiles++;
                }
            }

            if (in_array('temp', $cleanupTypes)) {
                $tempFiles = File::files(storage_path('app/temp'));
                foreach ($tempFiles as $file) {
                    $freedSpace += $file->getSize();
                    File::delete($file->getPathname());
                    $deletedFiles++;
                }
            }

            if (in_array('cache_images', $cleanupTypes)) {
                $cacheImages = File::files(storage_path('app/public/cache'));
                foreach ($cacheImages as $file) {
                    if ($file->getMTime() < strtotime('-7 days')) {
                        $freedSpace += $file->getSize();
                        File::delete($file->getPathname());
                        $deletedFiles++;
                    }
                }
            }

            \Log::info('Nettoyage fichiers par admin', [
                'admin_id' => auth()->id(),
                'files_deleted' => $deletedFiles,
                'space_freed' => $freedSpace
            ]);

            return response()->json([
                'success' => true,
                'message' => "{$deletedFiles} fichier(s) supprimé(s), {$this->formatBytes($freedSpace)} libéré(s)"
            ]);

        } catch (\Exception $e) {
            \Log::error('Erreur nettoyage fichiers: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du nettoyage : ' . $e->getMessage()
            ], 500);
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
        } catch (\Exception $e) {
            return 'Inconnu';
        }
    }

    /**
     * Obtenir la taille du stockage utilisé
     */
    private function getStorageSize()
    {
        try {
            $size = 0;
            foreach ($files as $file) {
                $size += $file->getSize();
            }
            return $size;
        } catch (\Exception $e) {
            return 0;
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