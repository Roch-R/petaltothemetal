<?php
return [
 'admin catalog rejects invalid price and stock' => static function (): void {
  $pdo = test_db(); $id = fixture_product($pdo); $product = $pdo->query("SELECT * FROM products WHERE id = $id")->fetch();
  foreach (['-1', '1.001', 'oops', '100000000.00'] as $price) {
   $errors = validate_admin_product($pdo, array_replace($product, ['price' => $price]));
   assert_true(isset($errors['price']));
  }
  foreach (['-1', '1.5', 'oops', '1000001'] as $stock) {
   $errors = validate_admin_product($pdo, array_replace($product, ['stock_quantity' => $stock]));
   assert_true(isset($errors['stock_quantity']));
  }
 },
 'admin catalog rejects missing category and arbitrary images' => static function (): void {
  $pdo = test_db(); $id = fixture_product($pdo); $product = $pdo->query("SELECT * FROM products WHERE id = $id")->fetch();
  assert_true(isset(validate_admin_product($pdo, array_replace($product, ['category_id' => '999999999']))['category_id']));
  foreach (['https://example.com/a.jpg', '../config/local.php', 'assets/images/missing.jpg'] as $path) assert_true(isset(validate_admin_product($pdo, array_replace($product, ['image_path' => $path]))['image_path']));
 },
 'category in use cannot be deleted' => static function (): void {
  $pdo = test_db(); $id = fixture_product($pdo); $category = (int)$pdo->query("SELECT category_id FROM products WHERE id = $id")->fetchColumn();
  assert_throws(static fn() => delete_admin_category($pdo, $category));
  assert_same(1, (int)$pdo->query("SELECT COUNT(*) FROM categories WHERE id = $category")->fetchColumn());
 },
 'product archive preserves row and stock' => static function (): void {
  $pdo = test_db(); $id = fixture_product($pdo, ['stock_quantity' => 8]); archive_admin_product($pdo, $id);
  $row = $pdo->query("SELECT is_active,stock_quantity FROM products WHERE id = $id")->fetch();
  assert_same(0, (int)$row['is_active']); assert_same(8, (int)$row['stock_quantity']);
 },
 'admin save normalizes product and updates stock' => static function (): void {
  $pdo = test_db(); $id = fixture_product($pdo); $row = $pdo->query("SELECT * FROM products WHERE id = $id")->fetch();
  $row['price'] = '321.50'; $row['stock_quantity'] = '12'; $row['name'] = '  School bouquet  '; $row['image_path'] = 'assets/images/rose-bouquet.jpg';
  assert_same($id, save_admin_product($pdo, $row, $id));
  $saved = $pdo->query("SELECT * FROM products WHERE id = $id")->fetch();
  assert_same('School bouquet', $saved['name']); assert_same('321.50', $saved['price']); assert_same(12, (int)$saved['stock_quantity']);
 },
];
