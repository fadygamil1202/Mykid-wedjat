<?php // $sub: active subscription or null. Links only for the parent themself.
$selfView = (current_user()['role'] ?? '') === 'parent'; ?>
<?php if ($sub): ?>
  <div class="sub-banner ok">
    <div><strong><?= icon('shield') ?> <?= e(t('sub.active_title', ['plan' => loc($sub, 'name')])) ?></strong>
      <span><?= e(t('sub.until', ['date' => format_date($sub['ends_at'])])) ?></span></div>
    <?php if ($selfView): ?><a class="btn btn-sm" href="/subscription"><?= e(t('sub.manage')) ?></a><?php endif; ?>
  </div>
<?php else: ?>
  <div class="sub-banner no">
    <div><strong><?= icon('alert') ?> <?= e(t('sub.none_title')) ?></strong><span><?= e(t('sub.none_text')) ?></span></div>
    <?php if ($selfView): ?><a class="btn btn-sm btn-gold" href="/subscription"><?= e(t('sub.subscribe')) ?></a><?php endif; ?>
  </div>
<?php endif; ?>
