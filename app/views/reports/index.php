<div class="page-head">
    <div><h2>Báo cáo & phân tích</h2><div class="sub">Kỳ báo cáo: <?= fdate($from) ?> – <?= fdate($to) ?></div></div>
    <div class="d-flex gap-2 no-print">
        <div class="dropdown"><button class="btn btn-outline-success dropdown-toggle" data-bs-toggle="dropdown"><i class="bi bi-filetype-csv"></i> Xuất Excel</button>
            <ul class="dropdown-menu">
                <li><a class="dropdown-item" href="<?= url('reports/export', ['type' => 'tenders', 'from' => $from, 'to' => $to]) ?>">Báo cáo gói thầu</a></li>
                <li><a class="dropdown-item" href="<?= url('reports/export', ['type' => 'suppliers']) ?>">Hiệu suất nhà cung cấp</a></li>
                <li><a class="dropdown-item" href="<?= url('reports/export', ['type' => 'projects']) ?>">Chi phí theo dự án</a></li>
            </ul></div>
        <button class="btn btn-outline-secondary" onclick="window.print()"><i class="bi bi-printer"></i> In</button>
    </div>
</div>
<form class="card card-body mb-3 no-print"><input type="hidden" name="r" value="reports/index">
    <div class="row g-2 align-items-end">
        <div class="col-md-3"><label class="form-label">Từ ngày</label><input class="form-control" type="date" name="from" value="<?= e($from) ?>"></div>
        <div class="col-md-3"><label class="form-label">Đến ngày</label><input class="form-control" type="date" name="to" value="<?= e($to) ?>"></div>
        <div class="col-md-2"><button class="btn btn-primary w-100">Xem báo cáo</button></div>
    </div>
</form>

<div class="row g-3 mb-3">
    <div class="col-md-2"><div class="kpi k1"><div class="l">Gói thầu trong kỳ</div><div class="v"><?= $kpi['total'] ?></div><div class="l"><?= $kpi['published'] ?> đã công bố</div></div></div>
    <div class="col-md-2"><div class="kpi k2"><div class="l">Tỷ lệ thành công</div><div class="v"><?= $kpi['success_rate'] !== null ? $kpi['success_rate'] . '%' : '—' ?></div><div class="l"><?= $kpi['awarded'] ?> trao thầu</div></div></div>
    <div class="col-md-2"><div class="kpi k3"><div class="l">HSDT bình quân/gói</div><div class="v"><?= num($kpi['avg_bids'], 1) ?></div><div class="l"><?= $kpi['single_bid'] ?> gói chỉ 1 HSDT</div></div></div>
    <div class="col-md-3"><div class="kpi k4"><div class="l">Giá trị trúng thầu</div><div class="v"><?= money($kpi['value_awarded'], false) ?></div><div class="l">trên giá gói thầu <?= money($kpi['budget_awarded'], false) ?></div></div></div>
    <div class="col-md-3"><div class="kpi k2"><div class="l">Tiết kiệm ngân sách</div><div class="v"><?= money($kpi['saving'], false) ?></div><div class="l">Tỷ lệ tiết kiệm <?= num($kpi['saving_rate']) ?>%</div></div></div>
</div>

<div class="row">
    <div class="col-lg-4"><div class="card"><div class="card-header">Cơ cấu trạng thái gói thầu</div><div class="card-body"><canvas id="cStatus" height="230"></canvas></div></div></div>
    <div class="col-lg-4"><div class="card"><div class="card-header">Thời gian xử lý bình quân (ngày)</div><div class="card-body">
        <?php $maxStage = max(1, max(array_map('floatval', array_filter($stages, fn($v) => $v !== null)) ?: [1])); ?>
        <?php foreach ($stages as $name => $d): ?>
            <div class="mb-2"><div class="d-flex justify-content-between small"><span><?= e($name) ?></span><strong><?= $d !== null ? num($d, 1) . ' ngày' : '—' ?></strong></div>
            <div class="progress" style="height:6px"><div class="progress-bar <?= str_starts_with($name, 'Tổng') ? 'bg-dark' : '' ?>" style="width:<?= $d ? min(100, $d * 100 / $maxStage) : 0 ?>%"></div></div></div>
        <?php endforeach; ?>
        <p class="small text-muted mb-0">Tính từ nhật ký kiểm toán — giúp phát hiện điểm nghẽn quy trình.</p>
    </div></div></div>
    <div class="col-lg-4"><div class="card"><div class="card-header">Theo hình thức lựa chọn</div><table class="table table-sm mb-0">
        <thead><tr><th>Hình thức</th><th>Số gói</th><th>HSDT BQ</th></tr></thead>
        <?php foreach ($byMethod as $m): ?><tr><td><?= e(label('method', $m['method'])) ?></td><td><?= (int) $m['n'] ?></td><td><?= num($m['avg_bids'], 1) ?></td></tr><?php endforeach; ?>
    </table></div>
    <div class="card"><div class="card-header">Hợp đồng</div><div class="card-body small">
        <div class="d-flex justify-content-between"><span>Tổng số / giá trị</span><strong><?= (int) $contracts['n'] ?> / <?= money($contracts['v']) ?></strong></div>
        <div class="d-flex justify-content-between"><span>Đang thực hiện</span><strong><?= (int) $contracts['active'] ?></strong></div>
        <div class="d-flex justify-content-between"><span>Đã quyết toán</span><strong><?= (int) $contracts['completed'] ?></strong></div>
        <div class="d-flex justify-content-between text-danger"><span>Quá hạn chưa hoàn thành</span><strong><?= (int) $contracts['overdue'] ?></strong></div>
        <div class="d-flex justify-content-between"><span>Chênh lệch HĐ − quyết toán</span><strong><?= money($contracts['settle_diff']) ?></strong></div>
    </div></div></div>
</div>

<div class="row">
    <div class="col-lg-7"><div class="card"><div class="card-header">Giá trị gói thầu công bố & giải ngân theo tháng</div><div class="card-body"><canvas id="cMonthly" height="140"></canvas></div></div></div>
    <div class="col-lg-5"><div class="card"><div class="card-header">Hiệu quả theo lĩnh vực</div><table class="table table-sm mb-0">
        <thead><tr><th>Lĩnh vực</th><th>Số gói</th><th class="text-end">Giá gói thầu</th><th class="text-end">Tiết kiệm</th></tr></thead>
        <?php foreach ($byCategory as $c): $sv = $c['awarded_budget'] ? ($c['awarded_budget'] - $c['awarded']) * 100 / $c['awarded_budget'] : null; ?>
            <tr><td><?= e(label('category', $c['category'])) ?></td><td><?= (int) $c['n'] ?></td><td class="text-end"><?= money($c['budget'], false) ?></td><td class="text-end text-success"><?= $sv !== null ? num($sv, 1) . '%' : '—' ?></td></tr>
        <?php endforeach; ?>
    </table></div></div>
</div>

<div class="card"><div class="card-header">Hiệu suất nhà cung cấp</div><div class="table-responsive"><table class="table table-sm align-middle mb-0">
    <thead><tr><th>Nhà cung cấp</th><th>Trạng thái</th><th>HSDT</th><th>Trúng</th><th>Tỷ lệ trúng</th><th>Điểm KT TB</th><th>Điểm hiệu suất</th><th>Số lần ĐG</th><th class="text-end">Giá trị HĐ</th><th>HĐ trễ hạn</th></tr></thead>
    <?php foreach ($suppliers as $s): ?>
        <tr><td><?= e($s['code']) ?> — <?= e($s['name']) ?></td><td><?= badge('supplier_status', $s['status']) ?></td><td><?= (int) $s['bids'] ?></td><td><?= (int) $s['wins'] ?></td>
            <td><?= $s['bids'] ? round($s['wins'] * 100 / $s['bids']) . '%' : '—' ?></td><td><?= num($s['avg_tech'], 1) ?></td>
            <td><div class="progress" style="height:8px;width:90px"><div class="progress-bar bg-<?= $s['rating'] >= 8 ? 'success' : ($s['rating'] >= 6 ? 'warning' : 'danger') ?>" style="width:<?= $s['rating'] * 10 ?>%"></div></div><small><?= num($s['rating'], 1) ?></small></td>
            <td><?= (int) $s['evals'] ?></td><td class="text-end"><?= money($s['contract_value']) ?></td><td class="<?= $s['late_contracts'] ? 'text-danger fw-bold' : '' ?>"><?= (int) $s['late_contracts'] ?></td></tr>
    <?php endforeach; ?>
</table></div></div>

<div class="card"><div class="card-header">Kiểm soát chi phí theo dự án</div><div class="row g-0">
    <div class="col-lg-7"><table class="table table-sm mb-0">
        <thead><tr><th>Dự án</th><th class="text-end">TMĐT</th><th class="text-end">Cam kết</th><th class="text-end">Đã chi</th><th>Giải ngân</th><th>Việc trễ</th></tr></thead>
        <?php foreach ($projects as $p): $pct = $p['approved'] > 0 ? round($p['spent'] * 100 / $p['approved'], 1) : 0; ?>
            <tr><td><?= e($p['code']) ?><div class="small text-muted"><?= e($p['name']) ?></div></td><td class="text-end"><?= money($p['approved'], false) ?></td>
                <td class="text-end <?= $p['committed'] > $p['approved'] ? 'text-danger' : '' ?>"><?= money($p['committed'], false) ?></td><td class="text-end"><?= money($p['spent'], false) ?></td>
                <td><?= $pct ?>%</td><td class="<?= $p['late_tasks'] ? 'text-danger' : '' ?>"><?= (int) $p['late_tasks'] ?></td></tr>
        <?php endforeach; ?>
    </table></div>
    <div class="col-lg-5 p-3"><canvas id="cProjects" height="220"></canvas></div>
</div></div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const statusLabels = <?= json_encode(array_map(fn($k) => label('tender_status', $k), array_keys($byStatus)), JSON_UNESCAPED_UNICODE) ?>;
    new Chart(document.getElementById('cStatus'), { type: 'doughnut',
        data: { labels: statusLabels, datasets: [{ data: <?= json_encode(array_values($byStatus)) ?>, backgroundColor: ['#90a4ae','#ffb300','#29b6f6','#1e88e5','#546e7a','#8e24aa','#43a047','#e53935'] }] },
        options: { plugins: { legend: { position: 'bottom' } } } });
    const m = <?= json_encode($monthly) ?>, d = <?= json_encode($disbursed) ?>;
    const months = [...new Set(m.map(x => x.ym).concat(d.map(x => x.ym)))].sort();
    new Chart(document.getElementById('cMonthly'), {
        data: { labels: months, datasets: [
            { type: 'bar', label: 'Giá trị gói thầu công bố', data: months.map(k => Number((m.find(x => x.ym === k) || {}).budget || 0)), backgroundColor: '#90caf9' },
            { type: 'line', label: 'Giải ngân', data: months.map(k => Number((d.find(x => x.ym === k) || {}).s || 0)), borderColor: '#2e7d32', tension: .2 }] } });
    const p = <?= json_encode($projects, JSON_UNESCAPED_UNICODE) ?>;
    new Chart(document.getElementById('cProjects'), { type: 'bar',
        data: { labels: p.map(x => x.code), datasets: [
            { label: 'TMĐT', data: p.map(x => Number(x.approved)), backgroundColor: '#90caf9' },
            { label: 'Cam kết', data: p.map(x => Number(x.committed)), backgroundColor: '#ffb74d' },
            { label: 'Đã chi', data: p.map(x => Number(x.spent)), backgroundColor: '#2e7d32' }] } });
});
</script>
