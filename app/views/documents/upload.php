<div class="page-head"><h2>Tải lên tài liệu</h2><a class="btn btn-light" href="<?= url('documents/index') ?>">← Kho tài liệu</a></div>
<form method="post" enctype="multipart/form-data" class="card card-body">
    <?= csrf_field() ?>
    <div class="row g-3">
        <div class="col-md-8"><label class="form-label">Tiêu đề</label><input class="form-control" name="title" placeholder="Để trống sẽ lấy theo tên tệp"></div>
        <div class="col-md-4"><label class="form-label">Loại tài liệu</label><select class="form-select" name="category"><?= select_options(labels('doc_category'), input('category', 'other')) ?></select></div>
        <div class="col-md-4"><label class="form-label">Gắn với</label>
            <select class="form-select" name="entity_type" id="entityType"><?= select_options(['general' => 'Tài liệu chung', 'project' => 'Dự án', 'tender' => 'Hồ sơ mời thầu', 'contract' => 'Hợp đồng'], input('entity_type', 'general')) ?></select></div>
        <div class="col-md-4 entity-pick" data-for="tender"><label class="form-label">Hồ sơ mời thầu</label><select class="form-select" name="entity_id" disabled><?= select_options($tenders, input('entity_id'), '--') ?></select></div>
        <div class="col-md-4 entity-pick" data-for="contract"><label class="form-label">Hợp đồng</label><select class="form-select" name="entity_id" disabled><?= select_options($contracts, input('entity_id'), '--') ?></select></div>
        <div class="col-md-4"><label class="form-label">Dự án</label><select class="form-select" name="project_id"><?= select_options($projects, input('project_id'), '--') ?></select></div>
        <div class="col-md-4"><label class="form-label">Phòng ban quản lý</label><select class="form-select" name="department_id"><?= select_options($departments, auth()['department_id'], '--') ?></select></div>
        <div class="col-md-4"><label class="form-label">Mức độ bảo mật</label><select class="form-select" name="confidentiality"><?= select_options(labels('confidentiality'), 'internal') ?></select></div>
        <div class="col-md-8"><label class="form-label">Thẻ (phân cách bằng dấu phẩy)</label><input class="form-control" name="tags" placeholder="bản vẽ, điện, tầng 3"></div>
        <div class="col-12"><label class="form-label">Mô tả</label><textarea class="form-control" name="description" rows="2"></textarea></div>
        <div class="col-12">
            <label class="form-label">Tệp (có thể chọn nhiều) *</label>
            <input class="form-control" type="file" name="files[]" multiple required>
            <div class="form-text">Hỗ trợ: <?= e(implode(', ', config('upload.allowed'))) ?> · tối đa <?= file_size(config('upload.max_size')) ?>/tệp. Tệp được mã hóa trước khi lưu.</div>
        </div>
    </div>
    <div class="mt-3"><button class="btn btn-primary"><i class="bi bi-cloud-upload"></i> Tải lên</button></div>
</form>
<script>
(function () {
    const sel = document.getElementById('entityType');
    const sync = () => document.querySelectorAll('.entity-pick').forEach(el => {
        const on = el.dataset.for === sel.value;
        el.style.display = on ? '' : 'none';
        el.querySelector('select').disabled = !on;
    });
    sel.addEventListener('change', sync); sync();
})();
</script>
