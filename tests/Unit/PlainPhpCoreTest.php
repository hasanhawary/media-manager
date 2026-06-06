<?php

namespace HasanHawary\MediaManager\Tests\Unit;

use HasanHawary\MediaManager\Support\FileNameGenerator;
use HasanHawary\MediaManager\Support\PathNormalizer;
use PHPUnit\Framework\TestCase;

class PlainPhpCoreTest extends TestCase
{
    public function test_path_normalizer_and_filename_generator_do_not_need_laravel_container(): void
    {
        $this->assertSame('files', (new PathNormalizer())->directory('../bad'));
        $this->assertMatchesRegularExpression(
            '/^[0-9a-f-]{36}\.txt$/',
            FileNameGenerator::generate('txt', 'uuid')
        );
    }
}
