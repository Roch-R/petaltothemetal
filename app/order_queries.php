<?php
declare(strict_types=1);
function customer_orders(PDO $db,int $customerId): array
{
    $query=$db->prepare('SELECT o.*,o.recipient_phone AS phone,o.delivery_address AS address FROM orders o WHERE o.user_id=? ORDER BY o.created_at DESC,o.id DESC');
    $query->execute([$customerId]);return $query->fetchAll();
}
function customer_order(PDO $db,int $customerId,int $orderId): ?array
{
    $query=$db->prepare('SELECT o.*,o.recipient_phone AS phone,o.delivery_address AS address FROM orders o WHERE o.id=? AND o.user_id=?');$query->execute([$orderId,$customerId]);$order=$query->fetch();
    if (!$order) return null;
    $query=$db->prepare('SELECT * FROM order_items WHERE order_id=? ORDER BY id');$query->execute([$orderId]);$order['items']=$query->fetchAll();
    $query=$db->prepare('SELECT * FROM payments WHERE order_id=?');$query->execute([$orderId]);$order['payment']=$query->fetch() ?: null;
    return $order;
}
