<?php
require_once dirname(__DIR__) . '/app/init.php';
require_once dirname(__DIR__) . '/templates/form_feedback.php';
if (current_user()) { redirect(current_user()['role'] === 'admin' ? '/admin/index.php' : '/shop.php'); }
$pageTitle = 'Welcome back'; $state = $_SESSION['_login_form'] ?? []; unset($_SESSION['_login_form']);
$return = safe_return_route($_GET['return'] ?? ($_SESSION['_login_return'] ?? '/shop.php'));
require dirname(__DIR__) . '/templates/header.php';
?>
<section class="auth-section"><div class="auth-visual"><img src="<?= e(url('/assets/images/tulip-bouquet.jpg')) ?>" alt="A fresh bouquet of tulips" width="700" height="900"><div class="auth-caption">Something lovely<br>is waiting for you.</div></div><div class="auth-panel"><p class="eyebrow">Good to see you again</p><h1>Welcome back.</h1><p class="muted">Log in to pick up where you left off.</p>
<?php if (!empty($state['error'])): ?><div class="error-summary" role="alert" tabindex="-1" data-error-summary><?= e($state['error']) ?></div><?php endif; ?>
<form action="<?= e(url('/actions/login.php')) ?>" method="post" data-submitting-label="Logging in..."><?= csrf_field() ?><input type="hidden" name="return" value="<?= e($return) ?>"><div class="field"><label for="email">Email address</label><input id="email" name="email" type="email" autocomplete="email" maxlength="190" value="<?= e($state['email'] ?? '') ?>" required></div><div class="field"><label for="password">Password</label><div class="password-wrap"><input id="password" name="password" type="password" autocomplete="current-password" maxlength="200" required><button type="button" class="password-toggle" data-password-toggle aria-controls="password" aria-pressed="false" hidden>Show</button></div></div><button class="button" type="submit">Log in <span aria-hidden="true">↗</span></button></form><p class="auth-switch">New around here? <a href="<?= e(url('/register.php' . ($return === '/shop.php' ? '' : '?return=' . rawurlencode($return)))) ?>">Create an account</a></p></div></section><?php require dirname(__DIR__) . '/templates/footer.php'; ?>
