<?php

namespace HasanHawary\MediaManager\Tests\Feature;

use HasanHawary\MediaManager\Facades\Media;
use HasanHawary\MediaManager\MediaManager;
use HasanHawary\MediaManager\Tests\TestCase;
use Illuminate\Support\Facades\Storage;

class MediaManagerStateTest extends TestCase
{
    public function test_container_resolves_fresh_media_manager_instances(): void
    {
        $first = $this->app->make(MediaManager::class);
        $second = $this->app->make(MediaManager::class);

        $this->assertNotSame($first, $second);
    }

    public function test_facade_does_not_leak_state_between_static_calls(): void
    {
        Media::on('other');

        $path = Media::fromContent('fresh content')
            ->withName('fresh.txt')
            ->store();

        $this->assertSame('files/fresh.txt', $path);
        Storage::disk('media')->assertExists('files/fresh.txt');
        Storage::disk('other')->assertMissing('files/fresh.txt');
    }
}
