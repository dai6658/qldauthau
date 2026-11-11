<div class="page-head">
    <div><h2>Xin chào, <?= e(auth()['full_name']) ?></h2><div class="sub">Tổng quan hoạt động đấu thầu, mua sắm — <?= date('d/m/Y') ?></div></div>
    <div class="d-flex gap-2">
        <?php if (can('tenders.manage')): ?><a href="<?= url('tenders/create') ?>" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Tạo HSMT</a><?php endif; ?>
        <?php if (can('reports.view')): ?><a href="<?= url('reports/index') ?>" class="btn btn-outline-primary"><i class="bi bi-bar-chart"></i> Báo cáo</a><?php endif; ?>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-6 col-xl-3"><div class="kpi k1"><div class="l">Gói thầu đang mời thầu</div><div class="v"><?= $kpi['tenders_open'] ?></div><div class="l"><?= $kpi['tenders_eval'] ?> gói đang đánh giá · tổng <?= $kpi['tenders_total'] ?></div><i class="bi bi-megaphone"></i></div></div>
    <div class="col-md-6 col-xl-3"><div class="kpi k2"><div class="l">Giá trị trúng thầu</div><div class="v"><?= money($kpi['awarded_value'], false) ?></div><div class="l">Tiết kiệm <?= money($kpi['savings']) ?> · thành công <?= $kpi['success_rate'] ?>%</div><i class="bi bi-trophy"></i></div></div>
    <div class="col-md-6 col-xl-3"><div class="kpi k3"><div class="l">Tổng mức đầu tư / đã chi</div><div class="v"><?= $kpi['tmdt'] ? round($kpi['spent'] * 100 / $kpi['tmdt'], 1) : 0 ?>%</div><div class="l"><?= money($kpi['spent']) ?> / <?= money($kpi['tmdt']) ?></div><i class="bi bi-cash-stack"></i></div></div>
    <div class="col-md-6 col-xl-3"><div class="kpi k4"><div class="l">Nhà cung cấp đã duyệt</div><div class="v"><?= $kpi['suppliers'] ?></div><div class="l"><?= $kpi['suppliers_pending'] ?> chờ duyệt · <?= $kpi['approvals'] ?> hồ sơ chờ phê duyệt</div><i class="bi bi-people"></i></div></div>
</div>

<div class="row">
    <div class="col-xl-8">
        <div class="card"><div class="card-header">TMĐT, giá trị hợp đồng và chi phí thực tế theo dự án</div>
            <div class="card-body"><canvas id="chartBudget" height="120"></canvas></div></div>
    </div>
    <div class="col-xl-4">
        <div class="card"><div class="card-header">Gói thầu theo trạng thái</div>
            <div class="card-body"><canvas id="chartStatus" height="220"></canvas></div></div>
    </div>
</div>

<div class="row">
    <div class="col-xl-6">
        <div class="card"><div class="card-header d-flex justify-content-between"><span><i class="bi bi-alarm"></i> Gói thầu sắp đóng</span><a href="<?= url('tenders/index', ['status' => 'published']) ?>" class="small">Xem tất cả</a></div>
            <table class="table table-sm table-hover mb-0">
                <thead><tr><th>Mã</th><th>Gói thầu</th><th>Đóng thầu</th><th>HSDT</th></tr></thead>
                <?php foreach ($closingSoon as $t): $d = days_left($t['close_at']); ?>
                    <tr><td><a href="<?= url('tenders/view', ['id' => $t['id']]) ?>"><?= e($t['code']) ?></a></td><td><?= e($t['title']) ?></td>
                        <td><?= fdatetime($t['close_at']) ?> <span class="badge bg-<?= $d <= 3 ? 'danger' : 'secondary' ?>">còn <?= $d ?> ngày</span></td><td><?= (int) $t['bid_count'] ?></td></tr>
                <?php endforeach; if (!$closingSoon): ?><tr><td colspan="4" class="text-muted text-center">Không có gói thầu đang mở</td></tr><?php endif; ?>
            </table>
        </div>
        <div class="card"><div class="card-header"><i class="bi bi-list-check"></i> Công việc sắp đến hạn / quá hạn</div>
            <table class="table table-sm mb-0">
                <thead><tr><th>Công việc</th><th>Người thực hiện</th><th>Hạn</th><th>Tiến độ</th></tr></thead>
                <?php foreach ($tasksDue as $t): $d = days_left($t['due_date']); ?>
                    <tr><td><a href="<?= url('plans/view', ['id' => $t['plan_id']]) ?>"><?= e($t['name']) ?></a></td><td><?= e($t['full_name']) ?></td>
                        <td><?= fdate($t['due_date']) ?> <?= $d < 0 ? '<span class="badge bg-danger">quá ' . abs($d) . ' ngày</span>' : '' ?></td>
                        <td style="width:110px"><div class="progress" style="height:8px"><div class="progress-bar" style="width:<?= (int) $t['progress'] ?>%"></div></div></td></tr>
                <?php endforeach; if (!$tasksDue): ?><tr><td colspan="4" class="text-muted text-center">Không có</td></tr><?php endif; ?>
            </table>
        </div>
    </div>
    <div class="col-xl-6">
        <div class="card"><div class="card-header">Giải ngân theo tháng năm <?= $year ?></div>
            <div class="card-body"><canvas id="chartMonthly" height="130"></canvas></div></div>
        <div class="card"><div class="card-header"><i class="bi bi-cash-coin"></i> Kế hoạch thanh toán sắp tới</div>
            <table class="table table-sm mb-0">
                <thead><tr><th>Hợp đồng</th><th>Đợt</th><th class="text-end">Số tiền</th><th>Hạn</th><th></th></tr></thead>
                <?php foreach ($paymentsDue as $p): ?>
                    <tr><td><a href="<?= url('contracts/view', ['id' => $p['contract_id'], 'tab' => 'payments']) ?>"><?= e($p['contract_code']) ?></a></td><td><?= e($p['milestone']) ?></td>
                        <td class="text-end"><?= money($p['amount']) ?></td><td><?= fdate($p['due_date']) ?></td><td><?= badge('payment_status', $p['status']) ?></td></tr>
                <?php endforeach; ?>
            </table>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-xl-6">
        <div class="card"><div class="card-header">Nhà cung cấp hiệu suất cao</div>
            <table class="table table-sm mb-0">
                <thead><tr><th>Nhà cung cấp</th><th>Điểm</th><th>Dự thầu</th><th>Trúng</th><th>Tỷ lệ</th></tr></thead>
                <?php foreach ($topSuppliers as $s): ?>
                    <tr><td><a href="<?= url('suppliers/view', ['id' => $s['id']]) ?>"><?= e($s['name']) ?></a></td><td><span class="badge bg-success"><?= num($s['rating'], 2) ?>/10</span></td>
                        <td><?= (int) $s['bids'] ?></td><td><?= (int) $s['wins'] ?></td><td><?= $s['bids'] ? round($s['wins'] * 100 / $s['bids']) : 0 ?>%</td></tr>
                <?php endforeach; ?>
            </table>
        </div>
    </div>
    <div class="col-xl-6">
        <div class="card"><div class="card-header">Hoạt động gần đây</div>
            <div class="card-body"><ul class="timeline mb-0">
                <?php foreach ($recent as $a): ?>
                    <li><strong><?= e($a['full_name'] ?: $a['username']) ?></strong> — <?= e(Audit::actionLabel($a['action'])) ?> <?= $a['entity_type'] ? e(label('entity', $a['entity_type'])) . ' #' . (int) $a['entity_id'] : '' ?>
                        <div class="small text-muted"><?= e(mb_strimwidth((string) $a['details'], 0, 90, '…')) ?> · <?= time_ago($a['created_at']) ?></div></li>
                <?php endforeach; ?>
            </ul></div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const statusLabels = <?= json_encode(array_map(fn($k) => label('tender_status', $k), array_keys($byStatus)), JSON_UNESCAPED_UNICODE) ?>;
    new Chart(document.getElementById('chartStatus'), {
        type: 'doughnut',
        data: { labels: statusLabels, datasets: [{ data: <?= json_encode(array_values(array_map('intval', $byStatus))) ?>,
            backgroundColor: ['#90a4ae','#ffb300','#29b6f6','#1e88e5','#37474f','#fb8c00','#43a047','#e53935'] }] },
        options: { plugins: { legend: { position: 'bottom' } } }
    });
    const fmt = v => (v / 1e9).toLocaleString('vi-VN', { maximumFractionDigits: 2 }) + ' tỷ';
    new Chart(document.getElementById('chartBudget'), {
        type: 'bar',
        data: {
            labels: <?= json_encode(array_column($budgetByProject, 'code')) ?>,
            datasets: [
                { label: 'TMĐT được duyệt', data: <?= json_encode(array_map('floatval', array_column($budgetByProject, 'tmdt'))) ?>, backgroundColor: '#90caf9' },
                { label: 'Giá trị hợp đồng', data: <?= json_encode(array_map('floatval', array_column($budgetByProject, 'committed'))) ?>, backgroundColor: '#ffb74d' },
                { label: 'Chi phí thực tế', data: <?= json_encode(array_map('floatval', array_column($budgetByProject, 'spent'))) ?>, backgroundColor: '#1565c0' }
            ]
        },
        options: { scales: { y: { ticks: { callback: fmt } } }, plugins: { tooltip: { callbacks: { label: c => c.dataset.label + ': ' + c.raw.toLocaleString('vi-VN') + ' đ' } } } }
    });
    new Chart(document.getElementById('chartMonthly'), {
        type: 'line',
        data: { labels: ['T1','T2','T3','T4','T5','T6','T7','T8','T9','T10','T11','T12'],
            datasets: [{ label: 'Giải ngân', data: <?= json_encode(array_values($monthly)) ?>, borderColor: '#2e7d32', backgroundColor: 'rgba(46,125,50,.15)', fill: true, tension: .3 }] },
        options: { scales: { y: { ticks: { callback: fmt } } } }
    });
});
</script>
