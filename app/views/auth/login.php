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
<details class="mt-4">
    <summary class="small text-muted">Tài khoản demo (mật khẩu: 123456)</summary>
    <table class="table table-sm demo-accounts mt-2 mb-0">
        <tr><th>admin</th><td>Quản trị hệ thống</td></tr>
        <tr><th>giamdoc</th><td>Ban Giám đốc (phê duyệt)</td></tr>
        <tr><th>dauthau</th><td>Chuyên viên đấu thầu</td></tr>
        <tr><th>kythuat</th><td>Tổ chuyên gia kỹ thuật</td></tr>
        <tr><th>chuyengia</th><td>Tổ chuyên gia tài chính - thương mại</td></tr>
        <tr><th>ketoan</th><td>Tài chính - Kế toán</td></tr>
        <tr><th>kiemsoat</th><td>Kiểm soát nội bộ</td></tr>
        <tr><th>ncc1, ncc2, ncc3</th><td>Nhà cung cấp</td></tr>
    </table>
</details>
