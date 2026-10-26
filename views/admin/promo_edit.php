<div class="page-head">
  <div><h1><?= e(t('admin.promo_edit')) ?> · <span class="code" dir="ltr"><?= e($promo['code']) ?></span></h1>
    <p class="muted"><?php $pc = $promo; require BASE_PATH . '/views/admin/_promo_summary.php'; ?></p></div>
  <form method="post" action="/admin/promos/<?= e($promo['id']) ?>/delete" data-confirm="<?= e(t('admin.promo_delete_confirm')) ?>"><?= csrf_field() ?>
    <button class="btn btn-sm btn-danger" type="submit"><?= e(t('delete')) ?></button></form>
</div>
<div class="cols">
  <section class="card col-main">
    <?php if ($payments): ?><p class="hint warn"><?= e(t('admin.promo_edit_note')) ?></p><?php endif; ?>
    <?php require BASE_PATH . '/views/admin/_promo_form.php'; ?>
  </section>
  <section class="card col-side">
    <h2><?= e(t('admin.promo_history')) ?></h2>
    <?php if (!$payments): ?><p class="muted"><?= e(t('admin.promo_not_used')) ?></p><?php endif; ?>
    <ul class="list">
      <?php foreach ($payments as $p): ?>
        <li><span><a href="/admin/users/<?= e($p['user_id']) ?>"><?= e($p['name']) ?></a><br>
              <span class="muted small" dir="ltr">MKW-<?= e($p['id']) ?></span> · <span class="small"><?= e(t('pay.kind.' . $p['kind'])) ?> · −<?= e(money($p['discount'])) ?></span></span>
            <span class="badge badge-<?= e($p['status']) ?>"><?= e(t('pay.status.' . $p['status'])) ?></span></li>
      <?php endforeach; ?>
    </ul>
  </section>
</div>
