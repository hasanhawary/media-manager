<?php

namespace HasanHawary\MediaManager\Tests\Feature;

use HasanHawary\MediaManager\Http\Controllers\ChunkFileController;
use HasanHawary\MediaManager\Support\ChunkResolver;
use HasanHawary\MediaManager\Tests\TestCase;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class ChunkFileControllerTest extends TestCase
{
    public function test_chunk_file_controller_uploads_non_final_chunk(): void
    {
        $response = (new ChunkFileController())(
            $this->chunkRequest('video.mp4', 1, 'part-one', false),
            new ChunkResolver('media')
        );

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame([
            'status' => true,
            'code' => 200,
            'message' => 'Chunk uploaded successfully.',
            'data' => [
                'path' => '1',
                'is_final' => false,
            ],
        ], $response->getData(true));

        Storage::disk('media')->assertExists('chunks/99/video/1');
    }

    public function test_chunk_file_controller_assembles_final_chunk(): void
    {
        $controller = new ChunkFileController();
        $resolver = new ChunkResolver('media');

        $controller($this->chunkRequest('video.mp4', 1, 'first-', false), $resolver);
        $response = $controller($this->chunkRequest('video.mp4', 2, 'second', true), $resolver);

        $this->assertSame(201, $response->getStatusCode());
        $payload = $response->getData(true);

        $this->assertTrue($payload['status']);
        $this->assertTrue($payload['data']['is_final']);
        $this->assertSame('first-second', Storage::disk('media')->get($payload['data']['path']));
    }

    public function test_chunk_file_controller_returns_validation_error_payload(): void
    {
        $request = Request::create('/media-manager/chunk-file', 'POST', [
            'file_name' => 'missing-file.txt',
            'chunk_number' => 1,
            'user_id' => 99,
        ]);

        $response = (new ChunkFileController())($request, new ChunkResolver('media'));

        $this->assertSame(422, $response->getStatusCode());
        $this->assertFalse($response->getData(true)['status']);
    }

    private function chunkRequest(string $fileName, int $chunkNumber, string $content, bool $isFinal): Request
    {
        return Request::create('/media-manager/chunk-file', 'POST', [
            'file_name' => $fileName,
            'chunk_number' => $chunkNumber,
            'user_id' => 99,
            'directory' => 'assembled',
            'is_final' => $isFinal ? '1' : '0',
        ], [], [
            'chunk_file' => UploadedFile::fake()->createWithContent("chunk-{$chunkNumber}", $content),
        ]);
    }
}
