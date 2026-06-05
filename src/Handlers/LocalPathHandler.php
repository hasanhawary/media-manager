<?php

namespace HasanHawary\MediaManager\Handlers;

use HasanHawary\MediaManager\BaseHandler;
use HasanHawary\MediaManager\Contracts\HandlerInterface;
use HasanHawary\MediaManager\Support\MediaStorageWriter;

class LocalPathHandler extends BaseHandler implements HandlerInterface
{
    public function __construct(protected string $sourcePath, protected bool $isCopy = true)
    {
    }

    public function store(string $path, array $options): ?string
    {
        if (!is_file($this->sourcePath)) {
            return null;
        }

        $ext = pathinfo($this->sourcePath, PATHINFO_EXTENSION) ?: ($options['fallbackExtension'] ?? 'jpg');
        $stream = fopen($this->sourcePath, 'rb');
        if ($stream === false) {
            return null;
        }

        $storedPath = (new MediaStorageWriter())->putStream(
            $path,
            $stream,
            $options,
            $ext,
            basename($this->sourcePath)
        );

        if (is_resource($stream)) {
            fclose($stream);
        }

        if ($storedPath && ! $this->isCopy) {
            @unlink($this->sourcePath);
        }

        return $storedPath;
    }
}
