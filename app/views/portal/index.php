<div class="page-head"><div><h2>Xin chào, <?= e($s['name']) ?></h2><div class="sub">Mã NCC <?= e($s['code']) ?> · <?= badge('supplier_status', $s['status']) ?> · Điểm hiệu suất <?= num($s['rating']) ?>/10</div></div></div>
<?php if ($s['status'] !== 'approved'): ?>
    <div class="alert alert-warning"><i class="bi bi-hourglass-split"></i> Hồ sơ doanh nghiệp đang ở trạng thái <strong><?= e(label('supplier_status', $s['status'])) ?></strong>. <?= e($s['status_note']) ?>
        Vui lòng <a href="<?= url('portal/profile') ?>">hoàn thiện hồ sơ năng lực</a> — bạn chỉ có thể nộp HSDT khi hồ sơ đã được duyệt.</div>
<?php endif; ?>
<div class="row g-3 mb-3">
    <div class="col-md-3"><a class="text-decoration-none" href="<?= url('portal/tenders') ?>"><div class="kpi k1"><div class="l">Gói thầu đang mời</div><div class="v"><?= count($tenders) ?></div><i class="bi bi-megaphone"></i></div></a></div>
    <div class="col-md-3"><a class="text-decoration-none" href="<?= url('portal/bids') ?>"><div class="kpi k2"><div class="l">HSDT đã nộp / trúng thầu</div><div class="v"><?= array_sum($bidStats) - (int) ($bidStats['draft'] ?? 0) - (int) ($bidStats['withdrawn'] ?? 0) ?> / <?= (int) ($bidStats['awarded'] ?? 0) ?></div><i class="bi bi-trophy"></i></div></a></div>
    <div class="col-md-3"><a class="text-decoration-none" href="<?= url('portal/orders') ?>"><div class="kpi k3"><div class="l">Đơn hàng chờ xác nhận</div><div class="v"><?= $pendingOrders ?></div><i class="bi bi-truck"></i></div></a></div>
    <div class="col-md-3"><a class="text-decoration-none" href="<?= url('portal/invoices') ?>"><div class="kpi k4"><div class="l">Hóa đơn chờ kiểm tra / bị trả</div><div class="v"><?= (int) ($invoiceStats['submitted'] ?? 0) ?> / <?= (int) ($invoiceStats['rejected'] ?? 0) ?></div><i class="bi bi-receipt"></i></div></a></div>
</div>
<div class="row">
    <div class="col-lg-7">
        <div class="card"><div class="card-header">Gói thầu đang mời</div><table class="table mb-0 align-middle">
            <?php foreach ($tenders as $t): $d = days_left($t['close_at']); ?>
                <tr><td><a class="fw-semibold" href="<?= url('portal/tender', ['id' => $t['id']]) ?>"><?= e($t['code']) ?> — <?= e($t['title']) ?></a><div class="small text-muted"><?= e(label('method', $t['method'])) ?> · Đóng thầu <?= fdatetime($t['close_at']) ?></div></td>
                    <td><span class="badge bg-<?= $d <= 3 ? 'danger' : 'info text-dark' ?>">còn <?= $d ?> ngày</span></td>
                    <td><?= $t['my_bid_status'] ? badge('bid_status', $t['my_bid_status']) : '<span class="badge bg-light text-dark border">Chưa tham gia</span>' ?></td></tr>
            <?php endforeach; if (!$tenders): ?><tr><td class="text-muted">Hiện chưa có gói thầu phù hợp</td></tr><?php endif; ?>
        </table></div>
        <div class="card"><div class="card-header">Hợp đồng gần đây</div><table class="table table-sm mb-0">
            <?php foreach ($contracts as $c): $pct = $c['value'] > 0 ? round($c['paid'] * 100 / $c['value']) : 0; ?>
                <tr><td><?= e($c['code']) ?> — <?= e($c['title']) ?></td><td class="text-end"><?= money($c['value']) ?></td><td style="width:140px"><div class="progress" style="height:8px"><div class="progress-bar bg-success" style="width:<?= $pct ?>%"></div></div><small>Đã nhận <?= $pct ?>%</small></td><td><?= badge('contract_status', $c['status']) ?></td></tr>
            <?php endforeach; if (!$contracts): ?><tr><td class="text-muted">Chưa có hợp đồng</td></tr><?php endif; ?>
        </table></div>
    </div>
    <div class="col-lg-5"><div class="card"><div class="card-header">Thông báo mới</div><ul class="list-group list-group-flush">
        <?php foreach ($notifications as $n): ?>
            <li class="list-group-item <?= $n['is_read'] ? '' : 'bg-light' ?>"><a class="text-decoration-none" href="<?= url('notifications/open', ['id' => $n['id']]) ?>"><strong class="text-<?= e($n['type']) === 'info' ? 'dark' : e($n['type']) ?>"><?= e($n['title']) ?></strong></a>
                <div class="small"><?= e($n['message']) ?></div><div class="small text-muted"><?= time_ago($n['created_at']) ?></div></li>
        <?php endforeach; if (!$notifications): ?><li class="list-group-item text-muted">Không có thông báo</li><?php endif; ?>
    </ul></div></div>
</div>
