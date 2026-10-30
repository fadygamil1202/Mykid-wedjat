<?php
$u = current_user();
$path = '/' . trim((string)parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH), '/');
if (BASE_URL !== '' && str_starts_with(rawurldecode($path), BASE_URL)) $path = substr(rawurldecode($path), strlen(BASE_URL)) ?: '/';
// "/" and "/admin" match exactly; other links also match their sub-pages
$on = fn(string $href) => (in_array($href, ['/', '/admin'], true) ? $path === $href : str_starts_with($path, $href)) ? 'on' : '';
$unread = $u ? (int)q_one('SELECT COUNT(*) n FROM notifications WHERE user_id=? AND is_read=0', [$u['id']])['n'] : 0;
$isAdmin = $u && $u['role'] === 'admin';
$links = match (true) {
    $isAdmin => [
        ['/admin', 'chart', t('admin.dashboard')], ['/admin/users', 'users', t('admin.users')],
        ['/admin/orders', 'box', t('admin.orders')], ['/admin/payments', 'money', t('admin.payments')],
        ['/admin/devices', 'nfc', t('admin.devices')], ['/admin/requests', 'refresh', t('admin.requests')],
        ['/admin/subscriptions', 'shield', t('admin.subscriptions')], ['/admin/promos', 'money', t('admin.promos')],
        ['/admin/products', 'bag', t('admin.products')],
        ['/admin/plans', 'settings', t('admin.plans')],
    ],
    (bool)$u => [
        ['/dashboard', 'home', t('nav.dashboard')], ['/shop', 'bag', t('nav.shop')],
        ['/subscription', 'shield', t('nav.subscription')], ['/orders', 'box', t('nav.orders')],
    ],
    default => [['/', 'home', t('nav.home')], ['/shop', 'bag', t('nav.shop')], ['/pricing', 'shield', t('nav.pricing')]],
};
?>
<!doctype html>
<html lang="<?= e(current_lang()) ?>" dir="<?= is_rtl() ? 'rtl' : 'ltr' ?>">
<head>
<?php require BASE_PATH . '/views/partials/head.php'; ?>
<title><?= e($title ?? t('app.name')) ?> · <?= e(t('app.name')) ?></title>
</head>
<body>
<header class="topbar">
  <div class="topbar-in">
    <a class="brand" href="<?= $u ? '/' . home_path($u) : '/' ?>">
      <img src="/assets/logo.svg" alt="" width="38" height="38">
      <span class="brand-name">MyKid <b>Wedjat</b></span>
    </a>
    <nav class="nav" aria-label="<?= e(t('nav.main')) ?>">
      <?php foreach ($isAdmin ? array_slice($links, 0, 6) : $links as [$href, $ic, $label]): ?>
        <a class="<?= $on($href) ?>" href="<?= e($href) ?>"><?= e($label) ?></a>
      <?php endforeach; ?>
    </nav>
    <div class="topbar-end">
      <a class="pill-link hide-sm" href="/lang/<?= is_rtl() ? 'en' : 'ar' ?>"><?= icon('globe') ?> <?= is_rtl() ? 'EN' : 'عربي' ?></a>
      <?php if ($u): ?>
        <?php if (!$isAdmin): ?>
          <a class="pill-link hide-sm" href="/cart" aria-label="<?= e(t('cart.title')) ?>"><?= icon('cart') ?><?php if (cart_count()): ?><span class="count"><?= cart_count() ?></span><?php endif; ?></a>
        <?php endif; ?>
        <a class="pill-link" href="/notifications" aria-label="<?= e(t('nav.notifications')) ?>"><?= icon('bell') ?><?php if ($unread): ?><span class="count"><?= $unread > 9 ? '9+' : $unread ?></span><?php endif; ?></a>
      <?php else: ?>
        <a class="btn btn-sm btn-ghost hide-sm" href="/login"><?= e(t('nav.login')) ?></a>
        <a class="btn btn-sm btn-primary" href="/register"><?= e(t('nav.register')) ?></a>
      <?php endif; ?>
      <details class="menu<?= $u ? ' always' : '' ?>">
        <summary class="pill-link" aria-label="<?= e(t('nav.menu')) ?>"><?= icon('menu') ?></summary>
        <div class="menu-panel">
          <?php foreach ($links as [$href, $ic, $label]): ?>
            <a href="<?= e($href) ?>"><?= icon($ic) ?> <?= e($label) ?></a>
          <?php endforeach; ?>
          <?php if ($u): ?>
            <?php if (!$isAdmin): ?><a href="/cart"><?= icon('cart') ?> <?= e(t('cart.title')) ?><?= cart_count() ? ' (' . cart_count() . ')' : '' ?></a><?php endif; ?>
            <a href="/account"><?= icon('settings') ?> <?= e(t('nav.account')) ?></a>
          <?php else: ?>
            <a href="/login"><?= icon('child') ?> <?= e(t('nav.login')) ?></a>
          <?php endif; ?>
          <a href="/lang/<?= is_rtl() ? 'en' : 'ar' ?>"><?= icon('globe') ?> <?= is_rtl() ? 'English' : 'العربية' ?></a>
          <?php if ($u): ?>
            <form method="post" action="/logout"><?= csrf_field() ?><button type="submit"><?= icon('logout') ?> <?= e(t('nav.logout')) ?></button></form>
          <?php endif; ?>
        </div>
      </details>
    </div>
  </div>
</header>

<main class="container<?= !empty($narrow) ? ' narrow' : '' ?>">
  <?php foreach (take_flashes() as $f): ?>
    <div class="flash flash-<?= e($f['type']) ?>" role="status"><?= e($f['message']) ?></div>
  <?php endforeach; ?>
  <?= $content ?>
</main>

<?php
$supportPhone = (string)config('support_phone', '');
$supportEmail = (string)config('support_email', '');
$supportAddress = (string)config('support_address_' . current_lang(), config('support_address_en', ''));
?>
<footer class="footer">
  <div class="footer-in">
    <span>© <?= date('Y') ?> MyKid Wedjat — <?= e(t('footer.tagline')) ?></span>
    <span class="row">
      <a href="/shop"><?= e(t('nav.shop')) ?></a>
      <a href="/pricing"><?= e(t('nav.pricing')) ?></a>
    </span>
  </div>
  <div class="footer-in footer-contact">
    <strong><?= e(t('footer.contact')) ?></strong>
    <?php if ($supportPhone): ?><a href="tel:<?= e(clean_phone($supportPhone)) ?>"><?= icon('phone') ?> <span dir="ltr"><?= e($supportPhone) ?></span></a><?php endif; ?>
    <?php if ($supportEmail): ?><a href="mailto:<?= e($supportEmail) ?>"><?= icon('mail') ?> <span dir="ltr"><?= e($supportEmail) ?></span></a><?php endif; ?>
    <?php if ($supportAddress): ?><a href="https://www.google.com/maps/search/?api=1&amp;query=<?= e(rawurlencode($supportAddress)) ?>" target="_blank" rel="noopener"><?= icon('pin') ?> <?= e($supportAddress) ?></a><?php endif; ?>
  </div>
</footer>

<?php if ($u && !$isAdmin): ?>
<nav class="tabbar" aria-label="<?= e(t('nav.main')) ?>">
  <a class="<?= $on('/dashboard') ?: $on('/children') ?>" href="/dashboard"><?= icon('home') ?><?= e(t('nav.home')) ?></a>
  <a class="<?= $on('/shop') ?>" href="/shop"><?= icon('bag') ?><?= e(t('nav.shop')) ?></a>
  <a class="<?= $on('/cart') ?: $on('/checkout') ?>" href="/cart"><?= icon('cart') ?><?= e(t('cart.short')) ?><?php if (cart_count()): ?><span class="count"><?= cart_count() ?></span><?php endif; ?></a>
  <a class="<?= $on('/subscription') ?: $on('/payments') ?>" href="/subscription"><?= icon('shield') ?><?= e(t('nav.plan_short')) ?></a>
  <a class="<?= $on('/account') ?: $on('/orders') ?>" href="/account"><?= icon('settings') ?><?= e(t('nav.account_short')) ?></a>
</nav>
<?php endif; ?>
<script src="/assets/app.js?v=3"></script>
</body>
</html>
