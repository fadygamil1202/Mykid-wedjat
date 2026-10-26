<div class="page-head">
  <h1><?= e(t('admin.users')) ?></h1>
  <div class="row">
    <a class="btn btn-sm <?= $role === 'parent' ? 'btn-primary' : '' ?>" href="/admin/users"><?= e(t('admin.parents')) ?></a>
    <a class="btn btn-sm <?= $role === 'admin' ? 'btn-primary' : '' ?>" href="/admin/users?role=admin"><?= e(t('admin.admins')) ?></a>
  </div>
</div>
<form class="filters" method="get" action="/admin/users">
  <input type="hidden" name="role" value="<?= e($role) ?>">
  <input type="search" name="q" value="<?= e($search) ?>" placeholder="<?= e(t('admin.search_users')) ?>">
  <button class="btn" type="submit"><?= e(t('filter')) ?></button>
</form>
<section class="card">
  <div class="table-wrap"><table class="table-cards">
    <thead><tr><th><?= e(t('field.name')) ?></th><th><?= e(t('field.email')) ?></th><th><?= e(t('field.phone')) ?></th>
      <?php if ($role === 'parent'): ?><th><?= e(t('admin.s_children')) ?></th><th><?= e(t('nav.subscription')) ?></th><?php endif; ?><th><?= e(t('field.joined')) ?></th></tr></thead>
    <tbody>
    <?php foreach ($users as $u): $subOk = $u['sub_ends'] && strtotime($u['sub_ends']) > time(); ?>
      <tr class="<?= $u['is_active'] ? '' : 'inactive' ?>">
        <td data-label="<?= e(t('field.name')) ?>"><a href="/admin/users/<?= e($u['id']) ?>"><?= e($u['name']) ?></a><?= $u['is_active'] ? '' : ' <span class="badge badge-off">' . e(t('admin.disabled')) . '</span>' ?></td>
        <td data-label="<?= e(t('field.email')) ?>" dir="ltr"><?= e($u['email']) ?></td>
        <td data-label="<?= e(t('field.phone')) ?>" dir="ltr"><?= e($u['phone']) ?></td>
        <?php if ($role === 'parent'): ?>
        <td data-label="<?= e(t('admin.s_children')) ?>"><?= e($u['kids']) ?></td>
        <td data-label="<?= e(t('nav.subscription')) ?>"><span class="badge badge-<?= $subOk ? 'active' : 'off' ?>"><?= e($subOk ? t('sub.until', ['date' => format_date($u['sub_ends'])]) : t('sub.state.none')) ?></span></td>
        <?php endif; ?>
        <td data-label="<?= e(t('field.joined')) ?>"><?= e(format_date($u['created_at'])) ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table></div>
  <?php if (!$users): ?><p class="muted"><?= e(t('admin.nothing')) ?></p><?php endif; ?>
</section>
<?php if ($role === 'admin'): ?>
<section class="card narrow">
  <h2><?= e(t('admin.add_admin')) ?></h2>
  <form method="post" action="/admin/users" class="stack"><?= csrf_field() ?>
    <label><?= e(t('field.name')) ?><input name="name" required></label>
    <label><?= e(t('field.email')) ?><input type="email" name="email" required dir="ltr" autocapitalize="none"></label>
    <label><?= e(t('field.password')) ?><input type="password" name="password" required minlength="10" dir="ltr" autocomplete="new-password"><span class="hint"><?= e(t('install.pw_hint')) ?></span></label>
    <button class="btn btn-primary" type="submit"><?= e(t('add')) ?></button>
  </form>
</section>
<?php endif; ?>
