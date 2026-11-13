<?php

namespace App\Support\Filesystem;

/**
 * Configuration Cloudinary lue depuis la variable CLOUDINARY_URL (cloudinary://<api_key>:<api_secret>@<cloud_name>).
 * Ne journalise et n'affiche JAMAIS la valeur : aucun message d'erreur ne la contient.
 */
final class CloudinaryConfig
{
    public const DEFAULT_TRANSFORMATIONS = 'f_auto,q_auto';

    public function __construct(
        public readonly string $cloudName,
        public readonly string $apiKey,
        public readonly string $apiSecret,
        public readonly string $folder = '',
        public readonly string $transformations = self::DEFAULT_TRANSFORMATIONS,
    ) {}

    /** Analyse CLOUDINARY_URL ; null si absente ou mal formée. */
    public static function parseUrl(?string $url): ?array
    {
        $url = trim((string) $url);
        if ($url === '') {
            return null;
        }

        if (! preg_match('#^cloudinary://([^:@/\s]+):([^@/\s]+)@([A-Za-z0-9_-]+)/?(?:\?.*)?$#', $url, $m)) {
            return null;
        }

        return ['api_key' => $m[1], 'api_secret' => $m[2], 'cloud_name' => $m[3]];
    }

    /** @param array $diskConfig Configuration du disque (config/filesystems.php) */
    public static function fromDiskConfig(array $diskConfig): ?self
    {
        $parsed = self::parseUrl($diskConfig['cloudinary_url'] ?? null);
        if ($parsed === null) {
            return null;
        }

        $transformations = $diskConfig['transformations'] ?? null;

        return new self(
            $parsed['cloud_name'],
            $parsed['api_key'],
            $parsed['api_secret'],
            trim((string) ($diskConfig['folder'] ?? ''), '/ '),
            is_string($transformations) && trim($transformations) !== '' ? trim($transformations, '/ ') : self::DEFAULT_TRANSFORMATIONS,
        );
    }

    /** Noms (jamais les valeurs) des variables d'environnement à corriger. */
    public static function problem(array $diskConfig): ?string
    {
        $raw = trim((string) ($diskConfig['cloudinary_url'] ?? ''));
        if ($raw === '') {
            return 'CLOUDINARY_URL manquante';
        }

        return self::parseUrl($raw) === null
            ? 'CLOUDINARY_URL mal formée (format attendu : cloudinary://<api_key>:<api_secret>@<cloud_name>)'
            : null;
    }
}
