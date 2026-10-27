<?php
// Page shown instead of the child's information.
// $variant: 'locked' (device locked by the parent) or 'inactive' (no active subscription). $preview: parent's preview.
$variant = ($variant ?? 'locked') === 'inactive' ? 'inactive' : 'locked';
?>
<div class="em">
  <?php if (!empty($preview)): ?>
    <div class="em-preview"><?= e(t('public.preview_banner')) ?> · <a href="/subscription"><?= e(t('sub.subscribe')) ?></a></div>
  <?php endif; ?>
  <header class="em-head">
    <div class="em-top">
      <span class="em-label"><?= icon('lock') ?> <?= e(t("public.{$variant}_label")) ?></span>
      <a class="em-lang" href="/lang/<?= is_rtl() ? 'en' : 'ar' ?>"><?= is_rtl() ? 'English' : 'العربية' ?></a>
    </div>
    <div class="em-name" style="font-size:1.6rem"><?= e(t("public.{$variant}_title")) ?></div>
  </header>
  <main class="em-body">
    <div class="em-card"><p class="em-v" style="margin:0"><?= e(t("public.{$variant}_text")) ?></p></div>
    <div class="em-actions">
      <a class="em-btn em-btn-outline" href="tel:123"><?= icon('alert') ?> <?= e(t('public.call_ambulance')) ?></a>
      <a class="em-btn em-btn-primary" href="tel:122"><?= icon('phone') ?> <?= e(t('public.call_police')) ?></a>
    </div>
  </main>
  <footer class="em-foot"><a class="em-brand" href="/"><img src="/assets/logo.svg" alt=""> MyKid Wedjat</a></footer>
</div>
