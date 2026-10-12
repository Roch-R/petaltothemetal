<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$root = dirname(__DIR__);
require_once __DIR__ . '/../app/database.php';
try {
    $path = $root . '/config/local.php';
    if (!is_file($path)) {
        $connection = ['host' => '127.0.0.1', 'port' => 3307,
            'database' => 'petal_to_the_metal', 'username' => 'root', 'password' => ''];
        $test = $connection;
        $test['database'] .= '_test';
        $contents = "<?php\ndeclare(strict_types=1);\nreturn "
            . var_export(['database' => $connection, 'test_database' => $test], true) . ";\n";
        if (file_put_contents($path, $contents) === false) {
            throw new RuntimeException('Could not write config/local.php.');
        }
    }
    $settings = require $path;
    $connection = $settings['database'];
    // Validate the connection fields using the application's DSN validation.
    if (($argv[1] ?? '') === '--settings') {
        if (!in_array($connection['host'], ['localhost', '127.0.0.1'], true)) {
            throw new RuntimeException('This launcher supports local databases only.');
        }
        echo json_encode(['host' => $connection['host'], 'port' => $connection['port']]);
        exit;
    }
    if (($argv[1] ?? '') === '--phpmyadmin') {
        $cfg = ['blowfish_secret' => bin2hex(random_bytes(16)),
            'Servers' => [1 => ['auth_type' => 'config', 'host' => $connection['host'],
                'port' => (string) $connection['port'], 'user' => $connection['username'],
                'password' => $connection['password'], 'AllowNoPassword' => $connection['password'] === '']],
            'TempDir' => $root . '/storage/logs/phpmyadmin/tmp/', 'CheckConfigurationPermissions' => false];
        if (file_put_contents($root . '/storage/logs/phpmyadmin/config.inc.php',
            "<?php\n\$cfg = " . var_export($cfg, true) . ";\n") === false) {
            throw new RuntimeException('Could not configure project phpMyAdmin.');
        }
        exit;
    }
    // Connect through an existing system database so a dropped shop DB can be recreated.
    $serverSettings = $connection;
    $serverSettings['database'] = 'information_schema';
    $server = connect_database($serverSettings);
    foreach (['database', 'test_database'] as $key) {
        if (!isset($settings[$key])) {
            continue;
        }
        $target = $settings[$key];
        $name = $target['database'];
        if (!preg_match('/\A[a-zA-Z0-9_]+\z/', $name)
            || ($key === 'test_database' && (!str_ends_with($name, '_test') || $name === $connection['database']))) {
            throw new RuntimeException('Invalid database name in config/local.php.');
        }
        if ($target['host'] !== $connection['host'] || $target['port'] !== $connection['port']) {
            throw new RuntimeException('Local app and test databases must use the same server.');
        }
        $server->exec("CREATE DATABASE IF NOT EXISTS `$name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $db = connect_database($target);
        $tables = $db->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
        if ($tables === []) {
            $db->exec(file_get_contents($root . '/database/schema.sql'));
            if ($key === 'database') {
                $db->exec(file_get_contents($root . '/database/seed.sql'));
            }
            echo "Created $name" . ($key === 'database' ? ' with sample flowers.' : '.') . PHP_EOL;
        } else {
            $missing = array_diff(['users', 'categories', 'products', 'orders', 'order_items', 'payments'], $tables);
            if ($missing !== []) {
                throw new RuntimeException("Database $name is incomplete (missing " . implode(', ', $missing)
                    . '). Restore a backup, or delete this demo database in phpMyAdmin and rerun run.bat.');
            }
            echo "Using existing $name; saved data kept." . PHP_EOL;
        }
    }
} catch (Throwable $error) {
    // Connection errors can contain sensitive connection details.
    fwrite(STDERR, ($error instanceof PDOException
        ? 'Database setup failed. Check config/local.php credentials and storage/logs/database-start.err.log.'
        : $error->getMessage()) . PHP_EOL);
    exit(1);
}
