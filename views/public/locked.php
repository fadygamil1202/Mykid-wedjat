<div class="em">
  <header class="em-head">
    <div class="em-top">
      <span class="em-label"><?= icon('lock') ?> <?= e(t('public.locked_label')) ?></span>
      <a class="em-lang" href="/lang/<?= is_rtl() ? 'en' : 'ar' ?>"><?= is_rtl() ? 'English' : 'العربية' ?></a>
    </div>
    <div class="em-name" style="font-size:1.6rem"><?= e(t('public.locked_title')) ?></div>
  </header>
  <main class="em-body">
    <div class="em-card"><p class="em-v" style="margin:0"><?= e(t('public.locked_text')) ?></p></div>
    <div class="em-actions">
      <a class="em-btn em-btn-outline" href="tel:123"><?= icon('alert') ?> <?= e(t('public.call_ambulance')) ?></a>
      <a class="em-btn em-btn-primary" href="tel:122"><?= icon('phone') ?> <?= e(t('public.call_police')) ?></a>
    </div>
  </main>
  <footer class="em-foot"><a class="em-brand" href="/"><img src="/assets/logo.svg" alt=""> MyKid Wedjat</a></footer>
</div>
