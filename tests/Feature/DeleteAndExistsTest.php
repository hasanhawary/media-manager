<?php

namespace HasanHawary\MediaManager\Tests\Feature;

use HasanHawary\MediaManager\Facades\Media;
use HasanHawary\MediaManager\Tests\TestCase;
use Illuminate\Support\Facades\Storage;

class DeleteAndExistsTest extends TestCase
{
    public function test_exists_resolves_disk_urls_before_checking_storage(): void
    {
        Storage::disk('media')->put('files/avatar.jpg', 'avatar');

        $url = Storage::disk('media')->url('files/avatar.jpg');

        $this->assertTrue(Media::exists($url));
        $this->assertFalse(Media::exists('https://example.com/files/avatar.jpg'));
    }

    public function test_safe_delete_moves_normalized_disk_url_path_to_trash(): void
    {
        Storage::disk('media')->put('files/avatar.jpg', 'avatar');

        $url = Storage::disk('media')->url('files/avatar.jpg');

        Media::safeDelete($url);

        Storage::disk('media')->assertMissing('files/avatar.jpg');
        Storage::disk('media')->assertExists('trash/avatar.jpg');
    }
}
