<?php $v = fn($k, $d = '') => e($tender[$k] ?? $d); ?>
<div class="page-head">
    <div><h2><?= e($title) ?></h2><?php if ($template): ?><div class="sub"><i class="bi bi-magic"></i> Từ mẫu: <strong><?= e($template['name']) ?></strong> — nội dung HSMT và tiêu chí sẽ được sinh tự động</div><?php endif; ?></div>
    <a class="btn btn-light" href="<?= url('tenders/index') ?>">← Danh sách</a>
</div>
<?php if ($isNew && !$template && $templates): ?>
<div class="alert alert-info d-flex align-items-center gap-2">
    <i class="bi bi-lightbulb"></i> Tạo nhanh từ mẫu chuẩn:
    <?php foreach ($templates as $tid => $tname): ?><a class="btn btn-sm btn-outline-primary" href="<?= url('tenders/create', ['template_id' => $tid, 'project_id' => $tender['project_id'] ?? '']) ?>"><?= e($tname) ?></a><?php endforeach; ?>
</div>
<?php endif; ?>
<form method="post">
    <?= csrf_field() ?>
    <div class="card card-body">
        <div class="row g-3">
            <div class="col-md-8"><label class="form-label">Tên gói thầu *</label><input class="form-control" name="title" value="<?= $v('title') ?>" required></div>
            <div class="col-md-4"><label class="form-label">Dự án *</label><select class="form-select" name="project_id" id="projectSel" required><?= select_options($projects, $tender['project_id'] ?? null, '-- Chọn dự án --') ?></select></div>
            <div class="col-md-4"><label class="form-label">Khoản mục TMĐT (kiểm soát ngân sách)</label>
                <select class="form-select" name="investment_item_id" id="itemSel"><option value="">-- Không gắn --</option>
                    <?php foreach ($investItems as $ii): ?><option data-project="<?= (int) $ii['project_id'] ?>" value="<?= (int) $ii['id'] ?>" <?= (int) ($tender['investment_item_id'] ?? 0) === (int) $ii['id'] ? 'selected' : '' ?>><?= e($ii['name']) ?> (còn <?= money($ii['approved_amount'] - $ii['used']) ?>)</option><?php endforeach; ?>
                </select></div>
            <div class="col-md-4"><label class="form-label">Giá gói thầu (đ) *</label><input class="form-control money" name="budget" value="<?= money_input($tender['budget'] ?? null) ?>" required></div>
            <div class="col-md-4"><label class="form-label">Bảo đảm dự thầu (đ)</label><input class="form-control money" name="bid_security" value="<?= money_input($tender['bid_security'] ?? null) ?>"></div>
            <div class="col-md-4"><label class="form-label">Lĩnh vực</label><select class="form-select" name="category"><?= select_options(labels('category'), $tender['category'] ?? 'goods') ?></select></div>
            <div class="col-md-4"><label class="form-label">Hình thức lựa chọn nhà thầu</label><select class="form-select" name="method"><?= select_options(labels('method'), $tender['method'] ?? 'open') ?></select></div>
            <div class="col-md-4"><label class="form-label">Phương pháp đánh giá</label><select class="form-select" name="evaluation_method"><?= select_options(labels('eval_method'), $tender['evaluation_method'] ?? 'qcs') ?></select></div>
            <div class="col-md-3"><label class="form-label">Trọng số kỹ thuật (%)</label><input class="form-control" type="number" min="0" max="100" name="tech_weight" value="<?= $v('tech_weight', 70) ?>"><div class="form-text">Trọng số giá = 100 − KT (áp dụng QCS)</div></div>
            <div class="col-md-3"><label class="form-label">Điểm KT tối thiểu</label><input class="form-control" type="number" min="0" max="100" name="pass_tech_score" value="<?= $v('pass_tech_score', 70) ?>"></div>
            <div class="col-md-3"><label class="form-label">Thời điểm đóng thầu</label><input class="form-control" type="datetime-local" name="close_at" value="<?= e(dt_local($tender['close_at'] ?? null)) ?>"></div>
            <div class="col-md-3"><label class="form-label">Thời điểm mở thầu</label><input class="form-control" type="datetime-local" name="opening_at" value="<?= e(dt_local($tender['opening_at'] ?? null)) ?>"></div>
            <div class="col-12"><label class="form-label">Mô tả phạm vi</label><textarea class="form-control" name="description" rows="2"><?= $v('description') ?></textarea></div>
        </div>
    </div>
    <?php if ($isNew): ?>
    <div class="card">
        <div class="card-header">Hạng mục / phạm vi cung cấp (có thể bổ sung sau)</div>
        <div class="table-responsive"><table class="table table-sm mb-0" id="itemsTbl">
            <thead><tr><th style="width:28%">Tên hạng mục</th><th>Yêu cầu kỹ thuật</th><th style="width:10%">ĐVT</th><th style="width:10%">Số lượng</th><th style="width:16%">Đơn giá dự toán</th></tr></thead>
            <tbody>
            <?php for ($i = 0; $i < 3; $i++): ?>
                <tr><td><input class="form-control form-control-sm" name="item_name[]"></td><td><input class="form-control form-control-sm" name="item_specs[]"></td>
                    <td><input class="form-control form-control-sm" name="item_unit[]"></td><td><input class="form-control form-control-sm" name="item_qty[]" value="1"></td>
                    <td><input class="form-control form-control-sm money" name="item_price[]"></td></tr>
            <?php endfor; ?>
            </tbody>
        </table></div>
        <div class="card-footer"><button type="button" class="btn btn-sm btn-outline-primary" onclick="const tb=document.querySelector('#itemsTbl tbody');tb.insertAdjacentHTML('beforeend', tb.rows[0].outerHTML);tb.lastElementChild.querySelectorAll('input').forEach(i=>i.value=i.name==='item_qty[]'?'1':'')">+ Thêm dòng</button></div>
    </div>
    <?php endif; ?>
    <button class="btn btn-primary"><i class="bi bi-save"></i> <?= $isNew ? 'Tạo hồ sơ' : 'Lưu thay đổi' ?></button>
</form>
<script>
(function () {
    const p = document.getElementById('projectSel'), s = document.getElementById('itemSel');
    const sync = () => s.querySelectorAll('option[data-project]').forEach(o => o.hidden = p.value && o.dataset.project !== p.value);
    p.addEventListener('change', () => { s.value = ''; sync(); }); sync();
})();
</script>
