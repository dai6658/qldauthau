<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title ?? 'Đăng nhập') ?> | <?= e(config('app_short')) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="<?= asset('css/app.css') ?>" rel="stylesheet">
</head>
<body class="guest">
<div class="guest-wrap">
    <div class="guest-card <?= e($wide ?? '') ?>">
        <div class="text-center mb-4">
            <div class="guest-logo"><i class="bi bi-briefcase-fill"></i></div>
            <h1 class="h4 mt-3 mb-1"><?= e(config('app_name')) ?></h1>
            <div class="text-muted small"><?= e(setting('org_display_name', '')) ?></div>
        </div>
        <?php foreach (get_flashes() as $f): ?>
            <div class="alert alert-<?= e($f['type']) ?>"><?= e($f['message']) ?></div>
        <?php endforeach; ?>
        <?= $content ?>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
