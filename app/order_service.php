<?php
declare(strict_types=1);

function existing_checkout_order(PDO $db,int $customerId,string $key): ?int
{
    $query=$db->prepare('SELECT id,user_id FROM orders WHERE checkout_key=?');$query->execute([$key]);$order=$query->fetch();
    if (!$order) return null;
    if ((int)$order['user_id']!==$customerId) throw new ValidationException(['checkout'=>'This checkout belongs to another account. Start a new checkout.']);
    return (int)$order['id'];
}

function place_order(PDO $db,int $customerId,array $cart,array $details,string $checkoutKey,array $appConfig): int
{
    if (!preg_match('/\A[a-f0-9]{64}\z/',$checkoutKey)) throw new ValidationException(['checkout'=>'Start a new checkout before completing demo payment.']);
    $existing=existing_checkout_order($db,$customerId,$checkoutKey);
    if ($existing!==null) return $existing;
    $cart=validate_cart_quantities($cart);
    $today=new DateTimeImmutable('today',new DateTimeZone($appConfig['timezone'] ?? 'Asia/Singapore'));
    $input=$details;
    $input['phone']=$details['phone'] ?? $details['recipient_phone'] ?? '';
    $input['address']=$details['address'] ?? $details['delivery_address'] ?? '';
    $normalized=validate_checkout($input,$today);
    $prices=$details['expected_prices'] ?? [];
    if (!is_array($prices)) throw new ValidationException(['checkout'=>'Please review your cart prices again.']);
    $db->beginTransaction();
    try {
        $query=$db->prepare('SELECT id,role FROM users WHERE id=? FOR UPDATE');$query->execute([$customerId]);$customer=$query->fetch();
        if (!$customer || $customer['role']!=='customer') throw new ValidationException(['account'=>'A customer account is required to order.']);
        // The customer row serializes repeat submissions. A locking lookup of an
        // absent checkout key would gap-lock the orders index and deadlock with
        // another customer's insert while that customer waits for product stock.
        // This is the first consistent read after acquiring the customer lock.
        $existing=existing_checkout_order($db,$customerId,$checkoutKey);
        if ($existing!==null) {$db->commit();return $existing;}
        $products=[];$subtotal=0;
        foreach ($cart as $id=>$quantity) {
            $query=$db->prepare('SELECT * FROM products WHERE id=? FOR UPDATE');$query->execute([$id]);$product=$query->fetch();
            if (!$product || !(int)$product['is_active']) throw new ValidationException(['cart'=>'An arrangement is no longer available. Please update your cart.']);
            if ((int)$product['stock_quantity']<$quantity) throw new ValidationException(['cart'=>'There is not enough stock for '.$product['name'].'. Please update your cart.']);
            $price=money_to_minor($product['price']);
            if (!isset($prices[$id]) || !is_string($prices[$id]) || money_to_minor($prices[$id])!==$price) throw new ValidationException(['price'=>'A price changed. Please return to checkout and review your total again.']);
            $subtotal+=$price*$quantity;$products[$id]=$product;
        }
        $fee=money_to_minor((string)($appConfig[$normalized['fulfillment_method']==='delivery'?'delivery_fee':'pickup_fee'] ?? '0.00'));
        if (isset($details['expected_fee']) && money_to_minor((string)$details['expected_fee'])!==$fee) throw new ValidationException(['price'=>'The fulfillment fee changed. Please review checkout again.']);
        $total=validate_order_total($subtotal,$fee);
        $query=$db->prepare('INSERT INTO orders (user_id,order_reference,checkout_key,fulfillment_method,scheduled_date,recipient_name,recipient_phone,delivery_address,gift_message,subtotal,fulfillment_fee,total,status) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)');
        $query->execute([$customerId,'PTM-'.date('Ymd').'-'.strtoupper(bin2hex(random_bytes(6))),$checkoutKey,$normalized['fulfillment_method'],$normalized['scheduled_date'],$normalized['recipient_name'],$normalized['phone'],$normalized['address'],$normalized['gift_message'],minor_to_money($subtotal),minor_to_money($fee),minor_to_money($total),'pending']);
        $orderId=(int)$db->lastInsertId();
        $item=$db->prepare('INSERT INTO order_items (order_id,product_id,product_name,unit_price,quantity) VALUES (?,?,?,?,?)');
        $stock=$db->prepare('UPDATE products SET stock_quantity=stock_quantity-? WHERE id=?');
        foreach ($cart as $id=>$quantity) {$item->execute([$orderId,$id,$products[$id]['name'],$products[$id]['price'],$quantity]);$stock->execute([$quantity,$id]);}
        $query=$db->prepare('INSERT INTO payments (order_id,demo_reference,status,amount) VALUES (?,?,?,?)');
        $query->execute([$orderId,'DEMO-'.strtoupper(bin2hex(random_bytes(12))),'simulated_paid',minor_to_money($total)]);
        $db->commit();return $orderId;
    } catch (Throwable $error) {
        if ($db->inTransaction()) $db->rollBack();
        // A different account may race to submit the same unique key.
        if ($error instanceof PDOException && (int)($error->errorInfo[1] ?? 0)===1062) {
            $existing=existing_checkout_order($db,$customerId,$checkoutKey);
            if ($existing!==null) return $existing;
        }
        throw $error;
    }
}

function change_order_status(PDO $db,int $orderId,string $next): void
{
    $db->beginTransaction();
    try {
        $query=$db->prepare('SELECT * FROM orders WHERE id=? FOR UPDATE');$query->execute([$orderId]);$order=$query->fetch();
        if (!$order) throw new ValidationException(['status'=>'This order could not be found.']);
        if ($order['status']===$next) {$db->commit();return;}
        if (!in_array($next,allowed_order_transitions($order['fulfillment_method'],$order['status']),true)) throw new ValidationException(['status'=>'This order cannot move to the selected status.']);
        if ($next==='cancelled') {
            $query=$db->prepare('SELECT product_id,SUM(quantity) AS quantity FROM order_items WHERE order_id=? GROUP BY product_id ORDER BY product_id');$query->execute([$orderId]);$items=$query->fetchAll();
            foreach ($items as $item) {
                $lock=$db->prepare('SELECT id FROM products WHERE id=? FOR UPDATE');$lock->execute([$item['product_id']]);
                if (!$lock->fetch()) throw new RuntimeException('Ordered product is missing.');
                $stock=$db->prepare('UPDATE products SET stock_quantity=stock_quantity+? WHERE id=?');$stock->execute([(int)$item['quantity'],(int)$item['product_id']]);
            }
            $query=$db->prepare("UPDATE payments SET status='simulated_refunded' WHERE order_id=? AND status='simulated_paid'");$query->execute([$orderId]);
            if ($query->rowCount()!==1) throw new RuntimeException('The simulated payment could not be refunded.');
        }
        $query=$db->prepare('UPDATE orders SET status=? WHERE id=?');$query->execute([$next,$orderId]);$db->commit();
    } catch (Throwable $error) {if ($db->inTransaction()) $db->rollBack();throw $error;}
}
