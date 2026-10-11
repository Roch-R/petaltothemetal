<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$failed = false;
$phpReady = PHP_VERSION_ID >= 80000;
echo ($phpReady ? 'PASS' : 'FAIL') . ' PHP: ' . PHP_VERSION . PHP_EOL;
$failed = $failed || !$phpReady;

$pdoReady = class_exists('PDO') && in_array('mysql', PDO::getAvailableDrivers(), true);
echo ($pdoReady ? 'PASS' : 'FAIL') . ' PDO MySQL driver' . PHP_EOL;
$failed = $failed || !$pdoReady;

$localPath = dirname(__DIR__) . '/config/local.php';
if (!is_file($localPath)) {
    echo 'SKIP database connections: copy config/local.example.php to config/local.php and configure it.' . PHP_EOL;
    exit($failed ? 1 : 0);
}

require_once dirname(__DIR__) . '/app/database.php';
try {
    $settings = require $localPath;
    if (!is_array($settings)) {
        throw new RuntimeException('Invalid configuration.');
    }

    foreach (['database', 'test_database'] as $key) {
        try {
            $connectionSettings = $settings[$key] ?? [];
            if ($key === 'test_database'
                && !preg_match('/_test\z/', $connectionSettings['database'] ?? '')) {
                throw new RuntimeException('Test connection must point to a database ending in _test.');
            }
            if ($key === 'test_database'
                && ($connectionSettings['database'] ?? null) === ($settings['database']['database'] ?? null)) {
                throw new RuntimeException('Application and test databases must be different.');
            }
            $connection = connect_database($connectionSettings);
            $version = $connection->query('SELECT VERSION()')->fetchColumn();
            echo 'PASS ' . $key . ' connection; server: ' . $version . PHP_EOL;
        } catch (Throwable $error) {
            $failed = true;
            // Do not print exceptions containing connection details or credentials.
            echo 'FAIL ' . $key . ' connection. Check XAMPP, credentials, port, and database name.' . PHP_EOL;
        }
    }
} catch (Throwable $error) {
    $failed = true;
    echo 'FAIL local configuration. Compare it with config/local.example.php.' . PHP_EOL;
}

exit($failed ? 1 : 0);
