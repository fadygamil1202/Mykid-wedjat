<?php $flow = ['pending_payment', 'paid', 'processing', 'shipped', 'delivered']; $pos = array_search($order['status'], $flow, true); ?>
<div class="page-head">
  <h1><?= e(t('orders.number', ['id' => $order['id']])) ?></h1>
  <span class="badge badge-<?= e($order['status']) ?>"><?= e(t('order.' . $order['status'])) ?></span>
</div>
<?php if ($pos !== false): ?>
  <div class="timeline" aria-hidden="true"><?php foreach ($flow as $i => $st): ?><span class="<?= $i <= $pos ? 'on' : '' ?>"></span><?php endforeach; ?></div>
<?php endif; ?>
<?php if ($payment && $payment['status'] === 'pending'): ?>
  <div class="flash flash-info"><?= e(t('orders.awaiting_payment')) ?> <a href="/payments/<?= e($payment['id']) ?>"><?= e(t('sub.pending_link')) ?></a></div>
<?php endif; ?>
<div class="cols">
  <section class="card col-main">
    <ul class="list">
      <?php foreach ($items as $it): ?>
        <li><span class="row" style="flex-wrap:nowrap">
              <img src="/<?= e($it['image']) ?>" alt="" width="64" height="48" style="border-radius:10px;object-fit:cover">
              <span><strong><?= e(loc($it, 'name')) ?></strong> × <?= e($it['qty']) ?><br><span class="muted small"><?= e(t('cart.for', ['name' => $it['first_name'] ?? '—'])) ?></span></span></span>
            <strong><?= e(money($it['unit_price'] * $it['qty'])) ?></strong></li>
      <?php endforeach; ?>
    </ul>
    <?php $subtotal = $order['subtotal']; $shipping = $order['shipping_fee']; $discount = $order['discount']; require BASE_PATH . '/views/shop/_totals.php'; ?>
  </section>
  <section class="card col-side">
    <h2><?= e(t('checkout.shipping')) ?></h2>
    <p><?= e($order['ship_name']) ?><br><span dir="ltr"><?= e($order['ship_phone']) ?></span><br>
       <?= e($order['ship_address']) ?><br><?= e($order['ship_city']) ?> — <?= e(t('gov.' . $order['ship_governorate'])) ?></p>
  </section>
</div>
