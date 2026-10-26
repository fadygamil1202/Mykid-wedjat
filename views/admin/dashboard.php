<div class="page-head"><div><h1><?= e(t('admin.dashboard')) ?></h1><p class="muted"><?= e(date('Y-m-d')) ?></p></div></div>

<div class="todo">
  <a class="<?= $todo['payments'] ? 'hot' : '' ?>" href="/admin/payments?status=pending"><span class="n"><?= (int)$todo['payments'] ?></span> <?= e(t('admin.todo_payments')) ?></a>
  <a class="<?= $todo['orders'] ? 'hot' : '' ?>" href="/admin/orders?status=paid"><span class="n"><?= (int)$todo['orders'] ?></span> <?= e(t('admin.todo_orders')) ?></a>
  <a class="<?= $todo['requests'] ? 'hot' : '' ?>" href="/admin/requests"><span class="n"><?= (int)$todo['requests'] ?></span> <?= e(t('admin.todo_requests')) ?></a>
  <a href="/admin/subscriptions"><span class="n"><?= (int)$todo['expiring'] ?></span> <?= e(t('admin.todo_expiring')) ?></a>
</div>

<div class="stats">
  <a class="stat" href="/admin/users"><?= icon('users') ?><span class="stat-n"><?= (int)$stats['parents'] ?></span><span class="stat-l"><?= e(t('admin.s_parents')) ?></span></a>
  <div class="stat nile"><?= icon('child') ?><span class="stat-n"><?= (int)$stats['children'] ?></span><span class="stat-l"><?= e(t('admin.s_children')) ?></span></div>
  <a class="stat gold" href="/admin/subscriptions"><?= icon('shield') ?><span class="stat-n"><?= (int)$stats['subs'] ?></span><span class="stat-l"><?= e(t('admin.s_subs')) ?></span></a>
  <a class="stat" href="/admin/devices"><?= icon('nfc') ?><span class="stat-n"><?= (int)$stats['devices'] ?></span><span class="stat-l"><?= e(t('admin.s_devices')) ?></span></a>
  <a class="stat gold" href="/admin/payments?status=paid"><?= icon('money') ?><span class="stat-n"><?= e(money($stats['revenue'])) ?></span><span class="stat-l"><?= e(t('admin.s_revenue')) ?></span></a>
  <div class="stat nile"><?= icon('chart') ?><span class="stat-n"><?= e(money($stats['revenue_all'])) ?></span><span class="stat-l"><?= e(t('admin.s_revenue_all')) ?></span></div>
  <div class="stat coral"><?= icon('eye') ?><span class="stat-n"><?= (int)$stats['scans'] ?></span><span class="stat-l"><?= e(t('admin.s_scans')) ?></span></div>
  <div class="stat"><?= icon('plus') ?><span class="stat-n"><?= (int)$stats['new_parents'] ?></span><span class="stat-l"><?= e(t('admin.s_new')) ?></span></div>
</div>

<div class="cols">
  <section class="card col-main">
    <h2><?= e(t('admin.revenue_6m')) ?></h2>
    <?php
      $months = [];
      for ($i = 5; $i >= 0; $i--) $months[date('Y-m', strtotime("first day of -{$i} month"))] = 0;
      foreach ($monthly as $m) if (isset($months[$m['m']])) $months[$m['m']] = (float)$m['total'];
      $max = max(1, max($months));
    ?>
    <div class="bars" role="img" aria-label="<?= e(t('admin.revenue_6m')) ?>">
      <?php foreach ($months as $m => $v): ?>
        <div class="bar-col">
          <span class="v"><?= e(number_format($v)) ?></span>
          <span class="bar" style="height:<?= round($v / $max * 100) ?>%"></span>
          <span class="m"><?= e(substr($m, 5)) ?>/<?= e(substr($m, 2, 2)) ?></span>
        </div>
      <?php endforeach; ?>
    </div>
  </section>
  <section class="card col-side">
    <div class="card-head"><h2><?= e(t('admin.pending_payments')) ?></h2><a class="small" href="/admin/payments?status=pending"><?= e(t('view_all')) ?></a></div>
    <?php if (!$payments): ?><p class="muted"><?= e(t('admin.nothing')) ?></p><?php endif; ?>
    <ul class="list">
      <?php foreach ($payments as $p): ?>
        <li><span><strong>MKW-<?= e($p['id']) ?></strong> · <?= e($p['name']) ?><br><span class="muted small"><?= e(t('pay.kind.' . $p['kind'])) ?> · <?= e(money($p['amount'])) ?></span></span>
          <form method="post" action="/admin/payments/<?= e($p['id']) ?>/paid" data-confirm="<?= e(t('admin.confirm_paid')) ?>"><?= csrf_field() ?>
            <input type="hidden" name="back" value="admin"><button class="btn btn-sm btn-primary" type="submit"><?= icon('check') ?> <?= e(t('admin.mark_paid')) ?></button></form></li>
      <?php endforeach; ?>
    </ul>
  </section>
</div>

<div class="cols">
  <section class="card col-main">
    <div class="card-head"><h2><?= e(t('admin.latest_orders')) ?></h2><a class="small" href="/admin/orders"><?= e(t('view_all')) ?></a></div>
    <div class="table-wrap"><table class="table-cards">
      <thead><tr><th>#</th><th><?= e(t('field.parent')) ?></th><th><?= e(t('cart.total')) ?></th><th><?= e(t('field.status')) ?></th></tr></thead>
      <tbody>
      <?php foreach ($orders as $o): ?>
        <tr><td data-label="#"><a href="/admin/orders/<?= e($o['id']) ?>"><?= e($o['id']) ?></a></td><td data-label="<?= e(t('field.parent')) ?>"><?= e($o['name']) ?></td>
            <td data-label="<?= e(t('cart.total')) ?>"><?= e(money($o['total'])) ?></td>
            <td data-label="<?= e(t('field.status')) ?>"><span class="badge badge-<?= e($o['status']) ?>"><?= e(t('order.' . $o['status'])) ?></span></td></tr>
      <?php endforeach; ?>
      </tbody>
    </table></div>
    <?php if (!$orders): ?><p class="muted"><?= e(t('admin.nothing')) ?></p><?php endif; ?>
  </section>
  <section class="card col-side">
    <h2><?= e(t('admin.recent_scans')) ?></h2>
    <?php if (!$scans): ?><p class="muted"><?= e(t('dash.no_scans')) ?></p><?php endif; ?>
    <ul class="list">
      <?php foreach ($scans as $s): ?>
        <li><span><?= icon($s['action'] === 'call' ? 'phone' : 'eye') ?> <?= e($s['first_name'] . ' ' . $s['last_name']) ?> · <span class="muted"><?= e(t('tag.' . $s['type'])) ?></span>
              <?php if ($s['tag_status'] !== 'active'): ?><span class="badge badge-locked"><?= e(t('tags.status.' . $s['tag_status'])) ?></span><?php endif; ?></span>
            <span class="muted small nowrap"><?= e(format_dt($s['created_at'])) ?></span></li>
      <?php endforeach; ?>
    </ul>
  </section>
</div>
