<?php
// $promo: existing code or null. After a failed save the typed values come back from the session.
$v = $_SESSION['old_promo'] ?? $promo ?? [];
unset($_SESSION['old_promo']);
$date = fn($dt) => $dt ? substr((string)$dt, 0, 10) : '';
?>
<form method="post" action="<?= $promo ? '/admin/promos/' . e($promo['id']) : '/admin/promos' ?>" class="stack">
  <?= csrf_field() ?>
  <div class="two">
    <label><?= e(t('admin.promo_code')) ?>
      <input name="code" required pattern="[A-Za-z0-9_\-]{3,40}" maxlength="40" dir="ltr" autocapitalize="characters" class="code" value="<?= e($v['code'] ?? '') ?>" placeholder="WELCOME20">
      <span class="hint"><?= e(t('admin.promo_code_hint')) ?></span></label>
    <label><?= e(t('admin.promo_note')) ?>
      <input name="description" maxlength="190" value="<?= e($v['description'] ?? '') ?>" placeholder="<?= e(t('admin.promo_note_ph')) ?>"></label>
  </div>
  <div class="two">
    <label><?= e(t('admin.promo_type')) ?>
      <select name="discount_type">
        <option value="percent" <?= ($v['discount_type'] ?? '') === 'percent' ? 'selected' : '' ?>><?= e(t('admin.promo_percent')) ?></option>
        <option value="fixed" <?= ($v['discount_type'] ?? '') === 'fixed' ? 'selected' : '' ?>><?= e(t('admin.promo_fixed', ['cur' => config('currency', 'EGP')])) ?></option>
      </select></label>
    <label><?= e(t('admin.promo_value')) ?>
      <input name="discount_value" type="number" step="0.01" min="0.01" inputmode="decimal" required dir="ltr" value="<?= e($v['discount_value'] ?? '') ?>"></label>
  </div>
  <div class="two">
    <label><?= e(t('admin.promo_applies')) ?>
      <select name="applies_to">
        <?php foreach (['all', 'subscription', 'order'] as $a): ?>
          <option value="<?= $a ?>" <?= ($v['applies_to'] ?? 'all') === $a ? 'selected' : '' ?>><?= e(t('promo.applies.' . $a)) ?></option>
        <?php endforeach; ?>
      </select></label>
    <label><?= e(t('admin.promo_min')) ?>
      <input name="min_amount" type="number" step="0.01" min="0" inputmode="decimal" dir="ltr" value="<?= e($v['min_amount'] ?? '') ?>" placeholder="<?= e(t('admin.promo_none')) ?>"></label>
  </div>
  <div class="two">
    <label><?= e(t('admin.promo_max')) ?>
      <input name="max_uses" type="number" min="1" inputmode="numeric" dir="ltr" value="<?= e($v['max_uses'] ?? '') ?>" placeholder="<?= e(t('admin.promo_unlimited')) ?>"></label>
    <label><?= e(t('admin.promo_max_user')) ?>
      <input name="max_uses_per_user" type="number" min="1" inputmode="numeric" dir="ltr" value="<?= e(array_key_exists('max_uses_per_user', $v) ? $v['max_uses_per_user'] : 1) ?>" placeholder="<?= e(t('admin.promo_unlimited')) ?>"></label>
  </div>
  <div class="two">
    <label><?= e(t('admin.promo_starts')) ?><input name="starts_at" type="date" value="<?= e($date($v['starts_at'] ?? null)) ?>"></label>
    <label><?= e(t('admin.promo_ends')) ?><input name="ends_at" type="date" value="<?= e($date($v['ends_at'] ?? null)) ?>">
      <span class="hint"><?= e(t('admin.promo_dates_hint')) ?></span></label>
  </div>
  <label class="check"><input type="checkbox" name="is_active" value="1" <?= ($v['is_active'] ?? 1) ? 'checked' : '' ?>> <?= e(t('admin.promo_active')) ?></label>
  <div class="row">
    <button class="btn btn-primary" type="submit"><?= e($promo ? t('save') : t('admin.promo_create')) ?></button>
    <?php if ($promo): ?><a class="btn btn-ghost" href="/admin/promos"><?= e(t('cancel')) ?></a><?php endif; ?>
  </div>
</form>
