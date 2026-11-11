<div class="page-head"><h2><?= e($title) ?></h2><a class="btn btn-light" href="<?= url('plans/index') ?>">← Danh sách</a></div>
<form method="post" class="card card-body">
    <?= csrf_field() ?>
    <div class="row g-3">
        <div class="col-md-4"><label class="form-label">Dự án *</label><select class="form-select" name="project_id" required><?= select_options($projects, $plan['project_id'] ?? null, '--') ?></select></div>
        <div class="col-md-8"><label class="form-label">Tên kế hoạch *</label><input class="form-control" name="name" value="<?= e($plan['name'] ?? '') ?>" required></div>
        <div class="col-md-3"><label class="form-label">Từ ngày</label><input class="form-control" type="date" name="start_date" value="<?= e($plan['start_date'] ?? '') ?>"></div>
        <div class="col-md-3"><label class="form-label">Đến ngày</label><input class="form-control" type="date" name="end_date" value="<?= e($plan['end_date'] ?? '') ?>"></div>
        <div class="col-12"><label class="form-label">Mô tả</label><textarea class="form-control" name="description" rows="3"><?= e($plan['description'] ?? '') ?></textarea></div>
    </div>
    <div class="mt-3"><button class="btn btn-primary"><i class="bi bi-save"></i> Lưu</button></div>
</form>
