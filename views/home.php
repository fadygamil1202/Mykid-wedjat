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
    <div class="id-card">
      <div class="band"><span>+ <?= e(t('card.heading')) ?></span><span dir="ltr">SOS</span></div>
      <div class="body">
        <div class="who">
          <div class="name"><?= e(t('card.front_title')) ?></div>
          <div class="tap"><?= e(t('card.tap_text')) ?></div>
        </div>
        <svg class="nfc" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M8 8a6 6 0 0 1 0 8"/><path d="M12 5a10 10 0 0 1 0 14"/><path d="M16 2.5a13.5 13.5 0 0 1 0 19"/><circle cx="4.5" cy="12" r="1.5"/></svg>
      </div>
      <div class="foot"><img src="/assets/logo.svg" alt=""> MyKid Wedjat</div>
    </div>
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
