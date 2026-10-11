<?php
function field_feedback_attributes(string $field, array $errors, string $help = ''): string
{
    $description = trim($help . (isset($errors[$field]) ? ' ' . $field . '-error' : ''));
    return (isset($errors[$field]) ? ' aria-invalid="true"' : '')
        . ($description !== '' ? ' aria-describedby="' . e($description) . '"' : '');
}
function field_feedback(string $field, array $errors): void
{
    if (isset($errors[$field])) {
        echo '<p class="field-error" id="' . e($field) . '-error">' . e($errors[$field]) . '</p>';
    }
}
function error_summary(array $errors): void
{
    if (!$errors) { return; }
    echo '<div class="error-summary" role="alert" tabindex="-1" data-error-summary><p>Please check these details.</p><ul>';
    foreach ($errors as $field => $error) {
        $fields = ['name', 'email', 'password', 'fulfillment_method', 'scheduled_date', 'recipient_name', 'phone', 'address', 'gift_message'];
        $target = $field === 'cart' ? url('/cart.php') : (in_array($field, $fields, true) ? '#' . $field : null);
        echo '<li>' . ($target !== null ? '<a href="' . e($target) . '">' . e($error) . '</a>' : e($error)) . '</li>';
    }
    echo '</ul></div>';
}
