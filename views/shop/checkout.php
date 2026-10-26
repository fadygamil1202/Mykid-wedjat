<h1><?= e(t('checkout.title')) ?></h1>
<form method="post" action="/checkout" class="cols">
  <?= csrf_field() ?>
  <input type="hidden" name="back" value="checkout">
  <section class="card col-main stack">
    <?php if (!$plan && $plans): ?><?php $inForm = true; require BASE_PATH . '/views/shop/_plan_upsell.php'; ?><?php endif; ?>
    <h2><?= e(t('checkout.shipping')) ?></h2>
    <div class="two">
      <label><?= e(t('field.name')) ?><input name="ship_name" required autocomplete="name" value="<?= e($last['ship_name'] ?? $user['name']) ?>"></label>
      <label><?= e(t('field.phone')) ?><input name="ship_phone" type="tel" inputmode="tel" required dir="ltr" autocomplete="tel" value="<?= e($last['ship_phone'] ?? $user['phone']) ?>"></label>
    </div>
    <div class="two">
      <label><?= e(t('field.governorate')) ?>
        <select name="ship_governorate" required>
          <option value=""><?= e(t('checkout.pick')) ?></option>
          <?php foreach (GOVERNORATES as $g): ?><option value="<?= e($g) ?>" <?= ($last['ship_governorate'] ?? '') === $g ? 'selected' : '' ?>><?= e(t('gov.' . $g)) ?></option><?php endforeach; ?>
        </select></label>
      <label><?= e(t('field.city')) ?><input name="ship_city" required autocomplete="address-level2" value="<?= e($last['ship_city'] ?? '') ?>"></label>
    </div>
    <label><?= e(t('field.address')) ?><textarea name="ship_address" rows="2" required autocomplete="street-address"><?= e($last['ship_address'] ?? '') ?></textarea></label>
    <label><?= e(t('field.notes')) ?><input name="notes" maxlength="500"></label>
  </section>
  <section class="card col-side">
    <h2><?= e(t('checkout.summary')) ?></h2>
    <ul class="list small">
      <?php foreach ($lines as $l): ?>
        <li><span><?= e(loc($l['product'], 'name')) ?> × <?= e($l['qty']) ?> · <?= e($l['child']['first_name']) ?></span><span><?= e(money($l['total'])) ?></span></li>
      <?php endforeach; ?>
      <?php if ($plan): $removeAction = true; require BASE_PATH . '/views/shop/_plan_line.php'; endif; ?>
    </ul>
    <?php require BASE_PATH . '/views/shop/_totals.php'; ?>
    <?php if ($plan): ?><p class="hint"><?= e(t('cart.plan_starts')) ?></p><?php endif; ?>
    <label style="margin-bottom:1rem"><?= e(t('promo.label')) ?>
      <input name="promo_code" placeholder="<?= e(t('promo.placeholder')) ?>" autocapitalize="characters" autocomplete="off" dir="ltr" maxlength="40"></label>
    <button class="btn btn-primary btn-block" type="submit"><?= e(t('checkout.place')) ?></button>
    <p class="hint center" style="margin-top:.5rem"><?= e(t('checkout.pay_next')) ?></p>
  </section>
</form>
