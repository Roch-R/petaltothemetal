<?php
declare(strict_types=1);

function fixture_customer(PDO $connection, array $overrides = []): int
{
    $values = array_merge(['name'=>'Fixture Customer','email'=>'fixture-' . bin2hex(random_bytes(8)) . '@example.test','password_hash'=>password_hash('FixturePassword123', PASSWORD_DEFAULT),'role'=>'customer'], $overrides);
    $statement = $connection->prepare('INSERT INTO users (name,email,password_hash,role) VALUES (?,?,?,?)');
    $statement->execute([$values['name'],$values['email'],$values['password_hash'],$values['role']]);
    $id = (int) $connection->lastInsertId();
    $GLOBALS['_fixture_users'][] = $id;
    return $id;
}

function fixture_product(PDO $connection, array $overrides = []): int
{
    if (!isset($overrides['category_id'])) {
        $statement = $connection->prepare('INSERT INTO categories (name) VALUES (?)');
        $statement->execute(['Fixture ' . bin2hex(random_bytes(8))]);
        $overrides['category_id'] = (int) $connection->lastInsertId();
        $GLOBALS['_fixture_categories'][] = $overrides['category_id'];
    }
    $values = array_merge(['name'=>'Fixture Flower','description'=>'A test arrangement','image_path'=>'assets/images/rose-bouquet.jpg','price'=>'125.50','stock_quantity'=>10,'is_active'=>1],$overrides);
    $statement = $connection->prepare('INSERT INTO products (category_id,name,description,image_path,price,stock_quantity,is_active) VALUES (?,?,?,?,?,?,?)');
    $statement->execute([$values['category_id'],$values['name'],$values['description'],$values['image_path'],$values['price'],$values['stock_quantity'],$values['is_active']]);
    $id = (int) $connection->lastInsertId();
    $GLOBALS['_fixture_products'][] = $id;
    return $id;
}

function cleanup_fixtures(PDO $connection): void
{
    if ($connection->inTransaction()) { $connection->rollBack(); }
    foreach ($GLOBALS['_fixture_users'] ?? [] as $id) {
        $statement = $connection->prepare('SELECT id FROM orders WHERE user_id=?');
        $statement->execute([$id]);
        foreach ($statement->fetchAll(PDO::FETCH_COLUMN) as $orderId) {
            foreach (['payments','order_items'] as $table) {
                $delete = $connection->prepare('DELETE FROM ' . $table . ' WHERE order_id=?');
                $delete->execute([$orderId]);
            }
            $delete = $connection->prepare('DELETE FROM orders WHERE id=?'); $delete->execute([$orderId]);
        }
    }
    foreach (['_fixture_products'=>'products','_fixture_categories'=>'categories','_fixture_users'=>'users'] as $key=>$table) {
        foreach ($GLOBALS[$key] ?? [] as $id) {
            $delete = $connection->prepare('DELETE FROM ' . $table . ' WHERE id=?'); $delete->execute([$id]);
        }
        $GLOBALS[$key] = [];
    }
}
