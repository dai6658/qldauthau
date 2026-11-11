<?php
$from = strtotime($range[0]); $to = strtotime($range[1]); $span = max(1, ($to - $from) / 86400 + 1);
$pos = fn($d) => max(0, min(100, (strtotime($d) - $from) / 86400 / $span * 100));
$todayPos = $pos(date('Y-m-d'));
$overall = $tasks ? round(array_sum(array_column($tasks, 'progress')) / count($tasks)) : 0;
$late = count(array_filter($tasks, fn($t) => $t['status'] !== 'done' && $t['due_date'] && $t['due_date'] < date('Y-m-d')));
$canManage = can('plans.manage');
?>
<div class="page-head">
    <div><h2><?= e($plan['name']) ?></h2><div class="sub"><?= e($plan['code']) ?> · <a href="<?= url('projects/view', ['id' => $plan['project_id']]) ?>"><?= e($plan['project_code']) ?></a> · <?= badge('plan_status', $plan['status']) ?> · lập bởi <?= e($plan['creator']) ?></div></div>
    <div class="d-flex gap-2">
        <?php if ($canManage): ?>
            <a class="btn btn-outline-primary" href="<?= url('plans/form', ['id' => $plan['id']]) ?>"><i class="bi bi-pencil"></i></a>
            <?php if ($plan['status'] === 'draft'): ?><form method="post" action="<?= url('plans/submitApproval', ['id' => $plan['id']]) ?>"><?= csrf_field() ?><button class="btn btn-warning"><i class="bi bi-send"></i> Trình duyệt</button></form><?php endif; ?>
            <?php if (in_array($plan['status'], ['approved', 'in_progress'], true) && $overall >= 100): ?><form method="post" action="<?= url('plans/complete', ['id' => $plan['id']]) ?>"><?= csrf_field() ?><button class="btn btn-success">Hoàn thành kế hoạch</button></form><?php endif; ?>
        <?php endif; ?>
    </div>
</div>
<div class="row g-3 mb-3">
    <div class="col-md-3"><div class="kpi k1"><div class="l">Tiến độ tổng thể</div><div class="v"><?= $overall ?>%</div><i class="bi bi-graph-up"></i></div></div>
    <div class="col-md-3"><div class="kpi k2"><div class="l">Hoàn thành</div><div class="v"><?= count(array_filter($tasks, fn($t) => $t['status'] === 'done')) ?>/<?= count($tasks) ?></div><i class="bi bi-check2-all"></i></div></div>
    <div class="col-md-3"><div class="kpi k3"><div class="l">Đang thực hiện</div><div class="v"><?= count(array_filter($tasks, fn($t) => $t['status'] === 'in_progress')) ?></div><i class="bi bi-hourglass-split"></i></div></div>
    <div class="col-md-3"><div class="kpi <?= $late ? 'k4' : 'k2' ?>"><div class="l">Trễ hạn</div><div class="v"><?= $late ?></div><i class="bi bi-alarm"></i></div></div>
</div>

<div class="card">
    <div class="card-header d-flex justify-content-between"><span>Biểu đồ tiến độ (Gantt) <?= fdate($range[0]) ?> → <?= fdate($range[1]) ?></span><span class="small"><span class="text-danger">│</span> Hôm nay</span></div>
    <div class="card-body gantt">
        <?php foreach ($tasks as $t): if (!$t['due_date']) continue;
            $s = $pos($t['start_date'] ?: $t['due_date']); $w = max(1.5, $pos($t['due_date']) - $s + 100 / $span);
            $isLate = $t['status'] !== 'done' && $t['due_date'] < date('Y-m-d'); ?>
            <div class="gantt-row">
                <div class="gantt-label"><strong><?= e($t['stage']) ?></strong> · <?= e($t['name']) ?><br><small class="text-muted"><?= e($t['assignee']) ?></small></div>
                <div class="gantt-track">
                    <div class="gantt-today" style="left:<?= $todayPos ?>%"></div>
                    <div class="gantt-bar <?= $t['status'] === 'done' ? 'done' : '' ?> <?= $isLate ? 'late' : '' ?>" style="left:<?= $s ?>%;width:<?= $w ?>%" title="<?= e($t['name'] . ' ' . fdate($t['start_date']) . ' - ' . fdate($t['due_date']) . ' (' . $t['progress'] . '%)') ?>"><div class="fill" style="width:<?= (int) $t['progress'] ?>%"></div></div>
                </div>
            </div>
        <?php endforeach; if (!$tasks): ?><p class="text-muted mb-0">Chưa có công việc.</p><?php endif; ?>
    </div>
</div>

<div class="card">
    <div class="card-header">Danh sách công việc & phân công</div>
    <div class="table-responsive"><table class="table align-middle mb-0">
        <thead><tr><th>Giai đoạn</th><th>Công việc</th><th>Gói thầu</th><th>Phụ trách</th><th>Thời gian</th><th style="width:130px">Tiến độ</th><th>Trạng thái</th><th style="width:330px">Cập nhật</th></tr></thead>
        <?php foreach ($tasks as $t): $isLate = $t['status'] !== 'done' && $t['due_date'] < date('Y-m-d'); $mine = (int) $t['assignee_id'] === user_id(); ?>
            <tr class="<?= $isLate ? 'table-danger' : '' ?>">
                <td class="small"><?= e($t['stage']) ?></td>
                <td><?= e($t['name']) ?><?php if ($t['note']): ?><div class="small text-muted"><?= e($t['note']) ?></div><?php endif; ?></td>
                <td class="small"><?php if ($t['tender_id']): ?><a href="<?= url('tenders/view', ['id' => $t['tender_id']]) ?>"><?= e($t['tender_code']) ?></a><br><?= badge('tender_status', $t['tender_status']) ?><?php endif; ?></td>
                <td class="small"><?= e($t['assignee']) ?><br><span class="text-muted"><?= e($t['dept']) ?></span></td>
                <td class="small"><?= fdate($t['start_date']) ?> → <?= fdate($t['due_date']) ?><?= $isLate ? '<br><span class="badge bg-danger">Trễ ' . abs(days_left($t['due_date'])) . ' ngày</span>' : '' ?></td>
                <td><div class="progress" style="height:8px"><div class="progress-bar <?= $t['status'] === 'done' ? 'bg-success' : '' ?>" style="width:<?= (int) $t['progress'] ?>%"></div></div><small><?= (int) $t['progress'] ?>%</small></td>
                <td><?= badge('task_status', $t['status']) ?></td>
                <td>
                    <?php if ($canManage || $mine): ?>
                    <form method="post" action="<?= url('plans/updateTask') ?>" class="d-flex gap-1">
                        <?= csrf_field() ?><input type="hidden" name="task_id" value="<?= (int) $t['id'] ?>">
                        <input class="form-control form-control-sm" style="width:64px" type="number" min="0" max="100" step="5" name="progress" value="<?= (int) $t['progress'] ?>">
                        <select class="form-select form-select-sm" style="width:110px" name="status"><?= select_options(labels('task_status'), $t['status']) ?></select>
                        <input class="form-control form-control-sm" name="note" placeholder="Ghi chú">
                        <button class="btn btn-sm btn-primary"><i class="bi bi-check"></i></button>
                    </form>
                    <?php endif; ?>
                    <?php if ($canManage): ?><form method="post" action="<?= url('plans/deleteTask') ?>" data-confirm="Xóa công việc?" class="d-inline"><?= csrf_field() ?><input type="hidden" name="task_id" value="<?= (int) $t['id'] ?>"><button class="btn btn-link btn-sm text-danger p-0">Xóa</button></form><?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
    </table></div>
</div>

<?php if ($canManage): ?>
<div class="row">
    <div class="col-lg-7"><form method="post" action="<?= url('plans/saveTask') ?>" class="card card-body">
        <?= csrf_field() ?><input type="hidden" name="plan_id" value="<?= (int) $plan['id'] ?>">
        <h6>Thêm công việc / giao việc</h6>
        <div class="row g-2">
            <div class="col-md-3"><input class="form-control form-control-sm" name="stage" placeholder="Giai đoạn"></div>
            <div class="col-md-9"><input class="form-control form-control-sm" name="name" placeholder="Tên công việc *" required></div>
            <div class="col-md-6"><select class="form-select form-select-sm" name="tender_id"><?= select_options($tenders, null, '-- Gói thầu liên quan --') ?></select></div>
            <div class="col-md-3"><select class="form-select form-select-sm" name="assignee_id"><?= select_options($users, null, '-- Người thực hiện --') ?></select></div>
            <div class="col-md-3"><select class="form-select form-select-sm" name="department_id"><?= select_options($departments, null, '-- Phòng ban --') ?></select></div>
            <div class="col-md-3"><input class="form-control form-control-sm" type="date" name="start_date"></div>
            <div class="col-md-3"><input class="form-control form-control-sm" type="date" name="due_date" required></div>
            <div class="col-md-4"><input class="form-control form-control-sm" name="note" placeholder="Ghi chú"></div>
            <div class="col-md-2"><button class="btn btn-sm btn-primary w-100">Thêm</button></div>
        </div>
    </form></div>
    <div class="col-lg-5"><form method="post" action="<?= url('plans/generate') ?>" class="card card-body">
        <?= csrf_field() ?><input type="hidden" name="plan_id" value="<?= (int) $plan['id'] ?>">
        <h6><i class="bi bi-magic"></i> Tự động sinh các giai đoạn chuẩn</h6>
        <p class="small text-muted mb-2">Lập HSMT → Thẩm định → Phát hành → Nhận HSDT → Mở thầu → Đánh giá → Thương thảo → Phê duyệt KQ → Ký HĐ</p>
        <div class="row g-2">
            <div class="col-md-6"><select class="form-select form-select-sm" name="tender_id"><?= select_options($tenders, null, '-- Gói thầu --') ?></select></div>
            <div class="col-md-6"><select class="form-select form-select-sm" name="assignee_id"><?= select_options($users, user_id(), '-- Phụ trách --') ?></select></div>
            <div class="col-md-6"><input class="form-control form-control-sm" type="date" name="start_date" value="<?= date('Y-m-d') ?>"></div>
            <div class="col-md-6"><button class="btn btn-sm btn-outline-primary w-100">Sinh kế hoạch</button></div>
        </div>
    </form></div>
</div>
<?php endif; ?>

<div class="row">
    <div class="col-lg-7"><div class="card"><div class="card-header">Lịch sử cập nhật tiến độ</div><div class="card-body"><ul class="timeline mb-0">
        <?php foreach ($updates as $u): ?><li><strong><?= e($u['full_name']) ?></strong> cập nhật "<?= e($u['task_name']) ?>" → <?= (int) $u['progress'] ?>% (<?= e(label('task_status', $u['status'])) ?>)
            <div class="small text-muted"><?= e($u['note']) ?> · <?= fdatetime($u['created_at']) ?></div></li><?php endforeach; ?>
        <?php if (!$updates): ?><li class="text-muted">Chưa có cập nhật</li><?php endif; ?>
    </ul></div></div></div>
    <div class="col-lg-5"><div class="card"><div class="card-header">Phê duyệt kế hoạch</div><div class="card-body"><?php partial('approvals/_history', ['history' => $history]); ?></div></div></div>
</div>
