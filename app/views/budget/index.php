<?php $tot = ['approved' => array_sum(array_column($projects, 'approved')), 'committed' => array_sum(array_column($projects, 'committed')), 'spent' => array_sum(array_column($projects, 'spent'))]; ?>
<div class="page-head"><div><h2>Tổng mức đầu tư & kiểm soát chi phí</h2><div class="sub">So sánh TMĐT được duyệt – giá trị cam kết (hợp đồng) – chi phí thực tế</div></div></div>
<div class="row g-3 mb-3">
    <div class="col-md-4"><div class="kpi k1"><div class="l">Tổng TMĐT được duyệt</div><div class="v"><?= money($tot['approved'], false) ?></div><i class="bi bi-bank"></i></div></div>
    <div class="col-md-4"><div class="kpi k3"><div class="l">Đã cam kết qua hợp đồng</div><div class="v"><?= money($tot['committed'], false) ?></div><div class="l"><?= $tot['approved'] ? round($tot['committed'] * 100 / $tot['approved'], 1) : 0 ?>% TMĐT</div><i class="bi bi-file-earmark-check"></i></div></div>
    <div class="col-md-4"><div class="kpi k2"><div class="l">Đã chi / giải ngân</div><div class="v"><?= money($tot['spent'], false) ?></div><div class="l"><?= $tot['approved'] ? round($tot['spent'] * 100 / $tot['approved'], 1) : 0 ?>% TMĐT</div><i class="bi bi-cash-coin"></i></div></div>
</div>
<div class="card"><div class="table-responsive"><table class="table table-hover align-middle mb-0">
    <thead><tr><th>Dự án</th><th class="text-end">Dự toán</th><th class="text-end">TMĐT được duyệt</th><th class="text-end">Cam kết (HĐ)</th><th class="text-end">Đã chi</th><th class="text-end">Còn lại</th><th style="width:200px">Tỷ lệ giải ngân</th><th></th></tr></thead>
    <?php foreach ($projects as $p): $pct = $p['approved'] ? round($p['spent'] * 100 / $p['approved'], 1) : 0; $cpct = $p['approved'] ? round($p['committed'] * 100 / $p['approved'], 1) : 0; ?>
        <tr>
            <td><strong><?= e($p['code']) ?></strong><br><span class="small"><?= e($p['name']) ?></span></td>
            <td class="text-end"><?= money($p['estimated']) ?></td><td class="text-end fw-semibold"><?= money($p['approved']) ?></td>
            <td class="text-end"><?= money($p['committed']) ?></td><td class="text-end"><?= money($p['spent']) ?></td>
            <td class="text-end <?= $p['approved'] - $p['spent'] < 0 ? 'text-danger fw-bold' : '' ?>"><?= money($p['approved'] - $p['spent']) ?></td>
            <td><div class="progress" style="height:14px" title="Cam kết <?= $cpct ?>%">
                <div class="progress-bar bg-success" style="width:<?= min(100, $pct) ?>%"><?= $pct ?>%</div>
                <div class="progress-bar bg-warning" style="width:<?= max(0, min(100 - $pct, $cpct - $pct)) ?>%"></div></div>
                <small class="text-muted">xanh: đã chi · vàng: đã cam kết</small></td>
            <td><a class="btn btn-sm btn-primary" href="<?= url('budget/projectView', ['id' => $p['id']]) ?>">Chi tiết</a></td>
        </tr>
    <?php endforeach; ?>
</table></div></div>
