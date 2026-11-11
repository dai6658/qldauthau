<?php
$active = array_values(array_filter($bids, fn($b) => in_array($b['status'], ['submitted', 'valid'], true)));
$lowest = [];
foreach ($items as $it) {
    $prices = [];
    foreach ($bids as $b) { if (isset($bidItems[$b['id']][$it['id']])) $prices[] = (float) $bidItems[$b['id']][$it['id']]['unit_price']; }
    $lowest[$it['id']] = $prices ? min($prices) : null;
}
$maxTotal = array_sum(array_column($criteria, 'max_score'));
?>
<div class="page-head">
    <div><h2>Đánh giá HSDT — <?= e($t['code']) ?></h2><div class="sub"><?= e($t['title']) ?> · <?= e(label('eval_method', $t['evaluation_method'])) ?> (KT <?= (int) $t['tech_weight'] ?>% / Giá <?= (int) $t['price_weight'] ?>%, KT tối thiểu <?= (int) $t['pass_tech_score'] ?>) · <?= badge('tender_status', $t['status']) ?></div></div>
    <div class="d-flex gap-2">
        <a class="btn btn-light" href="<?= url('tenders/view', ['id' => $t['id']]) ?>">← HSMT</a>
        <a class="btn btn-outline-success" href="<?= url('evaluation/export', ['id' => $t['id']]) ?>"><i class="bi bi-filetype-csv"></i> Xuất Excel</a>
        <?php if ($canEvaluate): $enough = $progress['complete'] >= $progress['min']; ?>
            <span class="badge align-self-center bg-<?= $enough ? 'success' : 'warning text-dark' ?>" title="Số chuyên gia đã chấm đủ / số tối thiểu theo cấu hình">
                <i class="bi bi-people"></i> Đã chấm đủ: <?= (int) $progress['complete'] ?>/<?= (int) $progress['min'] ?> chuyên gia</span>
            <form method="post" action="<?= url('evaluation/calculate', ['id' => $t['id']]) ?>"><?= csrf_field() ?><button class="btn btn-primary"><i class="bi bi-calculator"></i> Tính điểm & xếp hạng</button></form>
        <?php endif; ?>
    </div>
</div>

<ul class="nav nav-tabs mb-3">
    <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-validity">1. Tính hợp lệ</button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-scoring">2. Chấm điểm kỹ thuật</button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-compare">3. So sánh báo giá</button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-result">4. Kết quả & xếp hạng</button></li>
</ul>
<div class="tab-content">

<div class="tab-pane fade show active" id="tab-validity"><div class="card"><div class="table-responsive">
<table class="table align-middle mb-0">
    <thead><tr><th>Nhà thầu</th><th>HSDT</th><th class="text-end">Giá dự thầu</th><th>Hiệu lực / Giao hàng / BH</th><th>Đề xuất kỹ thuật</th><th>Tài liệu</th><th>Trạng thái</th><?php if ($canEvaluate): ?><th style="width:260px">Kết luận</th><?php endif; ?></tr></thead>
    <?php foreach ($bids as $b): ?>
        <tr>
            <td><a href="<?= url('suppliers/view', ['id' => $b['supplier_id']]) ?>"><?= e($b['supplier_name']) ?></a><div class="small text-muted">Điểm NCC: <?= num($b['rating']) ?></div></td>
            <td><?= e($b['code']) ?><div class="small text-muted"><?= fdatetime($b['submitted_at']) ?></div></td>
            <td class="text-end"><?= money($b['total_price']) ?><?= $b['total_price'] > $t['budget'] ? '<div class="badge bg-danger">Vượt giá gói thầu</div>' : '' ?></td>
            <td class="small"><?= (int) $b['validity_days'] ?> ngày / <?= (int) $b['delivery_days'] ?> ngày / <?= (int) $b['warranty_months'] ?> tháng</td>
            <td class="small" style="max-width:260px"><?= e(mb_strimwidth((string) $b['technical_proposal'], 0, 160, '…')) ?></td>
            <td class="small"><?php foreach ($bidDocs[$b['id']] as $d): ?><a href="<?= url('documents/download', ['id' => $d['id']]) ?>"><i class="bi bi-paperclip"></i> <?= e($d['title']) ?></a><br><?php endforeach; ?></td>
            <td><?= badge('bid_status', $b['status']) ?><div class="small text-muted"><?= e($b['review_note']) ?></div></td>
            <?php if ($canEvaluate): ?><td>
                <?php if (in_array($b['status'], ['submitted', 'valid', 'invalid'], true)): ?>
                <form method="post" action="<?= url('evaluation/validity', ['id' => $t['id']]) ?>" class="d-flex gap-1">
                    <?= csrf_field() ?><input type="hidden" name="bid_id" value="<?= (int) $b['id'] ?>">
                    <input class="form-control form-control-sm" name="note" placeholder="Ghi chú / lý do">
                    <button class="btn btn-sm btn-outline-success" name="valid" value="1" title="Hợp lệ"><i class="bi bi-check"></i></button>
                    <button class="btn btn-sm btn-outline-danger" name="valid" value="0" title="Không hợp lệ"><i class="bi bi-x"></i></button>
                </form>
                <?php endif; ?>
            </td><?php endif; ?>
        </tr>
    <?php endforeach; if (!$bids): ?><tr><td colspan="8" class="text-center text-muted">Không có HSDT nào</td></tr><?php endif; ?>
</table></div></div></div>

<div class="tab-pane fade" id="tab-scoring">
    <div class="card">
        <div class="card-header d-flex justify-content-between"><span>Phiếu chấm điểm của <?= e(auth()['full_name']) ?></span><span class="small text-muted">Mỗi chuyên gia chấm độc lập; kết quả lấy trung bình</span></div>
        <?php if (!$active): ?><div class="card-body text-muted">Không có HSDT hợp lệ để chấm.</div><?php else: ?>
        <form method="post" action="<?= url('evaluation/score', ['id' => $t['id']]) ?>">
            <?= csrf_field() ?>
            <div class="table-responsive"><table class="table table-bordered align-middle mb-0">
                <thead><tr><th>Tiêu chí</th><th class="text-center">Tối đa</th><?php foreach ($active as $b): ?><th class="text-center"><?= e($b['supplier_name']) ?></th><?php endforeach; ?></tr></thead>
                <?php foreach ($criteria as $c): ?>
                    <tr><td><?= e($c['name']) ?><div class="small text-muted"><?= e($c['description']) ?></div></td><td class="text-center"><?= (int) $c['max_score'] ?></td>
                        <?php foreach ($active as $b): $ms = $myScores[$b['id']][$c['id']] ?? null; $av = $avg[$b['id']][$c['id']] ?? null; ?>
                            <td class="text-center">
                                <input class="form-control form-control-sm score-input mx-auto" type="number" step="0.5" min="0" max="<?= (int) $c['max_score'] ?>" name="score[<?= (int) $b['id'] ?>][<?= (int) $c['id'] ?>]" value="<?= $ms ? e((float) $ms['score']) : '' ?>" <?= $canEvaluate ? '' : 'disabled' ?>>
                                <?php if ($av): ?><div class="small text-muted">TB: <?= num($av['a'], 1) ?> (<?= (int) $av['n'] ?> người)</div><?php endif; ?>
                            </td>
                        <?php endforeach; ?></tr>
                <?php endforeach; ?>
                <tr class="table-light"><th>Tổng (trung bình các chuyên gia)</th><th class="text-center"><?= $maxTotal ?></th>
                    <?php foreach ($active as $b): $sum = array_sum(array_map(fn($x) => (float) $x['a'], $avg[$b['id']] ?? [])); ?><th class="text-center"><?= num($sum, 1) ?></th><?php endforeach; ?></tr>
            </table></div>
            <?php if ($canEvaluate): ?><div class="card-footer"><button class="btn btn-primary"><i class="bi bi-save"></i> Lưu phiếu chấm</button></div><?php endif; ?>
        </form>
        <?php endif; ?>
    </div>
    <?php $lastBy = array_column($evaluators, 'last', 'id'); ?>
    <div class="card">
        <div class="card-header d-flex justify-content-between">
            <span>Tổ chuyên gia</span>
            <span class="small text-muted">Mỗi chuyên gia cần chấm <?= (int) $progress['need'] ?> điểm thành phần · tối thiểu <?= (int) $progress['min'] ?> người chấm đủ</span>
        </div>
        <table class="table table-sm align-middle mb-0">
            <thead><tr><th>Chuyên gia</th><th>Đã chấm</th><th>Trạng thái</th><th>Lần lưu cuối</th></tr></thead>
            <?php
            $rowsShown = [];
            foreach ($panel as $m) { $rowsShown[$m['id']] = $m['full_name'] . ($m['dept'] ? ' — ' . $m['dept'] : ''); }
            foreach ($evaluators as $ev) { if (!isset($rowsShown[$ev['id']])) { $rowsShown[$ev['id']] = $ev['full_name']; } }
            foreach ($rowsShown as $uid => $name): $n = $progress['per'][$uid] ?? 0; $done = $progress['need'] > 0 && $n >= $progress['need']; ?>
                <tr><td><?= e($name) ?></td><td><?= (int) $n ?>/<?= (int) $progress['need'] ?></td>
                    <td><?= $done ? '<span class="badge bg-success">Đã chấm đủ</span>' : ($n ? '<span class="badge bg-warning text-dark">Chấm dở</span>' : '<span class="badge bg-secondary">Chưa chấm</span>') ?></td>
                    <td class="small"><?= fdatetime($lastBy[$uid] ?? null) ?></td></tr>
            <?php endforeach; if (!$rowsShown): ?><tr><td colspan="4" class="text-muted">Chưa có thành viên tổ chuyên gia (thêm ở tab Phân quyền & mời thầu của HSMT)</td></tr><?php endif; ?>
        </table>
    </div>
</div>

<div class="tab-pane fade" id="tab-compare"><div class="card">
    <div class="card-header">So sánh đơn giá theo hạng mục <span class="small text-muted">(ô xanh = giá thấp nhất)</span></div>
    <div class="table-responsive"><table class="table table-bordered table-sm align-middle mb-0">
        <thead><tr><th>Hạng mục</th><th class="text-end">SL</th><th class="text-end">Đơn giá dự toán</th><?php foreach ($bids as $b): ?><th class="text-end"><?= e($b['supplier_name']) ?></th><?php endforeach; ?></tr></thead>
        <?php foreach ($items as $it): ?>
            <tr><td><?= e($it['name']) ?></td><td class="text-end"><?= num($it['quantity'], 0) ?></td><td class="text-end"><?= money($it['estimated_price'], false) ?></td>
                <?php foreach ($bids as $b): $bi = $bidItems[$b['id']][$it['id']] ?? null; $isLow = $bi && (float) $bi['unit_price'] === $lowest[$it['id']]; ?>
                    <td class="text-end <?= $isLow ? 'table-success fw-bold' : '' ?>"><?php if ($bi): ?><?= money($bi['unit_price'], false) ?>
                        <?php $diff = $it['estimated_price'] > 0 ? ($bi['unit_price'] - $it['estimated_price']) * 100 / $it['estimated_price'] : 0; ?>
                        <div class="small <?= $diff > 0 ? 'text-danger' : 'text-success' ?>"><?= $diff > 0 ? '+' : '' ?><?= num($diff, 1) ?>%</div><?php else: ?>—<?php endif; ?></td>
                <?php endforeach; ?></tr>
        <?php endforeach; ?>
        <tr class="table-light fw-bold"><td colspan="2">Tổng giá</td><td class="text-end"><?= money($t['budget'], false) ?></td>
            <?php foreach ($bids as $b): ?><td class="text-end"><?= money($b['total_price'], false) ?></td><?php endforeach; ?></tr>
    </table></div>
    <div class="card-body"><canvas id="cmpChart" height="90"></canvas></div>
</div></div>

<div class="tab-pane fade" id="tab-result"><div class="card">
    <div class="card-header">Bảng tổng hợp kết quả đánh giá</div>
    <div class="table-responsive"><table class="table align-middle mb-0">
        <thead><tr><th>Hạng</th><th>Nhà thầu</th><th class="text-end">Giá dự thầu</th><th>Điểm KT (/100)</th><th>Đạt KT</th><th>Điểm giá</th><th>Điểm tổng hợp</th><th>Trạng thái</th><?php if (can('bids.award') && $t['status'] === 'evaluating'): ?><th></th><?php endif; ?></tr></thead>
        <?php foreach ($bids as $b): ?>
            <tr class="<?= (int) $b['rank_no'] === 1 ? 'rank-1' : '' ?>">
                <td class="fs-5"><?= $b['rank_no'] ? ((int) $b['rank_no'] === 1 ? '🥇' : '#' . (int) $b['rank_no']) : '—' ?></td>
                <td class="fw-semibold"><?= e($b['supplier_name']) ?></td><td class="text-end"><?= money($b['total_price']) ?></td>
                <td><?= num($b['tech_score']) ?></td>
                <td><?= $b['passed'] === null ? '—' : ($b['passed'] ? '<span class="badge bg-success">Đạt</span>' : '<span class="badge bg-danger">Không đạt</span>') ?></td>
                <td><?= num($b['price_score']) ?></td><td class="fw-bold fs-6"><?= num($b['final_score']) ?></td>
                <td><?= badge('bid_status', $b['status']) ?></td>
                <?php if (can('bids.award') && $t['status'] === 'evaluating'): ?><td>
                    <?php if ($b['passed'] && $b['rank_no']): ?>
                    <form method="post" action="<?= url('evaluation/award', ['id' => $t['id']]) ?>" data-confirm="Phê duyệt <?= e($b['supplier_name']) ?> trúng thầu?" class="d-flex gap-1">
                        <?= csrf_field() ?><input type="hidden" name="bid_id" value="<?= (int) $b['id'] ?>">
                        <?php if ((int) $b['rank_no'] !== 1): ?><input class="form-control form-control-sm" name="justification" placeholder="Giải trình (bắt buộc)" required><?php endif; ?>
                        <button class="btn btn-sm btn-success text-nowrap"><i class="bi bi-trophy"></i> Trao thầu</button>
                    </form>
                    <?php endif; ?>
                </td><?php endif; ?>
            </tr>
        <?php endforeach; ?>
    </table></div>
    <div class="card-footer small text-muted">
        Công thức: Điểm KT = Σ(điểm TB các chuyên gia) / <?= $maxTotal ?> × 100 · Điểm giá = giá thấp nhất (trong các HSDT đạt KT) / giá HSDT × 100 ·
        <?= $t['evaluation_method'] === 'qcs' ? 'Điểm tổng = ' . (int) $t['tech_weight'] . '% × KT + ' . (int) $t['price_weight'] . '% × Giá' : ($t['evaluation_method'] === 'lowest_price' ? 'Xếp hạng theo giá thấp nhất' : 'Xếp hạng theo điểm KT') ?>
    </div>
</div></div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    new Chart(document.getElementById('cmpChart'), {
        type: 'bar',
        data: { labels: ['Giá gói thầu'].concat(<?= json_encode(array_column($bids, 'supplier_name'), JSON_UNESCAPED_UNICODE) ?>),
            datasets: [{ label: 'Giá (đ)', data: [<?= (float) $t['budget'] ?>].concat(<?= json_encode(array_map('floatval', array_column($bids, 'total_price'))) ?>),
                backgroundColor: ['#90a4ae'].concat(<?= json_encode(array_map(fn($b) => $b['status'] === 'awarded' ? '#43a047' : '#1e88e5', $bids)) ?>) }] },
        options: { indexAxis: 'y', plugins: { legend: { display: false } } }
    });
});
</script>
