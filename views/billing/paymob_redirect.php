<div class="auth-wrap">
  <section class="card auth center" data-redirect="<?= e($url) ?>">
    <img class="auth-logo" src="/assets/logo.svg" alt="">
    <h1><?= e(t('paymob.redirect_title')) ?></h1>
    <p class="muted"><?= e(t('paymob.redirect_text')) ?></p>
    <a class="btn btn-primary btn-block" href="<?= e($url) ?>"><?= icon('card') ?> <?= e(t('paymob.redirect_btn')) ?></a>
    <p class="small" style="margin-top:1rem"><a href="/payments/<?= e($paymentId) ?>"><?= e(t('back')) ?></a></p>
  </section>
</div>
