<div class="page-head"><div><h2>Gói thầu</h2><div class="sub">Gói thầu rộng rãi và các gói bạn được mời tham dự</div></div></div>
<div class="card"><div class="table-responsive"><table class="table table-hover align-middle mb-0">
    <thead><tr><th>Mã</th><th>Gói thầu</th><th>Hình thức</th><th class="text-end">Giá gói thầu</th><th>Đóng thầu</th><th>Trạng thái gói</th><th>HSDT của tôi</th><th></th></tr></thead>
    <?php foreach ($tenders as $t): $d = days_left($t['close_at']); $open = $t['status'] === 'published' && strtotime($t['close_at']) > time(); ?>
        <tr>
            <td class="fw-semibold"><?= e($t['code']) ?></td>
            <td><?= e($t['title']) ?><div class="small text-muted"><?= e($t['project_name']) ?> · <?= e(label('category', $t['category'])) ?></div></td>
            <td class="small"><?= e(label('method', $t['method'])) ?></td>
            <td class="text-end"><?= money($t['budget']) ?></td>
            <td class="small"><?= fdatetime($t['close_at']) ?><?= $open ? '<br><span class="badge bg-' . ($d <= 3 ? 'danger' : 'info text-dark') . '">còn ' . $d . ' ngày</span>' : '' ?></td>
            <td><?= $open ? '<span class="badge bg-primary">Đang nhận HSDT</span>' : badge('tender_status', $t['status']) ?></td>
            <td><?= $t['my_bid_status'] ? badge('bid_status', $t['my_bid_status']) : '—' ?></td>
            <td><a class="btn btn-sm <?= $open ? 'btn-primary' : 'btn-outline-secondary' ?>" href="<?= url('portal/tender', ['id' => $t['id']]) ?>"><?= $open ? ($t['my_bid_status'] ? 'Xem / sửa HSDT' : 'Tham dự') : 'Xem' ?></a></td>
        </tr>
    <?php endforeach; if (!$tenders): ?><tr><td colspan="8" class="text-center text-muted py-4">Chưa có gói thầu</td></tr><?php endif; ?>
</table></div></div>
