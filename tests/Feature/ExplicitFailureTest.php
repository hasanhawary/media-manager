<?php

namespace HasanHawary\MediaManager\Tests\Feature;

use HasanHawary\MediaManager\Exceptions\NoHandlerDefinedException;
use HasanHawary\MediaManager\Exceptions\UnsupportedTypeException;
use HasanHawary\MediaManager\Facades\Media;
use HasanHawary\MediaManager\Handlers\ApiHandler;
use HasanHawary\MediaManager\Handlers\ZipHandler;
use HasanHawary\MediaManager\Tests\TestCase;

class ExplicitFailureTest extends TestCase
{
    public function test_store_without_source_throws_explicit_exception(): void
    {
        $this->expectException(NoHandlerDefinedException::class);

        Media::store();
    }

    public function test_unsupported_source_type_throws_explicit_exception(): void
    {
        $this->expectException(UnsupportedTypeException::class);

        Media::from(['not' => 'a supported source']);
    }

    public function test_invalid_naming_strategy_throws_explicit_exception(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        Media::generateName('snowflake');
    }

    public function test_placeholder_handlers_fail_explicitly(): void
    {
        $this->expectException(UnsupportedTypeException::class);

        new ApiHandler();
    }

    public function test_zip_placeholder_handler_fails_explicitly(): void
    {
        $this->expectException(UnsupportedTypeException::class);

        new ZipHandler();
    }
}
