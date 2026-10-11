<?php
require_once dirname(__DIR__) . '/app/init.php';
$pageTitle = 'Flowers for the feeling';
$featured = array_slice(list_products(db(), ['sort' => 'featured']), 0, 4);
$categories = list_categories(db());
require dirname(__DIR__) . '/templates/header.php';
?>
<section class="hero">
  <div class="hero-copy"><p class="eyebrow">A flower for every feeling</p><h1>Some things<br>are better said<br>with <em>flowers.</em></h1><p>A thank you. A fresh start. A little just-because. Find thoughtfully arranged blooms for the moments that make life lovely.</p><a class="button" href="<?= e(url('/shop.php')) ?>">Find your flowers <span aria-hidden="true">↗</span></a><p class="hero-note">Handpicked blooms. Thoughtful arrangements.</p></div>
  <div class="hero-image"><img src="<?= e(url('/assets/images/hero-flowers.jpg')) ?>" alt="Creamy peonies, purple flowers, and fresh green foliage" width="1000" height="1100" fetchpriority="high"><div class="hero-stamp"><span aria-hidden="true">✳</span>made for<br>your moments</div></div>
</section>
<div class="service-strip"><div><span aria-hidden="true">✳</span> Flowers with a personal touch</div><div><span aria-hidden="true">↗</span> Delivery or store pickup</div><div><span aria-hidden="true">♡</span> A gift message, just for them</div></div>
<section class="container section"><div class="section-heading"><div><p class="eyebrow">Meet your next lovely gesture</p><h2>A few of our favorites</h2></div><a class="text-link" href="<?= e(url('/shop.php')) ?>">Shop all flowers ↗</a></div>
<?php if ($featured): ?><div class="product-grid"><?php foreach ($featured as $product) { require dirname(__DIR__) . '/templates/product_card.php'; } ?></div><?php else: ?><div class="empty-state"><h2>New blooms are on the way.</h2><p>Check back soon for our latest arrangements.</p></div><?php endif; ?>
</section>
<section class="story-banner"><img src="<?= e(url('/assets/images/mixed-bouquet.jpg')) ?>" alt="Softly colored seasonal flowers arranged together" loading="lazy" width="800" height="650"><div class="story-copy"><p class="eyebrow">Little gestures, lasting feelings</p><h2>For the big days.<br>And the everyday.</h2><p>Flowers have a way of turning an ordinary moment into something worth remembering. Pick a bouquet, write a little note, and let the blooms do the rest.</p><a class="text-link" href="<?= e(url('/shop.php')) ?>">Send a little joy ↗</a></div></section>
<section class="container section"><div class="section-heading"><div><p class="eyebrow">Find your kind of lovely</p><h2>What are you drawn to?</h2></div></div><div class="category-links"><?php foreach ($categories as $category): ?><a class="category-link" href="<?= e(url('/shop.php?category=' . $category['id'])) ?>"><?= e($category['name']) ?> ↗</a><?php endforeach; ?></div></section>
<?php require dirname(__DIR__) . '/templates/footer.php'; ?>
