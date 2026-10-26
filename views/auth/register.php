<div class="auth-wrap">
  <div class="card auth">
    <img class="auth-logo" src="/assets/logo.svg" alt="">
    <h1><?= e(t('register.title')) ?></h1>
    <p class="muted"><?= e(t('register.subtitle')) ?></p>
    <form method="post" action="/register" class="stack">
      <?= csrf_field() ?>
      <label><?= e(t('field.name')) ?><input name="name" required autocomplete="name" value="<?= e($old['name'] ?? '') ?>"></label>
      <label><?= e(t('field.email')) ?><input type="email" name="email" required autocomplete="email" inputmode="email" autocapitalize="none" dir="ltr" value="<?= e($old['email'] ?? '') ?>"></label>
      <label><?= e(t('field.phone')) ?>
        <input type="tel" name="phone" required autocomplete="tel" inputmode="tel" dir="ltr" placeholder="01xxxxxxxxx" value="<?= e($old['phone'] ?? '') ?>">
        <span class="hint"><?= e(t('register.phone_hint')) ?></span>
      </label>
      <label><?= e(t('field.password')) ?><input type="password" name="password" required minlength="8" autocomplete="new-password" dir="ltr"></label>
      <label><?= e(t('register.confirm')) ?><input type="password" name="password_confirm" required minlength="8" autocomplete="new-password" dir="ltr"></label>
      <label class="check"><input type="checkbox" name="terms" value="1" required> <?= e(t('register.terms')) ?></label>
      <button class="btn btn-primary" type="submit"><?= e(t('register.submit')) ?></button>
    </form>
    <hr class="divider">
    <p class="center"><?= e(t('register.have_account')) ?> <a href="/login"><?= e(t('nav.login')) ?></a></p>
  </div>
</div>
