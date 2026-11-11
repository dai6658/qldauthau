<div class="page-head"><div><h2>Hóa đơn điện tử</h2><div class="sub">Gửi hóa đơn điện tử theo hợp đồng và theo dõi tình trạng thanh toán</div></div></div>
<div class="row">
    <div class="col-lg-8"><div class="card"><div class="table-responsive"><table class="table align-middle mb-0">
        <thead><tr><th>Số HĐơn</th><th>Hợp đồng</th><th>Ngày</th><th class="text-end">Tiền hàng</th><th class="text-end">VAT</th><th>Trạng thái</th><th>Phản hồi</th></tr></thead>
        <?php foreach ($invoices as $i): ?>
            <tr><td class="fw-semibold"><?= $i['document_id'] ? '<a href="' . url('documents/download', ['id' => $i['document_id']]) . '">' . e($i['invoice_no']) . '</a>' : e($i['invoice_no']) ?></td>
                <td><?= e($i['contract_code']) ?></td><td><?= fdate($i['invoice_date']) ?></td>
                <td class="text-end"><?= money($i['amount'], false) ?></td><td class="text-end"><?= money($i['vat_amount'], false) ?></td>
                <td><?= badge('invoice_status', $i['status']) ?></td><td class="small"><?= e($i['review_note']) ?><?= $i['reviewed_at'] ? '<br>' . fdatetime($i['reviewed_at']) : '' ?></td></tr>
        <?php endforeach; if (!$invoices): ?><tr><td colspan="7" class="text-center text-muted py-4">Chưa gửi hóa đơn nào</td></tr><?php endif; ?>
    </table></div></div></div>
    <div class="col-lg-4">
        <form method="post" enctype="multipart/form-data" action="<?= url('portal/submitInvoice') ?>" class="card card-body">
            <?= csrf_field() ?>
            <h6><i class="bi bi-send"></i> Gửi hóa đơn mới</h6>
            <?php if (!$contracts): ?><p class="text-muted small">Chưa có hợp đồng đang hiệu lực.</p><?php else: ?>
            <label class="form-label">Hợp đồng *</label><select class="form-select mb-2" name="contract_id" required><?= select_options($contracts) ?></select>
            <label class="form-label">Số hóa đơn *</label><input class="form-control mb-2" name="invoice_no" required placeholder="VD: 1C26TAA-0000123">
            <label class="form-label">Ngày hóa đơn *</label><input class="form-control mb-2" type="date" name="invoice_date" value="<?= date('Y-m-d') ?>" required>
            <div class="row g-2 mb-2">
                <div class="col"><label class="form-label">Tiền hàng *</label><input class="form-control money" name="amount" required></div>
                <div class="col"><label class="form-label">Thuế GTGT</label><input class="form-control money" name="vat_amount"></div>
            </div>
            <label class="form-label">Tệp hóa đơn (PDF/XML) *</label><input class="form-control mb-2" type="file" name="file" accept=".pdf,.xml" required>
            <label class="form-label">Ghi chú</label><input class="form-control mb-3" name="note">
            <button class="btn btn-primary">Gửi hóa đơn</button>
            <?php endif; ?>
        </form>
    </div>
</div>
