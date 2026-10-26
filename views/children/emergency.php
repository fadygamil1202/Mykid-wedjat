<?php $p = $profile; $rows = array_pad($contacts, 3, ['name' => '', 'relation' => '', 'phone' => '']); ?>
<h1><?= e(t('emergency.edit_title')) ?> — <?= e($child['first_name']) ?></h1>
<section class="card narrow">
  <p class="hint"><?= e(t('emergency.intro')) ?></p>
  <form method="post" action="/children/<?= e($child['id']) ?>/emergency" class="stack">
    <?= csrf_field() ?>

    <fieldset>
      <legend><?= e(t('emergency.contacts_title')) ?></legend>
      <p class="hint"><?= e(t('emergency.contacts_hint')) ?></p>
      <?php foreach ($rows as $i => $ct): ?>
        <div class="contact-row">
          <input name="contact_name[]" placeholder="<?= e(t('field.name')) ?>" value="<?= e($ct['name']) ?>" aria-label="<?= e(t('field.name')) ?> <?= $i + 1 ?>" <?= $i === 0 ? 'required' : '' ?>>
          <input name="contact_relation[]" placeholder="<?= e(t('field.relation')) ?>" value="<?= e($ct['relation']) ?>" aria-label="<?= e(t('field.relation')) ?> <?= $i + 1 ?>">
          <input name="contact_phone[]" type="tel" inputmode="tel" dir="ltr" placeholder="01xxxxxxxxx" value="<?= e($ct['phone']) ?>" aria-label="<?= e(t('field.phone')) ?> <?= $i + 1 ?>" <?= $i === 0 ? 'required' : '' ?>>
        </div>
      <?php endforeach; ?>
    </fieldset>

    <label><?= e(t('field.blood_type')) ?>
      <select name="blood_type" dir="ltr">
        <option value="">—</option>
        <?php foreach (BLOOD_TYPES as $b): ?><option <?= ($p['blood_type'] ?? '') === $b ? 'selected' : '' ?>><?= e($b) ?></option><?php endforeach; ?>
      </select>
    </label>

    <label><?= e(t('field.allergies')) ?>
      <textarea name="allergies" rows="3" placeholder="<?= e(t('emergency.allergies_ph')) ?>"><?= e(allergies_to_text($p['allergies'] ?? null)) ?></textarea>
      <span class="hint"><?= e(t('emergency.allergies_hint')) ?></span>
    </label>
    <label><?= e(t('field.conditions')) ?>
      <textarea name="conditions" rows="2" maxlength="1000" placeholder="<?= e(t('emergency.conditions_ph')) ?>"><?= e($p['conditions'] ?? '') ?></textarea>
    </label>
    <label><?= e(t('field.medications')) ?>
      <textarea name="medications" rows="2" maxlength="1000" placeholder="<?= e(t('emergency.medications_ph')) ?>"><?= e($p['medications'] ?? '') ?></textarea>
    </label>
    <label><?= e(t('field.care_notes')) ?>
      <textarea name="care_notes" rows="2" maxlength="1000" placeholder="<?= e(t('emergency.care_ph')) ?>"><?= e($p['care_notes'] ?? '') ?></textarea>
    </label>

    <fieldset>
      <legend><?= e(t('emergency.visible_title')) ?></legend>
      <p class="hint"><?= e(t('emergency.visible_hint')) ?></p>
      <?php foreach (['show_age' => 'field.age', 'show_blood_type' => 'field.blood_type', 'show_allergies' => 'field.allergies',
                      'show_conditions' => 'field.conditions', 'show_medications' => 'field.medications', 'show_care_notes' => 'field.care_notes'] as $flag => $label): ?>
        <label class="check"><input type="checkbox" name="<?= e($flag) ?>" value="1" <?= ($p[$flag] ?? 1) ? 'checked' : '' ?>> <?= e(t($label)) ?></label>
      <?php endforeach; ?>
    </fieldset>

    <?php if (!empty($p['consent_at'])): ?>
      <p class="hint"><?= icon('check') ?> <?= e(t('emergency.consented_at', ['date' => format_dt($p['consent_at'])])) ?></p>
    <?php else: ?>
      <label class="check consent"><input type="checkbox" name="consent" value="1"> <?= e(t('emergency.consent')) ?></label>
    <?php endif; ?>

    <div class="row">
      <button class="btn btn-primary" type="submit"><?= e(t('save')) ?></button>
      <a class="btn btn-ghost" href="/children/<?= e($child['id']) ?>"><?= e(t('cancel')) ?></a>
    </div>
  </form>
</section>
