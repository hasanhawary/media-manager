<?php

namespace HasanHawary\MediaManager\Handlers;

use HasanHawary\MediaManager\Exceptions\UnsupportedTypeException;

class ApiHandler
{
    public function __construct()
    {
        throw new UnsupportedTypeException('ApiHandler is not implemented.');
    }
}
