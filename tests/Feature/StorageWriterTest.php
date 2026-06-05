<?php

namespace HasanHawary\MediaManager\Tests\Feature;

use HasanHawary\MediaManager\Handlers\Base64Handler;
use HasanHawary\MediaManager\Handlers\ContentHandler;
use HasanHawary\MediaManager\Handlers\LocalPathHandler;
use HasanHawary\MediaManager\Handlers\UploadedFileHandler;
use HasanHawary\MediaManager\Tests\TestCase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class StorageWriterTest extends TestCase
{
    public function test_handlers_share_path_naming_and_visibility_storage_rules(): void
    {
        $options = [
            'disk' => 'media',
            'visibility' => 'public',
            'fallbackExtension' => 'txt',
            'namingMode' => 'custom',
            'customName' => 'content.txt',
        ];

        $path = (new ContentHandler('content'))->store('/uploads/', $options);

        $this->assertSame('uploads/content.txt', $path);
        Storage::disk('media')->assertExists('uploads/content.txt');
    }

    public function test_all_primary_handlers_write_through_the_same_contract_shape(): void
    {
        $sourcePath = sys_get_temp_dir().'/media-manager-source.txt';
        file_put_contents($sourcePath, 'local');

        $cases = [
            'base64.txt' => new Base64Handler(base64_encode('base64')),
            'local.txt' => new LocalPathHandler($sourcePath),
            'uploaded.txt' => new UploadedFileHandler(UploadedFile::fake()->createWithContent('uploaded.txt', 'uploaded')),
        ];

        foreach ($cases as $name => $handler) {
            $path = $handler->store('uploads', [
                'disk' => 'media',
                'visibility' => 'public',
                'fallbackExtension' => 'txt',
                'namingMode' => 'custom',
                'customName' => $name,
            ]);

            $this->assertSame("uploads/{$name}", $path);
            Storage::disk('media')->assertExists("uploads/{$name}");
        }

        @unlink($sourcePath);
    }

    public function test_local_path_can_keep_original_filename(): void
    {
        $sourcePath = sys_get_temp_dir().'/original-report.txt';
        file_put_contents($sourcePath, 'report');

        $path = (new LocalPathHandler($sourcePath))->store('uploads', [
            'disk' => 'media',
            'visibility' => 'public',
            'fallbackExtension' => 'txt',
            'namingMode' => 'original',
            'customName' => null,
        ]);

        $this->assertSame('uploads/original-report.txt', $path);
        Storage::disk('media')->assertExists('uploads/original-report.txt');

        @unlink($sourcePath);
    }

    public function test_custom_filenames_are_sanitized_by_the_storage_writer(): void
    {
        $path = (new ContentHandler('safe'))->store('uploads', [
            'disk' => 'media',
            'visibility' => 'public',
            'fallbackExtension' => 'txt',
            'namingMode' => 'custom',
            'customName' => '../unsafe.txt',
        ]);

        $this->assertSame('uploads/unsafe.txt', $path);
        Storage::disk('media')->assertExists('uploads/unsafe.txt');
        Storage::disk('media')->assertMissing('unsafe.txt');
    }
}
