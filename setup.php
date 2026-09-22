<?php
// Cross-platform local setup. Run after `composer install`.
if (!file_exists(__DIR__.'/vendor/autoload.php')) { fwrite(STDERR, "Run composer install first.\n"); exit(1); }
chdir(__DIR__);
if (!file_exists('.env')) copy('.env.example', '.env');
foreach (['bootstrap/cache', 'storage/framework/cache/data', 'storage/framework/sessions', 'storage/framework/views', 'storage/logs'] as $dir) {
    if (!is_dir($dir)) mkdir($dir, 0775, true);
}
if (!file_exists('database/database.sqlite')) touch('database/database.sqlite');
$commands = ['config:clear'];
if (!preg_match('/^APP_KEY=.+$/m', file_get_contents('.env'))) $commands[] = 'key:generate';
$commands[] = 'migrate --seed';
foreach ($commands as $command) {
    passthru(escapeshellarg(PHP_BINARY).' artisan '.$command, $status);
    if ($status !== 0) exit($status);
}
echo "\nReady! Run: php artisan serve\nDemo: demo@campusflow.test / CampusFlow123!\n";
