<?php

namespace HasanHawary\MediaManager\Handlers;

use HasanHawary\MediaManager\Exceptions\UnsupportedTypeException;

class ZipHandler
{
    public function __construct()
    {
        throw new UnsupportedTypeException('ZipHandler is not implemented.');
    }
}
