<?php

namespace HasanHawary\MediaManager\Tests\Feature;

use HasanHawary\MediaManager\Facades\Media;
use HasanHawary\MediaManager\Tests\TestCase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class ChunkResolverTest extends TestCase
{
    public function test_chunk_upload_uses_configured_media_manager_disk(): void
    {
        $result = Media::on('other')->chunk([
            'user_id' => 1,
            'file_name' => 'sample.txt',
            'chunk_number' => 1,
            'chunk_file' => UploadedFile::fake()->createWithContent('chunk', 'hello'),
            'directory' => 'assembled',
            'is_final' => true,
        ]);

        $this->assertIsString($result);
        Storage::disk('other')->assertExists($result);
        Storage::disk('media')->assertMissing($result);
    }

    public function test_chunk_merge_uses_numeric_order_for_double_digit_chunks(): void
    {
        $parts = ['01-', '02-', '03-', '04-', '05-', '06-', '07-', '08-', '09-', '10'];
        $result = null;

        foreach ($parts as $index => $content) {
            $chunkNumber = $index + 1;
            $result = Media::chunk([
                'user_id' => 7,
                'file_name' => 'ordered.txt',
                'chunk_number' => $chunkNumber,
                'chunk_file' => UploadedFile::fake()->createWithContent("chunk-{$chunkNumber}", $content),
                'directory' => 'assembled',
                'is_final' => $chunkNumber === count($parts),
            ]);
        }

        $this->assertIsString($result);
        $this->assertSame(implode('', $parts), Storage::disk('media')->get($result));
        Storage::disk('media')->assertMissing('chunks/7/ordered/1');
    }

    public function test_chunk_file_name_is_sanitized_before_storage_paths_are_built(): void
    {
        $result = Media::chunk([
            'user_id' => 2,
            'file_name' => '../safe.txt',
            'chunk_number' => 1,
            'chunk_file' => UploadedFile::fake()->createWithContent('chunk', 'safe'),
            'directory' => 'assembled',
            'is_final' => true,
        ]);

        $this->assertIsString($result);
        $this->assertStringEndsWith('_safe.txt', $result);
        $this->assertStringNotContainsString('..', $result);
    }
}
