<?php
$submitted = $bid && $bid['status'] === 'submitted';
$editable = $canBid && (!$bid || in_array($bid['status'], ['draft', 'submitted', 'withdrawn'], true));
?>
<div class="page-head">
    <div><h2><?= e($t['code']) ?> — <?= e($t['title']) ?></h2>
        <div class="sub"><?= e($t['project_name']) ?> · <?= e(label('method', $t['method'])) ?> · Đóng thầu: <strong><?= fdatetime($t['close_at']) ?></strong>
        <?php if ($t['status'] === 'published' && strtotime($t['close_at']) > time()): ?><span class="badge bg-danger" id="countdown" data-close="<?= e(date('c', strtotime($t['close_at']))) ?>"></span><?php endif; ?></div></div>
    <a class="btn btn-light" href="<?= url('portal/tenders') ?>">← Danh sách</a>
</div>

<?php if ($bid): ?>
<div class="alert alert-<?= $bid['status'] === 'awarded' ? 'success' : ($submitted ? 'primary' : 'secondary') ?> d-flex justify-content-between align-items-center">
    <div>HSDT của bạn: <strong><?= e($bid['code']) ?></strong> — <?= badge('bid_status', $bid['status']) ?>
        <?= $bid['submitted_at'] ? ' · nộp lúc ' . fdatetime($bid['submitted_at']) : '' ?> · Tổng giá: <strong><?= money($bid['total_price']) ?></strong>
        <?php if ($bid['rank_no'] && in_array($t['status'], ['awarded'], true)): ?> · Xếp hạng #<?= (int) $bid['rank_no'] ?> · Điểm <?= num($bid['final_score']) ?><?php endif; ?>
        <?php if ($bid['review_note']): ?><div class="small"><?= e($bid['review_note']) ?></div><?php endif; ?></div>
    <?php if ($canBid && in_array($bid['status'], ['draft', 'submitted'], true)): ?>
        <form method="post" action="<?= url('portal/withdrawBid', ['id' => $t['id']]) ?>" data-confirm="Rút hồ sơ dự thầu?"><?= csrf_field() ?><button class="btn btn-sm btn-outline-danger">Rút HSDT</button></form>
    <?php endif; ?>
</div>
<?php endif; ?>
<?php if ($result): ?><div class="alert alert-info">Kết quả lựa chọn nhà thầu: <strong><?= e($result['name']) ?></strong> — giá trúng thầu <?= money($result['total_price']) ?></div><?php endif; ?>
<?php if (!$canBid && $t['status'] === 'published' && $s['status'] !== 'approved'): ?><div class="alert alert-warning">Hồ sơ doanh nghiệp chưa được duyệt nên chưa thể nộp HSDT.</div><?php endif; ?>

<div class="row">
    <div class="col-lg-5">
        <div class="card"><div class="card-header">Thông tin gói thầu</div><div class="card-body"><dl class="dl-grid">
            <dt>Bên mời thầu</dt><dd><?= e($t['investor'] ?: setting('org_display_name')) ?></dd>
            <dt>Lĩnh vực</dt><dd><?= e(label('category', $t['category'])) ?></dd>
            <dt>Giá gói thầu</dt><dd><?= money($t['budget']) ?></dd>
            <dt>Bảo đảm dự thầu</dt><dd><?= money($t['bid_security']) ?></dd>
            <dt>Phương pháp</dt><dd><?= e(label('eval_method', $t['evaluation_method'])) ?> (KT <?= (int) $t['tech_weight'] ?>% / Giá <?= (int) $t['price_weight'] ?>%)</dd>
            <dt>Phát hành</dt><dd><?= fdatetime($t['publish_at']) ?></dd>
            <dt>Mô tả</dt><dd class="pre-wrap"><?= e($t['description']) ?></dd>
        </dl></div></div>
        <div class="card"><div class="card-header">Tải hồ sơ mời thầu</div><div class="card-body">
            <?php foreach ($docs as $d): ?><div><a href="<?= url('documents/download', ['id' => $d['id']]) ?>"><i class="bi bi-download"></i> <?= e($d['title']) ?></a> <small class="text-muted">(<?= file_size($d['size_bytes']) ?>, v<?= (int) $d['current_version'] ?>)</small></div><?php endforeach; ?>
            <?php if (!$docs): ?><span class="text-muted">Không có tệp đính kèm</span><?php endif; ?>
        </div></div>
        <div class="card"><div class="card-header">Tiêu chí đánh giá kỹ thuật</div><table class="table table-sm mb-0">
            <?php foreach ($criteria as $c): ?><tr><td><?= e($c['name']) ?><div class="small text-muted"><?= e($c['description']) ?></div></td><td class="text-end"><?= (int) $c['max_score'] ?>đ</td></tr><?php endforeach; ?>
            <tr><td class="small">Điểm kỹ thuật tối thiểu</td><td class="text-end"><?= (int) $t['pass_tech_score'] ?>/100</td></tr>
        </table></div>
        <details class="card card-body"><summary>Nội dung chi tiết HSMT</summary><div class="doc-content mt-2 small"><?= e($t['content']) ?></div></details>
    </div>

    <div class="col-lg-7">
        <form method="post" enctype="multipart/form-data" action="<?= url('portal/saveBid', ['id' => $t['id']]) ?>" class="card">
            <?= csrf_field() ?>
            <div class="card-header d-flex justify-content-between"><span><i class="bi bi-file-earmark-text"></i> Hồ sơ dự thầu / Bảng chào giá</span><?php if ($editable): ?><span class="small text-muted">Có thể sửa đổi đến thời điểm đóng thầu</span><?php endif; ?></div>
            <div class="table-responsive"><table class="table table-sm align-middle mb-0" data-bid-table>
                <thead><tr><th>Hạng mục</th><th class="text-end">SL</th><th>ĐVT</th><th style="width:150px">Đơn giá (đ)</th><th class="text-end">Thành tiền</th></tr></thead>
                <?php foreach ($items as $it): ?>
                    <tr data-qty="<?= (float) $it['quantity'] ?>">
                        <td><?= e($it['name']) ?><div class="small text-muted"><?= e($it['specs']) ?></div></td>
                        <td class="text-end"><?= num($it['quantity'], 0) ?></td><td><?= e($it['unit']) ?></td>
                        <td><input class="form-control form-control-sm money text-end" name="unit_price[<?= (int) $it['id'] ?>]" value="<?= isset($bidItems[$it['id']]) ? number_format((float) $bidItems[$it['id']], 0, ',', '.') : '' ?>" <?= $editable ? '' : 'disabled' ?>></td>
                        <td class="text-end amount">0</td>
                    </tr>
                <?php endforeach; ?>
                <tr class="table-light"><th colspan="4" class="text-end">Tổng giá dự thầu</th><th class="text-end bid-total">0 đ</th></tr>
            </table></div>
            <div class="card-body">
                <div class="row g-2">
                    <div class="col-md-4"><label class="form-label">Hiệu lực HSDT (ngày) *</label><input class="form-control" type="number" name="validity_days" value="<?= e($bid['validity_days'] ?? 90) ?>" <?= $editable ? '' : 'disabled' ?>></div>
                    <div class="col-md-4"><label class="form-label">Thời gian giao hàng (ngày)</label><input class="form-control" type="number" name="delivery_days" value="<?= e($bid['delivery_days'] ?? '') ?>" <?= $editable ? '' : 'disabled' ?>></div>
                    <div class="col-md-4"><label class="form-label">Bảo hành (tháng)</label><input class="form-control" type="number" name="warranty_months" value="<?= e($bid['warranty_months'] ?? '') ?>" <?= $editable ? '' : 'disabled' ?>></div>
                    <div class="col-12"><label class="form-label">Đề xuất kỹ thuật / giải pháp *</label><textarea class="form-control" name="technical_proposal" rows="5" <?= $editable ? '' : 'disabled' ?>><?= e($bid['technical_proposal'] ?? '') ?></textarea></div>
                </div>
                <h6 class="mt-3">Tài liệu đính kèm</h6>
                <?php foreach ($bidDocs as $d): ?>
                    <div class="d-flex justify-content-between border-bottom py-1"><a href="<?= url('documents/download', ['id' => $d['id']]) ?>"><i class="bi bi-paperclip"></i> <?= e($d['title']) ?></a>
                        <span class="small text-muted"><?= file_size($d['size_bytes']) ?> · <?= e(label('doc_category', $d['category'])) ?>
                        <?php if ($editable): ?><button class="btn btn-link btn-sm text-danger p-0 ms-2" formaction="<?= url('portal/deleteBidDoc', ['id' => $t['id'], 'doc_id' => $d['id']]) ?>" formnovalidate>Gỡ</button><?php endif; ?></span></div>
                <?php endforeach; ?>
                <?php if ($editable): ?>
                    <div class="row g-2 mt-1">
                        <div class="col-md-4"><select class="form-select form-select-sm" name="doc_category"><?= select_options(['bid_doc' => 'Hồ sơ dự thầu', 'proposal' => 'Giải pháp đề xuất', 'drawing' => 'Bản vẽ', 'capability' => 'Năng lực', 'legal' => 'Pháp lý / bảo lãnh'], 'bid_doc') ?></select></div>
                        <div class="col-md-8"><input class="form-control form-control-sm" type="file" name="files[]" multiple></div>
                    </div>
                    <p class="small text-muted mt-2 mb-0"><i class="bi bi-shield-lock"></i> Tệp được mã hóa AES-256 khi lưu trữ và <strong>niêm phong</strong> — bên mời thầu chỉ xem được sau thời điểm đóng thầu.</p>
                <?php endif; ?>
            </div>
            <?php if ($editable): ?>
            <div class="card-footer d-flex gap-2 justify-content-end">
                <button class="btn btn-outline-secondary" name="action" value="draft"><i class="bi bi-save"></i> Lưu nháp</button>
                <button class="btn btn-primary" name="action" value="submit" data-confirm="Xác nhận nộp hồ sơ dự thầu?"><i class="bi bi-send"></i> <?= $submitted ? 'Nộp lại (sửa đổi)' : 'Nộp HSDT' ?></button>
            </div>
            <?php endif; ?>
        </form>
    </div>
</div>
<script>
(function () {
    const el = document.getElementById('countdown');
    if (!el) return;
    const end = new Date(el.dataset.close).getTime();
    const tick = () => {
        let s = Math.max(0, Math.floor((end - Date.now()) / 1000));
        const d = Math.floor(s / 86400); s %= 86400;
        const h = Math.floor(s / 3600); s %= 3600;
        el.textContent = 'Còn ' + d + ' ngày ' + h + ' giờ ' + Math.floor(s / 60) + ' phút';
    };
    tick(); setInterval(tick, 30000);
})();
</script>
