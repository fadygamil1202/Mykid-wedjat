<div class="page-head"><h1><?= e(t('admin.requests')) ?></h1>
  <div class="row">
    <?php foreach (['pending', 'completed', 'rejected'] as $s): ?>
      <a class="btn btn-sm <?= $status === $s ? 'btn-primary' : '' ?>" href="/admin/requests?status=<?= e($s) ?>"><?= e(t('req.' . $s)) ?></a>
    <?php endforeach; ?>
  </div>
</div>
<section class="card">
  <?php if (!$requests): ?><p class="muted"><?= e(t('admin.nothing')) ?></p><?php endif; ?>
  <?php foreach ($requests as $r): ?>
    <div class="device">
      <span class="device-icon"><?= icon('refresh') ?></span>
      <div class="device-main">
        <strong><?= e(t('tag.' . $r['type'])) ?> <a href="/admin/devices/<?= e($r['tag_id']) ?>">#<?= e($r['tag_id']) ?></a> · <?= e($r['first_name'] . ' ' . $r['last_name']) ?></strong>
        <span class="muted small"><?= e(t('reason.' . $r['reason'])) ?> · <a href="/admin/users/<?= e($r['user_id']) ?>"><?= e($r['parent_name']) ?></a> · <?= e(format_dt($r['created_at'])) ?></span>
        <?php if ($r['notes']): ?><div class="small"><?= e($r['notes']) ?></div><?php endif; ?>
        <?php if ($r['pay_status']): ?><span class="badge badge-<?= e($r['pay_status']) ?>"><?= e(t('pay.status.' . $r['pay_status'])) ?></span><?php endif; ?>
        <?php if ($r['new_tag_id']): ?><div class="small"><?= e(t('admin.new_device')) ?> <a href="/admin/devices/<?= e($r['new_tag_id']) ?>">#<?= e($r['new_tag_id']) ?></a></div><?php endif; ?>
      </div>
      <?php if ($r['status'] === 'pending'): ?>
      <div class="row">
        <form method="post" action="/admin/requests/<?= e($r['id']) ?>/complete" data-confirm="<?= e(t('admin.complete_confirm')) ?>"><?= csrf_field() ?>
          <button class="btn btn-sm btn-primary" type="submit"><?= icon('nfc') ?> <?= e(t('admin.issue_replacement')) ?></button></form>
        <form method="post" action="/admin/requests/<?= e($r['id']) ?>/reject" data-confirm="<?= e(t('admin.reject_confirm')) ?>"><?= csrf_field() ?>
          <button class="btn btn-sm btn-ghost" type="submit"><?= e(t('admin.reject')) ?></button></form>
      </div>
      <?php endif; ?>
    </div>
  <?php endforeach; ?>
</section>
