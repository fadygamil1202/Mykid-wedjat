<div class="page-head">
  <div><h1><?= e(t('card.title')) ?> — <?= e($child['first_name']) ?></h1><p class="muted"><?= e(t('card.intro')) ?></p></div>
  <a class="btn btn-sm btn-ghost" href="/children/<?= e($child['id']) ?>"><?= e(t('back')) ?></a>
</div>
<section class="card">
  <?php $qrLink = null; require BASE_PATH . '/views/partials/id_card.php'; ?>
  <div class="hint warn"><?= icon('lock') ?> <?= e(t('card.qr_hidden_text')) ?></div>
</section>
<div class="row">
  <a class="btn btn-primary" href="/children/<?= e($child['id']) ?>/preview"><?= icon('eye') ?> <?= e(t('child.preview')) ?></a>
  <a class="btn" href="/shop"><?= icon('bag') ?> <?= e(t('card.order')) ?></a>
</div>
