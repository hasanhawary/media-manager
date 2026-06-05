<?php

namespace HasanHawary\MediaManager\Support;

class PathNormalizer
{
    public function directory(string $path, string $fallback = 'files'): string
    {
        $path = trim(str_replace('\\', '/', $path), '/');

        if ($path === '' || str_contains($path, '..')) {
            return $fallback;
        }

        return $path;
    }
}
