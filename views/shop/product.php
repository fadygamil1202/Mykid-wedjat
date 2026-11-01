<?php $p = $product; ?>
<p><a href="/shop">← <?= e(t('nav.shop')) ?></a></p>
<div class="product-page">
  <img src="/<?= e($p['image'] ?: 'assets/products/' . $p['type'] . '.svg') ?>" alt="<?= e(loc($p, 'name')) ?>">
  <section class="card">
    <h1 style="margin-bottom:.25rem"><?= e(loc($p, 'name')) ?></h1>
    <p class="price" style="font-size:1.7rem"><?= e(money($p['price'])) ?></p>
    <p><?= nl2br(e(loc($p, 'description'))) ?></p>
    <ul class="list small">
      <li><?= icon('nfc') ?> <?= e(t('shop.f_nfc')) ?></li>
      <li><?= icon('qr') ?> <?= e(t('shop.f_qr')) ?></li>
      <li><?= icon('lock') ?> <?= e(t('shop.f_lock')) ?></li>
      <li><?= icon('box') ?> <?= e(t('shop.f_ship')) ?></li>
    </ul>
    <hr class="divider">
    <?php $avail = product_available($p); ?>
    <?php if ($avail !== null && $avail > 0 && $avail <= low_stock_threshold()): ?>
      <p class="hint warn"><?= icon('alert') ?> <?= e(t('stock.only_n', ['n' => $avail])) ?></p>
    <?php endif; ?>
    <?php if ($avail === 0): ?>
      <div class="flash flash-error" style="margin:0"><?= icon('x') ?> <?= e(t('stock.out_long')) ?></div>
    <?php elseif (!$user): ?>
      <a class="btn btn-primary btn-block" href="/register"><?= e(t('shop.signup_to_buy')) ?></a>
      <p class="center small" style="margin-top:.5rem"><a href="/login"><?= e(t('register.have_account')) ?> <?= e(t('nav.login')) ?></a></p>
    <?php elseif ($user['role'] !== 'parent'): ?>
      <p class="muted"><?= e(t('shop.admin_note')) ?></p>
    <?php elseif (!$children): ?>
      <p class="hint warn"><?= e(t('shop.add_child_first')) ?></p>
      <a class="btn btn-primary btn-block" href="/children/new"><?= e(t('children.new')) ?></a>
    <?php else: ?>
      <form method="post" action="/cart" class="stack">
        <?= csrf_field() ?>
        <input type="hidden" name="product_id" value="<?= e($p['id']) ?>">
        <label><?= e(t('shop.for_child')) ?>
          <select name="child_id" required>
            <?php foreach ($children as $c): ?><option value="<?= e($c['id']) ?>"><?= e($c['first_name']) ?></option><?php endforeach; ?>
          </select>
          <span class="hint"><?= e(t('shop.for_child_hint')) ?></span>
        </label>
        <label class="qty"><?= e(t('field.qty')) ?>
          <select name="qty"><?php for ($i = 1; $i <= min(5, $avail ?? 5); $i++): ?><option><?= $i ?></option><?php endfor; ?></select>
        </label>
        <button class="btn btn-primary btn-block" type="submit"><?= icon('cart') ?> <?= e(t('shop.add_to_cart')) ?></button>
      </form>
    <?php endif; ?>
  </section>
</div>
