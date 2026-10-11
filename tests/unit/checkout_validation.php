<?php
return [
 'checkout rejects oversized totals including fulfillment fees' => static function (): void {
  assert_same(9999999999, validate_order_total(9999999998, 1));
  assert_throws(static fn() => validate_order_total(9999999999, 1), ValidationException::class);
  assert_throws(static fn() => validate_order_total(19999999998, 0), ValidationException::class);
 },
 'pickup needs no delivery address' => static function (): void {
  $data = validate_checkout(['fulfillment_method'=>'pickup','scheduled_date'=>'2026-10-08'],new DateTimeImmutable('2026-10-08'));
  assert_same(null,$data['address']);
 },
 'delivery requires recipient phone and address' => static function (): void {
  assert_throws(static fn() => validate_checkout(['fulfillment_method'=>'delivery','scheduled_date'=>'2026-10-08'],new DateTimeImmutable('2026-10-08')),ValidationException::class);
 },
 'invalid method date cart and overlong fields rejected' => static function (): void {
  $base=['fulfillment_method'=>'pickup','scheduled_date'=>'2026-10-08'];
  foreach ([['fulfillment_method'=>'courier'],['scheduled_date'=>'2026-02-30'],['scheduled_date'=>'2026-10-07'],['scheduled_date'=>'2026-10-8'],['cart'=>[]],['cart'=>[1=>0]],['gift_message'=>str_repeat('x',501)]] as $change)
   assert_throws(static fn() => validate_checkout(array_replace($base,$change),new DateTimeImmutable('2026-10-08')),ValidationException::class);
 },
];
