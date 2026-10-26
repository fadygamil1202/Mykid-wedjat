<div class="page-head no-print">
  <div><h1><?= e(t('admin.device_n', ['id' => $tag['id']])) ?> · <?= e(t('tag.' . $tag['type'])) ?></h1>
    <p class="muted"><?= e($tag['first_name'] . ' ' . $tag['last_name']) ?> · <a href="/admin/users/<?= e($tag['parent_id']) ?>"><?= e($tag['parent_name']) ?></a></p></div>
  <span class="badge badge-<?= e($tag['status']) ?>"><?= e(t('tags.status.' . $tag['status'])) ?></span>
</div>

<section class="card">
  <?php $child = $tag; $qrLink = $tag['status'] === 'replaced' ? null : $link; $locked = $tag['status'] !== 'active'; require BASE_PATH . '/views/partials/id_card.php'; ?>
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

<section class="card no-print">
  <h2><?= e(t('child.scan_log')) ?></h2>
  <?php if (!$scans): ?><p class="muted"><?= e(t('dash.no_scans')) ?></p><?php endif; ?>
  <ul class="list">
    <?php foreach ($scans as $s): ?><li><span><?= icon($s['action'] === 'call' ? 'phone' : 'eye') ?> <?= e(t('scan.kind.' . $s['action'])) ?></span><span class="muted small"><?= e(format_dt($s['created_at'])) ?></span></li><?php endforeach; ?>
  </ul>
</section>
<script src="/assets/qrcode.js"></script>
