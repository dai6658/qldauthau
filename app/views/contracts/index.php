<div class="page-head">
    <div><h2>Hợp đồng & thanh toán</h2><div class="sub">Theo dõi thực hiện hợp đồng, đơn hàng, nghiệm thu, hóa đơn, giải ngân và quyết toán</div></div>
    <a class="btn btn-outline-success" href="<?= url('contracts/export') ?>"><i class="bi bi-filetype-csv"></i> Xuất Excel</a>
</div>
<form class="card card-body mb-3"><input type="hidden" name="r" value="contracts/index">
    <div class="row g-2">
        <div class="col-md-3"><input class="form-control" name="q" value="<?= e(input('q')) ?>" placeholder="Số HĐ, tên, nhà thầu"></div>
        <div class="col-md-2"><select class="form-select" name="status"><?= select_options(labels('contract_status'), input('status'), '-- Trạng thái --') ?></select></div>
        <div class="col-md-2"><select class="form-select" name="project_id"><?= select_options($projects, input('project_id'), '-- Dự án --') ?></select></div>
        <div class="col-md-3"><select class="form-select" name="supplier_id"><?= select_options($suppliers, input('supplier_id'), '-- Nhà thầu --') ?></select></div>
        <div class="col-md-2"><button class="btn btn-outline-primary w-100">Lọc</button></div>
    </div>
</form>
<div class="card"><div class="table-responsive"><table class="table table-hover align-middle mb-0">
    <thead><tr><th>Số HĐ</th><th>Tên hợp đồng</th><th>Nhà thầu</th><th>Dự án / Gói thầu</th><th class="text-end">Giá trị</th><th style="width:170px">Đã thanh toán</th><th>Thời hạn</th><th>Trạng thái</th></tr></thead>
    <?php foreach ($p['items'] as $c): $pct = $c['value'] > 0 ? round($c['paid'] * 100 / $c['value']) : 0; $d = days_left($c['end_date']); ?>
        <tr>
            <td><a class="fw-semibold" href="<?= url('contracts/view', ['id' => $c['id']]) ?>"><?= e($c['code']) ?></a><?= $c['pending_invoices'] ? '<br><span class="badge bg-warning text-dark">' . (int) $c['pending_invoices'] . ' HĐơn chờ kiểm tra</span>' : '' ?></td>
            <td><?= e($c['title']) ?></td><td><?= e($c['supplier_name']) ?></td>
            <td class="small"><?= e($c['project_code']) ?><br><?= e($c['tender_code']) ?></td>
            <td class="text-end"><?= money($c['value']) ?></td>
            <td><div class="progress" style="height:8px"><div class="progress-bar bg-success" style="width:<?= min(100, $pct) ?>%"></div></div><small><?= money($c['paid'], false) ?> (<?= $pct ?>%)</small></td>
            <td class="small"><?= fdate($c['end_date']) ?><?php if ($c['status'] === 'active' && $d !== null && $d <= 30): ?><br><span class="badge bg-<?= $d < 0 ? 'danger' : 'warning text-dark' ?>"><?= $d < 0 ? 'Quá hạn ' . abs($d) . ' ngày' : 'Còn ' . $d . ' ngày' ?></span><?php endif; ?></td>
            <td><?= badge('contract_status', $c['status']) ?></td>
        </tr>
    <?php endforeach; if (!$p['items']): ?><tr><td colspan="8" class="text-center text-muted py-4">Chưa có hợp đồng</td></tr><?php endif; ?>
</table></div><div class="card-footer"><?= pager($p) ?></div></div>
