<?php
/*
 * Front and back of the emergency card.
 * The printed design is the same for every child: no name, blood type or other personal data,
 * so cards can be mass-produced and programmed later. The child's details live behind the link.
 * $qrLink: the real link (admin only) — when null the QR is replaced by a locked placeholder,
 * so the parent's browser never receives it.
 * Optional $locked (device locked), $type (card|bracelet|necklace).
 */
$qrLink ??= null;
?>
<div class="card-stage">
  <div class="card-side">
    <div class="id-card<?= !empty($locked) ? ' is-locked' : '' ?>">
      <div class="band"><span>+ <?= e(t('card.heading')) ?></span><span dir="ltr">SOS</span></div>
      <div class="body">
        <div class="who">
          <div class="name"><?= e(t('card.front_title')) ?></div>
          <div class="tap"><?= e(t('card.tap_text')) ?></div>
        </div>
        <svg class="nfc" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M8 8a6 6 0 0 1 0 8"/><path d="M12 5a10 10 0 0 1 0 14"/><path d="M16 2.5a13.5 13.5 0 0 1 0 19"/><circle cx="4.5" cy="12" r="1.5"/></svg>
      </div>
      <div class="foot"><img src="/assets/logo.svg" alt=""> MyKid Wedjat</div>
      <?php if (!empty($locked)): ?><div class="locked-stamp"><span><?= icon('lock') ?> <?= e(t('tags.status.locked')) ?></span></div><?php endif; ?>
    </div>
    <span class="card-side-label"><?= e(t('card.front')) ?></span>
  </div>
  <div class="card-side">
    <div class="id-card back">
      <div class="body">
        <div class="steps-mini">
          <b><?= e(t('card.back_title')) ?></b>
          1. <?= e(t('card.back_1')) ?><br>
          2. <?= e(t('card.back_2')) ?><br>
          3. <?= e(t('card.back_3')) ?>
        </div>
        <?php if ($qrLink): ?>
          <div class="qr-box" data-qr="<?= e($qrLink) ?>" role="img" aria-label="QR"></div>
        <?php else: ?>
          <div class="qr-hidden" role="img" aria-label="<?= e(t('card.qr_hidden')) ?>"><?= icon('lock') ?></div>
        <?php endif; ?>
      </div>
      <div class="foot" style="color:var(--muted)"><?= e(t('card.back_foot')) ?></div>
    </div>
    <span class="card-side-label"><?= e(t('card.back')) ?></span>
  </div>
</div>
