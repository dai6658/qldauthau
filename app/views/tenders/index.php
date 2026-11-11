<div class="page-head">
    <div><h2>Hồ sơ mời thầu</h2><div class="sub">Tạo – phê duyệt – công bố – đóng/mở thầu – đánh giá – trao thầu</div></div>
    <?php if (can('tenders.manage')): ?>
    <div class="d-flex gap-2">
        <a class="btn btn-outline-primary" href="<?= url('templates/index') ?>"><i class="bi bi-magic"></i> Tạo từ mẫu</a>
        <a class="btn btn-primary" href="<?= url('tenders/create') ?>"><i class="bi bi-plus-lg"></i> Tạo HSMT</a>
    </div>
    <?php endif; ?>
</div>
<div class="d-flex flex-wrap gap-2 mb-3">
    <a class="btn btn-sm <?= !input('status') ? 'btn-dark' : 'btn-outline-dark' ?>" href="<?= url('tenders/index') ?>">Tất cả (<?= array_sum($counts) ?>)</a>
    <?php foreach (labels('tender_status') as $k => [$lbl, $color]): ?>
        <a class="btn btn-sm <?= input('status') === $k ? 'btn-' . $color : 'btn-outline-' . $color ?>" href="<?= url('tenders/index', ['status' => $k]) ?>"><?= e($lbl) ?> (<?= (int) ($counts[$k] ?? 0) ?>)</a>
    <?php endforeach; ?>
</div>
<form class="card card-body mb-3">
    <input type="hidden" name="r" value="tenders/index"><input type="hidden" name="status" value="<?= e(input('status')) ?>">
    <div class="row g-2">
        <div class="col-md-4"><input class="form-control" name="q" value="<?= e(input('q')) ?>" placeholder="Mã, tên gói thầu"></div>
        <div class="col-md-2"><select class="form-select" name="project_id"><?= select_options($projects, input('project_id'), '-- Dự án --') ?></select></div>
        <div class="col-md-2"><select class="form-select" name="category"><?= select_options(labels('category'), input('category'), '-- Lĩnh vực --') ?></select></div>
        <div class="col-md-2"><select class="form-select" name="method"><?= select_options(labels('method'), input('method'), '-- Hình thức --') ?></select></div>
        <div class="col-md-2"><button class="btn btn-outline-primary w-100">Lọc</button></div>
    </div>
</form>
<div class="card"><div class="table-responsive">
<table class="table table-hover align-middle mb-0">
    <thead><tr><th>Mã</th><th>Gói thầu</th><th>Dự án / Phòng</th><th>Hình thức</th><th class="text-end">Giá gói thầu</th><th>Đóng thầu</th><th>HSDT</th><th>Trạng thái</th></tr></thead>
    <?php foreach ($p['items'] as $t): $d = days_left($t['close_at']); ?>
        <tr>
            <td><a class="fw-semibold" href="<?= url('tenders/view', ['id' => $t['id']]) ?>"><?= e($t['code']) ?></a></td>
            <td><?= e($t['title']) ?><div class="small text-muted"><?= e(label('category', $t['category'])) ?> · <?= e($t['creator']) ?></div></td>
            <td class="small"><?= e($t['project_code']) ?><br><?= e($t['department_name']) ?></td>
            <td class="small"><?= e(label('method', $t['method'])) ?><br><?= e(label('eval_method', $t['evaluation_method'])) ?></td>
            <td class="text-end"><?= money($t['budget']) ?></td>
            <td class="small"><?= fdatetime($t['close_at']) ?><?php if ($t['status'] === 'published' && $d !== null): ?><br><span class="badge bg-<?= $d <= 3 ? 'danger' : 'info text-dark' ?>">còn <?= $d ?> ngày</span><?php endif; ?></td>
            <td><?= (int) $t['bid_count'] ?></td>
            <td><?= badge('tender_status', $t['status']) ?></td>
        </tr>
    <?php endforeach; if (!$p['items']): ?><tr><td colspan="8" class="text-center text-muted py-4">Không có hồ sơ phù hợp</td></tr><?php endif; ?>
</table></div>
<div class="card-footer"><?= pager($p) ?></div></div>
