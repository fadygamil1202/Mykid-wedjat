<div class="auth-wrap">
  <div class="card auth">
    <span class="device-icon" style="width:56px;height:56px;margin-bottom:.75rem"><?= icon('nfc') ?></span>
    <h1><?= e(t('activate.title')) ?></h1>
    <p class="muted"><?= e(t('activate.text')) ?></p>
    <?php if (!$children): ?>
      <p class="hint warn"><?= e(t('shop.add_child_first')) ?></p>
      <a class="btn btn-primary btn-block" href="/children/new"><?= e(t('children.new')) ?></a>
    <?php else: ?>
    <form method="post" action="/devices/activate" class="stack">
      <?= csrf_field() ?>
      <label><?= e(t('devices.serial')) ?>
        <input name="serial" required dir="ltr" autocapitalize="characters" autocomplete="off" spellcheck="false" class="code" placeholder="XXXX-XXXX-XX" maxlength="20">
        <span class="hint"><?= e(t('activate.serial_hint')) ?></span></label>
      <label><?= e(t('activate.for_child')) ?>
        <select name="child_id" required><?php foreach ($children as $c): ?><option value="<?= e($c['id']) ?>" <?= $childId === (int)$c['id'] ? 'selected' : '' ?>><?= e($c['first_name']) ?></option><?php endforeach; ?></select></label>
      <button class="btn btn-primary" type="submit"><?= icon('check') ?> <?= e(t('activate.submit')) ?></button>
      <p class="hint"><?= e(t('activate.sub_note')) ?></p>
    </form>
    <?php endif; ?>
  </div>
</div>
