<?php

namespace HasanHawary\MediaManager\Handlers;

use HasanHawary\MediaManager\BaseHandler;
use HasanHawary\MediaManager\Contracts\HandlerInterface;
use HasanHawary\MediaManager\Support\MediaStorageWriter;
use Illuminate\Http\UploadedFile;

class UploadedFileHandler extends BaseHandler implements HandlerInterface
{
    public function __construct(protected UploadedFile $file)
    {
    }

    public function store(string $path, array $options): ?string
    {
        return (new MediaStorageWriter())->putUploadedFile($path, $this->file, $options);
    }
}
