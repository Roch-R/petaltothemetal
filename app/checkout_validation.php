<?php
declare(strict_types=1);

class ValidationException extends InvalidArgumentException
{
    public array $errors;
    public function __construct(array $errors)
    {
        $this->errors = $errors;
        parent::__construct(implode(' ', array_values($errors)));
    }
}

function validate_order_total(int $subtotal, int $fee): int
{
    if ($subtotal < 0 || $fee < 0 || $subtotal > 9999999999 - $fee) {
        throw new ValidationException(['total'=>'This order exceeds the supported total of 99,999,999.99. Reduce quantities or remove an arrangement from your bag.']);
    }
    return $subtotal + $fee;
}

function validate_cart_quantities(array $cart): array
{
    if (!$cart || count($cart) > 100) throw new ValidationException(['cart'=>'Your cart must contain between 1 and 100 arrangements.']);
    $normalized = [];
    foreach ($cart as $id=>$quantity) {
        if (!ctype_digit((string)$id) || (int)$id < 1 || !is_int($quantity) || $quantity < 1 || $quantity > 999) {
            throw new ValidationException(['cart'=>'Select a valid product quantity between 1 and 999.']);
        }
        $normalized[(int)$id] = $quantity;
    }
    ksort($normalized, SORT_NUMERIC);
    return $normalized;
}

function validate_checkout(array $input, DateTimeImmutable $today): array
{
    $errors = [];
    $data = [];
    foreach (['fulfillment_method','scheduled_date','recipient_name','phone','address','gift_message'] as $key) {
        $data[$key] = is_string($input[$key] ?? '') ? trim($input[$key] ?? '') : '';
    }
    if (!in_array($data['fulfillment_method'], ['delivery','pickup'], true)) $errors['fulfillment_method']='Choose delivery or store pickup.';
    $date = DateTimeImmutable::createFromFormat('!Y-m-d', $data['scheduled_date'], $today->getTimezone());
    $dateErrors = DateTimeImmutable::getLastErrors();
    if (!$date || ($dateErrors && ($dateErrors['warning_count'] || $dateErrors['error_count'])) || $date->format('Y-m-d') !== $data['scheduled_date']) {
        $errors['scheduled_date']='Choose a valid date.';
    } elseif ($date < $today->setTime(0,0)) $errors['scheduled_date']='Choose today or a future date.';
    foreach (['recipient_name'=>100,'phone'=>30,'address'=>500,'gift_message'=>500] as $key=>$limit) {
        if (strlen($data[$key]) > $limit) $errors[$key]="This field must be at most $limit characters.";
    }
    if ($data['fulfillment_method']==='delivery') {
        foreach (['recipient_name','phone','address'] as $key) if ($data[$key]==='') $errors[$key]='This field is required for delivery.';
        if ($data['phone']!=='' && !preg_match('/\A[0-9+() .-]{7,30}\z/', $data['phone'])) $errors['phone']='Enter a valid contact phone number.';
    } else {
        $data['recipient_name']=$data['phone']=$data['address']=null;
    }
    if (array_key_exists('cart',$input)) {
        try { validate_cart_quantities(is_array($input['cart']) ? $input['cart'] : []); }
        catch (ValidationException $error) { $errors += $error->errors; }
    }
    if ($errors) throw new ValidationException($errors);
    return $data;
}
