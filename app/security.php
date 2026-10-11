<?php
declare(strict_types=1);

function require_post(): void
{
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
        header('Allow: POST');
        abort_request(405, 'This action requires a form submission.');
    }
}

function csrf_token(): string
{
    if (!isset($_SESSION['_csrf'])) {
        $_SESSION['_csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['_csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">';
}

function verify_csrf(?string $token = null): void
{
    $token = $token ?? ($_POST['_csrf'] ?? null);
    if (!is_string($token) || !isset($_SESSION['_csrf']) || !hash_equals($_SESSION['_csrf'], $token)) {
        abort_request(403, 'Your form has expired. Please reload the page and try again.');
    }
}
