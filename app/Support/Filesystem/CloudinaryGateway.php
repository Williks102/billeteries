<?php

namespace App\Support\Filesystem;

/**
 * Appels réseau vers Cloudinary, isolés pour pouvoir être remplacés par un faux dans les tests.
 */
interface CloudinaryGateway
{
    /** Envoie une image (chemin local ou URL https) de façon SIGNÉE ; remplace une ressource existante de même identifiant. */
    public function upload(string $source, string $publicId): void;

    /** Supprime la ressource ; sans erreur si elle n'existe pas. */
    public function destroy(string $publicId): void;

    public function exists(string $publicId): bool;
}
