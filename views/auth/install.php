<div class="auth-wrap">
  <div class="card auth">
    <img class="auth-logo" src="/assets/logo.svg" alt="">
    <h1><?= e(t('install.title')) ?></h1>
    <p class="muted"><?= e(t('install.text')) ?></p>
    <form method="post" action="/install" class="stack">
      <?= csrf_field() ?>
      <label><?= e(t('install.key')) ?><input type="password" name="install_key" required dir="ltr"></label>
      <label><?= e(t('field.name')) ?><input name="name" required></label>
      <label><?= e(t('field.email')) ?><input type="email" name="email" required dir="ltr" autocapitalize="none"></label>
      <label><?= e(t('field.password')) ?><input type="password" name="password" required minlength="10" dir="ltr" autocomplete="new-password">
        <span class="hint"><?= e(t('install.pw_hint')) ?></span></label>
      <button class="btn btn-primary" type="submit"><?= e(t('install.submit')) ?></button>
    </form>
  </div>
</div>
