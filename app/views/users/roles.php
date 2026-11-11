<div class="page-head"><div><h2>Vai trò & ma trận phân quyền</h2><div class="sub">Tùy biến quyền theo quy trình riêng của tổ chức. Quản trị hệ thống luôn có toàn quyền.</div></div>
    <a class="btn btn-light" href="<?= url('users/index') ?>">← Người dùng</a></div>
<form method="post">
    <?= csrf_field() ?>
    <div class="card"><div class="table-responsive">
        <table class="table table-sm table-bordered align-middle mb-0">
            <thead><tr><th>Nhóm</th><th>Quyền</th>
                <?php foreach ($roles as $r): ?><th class="text-center small" title="<?= e($r['description']) ?>"><?= e($r['name']) ?></th><?php endforeach; ?></tr></thead>
            <?php foreach ($perms as $p): ?>
                <tr><td class="small text-muted"><?= e($p['module']) ?></td><td><?= e($p['name']) ?><div class="small text-muted"><code><?= e($p['code']) ?></code></div></td>
                    <?php foreach ($roles as $r): ?>
                        <td class="text-center">
                            <?php if ($r['code'] === 'admin'): ?><i class="bi bi-check-circle-fill text-success"></i>
                            <?php else: ?><input type="checkbox" class="form-check-input" name="perm[<?= (int) $r['id'] ?>][<?= (int) $p['id'] ?>]" value="1" <?= isset($granted[$r['id']][$p['id']]) ? 'checked' : '' ?>><?php endif; ?>
                        </td>
                    <?php endforeach; ?></tr>
            <?php endforeach; ?>
        </table>
    </div></div>
    <div class="card card-body">
        <h6>Thêm vai trò mới</h6>
        <div class="row g-2">
            <div class="col-md-3"><input class="form-control" name="new_role_code" placeholder="Mã (vd: truongphong)"></div>
            <div class="col-md-4"><input class="form-control" name="new_role_name" placeholder="Tên vai trò"></div>
            <div class="col-md-5"><input class="form-control" name="new_role_desc" placeholder="Mô tả"></div>
        </div>
    </div>
    <button class="btn btn-primary"><i class="bi bi-save"></i> Lưu phân quyền</button>
</form>
