<?php
/*
 * Front of the emergency card. One bilingual design (Arabic + English) for every child:
 * no name, blood type or other personal data, so cards can be mass-produced and
 * programmed later. The text is fixed on purpose: it is what gets printed, whatever
 * language the website is shown in.
 * Optional $locked (device locked).
 */
?>
<div class="id-card front<?= !empty($locked) ? ' is-locked' : '' ?>" dir="rtl" lang="ar">
  <div class="band"><span>+ طوارئ <span class="en" dir="ltr" lang="en">EMERGENCY</span></span><span dir="ltr">SOS</span></div>
  <div class="body">
    <div class="who">
      <div class="kicker">لو محتاج مساعدة · <span dir="ltr" lang="en">If I need help</span></div>
      <div class="headline">حط الكارت على ضهر موبايلك</div>
      <div class="en-line" dir="ltr" lang="en">Put this card on the back of your phone</div>
    </div>
    <svg class="nfc" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M16 8a6 6 0 0 0 0 8"/><path d="M12 5a10 10 0 0 0 0 14"/><path d="M8 2.5a13.5 13.5 0 0 0 0 19"/><circle cx="19.5" cy="12" r="1.5"/></svg>
  </div>
  <div class="foot">
    <span class="card-brand"><img src="/assets/logo.svg" alt=""><span dir="ltr" lang="en">MyKid <b>Wedjat</b></span></span>
    <span class="order">
      <span class="order-text"><b>اطلب الآن</b><span dir="ltr" lang="en">Order now</span></span>
      <span class="qr-mini" data-qr="<?= e(site_base() . '/shop') ?>" role="img" aria-label="QR: <?= e(site_base() . '/shop') ?>"></span>
    </span>
  </div>
  <?php if (!empty($locked)): ?><div class="locked-stamp"><span><?= icon('lock') ?> <?= e(t('tags.status.locked')) ?></span></div><?php endif; ?>
</div>
<?php if (empty($GLOBALS['qr_script_loaded'])): $GLOBALS['qr_script_loaded'] = true; ?><script src="/assets/qrcode.js"></script><?php endif; ?>
