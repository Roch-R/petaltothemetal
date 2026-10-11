</main>
<footer class="site-footer">
  <div class="container footer-grid">
    <div><a class="footer-brand" href="<?= e(url('/')) ?>"><?= e(app_config()['shop_name']) ?><span aria-hidden="true"> ✳</span></a><p>For the big days.<br>And the just-because days.</p></div>
    <div><h2>Find your flowers</h2><a href="<?= e(url('/shop.php')) ?>">All arrangements</a><?php foreach (list_categories(db()) as $footerCategory): ?><a href="<?= e(url('/shop.php?category=' . $footerCategory['id'])) ?>"><?= e($footerCategory['name']) ?></a><?php endforeach; ?></div>
    <div><h2>A thoughtful little detail</h2><p>Choose delivery or store pickup.<br>Add a gift message at checkout.</p><a href="<?= e(url('/orders.php')) ?>">Your orders</a></div>
  </div>
  <div class="container footer-bottom"><span>© <?= date('Y') ?> <?= e(app_config()['shop_name']) ?></span><span>School demonstration · All payments are simulated</span></div>
</footer>
</body></html>
