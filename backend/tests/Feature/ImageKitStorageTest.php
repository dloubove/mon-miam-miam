<?php

namespace Tests\Feature;

use App\Services\ImageService;
use Illuminate\Http\Client\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ImageKitStorageTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'filesystems.image_disk' => 'imagekit',
            'services.imagekit.private_key' => 'private_test_key',
            'services.imagekit.url_endpoint' => 'https://ik.imagekit.io/test-account',
        ]);
    }

    public function test_it_uploads_files_and_stores_a_compact_imagekit_reference(): void
    {
        Http::fake([
            'upload.imagekit.io/api/v1/files/upload' => Http::response([
                'fileId' => 'file-123',
                'filePath' => '/articles/photo.webp',
            ]),
        ]);

        $reference = app(ImageService::class)->enregistrer(
            UploadedFile::fake()->create('photo.jpg', 10, 'image/jpeg'),
            'articles',
        );

        $this->assertSame('ik:file-123:/articles/photo.webp', $reference);
        Http::assertSent(fn (Request $request) =>
            $request->url() === 'https://upload.imagekit.io/api/v1/files/upload'
            && $request->hasHeader('Authorization')
        );
    }

    public function test_it_builds_imagekit_urls_from_saved_references(): void
    {
        $url = app(ImageService::class)->url('ik:file-123:/articles/photo.webp');

        $this->assertSame('https://ik.imagekit.io/test-account/articles/photo.webp', $url);
    }

    public function test_it_deletes_imagekit_assets_by_file_id(): void
    {
        Http::fake([
            'api.imagekit.io/v1/files/file-123' => Http::response([], 204),
        ]);

        app(ImageService::class)->supprimer('ik:file-123:/articles/photo.webp');

        Http::assertSent(fn (Request $request) =>
            $request->method() === 'DELETE'
            && $request->url() === 'https://api.imagekit.io/v1/files/file-123'
        );
    }
}
