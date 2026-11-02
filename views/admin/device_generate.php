<?php $pre = (int)($_GET['product'] ?? 0); ?>
<div class="page-head"><div><h1><?= e(t('devices.generate')) ?></h1><p class="muted"><?= e(t('devices.generate_intro')) ?></p></div>
  <a class="btn btn-sm btn-ghost" href="/admin/devices"><?= e(t('admin.devices')) ?></a></div>
<section class="card narrow">
  <form method="post" action="/admin/devices/generate" class="stack" data-confirm="<?= e(t('devices.generate_confirm')) ?>"><?= csrf_field() ?>
    <div class="two">
      <label><?= e(t('devices.product')) ?>
        <select name="product_id" required><?php foreach ($products as $p): ?><option value="<?= e($p['id']) ?>" <?= $pre === (int)$p['id'] ? 'selected' : '' ?>><?= e(loc($p, 'name')) ?></option><?php endforeach; ?></select></label>
      <label><?= e(t('field.qty')) ?><input name="qty" type="number" min="1" max="1000" step="1" required inputmode="numeric" dir="ltr" value="50"></label>
    </div>
    <label><?= e(t('field.notes')) ?><input name="note" maxlength="190" placeholder="<?= e(t('devices.note_ph')) ?>"></label>
    <label class="check"><input type="checkbox" name="online" value="1" checked> <?= e(t('devices.online_batch')) ?></label>
    <p class="hint" style="margin-top:-.5rem"><?= e(t('devices.online_batch_hint')) ?></p>
    <button class="btn btn-primary" type="submit"><?= icon('plus') ?> <?= e(t('devices.generate')) ?></button>
    <p class="hint"><?= e(t('devices.generate_hint')) ?></p>
  </form>
</section>
<section class="card">
  <h2><?= e(t('devices.batches')) ?></h2>
  <?php if (!$batches): ?><p class="muted"><?= e(t('admin.nothing')) ?></p><?php endif; ?>
  <div class="table-wrap"><table class="table-cards">
    <thead><tr><th>#</th><th><?= e(t('devices.product')) ?></th><th><?= e(t('field.qty')) ?></th><th><?= e(t('devices.on_shelf')) ?></th><th><?= e(t('devices.used')) ?></th><th><?= e(t('field.date')) ?></th><th></th></tr></thead>
    <tbody>
    <?php foreach ($batches as $bt): ?>
      <tr id="batch-<?= e($bt['id']) ?>">
        <td data-label="#"><?= e($bt['id']) ?><?php if ($bt['note']): ?><br><span class="muted small"><?= e($bt['note']) ?></span><?php endif; ?></td>
        <td data-label="<?= e(t('devices.product')) ?>"><?= e(loc($bt, 'name')) ?><br>
          <span class="badge badge-<?= $bt['online'] ? 'info' : 'off' ?>"><?= e($bt['online'] ? t('devices.channel_online') : t('devices.channel_store')) ?></span></td>
        <td data-label="<?= e(t('field.qty')) ?>"><?= e($bt['qty']) ?></td>
        <td data-label="<?= e(t('devices.on_shelf')) ?>"><?= e((int)$bt['on_shelf']) ?></td>
        <td data-label="<?= e(t('devices.used')) ?>"><?= e((int)$bt['used']) ?><?= (int)$bt['voided'] ? ' · ' . e(t('devices.void_n', ['n' => (int)$bt['voided']])) : '' ?></td>
        <td data-label="<?= e(t('field.date')) ?>"><?= e(format_dt($bt['created_at'])) ?><br><span class="muted small"><?= e($bt['admin_name']) ?></span></td>
        <td data-label=""><a class="btn btn-sm" href="/admin/batches/<?= e($bt['id']) ?>/csv"><?= icon('box') ?> CSV</a></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table></div>
  <p class="hint warn"><?= e(t('devices.csv_secret')) ?></p>
</section>
