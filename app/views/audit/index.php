<?php
$entityLinks = ['tender' => 'tenders/view', 'document' => 'documents/view', 'contract' => 'contracts/view', 'supplier' => 'suppliers/view', 'project' => 'projects/view', 'plan' => 'plans/view'];
?>
<div class="page-head">
    <div><h2>Nhật ký kiểm toán</h2><div class="sub">Ghi nhận bất biến mọi thao tác — chuỗi băm HMAC-SHA256, CSDL chặn sửa/xóa</div></div>
    <div class="d-flex gap-2">
        <form method="post" action="<?= url('audit/verify') ?>"><?= csrf_field() ?><button class="btn btn-warning"><i class="bi bi-shield-check"></i> Kiểm tra toàn vẹn</button></form>
        <a class="btn btn-outline-success" href="<?= url('audit/export', array_diff_key($_GET, ['r' => 1, 'page' => 1])) ?>"><i class="bi bi-filetype-csv"></i> Xuất</a>
    </div>
</div>
<?php if ($verify): ?>
    <div class="alert alert-<?= $verify['ok'] ? 'success' : 'danger' ?>">
        <i class="bi bi-<?= $verify['ok'] ? 'shield-fill-check' : 'shield-fill-exclamation' ?>"></i>
        <?php if ($verify['ok']): ?><strong>Nhật ký toàn vẹn.</strong> Đã kiểm tra <?= num($verify['checked'], 0) ?> bản ghi trong <?= (int) $verify['ms'] ?> ms — không phát hiện chỉnh sửa hay xóa.
        <?php else: ?><strong>PHÁT HIỆN SAI LỆCH!</strong> <?= e($verify['reason']) ?> tại bản ghi #<?= (int) $verify['broken_id'] ?> (đã kiểm tra <?= (int) $verify['checked'] ?> bản ghi).<?php endif; ?>
    </div>
<?php endif; ?>
<div class="row g-3 mb-3">
    <div class="col-md-3"><div class="kpi k1"><div class="l">Tổng bản ghi</div><div class="v"><?= num($stats['n'], 0) ?></div><div class="l">từ <?= fdatetime($stats['first_at']) ?></div></div></div>
    <div class="col-md-3"><div class="kpi k2"><div class="l">Hôm nay</div><div class="v"><?= (int) $stats['today'] ?></div></div></div>
    <div class="col-md-3"><div class="kpi k3"><div class="l">Đăng nhập thất bại</div><div class="v"><?= (int) $stats['failed'] ?></div></div></div>
    <div class="col-md-3"><div class="kpi k4"><div class="l">Truy cập bị từ chối</div><div class="v"><?= (int) $stats['denied'] ?></div></div></div>
</div>
<form class="card card-body mb-3"><input type="hidden" name="r" value="audit/index">
    <div class="row g-2">
        <div class="col-md-2"><select class="form-select" name="user_id"><?= select_options($users, input('user_id'), '-- Người dùng --') ?></select></div>
        <div class="col-md-2"><select class="form-select" name="action"><?= select_options($actions, input('action'), '-- Hành động --') ?></select></div>
        <div class="col-md-2"><select class="form-select" name="entity_type"><?= select_options($entities, input('entity_type'), '-- Đối tượng --') ?></select></div>
        <div class="col-md-1"><input class="form-control" name="entity_id" value="<?= e(input('entity_id')) ?>" placeholder="ID"></div>
        <div class="col-md-2"><input class="form-control" type="date" name="from" value="<?= e(input('from')) ?>"></div>
        <div class="col-md-2"><input class="form-control" type="date" name="to" value="<?= e(input('to')) ?>"></div>
        <div class="col-md-1"><button class="btn btn-outline-primary w-100"><i class="bi bi-search"></i></button></div>
        <div class="col-md-6"><input class="form-control" name="q" value="<?= e(input('q')) ?>" placeholder="Tìm trong chi tiết, tài khoản, IP"></div>
    </div>
</form>
<div class="card"><div class="table-responsive"><table class="table table-sm table-hover align-middle mb-0">
    <thead><tr><th>#</th><th>Thời gian</th><th>Người dùng</th><th>Hành động</th><th>Đối tượng</th><th>Chi tiết</th><th>IP</th><th>Hash</th></tr></thead>
    <?php foreach ($p['items'] as $l): $danger = in_array($l['action'], ['login_failed', 'access_denied', 'delete', 'key_revoke', 'cancel'], true); ?>
        <tr class="<?= $danger ? 'table-warning' : '' ?>">
            <td class="small text-muted"><?= (int) $l['id'] ?></td>
            <td class="small text-nowrap"><?= date('d/m/Y H:i:s', strtotime($l['created_at'])) ?></td>
            <td class="small"><?= e($l['full_name'] ?: $l['username']) ?></td>
            <td><span class="badge bg-light text-dark border"><?= e(Audit::actionLabel($l['action'])) ?></span></td>
            <td class="small"><?php if ($l['entity_type']): ?><?= e(label('entity', $l['entity_type']) ?: $l['entity_type']) ?><?php if ($l['entity_id']): ?>
                <?= isset($entityLinks[$l['entity_type']]) ? '<a href="' . url($entityLinks[$l['entity_type']], ['id' => $l['entity_id']]) . '">#' . (int) $l['entity_id'] . '</a>' : '#' . (int) $l['entity_id'] ?><?php endif; ?><?php endif; ?></td>
            <td class="small" style="max-width:420px"><?= e(mb_strimwidth((string) $l['details'], 0, 220, '…')) ?></td>
            <td class="small"><?= e($l['ip']) ?></td>
            <td><code class="small" title="prev: <?= e($l['prev_hash']) ?>"><?= e(substr($l['hash'], 0, 10)) ?>…</code></td>
        </tr>
    <?php endforeach; ?>
</table></div><div class="card-footer"><?= pager($p) ?></div></div>
