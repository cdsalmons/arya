<?php

error_reporting(E_ALL);
date_default_timezone_set(ini_get('date.timezone') ?: 'UTC');

require __DIR__ . '/../vendor/autoload.php';

$serverCommand = sprintf(
    '%s -S %s:%d %s >/dev/null 2>&1 & echo $!',
    defined('PHP_BINARY') ? PHP_BINARY : 'php',
    WEB_SERVER_HOST,
    WEB_SERVER_PORT,
    WEB_SERVER_ROUTER
);

// Start the web server and store the process ID so we can kill it when we're finished
$output = [];
exec($serverCommand, $output);
$pid = (int) $output[0];

printf(
    '%s[%s] Integration server started on %s:%d (pid: %d)%s',
    PHP_EOL,
    date('r'),
    WEB_SERVER_HOST,
    WEB_SERVER_PORT,
    $pid,
    PHP_EOL . PHP_EOL
);

// Kill the web server when the process ends
register_shutdown_function(function() use ($pid) {
    printf(
        '%s[%s] Killing integration server (pid: %d)%s',
        PHP_EOL,
        date('r'),
        $pid,
        PHP_EOL . PHP_EOL
    );
    exec('kill ' . $pid);
});
