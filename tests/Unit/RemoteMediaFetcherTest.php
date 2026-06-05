<?php

namespace HasanHawary\MediaManager\Tests\Unit;

use HasanHawary\MediaManager\Support\RemoteMediaFetcher;
use PHPUnit\Framework\TestCase;

class RemoteMediaFetcherTest extends TestCase
{
    public function test_rejects_private_and_local_urls(): void
    {
        $fetcher = new RemoteMediaFetcher();

        $this->assertFalse($fetcher->isValidUrl('http://127.0.0.1/file.jpg'));
        $this->assertFalse($fetcher->isValidUrl('http://localhost/file.jpg'));
        $this->assertFalse($fetcher->isValidUrl('file:///tmp/file.jpg'));
    }

    public function test_accepts_public_http_urls(): void
    {
        $fetcher = new RemoteMediaFetcher();

        $this->assertTrue($fetcher->isValidUrl('https://example.com/photo.jpg'));
    }
}
