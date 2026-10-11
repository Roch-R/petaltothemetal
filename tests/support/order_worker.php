<?php
declare(strict_types=1);
if (PHP_SAPI!=='cli') {http_response_code(404);exit;}
require_once dirname(__DIR__,2).'/app/init.php';
require_once __DIR__.'/database.php';
$payload=json_decode(file_get_contents($argv[1]),true,512,JSON_THROW_ON_ERROR);
$barrier=$argv[2];$result=$argv[3];
$connection=test_db();
file_put_contents($result.'.ready','ready');
$deadline=microtime(true)+12;
while (!is_file($barrier)) {
 if (microtime(true)>$deadline) {file_put_contents($result,json_encode(['error'=>'Barrier timeout','type'=>'RuntimeException']));exit(1);}
 usleep(10000);
}
try {
 $cart=[];foreach ($payload['cart'] as $id=>$quantity) $cart[(int)$id]=(int)$quantity;
 $id=place_order($connection,(int)$payload['customer'],$cart,$payload['details'],$payload['key'],[]);
 file_put_contents($result,json_encode(['id'=>$id]));
} catch (Throwable $error) {
 file_put_contents($result,json_encode(['error'=>$error->getMessage(),'type'=>get_class($error)]));
}
