<?php
require_once dirname(__DIR__, 2) . '/app/init.php'; require_post(); verify_csrf();
if (!current_user()) { $_SESSION['_login_return'] = '/cart.php'; redirect('/login.php?return=/cart.php'); }
require_customer();
$id = filter_var($_POST['product_id'] ?? null, FILTER_VALIDATE_INT, ['options'=>['min_range'=>1]]);
$quantity = filter_var($_POST['quantity'] ?? null, FILTER_VALIDATE_INT, ['options'=>['min_range'=>1,'max_range'=>999]]);
try {
    if (!$id) { throw new ValidationException(['product'=>'Choose a valid arrangement.']); }
    switch ($_POST['action'] ?? '') {
        case 'add': if (!$quantity) { throw new ValidationException(['quantity'=>'Choose a quantity between 1 and 999.']); } add_cart_item(db(), $id, $quantity); flash('success', 'A lovely choice. Your flowers are in your bag.'); break;
        case 'update': if (!$quantity) { throw new ValidationException(['quantity'=>'Choose a quantity between 1 and 999.']); } update_cart_item(db(), $id, $quantity); flash('success', 'Your flower bag is updated.'); break;
        case 'remove': remove_cart_item($id); flash('success', 'Arrangement removed from your bag.'); break;
        default: abort_request(400, 'Choose a valid cart action.');
    }
} catch (ValidationException $error) { flash('error', $error->getMessage()); }
redirect('/cart.php');
