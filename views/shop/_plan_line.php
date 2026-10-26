<?php // The subscription line in the cart / checkout: $plan, optional $removeAction (formaction for the checkout form) ?>
<li class="plan-line">
  <span class="row" style="flex-wrap:nowrap">
    <span class="device-icon"><?= icon('shield') ?></span>
    <span><strong><?= e(t('cart.plan_line', ['plan' => loc($plan, 'name')])) ?></strong><br>
      <span class="muted small"><?= e(t('cart.plan_covers')) ?></span></span>
  </span>
  <span class="row">
    <strong><?= e(money($plan['price'])) ?></strong>
    <?php if (!empty($removeAction)): ?>
      <button class="link-btn" type="submit" formaction="/cart/plan/remove" formnovalidate name="back" value="checkout" aria-label="<?= e(t('remove')) ?>"><?= icon('x') ?></button>
    <?php else: ?>
      <form method="post" action="/cart/plan/remove"><?= csrf_field() ?>
        <button class="link-btn" type="submit" aria-label="<?= e(t('remove')) ?>"><?= icon('x') ?></button></form>
    <?php endif; ?>
  </span>
</li>
