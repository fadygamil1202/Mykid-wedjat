<?php
// "Add a subscription" box: $plans, $sub (active subscription or null).
// $inForm: inside the checkout form, so the buttons post to /cart/plan with formaction.
$best = null;
foreach ($plans as $pl) if ((int)$pl['interval_months'] > 1) $best = $pl['id'];
?>
<div class="upsell">
  <strong><?= icon('shield') ?> <?= e($sub ? t('cart.upsell_renew') : t('cart.upsell_title')) ?></strong>
  <p class="small"><?= e($sub ? t('cart.upsell_renew_text', ['date' => format_date($sub['ends_at'])]) : t('cart.upsell_text')) ?></p>
  <div class="row">
    <?php foreach ($plans as $pl): ?>
      <?php if (!empty($inForm)): ?>
        <button class="btn btn-sm <?= $pl['id'] === $best ? 'btn-gold' : '' ?>" type="submit" formaction="/cart/plan" formnovalidate name="plan_id" value="<?= e($pl['id']) ?>">
          + <?= e(loc($pl, 'name')) ?> · <?= e(money($pl['price'])) ?></button>
      <?php else: ?>
        <form method="post" action="/cart/plan"><?= csrf_field() ?>
          <button class="btn btn-sm <?= $pl['id'] === $best ? 'btn-gold' : '' ?>" type="submit" name="plan_id" value="<?= e($pl['id']) ?>">
            + <?= e(loc($pl, 'name')) ?> · <?= e(money($pl['price'])) ?></button></form>
      <?php endif; ?>
    <?php endforeach; ?>
  </div>
</div>
