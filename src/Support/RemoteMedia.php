<?php

namespace HasanHawary\MediaManager\Support;

class RemoteMedia
{
    public function __construct(
        public readonly string $content,
        public readonly ?string $mime,
        public readonly ?string $extension,
        public readonly ?string $originalName,
    ) {
    }
}
