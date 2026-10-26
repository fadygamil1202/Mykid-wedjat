<div class="page-head"><h1><?= e(t('admin.orders')) ?></h1>
  <form method="get" action="/admin/orders" class="row">
    <select name="status" style="min-height:42px;width:auto">
      <option value=""><?= e(t('all')) ?></option>
      <?php foreach (ORDER_STATUSES as $s): ?><option value="<?= e($s) ?>" <?= $status === $s ? 'selected' : '' ?>><?= e(t('order.' . $s)) ?></option><?php endforeach; ?>
    </select>
    <button class="btn btn-sm" type="submit"><?= e(t('filter')) ?></button>
  </form>
</div>
<section class="card">
  <div class="table-wrap"><table class="table-cards">
    <thead><tr><th>#</th><th><?= e(t('field.parent')) ?></th><th><?= e(t('field.items')) ?></th><th><?= e(t('cart.total')) ?></th><th><?= e(t('field.governorate')) ?></th><th><?= e(t('field.status')) ?></th><th><?= e(t('field.date')) ?></th></tr></thead>
    <tbody>
    <?php foreach ($orders as $o): ?>
      <tr>
        <td data-label="#"><a href="/admin/orders/<?= e($o['id']) ?>"><strong><?= e($o['id']) ?></strong></a></td>
        <td data-label="<?= e(t('field.parent')) ?>"><?= e($o['name']) ?></td>
        <td data-label="<?= e(t('field.items')) ?>"><?= e($o['items']) ?></td>
        <td data-label="<?= e(t('cart.total')) ?>"><?= e(money($o['total'])) ?></td>
        <td data-label="<?= e(t('field.governorate')) ?>"><?= e(t('gov.' . $o['ship_governorate'])) ?></td>
        <td data-label="<?= e(t('field.status')) ?>"><span class="badge badge-<?= e($o['status']) ?>"><?= e(t('order.' . $o['status'])) ?></span></td>
        <td data-label="<?= e(t('field.date')) ?>"><?= e(format_dt($o['created_at'])) ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table></div>
  <?php if (!$orders): ?><p class="muted"><?= e(t('admin.nothing')) ?></p><?php endif; ?>
</section>
