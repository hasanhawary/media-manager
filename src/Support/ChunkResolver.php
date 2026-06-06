<?php

namespace HasanHawary\MediaManager\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ChunkResolver
{
    public function __construct(protected ?string $disk = null)
    {
        $this->disk ??= $this->config('media-manager.disk')
            ?? $this->config('filesystems.default')
            ?? 'local';
    }

    /**
     * Handle chunk upload
     */
    public function upload(array $data, bool $isFinal): false|string
    {
        $this->validateChunkData($data);
        $storage = Storage::disk($this->disk);

        // resolve user_id (request > auth > fail)
        $userId = $data['user_id']
            ?? $this->authenticatedUserId()
            ?? throw new \InvalidArgumentException("user_id is required");

        // filename without extension
        $fileBaseName = pathinfo($this->safeFileName($data['file_name']), PATHINFO_FILENAME);

        // where chunks will be stored
        $chunkRoot = (new PathNormalizer())->directory(
            (string) $this->config('media-manager.chunks.directory', 'chunks'),
            'chunks'
        );
        $chunkDir = "{$chunkRoot}/{$userId}/{$fileBaseName}";
        $chunkNumber = (int) $data['chunk_number'];

        // First chunk → create fresh directory
        if ($storage->exists($chunkDir) && $chunkNumber === 1) {
            $storage->deleteDirectory($chunkDir);
        }

        if (! $storage->exists($chunkDir)) {
            $storage->makeDirectory($chunkDir);
        }

        // Validate sequence
        $existingChunks = $storage->files($chunkDir);
        $expectedNext = count($existingChunks) + 1;

        if ($chunkNumber !== $expectedNext) {
            throw new \RuntimeException(
                "Unexpected chunk_number: got {$chunkNumber}, expected {$expectedNext}."
            );
        }

        // Store chunk
        $storage->putFileAs($chunkDir, $data['chunk_file'], (string) $chunkNumber);

        // If last chunk → merge
        return $isFinal
            ? $this->combineChunks($chunkDir, $data)
            : (string) $chunkNumber;
    }

    /**
     * Validate chunk input data
     */
    protected function validateChunkData($data): void
    {
        $required = ['file_name', 'chunk_number', 'chunk_file'];

        foreach ($required as $key) {
            if (!array_key_exists($key, $data)) {
                throw new \InvalidArgumentException("Missing required field: $key");
            }
        }

        if (!Str::contains($data['file_name'], '.')) {
            throw new \InvalidArgumentException("Invalid file_name: extension missing");
        }

        if (!is_numeric($data['chunk_number']) || $data['chunk_number'] < 1) {
            throw new \InvalidArgumentException("Invalid chunk_number");
        }

        if (!$data['chunk_file'] instanceof UploadedFile) {
            throw new \InvalidArgumentException(
                "chunk_file must be an instance of UploadedFile"
            );
        }
    }

    /**
     * Merge chunks into final file
     */
    public function combineChunks(string $chunkDir, array $data): string
    {
        $storage = Storage::disk($this->disk);

        // directory to save final file
        $directory = (new PathNormalizer())->directory(
            $data['directory'] ?? (string) $this->config('media-manager.chunks.uploads_directory', 'uploads'),
            'uploads'
        );

        if (! $storage->exists($directory)) {
            $storage->makeDirectory($directory);
        }

        $fileName = $this->safeFileName($data['file_name']);

        // unique name: (XXXX)_filename.ext
        $uniquePrefix = Str::limit(strrev(time()), 4, '');
        $finalPath = "{$directory}/({$uniquePrefix})_{$fileName}";

        $finalAbsolutePath = $storage->path($finalPath);

        $handle = fopen($finalAbsolutePath, 'ab');
        if ($handle === false) {
            throw new \RuntimeException("Unable to open final chunk file for writing: {$finalPath}");
        }

        $files = $storage->files($chunkDir);
        usort(
            $files,
            fn (string $a, string $b): int => (int) basename($a) <=> (int) basename($b)
        );

        foreach ($files as $file) {
            $source = $storage->readStream($file);
            if ($source === null || $source === false) {
                fclose($handle);
                throw new \RuntimeException("Unable to read chunk file: {$file}");
            }

            stream_copy_to_stream($source, $handle);
            fclose($source);
            $storage->delete($file);
        }

        fclose($handle);

        $storage->deleteDirectory($chunkDir);

        return $finalPath;
    }

    protected function safeFileName(string $fileName): string
    {
        $fileName = basename(str_replace('\\', '/', $fileName));

        if ($fileName === '' || $fileName === '.' || $fileName === '..') {
            throw new \InvalidArgumentException('Invalid file_name');
        }

        return $fileName;
    }

    private function config(string $key, mixed $default = null): mixed
    {
        return function_exists('config') ? config($key, $default) : $default;
    }

    private function authenticatedUserId(): mixed
    {
        return function_exists('auth') ? auth()->id() : null;
    }
}
