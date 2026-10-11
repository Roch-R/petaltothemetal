<?php
declare(strict_types=1);

function admin_product_image_choices(): array
{
    return [
        'assets/images/rose-bouquet.jpg' => 'Rose bouquet',
        'assets/images/tulip-bouquet.jpg' => 'Tulip bouquet',
        'assets/images/mixed-bouquet.jpg' => 'Mixed bouquet',
        'assets/images/hero-flowers.jpg' => 'Seasonal flowers',
    ];
}

function product_image_allowed(string $path): bool
{
    return array_key_exists($path, admin_product_image_choices());
}
