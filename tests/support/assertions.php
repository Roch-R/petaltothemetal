<?php
declare(strict_types=1);
function assert_same($expected, $actual, string $message = ''): void
{
    if ($expected !== $actual) {
        throw new RuntimeException(($message ? $message . ': ' : '') . 'Expected ' . var_export($expected, true) . ', got ' . var_export($actual, true));
    }
}
function assert_true(bool $condition, string $message = 'Condition was false.'): void
{
    if (!$condition) { throw new RuntimeException($message); }
}
function assert_throws(callable $action, string $class = Throwable::class): Throwable
{
    try { $action(); } catch (Throwable $error) {
        if (!$error instanceof $class) { throw new RuntimeException('Expected ' . $class . ', got ' . get_class($error)); }
        return $error;
    }
    throw new RuntimeException('Expected exception ' . $class . '.');
}
