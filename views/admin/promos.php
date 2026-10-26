<div class="page-head">
  <div><h1><?= e(t('admin.promos')) ?></h1><p class="muted"><?= e(t('admin.promos_intro')) ?></p></div>
  <a class="btn btn-primary btn-sm" href="#new"><?= icon('plus') ?> <?= e(t('admin.promo_new')) ?></a>
</div>
<section class="card">
  <div class="table-wrap"><table class="table-cards">
    <thead><tr><th><?= e(t('admin.promo_code')) ?></th><th><?= e(t('promo.discount')) ?></th><th><?= e(t('admin.promo_uses')) ?></th>
      <th><?= e(t('admin.promo_given')) ?></th><th><?= e(t('admin.promo_valid')) ?></th><th><?= e(t('field.status')) ?></th><th></th></tr></thead>
    <tbody>
    <?php foreach ($promos as $pc):
      $expired = $pc['ends_at'] && strtotime($pc['ends_at']) < time();
      $usedUp = $pc['max_uses'] !== null && ($pc['used'] + $pc['waiting']) >= $pc['max_uses'];
      $state = !$pc['is_active'] ? 'off' : ($expired ? 'expired' : ($usedUp ? 'used_up' : 'on'));
    ?>
      <tr class="<?= $state === 'on' ? '' : 'inactive' ?>">
        <td data-label="<?= e(t('admin.promo_code')) ?>"><a class="code" href="/admin/promos/<?= e($pc['id']) ?>" dir="ltr"><?= e($pc['code']) ?></a>
          <?php if ($pc['description']): ?><br><span class="muted small"><?= e($pc['description']) ?></span><?php endif; ?></td>
        <td data-label="<?= e(t('promo.discount')) ?>"><?php require BASE_PATH . '/views/admin/_promo_summary.php'; ?></td>
        <td data-label="<?= e(t('admin.promo_uses')) ?>"><?= e($pc['used']) ?><?= $pc['max_uses'] !== null ? ' / ' . e($pc['max_uses']) : '' ?>
          <?php if ($pc['waiting']): ?><br><span class="muted small"><?= e(t('admin.promo_waiting', ['n' => $pc['waiting']])) ?></span><?php endif; ?></td>
        <td data-label="<?= e(t('admin.promo_given')) ?>"><?= e(money($pc['given'])) ?></td>
        <td data-label="<?= e(t('admin.promo_valid')) ?>" class="small"><?= e($pc['starts_at'] ? format_date($pc['starts_at']) : '…') ?> → <?= e($pc['ends_at'] ? format_date($pc['ends_at']) : '…') ?></td>
        <td data-label="<?= e(t('field.status')) ?>"><span class="badge badge-<?= $state === 'on' ? 'active' : ($state === 'off' ? 'off' : 'pending') ?>"><?= e(t('admin.promo_state.' . $state)) ?></span></td>
        <td data-label="">
          <div class="row">
            <a class="btn btn-sm" href="/admin/promos/<?= e($pc['id']) ?>"><?= icon('edit') ?> <?= e(t('edit')) ?></a>
            <form method="post" action="/admin/promos/<?= e($pc['id']) ?>/toggle"><?= csrf_field() ?>
              <button class="btn btn-sm btn-ghost" type="submit"><?= e($pc['is_active'] ? t('admin.promo_pause') : t('admin.promo_resume')) ?></button></form>
          </div>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table></div>
  <?php if (!$promos): ?><p class="muted"><?= e(t('admin.promo_none_yet')) ?></p><?php endif; ?>
</section>

<section class="card narrow" id="new">
  <h2><?= e(t('admin.promo_new')) ?></h2>
  <?php require BASE_PATH . '/views/admin/_promo_form.php'; ?>
</section>
