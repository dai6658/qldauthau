<div class="page-head"><div><h2>Đánh giá hồ sơ dự thầu</h2><div class="sub">Tổ chuyên gia chấm điểm độc lập — hệ thống tổng hợp và xếp hạng khách quan (QCS)</div></div></div>
<div class="card"><table class="table table-hover align-middle mb-0">
    <thead><tr><th>Gói thầu</th><th>Dự án</th><th>Phương pháp</th><th>Đóng thầu</th><th>HSDT</th><th>Chuyên gia đã chấm</th><th>Trạng thái</th><th></th></tr></thead>
    <?php foreach ($tenders as $t): ?>
        <tr><td><strong><?= e($t['code']) ?></strong><br><?= e($t['title']) ?></td><td><?= e($t['project_code']) ?></td><td class="small"><?= e(label('eval_method', $t['evaluation_method'])) ?></td>
            <td class="small"><?= fdatetime($t['close_at']) ?></td><td><?= Access::bidsSealed($t) ? '<i class="bi bi-lock"></i> ' : '' ?><?= (int) $t['bid_count'] ?></td><td><?= (int) $t['evaluators'] ?></td>
            <td><?= badge('tender_status', $t['status']) ?></td>
            <td><?php if (Access::bidsSealed($t)): ?><span class="text-muted small">Chờ đóng thầu</span>
                <?php elseif ($t['status'] === 'closed'): ?><a class="btn btn-sm btn-success" href="<?= url('tenders/view', ['id' => $t['id']]) ?>">Mở thầu</a>
                <?php else: ?><a class="btn btn-sm btn-primary" href="<?= url('evaluation/tender', ['id' => $t['id']]) ?>"><?= $t['status'] === 'awarded' ? 'Xem kết quả' : 'Đánh giá' ?></a><?php endif; ?></td></tr>
    <?php endforeach; if (!$tenders): ?><tr><td colspan="8" class="text-center text-muted py-4">Không có gói thầu nào</td></tr><?php endif; ?>
</table></div>
