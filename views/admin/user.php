<div class="page-head">
  <div><h1><?= e($u['name']) ?></h1><p class="muted"><span dir="ltr"><?= e($u['email']) ?></span> · <span dir="ltr"><?= e($u['phone']) ?></span> · <?= e(t('role.' . $u['role'])) ?></p></div>
  <form method="post" action="/admin/users/<?= e($u['id']) ?>/toggle" data-confirm="<?= e(t('admin.toggle_confirm')) ?>"><?= csrf_field() ?>
    <button class="btn btn-sm <?= $u['is_active'] ? 'btn-danger' : 'btn-primary' ?>" type="submit"><?= e($u['is_active'] ? t('admin.disable') : t('admin.enable')) ?></button></form>
</div>
<?php if ($u['role'] === 'parent'): ?>
<?php require BASE_PATH . '/views/partials/sub_banner.php'; ?>
<div class="cols">
  <div class="col-main">
    <section class="card">
      <h2><?= e(t('dash.my_children')) ?></h2>
      <ul class="list">
        <?php foreach ($children as $c): ?><li><span><?= e($c['first_name'] . ' ' . $c['last_name']) ?></span><span class="muted small"><?= e($c['date_of_birth']) ?></span></li><?php endforeach; ?>
        <?php if (!$children): ?><li class="muted"><?= e(t('admin.nothing')) ?></li><?php endif; ?>
      </ul>
    </section>
    <section class="card">
      <h2><?= e(t('admin.devices')) ?></h2>
      <ul class="list">
        <?php foreach ($tags as $tg): ?><li><a href="/admin/devices/<?= e($tg['id']) ?>">#<?= e($tg['id']) ?> · <?= e(t('tag.' . $tg['type'])) ?> · <?= e($tg['first_name']) ?></a><span class="badge badge-<?= e($tg['status']) ?>"><?= e(t('tags.status.' . $tg['status'])) ?></span></li><?php endforeach; ?>
        <?php if (!$tags): ?><li class="muted"><?= e(t('admin.nothing')) ?></li><?php endif; ?>
      </ul>
    </section>
    <section class="card">
      <h2><?= e(t('admin.payments')) ?></h2>
      <ul class="list">
        <?php foreach ($payments as $p): ?><li><span>MKW-<?= e($p['id']) ?> · <?= e(t('pay.kind.' . $p['kind'])) ?> · <?= e(money($p['amount'])) ?></span><span class="badge badge-<?= e($p['status']) ?>"><?= e(t('pay.status.' . $p['status'])) ?></span></li><?php endforeach; ?>
        <?php if (!$payments): ?><li class="muted"><?= e(t('admin.nothing')) ?></li><?php endif; ?>
      </ul>
    </section>
  </div>
  <div class="col-side">
    <section class="card">
      <h2><?= e(t('admin.grant')) ?></h2>
      <p class="hint"><?= e(t('admin.grant_hint')) ?></p>
      <form method="post" action="/admin/users/<?= e($u['id']) ?>/grant" class="stack" data-confirm="<?= e(t('admin.grant_confirm')) ?>"><?= csrf_field() ?>
        <select name="plan_id"><?php foreach ($plans as $pl): ?><option value="<?= e($pl['id']) ?>"><?= e(loc($pl, 'name')) ?></option><?php endforeach; ?></select>
        <button class="btn btn-gold" type="submit"><?= e(t('admin.grant_btn')) ?></button>
      </form>
    </section>
    <section class="card">
      <h2><?= e(t('admin.orders')) ?></h2>
      <ul class="list">
        <?php foreach ($orders as $o): ?><li><a href="/admin/orders/<?= e($o['id']) ?>">#<?= e($o['id']) ?> · <?= e(money($o['total'])) ?></a><span class="badge badge-<?= e($o['status']) ?>"><?= e(t('order.' . $o['status'])) ?></span></li><?php endforeach; ?>
        <?php if (!$orders): ?><li class="muted"><?= e(t('admin.nothing')) ?></li><?php endif; ?>
      </ul>
    </section>
  </div>
</div>
<?php endif; ?>
