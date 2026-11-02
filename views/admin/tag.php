<?php $assigned = (bool)$tag['child_id']; ?>
<div class="page-head no-print">
  <div><h1><span class="code" dir="ltr"><?= e(serial_format($tag['serial'])) ?></span> · <?= e($tag['name_en'] ? loc($tag, 'name') : t('tag.' . $tag['type'])) ?></h1>
    <p class="muted">
      <?php if ($assigned): ?><?= e($tag['first_name'] . ' ' . $tag['last_name']) ?> · <a href="/admin/users/<?= e($tag['parent_id']) ?>"><?= e($tag['parent_name']) ?></a>
      <?php else: ?><?= e(t('devices.not_assigned')) ?><?php if ($tag['status'] === 'unassigned' && !$tag['self_activation']): ?> · <?= e(t('devices.admin_only')) ?><?php endif; ?><?php endif; ?>
      <?php if ($tag['batch_id']): ?> · <a href="/admin/devices/generate#batch-<?= e($tag['batch_id']) ?>"><?= e(t('devices.batch_n', ['id' => $tag['batch_id']])) ?></a><?php endif; ?></p></div>
  <span class="badge badge-<?= e($tag['status']) ?>"><?= e(t('tags.status.' . $tag['status'])) ?></span>
</div>

<section class="card">
  <?php $child = $tag; $qrLink = in_array($tag['status'], ['replaced', 'void'], true) ? null : $link; $locked = $tag['status'] === 'locked'; require BASE_PATH . '/views/partials/id_card.php'; ?>
  <div class="no-print">
    <h2><?= e(t('admin.nfc_link')) ?></h2>
    <p class="hint"><?= e(t('admin.nfc_help')) ?></p>
    <p class="mono" dir="ltr"><?= e($link) ?></p>
    <div class="row">
      <button class="btn btn-primary" type="button" data-print><?= icon('card') ?> <?= e(t('admin.print')) ?></button>
      <button class="btn" type="button" data-copy="<?= e($link) ?>" data-done="<?= e(t('admin.copied')) ?>"><?= e(t('admin.copy')) ?></button>
      <?php if ($tag['status'] === 'active'): ?>
        <form method="post" action="/admin/devices/<?= e($tag['id']) ?>/locked" data-confirm="<?= e(t('tags.lock_confirm')) ?>"><?= csrf_field() ?><button class="btn btn-danger" type="submit"><?= icon('lock') ?> <?= e(t('tags.lock')) ?></button></form>
      <?php elseif ($tag['status'] === 'locked'): ?>
        <form method="post" action="/admin/devices/<?= e($tag['id']) ?>/active"><?= csrf_field() ?><button class="btn" type="submit"><?= icon('unlock') ?> <?= e(t('tags.unlock')) ?></button></form>
      <?php endif; ?>
    </div>
    <p class="hint warn" style="margin-top:1rem"><?= e(t('admin.link_secret')) ?></p>
  </div>
</section>

<?php if (in_array($tag['status'], ['active', 'locked', 'replaced'], true)): ?>
<section class="card no-print">
  <h2><?= e(t('devices.reset')) ?></h2>
  <p class="hint"><?= e(t('devices.reset_hint')) ?></p>
  <form method="post" action="/admin/devices/<?= e($tag['id']) ?>/reset" data-confirm="<?= e(t('devices.reset_confirm')) ?>"><?= csrf_field() ?>
    <button class="btn" type="submit"><?= icon('refresh') ?> <?= e(t('devices.reset')) ?></button></form>
</section>
<?php elseif ($tag['status'] === 'unassigned'): ?>
<section class="card no-print">
  <h2><?= e(t('devices.void')) ?></h2>
  <p class="hint"><?= e(t('devices.void_hint')) ?></p>
  <form method="post" action="/admin/devices/<?= e($tag['id']) ?>/void" data-confirm="<?= e(t('devices.void_confirm')) ?>"><?= csrf_field() ?>
    <button class="btn btn-danger" type="submit"><?= icon('x') ?> <?= e(t('devices.void')) ?></button></form>
</section>
<?php endif; ?>

<?php if ($assigned): ?>
<section class="card no-print">
  <h2><?= e(t('child.scan_log')) ?></h2>
  <?php if (!$scans): ?><p class="muted"><?= e(t('dash.no_scans')) ?></p><?php endif; ?>
  <ul class="list">
    <?php foreach ($scans as $s): ?><li><span><?= icon($s['action'] === 'call' ? 'phone' : 'eye') ?> <?= e(t('scan.kind.' . $s['action'])) ?></span><span class="muted small"><?= e(format_dt($s['created_at'])) ?></span></li><?php endforeach; ?>
  </ul>
</section>
<?php endif; ?>
<script src="/assets/qrcode.js"></script>
