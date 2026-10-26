<ul class="list">
  <li><span><?= e(t('cart.subtotal')) ?></span><span><?= e(money($subtotal)) ?></span></li>
  <li><span><?= e(t('cart.shipping')) ?></span><span><?= e($shipping > 0 ? money($shipping) : t('cart.free')) ?></span></li>
  <?php $discount = (float)($discount ?? 0); ?>
  <?php if ($discount > 0): ?><li><span><?= e(t('promo.discount')) ?></span><span>−<?= e(money($discount)) ?></span></li><?php endif; ?>
  <li><strong><?= e(t('cart.total')) ?></strong><strong class="price"><?= e(money($subtotal + $shipping - $discount)) ?></strong></li>
</ul>
