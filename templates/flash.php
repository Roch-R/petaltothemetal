<?php $messages = take_flash(); if ($messages): ?>
<div class="flash-wrap container" aria-live="polite">
<?php foreach ($messages as $message): ?><div class="notice notice-content <?= e($message['type'] ?? 'info') ?>"><span class="notice-symbol" aria-hidden="true"><?= ($message['type'] ?? '') === 'success' ? ui_icon('check') : '!' ?></span><span><?= e($message['message'] ?? '') ?></span></div><?php endforeach; ?>
</div>
<?php endif; ?>
