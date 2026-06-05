<?php

namespace HasanHawary\MediaManager;

use HasanHawary\MediaManager\Support\ChunkResolver;
use HasanHawary\MediaManager\Support\MediaStorageWriter;
use HasanHawary\MediaManager\Support\PathNormalizer;
use HasanHawary\MediaManager\Support\RemoteMediaFetcher;
use Illuminate\Support\ServiceProvider;

class MediaManagerServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/media-manager.php', 'media-manager');

        $this->app->bind(MediaManager::class, function ($app) {
            return new MediaManager();
        });

        $this->app->bind(ChunkResolver::class);
        $this->app->bind(MediaStorageWriter::class);
        $this->app->bind(PathNormalizer::class);
        $this->app->bind(RemoteMediaFetcher::class);
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../config/media-manager.php' => $this->configPath('media-manager.php'),
        ], 'media-manager-config');

        if ($this->routesEnabled()) {
            $this->loadRoutesFrom(__DIR__.'/../routes/media-manager.php');
        }
    }

    private function configPath(string $path): string
    {
        if (function_exists('config_path')) {
            return config_path($path);
        }

        return getcwd().DIRECTORY_SEPARATOR.'config'.DIRECTORY_SEPARATOR.$path;
    }

    private function routesEnabled(): bool
    {
        return (bool) ($this->app['config']->get('media-manager.routes.enabled', true));
    }
}
