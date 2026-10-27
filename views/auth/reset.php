<div class="auth-wrap">
  <div class="card auth">
    <img class="auth-logo" src="/assets/logo.svg" alt="">
    <h1><?= e(t('reset.title')) ?></h1>
    <form method="post" action="/reset-password/<?= e($token) ?>" class="stack">
      <?= csrf_field() ?>
      <label><?= e(t('account.new_password_short')) ?>
        <input type="password" name="password" required minlength="8" autocomplete="new-password" dir="ltr"></label>
      <label><?= e(t('register.confirm')) ?>
        <input type="password" name="password_confirm" required minlength="8" autocomplete="new-password" dir="ltr"></label>
      <button class="btn btn-primary" type="submit"><?= e(t('reset.submit')) ?></button>
    </form>
  </div>
</div>
