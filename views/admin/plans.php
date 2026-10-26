<h1><?= e(t('admin.plans')) ?></h1>
<p class="muted"><?= e(t('admin.plans_hint')) ?></p>
<div class="two">
  <?php foreach ($plans as $pl): ?>
    <section class="card">
      <h2><?= e(loc($pl, 'name')) ?> <span class="muted small">(<?= e(t('admin.months_n', ['n' => $pl['interval_months']])) ?>)</span></h2>
      <form method="post" action="/admin/plans/<?= e($pl['id']) ?>" class="stack"><?= csrf_field() ?>
        <div class="two">
          <label><?= e(t('admin.name_en')) ?><input name="name_en" required value="<?= e($pl['name_en']) ?>" dir="ltr"></label>
          <label><?= e(t('admin.name_ar')) ?><input name="name_ar" required value="<?= e($pl['name_ar']) ?>" dir="rtl"></label>
        </div>
        <label><?= e(t('admin.price')) ?> (<?= e(config('currency', 'EGP')) ?>)<input name="price" type="number" step="0.01" min="0" inputmode="decimal" required value="<?= e($pl['price']) ?>" dir="ltr"></label>
        <label class="check"><input type="checkbox" name="is_active" value="1" <?= $pl['is_active'] ? 'checked' : '' ?>> <?= e(t('admin.plan_active')) ?></label>
        <button class="btn btn-primary" type="submit"><?= e(t('save')) ?></button>
      </form>
    </section>
  <?php endforeach; ?>
</div>
