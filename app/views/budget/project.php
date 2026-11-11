<?php
$sum = fn($k) => array_sum(array_column($items, $k));
$canManage = can('budget.manage');
$groups = [];
foreach ($items as $i) { $groups[$i['category']][] = $i; }
?>
<div class="page-head">
    <div><h2>TMĐT — <?= e($project['code']) ?></h2><div class="sub"><?= e($project['name']) ?></div></div>
    <div class="d-flex gap-2">
        <a class="btn btn-light" href="<?= url('budget/index') ?>">← Tổng hợp</a>
        <a class="btn btn-outline-success" href="<?= url('budget/export', ['id' => $project['id']]) ?>"><i class="bi bi-filetype-csv"></i> Xuất Excel</a>
        <?php if ($canManage && !$items): ?><form method="post" action="<?= url('budget/scaffold', ['id' => $project['id']]) ?>"><?= csrf_field() ?><button class="btn btn-primary"><i class="bi bi-magic"></i> Khởi tạo cơ cấu TMĐT</button></form><?php endif; ?>
    </div>
</div>
<div class="row g-3 mb-3">
    <div class="col-md-3"><div class="kpi k1"><div class="l">TMĐT được duyệt</div><div class="v"><?= money($sum('approved_amount'), false) ?></div><div class="l">Dự toán: <?= money($sum('estimated_amount')) ?></div><i class="bi bi-bank"></i></div></div>
    <div class="col-md-3"><div class="kpi k3"><div class="l">Cam kết qua hợp đồng</div><div class="v"><?= money($sum('committed'), false) ?></div><div class="l">+ <?= money($sum('tender_budget')) ?> đang đấu thầu</div><i class="bi bi-file-earmark-check"></i></div></div>
    <div class="col-md-3"><div class="kpi k2"><div class="l">Chi phí thực tế</div><div class="v"><?= money($sum('spent'), false) ?></div><i class="bi bi-cash"></i></div></div>
    <div class="col-md-3"><div class="kpi k4"><div class="l">Còn lại</div><div class="v"><?= money($sum('approved_amount') - $sum('spent'), false) ?></div><i class="bi bi-wallet2"></i></div></div>
</div>

<ul class="nav nav-tabs mb-3">
    <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-items">Cơ cấu TMĐT & chênh lệch</button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-expenses">Chi phí thực tế (<?= count($expenses) ?>)</button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-quotes">So sánh giá thầu</button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-chart">Biểu đồ</button></li>
</ul>
<div class="tab-content">
<div class="tab-pane fade show active" id="tab-items"><div class="card"><div class="table-responsive">
    <table class="table table-sm align-middle mb-0">
        <thead><tr><th>Khoản mục</th><th class="text-end">Dự toán</th><th class="text-end">Được duyệt</th><th class="text-end">Đang đấu thầu</th><th class="text-end">Cam kết HĐ</th><th class="text-end">Đã chi</th><th class="text-end">Chênh lệch (duyệt − chi)</th><th style="width:120px">Sử dụng</th><?php if ($canManage): ?><th></th><?php endif; ?></tr></thead>
        <?php foreach ($groups as $cat => $list): ?>
            <tr class="table-light"><th colspan="<?= $canManage ? 9 : 8 ?>"><?= e(label('invest_category', $cat)) ?></th></tr>
            <?php foreach ($list as $i): $use = $i['approved_amount'] > 0 ? round(max($i['spent'], $i['committed']) * 100 / $i['approved_amount']) : 0; $over = $i['committed'] > $i['approved_amount'] || $i['spent'] > $i['approved_amount']; ?>
                <tr class="<?= $over ? 'table-danger' : '' ?>">
                    <td class="ps-3"><?= e($i['name']) ?><?php if ($i['note']): ?><div class="small text-muted"><?= e($i['note']) ?></div><?php endif; ?></td>
                    <td class="text-end"><?= money($i['estimated_amount'], false) ?></td><td class="text-end fw-semibold"><?= money($i['approved_amount'], false) ?></td>
                    <td class="text-end"><?= money($i['tender_budget'], false) ?></td><td class="text-end"><?= money($i['committed'], false) ?></td>
                    <td class="text-end"><?= money($i['spent'], false) ?></td>
                    <td class="text-end <?= $i['approved_amount'] - $i['spent'] < 0 ? 'text-danger' : 'text-success' ?>"><?= money($i['approved_amount'] - $i['spent'], false) ?></td>
                    <td><div class="progress" style="height:8px"><div class="progress-bar <?= $use > 100 ? 'bg-danger' : ($use > 85 ? 'bg-warning' : 'bg-success') ?>" style="width:<?= min(100, $use) ?>%"></div></div><small><?= $use ?>%</small></td>
                    <?php if ($canManage): ?><td class="text-nowrap">
                        <button type="button" class="btn btn-sm btn-link p-0" onclick='editItem(<?= json_encode(['id' => $i['id'], 'category' => $i['category'], 'name' => $i['name'], 'est' => (float) $i['estimated_amount'], 'appr' => (float) $i['approved_amount'], 'note' => $i['note']], JSON_UNESCAPED_UNICODE | JSON_HEX_APOS | JSON_HEX_TAG) ?>)'><i class="bi bi-pencil"></i></button>
                        <form class="d-inline" method="post" action="<?= url('budget/deleteItem') ?>" data-confirm="Xóa khoản mục?"><?= csrf_field() ?><input type="hidden" name="item_id" value="<?= (int) $i['id'] ?>"><button class="btn btn-sm btn-link text-danger p-0"><i class="bi bi-trash"></i></button></form>
                    </td><?php endif; ?>
                </tr>
            <?php endforeach; ?>
        <?php endforeach; ?>
        <tr class="fw-bold table-primary"><td>TỔNG CỘNG</td><td class="text-end"><?= money($sum('estimated_amount'), false) ?></td><td class="text-end"><?= money($sum('approved_amount'), false) ?></td><td class="text-end"><?= money($sum('tender_budget'), false) ?></td><td class="text-end"><?= money($sum('committed'), false) ?></td><td class="text-end"><?= money($sum('spent'), false) ?></td><td class="text-end"><?= money($sum('approved_amount') - $sum('spent'), false) ?></td><td colspan="<?= $canManage ? 2 : 1 ?>"></td></tr>
    </table></div>
    <?php if ($canManage): ?>
    <div class="card-body border-top"><div class="row">
        <div class="col-lg-8">
            <form method="post" action="<?= url('budget/saveItem') ?>" class="row g-2" id="itemForm">
                <?= csrf_field() ?><input type="hidden" name="project_id" value="<?= (int) $project['id'] ?>"><input type="hidden" name="item_id" id="fItemId">
                <div class="col-md-3"><select class="form-select form-select-sm" name="category" id="fCat"><?= select_options(labels('invest_category')) ?></select></div>
                <div class="col-md-3"><input class="form-control form-control-sm" name="name" id="fName" placeholder="Tên khoản mục" required></div>
                <div class="col-md-2"><input class="form-control form-control-sm money" name="estimated_amount" id="fEst" placeholder="Dự toán"></div>
                <div class="col-md-2"><input class="form-control form-control-sm money" name="approved_amount" id="fAppr" placeholder="Được duyệt"></div>
                <div class="col-md-2"><button class="btn btn-sm btn-primary w-100" id="fBtn">+ Thêm</button></div>
                <div class="col-md-10"><input class="form-control form-control-sm" name="note" id="fNote" placeholder="Căn cứ / ghi chú"></div>
            </form>
        </div>
        <div class="col-lg-4">
            <form method="post" action="<?= url('budget/contingency', ['id' => $project['id']]) ?>" class="d-flex gap-2 align-items-center">
                <?= csrf_field() ?><span class="small text-nowrap">Dự phòng</span>
                <input class="form-control form-control-sm" name="rate" value="5" style="width:70px"><span>%</span>
                <button class="btn btn-sm btn-outline-primary text-nowrap"><i class="bi bi-calculator"></i> Tính tự động</button>
            </form>
        </div>
    </div></div>
    <script>
    function editItem(i) {
        document.getElementById('fItemId').value = i.id; document.getElementById('fCat').value = i.category;
        document.getElementById('fName').value = i.name; document.getElementById('fNote').value = i.note || '';
        document.getElementById('fEst').value = Number(i.est).toLocaleString('vi-VN'); document.getElementById('fAppr').value = Number(i.appr).toLocaleString('vi-VN');
        document.getElementById('fBtn').textContent = 'Cập nhật'; document.getElementById('fName').focus();
    }
    </script>
    <?php endif; ?>
</div></div>

<div class="tab-pane fade" id="tab-expenses"><div class="card">
    <table class="table table-sm align-middle mb-0">
        <thead><tr><th>Ngày</th><th>Nội dung</th><th>Khoản mục</th><th>Hợp đồng</th><th class="text-end">Số tiền</th><th>Người ghi</th><?php if ($canManage): ?><th></th><?php endif; ?></tr></thead>
        <?php foreach ($expenses as $x): ?>
            <tr><td><?= fdate($x['expense_date']) ?></td><td><?= e($x['description']) ?><?= $x['payment_id'] ? ' <span class="badge bg-info text-dark">giải ngân HĐ</span>' : '' ?></td><td class="small"><?= e($x['item_name']) ?></td>
                <td><?= $x['contract_id'] ? '<a href="' . url('contracts/view', ['id' => $x['contract_id']]) . '">' . e($x['contract_code']) . '</a>' : '' ?></td>
                <td class="text-end"><?= money($x['amount']) ?></td><td class="small"><?= e($x['full_name']) ?></td>
                <?php if ($canManage): ?><td><?php if (!$x['payment_id']): ?><form method="post" action="<?= url('budget/deleteExpense') ?>" data-confirm="Xóa khoản chi?"><?= csrf_field() ?><input type="hidden" name="expense_id" value="<?= (int) $x['id'] ?>"><button class="btn btn-sm btn-link text-danger p-0"><i class="bi bi-trash"></i></button></form><?php endif; ?></td><?php endif; ?></tr>
        <?php endforeach; ?>
    </table>
    <?php if ($canManage): ?>
    <form method="post" action="<?= url('budget/addExpense') ?>" class="card-body border-top row g-2">
        <?= csrf_field() ?><input type="hidden" name="project_id" value="<?= (int) $project['id'] ?>">
        <div class="col-md-2"><input class="form-control form-control-sm" type="date" name="expense_date" value="<?= date('Y-m-d') ?>"></div>
        <div class="col-md-3"><input class="form-control form-control-sm" name="description" placeholder="Nội dung chi" required></div>
        <div class="col-md-2"><select class="form-select form-select-sm" name="investment_item_id"><?= select_options(array_column($items, 'name', 'id'), null, '-- Khoản mục --') ?></select></div>
        <div class="col-md-2"><select class="form-select form-select-sm" name="contract_id"><?= select_options($contracts, null, '-- Hợp đồng --') ?></select></div>
        <div class="col-md-2"><input class="form-control form-control-sm money" name="amount" placeholder="Số tiền" required></div>
        <div class="col-md-1"><button class="btn btn-sm btn-primary w-100">Ghi</button></div>
    </form>
    <?php endif; ?>
</div></div>

<div class="tab-pane fade" id="tab-quotes"><div class="card">
    <div class="card-header">So sánh giá gói thầu – giá dự thầu – giá trúng thầu</div>
    <table class="table table-sm mb-0">
        <thead><tr><th>Gói thầu</th><th class="text-end">Giá gói thầu</th><th>Số HSDT</th><th class="text-end">Giá thấp nhất</th><th class="text-end">Giá cao nhất</th><th class="text-end">Giá trúng thầu</th><th>Nhà thầu</th><th class="text-end">Tiết kiệm</th></tr></thead>
        <?php foreach ($tenders as $t): $saving = $t['awarded_price'] !== null ? $t['budget'] - $t['awarded_price'] : null; ?>
            <tr><td><?= e($t['code']) ?> <?= e($t['title']) ?></td><td class="text-end"><?= money($t['budget'], false) ?></td><td><?= (int) $t['bid_count'] ?></td>
                <td class="text-end"><?= money($t['min_price'], false) ?></td><td class="text-end"><?= money($t['max_price'], false) ?></td>
                <td class="text-end fw-bold"><?= money($t['awarded_price'], false) ?></td><td><?= e($t['supplier']) ?></td>
                <td class="text-end text-success"><?= $saving !== null ? money($saving, false) . ' (' . round($saving * 100 / max(1, $t['budget']), 1) . '%)' : '—' ?></td></tr>
        <?php endforeach; if (!$tenders): ?><tr><td colspan="8" class="text-muted">Chưa có gói thầu đã mở thầu</td></tr><?php endif; ?>
    </table>
</div></div>

<div class="tab-pane fade" id="tab-chart"><div class="row">
    <div class="col-lg-7"><div class="card card-body"><canvas id="itemChart" height="200"></canvas></div></div>
    <div class="col-lg-5"><div class="card card-body"><canvas id="cumChart" height="260"></canvas></div></div>
</div></div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    new Chart(document.getElementById('itemChart'), {
        type: 'bar',
        data: { labels: <?= json_encode(array_column($items, 'name'), JSON_UNESCAPED_UNICODE) ?>,
            datasets: [
                { label: 'Được duyệt', data: <?= json_encode(array_map('floatval', array_column($items, 'approved_amount'))) ?>, backgroundColor: '#90caf9' },
                { label: 'Cam kết', data: <?= json_encode(array_map('floatval', array_column($items, 'committed'))) ?>, backgroundColor: '#ffb74d' },
                { label: 'Đã chi', data: <?= json_encode(array_map('floatval', array_column($items, 'spent'))) ?>, backgroundColor: '#2e7d32' }] },
        options: { indexAxis: 'y', plugins: { title: { display: true, text: 'Kế hoạch vs thực tế theo khoản mục' } } }
    });
    const m = <?= json_encode($monthly) ?>; let acc = 0;
    new Chart(document.getElementById('cumChart'), {
        type: 'line',
        data: { labels: m.map(x => x.ym), datasets: [{ label: 'Lũy kế chi phí', data: m.map(x => acc += Number(x.s)), borderColor: '#6a1b9a', fill: false, tension: .2 }] },
        options: { plugins: { title: { display: true, text: 'Lũy kế giải ngân' } } }
    });
});
</script>
