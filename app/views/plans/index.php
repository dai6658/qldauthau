<div class="page-head">
    <div><h2>Kế hoạch đấu thầu & theo dõi tiến độ</h2><div class="sub">Lập kế hoạch từng giai đoạn, giao việc, giám sát theo thời gian thực</div></div>
    <?php if (can('plans.manage')): ?><a class="btn btn-primary" href="<?= url('plans/form') ?>"><i class="bi bi-plus-lg"></i> Lập kế hoạch</a><?php endif; ?>
</div>
<form class="card card-body mb-3"><input type="hidden" name="r" value="plans/index">
    <div class="row g-2">
        <div class="col-md-4"><select class="form-select" name="project_id"><?= select_options($projects, input('project_id'), '-- Dự án --') ?></select></div>
        <div class="col-md-4"><select class="form-select" name="status"><?= select_options(labels('plan_status'), input('status'), '-- Trạng thái --') ?></select></div>
        <div class="col-md-2"><button class="btn btn-outline-primary w-100">Lọc</button></div>
    </div>
</form>
<div class="row">
<?php foreach ($plans as $pl): ?>
    <div class="col-lg-6"><div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-between"><h5 class="mb-1"><a href="<?= url('plans/view', ['id' => $pl['id']]) ?>"><?= e($pl['name']) ?></a></h5><?= badge('plan_status', $pl['status']) ?></div>
            <div class="small text-muted mb-2"><?= e($pl['code']) ?> · <?= e($pl['project_code']) ?> · <?= fdate($pl['start_date']) ?> – <?= fdate($pl['end_date']) ?> · <?= e($pl['creator']) ?></div>
            <div class="progress mb-2" style="height:10px"><div class="progress-bar bg-success" style="width:<?= round($pl['progress']) ?>%"></div></div>
            <div class="d-flex gap-3 small">
                <span><strong><?= round($pl['progress']) ?>%</strong> tiến độ</span>
                <span><?= (int) $pl['done_count'] ?>/<?= (int) $pl['task_count'] ?> việc hoàn thành</span>
                <?php if ($pl['late_count']): ?><span class="text-danger"><i class="bi bi-exclamation-triangle"></i> <?= (int) $pl['late_count'] ?> việc trễ hạn</span><?php endif; ?>
            </div>
        </div>
    </div></div>
<?php endforeach; if (!$plans): ?><div class="col-12"><div class="card card-body text-muted text-center">Chưa có kế hoạch</div></div><?php endif; ?>
</div>
