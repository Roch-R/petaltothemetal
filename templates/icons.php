<?php
function ui_icon(string $name): string
{
    $paths = [
        'bag' => '<path d="M6 7h12l1 14H5L6 7Z"/><path d="M9 7V5a3 3 0 0 1 6 0v2"/>',
        'user' => '<circle cx="12" cy="8" r="3"/><path d="M5 21v-2a7 7 0 0 1 14 0v2"/>',
        'chevron' => '<path d="m6 9 6 6 6-6"/>',
        'orders' => '<rect x="5" y="3" width="14" height="18" rx="2"/><path d="M9 8h6M9 12h6M9 16h4"/>',
        'logout' => '<path d="M9 5H4v14h5M14 8l4 4-4 4M8 12h10"/>',
        'menu' => '<path d="M4 6h16M4 12h16M4 18h16"/>',
        'flower' => '<path d="M12 2v20M2 12h20M5 5l14 14M5 19 14-14"/>',
        'check' => '<path d="m5 12 4 4L19 6"/>',
    ];
    return '<svg class="ui-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">' . ($paths[$name] ?? '') . '</svg>';
}
