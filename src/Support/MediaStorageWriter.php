<?php

namespace HasanHawary\MediaManager\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class MediaStorageWriter
{
    public function putContent(string $path, string $content, array $options, ?string $extension = null, ?string $originalName = null): ?string
    {
        $fullPath = $this->fullPath($path, $this->filename($extension ?? $this->fallbackExtension($options), $options, $originalName));

        $stored = Storage::disk($options['disk'])
            ->put($fullPath, $content, $this->visibilityOptions($options));

        return $stored ? $fullPath : null;
    }

    /**
     * @param resource $stream
     */
    public function putStream(string $path, mixed $stream, array $options, ?string $extension = null, ?string $originalName = null): ?string
    {
        $fullPath = $this->fullPath($path, $this->filename($extension ?? $this->fallbackExtension($options), $options, $originalName));

        $stored = Storage::disk($options['disk'])
            ->put($fullPath, $stream, $this->visibilityOptions($options));

        return $stored ? $fullPath : null;
    }

    public function putUploadedFile(string $path, UploadedFile $file, array $options): ?string
    {
        $stored = Storage::disk($options['disk'])
            ->putFileAs(
                $this->normalizePath($path),
                $file,
                $this->safeFileName(FileNameGenerator::generate($file, $options['namingMode'], $options['customName'])),
                $this->visibilityOptions($options)
            );

        return $stored ?: null;
    }

    public function fallbackExtension(array $options): string
    {
        return $options['fallbackExtension'] ?? 'jpg';
    }

    public function fullPath(string $path, string $filename): string
    {
        return $this->normalizePath($path).'/'.$filename;
    }

    public function normalizePath(string $path): string
    {
        return (new PathNormalizer())->directory($path);
    }

    public function visibilityOptions(array $options): array
    {
        return ! empty($options['visibility']) ? ['visibility' => $options['visibility']] : [];
    }

    private function filename(string $extension, array $options, ?string $originalName = null): string
    {
        if (($options['namingMode'] ?? null) === 'original' && $originalName) {
            return $this->safeFileName($originalName);
        }

        return $this->safeFileName(
            FileNameGenerator::generate($extension, $options['namingMode'], $options['customName'])
        );
    }

    private function safeFileName(string $filename): string
    {
        $filename = basename(str_replace('\\', '/', $filename));

        return $filename !== '' && $filename !== '.' && $filename !== '..'
            ? $filename
            : FileNameGenerator::uuid('bin');
    }
}
