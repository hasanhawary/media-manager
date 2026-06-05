<?php

namespace HasanHawary\MediaManager\Tests\Feature;

use HasanHawary\MediaManager\Facades\Media;
use HasanHawary\MediaManager\Support\MediaMeta;
use HasanHawary\MediaManager\Tests\TestCase;
use Illuminate\Support\Facades\Storage;

class MediaMetaTest extends TestCase
{
    public function test_media_meta_is_json_serializable(): void
    {
        Storage::disk('media')->put('docs/report.txt', 'report');

        $meta = Media::meta('docs/report.txt');

        $this->assertInstanceOf(MediaMeta::class, $meta);
        $this->assertJson(json_encode($meta, JSON_THROW_ON_ERROR));
        $this->assertSame('docs/report.txt', $meta->toArray()['path']);
    }

    public function test_media_meta_hash_uses_file_contents(): void
    {
        Storage::disk('media')->put('docs/report.txt', 'report');

        $this->assertSame(md5('report'), Media::meta('docs/report.txt')->hash());
    }
}
