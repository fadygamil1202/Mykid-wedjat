<?php $p = $product; ?>
<h1><?= e($p ? t('admin.product_edit') : t('admin.product_new')) ?></h1>
<section class="card narrow">
  <form method="post" action="<?= $p ? '/admin/products/' . e($p['id']) : '/admin/products' ?>" enctype="multipart/form-data" class="stack">
    <?= csrf_field() ?>
    <div class="two">
      <label><?= e(t('admin.name_en')) ?><input name="name_en" required value="<?= e($p['name_en'] ?? '') ?>" dir="ltr"></label>
      <label><?= e(t('admin.name_ar')) ?><input name="name_ar" required value="<?= e($p['name_ar'] ?? '') ?>" dir="rtl"></label>
    </div>
    <label><?= e(t('admin.desc_en')) ?><textarea name="description_en" rows="3" dir="ltr"><?= e($p['description_en'] ?? '') ?></textarea></label>
    <label><?= e(t('admin.desc_ar')) ?><textarea name="description_ar" rows="3" dir="rtl"><?= e($p['description_ar'] ?? '') ?></textarea></label>
    <div class="two">
      <label><?= e(t('field.type')) ?>
        <select name="type"><?php foreach (TAG_TYPES as $ty): ?><option value="<?= e($ty) ?>" <?= ($p['type'] ?? '') === $ty ? 'selected' : '' ?>><?= e(t('tag.' . $ty)) ?></option><?php endforeach; ?></select></label>
      <label><?= e(t('admin.price')) ?> (<?= e(config('currency', 'EGP')) ?>)<input name="price" type="number" step="0.01" min="0" inputmode="decimal" required value="<?= e($p['price'] ?? '') ?>" dir="ltr"></label>
    </div>
    <div class="two">
      <label><?= e(t('admin.slug')) ?><input name="slug" required pattern="[a-z0-9-]{2,80}" value="<?= e($p['slug'] ?? '') ?>" dir="ltr"><span class="hint"><?= e(t('admin.slug_hint')) ?></span></label>
      <label><?= e(t('admin.sort')) ?><input name="sort_order" type="number" value="<?= e($p['sort_order'] ?? 0) ?>" dir="ltr"></label>
    </div>
    <label><?= e(t('admin.image')) ?>
      <?php if (!empty($p['image'])): ?><img src="/<?= e($p['image']) ?>" alt="" style="max-width:200px;border-radius:12px"><?php endif; ?>
      <input type="file" name="image" accept="image/jpeg,image/png,image/webp"><span class="hint"><?= e(t('admin.image_hint')) ?></span></label>
    <label class="check"><input type="checkbox" name="is_active" value="1" <?= ($p['is_active'] ?? 1) ? 'checked' : '' ?>> <?= e(t('admin.visible_in_shop')) ?></label>
    <div class="row">
      <button class="btn btn-primary" type="submit"><?= e(t('save')) ?></button>
      <a class="btn btn-ghost" href="/admin/products"><?= e(t('cancel')) ?></a>
    </div>
  </form>
</section>
