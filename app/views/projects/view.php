<div class="page-head">
    <div><h2><?= e($project['code']) ?> — <?= e($project['name']) ?></h2><div class="sub"><?= e($project['company_name']) ?> · <?= e($project['department_name']) ?> · <?= badge('project_status', $project['status']) ?></div></div>
    <div class="d-flex gap-2">
        <?php if (can('projects.manage')): ?><a class="btn btn-outline-primary" href="<?= url('projects/form', ['id' => $project['id']]) ?>"><i class="bi bi-pencil"></i> Sửa</a><?php endif; ?>
        <?php if (can('budget.view')): ?><a class="btn btn-outline-success" href="<?= url('budget/projectView', ['id' => $project['id']]) ?>"><i class="bi bi-cash-stack"></i> TMĐT & chi phí</a><?php endif; ?>
        <?php if (can('tenders.manage')): ?><a class="btn btn-primary" href="<?= url('tenders/create', ['project_id' => $project['id']]) ?>"><i class="bi bi-plus-lg"></i> Tạo HSMT</a><?php endif; ?>
    </div>
</div>
<div class="row g-3 mb-3">
    <div class="col-md-3"><div class="kpi k1"><div class="l">TMĐT được duyệt</div><div class="v"><?= money($budget['appr'], false) ?></div><i class="bi bi-bank"></i></div></div>
    <div class="col-md-3"><div class="kpi k2"><div class="l">Đã chi / giải ngân</div><div class="v"><?= money($spent, false) ?></div><i class="bi bi-cash"></i></div></div>
    <div class="col-md-3"><div class="kpi k3"><div class="l">Còn lại</div><div class="v"><?= money($budget['appr'] - $spent, false) ?></div><i class="bi bi-wallet2"></i></div></div>
    <div class="col-md-3"><div class="kpi k4"><div class="l">Số gói thầu</div><div class="v"><?= count($tenders) ?></div><i class="bi bi-megaphone"></i></div></div>
</div>
<div class="row">
    <div class="col-lg-5"><div class="card"><div class="card-header">Thông tin</div><div class="card-body">
        <dl class="dl-grid">
            <dt>Chủ đầu tư</dt><dd><?= e($project['investor']) ?></dd>
            <dt>Địa điểm</dt><dd><?= e($project['location']) ?></dd>
            <dt>Quản lý</dt><dd><?= e($project['manager_name']) ?></dd>
            <dt>Thời gian</dt><dd><?= fdate($project['start_date']) ?> – <?= fdate($project['end_date']) ?></dd>
            <dt>Mô tả</dt><dd class="pre-wrap"><?= e($project['description']) ?></dd>
        </dl></div></div>
        <div class="card"><div class="card-header">Kế hoạch đấu thầu</div><ul class="list-group list-group-flush">
            <?php foreach ($plans as $pl): ?><li class="list-group-item d-flex justify-content-between"><a href="<?= url('plans/view', ['id' => $pl['id']]) ?>"><?= e($pl['code']) ?> – <?= e($pl['name']) ?></a><?= badge('plan_status', $pl['status']) ?></li><?php endforeach; ?>
            <?php if (!$plans): ?><li class="list-group-item text-muted">Chưa có kế hoạch</li><?php endif; ?>
        </ul></div>
    </div>
    <div class="col-lg-7">
        <div class="card"><div class="card-header">Gói thầu</div><table class="table table-sm mb-0">
            <thead><tr><th>Mã</th><th>Tên</th><th class="text-end">Giá gói thầu</th><th>Trạng thái</th></tr></thead>
            <?php foreach ($tenders as $t): ?><tr><td><a href="<?= url('tenders/view', ['id' => $t['id']]) ?>"><?= e($t['code']) ?></a></td><td><?= e($t['title']) ?></td><td class="text-end"><?= money($t['budget']) ?></td><td><?= badge('tender_status', $t['status']) ?></td></tr><?php endforeach; ?>
        </table></div>
        <div class="card"><div class="card-header">Hợp đồng</div><table class="table table-sm mb-0">
            <thead><tr><th>Số HĐ</th><th>Nhà thầu</th><th class="text-end">Giá trị</th><th>Trạng thái</th></tr></thead>
            <?php foreach ($contracts as $c): ?><tr><td><a href="<?= url('contracts/view', ['id' => $c['id']]) ?>"><?= e($c['code']) ?></a></td><td><?= e($c['supplier_name']) ?></td><td class="text-end"><?= money($c['value']) ?></td><td><?= badge('contract_status', $c['status']) ?></td></tr><?php endforeach; ?>
        </table></div>
        <div class="card"><div class="card-header">Tài liệu dự án</div><table class="table table-sm mb-0">
            <?php foreach ($documents as $d): ?><tr><td><a href="<?= url('documents/view', ['id' => $d['id']]) ?>"><?= e($d['title']) ?></a></td><td><?= badge('doc_category', $d['category']) ?></td><td>v<?= (int) $d['current_version'] ?></td></tr><?php endforeach; ?>
            <?php if (!$documents): ?><tr><td class="text-muted">Chưa có tài liệu</td></tr><?php endif; ?>
        </table></div>
    </div>
</div>
