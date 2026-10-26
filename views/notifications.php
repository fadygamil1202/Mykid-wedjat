<h1><?= e(t('nav.notifications')) ?></h1>
<section class="card">
  <?php if (!$items): ?>
    <p class="muted"><?= e(t('notify.none')) ?></p>
  <?php else: ?>
  <ul class="list">
    <?php foreach ($items as $n): ?>
      <li class="<?= $n['is_read'] ? '' : 'unread' ?>">
        <span><?= e($n['message']) ?></span>
        <span class="muted small nowrap"><?= e(format_dt($n['created_at'])) ?></span>
      </li>
    <?php endforeach; ?>
  </ul>
  <?php endif; ?>
</section>
