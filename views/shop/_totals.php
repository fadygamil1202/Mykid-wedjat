<?php
// $subtotal (products), $shipping; optional $planPrice (subscription in the same order), $discount
$planPrice = (float)($planPrice ?? 0);
$discount = (float)($discount ?? 0);
?>
<ul class="list">
  <?php if ($subtotal > 0): ?><li><span><?= e(t('cart.subtotal')) ?></span><span><?= e(money($subtotal)) ?></span></li><?php endif; ?>
  <?php if ($planPrice > 0): ?><li><span><?= e(t('cart.subscription')) ?></span><span><?= e(money($planPrice)) ?></span></li><?php endif; ?>
  <?php if ($subtotal > 0): ?><li><span><?= e(t('cart.shipping')) ?></span><span><?= e($shipping > 0 ? money($shipping) : t('cart.free')) ?></span></li><?php endif; ?>
  <?php if ($discount > 0): ?><li><span><?= e(t('promo.discount')) ?></span><span>−<?= e(money($discount)) ?></span></li><?php endif; ?>
  <li><strong><?= e(t('cart.total')) ?></strong><strong class="price"><?= e(money($subtotal + $planPrice + $shipping - $discount)) ?></strong></li>
</ul>
