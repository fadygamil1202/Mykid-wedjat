<div class="auth-wrap">
  <div class="card auth">
    <img class="auth-logo" src="/assets/logo.svg" alt="">
    <h1><?= e(t('forgot.title')) ?></h1>
    <p class="muted"><?= e(t('forgot.text')) ?></p>
    <form method="post" action="/forgot-password" class="stack">
      <?= csrf_field() ?>
      <label><?= e(t('field.email')) ?>
        <input type="email" name="email" required autocomplete="email" inputmode="email" autocapitalize="none" dir="ltr"></label>
      <button class="btn btn-primary" type="submit"><?= e(t('forgot.submit')) ?></button>
    </form>
    <hr class="divider">
    <p class="center"><a href="/login"><?= e(t('forgot.back')) ?></a></p>
  </div>
</div>
