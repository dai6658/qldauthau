<?php $cur = $versions[0] ?? null; ?>
<div class="page-head">
    <div><h2><i class="bi bi-file-earmark-text"></i> <?= e($doc['title']) ?></h2>
        <div class="sub"><?= e($doc['code']) ?> · <?= badge('doc_category', $doc['category']) ?> · <?= badge('confidentiality', $doc['confidentiality']) ?> · phiên bản hiện tại v<?= (int) $doc['current_version'] ?></div></div>
    <div class="d-flex gap-2">
        <?php if ($canDownload && $cur): ?>
            <?php if (in_array($cur['extension'], ['pdf', 'jpg', 'jpeg', 'png', 'gif', 'txt'], true)): ?><a class="btn btn-outline-primary" target="_blank" href="<?= url('documents/download', ['id' => $doc['id'], 'inline' => 1]) ?>"><i class="bi bi-eye"></i> Xem</a><?php endif; ?>
            <a class="btn btn-primary" href="<?= url('documents/download', ['id' => $doc['id']]) ?>"><i class="bi bi-download"></i> Tải xuống</a>
        <?php endif; ?>
        <?php if ($canEdit): ?><form method="post" action="<?= url('documents/delete', ['id' => $doc['id']]) ?>" data-confirm="Xóa tài liệu này?"><?= csrf_field() ?><button class="btn btn-outline-danger"><i class="bi bi-trash"></i></button></form><?php endif; ?>
    </div>
</div>

<ul class="nav nav-tabs mb-3">
    <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-info">Thông tin</button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-versions">Phiên bản (<?= count($versions) ?>)</button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-access">Phân quyền & chia sẻ</button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-audit">Nhật ký (<?= count($logs) ?>)</button></li>
</ul>
<div class="tab-content">
<div class="tab-pane fade show active" id="tab-info"><div class="row">
    <div class="col-lg-6"><div class="card card-body">
        <dl class="dl-grid">
            <dt>Gắn với</dt><dd><?= badge('entity', $doc['entity_type'] ?: 'general') ?>
                <?php if ($doc['entity_type'] === 'tender'): ?><a href="<?= url('tenders/view', ['id' => $doc['entity_id']]) ?>">Mở HSMT #<?= (int) $doc['entity_id'] ?></a>
                <?php elseif ($doc['entity_type'] === 'contract'): ?><a href="<?= url('contracts/view', ['id' => $doc['entity_id']]) ?>">Mở hợp đồng #<?= (int) $doc['entity_id'] ?></a>
                <?php elseif ($doc['entity_type'] === 'supplier'): ?><a href="<?= url('suppliers/view', ['id' => $doc['entity_id']]) ?>">Mở NCC #<?= (int) $doc['entity_id'] ?></a><?php endif; ?></dd>
            <dt>Dự án</dt><dd><?= e($doc['project_code']) ?> <?= e($doc['project_name']) ?></dd>
            <dt>Phòng ban</dt><dd><?= e($doc['department_name']) ?></dd>
            <dt>Người tạo</dt><dd><?= e($doc['creator']) ?> · <?= fdatetime($doc['created_at']) ?></dd>
            <dt>Tệp hiện tại</dt><dd><?= e($cur['original_name'] ?? '') ?> (<?= file_size($cur['size_bytes'] ?? 0) ?>)</dd>
            <dt>SHA-256</dt><dd><code class="small text-break"><?= e($cur['sha256'] ?? '') ?></code></dd>
            <dt>Mã hóa</dt><dd>AES-256-GCM · khóa "<?= e($cur['key_name'] ?? '') ?>" (<?= ($cur['key_type'] ?? '') === 'customer' ? 'khách hàng quản lý' : 'hệ thống' ?>)</dd>
            <dt>Mô tả</dt><dd class="pre-wrap"><?= e($doc['description']) ?></dd>
            <dt>Thẻ</dt><dd><?= e($doc['tags']) ?></dd>
        </dl>
        <?php if ($preview): ?><hr><h6>Xem trước nội dung</h6><pre class="small bg-light p-2" style="max-height:300px;overflow:auto"><?= e($preview) ?></pre><?php endif; ?>
    </div></div>
    <?php if ($canEdit): ?>
    <div class="col-lg-6"><form method="post" action="<?= url('documents/edit', ['id' => $doc['id']]) ?>" class="card card-body">
        <?= csrf_field() ?>
        <h6>Sửa thông tin</h6>
        <div class="mb-2"><label class="form-label">Tiêu đề</label><input class="form-control" name="title" value="<?= e($doc['title']) ?>"></div>
        <div class="row g-2 mb-2">
            <div class="col"><label class="form-label">Loại</label><select class="form-select" name="category"><?= select_options(labels('doc_category'), $doc['category']) ?></select></div>
            <div class="col"><label class="form-label">Mức mật</label><select class="form-select" name="confidentiality"><?= select_options(labels('confidentiality'), $doc['confidentiality']) ?></select></div>
        </div>
        <div class="mb-2"><label class="form-label">Thẻ</label><input class="form-control" name="tags" value="<?= e($doc['tags']) ?>"></div>
        <div class="mb-2"><label class="form-label">Mô tả</label><textarea class="form-control" name="description" rows="3"><?= e($doc['description']) ?></textarea></div>
        <button class="btn btn-primary">Lưu</button>
    </form></div>
    <?php endif; ?>
</div></div>

<div class="tab-pane fade" id="tab-versions"><div class="card">
    <table class="table table-sm align-middle mb-0">
        <thead><tr><th>Ver</th><th>Tệp</th><th>Kích thước</th><th>Ghi chú thay đổi</th><th>Người tải</th><th>Thời gian</th><th>Khóa</th><th></th></tr></thead>
        <?php foreach ($versions as $v): ?>
            <tr class="<?= (int) $v['version_no'] === (int) $doc['current_version'] ? 'table-success' : '' ?>">
                <td><strong>v<?= (int) $v['version_no'] ?></strong></td><td><?= e($v['original_name']) ?></td><td><?= file_size($v['size_bytes']) ?></td>
                <td><?= e($v['change_note']) ?></td><td><?= e($v['full_name']) ?></td><td><?= fdatetime($v['created_at']) ?></td>
                <td class="small"><?= e($v['key_name']) ?> <?= $v['key_status'] === 'revoked' ? '<span class="badge bg-danger">đã thu hồi</span>' : '' ?></td>
                <td class="text-nowrap">
                    <?php if ($canDownload): ?><a class="btn btn-sm btn-outline-primary" href="<?= url('documents/download', ['id' => $doc['id'], 'version' => $v['id']]) ?>"><i class="bi bi-download"></i></a><?php endif; ?>
                    <?php if ($canEdit && (int) $v['version_no'] !== (int) $doc['current_version']): ?>
                        <form class="d-inline" method="post" action="<?= url('documents/restore', ['id' => $doc['id']]) ?>" data-confirm="Khôi phục phiên bản v<?= (int) $v['version_no'] ?>?">
                            <?= csrf_field() ?><input type="hidden" name="version" value="<?= (int) $v['id'] ?>"><button class="btn btn-sm btn-outline-warning" title="Khôi phục"><i class="bi bi-arrow-counterclockwise"></i></button></form>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>
    <?php if ($canEdit): ?>
    <form method="post" enctype="multipart/form-data" action="<?= url('documents/newVersion', ['id' => $doc['id']]) ?>" class="card-body border-top row g-2">
        <?= csrf_field() ?>
        <div class="col-md-5"><input class="form-control" type="file" name="file" required></div>
        <div class="col-md-5"><input class="form-control" name="change_note" placeholder="Ghi chú thay đổi (bắt buộc)" required></div>
        <div class="col-md-2"><button class="btn btn-primary w-100"><i class="bi bi-plus"></i> Phiên bản mới</button></div>
    </form>
    <?php endif; ?>
</div></div>

<div class="tab-pane fade" id="tab-access"><div class="row">
    <div class="col-lg-6"><div class="card">
        <div class="card-header">Quyền truy cập cấp riêng</div>
        <div class="card-body small text-muted">Mặc định: người tạo, quản trị, người có quyền xem theo phòng ban; tài liệu gắn HSMT kế thừa quyền của HSMT. Tài liệu <b>Mật</b> chỉ người được cấp riêng mới xem được.</div>
        <table class="table table-sm mb-0">
            <thead><tr><th>Đối tượng</th><th>Xem</th><th>Tải</th><th>Sửa</th><th></th></tr></thead>
            <?php foreach ($perms as $pm): ?>
                <tr><td><?= $pm['user_id'] ? '<i class="bi bi-person"></i> ' . e($pm['full_name']) : ($pm['role_id'] ? '<i class="bi bi-people"></i> Vai trò: ' . e($pm['role_name']) : '<i class="bi bi-diagram-3"></i> Phòng: ' . e($pm['dept_name'])) ?></td>
                    <td><?= $pm['can_view'] ? '✔' : '' ?></td><td><?= $pm['can_download'] ? '✔' : '' ?></td><td><?= $pm['can_edit'] ? '✔' : '' ?></td>
                    <td><?php if ($canEdit): ?><form method="post" action="<?= url('documents/revoke', ['id' => $doc['id']]) ?>"><?= csrf_field() ?><input type="hidden" name="perm_id" value="<?= (int) $pm['id'] ?>"><button class="btn btn-sm btn-link text-danger p-0">Thu hồi</button></form><?php endif; ?></td></tr>
            <?php endforeach; ?>
        </table>
        <?php if ($canEdit): ?>
        <form method="post" action="<?= url('documents/grant', ['id' => $doc['id']]) ?>" class="card-body border-top" id="grantForm">
            <?= csrf_field() ?>
            <div class="row g-2">
                <div class="col-md-4"><select class="form-select form-select-sm" name="target_type" id="grantType"><option value="user">Người dùng</option><option value="role">Vai trò</option><option value="department">Phòng ban</option></select></div>
                <div class="col-md-8">
                    <select class="form-select form-select-sm grant-target" data-type="user" name="target_id"><?= select_options($users) ?></select>
                    <select class="form-select form-select-sm grant-target d-none" data-type="role" name="target_id" disabled><?= select_options($roles) ?></select>
                    <select class="form-select form-select-sm grant-target d-none" data-type="department" name="target_id" disabled><?= select_options($departments) ?></select>
                </div>
                <div class="col-md-8"><label class="me-3"><input type="checkbox" name="can_download" value="1" checked> Được tải</label><label><input type="checkbox" name="can_edit" value="1"> Được sửa</label></div>
                <div class="col-md-4"><button class="btn btn-sm btn-primary w-100">Cấp quyền</button></div>
            </div>
        </form>
        <script>
        document.getElementById('grantType').addEventListener('change', function () {
            document.querySelectorAll('.grant-target').forEach(s => { const on = s.dataset.type === this.value; s.classList.toggle('d-none', !on); s.disabled = !on; });
        });
        </script>
        <?php endif; ?>
    </div></div>
    <div class="col-lg-6"><div class="card">
        <div class="card-header">Liên kết chia sẻ an toàn</div>
        <?php if ($newShare): $link = (isset($_SERVER['HTTP_HOST']) ? ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\') . '/' : '') . url('share/open', ['token' => $newShare]); ?>
            <div class="alert alert-success m-3"><strong>Đã tạo liên kết:</strong><input class="form-control form-control-sm mt-1" value="<?= e($link) ?>" onclick="this.select()" readonly></div>
        <?php endif; ?>
        <table class="table table-sm mb-0">
            <thead><tr><th>Tạo bởi</th><th>Hết hạn</th><th>Mật khẩu</th><th>Lượt tải</th><th>Trạng thái</th><th></th></tr></thead>
            <?php foreach ($shares as $sh): $expired = strtotime($sh['expires_at']) < time(); ?>
                <tr><td><?= e($sh['full_name']) ?></td><td><?= fdatetime($sh['expires_at']) ?></td><td><?= $sh['password_hash'] ? '<i class="bi bi-lock-fill"></i>' : '—' ?></td>
                    <td><?= (int) $sh['download_count'] ?><?= $sh['max_downloads'] ? '/' . (int) $sh['max_downloads'] : '' ?></td>
                    <td><?= $sh['is_revoked'] ? '<span class="badge bg-secondary">Thu hồi</span>' : ($expired ? '<span class="badge bg-dark">Hết hạn</span>' : '<span class="badge bg-success">Hiệu lực</span>') ?></td>
                    <td><?php if ($canEdit && !$sh['is_revoked'] && !$expired): ?><form method="post" action="<?= url('documents/unshare', ['id' => $doc['id']]) ?>"><?= csrf_field() ?><input type="hidden" name="share_id" value="<?= (int) $sh['id'] ?>"><button class="btn btn-sm btn-link text-danger p-0">Thu hồi</button></form><?php endif; ?></td></tr>
            <?php endforeach; ?>
        </table>
        <?php if ($canEdit): ?>
        <form method="post" action="<?= url('documents/share', ['id' => $doc['id']]) ?>" class="card-body border-top row g-2">
            <?= csrf_field() ?>
            <div class="col-md-4"><label class="form-label small">Thời hạn</label><select class="form-select form-select-sm" name="hours"><option value="24">24 giờ</option><option value="72" selected>3 ngày</option><option value="168">7 ngày</option><option value="720">30 ngày</option></select></div>
            <div class="col-md-4"><label class="form-label small">Mật khẩu (tùy chọn)</label><input class="form-control form-control-sm" type="password" name="password" autocomplete="new-password"></div>
            <div class="col-md-4"><label class="form-label small">Số lượt tải tối đa</label><input class="form-control form-control-sm" type="number" name="max_downloads" min="1"></div>
            <div class="col-12"><button class="btn btn-sm btn-primary"><i class="bi bi-link-45deg"></i> Tạo liên kết</button></div>
        </form>
        <?php endif; ?>
    </div></div>
</div></div>

<div class="tab-pane fade" id="tab-audit"><div class="card">
    <table class="table table-sm mb-0">
        <thead><tr><th>Thời gian</th><th>Người dùng</th><th>Hành động</th><th>Chi tiết</th><th>IP</th></tr></thead>
        <?php foreach ($logs as $l): ?>
            <tr><td><?= fdatetime($l['created_at']) ?></td><td><?= e($l['full_name'] ?: $l['username']) ?></td><td><?= e(Audit::actionLabel($l['action'])) ?></td><td class="small"><?= e($l['details']) ?></td><td class="small"><?= e($l['ip']) ?></td></tr>
        <?php endforeach; ?>
    </table>
</div></div>
</div>
