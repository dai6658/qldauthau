<?php if ($error): ?>
    <div class="alert alert-danger"><i class="bi bi-exclamation-triangle"></i> <?= e($error) ?></div>
<?php else: ?>
    <div class="text-center mb-3">
        <i class="bi bi-file-earmark-lock display-4 text-primary"></i>
        <h5 class="mt-2"><?= e($link['title']) ?></h5>
        <div class="small text-muted"><?= e($version['original_name']) ?> · <?= file_size($version['size_bytes']) ?></div>
        <div class="small text-muted">Hết hạn: <?= fdatetime($link['expires_at']) ?><?= $link['max_downloads'] ? ' · còn ' . ($link['max_downloads'] - $link['download_count']) . ' lượt tải' : '' ?></div>
    </div>
    <form method="post" action="<?= url('share/open', ['token' => $token]) ?>">
        <?= csrf_field() ?>
        <?php if ($link['password_hash']): ?>
            <label class="form-label">Mật khẩu truy cập</label>
            <input class="form-control mb-3" type="password" name="password" required autofocus>
        <?php endif; ?>
        <button class="btn btn-primary w-100"><i class="bi bi-download"></i> Tải tài liệu</button>
    </form>
    <p class="small text-muted mt-3 mb-0">Tệp được lưu trữ mã hóa AES-256 và chỉ giải mã khi tải. Mọi lượt truy cập đều được ghi nhật ký.</p>
<?php endif; ?>
