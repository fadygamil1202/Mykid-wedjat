<!doctype html>
<html lang="<?= e(current_lang()) ?>" dir="<?= is_rtl() ? 'rtl' : 'ltr' ?>">
<head>
<?php require BASE_PATH . '/views/partials/head.php'; ?>
<meta name="robots" content="noindex, nofollow">
<title><?= e($title ?? t('app.name')) ?></title>
</head>
<body class="plain">
  <?php foreach (take_flashes() as $f): ?>
    <div class="flash flash-<?= e($f['type']) ?>" role="status"><?= e($f['message']) ?></div>
  <?php endforeach; ?>
  <?= $content ?>
<script src="/assets/app.js?v=2"></script>
</body>
</html>
