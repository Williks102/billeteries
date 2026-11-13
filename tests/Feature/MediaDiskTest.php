<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\User;
use App\Support\Media;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\BuildsEventsTable;
use Tests\Concerns\BuildsOrderSchema;
use Tests\TestCase;

/**
 * Stockage des images : tout passe par App\Support\Media (config filesystems.media_disk), jamais par 'public' en dur.
 * Aucun appel réseau : le disque média est un faux disque local.
 */
class MediaDiskTest extends TestCase
{
    use BuildsEventsTable;
    use BuildsOrderSchema;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-11-12 12:00:00');
        config(['filesystems.media_disk' => 'media_test']);
        Storage::fake('media_test');
        Storage::fake('public');
        $this->buildOrderSchema();
        $this->buildEventsTable();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function payload(array $override = []): array
    {
        return array_replace_recursive([
            'title' => 'Soirée', 'category_id' => 1, 'event_date' => '2026-11-14', 'event_time' => '20:00',
            'venue' => 'Palais', 'action' => 'draft',
            'ticket_types' => [0 => ['name' => 'Standard', 'price' => '5000', 'quantity_available' => '100', 'max_per_order' => '10']],
        ], $override);
    }

    public function test_default_media_disk_is_public(): void
    {
        $this->assertSame('public', (require base_path('config/filesystems.php'))['media_disk']);
    }

    public function test_url_is_null_for_empty_path(): void
    {
        $this->assertNull(Media::url(null));
        $this->assertNull(Media::url(''));
        $this->assertNull(Media::url('   '));
        $this->assertNotNull(Media::url('events/a.jpg'));
    }

    public function test_remote_disk_without_public_url_returns_null_without_network(): void
    {
        config(['filesystems.media_disk' => 'r2', 'filesystems.disks.r2.url' => null]);
        $this->assertNull(Media::url('events/a.jpg'));
    }

    public function test_remote_disk_url_comes_from_public_url_setting(): void
    {
        config([
            'filesystems.media_disk' => 'r2',
            'filesystems.disks.r2.url' => 'https://pub-test.r2.dev',
            'filesystems.disks.r2.key' => 'k', 'filesystems.disks.r2.secret' => 's',
            'filesystems.disks.r2.bucket' => 'b', 'filesystems.disks.r2.endpoint' => 'https://acc.r2.cloudflarestorage.com',
        ]);
        $this->assertSame('https://pub-test.r2.dev/events/a.jpg', Media::url('events/a.jpg'));
    }

    public function test_misconfigured_r2_fails_clearly_on_write(): void
    {
        config(['filesystems.media_disk' => 'r2', 'filesystems.disks.r2.key' => null]);
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('R2_ACCESS_KEY_ID');
        Media::store(UploadedFile::fake()->image('a.jpg'), 'events');
    }

    public function test_r2_driver_sends_no_acl_header(): void
    {
        $disk = app('filesystem')->build([
            'driver' => 'r2', 'key' => 'k', 'secret' => 's', 'region' => 'auto', 'bucket' => 'b',
            'endpoint' => 'https://acc.r2.cloudflarestorage.com', 'use_path_style_endpoint' => true, 'throw' => true,
        ]);
        $seen = null;
        $client = $disk->getClient();
        $client->getHandlerList()->setHandler(function ($command, $request) use (&$seen) {
            $seen = $request;

            return \GuzzleHttp\Promise\Create::promiseFor(new \Aws\Result([]));
        });
        $disk->put('events/a.jpg', 'x');

        $this->assertNotNull($seen);
        $this->assertFalse($seen->hasHeader('x-amz-acl'), 'R2 ne gère pas les ACL');
    }

    public function test_promoter_creation_writes_to_media_disk_not_public(): void
    {
        $response = $this->actingAs(User::find(2))->post(route('promoteur.events.store'), $this->payload([
            'image' => UploadedFile::fake()->image('cover.jpg'), 'description' => 'Une soirée',
        ]));
        $response->assertSessionHasNoErrors();

        $event = Event::firstOrFail();
        Storage::disk('media_test')->assertExists($event->image);
        $this->assertSame([], Storage::disk('public')->allFiles());
        $this->assertMatchesRegularExpression('#^events/[A-Za-z0-9]{40}\.jpg$#', $event->image);
    }

    public function test_replace_and_delete_use_media_disk(): void
    {
        $event = $this->readyEvent(2, ['image' => 'events/old.jpg']);
        Storage::disk('media_test')->put('events/old.jpg', 'ancien');
        Storage::disk('public')->put('events/old.jpg', 'local-intact');

        $payload = $this->payload(['image' => UploadedFile::fake()->image('new.jpg'), 'description' => 'Une soirée']);
        unset($payload['action']);
        $payload['title'] = 'Prêt';
        $this->actingAs(User::find(2))->put(route('promoteur.events.update', $event), $payload)->assertSessionHasNoErrors();

        $event->refresh();
        $this->assertNotSame('events/old.jpg', $event->image);
        Storage::disk('media_test')->assertExists($event->image);
        Storage::disk('media_test')->assertMissing('events/old.jpg');
        Storage::disk('public')->assertExists('events/old.jpg'); // le disque local n'est pas touché

        $this->actingAs(User::find(2))->delete(route('promoteur.events.destroy', $event));
        Storage::disk('media_test')->assertMissing($event->image);
    }

    public function test_duplication_copies_on_media_disk(): void
    {
        $event = $this->readyEvent(2, ['status' => 'published', 'image' => 'events/original.jpg']);
        Storage::disk('media_test')->put('events/original.jpg', 'contenu');

        $this->actingAs(User::find(2))->post(route('promoteur.events.duplicate', $event));

        $copy = Event::where('id', '!=', $event->id)->firstOrFail();
        $this->assertNotSame('events/original.jpg', $copy->image);
        $this->assertSame('contenu', Storage::disk('media_test')->get($copy->image));
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_promoter_avatar_uses_media_disk(): void
    {
        $user = User::find(2);
        $this->actingAs($user);
        $request = Request::create('/x', 'POST', [], [], ['avatar' => UploadedFile::fake()->image('me.jpg')]);
        $request->setUserResolver(fn () => $user);

        $json = app(\App\Http\Controllers\Promoteur\ProfileController::class)->updateAvatar($request)->getData(true);

        $files = Storage::disk('media_test')->allFiles('avatars');
        $this->assertCount(1, $files);
        $this->assertSame([], Storage::disk('public')->allFiles());
        $this->assertSame(Media::url($files[0]), $json['avatar_url']);
    }

    public function test_guardrail_no_hardcoded_public_disk_or_storage_url_in_app_and_views(): void
    {
        $offenders = [];
        foreach ([base_path('app'), resource_path('views')] as $root) {
            $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS));
            foreach ($it as $file) {
                if (! $file->isFile() || ! preg_match('/\.php$/', $file->getFilename())) {
                    continue;
                }
                $path = str_replace(base_path().'/', '', $file->getPathname());
                if ($path === 'app/Support/Media.php' || $path === 'app/Console/Commands/MigrateMediaToDisk.php') {
                    continue;
                }
                $code = file_get_contents($file->getPathname());
                if (preg_match('/disk\(\s*[\'"]public[\'"]\s*\)|,\s*[\'"]public[\'"]\s*\)|Storage::url\(|Storage::exists\(/', $code)) {
                    $offenders[] = $path;
                }
            }
        }

        $this->assertSame([], $offenders, 'Utiliser App\Support\Media (disque média) au lieu de \'public\' ou Storage::url().');
    }
}
