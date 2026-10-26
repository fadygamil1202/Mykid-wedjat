<?php $u = current_user(); ?>
<div class="section-head"><h1><?= e(t('nav.pricing')) ?></h1><p><?= e(t('home.plans_text')) ?></p></div>
<?php $canBuy = $u && $u['role'] === 'parent'; $sub = $canBuy ? active_subscription((int)$u['id']) : null;
require BASE_PATH . '/views/partials/plan_cards.php'; ?>
<p class="center muted small" style="margin-top:1.5rem"><?= e(t('plans.note')) ?></p>
