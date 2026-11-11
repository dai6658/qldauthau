<div class="page-head"><div><h2>Hồ sơ dự thầu / báo giá của tôi</h2><div class="sub">Theo dõi trạng thái và kết quả trực tuyến</div></div></div>
<div class="card"><div class="table-responsive"><table class="table align-middle mb-0">
    <thead><tr><th>Mã HSDT</th><th>Gói thầu</th><th>Nộp lúc</th><th class="text-end">Giá dự thầu</th><th>Tiến trình gói thầu</th><th>Kết quả</th><th></th></tr></thead>
    <?php foreach ($bids as $b): $public = in_array($b['tender_status'], ['awarded'], true); ?>
        <tr>
            <td><?= e($b['code']) ?></td>
            <td><?= e($b['tender_code']) ?><div class="small"><?= e($b['tender_title']) ?></div></td>
            <td class="small"><?= fdatetime($b['submitted_at']) ?></td>
            <td class="text-end"><?= money($b['total_price']) ?></td>
            <td><?= badge('tender_status', $b['tender_status']) ?></td>
            <td><?= badge('bid_status', $b['status']) ?><?php if ($public && $b['rank_no']): ?><div class="small">Hạng #<?= (int) $b['rank_no'] ?> · KT <?= num($b['tech_score']) ?> · Tổng <?= num($b['final_score']) ?></div><?php endif; ?>
                <?php if ($b['review_note']): ?><div class="small text-muted"><?= e($b['review_note']) ?></div><?php endif; ?></td>
            <td><a class="btn btn-sm btn-outline-primary" href="<?= url('portal/tender', ['id' => $b['tid']]) ?>">Chi tiết</a></td>
        </tr>
    <?php endforeach; if (!$bids): ?><tr><td colspan="7" class="text-center text-muted py-4">Bạn chưa tham gia gói thầu nào</td></tr><?php endif; ?>
</table></div></div>
