<?php

// Serve with: php -S 127.0.0.1:8010 -t public tests/Browser/router.php
// acceptance.cjs supplies a temporary DB and uses the built Vite manifest.
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$public = realpath(dirname(__DIR__, 2).'/public');
$file = realpath($public.$path);
if ($path !== '/' && $file && str_starts_with($file, $public.'/') && is_file($file)) {
    return false;
}
require dirname(__DIR__, 2).'/vendor/autoload.php';
$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$app->make(Illuminate\Foundation\Vite::class)->useHotFile(sys_get_temp_dir().'/slacknovo-acceptance-unused-hot');
$app->handleRequest(Illuminate\Http\Request::capture());
