<h1><?= e(t('tags.replace_title')) ?></h1>
<section class="card narrow">
  <p><?= e(t('tags.replace_intro', ['type' => t('tag.' . $tag['type']), 'name' => $tag['first_name']])) ?></p>
  <p class="hint warn"><?= icon('lock') ?> <?= e(t('tags.replace_lock_note')) ?></p>
  <?php if ($fee > 0): ?><p class="hint"><?= e(t('tags.replace_fee', ['fee' => money($fee)])) ?></p><?php endif; ?>
  <form method="post" action="/devices/<?= e($tag['id']) ?>/replace" class="stack">
    <?= csrf_field() ?>
    <fieldset>
      <legend><?= e(t('tags.reason')) ?></legend>
      <?php foreach (REQUEST_REASONS as $i => $r): ?>
        <label class="check"><input type="radio" name="reason" value="<?= e($r) ?>" <?= $i === 0 ? 'checked' : '' ?>> <?= e(t('reason.' . $r)) ?></label>
      <?php endforeach; ?>
    </fieldset>
    <label><?= e(t('field.notes')) ?><textarea name="notes" rows="2" maxlength="500"></textarea></label>
    <div class="row">
      <button class="btn btn-danger" type="submit"><?= e(t('tags.replace_submit')) ?></button>
      <a class="btn btn-ghost" href="/children/<?= e($tag['child_id']) ?>"><?= e(t('cancel')) ?></a>
    </div>
  </form>
</section>
