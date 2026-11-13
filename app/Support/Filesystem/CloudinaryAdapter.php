<?php

namespace App\Support\Filesystem;

use Illuminate\Support\Facades\Http;
use League\Flysystem\Config;
use League\Flysystem\FileAttributes;
use League\Flysystem\FilesystemAdapter;
use League\Flysystem\UnableToCheckExistence;
use League\Flysystem\UnableToCopyFile;
use League\Flysystem\UnableToCreateDirectory;
use League\Flysystem\UnableToDeleteDirectory;
use League\Flysystem\UnableToDeleteFile;
use League\Flysystem\UnableToListContents;
use League\Flysystem\UnableToMoveFile;
use League\Flysystem\UnableToReadFile;
use League\Flysystem\UnableToRetrieveMetadata;
use League\Flysystem\UnableToSetVisibility;
use League\Flysystem\UnableToWriteFile;

/**
 * Adaptateur Flysystem pour Cloudinary (images uniquement).
 *
 * Les chemins applicatifs restent relatifs (« events/<hash>.jpg ») ; l'identifiant Cloudinary (public_id)
 * est ce chemin SANS extension, précédé du dossier optionnel CLOUDINARY_FOLDER.
 * Listage, déplacement et visibilité ne sont pas pris en charge (exceptions explicites).
 */
class CloudinaryAdapter implements FilesystemAdapter
{
    private const ALLOWED_EXTENSIONS = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

    private const ALLOWED_MIMES = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

    public function __construct(private readonly CloudinaryConfig $config, private readonly CloudinaryGateway $gateway) {}

    // ---- URL (hors réseau) ------------------------------------------------------------------

    /** Appelée par Laravel pour Storage::disk()->url() ; ne contacte jamais Cloudinary et ne contient aucune clé. */
    public function getUrl(string $path): string
    {
        $path = $this->cleanPath($path);
        $publicId = $this->publicId($path);
        $transformations = $this->config->transformations;

        // Sans f_auto (format explicite), on ajoute l'extension d'origine ; avec f_auto, Cloudinary choisit le format.
        $suffix = preg_match('/(^|,)f_/', $transformations) === 1 ? '' : '.'.strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $segment = $transformations !== '' ? $transformations.'/' : '';

        return 'https://res.cloudinary.com/'.rawurlencode($this->config->cloudName).'/image/upload/'.$segment.$this->encode($publicId).$suffix;
    }

    /** URL de l'original (aucune transformation), pour lire ou copier le fichier. */
    private function originalUrl(string $path): string
    {
        return 'https://res.cloudinary.com/'.rawurlencode($this->config->cloudName).'/image/upload/'.$this->encode($this->publicId($path));
    }

    // ---- Écriture ---------------------------------------------------------------------------

    public function write(string $path, string $contents, Config $config): void
    {
        $tmp = $this->tempFile();
        try {
            file_put_contents($tmp, $contents);
            $this->send($path, $tmp);
        } finally {
            @unlink($tmp);
        }
    }

    public function writeStream(string $path, $contents, Config $config): void
    {
        $tmp = $this->tempFile();
        try {
            $out = fopen($tmp, 'wb');
            stream_copy_to_stream($contents, $out);
            fclose($out);
            $this->send($path, $tmp);
        } finally {
            @unlink($tmp);
        }
    }

    private function send(string $path, string $localFile): void
    {
        $path = $this->cleanPath($path);
        $this->assertImagePath($path, fn (string $why) => UnableToWriteFile::atLocation($path, $why));

        $mime = (string) (new \finfo(FILEINFO_MIME_TYPE))->file($localFile);
        if (! in_array($mime, self::ALLOWED_MIMES, true)) {
            throw UnableToWriteFile::atLocation($path, 'Seules les images JPEG, PNG, GIF et WebP sont acceptées.');
        }

        try {
            $this->gateway->upload($localFile, $this->publicId($path));
        } catch (\Throwable $e) {
            // Le message du SDK est conservé via getPrevious() ; jamais de clé dans notre message.
            throw UnableToWriteFile::atLocation($path, 'envoi à Cloudinary refusé ou impossible ('.get_class($e).')', $e);
        }
    }

    public function delete(string $path): void
    {
        $path = $this->cleanPath($path);
        try {
            $this->gateway->destroy($this->publicId($path));
        } catch (\Throwable $e) {
            throw UnableToDeleteFile::atLocation($path, 'suppression Cloudinary impossible ('.get_class($e).')', $e);
        }
    }

    /** Crée une NOUVELLE ressource (Cloudinary télécharge l'original) : supprimer la source ne casse pas la copie. */
    public function copy(string $source, string $destination, Config $config): void
    {
        $source = $this->cleanPath($source);
        $destination = $this->cleanPath($destination);
        $this->assertImagePath($destination, fn (string $why) => UnableToCopyFile::fromLocationTo($source, $destination, new \RuntimeException($why)));

        try {
            $this->gateway->upload($this->originalUrl($source), $this->publicId($destination));
        } catch (\Throwable $e) {
            throw UnableToCopyFile::fromLocationTo($source, $destination, $e);
        }
    }

    // ---- Lecture ----------------------------------------------------------------------------

    public function fileExists(string $path): bool
    {
        $path = $this->cleanPath($path);
        try {
            return $this->gateway->exists($this->publicId($path));
        } catch (\Throwable $e) {
            throw UnableToCheckExistence::forLocation($path, $e);
        }
    }

    public function directoryExists(string $path): bool
    {
        return false;
    }

    public function read(string $path): string
    {
        $path = $this->cleanPath($path);
        try {
            $response = Http::timeout(15)->get($this->originalUrl($path));
        } catch (\Throwable $e) {
            throw UnableToReadFile::fromLocation($path, 'téléchargement impossible', $e);
        }
        if (! $response->successful()) {
            throw UnableToReadFile::fromLocation($path, 'réponse HTTP '.$response->status());
        }

        return $response->body();
    }

    public function readStream(string $path)
    {
        $stream = fopen('php://temp', 'w+b');
        fwrite($stream, $this->read($path));
        rewind($stream);

        return $stream;
    }

    // ---- Non pris en charge -----------------------------------------------------------------

    public function deleteDirectory(string $path): void
    {
        throw UnableToDeleteDirectory::atLocation($path, 'non pris en charge par le disque Cloudinary');
    }

    public function createDirectory(string $path, Config $config): void
    {
        throw UnableToCreateDirectory::atLocation($path, 'non pris en charge par le disque Cloudinary');
    }

    public function setVisibility(string $path, string $visibility): void
    {
        throw UnableToSetVisibility::atLocation($path, 'les ressources Cloudinary sont publiques : visibilité non gérée');
    }

    public function visibility(string $path): FileAttributes
    {
        throw UnableToRetrieveMetadata::visibility($path, 'non pris en charge par le disque Cloudinary');
    }

    public function mimeType(string $path): FileAttributes
    {
        throw UnableToRetrieveMetadata::mimeType($path, 'non pris en charge par le disque Cloudinary');
    }

    public function lastModified(string $path): FileAttributes
    {
        throw UnableToRetrieveMetadata::lastModified($path, 'non pris en charge par le disque Cloudinary');
    }

    public function fileSize(string $path): FileAttributes
    {
        throw UnableToRetrieveMetadata::fileSize($path, 'non pris en charge par le disque Cloudinary');
    }

    public function listContents(string $path, bool $deep): iterable
    {
        throw UnableToListContents::atLocation($path, $deep, new \RuntimeException('non pris en charge par le disque Cloudinary'));
    }

    public function move(string $source, string $destination, Config $config): void
    {
        throw UnableToMoveFile::fromLocationTo($source, $destination, new \RuntimeException('non pris en charge par le disque Cloudinary'));
    }

    // ---- Outils -----------------------------------------------------------------------------

    /** Chemin relatif propre : pas de « .. », pas de « \ », pas de « / » initial. */
    private function cleanPath(string $path): string
    {
        $path = ltrim(str_replace('\\', '/', $path), '/');
        foreach (explode('/', $path) as $segment) {
            if ($segment === '..' || $segment === '.' || str_contains($segment, "\0")) {
                throw new \InvalidArgumentException('Chemin d\'image invalide.');
            }
        }
        if ($path === '') {
            throw new \InvalidArgumentException('Chemin d\'image vide.');
        }

        return $path;
    }

    private function assertImagePath(string $path, callable $exception): void
    {
        if (! in_array(strtolower(pathinfo($path, PATHINFO_EXTENSION)), self::ALLOWED_EXTENSIONS, true)) {
            throw $exception('Extension non autorisée : seules les images jpg, jpeg, png, gif et webp sont acceptées.');
        }
    }

    private function publicId(string $path): string
    {
        $withoutExtension = preg_replace('/\.[A-Za-z0-9]+$/', '', $path);
        $folder = $this->config->folder;

        return ($folder !== '' ? $folder.'/' : '').$withoutExtension;
    }

    private function encode(string $publicId): string
    {
        return implode('/', array_map('rawurlencode', explode('/', $publicId)));
    }

    private function tempFile(): string
    {
        $tmp = tempnam(sys_get_temp_dir(), 'cld');
        if ($tmp === false) {
            throw new \RuntimeException('Fichier temporaire impossible à créer.');
        }

        return $tmp;
    }
}
