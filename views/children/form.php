<?php $c = $child; ?>
<h1><?= e($c ? t('children.edit') : t('children.new')) ?></h1>
<section class="card narrow">
  <form method="post" action="<?= $c ? '/children/' . e($c['id']) : '/children' ?>" class="stack">
    <?= csrf_field() ?>
    <div class="two">
      <label><?= e(t('field.first_name')) ?><input name="first_name" required maxlength="100" value="<?= e($c['first_name'] ?? '') ?>">
        <span class="hint"><?= e(t('children.first_hint')) ?></span></label>
      <label><?= e(t('field.last_name')) ?><input name="last_name" required maxlength="100" value="<?= e($c['last_name'] ?? '') ?>">
        <span class="hint"><?= e(t('children.last_hint')) ?></span></label>
    </div>
    <label><?= e(t('field.dob')) ?><input type="date" name="date_of_birth" max="<?= date('Y-m-d') ?>" value="<?= e($c['date_of_birth'] ?? '') ?>"></label>
    <div class="row">
      <button class="btn btn-primary" type="submit"><?= e($c ? t('save') : t('children.next')) ?></button>
      <a class="btn btn-ghost" href="<?= $c ? '/children/' . e($c['id']) : '/dashboard' ?>"><?= e(t('cancel')) ?></a>
    </div>
  </form>
  <?php if ($c): ?>
  <div class="danger-zone">
    <form method="post" action="/children/<?= e($c['id']) ?>/delete" data-confirm="<?= e(t('children.delete_confirm')) ?>"><?= csrf_field() ?>
      <button class="btn btn-danger btn-sm" type="submit"><?= e(t('children.delete')) ?></button>
    </form>
  </div>
  <?php endif; ?>
</section>
