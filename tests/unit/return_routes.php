<?php
return [
 'login return supports a selected product and rejects external or malformed routes' => static function (): void {
  assert_same('/product.php?id=1', safe_return_route('/product.php?id=1'));
  assert_same('/orders.php', safe_return_route('/orders.php'));
  foreach (['https://example.com','//example.com','/product.php?id=0','/product.php?id=1&next=//example.com',[],"/shop.php\r\nLocation: evil"] as $route) assert_same('/shop.php', safe_return_route($route));
 },
];
