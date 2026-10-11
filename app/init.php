<?php
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';
foreach (['helpers','security','money','auth','catalog','cart','checkout_validation','order_policy','order_service','order_queries','media','admin_catalog'] as $service) {
    $servicePath = __DIR__ . '/' . $service . '.php';
    if (is_file($servicePath)) {
        require_once $servicePath;
    }
}
