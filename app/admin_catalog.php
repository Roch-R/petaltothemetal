<?php
declare(strict_types=1);

function validate_admin_product(PDO $pdo, array $input): array
{
    $errors = [];
    foreach (['name' => 150, 'description' => 5000] as $field => $limit) {
        $value = is_string($input[$field] ?? null) ? trim($input[$field]) : '';
        if ($value === '' || mb_strlen($value) > $limit) $errors[$field] = "Enter a $field of 1–$limit characters.";
    }
    $price = is_scalar($input['price'] ?? null) ? (string)$input['price'] : '';
    try { money_to_minor($price); } catch (Throwable $error) { $errors['price'] = 'Enter a valid nonnegative price with up to two decimal places.'; }
    $stock = is_scalar($input['stock_quantity'] ?? null) ? (string)$input['stock_quantity'] : '';
    if (!preg_match('/^(0|[1-9][0-9]{0,6})$/D', $stock) || (int)$stock > 1000000) $errors['stock_quantity'] = 'Stock must be a whole number from 0 to 1,000,000.';
    $category = filter_var($input['category_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    $statement = $pdo->prepare('SELECT id FROM categories WHERE id = ?'); $statement->execute([$category ?: 0]);
    if (!$statement->fetchColumn()) $errors['category_id'] = 'Select an existing category.';
    if (!is_string($input['image_path'] ?? null) || !product_image_allowed($input['image_path'])) $errors['image_path'] = 'Choose an approved local flower image.';
    return $errors;
}

function save_admin_product(PDO $pdo, array $input, ?int $id = null): int
{
    $errors = validate_admin_product($pdo, $input);
    if ($errors) throw new InvalidArgumentException(implode(' ', $errors));
    $values = [(int)$input['category_id'], trim($input['name']), trim($input['description']), $input['image_path'], minor_to_money(money_to_minor((string)$input['price'])), (int)$input['stock_quantity']];
    $pdo->beginTransaction();
    try {
        if ($id !== null) {
            $lock = $pdo->prepare('SELECT id FROM products WHERE id = ? FOR UPDATE'); $lock->execute([$id]);
            if (!$lock->fetchColumn()) throw new InvalidArgumentException('Product not found.');
            $statement = $pdo->prepare('UPDATE products SET category_id=?,name=?,description=?,image_path=?,price=?,stock_quantity=?,updated_at=CURRENT_TIMESTAMP WHERE id=?');
            $statement->execute(array_merge($values, [$id]));
        } else {
            $statement = $pdo->prepare('INSERT INTO products (category_id,name,description,image_path,price,stock_quantity,is_active) VALUES (?,?,?,?,?,?,1)'); $statement->execute($values);
            $id = (int)$pdo->lastInsertId();
        }
        $pdo->commit(); return $id;
    } catch (Throwable $error) { if ($pdo->inTransaction()) $pdo->rollBack(); throw $error; }
}

function archive_admin_product(PDO $pdo, int $id): void
{
    $pdo->beginTransaction();
    try {
        $statement = $pdo->prepare('SELECT id FROM products WHERE id=? FOR UPDATE'); $statement->execute([$id]);
        if (!$statement->fetchColumn()) throw new InvalidArgumentException('Product not found.');
        $statement = $pdo->prepare('UPDATE products SET is_active=0,updated_at=CURRENT_TIMESTAMP WHERE id=?'); $statement->execute([$id]); $pdo->commit();
    } catch (Throwable $error) { if ($pdo->inTransaction()) $pdo->rollBack(); throw $error; }
}

function save_admin_category(PDO $pdo, string $name, ?int $id = null): int
{
    $name = trim($name);
    if ($name === '' || mb_strlen($name) > 100) throw new InvalidArgumentException('Enter a category name of 1–100 characters.');
    $duplicate = $pdo->prepare('SELECT id FROM categories WHERE name=? AND id<>?'); $duplicate->execute([$name, $id ?? 0]);
    if ($duplicate->fetchColumn()) throw new InvalidArgumentException('That category name already exists.');
    if ($id !== null) {
        $statement = $pdo->prepare('SELECT id FROM categories WHERE id=?'); $statement->execute([$id]);
        if (!$statement->fetchColumn()) throw new InvalidArgumentException('Category not found.');
        $statement = $pdo->prepare('UPDATE categories SET name=? WHERE id=?'); $statement->execute([$name, $id]);
    } else {
        $statement = $pdo->prepare('INSERT INTO categories (name) VALUES (?)'); $statement->execute([$name]); $id = (int)$pdo->lastInsertId();
    }
    return $id;
}

function delete_admin_category(PDO $pdo, int $id): void
{
    $pdo->beginTransaction();
    try {
        $statement = $pdo->prepare('SELECT id FROM categories WHERE id=? FOR UPDATE'); $statement->execute([$id]);
        if (!$statement->fetchColumn()) throw new InvalidArgumentException('Category not found.');
        $statement = $pdo->prepare('SELECT id FROM products WHERE category_id=? LIMIT 1'); $statement->execute([$id]);
        if ($statement->fetchColumn()) throw new InvalidArgumentException('This category contains products and cannot be deleted.');
        $statement = $pdo->prepare('DELETE FROM categories WHERE id=?'); $statement->execute([$id]); $pdo->commit();
    } catch (Throwable $error) { if ($pdo->inTransaction()) $pdo->rollBack(); throw $error; }
}

function admin_dashboard(PDO $pdo): array
{
    return [
        'products' => (int)$pdo->query('SELECT COUNT(*) FROM products WHERE is_active=1')->fetchColumn(),
        'open_orders' => (int)$pdo->query("SELECT COUNT(*) FROM orders WHERE status NOT IN ('completed','cancelled')")->fetchColumn(),
        'demo_sales' => (string)$pdo->query("SELECT COALESCE(SUM(p.amount),0) FROM payments p JOIN orders o ON o.id=p.order_id WHERE p.status='simulated_paid' AND o.status<>'cancelled'")->fetchColumn(),
    ];
}
