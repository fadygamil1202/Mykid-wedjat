<a class="product" href="/shop/<?= e($p['slug']) ?>">
  <img src="/<?= e($p['image'] ?: 'assets/products/' . $p['type'] . '.svg') ?>" alt="" loading="lazy">
  <span class="product-body">
    <h3><?= e(loc($p, 'name')) ?></h3>
    <span class="muted small"><?= e(t('tag.' . $p['type'])) ?> · NFC + QR</span>
    <span class="price"><?= e(money($p['price'])) ?></span>
    <?php $avail = product_available($p); ?>
    <?php if ($avail === 0): ?><span class="badge badge-off"><?= e(t('stock.out')) ?></span>
    <?php elseif ($avail !== null && $avail <= low_stock_threshold()): ?><span class="badge badge-pending"><?= e(t('stock.only_n', ['n' => $avail])) ?></span><?php endif; ?>
  </span>
</a>
