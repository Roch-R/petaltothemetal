<?php
declare(strict_types=1);
function test_db(): PDO
{
    static $connection = null;
    if ($connection === null) {
        $settings = local_config();
        $test = $settings['test_database'] ?? [];
        if (!isset($test['database']) || !preg_match('/_test\z/', $test['database'])
            || $test['database'] === ($settings['database']['database'] ?? '')) {
            throw new RuntimeException('Refusing to test against a non-test database.');
        }
        $connection = connect_database($test);
    }
    return $connection;
}
