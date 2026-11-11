<?php $v = fn($k, $d = '') => e($tpl[$k] ?? $d); if (!$criteria) $criteria = [['name' => '', 'max_score' => 10]]; ?>
<div class="page-head"><h2><?= e($title) ?></h2><a class="btn btn-light" href="<?= url('templates/index') ?>">← Danh sách</a></div>
<form method="post">
    <?= csrf_field() ?>
    <div class="row">
        <div class="col-lg-8">
            <div class="card card-body">
                <div class="row g-3">
                    <div class="col-12"><label class="form-label">Tên mẫu *</label><input class="form-control" name="name" value="<?= $v('name') ?>" required></div>
                    <div class="col-md-4"><label class="form-label">Lĩnh vực</label><select class="form-select" name="category"><?= select_options(labels('category'), $tpl['category'] ?? 'goods') ?></select></div>
                    <div class="col-md-4"><label class="form-label">Hình thức</label><select class="form-select" name="method"><?= select_options(labels('method'), $tpl['method'] ?? 'open') ?></select></div>
                    <div class="col-md-4"><label class="form-label">Phương pháp đánh giá</label><select class="form-select" name="evaluation_method"><?= select_options(labels('eval_method'), $tpl['evaluation_method'] ?? 'qcs') ?></select></div>
                    <div class="col-md-6"><label class="form-label">Trọng số kỹ thuật (%) — giá = 100 − KT</label><input class="form-control" type="number" min="0" max="100" name="tech_weight" value="<?= $v('tech_weight', setting('default_tech_weight', 70)) ?>"></div>
                    <div class="col-md-6"><label class="form-label">Điểm kỹ thuật tối thiểu</label><input class="form-control" type="number" min="0" max="100" name="pass_tech_score" value="<?= $v('pass_tech_score', setting('default_pass_score', 70)) ?>"></div>
                    <div class="col-12"><label class="form-label">Nội dung mẫu</label><textarea class="form-control font-monospace" name="content" rows="18"><?= $v('content') ?></textarea></div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card"><div class="card-header">Tiêu chí đánh giá kỹ thuật mặc định</div><div class="card-body" id="critBox">
                <?php foreach ($criteria as $c): ?>
                    <div class="input-group input-group-sm mb-2"><input class="form-control" name="crit_name[]" value="<?= e($c['name']) ?>" placeholder="Tiêu chí"><input class="form-control" style="max-width:80px" type="number" name="crit_score[]" value="<?= (int) $c['max_score'] ?>"><span class="input-group-text">đ</span></div>
                <?php endforeach; ?>
            </div><div class="card-footer"><button type="button" class="btn btn-sm btn-outline-primary" onclick="const b=document.getElementById('critBox');b.insertAdjacentHTML('beforeend', b.lastElementChild.outerHTML);b.lastElementChild.querySelectorAll('input').forEach(i=>i.value='')">+ Thêm tiêu chí</button></div></div>
            <div class="card"><div class="card-header">Biến thay thế tự động</div>
                <table class="table table-sm mb-0 small"><?php foreach ($placeholders as $k => $lbl): ?><tr><td><code><?= e($k) ?></code></td><td><?= e($lbl) ?></td></tr><?php endforeach; ?></table></div>
            <button class="btn btn-primary w-100"><i class="bi bi-save"></i> Lưu mẫu</button>
        </div>
    </div>
</form>
