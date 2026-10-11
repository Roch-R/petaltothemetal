<?php
return [
 'fulfillment follows method specific branch' => static function (): void {
  assert_same(['out_for_delivery','cancelled'],allowed_order_transitions('delivery','preparing'));
  assert_same(['ready_for_pickup','cancelled'],allowed_order_transitions('pickup','preparing'));
 },
 'cancellation stops at dispatch or readiness' => static function (): void {
  assert_same(['completed'],allowed_order_transitions('delivery','out_for_delivery'));
  assert_same(['completed'],allowed_order_transitions('pickup','ready_for_pickup'));
  foreach (['completed','cancelled','bogus','ready_for_pickup'] as $status) assert_same([],allowed_order_transitions('delivery',$status));
 },
];
