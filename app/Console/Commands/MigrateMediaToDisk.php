<?php

namespace App\Console\Commands;

use App\Support\Media;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class MigrateMediaToDisk extends Command
{
    protected $signature = 'media:migrate-to-r2 {--dry-run : Affiche ce qui serait copié sans rien écrire}';

    protected $description = 'Copie les images locales (storage/app/public : events/, avatars/) vers le disque média (MEDIA_DISK). Ne supprime rien, idempotent.';

    public function handle(): int
    {
        $target = Media::diskName();

        if ($target === 'public') {
            $this->error('MEDIA_DISK vaut « public » : rien à migrer. Définissez MEDIA_DISK=r2 (et les variables R2_*) d\'abord.');

            return self::FAILURE;
        }

        try {
            Media::assertConfigured();
        } catch (\Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $dry = (bool) $this->option('dry-run');
        $source = Storage::disk('public');
        $destination = Media::disk();
        $copied = $skipped = $failed = 0;

        foreach (['events', 'avatars'] as $directory) {
            foreach ($source->allFiles($directory) as $path) {
                try {
                    if ($destination->exists($path)) {
                        $skipped++;

                        continue;
                    }

                    if ($dry) {
                        $this->line('[simulation] '.$path);
                        $copied++;

                        continue;
                    }

                    $stream = $source->readStream($path);
                    $destination->writeStream($path, $stream);
                    if (is_resource($stream)) {
                        fclose($stream);
                    }
                    $this->line('copié : '.$path);
                    $copied++;
                } catch (\Throwable $e) {
                    $failed++;
                    $this->warn('échec : '.$path.' ('.get_class($e).')');
                }
            }
        }

        $this->info(($dry ? 'Simulation : ' : '').$copied.' à copier/copié(s), '.$skipped.' déjà présent(s), '.$failed.' échec(s). Aucun fichier local supprimé.');

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}
