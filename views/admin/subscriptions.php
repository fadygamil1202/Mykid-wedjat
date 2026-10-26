<h1><?= e(t('admin.subscriptions')) ?></h1>
<section class="card">
  <div class="table-wrap"><table class="table-cards">
    <thead><tr><th><?= e(t('field.parent')) ?></th><th><?= e(t('field.plan')) ?></th><th><?= e(t('field.status')) ?></th><th><?= e(t('field.from')) ?></th><th><?= e(t('field.to')) ?></th><th></th></tr></thead>
    <tbody>
    <?php foreach ($subs as $s): $st = sub_state($s); $soon = $st === 'active' && strtotime($s['ends_at']) < strtotime('+7 days'); ?>
      <tr>
        <td data-label="<?= e(t('field.parent')) ?>"><a href="/admin/users/<?= e($s['user_id']) ?>"><?= e($s['name']) ?></a></td>
        <td data-label="<?= e(t('field.plan')) ?>"><?= e(loc($s, 'name')) ?> · <?= e(money($s['price'])) ?></td>
        <td data-label="<?= e(t('field.status')) ?>"><span class="badge badge-<?= e($soon ? 'expiring' : $st) ?>"><?= e($soon ? t('sub.state.expiring') : t('sub.state.' . $st)) ?></span></td>
        <td data-label="<?= e(t('field.from')) ?>"><?= e(format_date($s['starts_at'])) ?: '—' ?></td>
        <td data-label="<?= e(t('field.to')) ?>"><?= e(format_date($s['ends_at'])) ?: '—' ?></td>
        <td data-label=""><?php if ($st === 'active'): ?>
          <form method="post" action="/admin/subscriptions/<?= e($s['id']) ?>/cancel" data-confirm="<?= e(t('admin.cancel_sub_confirm')) ?>"><?= csrf_field() ?>
            <button class="link-btn small" type="submit"><?= e(t('admin.cancel_sub')) ?></button></form><?php endif; ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table></div>
  <?php if (!$subs): ?><p class="muted"><?= e(t('admin.nothing')) ?></p><?php endif; ?>
</section>
