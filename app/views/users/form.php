<?php $v = fn($k, $d = '') => e($user[$k] ?? $d); ?>
<div class="page-head"><h2><?= e($title) ?></h2><a class="btn btn-light" href="<?= url('users/index') ?>">← Danh sách</a></div>
<form method="post" class="card card-body" autocomplete="off">
    <?= csrf_field() ?>
    <div class="row g-3">
        <div class="col-md-4"><label class="form-label">Tên đăng nhập *</label><input class="form-control" name="username" value="<?= $v('username') ?>" required></div>
        <div class="col-md-4"><label class="form-label">Họ tên *</label><input class="form-control" name="full_name" value="<?= $v('full_name') ?>" required></div>
        <div class="col-md-4"><label class="form-label">Mật khẩu <?= !empty($user['id']) ? '(để trống nếu giữ nguyên)' : '*' ?></label><input class="form-control" type="password" name="password" autocomplete="new-password"></div>
        <div class="col-md-4"><label class="form-label">Email</label><input class="form-control" type="email" name="email" value="<?= $v('email') ?>"></div>
        <div class="col-md-4"><label class="form-label">Điện thoại</label><input class="form-control" name="phone" value="<?= $v('phone') ?>"></div>
        <div class="col-md-4"><label class="form-label">Vai trò *</label><select class="form-select" name="role_id" required><?= select_options($roles, $user['role_id'] ?? null, '--') ?></select></div>
        <div class="col-md-4"><label class="form-label">Công ty</label><select class="form-select" name="company_id"><?= select_options($companies, $user['company_id'] ?? null, '--') ?></select></div>
        <div class="col-md-4"><label class="form-label">Phòng ban</label><select class="form-select" name="department_id"><?= select_options($departments, $user['department_id'] ?? null, '--') ?></select></div>
        <div class="col-md-4"><label class="form-label">Nhà cung cấp (nếu vai trò NCC)</label><select class="form-select" name="supplier_id"><?= select_options($suppliers, $user['supplier_id'] ?? null, '--') ?></select></div>
        <div class="col-12"><div class="form-check"><input class="form-check-input" type="checkbox" name="is_active" value="1" id="act" <?= ($user['is_active'] ?? 1) ? 'checked' : '' ?>><label class="form-check-label" for="act">Đang hoạt động</label></div></div>
    </div>
    <div class="mt-3"><button class="btn btn-primary"><i class="bi bi-save"></i> Lưu</button></div>
</form>
