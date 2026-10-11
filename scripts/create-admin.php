<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require dirname(__DIR__) . '/app/bootstrap.php';
$options = getopt('', ['email:','name:']);
$email = strtolower(trim($options['email'] ?? ''));
$name = trim($options['name'] ?? '');
if (!filter_var($email,FILTER_VALIDATE_EMAIL) || strlen($email)>190 || $name==='' || strlen($name)>100) {
    fwrite(STDERR,"Usage: php scripts/create-admin.php --email=you@example.test --name=Administrator\n"); exit(1);
}
fwrite(STDERR,"Enter a password (minimum 8 characters): ");
$password = rtrim((string) fgets(STDIN), "\r\n");
if (strlen($password)<8 || strlen($password)>72) { fwrite(STDERR,"Password must contain 8 to 72 characters.\n"); exit(1); }
try {
    $statement = db()->prepare('INSERT INTO users (name,email,password_hash,role) VALUES (?,?,?,?)');
    $statement->execute([$name,$email,password_hash($password,PASSWORD_DEFAULT),'admin']);
    echo 'Administrator created for ' . $email . PHP_EOL;
} catch(Throwable $error) {
    fwrite(STDERR,"Unable to create administrator. Check the schema, connection, and whether the email is already registered.\n"); exit(1);
}
