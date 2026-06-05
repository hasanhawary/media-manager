<?php

namespace HasanHawary\MediaManager\Handlers;

use HasanHawary\MediaManager\BaseHandler;
use HasanHawary\MediaManager\Contracts\HandlerInterface;
use HasanHawary\MediaManager\Support\MediaStorageWriter;

class ContentHandler extends BaseHandler implements HandlerInterface
{
    public function __construct(protected string $content, protected string $extension = 'png')
    {
    }

    public function store(string $path, array $options): ?string
    {
        return (new MediaStorageWriter())->putContent(
            $path,
            $this->content,
            $options,
            $options['fallbackExtension'] ?? $this->extension
        );
    }
}
