<?php
$steps = ['bag' => ['Your bag', '/cart.php'], 'details' => ['Details', '/checkout.php'], 'payment' => ['Demo payment', '/payment.php']];
$stepIndex = array_search($checkoutStep, array_keys($steps), true);
?>
<nav class="checkout-step" aria-label="Checkout progress"><ol>
<?php $index = 0; foreach ($steps as $key => [$label, $path]): ?>
<li><span class="step-number" aria-hidden="true"><?= ++$index ?></span>
<?php if ($key === $checkoutStep): ?><span aria-current="step"><?= e($label) ?></span>
<?php elseif ($index - 1 < $stepIndex): ?><a href="<?= e(url($path)) ?>"><?= e($label) ?></a>
<?php else: ?><span><?= e($label) ?></span><?php endif; ?></li>
<?php endforeach; ?>
</ol></nav>
