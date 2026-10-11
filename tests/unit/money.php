<?php
return [
 'cart display supports totals above the order storage limit' => static function (): void {
  assert_same(app_config()['currency_symbol'] . '199,999,999.98', format_money(19999999998));
 },
 'decimal totals remain exact' => static function (): void {
  assert_same(30, money_to_minor('0.10') + money_to_minor('0.20'));
  assert_same('123.45', minor_to_money(12345));
  assert_same(150005, money_to_minor('1500.05'));
 },
 'malformed and negative money rejected' => static function (): void {
  foreach (['-1', '1.001', '01.20', '1e2', '', '100000000.00'] as $value) assert_throws(static fn() => money_to_minor($value),InvalidArgumentException::class);
  assert_throws(static fn() => minor_to_money(-1),InvalidArgumentException::class);
  assert_throws(static fn() => minor_to_money(10000000000),InvalidArgumentException::class);
 },
];
