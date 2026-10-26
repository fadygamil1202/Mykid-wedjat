<div class="page-head">
  <div><h1><?= e(t('orders.number', ['id' => $order['id']])) ?></h1><p class="muted"><?= e(format_dt($order['created_at'])) ?></p></div>
  <span class="badge badge-<?= e($order['status']) ?>"><?= e(t('order.' . $order['status'])) ?></span>
</div>
<div class="cols">
  <div class="col-main">
    <section class="card">
      <h2><?= e(t('field.items')) ?></h2>
      <?php foreach ($items as $it): ?>
        <div class="device">
          <span class="device-icon"><?= icon($it['type'] === 'card' ? 'card' : 'nfc') ?></span>
          <div class="device-main">
            <strong><?= e(loc($it, 'name')) ?> × <?= e($it['qty']) ?></strong>
            <span class="muted small"><?= e(t('cart.for', ['name' => trim(($it['first_name'] ?? '') . ' ' . ($it['last_name'] ?? '')) ?: '—'])) ?> · <?= e(money($it['unit_price'] * $it['qty'])) ?></span>
            <div class="small"><?= e(t('admin.issued_n', ['n' => $it['issued'], 'qty' => $it['qty']])) ?>
              <?php foreach ($it['tags'] as $tg): ?> <a href="/admin/devices/<?= e($tg['id']) ?>">#<?= e($tg['id']) ?></a><?php endforeach; ?></div>
          </div>
          <?php if ($it['issued'] < $it['qty'] && $it['child_id']): ?>
            <form method="post" action="/admin/order-items/<?= e($it['id']) ?>/issue"><?= csrf_field() ?>
              <button class="btn btn-sm btn-primary" type="submit"><?= icon('nfc') ?> <?= e(t('admin.issue')) ?></button></form>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
      <?php $subtotal = $order['subtotal']; $shipping = $order['shipping_fee']; $discount = $order['discount']; require BASE_PATH . '/views/shop/_totals.php'; ?>
      <?php if ($payment): ?>
        <p class="small"><?= e(t('pay.title')) ?>: <strong dir="ltr">MKW-<?= e($payment['id']) ?></strong> <span class="badge badge-<?= e($payment['status']) ?>"><?= e(t('pay.status.' . $payment['status'])) ?></span>
        <?php if ($payment['status'] === 'pending'): ?><a href="/admin/payments?status=pending"><?= e(t('admin.mark_paid')) ?></a><?php endif; ?></p>
      <?php endif; ?>
    </section>
  </div>
  <div class="col-side">
    <section class="card">
      <h2><?= e(t('checkout.shipping')) ?></h2>
      <p><?= e($order['ship_name']) ?><br><a href="tel:<?= e(clean_phone($order['ship_phone'])) ?>" dir="ltr"><?= e($order['ship_phone']) ?></a><br>
        <?= e($order['ship_address']) ?><br><?= e($order['ship_city']) ?> — <?= e(t('gov.' . $order['ship_governorate'])) ?></p>
      <?php if ($order['notes']): ?><p class="hint"><?= e($order['notes']) ?></p><?php endif; ?>
      <p class="small"><?= e(t('field.parent')) ?>: <a href="/admin/users/<?= e($order['user_id']) ?>"><?= e($order['name']) ?></a></p>
    </section>
    <section class="card">
      <h2><?= e(t('admin.change_status')) ?></h2>
      <form method="post" action="/admin/orders/<?= e($order['id']) ?>/status" class="stack"><?= csrf_field() ?>
        <select name="status"><?php foreach (ORDER_STATUSES as $s): ?><option value="<?= e($s) ?>" <?= $order['status'] === $s ? 'selected' : '' ?>><?= e(t('order.' . $s)) ?></option><?php endforeach; ?></select>
        <button class="btn btn-primary" type="submit"><?= e(t('save')) ?></button>
        <span class="hint"><?= e(t('admin.status_notifies')) ?></span>
      </form>
    </section>
  </div>
</div>
