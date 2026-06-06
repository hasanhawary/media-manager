<?php

namespace HasanHawary\MediaManager\Tests\Feature;

use HasanHawary\MediaManager\Facades\Media;
use HasanHawary\MediaManager\Tests\TestCase;
use Illuminate\Support\Facades\Storage;

class InputValidationTest extends TestCase
{
    public function test_empty_disk_name_is_rejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        Media::on('');
    }

    public function test_unsafe_upload_directory_falls_back_to_default_files_directory(): void
    {
        $path = Media::withName('safe.txt')
            ->fallbackExtension('txt')
            ->upload('safe', '../outside');

        $this->assertSame('files/safe.txt', $path);
        Storage::disk('media')->assertExists('files/safe.txt');
        Storage::disk('media')->assertMissing('outside/safe.txt');
    }
}
