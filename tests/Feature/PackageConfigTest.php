<?php

namespace HasanHawary\MediaManager\Tests\Feature;

use HasanHawary\MediaManager\Facades\Media;
use HasanHawary\MediaManager\Tests\TestCase;
use Illuminate\Support\Facades\Storage;

class PackageConfigTest extends TestCase
{
    public function test_media_manager_reads_package_defaults_from_config(): void
    {
        config()->set('media-manager.path', 'configured');
        config()->set('media-manager.fallback_extension', 'txt');

        $path = Media::withName('from-config.txt')->upload('configured content');

        $this->assertSame('configured/from-config.txt', $path);
        Storage::disk('media')->assertExists('configured/from-config.txt');
    }
}
