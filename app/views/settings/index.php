<div class="page-head"><div><h2>Cài đặt hệ thống</h2><div class="sub">Tùy biến theo quy trình riêng của tổ chức</div></div></div>
<ul class="nav nav-tabs mb-3">
    <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-general">Cấu hình chung</button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-workflow">Quy trình phê duyệt</button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-keys">Khóa mã hóa (BYOK)</button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-system">Hệ thống</button></li>
</ul>
<div class="tab-content">

<div class="tab-pane fade show active" id="tab-general">
    <form method="post" action="<?= url('settings/saveGeneral') ?>" class="card card-body" style="max-width:760px">
        <?= csrf_field() ?>
        <?php foreach ($fields as $k => [$label, $type]): ?>
            <?php if ($type === 'bool'): ?>
                <div class="form-check form-switch mb-3"><input class="form-check-input" type="checkbox" name="<?= $k ?>" value="1" id="f_<?= $k ?>" <?= $values[$k] === '1' ? 'checked' : '' ?>><label class="form-check-label" for="f_<?= $k ?>"><?= e($label) ?></label></div>
            <?php else: ?>
                <div class="mb-3"><label class="form-label"><?= e($label) ?></label><input class="form-control" type="<?= $type ?>" name="<?= $k ?>" value="<?= e($values[$k]) ?>"></div>
            <?php endif; ?>
        <?php endforeach; ?>
        <div><button class="btn btn-primary"><i class="bi bi-save"></i> Lưu cấu hình</button></div>
    </form>
</div>

<div class="tab-pane fade" id="tab-workflow">
    <p class="text-muted">Mỗi loại hồ sơ có thể có nhiều bước phê duyệt tuần tự; mỗi bước giao cho một vai trò. Để trống toàn bộ = không cần phê duyệt (tự động thông qua).</p>
    <div class="row">
    <?php foreach ($entities as $type => $name): $list = $steps[$type] ?? []; ?>
        <div class="col-lg-6"><form method="post" action="<?= url('settings/saveWorkflow') ?>" class="card">
            <?= csrf_field() ?><input type="hidden" name="entity_type" value="<?= e($type) ?>">
            <div class="card-header"><i class="bi bi-diagram-3"></i> <?= e($name) ?></div>
            <div class="card-body" id="wf_<?= e($type) ?>">
                <?php foreach (array_merge($list, [['name' => '', 'role_id' => null]]) as $i => $s): ?>
                    <div class="input-group input-group-sm mb-2 wf-row">
                        <span class="input-group-text">Bước <?= $i + 1 ?></span>
                        <input class="form-control" name="step_name[]" value="<?= e($s['name']) ?>" placeholder="Tên bước (vd: Giám đốc phê duyệt)">
                        <select class="form-select" name="step_role[]" style="max-width:190px"><?= select_options($roles, $s['role_id'], '-- Vai trò --') ?></select>
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="card-footer d-flex justify-content-between">
                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="const b=document.getElementById('wf_<?= e($type) ?>');const r=b.lastElementChild.cloneNode(true);r.querySelector('input').value='';r.querySelector('select').value='';r.querySelector('.input-group-text').textContent='Bước '+(b.children.length+1);b.appendChild(r)">+ Thêm bước</button>
                <button class="btn btn-sm btn-primary">Lưu quy trình</button>
            </div>
        </form></div>
    <?php endforeach; ?>
    </div>
</div>

<div class="tab-pane fade" id="tab-keys">
    <div class="alert alert-info small mb-3">
        <strong>Mô hình mã hóa phong bì (envelope encryption):</strong> mỗi phiên bản tài liệu được mã hóa AES-256-GCM bằng <em>khóa dữ liệu</em> đang hoạt động;
        khóa dữ liệu được bọc bởi <em>khóa chủ</em> (<code>app_key</code> trong tệp cấu hình, không lưu trong CSDL).
        Khách hàng có thể <strong>tự mang khóa (BYOK)</strong>. <strong>Thu hồi</strong> một khóa sẽ khiến mọi tài liệu mã hóa bằng khóa đó không thể giải mã (crypto-shredding).
        Mã hóa khi truyền tải: bật HTTPS và đặt <code>force_https = true</code>.
    </div>
    <div class="card"><div class="table-responsive"><table class="table align-middle mb-0">
        <thead><tr><th>#</th><th>Tên khóa</th><th>Loại</th><th>Dấu vân tay</th><th>Số phiên bản TL</th><th>Tạo bởi</th><th>Trạng thái</th><th style="width:330px"></th></tr></thead>
        <?php foreach ($keys as $k): ?>
            <tr class="<?= $k['status'] === 'active' ? 'table-success' : ($k['status'] === 'revoked' ? 'table-secondary' : '') ?>">
                <td><?= (int) $k['id'] ?></td><td class="fw-semibold"><?= e($k['name']) ?></td>
                <td><?= $k['key_type'] === 'customer' ? '<span class="badge bg-warning text-dark">Khách hàng quản lý</span>' : '<span class="badge bg-primary">Hệ thống</span>' ?></td>
                <td><code><?= e($k['fingerprint']) ?></code></td><td><?= (int) $k['version_count'] ?></td>
                <td class="small"><?= e($k['full_name'] ?: 'Hệ thống') ?><br><?= fdatetime($k['created_at']) ?></td>
                <td><?= ['active' => '<span class="badge bg-success">Đang hoạt động</span>', 'inactive' => '<span class="badge bg-secondary">Không hoạt động</span>', 'revoked' => '<span class="badge bg-danger">Đã thu hồi</span>'][$k['status']] ?></td>
                <td>
                    <?php if ($k['status'] === 'inactive'): ?>
                        <form class="d-inline" method="post" action="<?= url('settings/activateKey') ?>"><?= csrf_field() ?><input type="hidden" name="key_id" value="<?= (int) $k['id'] ?>"><button class="btn btn-sm btn-outline-success">Kích hoạt</button></form>
                        <form class="d-inline-flex gap-1" method="post" action="<?= url('settings/revokeKey') ?>" data-confirm="THU HỒI khóa là không thể hoàn tác. Tiếp tục?">
                            <?= csrf_field() ?><input type="hidden" name="key_id" value="<?= (int) $k['id'] ?>">
                            <input class="form-control form-control-sm" name="confirm_name" placeholder="Gõ tên khóa để xác nhận" style="width:170px">
                            <button class="btn btn-sm btn-outline-danger">Thu hồi</button></form>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
    </table></div>
    <div class="card-footer d-flex justify-content-between align-items-center">
        <span class="small text-muted">Xoay vòng khóa: mã hóa lại toàn bộ tài liệu (chưa bị thu hồi) bằng khóa đang hoạt động.</span>
        <form method="post" action="<?= url('settings/rotate') ?>" data-confirm="Mã hóa lại toàn bộ tài liệu bằng khóa đang hoạt động?"><?= csrf_field() ?><button class="btn btn-sm btn-warning"><i class="bi bi-arrow-repeat"></i> Xoay vòng khóa</button></form>
    </div></div>

    <div class="row">
        <div class="col-lg-5"><form method="post" action="<?= url('settings/createKey') ?>" class="card card-body">
            <?= csrf_field() ?>
            <h6><i class="bi bi-key"></i> Tạo khóa hệ thống mới</h6>
            <input class="form-control mb-2" name="name" placeholder="Tên khóa">
            <div class="form-check mb-2"><input class="form-check-input" type="checkbox" name="activate" value="1" id="actNew" checked><label class="form-check-label" for="actNew">Kích hoạt ngay</label></div>
            <button class="btn btn-primary">Tạo khóa</button>
        </form></div>
        <div class="col-lg-7"><form method="post" action="<?= url('settings/importKey') ?>" class="card card-body" autocomplete="off">
            <?= csrf_field() ?>
            <h6><i class="bi bi-safe"></i> Nhập khóa của khách hàng (BYOK)</h6>
            <input class="form-control mb-2" name="name" placeholder="Tên khóa (vd: Khóa KMS của khách hàng A)">
            <textarea class="form-control font-monospace mb-2" name="material" rows="2" placeholder="Khóa 256-bit: base64 (44 ký tự) hoặc hex (64 ký tự)" required><?= e($newKey) ?></textarea>
            <?php if ($newKey): ?><div class="alert alert-warning small py-1">Khóa mẫu vừa được sinh. Hãy <strong>lưu giữ an toàn</strong> ở phía khách hàng trước khi nhập.</div><?php endif; ?>
            <div class="d-flex justify-content-between align-items-center">
                <div class="form-check"><input class="form-check-input" type="checkbox" name="activate" value="1" id="actImp" checked><label class="form-check-label" for="actImp">Kích hoạt ngay</label></div>
                <div><button type="submit" class="btn btn-sm btn-link" formaction="<?= url('settings/generateMaterial') ?>" formnovalidate>Sinh khóa mẫu</button><button class="btn btn-warning">Nhập khóa</button></div>
            </div>
            <div class="form-text">Lệnh sinh khóa phía khách hàng: <code>openssl rand -base64 32</code></div>
        </form></div>
    </div>
</div>

<div class="tab-pane fade" id="tab-system">
    <div class="row">
        <div class="col-lg-7"><div class="card"><div class="card-header">Thông tin hệ thống</div><table class="table table-sm mb-0">
            <?php foreach ($system as $k => $v): ?><tr><th style="width:45%"><?= e($k) ?></th><td><?= e($v) ?></td></tr><?php endforeach; ?>
        </table></div></div>
        <div class="col-lg-5"><div class="card card-body">
            <h6>Nhắc việc & tự động hóa</h6>
            <p class="small text-muted">Hệ thống tự chạy mỗi giờ khi có người truy cập. Có thể đặt lịch <code>php cron/reminders.php</code> (Task Scheduler/cron) để chạy định kỳ.</p>
            <form method="post" action="<?= url('settings/runReminders') ?>"><?= csrf_field() ?><button class="btn btn-outline-primary"><i class="bi bi-alarm"></i> Chạy nhắc việc ngay</button></form>
        </div></div>
    </div>
</div>
</div>
