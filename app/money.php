<?php
declare(strict_types=1);

function money_to_minor(string $amount): int
{
    if (!preg_match('/\A(0|[1-9][0-9]{0,7})(?:\.([0-9]{1,2}))?\z/', $amount, $match)) {
        throw new InvalidArgumentException('Invalid monetary amount.');
    }
    return (int) $match[1] * 100 + (int) str_pad($match[2] ?? '', 2, '0');
}

function minor_to_money(int $minor): string
{
    if ($minor < 0 || $minor > 9999999999) {
        throw new InvalidArgumentException('Monetary amount exceeds the supported range.');
    }
    return intdiv($minor, 100) . '.' . str_pad((string) ($minor % 100), 2, '0', STR_PAD_LEFT);
}

function format_money($amount): string
{
    $minor = is_int($amount) ? $amount : money_to_minor((string) $amount);
    // Display a recoverable bag even if its current total exceeds order storage.
    if ($minor < 0) throw new InvalidArgumentException('Invalid monetary amount.');
    $whole = (string) intdiv($minor, 100);
    $fraction = str_pad((string) ($minor % 100), 2, '0', STR_PAD_LEFT);
    $whole = preg_replace('/\B(?=(\d{3})+(?!\d))/', ',', $whole);
    $symbol = function_exists('app_config') ? (app_config()['currency_symbol'] ?? '₱') : '₱';
    return $symbol . $whole . '.' . $fraction;
}
