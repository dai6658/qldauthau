<div class="row">
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header">Thông tin tài khoản</div>
            <div class="card-body">
                <form method="post" action="<?= url('auth/profile') ?>">
                    <?= csrf_field() ?>
                    <div class="row g-3">
                        <div class="col-md-6"><label class="form-label">Tên đăng nhập</label><input class="form-control" value="<?= e($u['username']) ?>" disabled></div>
                        <div class="col-md-6"><label class="form-label">Vai trò</label><input class="form-control" value="<?= e($u['role_name']) ?>" disabled></div>
                        <div class="col-md-12"><label class="form-label">Họ tên</label><input class="form-control" name="full_name" value="<?= e($u['full_name']) ?>"></div>
                        <div class="col-md-6"><label class="form-label">Email</label><input class="form-control" type="email" name="email" value="<?= e($u['email']) ?>"></div>
                        <div class="col-md-6"><label class="form-label">Điện thoại</label><input class="form-control" name="phone" value="<?= e($u['phone']) ?>"></div>
                    </div>
                    <hr>
                    <h6>Đổi mật khẩu <small class="text-muted">(để trống nếu không đổi)</small></h6>
                    <div class="row g-3">
                        <div class="col-md-4"><input class="form-control" type="password" name="current_password" placeholder="Mật khẩu hiện tại"></div>
                        <div class="col-md-4"><input class="form-control" type="password" name="new_password" placeholder="Mật khẩu mới"></div>
                        <div class="col-md-4"><input class="form-control" type="password" name="new_password2" placeholder="Nhập lại"></div>
                    </div>
                    <button class="btn btn-primary mt-3"><i class="bi bi-save"></i> Lưu</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="card">
            <div class="card-header">Lịch sử đăng nhập gần đây</div>
            <table class="table table-sm mb-0">
                <?php foreach ($logins as $l): ?>
                    <tr><td><?= e(Audit::actionLabel($l['action'])) ?></td><td><?= e($l['ip']) ?></td><td><?= fdatetime($l['created_at']) ?></td></tr>
                <?php endforeach; ?>
            </table>
        </div>
    </div>
</div>
