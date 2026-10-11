<article class="product-card">
  <a class="product-image-link" href="<?= e(url('/product.php?id=' . $product['id'])) ?>" aria-label="View <?= e($product['name']) ?>">
    <img src="<?= e(url('/' . $product['image_path'])) ?>" alt="<?= e($product['name']) ?> flower arrangement" loading="lazy" width="600" height="700">
    <?php if ((int) $product['stock_quantity'] < 1): ?><span class="product-badge">Sold out</span><?php endif; ?>
    <span class="product-view">Take a closer look <span aria-hidden="true">↗</span></span>
  </a>
  <div class="product-meta"><p class="eyebrow"><?= e($product['category_name'] ?? 'Flower arrangement') ?></p><div class="product-title-row"><h3><a href="<?= e(url('/product.php?id=' . $product['id'])) ?>"><?= e($product['name']) ?></a></h3><span><?= e(format_money($product['price'])) ?></span></div></div>
</article>
