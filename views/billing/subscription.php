<h1><?= e(t('nav.subscription')) ?></h1>
<?php require BASE_PATH . '/views/partials/sub_banner.php'; ?>
<?php if ($pending): ?>
  <div class="flash flash-info"><?= e(t('sub.pending', ['amount' => money($pending['amount'])])) ?> <a href="/payments/<?= e($pending['id']) ?>"><?= e(t('sub.pending_link')) ?></a></div>
<?php endif; ?>
<section class="section">
  <div class="section-head"><p><?= e(t('sub.intro')) ?></p></div>
  <?php $canBuy = true; require BASE_PATH . '/views/partials/plan_cards.php'; ?>
</section>
<?php if ($history): ?>
<section class="card">
  <h2><?= e(t('sub.history')) ?></h2>
  <div class="table-wrap"><table class="table-cards">
    <thead><tr><th><?= e(t('field.plan')) ?></th><th><?= e(t('field.status')) ?></th><th><?= e(t('field.from')) ?></th><th><?= e(t('field.to')) ?></th></tr></thead>
    <tbody>
    <?php foreach ($history as $h): $st = sub_state($h); ?>
      <tr><td data-label="<?= e(t('field.plan')) ?>"><?= e(loc($h, 'name')) ?> · <?= e(money($h['price'])) ?></td>
          <td data-label="<?= e(t('field.status')) ?>"><span class="badge badge-<?= e($st) ?>"><?= e(t('sub.state.' . $st)) ?></span></td>
          <td data-label="<?= e(t('field.from')) ?>"><?= e(format_date($h['starts_at'])) ?: '—' ?></td>
          <td data-label="<?= e(t('field.to')) ?>"><?= e(format_date($h['ends_at'])) ?: '—' ?></td></tr>
    <?php endforeach; ?>
    </tbody>
  </table></div>
</section>
<?php endif; ?>
