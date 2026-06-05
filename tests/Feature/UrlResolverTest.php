<?php

namespace HasanHawary\MediaManager\Tests\Feature;

use HasanHawary\MediaManager\Facades\Media;
use HasanHawary\MediaManager\Tests\TestCase;
use Illuminate\Support\Facades\Storage;

class UrlResolverTest extends TestCase
{
    public function test_array_url_resolution_returns_later_valid_item_when_earlier_item_is_missing(): void
    {
        Storage::disk('media')->put('files/exists.jpg', 'image');

        $url = Media::url(['files/missing.jpg', 'files/exists.jpg']);

        $this->assertSame(Storage::disk('media')->url('files/exists.jpg'), $url);
    }
}
