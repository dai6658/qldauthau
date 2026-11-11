<?php $v = fn($k, $d = '') => e($project[$k] ?? $d); ?>
<div class="page-head"><h2><?= e($title) ?></h2><a href="<?= url('projects/index') ?>" class="btn btn-light">← Danh sách</a></div>
<form method="post" class="card card-body">
    <?= csrf_field() ?>
    <div class="row g-3">
        <div class="col-md-3"><label class="form-label">Mã dự án *</label><input class="form-control" name="code" value="<?= $v('code') ?>" required></div>
        <div class="col-md-9"><label class="form-label">Tên dự án *</label><input class="form-control" name="name" value="<?= $v('name') ?>" required></div>
        <div class="col-md-4"><label class="form-label">Công ty</label><select class="form-select" name="company_id"><?= select_options($companies, $project['company_id'] ?? null) ?></select></div>
        <div class="col-md-4"><label class="form-label">Phòng ban phụ trách</label><select class="form-select" name="department_id"><?= select_options($departments, $project['department_id'] ?? null, '--') ?></select></div>
        <div class="col-md-4"><label class="form-label">Cán bộ quản lý</label><select class="form-select" name="manager_id"><?= select_options($users, $project['manager_id'] ?? null, '--') ?></select></div>
        <div class="col-md-6"><label class="form-label">Chủ đầu tư</label><input class="form-control" name="investor" value="<?= $v('investor') ?>"></div>
        <div class="col-md-6"><label class="form-label">Địa điểm</label><input class="form-control" name="location" value="<?= $v('location') ?>"></div>
        <div class="col-md-4"><label class="form-label">Ngày bắt đầu</label><input type="date" class="form-control" name="start_date" value="<?= $v('start_date') ?>"></div>
        <div class="col-md-4"><label class="form-label">Ngày kết thúc</label><input type="date" class="form-control" name="end_date" value="<?= $v('end_date') ?>"></div>
        <div class="col-md-4"><label class="form-label">Trạng thái</label><select class="form-select" name="status"><?= select_options(labels('project_status'), $project['status'] ?? 'planning') ?></select></div>
        <div class="col-12"><label class="form-label">Mô tả</label><textarea class="form-control" name="description" rows="3"><?= $v('description') ?></textarea></div>
    </div>
    <div class="mt-3"><button class="btn btn-primary"><i class="bi bi-save"></i> Lưu</button></div>
</form>
