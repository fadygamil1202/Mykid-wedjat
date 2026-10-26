<?php
// $plans; optional $canBuy (parent signed in), $sub (active subscription)
$monthly = null;
foreach ($plans as $pl) if ((int)$pl['interval_months'] === 1) $monthly = (float)$pl['price'];
?>
<div class="plans">
  <?php foreach ($plans as $pl):
    $months = (int)$pl['interval_months'];
    $saving = ($monthly && $months > 1) ? round(100 - ($pl['price'] / ($monthly * $months)) * 100) : 0;
  ?>
  <div class="plan<?= $months > 1 ? ' best' : '' ?>">
    <?php if ($saving > 0): ?><span class="plan-tag"><?= e(t('plans.save', ['n' => $saving])) ?></span><?php endif; ?>
    <h3><?= e(loc($pl, 'name')) ?></h3>
    <div class="plan-price"><?= e(money($pl['price'])) ?> <small>/ <?= e($months === 1 ? t('plans.month') : ($months === 12 ? t('plans.year') : t('plans.n_months', ['n' => $months]))) ?></small></div>
    <?php if ($months > 1 && $monthly): ?><p class="muted small"><?= e(t('plans.per_month', ['price' => money(round($pl['price'] / $months, 2))])) ?></p><?php endif; ?>
    <ul>
      <li><?= icon('check') ?> <?= e(t('plans.f1')) ?></li>
      <li><?= icon('check') ?> <?= e(t('plans.f2')) ?></li>
      <li><?= icon('check') ?> <?= e(t('plans.f3')) ?></li>
      <li><?= icon('check') ?> <?= e(t('plans.f4')) ?></li>
    </ul>
    <?php if (!empty($canBuy)): ?>
      <form method="post" action="/subscription"><?= csrf_field() ?>
        <input type="hidden" name="plan_id" value="<?= e($pl['id']) ?>">
        <button class="btn <?= $months > 1 ? 'btn-gold' : 'btn-primary' ?> btn-block" type="submit"><?= e(!empty($sub) ? t('plans.renew') : t('plans.choose')) ?></button>
      </form>
    <?php else: ?>
      <a class="btn <?= $months > 1 ? 'btn-gold' : 'btn-primary' ?> btn-block" href="/register"><?= e(t('plans.start')) ?></a>
    <?php endif; ?>
  </div>
  <?php endforeach; ?>
</div>
