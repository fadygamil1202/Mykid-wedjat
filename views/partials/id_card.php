<?php
/*
 * Front and back of the emergency card, in one bilingual design (Arabic + English).
 * The printed design is the same for every child: no name, blood type or other personal data,
 * so cards can be mass-produced and programmed later. The child's details live behind the link.
 * $qrLink: the real link (admin only) — when null the QR is replaced by a locked placeholder,
 * so the parent's browser never receives it.
 * Optional $locked (device locked).
 */
$qrLink ??= null;
?>
<div class="card-stage">
  <div class="card-side">
    <?php require BASE_PATH . '/views/partials/id_card_front.php'; ?>
    <span class="card-side-label"><?= e(t('card.front')) ?></span>
  </div>
  <div class="card-side">
    <div class="id-card back" dir="rtl" lang="ar">
      <div class="body">
        <div class="steps-mini">
          <b>لقيتني؟ ساعدني من فضلك</b>
          <span class="en" dir="ltr" lang="en">Found me? Please help</span>
          <ol>
            <li>حط الكارت على ضهر موبايلك<span class="en" dir="ltr" lang="en">Put this card on your phone</span></li>
            <li>أو امسح كود الـ QR<span class="en" dir="ltr" lang="en">Or scan the QR code</span></li>
            <li>اضغط "اتصال" عشان تكلم أهلي<span class="en" dir="ltr" lang="en">Press "Call" to reach my family</span></li>
          </ol>
        </div>
        <?php if ($qrLink): ?>
          <div class="qr-box" data-qr="<?= e($qrLink) ?>" role="img" aria-label="QR"></div>
        <?php else: ?>
          <div class="qr-hidden" role="img" aria-label="<?= e(t('card.qr_hidden')) ?>"><?= icon('lock') ?></div>
        <?php endif; ?>
      </div>
      <div class="foot"><span>من غير تطبيق · الإسعاف ١٢٣</span><span aria-hidden="true">|</span><span dir="ltr" lang="en">No app needed · Emergency 123</span></div>
    </div>
    <span class="card-side-label"><?= e(t('card.back')) ?></span>
  </div>
</div>
