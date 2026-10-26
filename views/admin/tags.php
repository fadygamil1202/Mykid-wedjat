<h1><?= e(t('admin.devices')) ?></h1>
<form class="filters" method="get" action="/admin/devices">
  <input type="search" name="q" value="<?= e($search) ?>" placeholder="<?= e(t('admin.search_devices')) ?>">
  <button class="btn" type="submit"><?= e(t('filter')) ?></button>
</form>
<section class="card">
  <div class="table-wrap"><table class="table-cards">
    <thead><tr><th>#</th><th><?= e(t('field.type')) ?></th><th><?= e(t('field.child')) ?></th><th><?= e(t('field.parent')) ?></th><th><?= e(t('field.status')) ?></th><th><?= e(t('admin.s_scans')) ?></th><th><?= e(t('field.date')) ?></th></tr></thead>
    <tbody>
    <?php foreach ($tags as $tg): ?>
      <tr>
        <td data-label="#"><a href="/admin/devices/<?= e($tg['id']) ?>"><strong><?= e($tg['id']) ?></strong></a></td>
        <td data-label="<?= e(t('field.type')) ?>"><?= e(t('tag.' . $tg['type'])) ?></td>
        <td data-label="<?= e(t('field.child')) ?>"><?= e($tg['first_name'] . ' ' . $tg['last_name']) ?></td>
        <td data-label="<?= e(t('field.parent')) ?>"><a href="/admin/users/<?= e($tg['parent_id']) ?>"><?= e($tg['parent_name']) ?></a></td>
        <td data-label="<?= e(t('field.status')) ?>"><span class="badge badge-<?= e($tg['status']) ?>"><?= e(t('tags.status.' . $tg['status'])) ?></span></td>
        <td data-label="<?= e(t('admin.s_scans')) ?>"><?= e($tg['scans']) ?></td>
        <td data-label="<?= e(t('field.date')) ?>"><?= e(format_date($tg['created_at'])) ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table></div>
  <?php if (!$tags): ?><p class="muted"><?= e(t('admin.nothing')) ?></p><?php endif; ?>
</section>
