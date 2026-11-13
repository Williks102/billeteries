<?php

namespace App\Support\Filesystem;

use Cloudinary\Api\Exception\NotFound;
use Cloudinary\Cloudinary;
use Psr\Http\Client\ClientInterface;

/** Passerelle basée sur le SDK officiel cloudinary/cloudinary_php (envois toujours signés avec la clé API et le secret). */
class SdkCloudinaryGateway implements CloudinaryGateway
{
    private ?Cloudinary $client = null;

    public function __construct(private readonly CloudinaryConfig $config, private readonly ?ClientInterface $httpClient = null) {}

    private function client(): Cloudinary
    {
        if ($this->client === null) {
            $this->client = new Cloudinary([
                'cloud' => [
                    'cloud_name' => $this->config->cloudName,
                    'api_key' => $this->config->apiKey,
                    'api_secret' => $this->config->apiSecret,
                ],
                'url' => ['secure' => true],
            ]);
            if ($this->httpClient !== null) {
                $this->client->setHttpClient($this->httpClient);
            }
        }

        return $this->client;
    }

    public function upload(string $source, string $publicId): void
    {
        // Pas de preset : upload() du SDK signe la requête avec api_key + api_secret.
        $this->client()->uploadApi()->upload($source, [
            'public_id' => $publicId,
            'resource_type' => 'image',
            'type' => 'upload',
            'overwrite' => true,
            'unique_filename' => false,
            'use_filename' => false,
        ]);
    }

    public function destroy(string $publicId): void
    {
        $this->client()->uploadApi()->destroy($publicId, [
            'resource_type' => 'image',
            'type' => 'upload',
            'invalidate' => true,
        ]);
    }

    public function exists(string $publicId): bool
    {
        try {
            $this->client()->adminApi()->asset($publicId, ['resource_type' => 'image', 'type' => 'upload']);

            return true;
        } catch (NotFound) {
            return false;
        }
    }
}
