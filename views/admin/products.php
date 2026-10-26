<div class="page-head"><h1><?= e(t('admin.products')) ?></h1><a class="btn btn-primary btn-sm" href="/admin/products/new"><?= icon('plus') ?> <?= e(t('admin.product_new')) ?></a></div>
<div class="grid-cards">
  <?php foreach ($products as $p): ?>
    <a class="product" href="/admin/products/<?= e($p['id']) ?>">
      <img src="/<?= e($p['image'] ?: 'assets/products/' . $p['type'] . '.svg') ?>" alt="">
      <span class="product-body">
        <h3><?= e(loc($p, 'name')) ?></h3>
        <span class="muted small"><?= e(t('tag.' . $p['type'])) ?> · /shop/<?= e($p['slug']) ?></span>
        <span class="row"><span class="price"><?= e(money($p['price'])) ?></span>
          <span class="badge badge-<?= $p['is_active'] ? 'on' : 'off' ?>"><?= e($p['is_active'] ? t('admin.visible') : t('admin.hidden')) ?></span></span>
      </span>
    </a>
  <?php endforeach; ?>
</div>
