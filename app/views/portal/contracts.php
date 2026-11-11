<div class="page-head"><div><h2>Hợp đồng & thanh toán</h2><div class="sub">Theo dõi tiến độ nghiệm thu và các đợt thanh toán</div></div></div>
<?php foreach ($contracts as $c): $pct = $c['value'] > 0 ? round($c['paid'] * 100 / $c['value']) : 0; ?>
<div class="card">
    <div class="card-header d-flex justify-content-between"><span><strong><?= e($c['code']) ?></strong> — <?= e($c['title']) ?></span><?= badge('contract_status', $c['status']) ?></div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-4"><dl class="dl-grid mb-0">
                <dt>Dự án</dt><dd><?= e($c['project_name']) ?></dd>
                <dt>Giá trị</dt><dd class="fw-bold"><?= money($c['value']) ?></dd>
                <dt>Thời hạn</dt><dd><?= fdate($c['start_date']) ?> → <?= fdate($c['end_date']) ?></dd>
                <?php if ($c['settled_amount'] !== null): ?><dt>Quyết toán</dt><dd><?= money($c['settled_amount']) ?></dd><?php endif; ?>
                <dt>Đã nhận</dt><dd><?= money($c['paid']) ?> (<?= $pct ?>%)</dd>
            </dl>
            <div class="progress mt-2" style="height:8px"><div class="progress-bar bg-success" style="width:<?= $pct ?>%"></div></div></div>
            <div class="col-md-5"><h6>Các đợt thanh toán</h6><table class="table table-sm">
                <?php foreach ($payments[$c['id']] as $p): ?><tr><td><?= e($p['milestone']) ?></td><td class="text-end"><?= money($p['amount'], false) ?></td><td class="small"><?= fdate($p['due_date']) ?></td><td><?= badge('payment_status', $p['status']) ?><?= $p['status'] === 'paid' ? '<div class="small">' . fdate($p['paid_at']) . '</div>' : '' ?></td></tr><?php endforeach; ?>
            </table></div>
            <div class="col-md-3"><h6>Nghiệm thu</h6>
                <?php foreach ($acceptances[$c['id']] as $a): ?><div class="small mb-1"><?= fdate($a['accept_date']) ?> · <?= e($a['title']) ?> <?= badge('acceptance_result', $a['result']) ?></div><?php endforeach; ?>
                <?php if ($docs[$c['id']]): ?><h6 class="mt-2">Tài liệu</h6><?php foreach ($docs[$c['id']] as $d): if (!Access::document($d, 'download')) continue; ?><div class="small"><a href="<?= url('documents/download', ['id' => $d['id']]) ?>"><i class="bi bi-download"></i> <?= e($d['title']) ?></a></div><?php endforeach; ?><?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php endforeach; if (!$contracts): ?><div class="card card-body text-muted text-center">Chưa có hợp đồng</div><?php endif; ?>
