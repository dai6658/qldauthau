<div class="page-head"><div><h2>Việc của tôi</h2><div class="sub">Công việc được giao trong các kế hoạch đấu thầu</div></div></div>
<div class="card"><div class="table-responsive"><table class="table align-middle mb-0">
    <thead><tr><th>Công việc</th><th>Kế hoạch</th><th>Hạn</th><th style="width:130px">Tiến độ</th><th>Trạng thái</th><th style="width:330px">Cập nhật</th></tr></thead>
    <?php foreach ($tasks as $t): $d = days_left($t['due_date']); ?>
        <tr class="<?= $t['status'] !== 'done' && $d !== null && $d < 0 ? 'table-danger' : '' ?>">
            <td><strong><?= e($t['name']) ?></strong><div class="small text-muted"><?= e($t['stage']) ?> <?= e($t['tender_code']) ?></div></td>
            <td><a href="<?= url('plans/view', ['id' => $t['plan_id']]) ?>"><?= e($t['plan_code']) ?></a></td>
            <td><?= fdate($t['due_date']) ?><?php if ($t['status'] !== 'done' && $d !== null): ?><br><small class="<?= $d < 0 ? 'text-danger' : 'text-muted' ?>"><?= $d < 0 ? 'Quá ' . abs($d) . ' ngày' : 'Còn ' . $d . ' ngày' ?></small><?php endif; ?></td>
            <td><div class="progress" style="height:8px"><div class="progress-bar" style="width:<?= (int) $t['progress'] ?>%"></div></div><small><?= (int) $t['progress'] ?>%</small></td>
            <td><?= badge('task_status', $t['status']) ?></td>
            <td><form method="post" action="<?= url('plans/updateTask') ?>" class="d-flex gap-1">
                <?= csrf_field() ?><input type="hidden" name="task_id" value="<?= (int) $t['id'] ?>">
                <input class="form-control form-control-sm" style="width:64px" type="number" min="0" max="100" step="5" name="progress" value="<?= (int) $t['progress'] ?>">
                <select class="form-select form-select-sm" style="width:110px" name="status"><?= select_options(labels('task_status'), $t['status']) ?></select>
                <input class="form-control form-control-sm" name="note" placeholder="Ghi chú">
                <button class="btn btn-sm btn-primary"><i class="bi bi-check"></i></button>
            </form></td>
        </tr>
    <?php endforeach; if (!$tasks): ?><tr><td colspan="6" class="text-center text-muted py-4">Bạn chưa được giao việc nào</td></tr><?php endif; ?>
</table></div></div>
