<?php

namespace HasanHawary\MediaManager\Tests\Feature;

use HasanHawary\MediaManager\Facades\Media;
use HasanHawary\MediaManager\Tests\TestCase;
use Illuminate\Support\Facades\Storage;

class SourceDetectionTest extends TestCase
{
    public function test_short_ambiguous_strings_are_stored_as_raw_content(): void
    {
        $path = Media::withName('raw.txt')
            ->fallbackExtension('txt')
            ->upload('test', 'docs');

        $this->assertSame('docs/raw.txt', $path);
        $this->assertSame('test', Storage::disk('media')->get($path));
    }

    public function test_plain_base64_strings_are_still_detected_when_unambiguous(): void
    {
        $content = 'this is long enough to be unambiguous';
        $path = Media::withName('decoded.txt')
            ->fallbackExtension('txt')
            ->upload(base64_encode($content), 'docs');

        $this->assertSame('docs/decoded.txt', $path);
        $this->assertSame($content, Storage::disk('media')->get($path));
    }

    public function test_zero_string_is_not_treated_as_empty_upload_input(): void
    {
        $path = Media::withName('zero.txt')
            ->fallbackExtension('txt')
            ->upload('0', 'docs');

        $this->assertSame('docs/zero.txt', $path);
        $this->assertSame('0', Storage::disk('media')->get($path));
    }
}
