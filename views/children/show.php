<?php $age = age_from_dob($child['date_of_birth']); ?>
<div class="page-head">
  <div class="row" style="gap:.9rem">
    <span class="avatar"><?= e(mb_substr($child['first_name'], 0, 1)) ?></span>
    <div>
      <h1 style="margin:0"><?= e($child['first_name']) ?> <?= e($child['last_name']) ?></h1>
      <?php if ($age !== null): ?><span class="muted"><?= e(t('field.age_n', ['n' => $age])) ?></span><?php endif; ?>
    </div>
  </div>
  <a class="btn btn-sm btn-ghost" href="/children/<?= e($child['id']) ?>/edit"><?= icon('edit') ?> <?= e(t('children.edit')) ?></a>
</div>

<?php if (!$sub): ?><div class="hint warn" style="margin-bottom:1rem"><?= e(t('child.no_sub')) ?> <a href="/subscription"><?= e(t('sub.subscribe')) ?></a></div><?php endif; ?>
<?php if (empty($profile['consent_at'])): ?><div class="hint warn" style="margin-bottom:1rem"><?= e(t('child.no_consent')) ?> <a href="/children/<?= e($child['id']) ?>/emergency"><?= e(t('child.fill_now')) ?></a></div><?php endif; ?>

<div class="row" style="margin-bottom:1rem">
  <a class="btn btn-primary" href="/children/<?= e($child['id']) ?>/emergency"><?= icon('heart') ?> <?= e(t('child.edit_emergency')) ?></a>
  <a class="btn" href="/children/<?= e($child['id']) ?>/card"><?= icon('card') ?> <?= e(t('child.view_card')) ?></a>
  <a class="btn" href="/children/<?= e($child['id']) ?>/preview"><?= icon('eye') ?> <?= e(t('child.preview')) ?></a>
</div>

<div class="cols">
  <div class="col-main">
    <section class="card">
      <div class="card-head"><h2><?= e(t('child.devices')) ?></h2><a class="btn btn-sm" href="/shop"><?= icon('plus') ?> <?= e(t('child.add_device')) ?></a></div>
      <?php if (!$tags): ?>
        <p class="muted"><?= e(t('child.no_devices')) ?></p>
      <?php endif; ?>
      <?php foreach ($tags as $tg): ?>
        <div class="device">
          <span class="device-icon"><?= icon($tg['type'] === 'card' ? 'card' : ($tg['type'] === 'bracelet' ? 'nfc' : 'heart')) ?></span>
          <div class="device-main">
            <strong><?= e(t('tag.' . $tg['type'])) ?> <span class="muted small">#<?= e($tg['id']) ?></span></strong>
            <span class="badge badge-<?= e($tg['status']) ?>"><?= e(t('tags.status.' . $tg['status'])) ?></span>
            <?php if ($tg['request_status'] === 'pending'): ?><span class="badge badge-pending"><?= e(t('tags.replacement_pending')) ?></span><?php endif; ?>
            <div class="muted small"><?= e(t('tags.scans_n', ['n' => $tg['scans']])) ?><?= $tg['last_scan'] ? ' · ' . e(t('tags.last', ['date' => format_dt($tg['last_scan'])])) : '' ?></div>
          </div>
          <div class="row">
            <?php if ($tg['status'] === 'active'): ?>
              <form method="post" action="/devices/<?= e($tg['id']) ?>/lock" data-confirm="<?= e(t('tags.lock_confirm')) ?>"><?= csrf_field() ?>
                <button class="btn btn-sm" type="submit"><?= icon('lock') ?> <?= e(t('tags.lock')) ?></button></form>
            <?php elseif ($tg['request_status'] !== 'pending'): ?>
              <form method="post" action="/devices/<?= e($tg['id']) ?>/unlock"><?= csrf_field() ?>
                <button class="btn btn-sm" type="submit"><?= icon('unlock') ?> <?= e(t('tags.unlock')) ?></button></form>
            <?php endif; ?>
            <?php if ($tg['request_status'] !== 'pending'): ?>
              <a class="btn btn-sm btn-ghost" href="/devices/<?= e($tg['id']) ?>/replace"><?= icon('refresh') ?> <?= e(t('tags.request_new')) ?></a>
            <?php endif; ?>
          </div>
        </div>
      <?php endforeach; ?>
      <p class="hint" style="margin-top:.75rem"><?= e(t('child.lock_hint')) ?></p>
    </section>

    <section class="card">
      <h2><?= e(t('child.scan_log')) ?></h2>
      <?php if (!$scans): ?><p class="muted"><?= e(t('dash.no_scans')) ?></p><?php else: ?>
      <ul class="list">
        <?php foreach ($scans as $s): ?>
          <li><span><?= icon($s['action'] === 'call' ? 'phone' : 'eye') ?> <?= e(t('scan.' . $s['action'], ['name' => $child['first_name'], 'type' => t('tag.' . $s['type'])])) ?></span>
              <span class="muted small nowrap"><?= e(format_dt($s['created_at'])) ?></span></li>
        <?php endforeach; ?>
      </ul>
      <?php endif; ?>
    </section>
  </div>

  <section class="card col-side">
    <div class="card-head"><h2><?= e(t('child.summary')) ?></h2><a class="small" href="/children/<?= e($child['id']) ?>/emergency"><?= e(t('edit')) ?></a></div>
    <div class="facts">
      <div><span class="label"><?= e(t('field.blood_type')) ?></span><span dir="ltr"><?= e($profile['blood_type'] ?: '—') ?></span></div>
      <div><span class="label"><?= e(t('field.allergies')) ?></span>
        <?php $al = json_decode($profile['allergies'] ?? '[]', true) ?: []; ?>
        <?= $al ? e(implode('، ', array_column($al, 'name'))) : '—' ?></div>
    </div>
    <span class="label muted small" style="font-weight:700"><?= e(t('child.contacts')) ?></span>
    <ul class="list">
      <?php foreach ($contacts as $ct): ?>
        <li><span><?= e($ct['name']) ?><?= $ct['relation'] ? ' · <span class="muted">' . e($ct['relation']) . '</span>' : '' ?></span><span dir="ltr" class="small"><?= e($ct['phone']) ?></span></li>
      <?php endforeach; ?>
      <?php if (!$contacts): ?><li class="muted"><?= e(t('emergency.contact_required')) ?></li><?php endif; ?>
    </ul>
  </section>
</div>
