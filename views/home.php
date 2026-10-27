<section class="hero">
  <div>
    <h1><?= t('home.hero_title') ?></h1>
    <p><?= e(t('home.hero_text')) ?></p>
    <div class="row">
      <a class="btn btn-gold" href="/register"><?= e(t('home.cta')) ?> <?= icon('arrow') ?></a>
      <a class="btn btn-ghost" href="/shop"><?= e(t('home.cta_shop')) ?></a>
    </div>
  </div>
  <div class="hero-art">
    <?php require BASE_PATH . '/views/partials/id_card_front.php'; ?>
  </div>
</section>

<section class="section">
  <div class="section-head"><h2><?= e(t('home.how_title')) ?></h2><p><?= e(t('home.how_text')) ?></p></div>
  <div class="steps">
    <div class="step"><?= icon('child') ?><h3><?= e(t('home.s1_t')) ?></h3><p class="muted"><?= e(t('home.s1')) ?></p></div>
    <div class="step"><?= icon('nfc') ?><h3><?= e(t('home.s2_t')) ?></h3><p class="muted"><?= e(t('home.s2')) ?></p></div>
    <div class="step"><?= icon('phone') ?><h3><?= e(t('home.s3_t')) ?></h3><p class="muted"><?= e(t('home.s3')) ?></p></div>
    <div class="step"><?= icon('lock') ?><h3><?= e(t('home.s4_t')) ?></h3><p class="muted"><?= e(t('home.s4')) ?></p></div>
  </div>
</section>

<?php if ($products): ?>
<section class="section">
  <div class="section-head"><h2><?= e(t('home.products_title')) ?></h2><p><?= e(t('home.products_text')) ?></p></div>
  <div class="grid-cards">
    <?php foreach ($products as $p): require BASE_PATH . '/views/shop/_tile.php'; endforeach; ?>
  </div>
</section>
<?php endif; ?>

<section class="section">
  <div class="section-head"><h2><?= e(t('home.plans_title')) ?></h2><p><?= e(t('home.plans_text')) ?></p></div>
  <?php require BASE_PATH . '/views/partials/plan_cards.php'; ?>
</section>
