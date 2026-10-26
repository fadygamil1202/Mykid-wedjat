<h1><?= e(t('cart.title')) ?></h1>
<?php if (!$lines && !$plan): ?>
  <section class="card center">
    <p class="muted"><?= e(t('cart.empty')) ?></p>
    <div class="row" style="justify-content:center">
      <a class="btn btn-primary" href="/shop"><?= e(t('nav.shop')) ?></a>
      <a class="btn" href="/subscription"><?= e(t('nav.subscription')) ?></a>
    </div>
  </section>
<?php else: ?>
<div class="cols">
  <section class="card col-main">
    <ul class="list">
      <?php foreach ($lines as $key => $l): ?>
        <li>
          <span class="row" style="flex-wrap:nowrap">
            <img src="/<?= e($l['product']['image']) ?>" alt="" width="64" height="48" style="border-radius:10px;object-fit:cover">
            <span><strong><?= e(loc($l['product'], 'name')) ?></strong> × <?= e($l['qty']) ?><br>
              <span class="muted small"><?= e(t('cart.for', ['name' => $l['child']['first_name']])) ?></span></span>
          </span>
          <span class="row">
            <strong><?= e(money($l['total'])) ?></strong>
            <form method="post" action="/cart/<?= e($key) ?>/remove"><?= csrf_field() ?>
              <button class="link-btn" type="submit" aria-label="<?= e(t('remove')) ?>"><?= icon('x') ?></button></form>
          </span>
        </li>
      <?php endforeach; ?>
      <?php if ($plan) require BASE_PATH . '/views/shop/_plan_line.php'; ?>
    </ul>
    <a href="/shop">+ <?= e(t('cart.continue')) ?></a>
    <?php if (!$plan && $plans): ?><?php $inForm = false; require BASE_PATH . '/views/shop/_plan_upsell.php'; ?><?php endif; ?>
  </section>
  <section class="card col-side">
    <?php require BASE_PATH . '/views/shop/_totals.php'; ?>
    <?php if ($lines): ?>
      <a class="btn btn-primary btn-block" href="/checkout"><?= e(t('cart.checkout')) ?></a>
      <?php if ($plan): ?><p class="hint center" style="margin-top:.5rem"><?= e(t('cart.one_payment')) ?></p><?php endif; ?>
    <?php else: ?>
      <!-- Subscription only: nothing to deliver, so go straight to payment -->
      <form method="post" action="/subscription"><?= csrf_field() ?>
        <button class="btn btn-primary btn-block" type="submit" name="plan_id" value="<?= e($plan['id']) ?>"><?= e(t('cart.pay_now')) ?></button></form>
    <?php endif; ?>
  </section>
</div>
<?php endif; ?>
