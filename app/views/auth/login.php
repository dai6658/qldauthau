<form method="post" action="<?= url('auth/login') ?>" autocomplete="off">
    <?= csrf_field() ?>
    <div class="mb-3">
        <label class="form-label">Tên đăng nhập</label>
        <div class="input-group"><span class="input-group-text"><i class="bi bi-person"></i></span>
            <input class="form-control" name="username" required autofocus></div>
    </div>
    <div class="mb-3">
        <label class="form-label">Mật khẩu</label>
        <div class="input-group"><span class="input-group-text"><i class="bi bi-lock"></i></span>
            <input class="form-control" type="password" name="password" required></div>
    </div>
    <button class="btn btn-primary w-100 py-2"><i class="bi bi-box-arrow-in-right"></i> Đăng nhập</button>
</form>
<?php if (setting('allow_supplier_register', '1') === '1'): ?>
<div class="text-center mt-3 small">Là nhà cung cấp? <a href="<?= url('auth/register') ?>">Đăng ký tài khoản</a></div>
<?php endif; ?>
