<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require dirname(__DIR__, 2) . '/app/init.php';
$emails = json_decode(stream_get_contents(STDIN), true);
if (!is_array($emails) || count($emails)>5) { fwrite(STDERR,"Invalid cleanup manifest.\n"); exit(1); }
foreach ($emails as $email) {
    if (!is_string($email) || !preg_match('/\Aqa-[a-f0-9]+-[12]@example\.test\z/', $email)) { fwrite(STDERR,"Cleanup is restricted to the exact QA test accounts.\n"); exit(1); }
}
$connection = db(); $connection->beginTransaction();
try {
    foreach($emails as $email) {
        $query=$connection->prepare('SELECT id FROM users WHERE email=? AND role=? FOR UPDATE'); $query->execute([$email,'customer']); $id=$query->fetchColumn();
        if(!$id) { continue; }
        $query=$connection->prepare('SELECT id,status FROM orders WHERE user_id=? ORDER BY id FOR UPDATE'); $query->execute([$id]);
        foreach($query->fetchAll() as $order) {
            if($order['status']!=='cancelled') {
                $items=$connection->prepare('SELECT product_id,SUM(quantity) AS quantity FROM order_items WHERE order_id=? GROUP BY product_id ORDER BY product_id'); $items->execute([$order['id']]);
                foreach($items->fetchAll() as $item) {
                    $lock=$connection->prepare('SELECT id FROM products WHERE id=? FOR UPDATE'); $lock->execute([$item['product_id']]);
                    $update=$connection->prepare('UPDATE products SET stock_quantity=stock_quantity+? WHERE id=?'); $update->execute([$item['quantity'],$item['product_id']]);
                }
            }
            foreach(['payments','order_items'] as $table) { $delete=$connection->prepare('DELETE FROM '.$table.' WHERE order_id=?'); $delete->execute([$order['id']]); }
            $delete=$connection->prepare('DELETE FROM orders WHERE id=?'); $delete->execute([$order['id']]);
        }
        $delete=$connection->prepare('DELETE FROM users WHERE id=?'); $delete->execute([$id]);
    }
    $connection->commit(); echo "PASS QA accounts/orders removed and stock restored.\n";
} catch(Throwable $error) { if($connection->inTransaction()) { $connection->rollBack(); } fwrite(STDERR,"QA cleanup failed.\n"); exit(1); }
