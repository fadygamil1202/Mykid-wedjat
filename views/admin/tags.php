<div class="page-head">
  <h1><?= e(t('admin.devices')) ?></h1>
  <a class="btn btn-primary btn-sm" href="/admin/devices/generate"><?= icon('plus') ?> <?= e(t('devices.generate')) ?></a>
</div>
<div class="stats">
  <?php foreach ($shelf as $sp): ?>
    <a class="stat" href="/admin/devices?status=unassigned"><?= icon('box') ?><span class="stat-n"><?= e($sp['n']) ?></span><span class="stat-l"><?= e(t('devices.on_shelf_of', ['product' => loc($sp, 'name')])) ?></span></a>
  <?php endforeach; ?>
</div>
<form class="filters" method="get" action="/admin/devices">
  <input type="search" name="q" value="<?= e($search) ?>" placeholder="<?= e(t('devices.search')) ?>">
  <select name="status">
    <option value=""><?= e(t('all')) ?></option>
    <?php foreach (DEVICE_FILTERS as $st): ?><option value="<?= e($st) ?>" <?= $status === $st ? 'selected' : '' ?>><?= e(t('tags.status.' . $st)) ?></option><?php endforeach; ?>
  </select>
  <button class="btn" type="submit"><?= e(t('filter')) ?></button>
</form>
<section class="card">
  <div class="table-wrap"><table class="table-cards">
    <thead><tr><th><?= e(t('devices.serial')) ?></th><th><?= e(t('field.type')) ?></th><th><?= e(t('field.child')) ?></th><th><?= e(t('field.parent')) ?></th><th><?= e(t('field.status')) ?></th><th><?= e(t('admin.s_scans')) ?></th><th><?= e(t('field.date')) ?></th></tr></thead>
    <tbody>
    <?php foreach ($tags as $tg): ?>
      <tr>
        <td data-label="<?= e(t('devices.serial')) ?>"><a class="code" href="/admin/devices/<?= e($tg['id']) ?>" dir="ltr"><?= e(serial_format($tg['serial'])) ?></a></td>
        <td data-label="<?= e(t('field.type')) ?>"><?= e($tg['name_en'] ? loc($tg, 'name') : t('tag.' . $tg['type'])) ?></td>
        <td data-label="<?= e(t('field.child')) ?>"><?= e($tg['first_name'] ? $tg['first_name'] . ' ' . $tg['last_name'] : '—') ?></td>
        <td data-label="<?= e(t('field.parent')) ?>"><?php if ($tg['parent_id']): ?><a href="/admin/users/<?= e($tg['parent_id']) ?>"><?= e($tg['parent_name']) ?></a><?php else: ?>—<?php endif; ?></td>
        <td data-label="<?= e(t('field.status')) ?>"><span class="badge badge-<?= e($tg['status']) ?>"><?= e(t('tags.status.' . $tg['status'])) ?></span></td>
        <td data-label="<?= e(t('admin.s_scans')) ?>"><?= e($tg['scans']) ?></td>
        <td data-label="<?= e(t('field.date')) ?>"><?= e(format_date($tg['assigned_at'] ?: $tg['created_at'])) ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table></div>
  <?php if (!$tags): ?><p class="muted"><?= e(t('admin.nothing')) ?></p><?php endif; ?>
</section>
