<h1><?= e(t('nav.orders')) ?></h1>
<?php if (!$orders): ?>
  <section class="card center"><p class="muted"><?= e(t('orders.none')) ?></p><a class="btn btn-primary" href="/shop"><?= e(t('nav.shop')) ?></a></section>
<?php else: ?>
<section class="card">
  <div class="table-wrap"><table class="table-cards">
    <thead><tr><th>#</th><th><?= e(t('field.date')) ?></th><th><?= e(t('field.items')) ?></th><th><?= e(t('cart.total')) ?></th><th><?= e(t('field.status')) ?></th><th></th></tr></thead>
    <tbody>
    <?php foreach ($orders as $o): ?>
      <tr><td data-label="#"><?= e($o['id']) ?></td>
          <td data-label="<?= e(t('field.date')) ?>"><?= e(format_date($o['created_at'])) ?></td>
          <td data-label="<?= e(t('field.items')) ?>"><?= e($o['items']) ?></td>
          <td data-label="<?= e(t('cart.total')) ?>"><?= e(money($o['total'])) ?></td>
          <td data-label="<?= e(t('field.status')) ?>"><span class="badge badge-<?= e($o['status']) ?>"><?= e(t('order.' . $o['status'])) ?></span></td>
          <td data-label=""><a href="/orders/<?= e($o['id']) ?>"><?= e(t('view')) ?></a></td></tr>
    <?php endforeach; ?>
    </tbody>
  </table></div>
</section>
<?php endif; ?>
