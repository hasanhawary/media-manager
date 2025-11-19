<?php

namespace HasanHawary\MediaManager\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ChunkResolver
{
    /**
     * Handle chunk upload
     */
    public function upload($data, bool $is_final): false|string
    {
        $this->validateChunkData($data);

        // resolve user_id (request > auth > fail)
        $userId = $data['user_id']
            ?? auth()->id()
            ?? throw new \InvalidArgumentException("user_id is required");

        // filename without extension
        $fileBaseName = pathinfo($data['file_name'], PATHINFO_FILENAME);

        // where chunks will be stored
        $chunkDir = "chunks/{$userId}/{$fileBaseName}";
        $chunkNumber = (int) $data['chunk_number'];

        // First chunk → create fresh directory
        if (Storage::exists($chunkDir) && $chunkNumber === 1) {
            Storage::deleteDirectory($chunkDir);
        }

        if (!Storage::exists($chunkDir)) {
            Storage::makeDirectory($chunkDir);
        }

        // Validate sequence
        $existingChunks = Storage::files($chunkDir);
        $expectedNext = count($existingChunks) + 1;

        if ($chunkNumber !== $expectedNext) {
            throw new \RuntimeException(
                "Unexpected chunk_number: got {$chunkNumber}, expected {$expectedNext}."
            );
        }

        // Store chunk
        Storage::putFileAs($chunkDir, $data['chunk_file'], $chunkNumber);

        // If last chunk → merge
        return $is_final
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
        // directory to save final file
        $directory = $data['directory'] ?? 'uploads';

        if (!Storage::exists($directory)) {
            Storage::makeDirectory($directory);
        }

        $fileName = $data['file_name'];

        // unique name: (XXXX)_filename.ext
        $uniquePrefix = Str::limit(strrev(time()), 4, '');
        $finalPath = "{$directory}/({$uniquePrefix})_{$fileName}";

        $finalAbsolutePath = Storage::path($finalPath);

        $handle = fopen($finalAbsolutePath, 'ab');

        $files = Storage::files($chunkDir);
        sort($files);

        foreach ($files as $file) {
            fwrite($handle, Storage::get($file));
            Storage::delete($file);
        }

        fclose($handle);

        Storage::deleteDirectory($chunkDir);

        return $finalPath;
    }
}
