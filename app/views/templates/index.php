<div class="page-head">
    <div><h2>Mẫu hồ sơ mời thầu</h2><div class="sub">Chuẩn hóa HSMT: tạo gói thầu từ mẫu, hệ thống tự điền thông tin, hạng mục và tiêu chí đánh giá</div></div>
    <a class="btn btn-primary" href="<?= url('templates/form') ?>"><i class="bi bi-plus-lg"></i> Thêm mẫu</a>
</div>
<div class="row">
<?php foreach ($items as $t): $crit = json_decode($t['criteria_json'] ?: '[]', true) ?: []; ?>
    <div class="col-lg-4"><div class="card h-100">
        <div class="card-body">
            <h5 class="card-title"><?= e($t['name']) ?></h5>
            <div class="mb-2"><?= badge('category', $t['category']) ?> <?= badge('method', $t['method']) ?> <?= badge('eval_method', $t['evaluation_method']) ?></div>
            <div class="small text-muted mb-2">Trọng số KT/Giá: <?= (int) $t['tech_weight'] ?>/<?= (int) $t['price_weight'] ?> · Điểm KT tối thiểu: <?= (int) $t['pass_tech_score'] ?></div>
            <ul class="small mb-2"><?php foreach ($crit as $c): ?><li><?= e($c['name']) ?> (<?= (int) $c['max_score'] ?>đ)</li><?php endforeach; ?></ul>
            <div class="small text-muted">Đã dùng cho <?= (int) $t['used'] ?> gói thầu</div>
        </div>
        <div class="card-footer d-flex gap-2">
            <a class="btn btn-sm btn-primary" href="<?= url('tenders/create', ['template_id' => $t['id']]) ?>"><i class="bi bi-magic"></i> Tạo HSMT từ mẫu</a>
            <a class="btn btn-sm btn-outline-secondary" href="<?= url('templates/form', ['id' => $t['id']]) ?>"><i class="bi bi-pencil"></i></a>
            <form method="post" action="<?= url('templates/delete', ['id' => $t['id']]) ?>" data-confirm="Xóa mẫu này?"><?= csrf_field() ?><button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button></form>
        </div>
    </div></div>
<?php endforeach; ?>
</div>
