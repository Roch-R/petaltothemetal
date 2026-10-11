<?php
declare(strict_types=1);

// PDO receives credentials as arguments; exclude them from logged stack traces.
ini_set('zend.exception_ignore_args', '1');

require_once __DIR__ . '/database.php';

function app_config(): array
{
    static $config = null;
    if ($config === null) {
        $config = require dirname(__DIR__) . '/config/app.php';
    }
    return $config;
}

function local_config(): array
{
    static $config = null;
    if ($config === null) {
        $path = dirname(__DIR__) . '/config/local.php';
        if (!is_file($path)) {
            throw new RuntimeException('Local database configuration is missing. See docs/setup.md.');
        }
        $config = require $path;
        if (!is_array($config)) {
            throw new RuntimeException('Local database configuration must return an array.');
        }
    }
    return $config;
}

function db(): PDO
{
    static $connection = null;
    if ($connection === null) {
        $connection = connect_database(local_config()['database'] ?? []);
    }
    return $connection;
}

date_default_timezone_set(app_config()['timezone']);
ini_set('display_errors', '0');
ini_set('log_errors', '1');
$logDirectory = dirname(__DIR__) . '/storage/logs';
if (!is_dir($logDirectory)) {
    mkdir($logDirectory, 0775, true);
}
ini_set('error_log', $logDirectory . '/php.log');

if (PHP_SAPI !== 'cli') {
    set_exception_handler(static function (Throwable $error): void {
        error_log((string) $error);
        if (!headers_sent()) {
            http_response_code(500);
            header('Content-Type: text/plain; charset=UTF-8');
        }
        echo 'The shop is temporarily unavailable. Please try again later.';
    });

    if (session_status() === PHP_SESSION_NONE) {
        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');
        session_name(app_config()['session_name']);
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => (app_config()['base_path'] ?: '') . '/',
            'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();
    }
}
