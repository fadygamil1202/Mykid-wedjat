<section class="card narrow" style="margin:0 auto">
  <h1><?= e(t('pay.title')) ?></h1>
  <div class="facts">
    <div><span class="label"><?= e(t('pay.for')) ?></span><?= e($what) ?></div>
    <div><span class="label"><?= e(t('pay.amount')) ?></span><span class="price"><?= e(money($payment['amount'])) ?></span></div>
    <div><span class="label"><?= e(t('pay.ref')) ?></span><strong dir="ltr">MKW-<?= e($payment['id']) ?></strong></div>
    <div><span class="label"><?= e(t('field.status')) ?></span><span class="badge badge-<?= e($payment['status']) ?>"><?= e(t('pay.status.' . $payment['status'])) ?></span></div>
  </div>
  <?php if ($payment['status'] === 'pending'): ?>
    <div class="hint warn" style="font-size:1rem">
      <strong><?= e(t('pay.how')) ?></strong><br>
      <?= e(config('payment.instructions_' . current_lang(), config('payment.instructions_en', ''))) ?>
      <br><?= e(t('pay.use_ref', ['ref' => 'MKW-' . $payment['id']])) ?>
    </div>
    <p class="hint" style="margin-top:.75rem"><?= e(t('pay.online_soon')) ?></p>
  <?php elseif ($payment['status'] === 'paid'): ?>
    <div class="flash flash-success"><?= e(t('pay.thanks')) ?></div>
  <?php endif; ?>
  <div class="row" style="margin-top:1rem">
    <a class="btn btn-primary" href="<?= $payment['kind'] === 'order' ? '/orders/' . e($payment['ref_id']) : ($payment['kind'] === 'subscription' ? '/subscription' : '/dashboard') ?>"><?= e(t('continue')) ?></a>
    <?php if (config('support_phone')): ?><a class="btn" href="tel:<?= e(clean_phone((string)config('support_phone'))) ?>"><?= icon('phone') ?> <?= e(t('pay.support')) ?></a><?php endif; ?>
  </div>
</section>
