<div class="page-head">
    <div><h2>Kho tài liệu tập trung</h2><div class="sub"><?= (int) $stats['n'] ?> tài liệu · <?= (int) $stats['nv'] ?> phiên bản · <?= file_size($stats['sz']) ?> · <i class="bi bi-shield-lock"></i> mã hóa AES-256-GCM</div></div>
    <?php if (can('documents.manage')): ?><a class="btn btn-primary" href="<?= url('documents/upload') ?>"><i class="bi bi-cloud-upload"></i> Tải lên</a><?php endif; ?>
</div>
<form class="card card-body mb-3">
    <input type="hidden" name="r" value="documents/index">
    <div class="row g-2">
        <div class="col-md-4"><input class="form-control" name="q" value="<?= e(input('q')) ?>" placeholder="Tìm theo tên, mã, thẻ, tên tệp, nội dung văn bản..."></div>
        <div class="col-md-2"><select class="form-select" name="category"><?= select_options(labels('doc_category'), input('category'), '-- Loại tài liệu --') ?></select></div>
        <div class="col-md-2"><select class="form-select" name="entity_type"><?= select_options(labels('entity'), input('entity_type'), '-- Gắn với --') ?></select></div>
        <div class="col-md-2"><select class="form-select" name="ext"><?= select_options(['pdf' => 'PDF', 'docx' => 'Word', 'xlsx' => 'Excel', 'pptx' => 'PowerPoint', 'jpg' => 'Ảnh JPG', 'png' => 'Ảnh PNG', 'dwg' => 'Bản vẽ DWG', 'eml' => 'Email', 'txt' => 'Văn bản'], input('ext'), '-- Định dạng --') ?></select></div>
        <div class="col-md-2"><select class="form-select" name="confidentiality"><?= select_options(labels('confidentiality'), input('confidentiality'), '-- Mức mật --') ?></select></div>
        <div class="col-md-4"><select class="form-select" name="project_id"><?= select_options($projects, input('project_id'), '-- Dự án --') ?></select></div>
        <div class="col-md-4"><select class="form-select" name="department_id"><?= select_options($departments, input('department_id'), '-- Phòng ban --') ?></select></div>
        <div class="col-md-2"><button class="btn btn-outline-primary w-100"><i class="bi bi-search"></i> Tìm kiếm</button></div>
        <div class="col-md-2"><a class="btn btn-light w-100" href="<?= url('documents/index') ?>">Xóa lọc</a></div>
    </div>
</form>
<div class="card"><div class="table-responsive">
<table class="table table-hover align-middle mb-0">
    <thead><tr><th>Mã</th><th>Tài liệu</th><th>Loại</th><th>Gắn với</th><th>Dự án / Phòng ban</th><th>Tệp</th><th>Ver</th><th>Mức mật</th><th>Cập nhật</th></tr></thead>
    <?php foreach ($p['items'] as $d): ?>
        <tr>
            <td class="small"><?= e($d['code']) ?></td>
            <td><a class="fw-semibold" href="<?= url('documents/view', ['id' => $d['id']]) ?>"><?= e($d['title']) ?></a>
                <?php if ($d['tags']): ?><div><?php foreach (array_filter(array_map('trim', explode(',', $d['tags']))) as $tag): ?><span class="badge bg-light text-dark border me-1">#<?= e($tag) ?></span><?php endforeach; ?></div><?php endif; ?></td>
            <td><?= badge('doc_category', $d['category']) ?></td>
            <td><?= badge('entity', $d['entity_type'] ?: 'general') ?><?= $d['entity_id'] ? ' #' . (int) $d['entity_id'] : '' ?></td>
            <td class="small"><?= e($d['project_code']) ?><br><?= e($d['department_name']) ?></td>
            <td class="small"><i class="bi bi-file-earmark"></i> <?= e($d['original_name']) ?><br><?= file_size($d['size_bytes']) ?></td>
            <td>v<?= (int) $d['current_version'] ?></td>
            <td><?= badge('confidentiality', $d['confidentiality']) ?></td>
            <td class="small"><?= e($d['creator']) ?><br><?= fdatetime($d['updated_at'] ?: $d['created_at']) ?></td>
        </tr>
    <?php endforeach; if (!$p['items']): ?><tr><td colspan="9" class="text-center text-muted py-4">Không tìm thấy tài liệu phù hợp</td></tr><?php endif; ?>
</table></div>
<div class="card-footer"><?= pager($p) ?></div></div>
