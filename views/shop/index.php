<div class="section-head"><h1><?= e(t('nav.shop')) ?></h1><p><?= e(t('shop.intro')) ?></p></div>
<div class="grid-cards">
  <?php foreach ($products as $p): require BASE_PATH . '/views/shop/_tile.php'; endforeach; ?>
</div>
<?php if (!$products): ?><p class="center muted"><?= e(t('shop.empty')) ?></p><?php endif; ?>
