<?php

$loader = require __DIR__.'/../vendor/autoload.php';

$loader->addPsr4('HasanHawary\\MediaManager\\', __DIR__.'/../src', true);
$loader->addPsr4('HasanHawary\\MediaManager\\Tests\\', __DIR__, true);

if (! function_exists('config')) {
    function config($key = null, $default = null)
    {
        if ($key === null) {
            return app('config');
        }

        return app('config')->get($key, $default);
    }
}

if (! function_exists('app')) {
    function app($abstract = null, array $parameters = [])
    {
        $container = \Illuminate\Container\Container::getInstance();

        if ($abstract === null) {
            return $container;
        }

        return $container->make($abstract, $parameters);
    }
}

if (! function_exists('storage_path')) {
    function storage_path($path = ''): string
    {
        return sys_get_temp_dir().'/media-manager-tests'.($path ? '/'.ltrim($path, '/') : '');
    }
}
