<?php
function parallel_checkouts(array $payloads): array
{
 $directory=sys_get_temp_dir().DIRECTORY_SEPARATOR.'petal-concurrency-'.bin2hex(random_bytes(8));mkdir($directory);
 $barrier=$directory.DIRECTORY_SEPARATOR.'go';$processes=[];$results=[];
 try {
  foreach ($payloads as $index=>$payload) {
   $input=$directory.DIRECTORY_SEPARATOR.'input-'.$index.'.json';$result=$directory.DIRECTORY_SEPARATOR.'result-'.$index.'.json';
   file_put_contents($input,json_encode($payload,JSON_THROW_ON_ERROR));
   $descriptors=[0=>['pipe','r'],1=>['file',$directory.DIRECTORY_SEPARATOR.'stdout-'.$index,'a'],2=>['file',$directory.DIRECTORY_SEPARATOR.'stderr-'.$index,'a']];
   $process=proc_open([PHP_BINARY,dirname(__DIR__).'/support/order_worker.php',$input,$barrier,$result],$descriptors,$pipes,null,null,['bypass_shell'=>true]);
   if (!is_resource($process)) throw new RuntimeException('Could not start checkout worker.');
   fclose($pipes[0]);$processes[]=$process;$results[]=$result;
  }
  $deadline=microtime(true)+15;
  while (count(array_filter($results,static fn($path)=>is_file($path.'.ready')))!==count($results)) {
   if (microtime(true)>$deadline) throw new RuntimeException('Workers did not reach barrier.');usleep(10000);
  }
  file_put_contents($barrier,'go');
  while (count(array_filter($results,'is_file'))!==count($results)) {
   if (microtime(true)>$deadline) throw new RuntimeException('Checkout worker timed out.');usleep(10000);
  }
  return array_map(static fn($path)=>json_decode(file_get_contents($path),true,512,JSON_THROW_ON_ERROR),$results);
 } finally {
  foreach ($processes as $process) {if (proc_get_status($process)['running']) proc_terminate($process);proc_close($process);}
  foreach (glob($directory.DIRECTORY_SEPARATOR.'*') ?: [] as $file) unlink($file);rmdir($directory);
 }
}
return [
 'independent database workers cannot oversell the last unit' => static function (): void {
  $db=test_db();try {
   $product=fixture_product($db,['price'=>'20.00','stock_quantity'=>1]);$first=fixture_customer($db);$second=fixture_customer($db);
   $details=['fulfillment_method'=>'pickup','scheduled_date'=>date('Y-m-d'),'expected_prices'=>[$product=>'20.00']];
   $results=parallel_checkouts([['customer'=>$first,'cart'=>[$product=>1],'details'=>$details,'key'=>bin2hex(random_bytes(32))],['customer'=>$second,'cart'=>[$product=>1],'details'=>$details,'key'=>bin2hex(random_bytes(32))]]);
   assert_same(1,count(array_filter($results,static fn($r)=>isset($r['id']))));
   $failures=array_values(array_filter($results,static fn($r)=>isset($r['error'])));assert_same('ValidationException',$failures[0]['type'],json_encode($results));assert_true(strpos($failures[0]['error'],'stock')!==false);
   assert_same(0,(int)$db->query('SELECT stock_quantity FROM products WHERE id='.$product)->fetchColumn());
   assert_same(1,(int)$db->query('SELECT COUNT(*) FROM orders WHERE user_id IN ('.$first.','.$second.')')->fetchColumn());
   assert_same(1,(int)$db->query('SELECT COUNT(*) FROM payments p JOIN orders o ON o.id=p.order_id WHERE o.user_id IN ('.$first.','.$second.')')->fetchColumn());
  } finally {cleanup_fixtures($db);}
 },
 'concurrent repeated key returns one order with one deduction' => static function (): void {
  $db=test_db();try {
   $product=fixture_product($db,['price'=>'20.00','stock_quantity'=>2]);$user=fixture_customer($db);
   $payload=['customer'=>$user,'cart'=>[$product=>1],'details'=>['fulfillment_method'=>'pickup','scheduled_date'=>date('Y-m-d'),'expected_prices'=>[$product=>'20.00']],'key'=>bin2hex(random_bytes(32))];
   $results=parallel_checkouts([$payload,$payload]);
   assert_true(isset($results[0]['id']) && isset($results[1]['id']),json_encode($results));assert_same($results[0]['id'],$results[1]['id']);
   assert_same(1,(int)$db->query('SELECT stock_quantity FROM products WHERE id='.$product)->fetchColumn());
   assert_same(1,(int)$db->query('SELECT COUNT(*) FROM orders WHERE user_id='.$user)->fetchColumn());
  } finally {cleanup_fixtures($db);}
 },
];
