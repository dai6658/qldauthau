<div class="page-head">
    <div><h2><?= e($s['name']) ?></h2><div class="sub"><?= e($s['code']) ?> · MST <?= e($s['tax_code']) ?> · <?= badge('supplier_status', $s['status']) ?> <?= $s['status_note'] ? '· ' . e($s['status_note']) : '' ?></div></div>
    <div class="d-flex gap-2">
        <?php if (can('suppliers.manage')): ?>
            <a class="btn btn-outline-primary" href="<?= url('suppliers/form', ['id' => $s['id']]) ?>"><i class="bi bi-pencil"></i> Sửa</a>
            <button class="btn btn-outline-dark" data-bs-toggle="modal" data-bs-target="#statusModal"><i class="bi bi-flag"></i> Thẩm định / đổi trạng thái</button>
        <?php endif; ?>
        <?php if (can('suppliers.evaluate')): ?><button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#evalModal"><i class="bi bi-star"></i> Đánh giá hiệu suất</button><?php endif; ?>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-3"><div class="kpi k1"><div class="l">Điểm hiệu suất</div><div class="v"><?= num($s['rating']) ?>/10</div><i class="bi bi-star"></i></div></div>
    <div class="col-md-3"><div class="kpi k2"><div class="l">Tỷ lệ trúng thầu</div><div class="v"><?= $stats['bids'] ? round($stats['wins'] * 100 / $stats['bids']) : 0 ?>%</div><div class="l"><?= $stats['wins'] ?>/<?= $stats['bids'] ?> hồ sơ</div><i class="bi bi-trophy"></i></div></div>
    <div class="col-md-3"><div class="kpi k3"><div class="l">Điểm kỹ thuật TB</div><div class="v"><?= $stats['avg_tech'] !== null ? num($stats['avg_tech'], 1) : '—' ?></div><i class="bi bi-gear"></i></div></div>
    <div class="col-md-3"><div class="kpi k4"><div class="l">Tổng giá trị hợp đồng</div><div class="v"><?= money($stats['contract_value'], false) ?></div><i class="bi bi-file-earmark-check"></i></div></div>
</div>

<ul class="nav nav-tabs mb-3">
    <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-info">Thông tin</button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-caps">Năng lực (<?= count($caps) ?>)</button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-perf">Đánh giá hiệu suất (<?= count($evals) ?>)</button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-bids">Lịch sử đấu thầu (<?= count($bids) ?>)</button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-contracts">Hợp đồng (<?= count($contracts) ?>)</button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-docs">Tài liệu (<?= count($documents) ?>)</button></li>
</ul>
<div class="tab-content">
    <div class="tab-pane fade show active" id="tab-info"><div class="row">
        <div class="col-lg-7"><div class="card card-body"><dl class="dl-grid">
            <dt>Địa chỉ</dt><dd><?= e($s['address']) ?></dd>
            <dt>Người đại diện</dt><dd><?= e($s['representative']) ?></dd>
            <dt>Liên hệ</dt><dd><?= e($s['phone']) ?> · <?= e($s['email']) ?> · <?= e($s['website']) ?></dd>
            <dt>Lĩnh vực</dt><dd><?= e($s['business_field']) ?></dd>
            <dt>Năm thành lập</dt><dd><?= e($s['established_year']) ?></dd>
            <dt>Vốn điều lệ</dt><dd><?= money($s['charter_capital']) ?></dd>
            <dt>Doanh thu BQ năm</dt><dd><?= money($s['annual_revenue']) ?></dd>
            <dt>Số lao động</dt><dd><?= e($s['employees']) ?></dd>
            <dt>Tài khoản</dt><dd><?= e($s['bank_account']) ?> — <?= e($s['bank_name']) ?></dd>
            <dt>Ngày đăng ký</dt><dd><?= fdatetime($s['created_at']) ?></dd>
        </dl></div></div>
        <div class="col-lg-5"><div class="card"><div class="card-header">Tài khoản cổng NCC</div><table class="table table-sm mb-0">
            <?php foreach ($users as $u): ?><tr><td><?= e($u['username']) ?></td><td><?= e($u['full_name']) ?></td><td class="small"><?= fdatetime($u['last_login_at']) ?></td></tr><?php endforeach; ?>
            <?php if (!$users): ?><tr><td class="text-muted">Chưa có tài khoản (tạo tại Người dùng)</td></tr><?php endif; ?>
        </table></div></div>
    </div></div>

    <div class="tab-pane fade" id="tab-caps"><div class="card card-body">
        <?php partial('suppliers/_capabilities', ['caps' => $caps, 'canEdit' => can('suppliers.manage'), 'deleteRoute' => 'suppliers/deleteCapability', 'addRoute' => 'suppliers/addCapability', 'supplierId' => $s['id']]); ?>
    </div></div>

    <div class="tab-pane fade" id="tab-perf"><div class="row">
        <div class="col-lg-4"><div class="card card-body"><canvas id="radar" height="260"></canvas></div></div>
        <div class="col-lg-8"><div class="card"><table class="table table-sm mb-0">
            <thead><tr><th>Ngày</th><th>Hợp đồng</th><th>Chất lượng</th><th>Tiến độ</th><th>Giá</th><th>Dịch vụ</th><th>TB</th><th>Nhận xét</th><th>Người đánh giá</th></tr></thead>
            <?php foreach ($evals as $ev): ?><tr><td><?= fdate($ev['created_at']) ?></td><td><?= e($ev['contract_code']) ?></td><td><?= (int) $ev['quality_score'] ?></td><td><?= (int) $ev['schedule_score'] ?></td><td><?= (int) $ev['price_score'] ?></td><td><?= (int) $ev['service_score'] ?></td><td><strong><?= num($ev['overall']) ?></strong></td><td class="small"><?= e($ev['comment']) ?></td><td class="small"><?= e($ev['full_name']) ?></td></tr><?php endforeach; ?>
        </table></div></div>
    </div></div>

    <div class="tab-pane fade" id="tab-bids"><div class="card"><table class="table table-sm mb-0">
        <thead><tr><th>HSDT</th><th>Gói thầu</th><th class="text-end">Giá dự thầu</th><th>Điểm KT</th><th>Điểm tổng</th><th>Xếp hạng</th><th>Kết quả</th></tr></thead>
        <?php foreach ($bids as $b): ?><tr><td><?= e($b['code']) ?></td><td><?= e($b['tender_code']) ?> – <?= e($b['tender_title']) ?></td>
            <td class="text-end"><?= Access::bidsSealed(['status' => $b['tender_status']]) ? '<i class="bi bi-lock"></i> Niêm phong' : money($b['total_price']) ?></td>
            <td><?= num($b['tech_score']) ?></td><td><?= num($b['final_score']) ?></td><td><?= e($b['rank_no']) ?></td><td><?= badge('bid_status', $b['status']) ?></td></tr><?php endforeach; ?>
    </table></div></div>

    <div class="tab-pane fade" id="tab-contracts"><div class="card"><table class="table table-sm mb-0">
        <thead><tr><th>Số HĐ</th><th>Tên</th><th class="text-end">Giá trị</th><th class="text-end">Đã thanh toán</th><th>Thời hạn</th><th>Trạng thái</th></tr></thead>
        <?php foreach ($contracts as $c): ?><tr><td><?= can('contracts.view') ? '<a href="' . url('contracts/view', ['id' => $c['id']]) . '">' . e($c['code']) . '</a>' : e($c['code']) ?></td><td><?= e($c['title']) ?></td>
            <td class="text-end"><?= money($c['value']) ?></td><td class="text-end"><?= money($c['paid']) ?></td><td><?= fdate($c['end_date']) ?></td><td><?= badge('contract_status', $c['status']) ?></td></tr><?php endforeach; ?>
    </table></div></div>

    <div class="tab-pane fade" id="tab-docs"><div class="card card-body">
        <?php partial('documents/_list', ['docs' => $documents]); ?>
        <?php if (can('suppliers.manage')): ?>
        <form method="post" enctype="multipart/form-data" action="<?= url('suppliers/uploadDoc') ?>" class="row g-2 border-top pt-3">
            <?= csrf_field() ?><input type="hidden" name="supplier_id" value="<?= (int) $s['id'] ?>">
            <div class="col-md-4"><input class="form-control" name="title" placeholder="Tiêu đề (tùy chọn)"></div>
            <div class="col-md-6"><input class="form-control" type="file" name="files[]" multiple required></div>
            <div class="col-md-2"><button class="btn btn-primary w-100">Tải lên</button></div>
        </form>
        <?php endif; ?>
    </div></div>
</div>

<div class="modal fade" id="statusModal"><div class="modal-dialog"><form method="post" action="<?= url('suppliers/setStatus', ['id' => $s['id']]) ?>" class="modal-content">
    <?= csrf_field() ?>
    <div class="modal-header"><h5 class="modal-title">Thẩm định nhà cung cấp</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
        <label class="form-label">Trạng thái</label><select class="form-select mb-3" name="status"><?= select_options(labels('supplier_status'), $s['status']) ?></select>
        <label class="form-label">Ghi chú / lý do</label><textarea class="form-control" name="status_note" rows="3"><?= e($s['status_note']) ?></textarea>
    </div>
    <div class="modal-footer"><button class="btn btn-primary">Cập nhật</button></div>
</form></div></div>

<div class="modal fade" id="evalModal"><div class="modal-dialog"><form method="post" action="<?= url('suppliers/evaluate') ?>" class="modal-content">
    <?= csrf_field() ?><input type="hidden" name="supplier_id" value="<?= (int) $s['id'] ?>">
    <div class="modal-header"><h5 class="modal-title">Đánh giá hiệu suất (thang 1–10)</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
        <label class="form-label">Hợp đồng</label>
        <select class="form-select mb-3" name="contract_id"><?= select_options(array_column($contracts, 'code', 'id'), null, '-- Không gắn hợp đồng --') ?></select>
        <?php foreach (['quality_score' => 'Chất lượng sản phẩm/dịch vụ', 'schedule_score' => 'Đảm bảo tiến độ', 'price_score' => 'Giá cả cạnh tranh', 'service_score' => 'Hỗ trợ, hậu mãi'] as $k => $lbl): ?>
            <label class="form-label"><?= $lbl ?>: <output id="o_<?= $k ?>">8</output></label>
            <input type="range" class="form-range mb-2" min="1" max="10" value="8" name="<?= $k ?>" oninput="document.getElementById('o_<?= $k ?>').value=this.value">
        <?php endforeach; ?>
        <label class="form-label">Nhận xét</label><textarea class="form-control" name="comment" rows="2"></textarea>
    </div>
    <div class="modal-footer"><button class="btn btn-success">Lưu đánh giá</button></div>
</form></div></div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    new Chart(document.getElementById('radar'), {
        type: 'radar',
        data: { labels: ['Chất lượng', 'Tiến độ', 'Giá', 'Dịch vụ'],
            datasets: [{ label: 'Điểm trung bình', data: <?= json_encode(array_map(fn($x) => round($x, 2), array_values($stats['avg']))) ?>, backgroundColor: 'rgba(21,101,192,.2)', borderColor: '#1565c0' }] },
        options: { scales: { r: { min: 0, max: 10 } } }
    });
});
</script>
