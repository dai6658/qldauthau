<?php /* $entityType, $entityId, $category, $back, [$confidentiality] */ ?>
<form method="post" enctype="multipart/form-data" action="<?= url('documents/upload') ?>" class="row g-2 border-top pt-3 no-print">
    <?= csrf_field() ?>
    <input type="hidden" name="entity_type" value="<?= e($entityType) ?>">
    <input type="hidden" name="entity_id" value="<?= (int) $entityId ?>">
    <input type="hidden" name="back" value="<?= e($back) ?>">
    <div class="col-md-3"><input class="form-control form-control-sm" name="title" placeholder="Tiêu đề (tùy chọn)"></div>
    <div class="col-md-2"><select class="form-select form-select-sm" name="category"><?= select_options(labels('doc_category'), $category) ?></select></div>
    <div class="col-md-2"><select class="form-select form-select-sm" name="confidentiality"><?= select_options(labels('confidentiality'), $confidentiality ?? 'internal') ?></select></div>
    <div class="col-md-3"><input class="form-control form-control-sm" type="file" name="files[]" multiple required></div>
    <div class="col-md-2"><button class="btn btn-sm btn-primary w-100"><i class="bi bi-upload"></i> Tải lên</button></div>
</form>
