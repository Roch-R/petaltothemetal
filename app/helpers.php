<?php
declare(strict_types=1);

function e($value): string
{
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function url(string $path = '/'): string
{
    if (preg_match('/[\r\n]/', $path) || strpos($path, '://') !== false || strpos($path, '//') === 0) {
        throw new InvalidArgumentException('Only application routes are allowed.');
    }
    return rtrim(app_config()['base_path'], '/') . '/' . ltrim($path, '/');
}

function safe_return_route($candidate): string
{
    $routes = ['/cart.php', '/checkout.php', '/orders.php', '/shop.php'];
    if (is_string($candidate) && (in_array($candidate, $routes, true)
        || preg_match('/\A\/product\.php\?id=[1-9][0-9]{0,9}\z/', $candidate))) {
        return $candidate;
    }
    return '/shop.php';
}

function redirect(string $path): void
{
    header('Location: ' . url($path), true, 303);
    exit;
}

function flash(string $type, string $message): void
{
    $_SESSION['_flash'][] = ['type' => $type, 'message' => $message];
}

function take_flash(): array
{
    $messages = $_SESSION['_flash'] ?? [];
    unset($_SESSION['_flash']);
    return $messages;
}

function abort_request(int $status, string $message): void
{
    http_response_code($status);
    header('Content-Type: text/html; charset=UTF-8');
    echo '<!doctype html><html lang="en"><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>' . $status . ' | Petal to the Metal</title><body><main><h1>' . e($status) . '</h1><p>' . e($message) . '</p><a href="' . e(url('/')) . '">Back to the shop</a></main></body></html>';
    exit;
}
