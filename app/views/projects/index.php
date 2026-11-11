<div class="page-head">
    <div><h2>Dự án</h2><div class="sub">Hồ sơ thầu được gắn theo công ty / phòng ban / mã dự án</div></div>
    <?php if (can('projects.manage')): ?><a class="btn btn-primary" href="<?= url('projects/form') ?>"><i class="bi bi-plus-lg"></i> Thêm dự án</a><?php endif; ?>
</div>
<form class="card card-body mb-3 no-print">
    <input type="hidden" name="r" value="projects/index">
    <div class="row g-2">
        <div class="col-md-4"><input class="form-control" name="q" value="<?= e(input('q')) ?>" placeholder="Tìm mã, tên dự án"></div>
        <div class="col-md-3"><select class="form-select" name="status"><?= select_options(labels('project_status'), input('status'), '-- Trạng thái --') ?></select></div>
        <div class="col-md-3"><select class="form-select" name="department_id"><?= select_options($departments, input('department_id'), '-- Phòng ban --') ?></select></div>
        <div class="col-md-2"><button class="btn btn-outline-primary w-100"><i class="bi bi-search"></i> Lọc</button></div>
    </div>
</form>
<div class="card"><div class="table-responsive">
<table class="table table-hover align-middle mb-0">
    <thead><tr><th>Mã</th><th>Tên dự án</th><th>Phòng ban</th><th>Quản lý</th><th>Thời gian</th><th class="text-end">TMĐT</th><th>Giải ngân</th><th>Gói thầu</th><th>Trạng thái</th></tr></thead>
    <?php foreach ($p['items'] as $r): $pct = $r['tmdt'] ? min(100, round($r['spent'] * 100 / $r['tmdt'])) : 0; ?>
        <tr>
            <td><a href="<?= url('projects/view', ['id' => $r['id']]) ?>" class="fw-semibold"><?= e($r['code']) ?></a></td>
            <td><?= e($r['name']) ?></td><td><?= e($r['department_name']) ?></td><td><?= e($r['manager_name']) ?></td>
            <td class="small"><?= fdate($r['start_date']) ?> – <?= fdate($r['end_date']) ?></td>
            <td class="text-end"><?= money($r['tmdt']) ?></td>
            <td style="width:120px"><div class="progress" style="height:8px"><div class="progress-bar bg-success" style="width:<?= $pct ?>%"></div></div><small><?= $pct ?>%</small></td>
            <td><?= (int) $r['tender_count'] ?></td>
            <td><?= badge('project_status', $r['status']) ?></td>
        </tr>
    <?php endforeach; ?>
</table></div>
<div class="card-footer"><?= pager($p) ?></div></div>
