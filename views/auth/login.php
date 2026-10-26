<div class="auth-wrap">
  <div class="card auth">
    <img class="auth-logo" src="/assets/logo.svg" alt="">
    <h1><?= e(t('login.title')) ?></h1>
    <p class="muted"><?= e(t('login.subtitle')) ?></p>
    <form method="post" action="/login" class="stack">
      <?= csrf_field() ?>
      <label><?= e(t('field.email')) ?>
        <input type="email" name="email" required autocomplete="username" inputmode="email" autocapitalize="none" dir="ltr">
      </label>
      <label><?= e(t('field.password')) ?>
        <input type="password" name="password" required autocomplete="current-password" dir="ltr">
      </label>
      <button class="btn btn-primary" type="submit"><?= e(t('login.submit')) ?></button>
    </form>
    <hr class="divider">
    <p class="center"><?= e(t('login.no_account')) ?> <a href="/register"><?= e(t('nav.register')) ?></a></p>
  </div>
</div>
