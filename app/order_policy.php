<?php
declare(strict_types=1);
function allowed_order_transitions(string $method, string $status): array
{
    if (!in_array($method,['delivery','pickup'],true)) return [];
    $ready = $method==='delivery' ? 'out_for_delivery' : 'ready_for_pickup';
    return ['pending'=>['preparing','cancelled'],'preparing'=>[$ready,'cancelled'],$ready=>['completed'],'completed'=>[],'cancelled'=>[]][$status] ?? [];
}
