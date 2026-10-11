<?php
return [
 'cancellation restores stock and demo payment exactly once' => static function (): void {
  $db=test_db();try {
   $user=fixture_customer($db);$product=fixture_product($db,['price'=>'10.00','stock_quantity'=>4]);
   $details=['fulfillment_method'=>'pickup','scheduled_date'=>date('Y-m-d'),'expected_prices'=>[$product=>'10.00']];
   $id=place_order($db,$user,[$product=>2],$details,bin2hex(random_bytes(32)),[]);
   change_order_status($db,$id,'cancelled');change_order_status($db,$id,'cancelled');
   assert_same(4,(int)$db->query('SELECT stock_quantity FROM products WHERE id='.$product)->fetchColumn());
   assert_same('simulated_refunded',customer_order($db,$user,$id)['payment']['status']);
  } finally {cleanup_fixtures($db);}
 },
 'cancellation cutoff and fulfillment branches enforced' => static function (): void {
  $db=test_db();try {
   $user=fixture_customer($db);$product=fixture_product($db,['price'=>'10.00','stock_quantity'=>4]);
   $id=place_order($db,$user,[$product=>1],['fulfillment_method'=>'pickup','scheduled_date'=>date('Y-m-d'),'expected_prices'=>[$product=>'10.00']],bin2hex(random_bytes(32)),[]);
   change_order_status($db,$id,'preparing');assert_throws(static fn()=>change_order_status($db,$id,'out_for_delivery'),ValidationException::class);
   change_order_status($db,$id,'ready_for_pickup');assert_throws(static fn()=>change_order_status($db,$id,'cancelled'),ValidationException::class);
   change_order_status($db,$id,'completed');assert_throws(static fn()=>change_order_status($db,$id,'cancelled'),ValidationException::class);
  } finally {cleanup_fixtures($db);}
 },
 'failed cancellation leaves stock payment and status unchanged' => static function (): void {
  $db=test_db();try {
   $user=fixture_customer($db);$product=fixture_product($db,['price'=>'10.00','stock_quantity'=>4]);
   $id=place_order($db,$user,[$product=>2],['fulfillment_method'=>'pickup','scheduled_date'=>date('Y-m-d'),'expected_prices'=>[$product=>'10.00']],bin2hex(random_bytes(32)),[]);
   $db->exec("CREATE TRIGGER test_refund_failure BEFORE UPDATE ON payments FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='fixture failure'");
   assert_throws(static fn()=>change_order_status($db,$id,'cancelled'),PDOException::class);
   assert_same(2,(int)$db->query('SELECT stock_quantity FROM products WHERE id='.$product)->fetchColumn());
   $order=customer_order($db,$user,$id);assert_same('pending',$order['status']);assert_same('simulated_paid',$order['payment']['status']);
  } finally {$db->exec('DROP TRIGGER IF EXISTS test_refund_failure');cleanup_fixtures($db);}
 },
];
