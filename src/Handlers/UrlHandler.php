<?php

namespace HasanHawary\MediaManager\Handlers;

use HasanHawary\MediaManager\BaseHandler;
use HasanHawary\MediaManager\Contracts\HandlerInterface;
use HasanHawary\MediaManager\Support\FileNameGenerator;
use HasanHawary\MediaManager\Support\MediaStorageWriter;
use HasanHawary\MediaManager\Support\RemoteMediaFetcher;

class UrlHandler extends BaseHandler implements HandlerInterface
{
    public function __construct(protected string $url, protected bool $storedLocal = false)
    {
    }

    public function store(string $path, array $options): ?string
    {
        $fetcher = new RemoteMediaFetcher();

        if (! $this->storedLocal) {
            return $fetcher->isValidUrl($this->url) ? $this->url : null;
        }

        $media = $fetcher->fetch($this->url);
        if (! $media) {
            return null;
        }

        $extension = $media->extension
            ?? FileNameGenerator::determineExtension($media->mime, $options['fallbackExtension'] ?? 'jpg');

        return (new MediaStorageWriter())->putContent(
            $path,
            $media->content,
            $options,
            $extension,
            $media->originalName
        );
    }
}
