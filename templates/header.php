<?php
require_once __DIR__ . '/icons.php';
$viewer = current_user(); $siteName = app_config()['shop_name']; $activePage = basename($_SERVER['SCRIPT_NAME'] ?? '');
$bagCount = array_sum(get_cart());
$firstName = $viewer ? explode(' ', trim($viewer['name']), 2)[0] : '';
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="description" content="Thoughtfully arranged flowers for everyday gestures and the moments that matter. Explore Petal to the Metal.">
  <title><?= e(($pageTitle ?? 'Flowers for the feeling') . ' · ' . $siteName) ?></title>
  <link rel="stylesheet" href="<?= e(url('/assets/css/styles.css')) ?>">
  <link rel="stylesheet" href="<?= e(url('/assets/css/experience.css')) ?>">
  <script src="<?= e(url('/assets/js/main.js')) ?>" defer></script>
  <?php foreach (($pageScripts ?? []) as $script): ?><script src="<?= e(url('/assets/js/' . $script . '.js')) ?>" defer></script><?php endforeach; ?>
</head>
<body>
<a class="skip-link" href="#main">Skip to content</a>
<div class="announcement">A little love, in full bloom. <span>Delivery &amp; store pickup</span></div>
<header class="site-header">
  <div class="header-inner">
    <a class="wordmark" href="<?= e(url('/')) ?>" aria-label="<?= e($siteName) ?> home"><span class="brand-flower"><?= ui_icon('flower') ?></span><span><?= e($siteName) ?><span class="brand-tagline">FLOWERS &amp; LITTLE JOYS</span></span></a>
    <button class="menu-toggle" type="button" aria-expanded="false" aria-controls="site-nav" hidden>Menu <?= ui_icon('menu') ?></button>
    <nav id="site-nav" class="site-nav" aria-label="Main navigation">
      <div class="nav-links">
      <a <?= $activePage === 'index.php' ? 'aria-current="page"' : '' ?> href="<?= e(url('/')) ?>">Home</a>
      <a <?= $activePage === 'shop.php' ? 'aria-current="page"' : '' ?> href="<?= e(url('/shop.php')) ?>">The flower shop</a>
      </div>
      <div class="nav-actions">
      <?php if (!$viewer || $viewer['role'] === 'customer'): ?><a class="cart-link" href="<?= e(url('/cart.php')) ?>" aria-label="Bag, <?= e($bagCount) ?> <?= $bagCount === 1 ? 'item' : 'items' ?>"><?= ui_icon('bag') ?><span>Bag</span><span class="cart-count" aria-hidden="true"><?= e($bagCount) ?></span></a><?php endif; ?>
      <?php if ($viewer): ?>
        <details class="account-menu">
          <summary aria-label="Account menu for <?= e($viewer['name']) ?>"><span class="account-avatar"><?= ui_icon('user') ?></span><span class="account-greeting">Hi, <?= e($firstName) ?></span><?= ui_icon('chevron') ?></summary>
          <div class="account-panel">
            <div class="account-identity"><span class="eyebrow">Your account</span><strong><?= e($viewer['name']) ?></strong><span><?= e($viewer['email']) ?></span></div>
            <?php if ($viewer['role'] === 'admin'): ?><a href="<?= e(url('/admin/index.php')) ?>"><?= ui_icon('orders') ?>Admin panel</a><?php else: ?><a href="<?= e(url('/orders.php')) ?>" <?= in_array($activePage, ['orders.php','order.php','confirmation.php'], true) ? 'aria-current="page"' : '' ?>><?= ui_icon('orders') ?>My orders</a><?php endif; ?>
            <form action="<?= e(url('/logout.php')) ?>" method="post" data-submitting-label="Logging out…"><?= csrf_field() ?><button class="account-logout" type="submit"><?= ui_icon('logout') ?>Log out</button></form>
          </div>
        </details>
      <?php else: ?><a class="login-link" href="<?= e(url('/login.php')) ?>"><?= ui_icon('user') ?>Log in</a><?php endif; ?>
      </div>
    </nav>
  </div>
</header>
<?php require __DIR__ . '/flash.php'; ?>
<main id="main">
