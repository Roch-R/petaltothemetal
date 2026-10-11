<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

// Reproduce the installed PHP default before the application bootstrap loads.
ini_set('zend.exception_ignore_args', '0');
require dirname(__DIR__) . '/app/bootstrap.php';

function synthetic_connection_failure(string $password): void
{
    throw new RuntimeException('Synthetic connection failure.');
}

try {
    synthetic_connection_failure('FAKE_PASSWORD');
} catch (Throwable $error) {
    $logEntry = (string) $error;
    if (strpos($logEntry, 'FAKE_PASSWORD') !== false) {
        fwrite(STDERR, 'FAIL exception trace includes a connection password.' . PHP_EOL);
        exit(1);
    }
    if (strpos($logEntry, 'Synthetic connection failure.') === false) {
        fwrite(STDERR, 'FAIL exception diagnostics are missing.' . PHP_EOL);
        exit(1);
    }
    echo 'PASS exception diagnostics omit function arguments.' . PHP_EOL;
    exit(0);
}

fwrite(STDERR, 'FAIL synthetic exception was not produced.' . PHP_EOL);
exit(1);
