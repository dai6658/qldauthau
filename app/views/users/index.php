<div class="page-head">
    <div><h2>Người dùng</h2><div class="sub">Phân quyền theo vai trò (RBAC) kết hợp thuộc tính phòng ban / công ty</div></div>
    <div class="d-flex gap-2">
        <a class="btn btn-outline-primary" href="<?= url('users/roles') ?>"><i class="bi bi-shield-lock"></i> Vai trò & quyền</a>
        <a class="btn btn-primary" href="<?= url('users/form') ?>"><i class="bi bi-person-plus"></i> Thêm người dùng</a>
    </div>
</div>
<form class="card card-body mb-3">
    <input type="hidden" name="r" value="users/index">
    <div class="row g-2">
        <div class="col-md-6"><input class="form-control" name="q" value="<?= e(input('q')) ?>" placeholder="Tìm tên đăng nhập, họ tên, email"></div>
        <div class="col-md-4"><select class="form-select" name="role_id"><?= select_options($roles, input('role_id'), '-- Vai trò --') ?></select></div>
        <div class="col-md-2"><button class="btn btn-outline-primary w-100">Lọc</button></div>
    </div>
</form>
<div class="card"><div class="table-responsive">
<table class="table table-hover align-middle mb-0">
    <thead><tr><th>Tên đăng nhập</th><th>Họ tên</th><th>Vai trò</th><th>Phòng ban / NCC</th><th>Email</th><th>Đăng nhập cuối</th><th>Trạng thái</th><th></th></tr></thead>
    <?php foreach ($p['items'] as $u): $locked = $u['locked_until'] && strtotime($u['locked_until']) > time(); ?>
        <tr>
            <td class="fw-semibold"><?= e($u['username']) ?></td><td><?= e($u['full_name']) ?></td><td><?= e($u['role_name']) ?></td>
            <td><?= e($u['department_name'] ?: $u['supplier_name']) ?></td><td><?= e($u['email']) ?></td><td><?= fdatetime($u['last_login_at']) ?></td>
            <td><?= $u['is_active'] ? '<span class="badge bg-success">Hoạt động</span>' : '<span class="badge bg-secondary">Vô hiệu</span>' ?>
                <?= $locked ? '<span class="badge bg-danger">Đang khóa</span>' : '' ?></td>
            <td class="text-nowrap">
                <a class="btn btn-sm btn-outline-primary" href="<?= url('users/form', ['id' => $u['id']]) ?>"><i class="bi bi-pencil"></i></a>
                <?php if ($locked): ?><form class="d-inline" method="post" action="<?= url('users/unlock', ['id' => $u['id']]) ?>"><?= csrf_field() ?><button class="btn btn-sm btn-outline-warning" title="Mở khóa"><i class="bi bi-unlock"></i></button></form><?php endif; ?>
            </td>
        </tr>
    <?php endforeach; ?>
</table></div>
<div class="card-footer"><?= pager($p) ?></div></div>
