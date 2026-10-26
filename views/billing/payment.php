<section class="card narrow" style="margin:0 auto">
  <h1><?= e(t('pay.title')) ?></h1>
  <div class="facts">
    <div><span class="label"><?= e(t('pay.for')) ?></span><?= e($what) ?></div>
    <div><span class="label"><?= e(t('pay.amount')) ?></span>
      <?php if ((float)$payment['discount'] > 0): ?><s class="muted"><?= e(money($payment['original_amount'])) ?></s><?php endif; ?>
      <span class="price"><?= e(money($payment['amount'])) ?></span></div>
    <div><span class="label"><?= e(t('pay.ref')) ?></span><strong dir="ltr">MKW-<?= e($payment['id']) ?></strong></div>
    <div><span class="label"><?= e(t('field.status')) ?></span><span class="badge badge-<?= e($payment['status']) ?>"><?= e(t('pay.status.' . $payment['status'])) ?></span></div>
  </div>
  <?php if ((float)$payment['discount'] > 0): ?>
    <div class="promo-applied">
      <span><?= icon('check') ?> <?= e(t('promo.code_label')) ?> <strong dir="ltr"><?= e($payment['promo_code']) ?></strong> · −<?= e(money($payment['discount'])) ?></span>
      <?php if ($payment['status'] === 'pending'): ?>
        <form method="post" action="/payments/<?= e($payment['id']) ?>/promo/remove"><?= csrf_field() ?>
          <button class="link-btn small" type="submit"><?= e(t('remove')) ?></button></form>
      <?php endif; ?>
    </div>
  <?php elseif ($payment['status'] === 'pending'): ?>
    <form method="post" action="/payments/<?= e($payment['id']) ?>/promo" class="promo-form">
      <?= csrf_field() ?>
      <input name="promo_code" placeholder="<?= e(t('promo.placeholder')) ?>" aria-label="<?= e(t('promo.placeholder')) ?>" autocapitalize="characters" autocomplete="off" dir="ltr" maxlength="40">
      <button class="btn" type="submit"><?= e(t('promo.apply')) ?></button>
    </form>
  <?php endif; ?>
  <?php if ($payment['status'] === 'pending'):
    $online = online_available($payment);
    $manual = manual_payment_enabled($payment); ?>
    <?php if (!$online && online_gateway()): ?>
      <div class="flash flash-info"><?= e(t('online.below_min', ['min' => money(online_min_amount()), 'name' => online_gateway_name()])) ?></div>
    <?php endif; ?>
    <?php if ($online): ?>
      <form method="post" action="/payments/<?= e($payment['id']) ?>/pay" class="pay-online">
        <?= csrf_field() ?>
        <button class="btn btn-primary btn-block" type="submit"><?= icon('card') ?> <?= e(t('online.pay', ['amount' => money($payment['amount'])])) ?></button>
        <p class="hint center"><?= icon('lock') ?> <?= e(t('online.methods', ['name' => online_gateway_name()])) ?></p>
      </form>
    <?php endif; ?>
    <?php if ($manual): ?>
      <div class="hint warn" style="font-size:1rem">
        <strong><?= e($online ? t('online.or_manual') : t('pay.how')) ?></strong><br>
        <?= e(config('payment.instructions_' . current_lang(), config('payment.instructions_en', ''))) ?>
        <br><?= e(t('pay.use_ref', ['ref' => 'MKW-' . $payment['id']])) ?>
      </div>
    <?php endif; ?>
  <?php elseif ($payment['status'] === 'failed' || $payment['status'] === 'refunded'): ?>
    <div class="flash flash-error"><?= e(t('pay.failed_text')) ?></div>
  <?php elseif ($payment['status'] === 'paid'): ?>
    <div class="flash flash-success"><?= e(t('pay.thanks')) ?></div>
  <?php endif; ?>
  <div class="row" style="margin-top:1rem">
    <a class="btn btn-primary" href="<?= $payment['kind'] === 'order' ? '/orders/' . e($payment['ref_id']) : ($payment['kind'] === 'subscription' ? '/subscription' : '/dashboard') ?>"><?= e(t('continue')) ?></a>
    <?php if (config('support_phone')): ?><a class="btn" href="tel:<?= e(clean_phone((string)config('support_phone'))) ?>"><?= icon('phone') ?> <?= e(t('pay.support')) ?></a><?php endif; ?>
  </div>
</section>
