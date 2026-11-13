<?php

namespace Tests\Feature;

use App\Http\Middleware\SecurityHeaders;
use App\Support\Media;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

/**
 * Les images du disque média (R2, CDN) doivent être autorisées par la politique de sécurité (CSP) :
 * sinon le navigateur les bloque alors qu'elles sont bien stockées.
 */
class SecurityHeadersImagesTest extends TestCase
{
    private function imgSrc(): string
    {
        $response = (new SecurityHeaders)->handle(Request::create('/'), fn () => new Response('ok'));
        $csp = (string) $response->headers->get('Content-Security-Policy');
        preg_match('/img-src ([^;]+);/', $csp, $m);

        return $m[1] ?? '';
    }

    public function test_default_local_disk_adds_no_extra_origin(): void
    {
        config(['filesystems.media_disk' => 'public']);

        $this->assertNull(Media::publicOrigin());
        $this->assertSame("'self' data: blob: https://res.cloudinary.com", $this->imgSrc());
    }

    public function test_r2_public_origin_is_allowed_in_img_src(): void
    {
        config([
            'filesystems.media_disk' => 'r2',
            'filesystems.disks.r2.url' => 'https://pub-abc123.r2.dev',
            'filesystems.disks.r2.key' => 'k', 'filesystems.disks.r2.secret' => 's',
            'filesystems.disks.r2.bucket' => 'b', 'filesystems.disks.r2.endpoint' => 'https://acc.r2.cloudflarestorage.com',
        ]);
        Storage::forgetDisk('r2');

        $this->assertSame('https://pub-abc123.r2.dev', Media::publicOrigin());
        $this->assertStringContainsString('https://pub-abc123.r2.dev', $this->imgSrc());
    }

    public function test_remote_disk_without_public_url_adds_nothing_and_does_not_break(): void
    {
        config(['filesystems.media_disk' => 'r2', 'filesystems.disks.r2.url' => null]);
        Storage::forgetDisk('r2');

        $this->assertNull(Media::publicOrigin());
        $this->assertSame("'self' data: blob: https://res.cloudinary.com", $this->imgSrc());
    }

    public function test_non_https_origin_is_never_added(): void
    {
        config(['filesystems.media_disk' => 'r2', 'filesystems.disks.r2.url' => 'http://insecure.example']);
        Storage::forgetDisk('r2');

        $this->assertNull(Media::publicOrigin());
    }
}
