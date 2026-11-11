<h2 class="h5 mb-3">Đăng ký tài khoản nhà cung cấp / nhà thầu</h2>
<form method="post" action="<?= url('auth/register') ?>">
    <?= csrf_field() ?>
    <div class="row g-3">
        <div class="col-md-8"><label class="form-label">Tên doanh nghiệp *</label><input class="form-control" name="name" value="<?= e(old('name')) ?>" required></div>
        <div class="col-md-4"><label class="form-label">Mã số thuế *</label><input class="form-control" name="tax_code" value="<?= e(old('tax_code')) ?>" required pattern="\d{10}(-\d{3})?"></div>
        <div class="col-md-12"><label class="form-label">Địa chỉ</label><input class="form-control" name="address" value="<?= e(old('address')) ?>"></div>
        <div class="col-md-4"><label class="form-label">Người đại diện</label><input class="form-control" name="representative" value="<?= e(old('representative')) ?>"></div>
        <div class="col-md-4"><label class="form-label">Email *</label><input class="form-control" type="email" name="email" value="<?= e(old('email')) ?>" required></div>
        <div class="col-md-4"><label class="form-label">Điện thoại</label><input class="form-control" name="phone" value="<?= e(old('phone')) ?>"></div>
        <div class="col-md-12"><label class="form-label">Lĩnh vực kinh doanh</label><input class="form-control" name="business_field" value="<?= e(old('business_field')) ?>" placeholder="VD: Thiết bị CNTT, xây lắp..."></div>
        <div class="col-md-4"><label class="form-label">Tên đăng nhập *</label><input class="form-control" name="username" value="<?= e(old('username')) ?>" required></div>
        <div class="col-md-4"><label class="form-label">Mật khẩu * (≥ 8 ký tự)</label><input class="form-control" type="password" name="password" required minlength="8"></div>
        <div class="col-md-4"><label class="form-label">Nhập lại mật khẩu *</label><input class="form-control" type="password" name="password2" required></div>
    </div>
    <div class="d-flex justify-content-between align-items-center mt-4">
        <a href="<?= url('auth/login') ?>">← Quay lại đăng nhập</a>
        <button class="btn btn-primary"><i class="bi bi-send"></i> Gửi đăng ký</button>
    </div>
</form>
