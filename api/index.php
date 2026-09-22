<?php

use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

if (getenv('VERCEL')) {
    $runtimeDirectories = [
        '/tmp/campusflow/views',
        '/tmp/campusflow/cache',
        '/tmp/campusflow/sessions',
        '/tmp/campusflow/storage',
    ];

    foreach ($runtimeDirectories as $directory) {
        if (! is_dir($directory)) {
            mkdir($directory, 0775, true);
        }
    }

    $runtimeDefaults = [
        'APP_CONFIG_CACHE' => '/tmp/campusflow/cache/config.php',
        'APP_EVENTS_CACHE' => '/tmp/campusflow/cache/events.php',
        'APP_PACKAGES_CACHE' => '/tmp/campusflow/cache/packages.php',
        'APP_ROUTES_CACHE' => '/tmp/campusflow/cache/routes.php',
        'APP_SERVICES_CACHE' => '/tmp/campusflow/cache/services.php',
        'CACHE_STORE' => 'array',
        'LOCAL_FILESYSTEM_ROOT' => '/tmp/campusflow/storage',
        'LOG_CHANNEL' => 'stderr',
        'SESSION_DRIVER' => 'cookie',
        'VIEW_COMPILED_PATH' => '/tmp/campusflow/views',
    ];

    foreach ($runtimeDefaults as $key => $value) {
        if (getenv($key) === false) {
            putenv("{$key}={$value}");
            $_ENV[$key] = $value;
            $_SERVER[$key] = $value;
        }
    }
}

require __DIR__.'/../vendor/autoload.php';

$app = require_once __DIR__.'/../bootstrap/app.php';

$app->handleRequest(Request::capture());
