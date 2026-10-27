<div class="auth-wrap">
  <div class="card auth">
    <img class="auth-logo" src="/assets/logo.svg" alt="">
    <h1><?= e(t('verify.title')) ?></h1>
    <p><?= e(t('verify.text')) ?></p>
    <p class="mono center" dir="ltr"><?= e($user['email']) ?></p>
    <p class="hint"><?= e(t('verify.hint')) ?></p>
    <form method="post" action="/verify-email/resend"><?= csrf_field() ?>
      <button class="btn btn-primary btn-block" type="submit"><?= icon('refresh') ?> <?= e(t('verify.resend')) ?></button>
    </form>
    <details style="margin-top:1rem">
      <summary class="link-btn"><?= e(t('verify.wrong_email')) ?></summary>
      <form method="post" action="/verify-email/change" class="stack" style="margin-top:.75rem"><?= csrf_field() ?>
        <label><?= e(t('field.email')) ?><input type="email" name="email" required dir="ltr" autocapitalize="none" inputmode="email" value="<?= e($user['email']) ?>"></label>
        <button class="btn" type="submit"><?= e(t('verify.change_btn')) ?></button>
      </form>
    </details>
    <hr class="divider">
    <form method="post" action="/logout" class="center"><?= csrf_field() ?><button class="link-btn" type="submit"><?= e(t('nav.logout')) ?></button></form>
  </div>
</div>
