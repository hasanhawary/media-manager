<?php

namespace HasanHawary\MediaManager\Tests;

use HasanHawary\MediaManager\MediaManagerServiceProvider;
use Illuminate\Config\Repository;
use Illuminate\Container\Container;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Filesystem\FilesystemManager;
use Illuminate\Support\Facades\Facade;
use PHPUnit\Framework\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected Container $app;

    protected function setUp(): void
    {
        parent::setUp();

        $this->app = new Container();
        Container::setInstance($this->app);
        (new Filesystem())->deleteDirectory(sys_get_temp_dir().'/media-manager-tests');
        $this->app->instance('config', new Repository([
            'filesystems.default' => 'media',
            'filesystems.disks.media' => [
                'driver' => 'local',
                'root' => sys_get_temp_dir().'/media-manager-tests/media',
                'url' => 'http://localhost/storage',
                'visibility' => 'public',
            ],
            'filesystems.disks.other' => [
                'driver' => 'local',
                'root' => sys_get_temp_dir().'/media-manager-tests/other',
                'url' => 'http://localhost/other-storage',
                'visibility' => 'public',
            ],
        ]));
        $this->app->instance('files', new Filesystem());
        $this->app->singleton('filesystem', fn ($app) => new FilesystemManager($app));

        Facade::clearResolvedInstances();
        Facade::setFacadeApplication($this->app);

        (new MediaManagerServiceProvider($this->app))->register();
    }

    protected function tearDown(): void
    {
        Facade::clearResolvedInstances();
        Facade::setFacadeApplication(null);
        Container::setInstance(null);
        (new Filesystem())->deleteDirectory(sys_get_temp_dir().'/media-manager-tests');

        parent::tearDown();
    }
}
