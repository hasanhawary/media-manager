<?php

namespace HasanHawary\MediaManager\Handlers;

use HasanHawary\MediaManager\BaseHandler;
use HasanHawary\MediaManager\Contracts\HandlerInterface;
use HasanHawary\MediaManager\Support\FileNameGenerator;
use Illuminate\Support\Facades\Storage;

class ContentHandler extends BaseHandler implements HandlerInterface
{
    public function __construct(protected string $content, protected string $extension = 'png')
    {
    }

    public function store(string $path, array $options): ?string
    {
        $ext = $options['fallbackExtension'] ?? 'jpg';
        $filename = $this->filename($options['namingMode'], $options['customName'], $ext);
        $fullPath = $this->path($path) . '/' . $filename;

        $stored = Storage::disk($options['disk'])
            ->put(
                $fullPath,
                $this->content,
                $this->options($options['visibility'] ?? null)
            );

        return $stored ? $fullPath : null;
    }

    private function filename($namingMode, $customName, $extension): string
    {
        return FileNameGenerator::generate($extension, $namingMode, $customName);
    }
}
