<h1><?= e(t('cart.title')) ?></h1>
<?php if (!$lines): ?>
  <section class="card center">
    <p class="muted"><?= e(t('cart.empty')) ?></p>
    <a class="btn btn-primary" href="/shop"><?= e(t('nav.shop')) ?></a>
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
    </ul>
    <a href="/shop">+ <?= e(t('cart.continue')) ?></a>
  </section>
  <section class="card col-side">
    <?php require BASE_PATH . '/views/shop/_totals.php'; ?>
    <a class="btn btn-primary btn-block" href="/checkout"><?= e(t('cart.checkout')) ?></a>
  </section>
</div>
<?php endif; ?>
