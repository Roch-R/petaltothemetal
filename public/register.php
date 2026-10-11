<?php
require_once dirname(__DIR__) . '/app/init.php';
require_once dirname(__DIR__) . '/templates/form_feedback.php';
if (current_user()) { redirect(current_user()['role'] === 'admin' ? '/admin/index.php' : '/shop.php'); }
$return = safe_return_route($_GET['return'] ?? '/shop.php');
$returnQuery = $return === '/shop.php' ? '' : '?return=' . rawurlencode($return);
$pageTitle = 'Create an account'; $state = $_SESSION['_register_form'] ?? []; unset($_SESSION['_register_form']); $old = $state['old'] ?? []; $errors = $state['errors'] ?? []; require dirname(__DIR__) . '/templates/header.php';
?>
<section class="auth-section"><div class="auth-visual"><img src="<?= e(url('/assets/images/rose-bouquet.jpg')) ?>" alt="Blush pink rose bouquet" width="700" height="900"><div class="auth-caption">A little joy.<br>A lovely beginning.</div></div><div class="auth-panel"><p class="eyebrow">Welcome to our little flower shop</p><h1>Let's make<br>someone's day.</h1><p class="muted">Create an account to order flowers and keep your lovely gestures in one place.</p>
<?php error_summary($errors); ?>
<form action="<?= e(url('/actions/register.php')) ?>" method="post" data-submitting-label="Creating account...">
<?= csrf_field() ?><input type="hidden" name="return" value="<?= e($return) ?>">
<?php foreach (['name' => ['Your name', 'text', 'name', 100], 'email' => ['Email address', 'email', 'email', 190]] as $field => [$label, $type, $autocomplete, $limit]): ?>
<div class="field"><label for="<?= e($field) ?>"><?= e($label) ?></label><input id="<?= e($field) ?>" name="<?= e($field) ?>" type="<?= e($type) ?>" autocomplete="<?= e($autocomplete) ?>" maxlength="<?= e($limit) ?>" value="<?= e($old[$field] ?? '') ?>" required <?= field_feedback_attributes($field, $errors) ?>><?php field_feedback($field, $errors); ?></div>
<?php endforeach; ?>
<div class="field"><label for="password">Password</label><div class="password-wrap"><input id="password" name="password" type="password" autocomplete="new-password" minlength="8" maxlength="72" required <?= field_feedback_attributes('password', $errors, 'password-help') ?>><button type="button" class="password-toggle" data-password-toggle aria-controls="password" aria-pressed="false" hidden>Show</button></div><?php field_feedback('password', $errors); ?><p class="field-help" id="password-help">Use between 8 and 72 characters.</p></div>
<button class="button" type="submit">Create my account</button></form><p class="auth-switch">Already part of the garden? <a href="<?= e(url('/login.php' . $returnQuery)) ?>">Log in</a></p></div></section><?php require dirname(__DIR__) . '/templates/footer.php'; ?>
