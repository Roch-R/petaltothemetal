<?php
function fixture_order_details(array $prices, string $method='pickup'): array {
 return ['fulfillment_method'=>$method,'scheduled_date'=>date('Y-m-d'),'recipient_name'=>'Fixture Recipient','phone'=>'09123456789','address'=>'Fixture delivery address','gift_message'=>'<b>Flowers</b>','expected_prices'=>$prices];
}
return [
 'oversized bag stays readable and order rejects before persistence' => static function (): void {
  $db=test_db(); $savedSession=$_SESSION ?? [];
  try {
   $user=fixture_customer($db);$product=fixture_product($db,['price'=>'99999999.99','stock_quantity'=>2]);
   $_SESSION['_cart']=[];add_cart_item($db,$product,2);
   assert_same(19999999998,cart_summary($db)['subtotal']);
   assert_throws(static fn()=>place_order($db,$user,[$product=>2],fixture_order_details([$product=>'99999999.99']),bin2hex(random_bytes(32)),[]),ValidationException::class);
   assert_same(2,(int)$db->query('SELECT stock_quantity FROM products WHERE id='.$product)->fetchColumn());
   assert_same(0,(int)$db->query('SELECT COUNT(*) FROM orders WHERE user_id='.$user)->fetchColumn());
   remove_cart_item($product);assert_same([],get_cart());
  } finally {$_SESSION=$savedSession;cleanup_fixtures($db);}
 },
 'checkout trusts locked prices and records snapshots once' => static function (): void {
  $db=test_db();try {
   $user=fixture_customer($db);$product=fixture_product($db,['price'=>'0.10','stock_quantity'=>5]);$key=bin2hex(random_bytes(32));
   $id=place_order($db,$user,[$product=>3],fixture_order_details([$product=>'0.10'],'delivery'),$key,['delivery_fee'=>'0.20','pickup_fee'=>'0.00']);
   $order=customer_order($db,$user,$id);assert_same('0.50',$order['total']);assert_same('0.10',$order['items'][0]['unit_price']);assert_same('simulated_paid',$order['payment']['status']);
   $db->exec("UPDATE products SET name='Updated name',price=99.00 WHERE id=".$product);
   $historical=customer_order($db,$user,$id);assert_same('0.10',$historical['items'][0]['unit_price']);assert_same('Fixture Flower',$historical['items'][0]['product_name']);
   assert_same($id,place_order($db,$user,[],[],$key,[]));
   assert_same(2,(int)$db->query('SELECT stock_quantity FROM products WHERE id='.$product)->fetchColumn());
   assert_same(null,customer_order($db,fixture_customer($db),$id));
   assert_throws(static fn()=>place_order($db,fixture_customer($db),[$product=>1],fixture_order_details([$product=>'0.10']),$key,[]),ValidationException::class);
  } finally {cleanup_fixtures($db);}
 },
 'stock inactive product and changed review price reject without deduction' => static function (): void {
  $db=test_db();try {
   $user=fixture_customer($db);$product=fixture_product($db,['price'=>'20.00','stock_quantity'=>1]);
   assert_throws(static fn()=>place_order($db,$user,[$product=>2],fixture_order_details([$product=>'20.00']),bin2hex(random_bytes(32)),[]),ValidationException::class);
   assert_throws(static fn()=>place_order($db,$user,[$product=>1],fixture_order_details([$product=>'19.00']),bin2hex(random_bytes(32)),[]),ValidationException::class);
   $db->exec('UPDATE products SET is_active=0 WHERE id='.$product);
   assert_throws(static fn()=>place_order($db,$user,[$product=>1],fixture_order_details([$product=>'20.00']),bin2hex(random_bytes(32)),[]),ValidationException::class);
   assert_same(1,(int)$db->query('SELECT stock_quantity FROM products WHERE id='.$product)->fetchColumn());
   assert_same(0,(int)$db->query('SELECT COUNT(*) FROM orders WHERE user_id='.$user)->fetchColumn());
  } finally {cleanup_fixtures($db);}
 },
 'payment constraint failure rolls back order items and stock' => static function (): void {
  $db=test_db();try {
   $user=fixture_customer($db);$product=fixture_product($db,['price'=>'20.00','stock_quantity'=>2]);
   $db->exec("CREATE TRIGGER test_payment_failure BEFORE INSERT ON payments FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='fixture failure'");
   assert_throws(static fn()=>place_order($db,$user,[$product=>1],fixture_order_details([$product=>'20.00']),bin2hex(random_bytes(32)),[]),PDOException::class);
   assert_same(2,(int)$db->query('SELECT stock_quantity FROM products WHERE id='.$product)->fetchColumn());
   assert_same(0,(int)$db->query('SELECT COUNT(*) FROM orders WHERE user_id='.$user)->fetchColumn());
  } finally {$db->exec('DROP TRIGGER IF EXISTS test_payment_failure');cleanup_fixtures($db);}
 },
];
