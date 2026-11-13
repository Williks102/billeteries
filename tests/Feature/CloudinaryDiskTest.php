<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\User;
use App\Support\Filesystem\CloudinaryAdapter;
use App\Support\Filesystem\CloudinaryConfig;
use App\Support\Filesystem\CloudinaryGateway;
use App\Support\Filesystem\SdkCloudinaryGateway;
use App\Support\Media;
use Carbon\Carbon;
use GuzzleHttp\Psr7\Response;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use League\Flysystem\Config;
use League\Flysystem\UnableToCopyFile;
use League\Flysystem\UnableToListContents;
use League\Flysystem\UnableToWriteFile;
use PHPUnit\Framework\Attributes\DataProvider;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Tests\Concerns\BuildsEventsTable;
use Tests\Concerns\BuildsOrderSchema;
use Tests\TestCase;

/** Faux client Cloudinary : mémorise les appels, aucun réseau. */
class FakeCloudinaryGateway implements CloudinaryGateway
{
    public array $uploads = [];

    public array $destroyed = [];

    public array $existing = [];

    public function upload(string $source, string $publicId): void
    {
        $this->uploads[] = ['public_id' => $publicId, 'source' => $source, 'bytes' => is_file($source) ? file_get_contents($source) : null];
        $this->existing[$publicId] = true;
    }

    public function destroy(string $publicId): void
    {
        $this->destroyed[] = $publicId;
        unset($this->existing[$publicId]);
    }

    public function exists(string $publicId): bool
    {
        return isset($this->existing[$publicId]);
    }
}

/** Client PSR-18 qui capture les requêtes sans sortir sur Internet. */
class CapturingHttpClient implements ClientInterface
{
    public array $requests = [];

    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        $this->requests[] = $request;

        return new Response(200, ['Content-Type' => 'application/json'], '{"public_id":"x","version":1,"result":"ok"}');
    }
}

class CloudinaryDiskTest extends TestCase
{
    use BuildsEventsTable;
    use BuildsOrderSchema;

    private const SECRET = 'S3cr3tValueDoNotLeak';

    private const URL = 'cloudinary://123456789:'.self::SECRET.'@democloud';

    private FakeCloudinaryGateway $gateway;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-11-12 12:00:00');
        $this->gateway = new FakeCloudinaryGateway;
        $this->app->instance(CloudinaryGateway::class, $this->gateway);
        $this->useCloudinary();
        Storage::fake('public');
        $this->buildOrderSchema();
        $this->buildEventsTable();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function useCloudinary(array $override = []): void
    {
        config(['filesystems.media_disk' => 'cloudinary', 'filesystems.disks.cloudinary' => array_merge([
            'driver' => 'cloudinary', 'cloudinary_url' => self::URL, 'folder' => '', 'transformations' => 'f_auto,q_auto',
            'throw' => true, 'report' => true,
        ], $override)]);
        Storage::forgetDisk('cloudinary');
    }

    private function adapter(array $override = []): CloudinaryAdapter
    {
        $config = CloudinaryConfig::fromDiskConfig(array_merge(config('filesystems.disks.cloudinary'), $override));

        return new CloudinaryAdapter($config, $this->gateway);
    }

    private function jpeg(): string
    {
        $file = UploadedFile::fake()->image('a.jpg', 10, 10);

        return file_get_contents($file->getPathname());
    }

    private function payload(array $override = []): array
    {
        return array_replace_recursive([
            'title' => 'Soirée', 'category_id' => 1, 'event_date' => '2026-11-14', 'event_time' => '20:00',
            'venue' => 'Palais', 'action' => 'draft',
            'ticket_types' => [0 => ['name' => 'Standard', 'price' => '5000', 'quantity_available' => '100', 'max_per_order' => '10']],
        ], $override);
    }

    // ---- URL hors réseau ---------------------------------------------------------------------

    public function test_url_is_built_offline_without_extension_when_f_auto(): void
    {
        $this->assertSame('https://res.cloudinary.com/democloud/image/upload/f_auto,q_auto/events/abc', Media::url('events/abc.jpg'));
        $this->assertSame([], $this->gateway->uploads);
    }

    public function test_url_with_folder_and_custom_transformations(): void
    {
        $this->useCloudinary(['folder' => 'billetterie/prod/', 'transformations' => 'f_auto,q_auto,w_800']);
        $this->assertSame('https://res.cloudinary.com/democloud/image/upload/f_auto,q_auto,w_800/billetterie/prod/events/abc', Media::url('events/abc.png'));
    }

    public function test_url_without_f_auto_keeps_original_extension(): void
    {
        $this->useCloudinary(['transformations' => 'q_auto,w_400']);
        $this->assertSame('https://res.cloudinary.com/democloud/image/upload/q_auto,w_400/events/abc.jpg', Media::url('events/abc.jpg'));
    }

    public function test_empty_transformations_fall_back_to_default(): void
    {
        $this->useCloudinary(['transformations' => '']);
        $this->assertStringContainsString('/image/upload/f_auto,q_auto/', Media::url('events/abc.jpg'));
    }

    public function test_url_never_contains_secret_or_api_key(): void
    {
        $url = Media::url('events/abc.jpg');
        $this->assertStringNotContainsString(self::SECRET, $url);
        $this->assertStringNotContainsString('123456789', $url);
        $this->assertStringStartsWith('https://', $url);
    }

    public function test_path_traversal_is_rejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->adapter()->getUrl('events/../secret.jpg');
    }

    // ---- Adaptateur ---------------------------------------------------------------------------

    public function test_write_uploads_with_public_id_without_extension_and_folder(): void
    {
        $this->adapter(['folder' => 'prod'])->write('events/hash1.jpg', $this->jpeg(), new Config);

        $this->assertCount(1, $this->gateway->uploads);
        $this->assertSame('prod/events/hash1', $this->gateway->uploads[0]['public_id']);
        $this->assertNotEmpty($this->gateway->uploads[0]['bytes']);
    }

    public function test_write_stream_via_laravel_disk_uses_adapter_not_public(): void
    {
        $path = Media::store(UploadedFile::fake()->image('cover.jpg'), 'events');

        $this->assertMatchesRegularExpression('#^events/[A-Za-z0-9]{40}\.jpg$#', $path);
        $this->assertSame('events/'.pathinfo($path, PATHINFO_FILENAME), $this->gateway->uploads[0]['public_id']);
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_non_image_is_refused_by_mime_and_by_extension(): void
    {
        $adapter = $this->adapter();
        try {
            $adapter->write('events/x.jpg', '<?php echo 1;', new Config);
            $this->fail('Un faux JPEG doit être refusé.');
        } catch (UnableToWriteFile) {
        }
        try {
            $adapter->write('events/x.svg', $this->jpeg(), new Config);
            $this->fail('Une extension non image doit être refusée.');
        } catch (UnableToWriteFile) {
        }
        $this->assertSame([], $this->gateway->uploads);
    }

    public function test_delete_destroys_the_right_public_id(): void
    {
        $this->adapter(['folder' => 'prod'])->delete('events/hash1.jpg');
        $this->assertSame(['prod/events/hash1'], $this->gateway->destroyed);
    }

    public function test_copy_creates_a_new_resource_from_the_original_url(): void
    {
        $this->gateway->existing['events/original'] = true;
        $this->adapter()->copy('events/original.jpg', 'events/copie.jpg', new Config);

        $this->assertSame('events/copie', $this->gateway->uploads[0]['public_id']);
        $this->assertSame('https://res.cloudinary.com/democloud/image/upload/events/original', $this->gateway->uploads[0]['source']);

        $this->adapter()->delete('events/original.jpg');
        $this->assertTrue($this->gateway->exists('events/copie'), 'la copie survit à la suppression de l\'original');
    }

    public function test_unsupported_operations_raise_clear_exceptions(): void
    {
        $adapter = $this->adapter();
        $this->expectException(UnableToListContents::class);
        iterator_to_array($adapter->listContents('events', false));
    }

    public function test_copy_to_non_image_destination_is_refused(): void
    {
        $this->expectException(UnableToCopyFile::class);
        $this->adapter()->copy('events/a.jpg', 'events/b.php', new Config);
    }

    public function test_file_exists_uses_gateway(): void
    {
        $this->gateway->existing['events/a'] = true;
        $this->assertTrue($this->adapter()->fileExists('events/a.jpg'));
        $this->assertFalse($this->adapter()->fileExists('events/b.jpg'));
    }

    // ---- Client SDK : requête signée, sans réseau ----------------------------------------------

    public function test_sdk_gateway_sends_signed_requests_without_preset(): void
    {
        $http = new CapturingHttpClient;
        $gateway = new SdkCloudinaryGateway(new CloudinaryConfig('democloud', '123456789', self::SECRET), $http);
        $file = tempnam(sys_get_temp_dir(), 'cld');
        file_put_contents($file, $this->jpeg());

        $gateway->upload($file, 'events/h');
        $gateway->destroy('events/h');
        @unlink($file);

        $this->assertCount(2, $http->requests);
        $upload = $http->requests[0];
        $body = (string) $upload->getBody();
        $this->assertSame('https://api.cloudinary.com/v1_1/democloud/image/upload', (string) $upload->getUri());
        $this->assertStringContainsString('name="signature"', $body);
        $this->assertStringContainsString('name="api_key"', $body);
        $this->assertStringContainsString('events/h', $body);
        $this->assertStringNotContainsString('upload_preset', $body);
        $this->assertStringNotContainsString(self::SECRET, $body, 'le secret ne part jamais sur le réseau');
        $this->assertStringContainsString('/image/destroy', (string) $http->requests[1]->getUri());
        $this->assertStringContainsString('signature=', (string) $http->requests[1]->getBody());
    }

    // ---- Configuration -------------------------------------------------------------------------

    public static function badUrls(): array
    {
        return [
            'absente' => [null],
            'vide' => [''],
            'sans cloud name' => ['cloudinary://123:'.self::SECRET.'@'],
            'mauvais schéma' => ['https://123:'.self::SECRET.'@democloud'],
            'sans secret' => ['cloudinary://123@democloud'],
            'texte libre' => ['n-importe-quoi-'.self::SECRET],
        ];
    }

    #[DataProvider('badUrls')]
    public function test_missing_or_malformed_config_fails_clearly_without_leaking_secret(?string $bad): void
    {
        $this->useCloudinary(['cloudinary_url' => $bad]);
        Log::spy();

        try {
            Media::store(UploadedFile::fake()->image('a.jpg'), 'events');
            $this->fail('Une configuration invalide doit échouer.');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('CLOUDINARY_URL', $e->getMessage());
            $this->assertStringNotContainsString(self::SECRET, $e->getMessage());
        }

        Log::shouldHaveReceived('error')->withArgs(fn ($message) => str_contains($message, 'CLOUDINARY_URL') && ! str_contains($message, self::SECRET));
        $this->assertSame([], $this->gateway->uploads);
    }

    #[DataProvider('badUrls')]
    public function test_url_without_valid_config_is_null_without_network(?string $bad): void
    {
        $this->useCloudinary(['cloudinary_url' => $bad]);
        $this->assertNull(Media::url('events/a.jpg'));
    }

    public function test_url_parsing(): void
    {
        $this->assertSame(
            ['api_key' => '123', 'api_secret' => 'a.b_c-d', 'cloud_name' => 'my-cloud'],
            CloudinaryConfig::parseUrl('cloudinary://123:a.b_c-d@my-cloud')
        );
    }

    public function test_default_media_disk_stays_public_and_secret_is_not_in_repo_files(): void
    {
        $this->assertSame('public', (require base_path('config/filesystems.php'))['media_disk']);
        foreach (['.env.example', 'render.yaml'] as $file) {
            $this->assertDoesNotMatchRegularExpression('#cloudinary://[^<\s]+:[^<\s]+@#', file_get_contents(base_path($file)), $file);
        }
    }

    // ---- Parcours événement avec MEDIA_DISK=cloudinary -----------------------------------------

    public function test_event_create_replace_delete_go_through_the_adapter(): void
    {
        $this->actingAs(User::find(2))->post(route('promoteur.events.store'), $this->payload([
            'image' => UploadedFile::fake()->image('cover.jpg'), 'description' => 'Une soirée',
        ]))->assertSessionHasNoErrors();
        $event = Event::firstOrFail();
        $firstId = 'events/'.pathinfo($event->image, PATHINFO_FILENAME);
        $this->assertSame($firstId, $this->gateway->uploads[0]['public_id']);

        $payload = $this->payload(['image' => UploadedFile::fake()->image('new.jpg'), 'description' => 'Une soirée']);
        unset($payload['action']);
        $payload['title'] = 'Prêt';
        $event->update(['status' => 'draft']);
        $this->actingAs(User::find(2))->put(route('promoteur.events.update', $event), $payload)->assertSessionHasNoErrors();
        $event->refresh();
        $this->assertNotSame($firstId, 'events/'.pathinfo($event->image, PATHINFO_FILENAME));
        $this->assertContains($firstId, $this->gateway->destroyed);

        $this->actingAs(User::find(2))->delete(route('promoteur.events.destroy', $event));
        $this->assertContains('events/'.pathinfo($event->image, PATHINFO_FILENAME), $this->gateway->destroyed);
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_event_duplication_creates_a_new_cloudinary_resource(): void
    {
        $event = $this->readyEvent(2, ['status' => 'published', 'image' => 'events/original.jpg']);
        $this->gateway->existing['events/original'] = true;

        $this->actingAs(User::find(2))->post(route('promoteur.events.duplicate', $event));

        $copy = Event::where('id', '!=', $event->id)->firstOrFail();
        $this->assertNotSame('events/original.jpg', $copy->image);
        $upload = $this->gateway->uploads[0];
        $this->assertSame('events/'.pathinfo($copy->image, PATHINFO_FILENAME), $upload['public_id']);
        $this->assertSame('https://res.cloudinary.com/democloud/image/upload/events/original', $upload['source']);
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    // ---- Commande de reprise ------------------------------------------------------------------

    public function test_migration_command_works_with_cloudinary_is_idempotent_and_deletes_nothing(): void
    {
        Storage::disk('public')->put('events/local1.jpg', $this->jpeg());

        $this->artisan('media:migrate-to-r2', ['--dry-run' => true])->assertSuccessful();
        $this->assertSame([], $this->gateway->uploads, 'la simulation n\'écrit rien');

        $this->artisan('media:migrate')->assertSuccessful(); // alias
        $this->assertSame(['events/local1'], array_column($this->gateway->uploads, 'public_id'));

        $this->artisan('media:migrate-to-r2')->assertSuccessful();
        $this->assertCount(1, $this->gateway->uploads, 'deuxième passage : rien de recopié');
        Storage::disk('public')->assertExists('events/local1.jpg');
    }

    public function test_migration_command_refuses_local_media_disk(): void
    {
        config(['filesystems.media_disk' => 'public']);
        $this->artisan('media:migrate-to-r2')->assertFailed();
    }
}
