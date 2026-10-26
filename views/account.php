<h1><?= e(t('nav.account')) ?></h1>
<?php if ($user['role'] === 'parent'): ?>
<div class="row" style="margin-bottom:1rem">
  <a class="btn btn-sm" href="/orders"><?= icon('box') ?> <?= e(t('nav.orders')) ?></a>
  <a class="btn btn-sm" href="/subscription"><?= icon('shield') ?> <?= e(t('nav.subscription')) ?></a>
  <a class="btn btn-sm" href="/notifications"><?= icon('bell') ?> <?= e(t('nav.notifications')) ?></a>
</div>
<?php endif; ?>
<section class="card narrow">
  <form method="post" action="/account" class="stack">
    <?= csrf_field() ?>
    <label><?= e(t('field.name')) ?><input name="name" value="<?= e($user['name']) ?>" required autocomplete="name"></label>
    <label><?= e(t('field.email')) ?><input value="<?= e($user['email']) ?>" disabled dir="ltr"></label>
    <label><?= e(t('field.phone')) ?><input name="phone" type="tel" inputmode="tel" value="<?= e($user['phone']) ?>" dir="ltr" autocomplete="tel"></label>
    <h2><?= e(t('account.change_password')) ?></h2>
    <label><?= e(t('account.current_password')) ?><input type="password" name="current_password" autocomplete="current-password" dir="ltr"></label>
    <label><?= e(t('account.new_password')) ?><input type="password" name="new_password" autocomplete="new-password" minlength="8" dir="ltr"></label>
    <button class="btn btn-primary" type="submit"><?= e(t('save')) ?></button>
  </form>
</section>
<form method="post" action="/logout" class="card narrow"><?= csrf_field() ?>
  <button class="btn btn-danger btn-block" type="submit"><?= icon('logout') ?> <?= e(t('nav.logout')) ?></button>
</form>
