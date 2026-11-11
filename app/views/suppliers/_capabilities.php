<?php /* $caps, $canEdit, $deleteRoute */ ?>
<table class="table table-sm align-middle">
    <thead><tr><th>Loại</th><th>Nội dung</th><th class="text-end">Giá trị</th><th>Năm</th><?php if ($canEdit): ?><th></th><?php endif; ?></tr></thead>
    <?php foreach ($caps as $c): ?>
        <tr><td><?= badge('cap_type', $c['cap_type']) ?></td><td><strong><?= e($c['title']) ?></strong><div class="small text-muted"><?= e($c['description']) ?></div></td>
            <td class="text-end"><?= $c['value_amount'] ? money($c['value_amount']) : '' ?></td><td><?= e($c['year']) ?></td>
            <?php if ($canEdit): ?><td><form method="post" action="<?= url($deleteRoute, ['id' => $c['id']]) ?>" data-confirm="Xóa mục này?"><?= csrf_field() ?><button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button></form></td><?php endif; ?></tr>
    <?php endforeach; if (!$caps): ?><tr><td colspan="5" class="text-muted">Chưa có hồ sơ năng lực</td></tr><?php endif; ?>
</table>
<?php if ($canEdit): ?>
<form method="post" action="<?= url($addRoute) ?>" class="row g-2 border-top pt-3">
    <?= csrf_field() ?><input type="hidden" name="supplier_id" value="<?= (int) $supplierId ?>">
    <div class="col-md-3"><select class="form-select form-select-sm" name="cap_type"><?= select_options(labels('cap_type')) ?></select></div>
    <div class="col-md-4"><input class="form-control form-control-sm" name="title" placeholder="Nội dung (vd: HĐ tương tự...)" required></div>
    <div class="col-md-2"><input class="form-control form-control-sm money" name="value_amount" placeholder="Giá trị (đ)"></div>
    <div class="col-md-1"><input class="form-control form-control-sm" name="year" placeholder="Năm"></div>
    <div class="col-md-2"><button class="btn btn-sm btn-primary w-100"><i class="bi bi-plus"></i> Thêm</button></div>
    <div class="col-12"><input class="form-control form-control-sm" name="description" placeholder="Mô tả chi tiết"></div>
</form>
<?php endif; ?>
