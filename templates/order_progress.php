<?php
if ($order['status'] === 'cancelled'): ?>
<p class="field-help">This order was cancelled. See the simulated refund details below.</p>
<?php else:
$ready = $order['fulfillment_method'] === 'pickup' ? 'ready_for_pickup' : 'out_for_delivery';
$stages = ['pending' => 'Order received', 'preparing' => 'Preparing flowers', $ready => $ready === 'ready_for_pickup' ? 'Ready for pickup' : 'Out for delivery', 'completed' => 'Completed'];
$currentStage = array_search($order['status'], array_keys($stages), true);
?>
<ol class="order-progress" aria-label="Order progress">
<?php $stageIndex = 0; foreach ($stages as $status => $label): ?>
<li <?= $stageIndex < $currentStage ? 'class="is-complete"' : '' ?> <?= $status === $order['status'] ? 'aria-current="step"' : '' ?>><span class="progress-dot" aria-hidden="true"><?= $stageIndex < $currentStage ? ui_icon('check') : $stageIndex + 1 ?></span><span><?= e($label) ?><?php if ($stageIndex < $currentStage): ?><span class="sr-only"> (done)</span><?php endif; ?></span></li>
<?php $stageIndex++; endforeach; ?>
</ol>
<?php endif; ?>
