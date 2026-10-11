<?php
require_once dirname(__DIR__, 2) . '/app/init.php'; require_post(); verify_csrf(); $customer = require_customer();
$action = $_POST['action'] ?? ''; $config = app_config();
if ($action === 'review') {
    unset($_SESSION['_pending_checkout']);
    $input = []; foreach (['fulfillment_method','scheduled_date','recipient_name','phone','address','gift_message'] as $field) { $input[$field] = is_string($_POST[$field] ?? null) ? $_POST[$field] : ''; }
    try {
        $details = validate_checkout($input + ['cart'=>get_cart()], new DateTimeImmutable('today', new DateTimeZone($config['timezone'])));
        $summary = cart_summary(db()); $prices = [];
        foreach ($summary['items'] as $item) { if (!(int) $item['is_active'] || $item['quantity'] > $item['stock_quantity']) { throw new ValidationException(['cart'=>'An arrangement or quantity is unavailable. Please update your bag.']); } $prices[(int) $item['id']] = $item['price']; }
        if (count($prices) !== count(get_cart())) { throw new ValidationException(['cart'=>'An arrangement is unavailable. Please update your bag.']); }
        $details['expected_prices'] = $prices; $details['expected_fee'] = $config[$details['fulfillment_method'] === 'delivery' ? 'delivery_fee' : 'pickup_fee'];
        validate_order_total($summary['subtotal'], money_to_minor($details['expected_fee']));
        $_SESSION['_pending_checkout'] = ['customer_id'=>(int) $customer['id'],'cart'=>get_cart(),'details'=>$details,'key'=>bin2hex(random_bytes(32)),'expected_prices'=>$prices,'created_at'=>time()];
        redirect('/payment.php');
    } catch (ValidationException $error) { $_SESSION['_checkout_form'] = ['old'=>$input,'errors'=>$error->errors]; redirect('/checkout.php'); }
}
if ($action === 'pay') {
    $key = is_string($_POST['checkout_key'] ?? null) ? $_POST['checkout_key'] : '';
    if (!preg_match('/\A[a-f0-9]{64}\z/', $key)) { abort_request(400, 'A valid checkout review is required.'); }
    $recent = $_SESSION['_recent_checkout'] ?? null;
    if ($recent && $recent['customer_id'] === (int) $customer['id'] && hash_equals($recent['key'], $key) && customer_order(db(), (int) $customer['id'], (int) $recent['order_id'])) { redirect('/confirmation.php?id=' . $recent['order_id']); }
    $pending = $_SESSION['_pending_checkout'] ?? null;
    if (!$pending || $pending['customer_id'] !== (int) $customer['id'] || !hash_equals($pending['key'], $key) || $pending['cart'] !== get_cart() || $pending['created_at'] < time() - 900) { unset($_SESSION['_pending_checkout']); flash('error', 'Your checkout review changed or expired. Please review your details again.'); redirect('/checkout.php'); }
    try {
        $orderId = place_order(db(), (int) $customer['id'], $pending['cart'], $pending['details'], $key, $config);
        clear_cart(); $_SESSION['_recent_checkout'] = ['key'=>$key,'customer_id'=>(int) $customer['id'],'order_id'=>$orderId];
        redirect('/confirmation.php?id=' . $orderId);
    } catch (ValidationException $error) { unset($_SESSION['_pending_checkout']); flash('error', $error->getMessage() . ' Please review your bag before trying again.'); redirect('/cart.php'); }
    catch (Throwable $error) { error_log('Demo checkout failed: ' . get_class($error)); flash('error', 'We could not complete your demo order. Your flowers are still in your bag; please try again.'); redirect('/payment.php'); }
}
abort_request(400, 'Choose a valid checkout action.');
