<?php

use HasanHawary\MediaManager\Http\Controllers\ChunkFileController;
use Illuminate\Support\Facades\Route;

$prefix = config('media-manager.routes.prefix', 'media-manager');
$middleware = config('media-manager.routes.middleware', []);
$name = config('media-manager.routes.name', 'media-manager.');

Route::prefix($prefix)
    ->middleware($middleware)
    ->name($name)
    ->group(function (): void {
        Route::post('chunk-file', ChunkFileController::class)->name('chunk-file');
    });
