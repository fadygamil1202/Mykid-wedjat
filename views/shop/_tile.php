<a class="product" href="/shop/<?= e($p['slug']) ?>">
  <img src="/<?= e($p['image'] ?: 'assets/products/' . $p['type'] . '.svg') ?>" alt="" loading="lazy">
  <span class="product-body">
    <h3><?= e(loc($p, 'name')) ?></h3>
    <span class="muted small"><?= e(t('tag.' . $p['type'])) ?> · NFC + QR</span>
    <span class="price"><?= e(money($p['price'])) ?></span>
  </span>
</a>
