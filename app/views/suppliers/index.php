<div class="page-head">
    <div><h2>Nhà cung cấp / Nhà thầu</h2><div class="sub">Cơ sở dữ liệu tập trung: đăng ký → thẩm định → đánh giá hiệu suất</div></div>
    <?php if (can('suppliers.manage')): ?><a class="btn btn-primary" href="<?= url('suppliers/form') ?>"><i class="bi bi-plus-lg"></i> Thêm nhà cung cấp</a><?php endif; ?>
</div>
<form class="card card-body mb-3">
    <input type="hidden" name="r" value="suppliers/index">
    <div class="row g-2">
        <div class="col-md-5"><input class="form-control" name="q" value="<?= e(input('q')) ?>" placeholder="Tên, mã số thuế, lĩnh vực..."></div>
        <div class="col-md-3"><select class="form-select" name="status"><?= select_options(labels('supplier_status'), input('status'), '-- Trạng thái --') ?></select></div>
        <div class="col-md-2"><select class="form-select" name="sort"><?= select_options(['' => 'Mới nhất', 'rating' => 'Điểm hiệu suất'], input('sort')) ?></select></div>
        <div class="col-md-2"><button class="btn btn-outline-primary w-100">Lọc</button></div>
    </div>
</form>
<div class="card"><div class="table-responsive">
<table class="table table-hover align-middle mb-0">
    <thead><tr><th>Mã</th><th>Tên doanh nghiệp</th><th>MST</th><th>Lĩnh vực</th><th>Điểm</th><th>Dự thầu / Trúng</th><th class="text-end">Giá trị HĐ</th><th>Trạng thái</th></tr></thead>
    <?php foreach ($p['items'] as $s): ?>
        <tr>
            <td><?= e($s['code']) ?></td>
            <td><a class="fw-semibold" href="<?= url('suppliers/view', ['id' => $s['id']]) ?>"><?= e($s['name']) ?></a><div class="small text-muted"><?= e($s['representative']) ?> · <?= e($s['phone']) ?></div></td>
            <td><?= e($s['tax_code']) ?></td><td class="small"><?= e($s['business_field']) ?></td>
            <td><?php $r = (float) $s['rating']; ?><span class="badge bg-<?= $r >= 8 ? 'success' : ($r >= 6 ? 'warning text-dark' : 'secondary') ?>"><?= num($r) ?></span></td>
            <td><?= (int) $s['bid_count'] ?> / <?= (int) $s['win_count'] ?></td>
            <td class="text-end"><?= money($s['contract_value']) ?></td>
            <td><?= badge('supplier_status', $s['status']) ?></td>
        </tr>
    <?php endforeach; ?>
</table></div>
<div class="card-footer"><?= pager($p) ?></div></div>
