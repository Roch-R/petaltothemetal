<?php
declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/app/init.php';
$admin = require_admin();

function admin_id($value, bool $optional = false): ?int
{
    if ($optional && ($value === null || $value === '')) return null;
    $id = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if ($id === false) abort_request(400, 'A valid record ID is required.');
    return $id;
}

function admin_label(string $status): string { return ucwords(str_replace('_', ' ', $status)); }
function admin_error(array $errors, string $field): void
{
    if (isset($errors[$field])) echo '<span class="field-error" id="error-' . e($field) . '">' . e($errors[$field]) . '</span>';
}
function admin_old(array $input): array
{
    return array_filter($input, static fn($value) => is_string($value) || is_int($value));
}
