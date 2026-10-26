<?php $p = $p ?: []; ?>
<div class="em">
  <?php if (!empty($preview)): ?>
    <div class="em-preview"><?= e(t('public.preview_banner')) ?> · <a href="/children/<?= e($child['id']) ?>"><?= e(t('back')) ?></a></div>
  <?php endif; ?>
  <header class="em-head">
    <div class="em-top">
      <span class="em-label"><?= icon('alert') ?> <?= e(t('public.heading')) ?></span>
      <a class="em-lang" href="/lang/<?= is_rtl() ? 'en' : 'ar' ?>"><?= is_rtl() ? 'English' : 'العربية' ?></a>
    </div>
    <div class="em-person">
      <div class="em-avatar" aria-hidden="true"><?= e(mb_substr($child['first_name'], 0, 1)) ?></div>
      <div>
        <div class="em-name"><?= e($child['first_name']) ?></div>
        <?php if ($age !== null): ?><div class="em-sub"><?= e(t('field.age_n', ['n' => $age])) ?></div><?php endif; ?>
      </div>
    </div>
  </header>

  <main class="em-body">
    <div class="em-actions">
      <?php foreach ($contacts as $i => $ct): ?>
        <?php if (!empty($preview)): ?>
          <button class="em-btn em-btn-primary" type="button" disabled><?= icon('phone') ?> <span><?= e(t('public.call', ['name' => $ct['name']])) ?><?php if ($ct['relation']): ?><small><?= e($ct['relation']) ?></small><?php endif; ?></span></button>
        <?php else: ?>
          <form method="post" action="/e/<?= e($token) ?>/call/<?= e($ct['id']) ?>">
            <?= csrf_field() ?>
            <button class="em-btn em-btn-primary" type="submit"><?= icon('phone') ?> <span><?= e(t('public.call', ['name' => $ct['name']])) ?><?php if ($ct['relation']): ?><small><?= e($ct['relation']) ?></small><?php endif; ?></span></button>
          </form>
        <?php endif; ?>
      <?php endforeach; ?>
      <a class="em-btn em-btn-outline" href="tel:123"><?= icon('alert') ?> <?= e(t('public.call_ambulance')) ?></a>
    </div>

    <?php if ($full): ?>
      <?php if ($p['show_blood_type'] && $p['blood_type']): ?>
      <div class="em-card">
        <div class="em-k"><?= e(t('field.blood_type')) ?></div>
        <div class="em-big" dir="ltr"><?= e($p['blood_type']) ?></div>
      </div>
      <?php endif; ?>

      <?php if ($allergies): ?>
      <div class="em-card">
        <div class="em-k"><?= e(t('field.allergies')) ?></div>
        <div class="chips">
          <?php foreach ($allergies as $a): ?>
            <span class="chip chip-<?= e($a['severity']) ?>"><?= e($a['name']) ?> · <?= e(t('severity.' . $a['severity'])) ?></span>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endif; ?>

      <?php foreach (['conditions', 'medications', 'care_notes'] as $f): ?>
        <?php if ($p['show_' . $f] && $p[$f]): ?>
        <div class="em-card">
          <div class="em-k"><?= e(t('field.' . $f)) ?></div>
          <div class="em-v"><?= nl2br(e($p[$f])) ?></div>
        </div>
        <?php endif; ?>
      <?php endforeach; ?>
    <?php elseif (!empty($preview)): ?>
      <div class="em-note"><?= e($subscribed ? t('public.preview_no_consent') : t('public.preview_no_sub')) ?></div>
    <?php endif; ?>
  </main>

  <footer class="em-foot">
    <p><?= e(t('public.privacy')) ?></p>
    <p><?= e(t('public.logged')) ?></p>
    <a class="em-brand" href="/"><img src="/assets/logo.svg" alt=""> MyKid Wedjat</a>
  </footer>
</div>
