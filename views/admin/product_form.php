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
    <fieldset>
      <legend><?= e(t('stock.title')) ?></legend>
      <?php $tracked = $p ? $p['stock'] !== null : true; ?>
      <label class="check"><input type="checkbox" name="track_stock" value="1" <?= $tracked ? 'checked' : '' ?>> <?= e(t('stock.track')) ?></label>
      <?php if ($p && $p['stock'] !== null): ?>
        <p class="hint"><?= e(t('stock.tracked_hint')) ?></p>
      <?php else: ?>
        <label><?= e(t('stock.starting')) ?><input name="stock" type="number" min="0" step="1" inputmode="numeric" value="0" dir="ltr">
          <span class="hint"><?= e(t('stock.track_hint')) ?></span></label>
      <?php endif; ?>
    </fieldset>
    <label class="check"><input type="checkbox" name="is_active" value="1" <?= ($p['is_active'] ?? 1) ? 'checked' : '' ?>> <?= e(t('admin.visible_in_shop')) ?></label>
    <div class="row">
      <button class="btn btn-primary" type="submit"><?= e(t('save')) ?></button>
      <a class="btn btn-ghost" href="/admin/products"><?= e(t('cancel')) ?></a>
    </div>
  </form>
</section>

<?php if ($p && $p['stock'] !== null): ?>
<section class="card narrow" id="stock">
  <div class="card-head"><h2><?= e(t('stock.title')) ?></h2>
    <span class="badge badge-<?= (int)$p['stock'] <= 0 ? 'locked' : ((int)$p['stock'] <= low_stock_threshold() ? 'pending' : 'active') ?>"><?= e(t('stock.in_stock_n', ['n' => (int)$p['stock']])) ?></span></div>
  <?php if ($held > 0): ?><p class="hint"><?= e(t('stock.held_hint', ['n' => $held])) ?></p><?php endif; ?>
  <form method="post" action="/admin/products/<?= e($p['id']) ?>/stock" class="stack"><?= csrf_field() ?>
    <div class="two">
      <label><?= e(t('stock.qty_change')) ?><input name="qty" type="number" step="1" required inputmode="numeric" dir="ltr" placeholder="+50 / -2">
        <span class="hint"><?= e(t('stock.qty_change_hint')) ?></span></label>
      <label><?= e(t('stock.reason')) ?>
        <select name="reason"><option value="received"><?= e(t('stock.reason.received')) ?></option><option value="correction"><?= e(t('stock.reason.correction')) ?></option></select></label>
    </div>
    <label><?= e(t('field.notes')) ?><input name="note" maxlength="190" placeholder="<?= e(t('stock.note_ph')) ?>"></label>
    <button class="btn btn-primary" type="submit"><?= e(t('save')) ?></button>
  </form>
  <h3 style="margin-top:1.25rem"><?= e(t('stock.history')) ?></h3>
  <?php if (!$movements): ?><p class="muted"><?= e(t('admin.nothing')) ?></p><?php endif; ?>
  <ul class="list small">
    <?php foreach ($movements as $m): ?>
      <li><span><strong dir="ltr" style="color:<?= $m['change_qty'] < 0 ? 'var(--coral-dark)' : 'var(--nile-dark)' ?>"><?= $m['change_qty'] > 0 ? '+' : '' ?><?= e($m['change_qty']) ?></strong>
          · <?= e(t('stock.reason.' . $m['reason'])) ?>
          <?php if ($m['order_id']): ?> <a href="/admin/orders/<?= e($m['order_id']) ?>">#<?= e($m['order_id']) ?></a><?php endif; ?>
          <?php if ($m['note']): ?> · <span class="muted"><?= e($m['note']) ?></span><?php endif; ?>
          <?php if ($m['admin_name']): ?> · <span class="muted"><?= e($m['admin_name']) ?></span><?php endif; ?></span>
        <span class="muted nowrap"><?= e(t('stock.after_n', ['n' => $m['stock_after']])) ?> · <?= e(format_dt($m['created_at'])) ?></span></li>
    <?php endforeach; ?>
  </ul>
</section>
<?php endif; ?>
