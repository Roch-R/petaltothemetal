<?php
declare(strict_types=1);

// Copy to local.php and enter your actual local database credentials.
// This file contains examples only. local.php is excluded from Git.
return [
    'database' => [
        'host' => '127.0.0.1',
        'port' => 3306,
        'database' => 'petal_to_the_metal',
        'username' => 'YOUR_DB_USER',
        'password' => 'YOUR_DB_PASSWORD',
    ],
    'test_database' => [
        'host' => '127.0.0.1',
        'port' => 3306,
        'database' => 'petal_to_the_metal_test',
        'username' => 'YOUR_DB_USER',
        'password' => 'YOUR_DB_PASSWORD',
    ],
];
