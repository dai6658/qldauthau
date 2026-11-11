<div class="page-head"><div><h2>Hồ sơ doanh nghiệp</h2><div class="sub"><?= e($s['code']) ?> · <?= badge('supplier_status', $s['status']) ?> <?= $s['status_note'] ? '· ' . e($s['status_note']) : '' ?></div></div></div>
<ul class="nav nav-tabs mb-3">
    <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-info">Thông tin chung</button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-caps">Năng lực, kinh nghiệm (<?= count($caps) ?>)</button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-docs">Tài liệu pháp lý & chứng chỉ (<?= count($documents) ?>)</button></li>
</ul>
<div class="tab-content">
    <div class="tab-pane fade show active" id="tab-info">
        <form method="post" class="card card-body">
            <?= csrf_field() ?>
            <?php partial('suppliers/_fields', ['s' => $s, 'lockTax' => true]); ?>
            <div class="mt-3"><button class="btn btn-primary"><i class="bi bi-save"></i> Cập nhật hồ sơ</button></div>
        </form>
    </div>
    <div class="tab-pane fade" id="tab-caps"><div class="card card-body">
        <p class="small text-muted">Kê khai hợp đồng tương tự, năng lực tài chính, nhân sự chủ chốt, thiết bị, chứng chỉ để bên mời thầu thẩm định.</p>
        <?php partial('suppliers/_capabilities', ['caps' => $caps, 'canEdit' => true, 'deleteRoute' => 'portal/deleteCapability', 'addRoute' => 'portal/addCapability', 'supplierId' => $s['id']]); ?>
    </div></div>
    <div class="tab-pane fade" id="tab-docs"><div class="card card-body">
        <?php partial('documents/_list', ['docs' => $documents]); ?>
        <form method="post" enctype="multipart/form-data" action="<?= url('portal/uploadDoc') ?>" class="row g-2 border-top pt-3">
            <?= csrf_field() ?>
            <div class="col-md-3"><input class="form-control" name="title" placeholder="Tên tài liệu (vd: GCN ĐKKD)"></div>
            <div class="col-md-3"><select class="form-select" name="category"><?= select_options(['capability' => 'Hồ sơ năng lực', 'legal' => 'Hồ sơ pháp lý', 'other' => 'Khác'], 'legal') ?></select></div>
            <div class="col-md-4"><input class="form-control" type="file" name="files[]" multiple required></div>
            <div class="col-md-2"><button class="btn btn-primary w-100">Tải lên</button></div>
        </form>
    </div></div>
</div>
