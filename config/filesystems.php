<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Filesystem Disk
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default filesystem disk that should be used
    | by the framework. The "local" disk, as well as a variety of cloud
    | based disks are available to your application for file storage.
    |
    */

    'default' => env('FILESYSTEM_DISK', 'local'),

    /*
    |--------------------------------------------------------------------------
    | Disque des images (affiches d'événements, avatars)
    |--------------------------------------------------------------------------
    |
    | Source unique de vérité : App\Support\Media. Défaut « public » (disque local,
    | storage/app/public). Mettre MEDIA_DISK=r2 pour Cloudflare R2 (voir docs/stockage-images.md)
    | ou MEDIA_DISK=cloudinary (voir docs/stockage-cloudinary.md).
    |
    */

    'media_disk' => env('MEDIA_DISK', 'public'),

    /*
    |--------------------------------------------------------------------------
    | Filesystem Disks
    |--------------------------------------------------------------------------
    |
    | Below you may configure as many filesystem disks as necessary, and you
    | may even configure multiple disks for the same driver. Examples for
    | most supported storage drivers are configured here for reference.
    |
    | Supported drivers: "local", "ftp", "sftp", "s3"
    |
    */

    'disks' => [

        'local' => [
            'driver' => 'local',
            'root' => storage_path('app/private'),
            'serve' => true,
            'throw' => false,
            'report' => false,
        ],

        'public' => [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            'url' => env('APP_URL').'/storage',
            'visibility' => 'public',
            'throw' => false,
            'report' => false,
        ],

        's3' => [
            'driver' => 's3',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION'),
            'bucket' => env('AWS_BUCKET'),
            'url' => env('AWS_URL'),
            'endpoint' => env('AWS_ENDPOINT'),
            'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
            'throw' => false,
            'report' => false,
        ],

        // Cloudflare R2 (compatible S3). Pas de `visibility` : R2 ne gère pas les ACL, et le pilote « r2 »
        // (AppServiceProvider) retire l'en-tête x-amz-acl. L'accès public se règle sur le bucket.
        'r2' => [
            'driver' => 'r2',
            'key' => env('R2_ACCESS_KEY_ID'),
            'secret' => env('R2_SECRET_ACCESS_KEY'),
            'region' => 'auto',
            'bucket' => env('R2_BUCKET'),
            'endpoint' => env('R2_ENDPOINT'),
            'url' => env('R2_PUBLIC_URL') ?: null,
            'use_path_style_endpoint' => true,
            'retain_visibility' => false,
            'throw' => true,
            'report' => true,
        ],

        // Cloudinary (plan gratuit sans carte bancaire) : seul secret = CLOUDINARY_URL
        // (cloudinary://<api_key>:<api_secret>@<cloud_name>), lu par App\Support\Filesystem\CloudinaryConfig.
        // Voir docs/stockage-cloudinary.md.
        'cloudinary' => [
            'driver' => 'cloudinary',
            'cloudinary_url' => env('CLOUDINARY_URL'),
            'folder' => env('CLOUDINARY_FOLDER', ''),
            'transformations' => env('CLOUDINARY_TRANSFORMATIONS') ?: 'f_auto,q_auto',
            'throw' => true,
            'report' => true,
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Symbolic Links
    |--------------------------------------------------------------------------
    |
    | Here you may configure the symbolic links that will be created when the
    | `storage:link` Artisan command is executed. The array keys should be
    | the locations of the links and the values should be their targets.
    |
    */

    'links' => [
        public_path('storage') => storage_path('app/public'),
    ],

];
