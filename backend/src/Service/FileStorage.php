<?php

declare(strict_types=1);

namespace App\Service;

use Aws\S3\S3Client;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final class FileStorage
{
    private S3Client $client;

    public function __construct(
        #[Autowire('%env(S3_ENDPOINT)%')] string $endpoint,
        #[Autowire('%env(S3_BUCKET)%')] private string $bucket,
        #[Autowire('%env(S3_ACCESS_KEY)%')] string $accessKey,
        #[Autowire('%env(S3_SECRET_KEY)%')] string $secretKey,
    ) {
        $this->client = new S3Client([
            'version' => 'latest',
            'region' => 'us-east-1',
            'endpoint' => $endpoint,
            'use_path_style_endpoint' => true,
            'request_checksum_calculation' => 'when_required',
            'credentials' => ['key' => $accessKey, 'secret' => $secretKey],
            'http' => ['connect_timeout' => 5, 'timeout' => 240],
        ]);
    }

    public function put(string $key, string $path, int $size, string $mime): void
    {
        $this->client->putObject([
            'Bucket' => $this->bucket,
            'Key' => $key,
            'SourceFile' => $path,
            'ContentLength' => $size,
            'ContentType' => $mime,
        ]);
    }

    public function delete(string $key): void
    {
        $this->client->deleteObject(['Bucket' => $this->bucket, 'Key' => $key]);
    }
}
