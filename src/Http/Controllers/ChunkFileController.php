<?php

namespace HasanHawary\MediaManager\Http\Controllers;

use HasanHawary\MediaManager\Support\ChunkResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ChunkFileController
{
    public function __invoke(Request $request, ChunkResolver $chunkResolver): JsonResponse
    {
        try {
            $isFinal = filter_var($request->input('is_final', false), FILTER_VALIDATE_BOOLEAN);
            $path = $chunkResolver->upload([
                'file_name' => $request->input('file_name'),
                'chunk_number' => $request->input('chunk_number'),
                'chunk_file' => $request->file('chunk_file'),
                'user_id' => $request->input('user_id'),
                'directory' => $request->input('directory'),
            ], $isFinal);
        } catch (\InvalidArgumentException $exception) {
            return $this->error($exception->getMessage(), 422);
        } catch (\RuntimeException $exception) {
            return $this->error($exception->getMessage(), 409);
        }

        return new JsonResponse([
            'status' => true,
            'code' => $isFinal ? 201 : 200,
            'message' => $isFinal ? 'File assembled successfully.' : 'Chunk uploaded successfully.',
            'data' => [
                'path' => $path,
                'is_final' => $isFinal,
            ],
        ], $isFinal ? 201 : 200);
    }

    private function error(string $message, int $status): JsonResponse
    {
        return new JsonResponse([
            'status' => false,
            'code' => $status,
            'message' => $message,
            'data' => null,
        ], $status);
    }
}
