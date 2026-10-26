<div class="page-head">
  <div>
    <h1><?= e(t('dash.welcome', ['name' => explode(' ', $user['name'])[0]])) ?></h1>
    <p class="muted"><?= e(t('dash.subtitle')) ?></p>
  </div>
</div>

<?php require BASE_PATH . '/views/partials/sub_banner.php'; ?>

<?php if ($unread): ?><div class="flash flash-info"><a href="/notifications"><?= e(t('dash.unread', ['n' => $unread])) ?></a></div><?php endif; ?>

<section class="section" style="margin-top:0">
  <div class="card-head"><h2><?= e(t('dash.my_children')) ?></h2></div>
  <div class="grid-cards">
    <?php foreach ($children as $c): ?>
      <a class="kid" href="/children/<?= e($c['id']) ?>">
        <span class="avatar"><?= e(mb_substr($c['first_name'], 0, 1)) ?></span>
        <span>
          <span class="kid-name"><?= e($c['first_name']) ?> <?= e($c['last_name']) ?></span><br>
          <span class="muted small">
            <?= e(t('dash.devices_n', ['n' => $c['active_tags']])) ?>
            <?php if ($c['locked_tags']): ?> · <span class="badge badge-locked"><?= e(t('dash.locked_n', ['n' => $c['locked_tags']])) ?></span><?php endif; ?>
          </span>
          <?php if (!$c['consent_at']): ?><br><span class="badge badge-pending"><?= e(t('dash.needs_info')) ?></span><?php endif; ?>
        </span>
      </a>
    <?php endforeach; ?>
    <a class="kid add" href="/children/new"><?= icon('plus') ?> <?= e(t('children.new')) ?></a>
  </div>
</section>

<div class="cols">
  <section class="card col-main">
    <div class="card-head"><h2><?= e(t('dash.recent_scans')) ?></h2></div>
    <?php if (!$scans): ?>
      <p class="muted"><?= e(t('dash.no_scans')) ?></p>
    <?php else: ?>
      <ul class="list">
        <?php foreach ($scans as $s): ?>
          <li><span><?= icon($s['action'] === 'call' ? 'phone' : 'eye') ?> <?= e(t('scan.' . $s['action'], ['name' => $s['first_name'], 'type' => t('tag.' . $s['type'])])) ?></span>
              <span class="muted small nowrap"><?= e(format_dt($s['created_at'])) ?></span></li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>
  </section>
  <section class="card col-side">
    <h2><?= e(t('dash.get_devices')) ?></h2>
    <p class="muted"><?= e(t('dash.get_devices_text')) ?></p>
    <div class="row">
      <a class="btn btn-primary" href="/shop"><?= icon('bag') ?> <?= e(t('nav.shop')) ?></a>
      <?php if ($openOrders): ?><a class="btn" href="/orders"><?= e(t('dash.open_orders', ['n' => $openOrders])) ?></a><?php endif; ?>
    </div>
  </section>
</div>
