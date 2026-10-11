<?php
declare(strict_types=1);

function connect_database(array $settings): PDO
{
    foreach (['host', 'port', 'database', 'username', 'password'] as $key) {
        if (!array_key_exists($key, $settings)) {
            throw new InvalidArgumentException('Database configuration is incomplete.');
        }
    }

    // Prevent configuration values from injecting extra PDO DSN parameters.
    if (!is_string($settings['host']) || !preg_match('/\A[a-zA-Z0-9._:-]+\z/', $settings['host'])
        || !is_string($settings['database']) || !preg_match('/\A[a-zA-Z0-9_]+\z/', $settings['database'])
        || !is_int($settings['port']) || $settings['port'] < 1 || $settings['port'] > 65535
        || !is_string($settings['username']) || !is_string($settings['password'])) {
        throw new InvalidArgumentException('Database configuration contains invalid values.');
    }

    $dsn = sprintf(
        'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
        $settings['host'],
        $settings['port'],
        $settings['database']
    );

    return new PDO($dsn, $settings['username'], $settings['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
}
