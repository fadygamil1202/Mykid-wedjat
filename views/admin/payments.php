<div class="page-head"><h1><?= e(t('admin.payments')) ?></h1>
  <div class="row">
    <?php foreach (['' => t('all'), 'pending' => t('pay.status.pending'), 'paid' => t('pay.status.paid'), 'failed' => t('pay.status.failed'), 'refunded' => t('pay.status.refunded')] as $k => $label): ?>
      <a class="btn btn-sm <?= $status === $k ? 'btn-primary' : '' ?>" href="/admin/payments<?= $k ? '?status=' . e($k) : '' ?>"><?= e($label) ?></a>
    <?php endforeach; ?>
  </div>
</div>
<section class="card">
  <div class="table-wrap"><table class="table-cards">
    <thead><tr><th><?= e(t('pay.ref')) ?></th><th><?= e(t('field.parent')) ?></th><th><?= e(t('pay.for')) ?></th><th><?= e(t('pay.amount')) ?></th><th><?= e(t('field.status')) ?></th><th><?= e(t('field.date')) ?></th><th></th></tr></thead>
    <tbody>
    <?php foreach ($payments as $p): ?>
      <tr>
        <td data-label="<?= e(t('pay.ref')) ?>" dir="ltr">MKW-<?= e($p['id']) ?></td>
        <td data-label="<?= e(t('field.parent')) ?>"><a href="/admin/users/<?= e($p['user_id']) ?>"><?= e($p['name']) ?></a></td>
        <td data-label="<?= e(t('pay.for')) ?>"><?= e(t('pay.kind.' . $p['kind'])) ?><?php if ($p['kind'] === 'order'): ?> <a href="/admin/orders/<?= e($p['ref_id']) ?>">#<?= e($p['ref_id']) ?></a><?php endif; ?></td>
        <td data-label="<?= e(t('pay.amount')) ?>"><?= e(money($p['amount'])) ?>
          <?php if ((float)$p['discount'] > 0): ?><br><span class="muted small"><span class="code" dir="ltr"><?= e($p['promo_code']) ?></span> −<?= e(money($p['discount'])) ?></span><?php endif; ?></td>
        <td data-label="<?= e(t('field.status')) ?>"><span class="badge badge-<?= e($p['status']) ?>"><?= e(t('pay.status.' . $p['status'])) ?></span><?php if ($p['provider_ref']): ?><br><span class="muted small"><?= e($p['provider_ref']) ?></span><?php endif; ?></td>
        <td data-label="<?= e(t('field.date')) ?>"><?= e(format_dt($p['paid_at'] ?: $p['created_at'])) ?></td>
        <td data-label="">
          <?php if ($p['status'] === 'pending'): ?>
            <form method="post" action="/admin/payments/<?= e($p['id']) ?>/paid" class="row" data-confirm="<?= e(t('admin.confirm_paid')) ?>"><?= csrf_field() ?>
              <input type="hidden" name="back" value="admin/payments?status=pending">
              <input name="provider_ref" placeholder="<?= e(t('admin.transfer_ref')) ?>" style="min-height:40px;max-width:150px">
              <button class="btn btn-sm btn-primary" type="submit"><?= e(t('admin.mark_paid')) ?></button></form>
            <form method="post" action="/admin/payments/<?= e($p['id']) ?>/failed" data-confirm="<?= e(t('admin.confirm_failed')) ?>"><?= csrf_field() ?>
              <button class="link-btn small" type="submit"><?= e(t('admin.mark_failed')) ?></button></form>
          <?php elseif ($p['status'] === 'paid'): ?>
            <form method="post" action="/admin/payments/<?= e($p['id']) ?>/refunded" data-confirm="<?= e(t('admin.confirm_refund')) ?>"><?= csrf_field() ?>
              <button class="link-btn small" type="submit"><?= e(t('admin.mark_refunded')) ?></button></form>
          <?php endif; ?>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table></div>
  <?php if (!$payments): ?><p class="muted"><?= e(t('admin.nothing')) ?></p><?php endif; ?>
</section>
