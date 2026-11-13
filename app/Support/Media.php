<?php

namespace App\Support;

use App\Support\Filesystem\CloudinaryConfig;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Point d'accès unique au stockage des images (affiches d'événements, avatars).
 *
 * Le disque vient de config('filesystems.media_disk') (variable MEDIA_DISK, défaut « public »).
 * Les chemins enregistrés en base restent relatifs (« events/xxx.jpg »), quel que soit le disque.
 */
class Media
{
    public static function diskName(): string
    {
        return (string) config('filesystems.media_disk', 'public');
    }

    public static function disk(): Filesystem
    {
        return Storage::disk(self::diskName());
    }

    /** Enregistre un fichier téléversé sous un nom généré (hash) ; renvoie le chemin relatif. */
    public static function store(\Illuminate\Http\UploadedFile $file, string $directory): string
    {
        self::assertConfigured();

        $path = $file->store($directory, self::diskName());

        if ($path === false) {
            throw new \RuntimeException('Échec de l\'enregistrement de l\'image sur le disque « '.self::diskName().' ».');
        }

        return $path;
    }

    /** Échec clair (et journalisé, sans jamais afficher de clé) si le disque distant est mal configuré. */
    public static function assertConfigured(): void
    {
        $name = self::diskName();
        $config = (array) config('filesystems.disks.'.$name, []);

        if (($config['driver'] ?? null) === 'r2') {
            $missing = [];
            foreach (['key' => 'R2_ACCESS_KEY_ID', 'secret' => 'R2_SECRET_ACCESS_KEY', 'bucket' => 'R2_BUCKET', 'endpoint' => 'R2_ENDPOINT'] as $k => $var) {
                if (empty($config[$k])) {
                    $missing[] = $var;
                }
            }
            if ($missing !== []) {
                Log::error('Stockage des images : variables R2 manquantes : '.implode(', ', $missing).'.');
                throw new \RuntimeException('Stockage R2 non configuré (variables manquantes : '.implode(', ', $missing).').');
            }
        }

        if (($config['driver'] ?? null) === 'cloudinary') {
            $problem = CloudinaryConfig::problem($config);
            if ($problem !== null) {
                Log::error('Stockage des images : '.$problem.'.');
                throw new \RuntimeException('Stockage Cloudinary non configuré ('.$problem.').');
            }
        }
    }

    /**
     * URL publique d'une image, ou null si le chemin est vide ou si l'URL ne peut pas être construite.
     * Ne fait JAMAIS d'appel réseau : l'URL est calculée à partir de la configuration.
     */
    public static function url(?string $path): ?string
    {
        if ($path === null || trim($path) === '') {
            return null;
        }

        $name = self::diskName();
        $config = (array) config('filesystems.disks.'.$name, []);

        // Disque distant sans URL publique : ne pas laisser le SDK deviner une URL (pas de lien cassé silencieux).
        if (in_array($config['driver'] ?? null, ['r2', 's3'], true) && empty($config['url'])) {
            Log::error('Stockage des images : URL publique absente pour le disque « '.$name.' » (R2_PUBLIC_URL non défini).');

            return null;
        }

        // Cloudinary sans CLOUDINARY_URL valide : pas d'URL devinée, pas d'appel réseau.
        if (($config['driver'] ?? null) === 'cloudinary' && ($problem = CloudinaryConfig::problem($config)) !== null) {
            Log::error('Stockage des images : '.$problem.'.');

            return null;
        }

        try {
            return Storage::disk($name)->url($path);
        } catch (\Throwable $e) {
            Log::error('Stockage des images : URL impossible pour le disque « '.$name.' » : '.$e->getMessage());

            return null;
        }
    }

    /**
     * Comme url(), mais sur disque LOCAL renvoie null si le fichier a disparu (évite une image cassée dans le panier).
     * Sur un disque distant, aucune vérification (elle coûterait un appel réseau par ligne) : l'`onerror` de la vue prend le relais.
     */
    public static function urlIfPresent(?string $path): ?string
    {
        $url = self::url($path);
        $driver = config('filesystems.disks.'.self::diskName().'.driver');

        if ($url !== null && ($driver === 'local' || $driver === null) && ! self::disk()->exists((string) $path)) {
            return null;
        }

        return $url;
    }

    /** Supprime une image ; ne lève jamais d'exception (une image orpheline ne doit pas bloquer une action). */
    public static function delete(?string $path): void
    {
        if ($path === null || trim($path) === '') {
            return;
        }

        try {
            self::disk()->delete($path);
        } catch (\Throwable $e) {
            Log::warning('Stockage des images : suppression impossible de « '.$path.' » : '.$e->getMessage());
        }
    }
}
