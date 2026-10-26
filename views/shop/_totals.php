<ul class="list">
  <li><span><?= e(t('cart.subtotal')) ?></span><span><?= e(money($subtotal)) ?></span></li>
  <li><span><?= e(t('cart.shipping')) ?></span><span><?= e($shipping > 0 ? money($shipping) : t('cart.free')) ?></span></li>
  <li><strong><?= e(t('cart.total')) ?></strong><strong class="price"><?= e(money($subtotal + $shipping)) ?></strong></li>
</ul>
