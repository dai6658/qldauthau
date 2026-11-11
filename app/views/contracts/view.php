<?php
$isDraft = $c['status'] === 'draft';
$isActive = $c['status'] === 'active';
$canC = can('contracts.manage');
$canP = can('payments.manage');
$verifiedInvoices = array_filter($invoices, fn($i) => $i['status'] === 'verified');
$usedInvoiceIds = array_filter(array_map(fn($p) => in_array($p['status'], ['pending_approval', 'approved', 'paid'], true) ? $p['invoice_id'] : null, $payments));
$paidPct = $c['value'] > 0 ? round($stats['paid'] * 100 / $c['value'], 1) : 0;
?>
<div class="page-head">
    <div><h2><?= e($c['code']) ?> — <?= e($c['title']) ?></h2>
        <div class="sub"><?= badge('contract_status', $c['status']) ?> · <?= e($c['supplier_name']) ?> · <a href="<?= url('projects/view', ['id' => $c['project_id']]) ?>"><?= e($c['project_code']) ?></a>
            <?php if ($c['tender_id']): ?> · <a href="<?= url('tenders/view', ['id' => $c['tender_id']]) ?>"><?= e($c['tender_code']) ?></a><?php endif; ?></div></div>
    <div class="d-flex gap-2">
        <?php if ($canC && $isDraft): ?><form method="post" action="<?= url('contracts/submitApproval', ['id' => $c['id']]) ?>" data-confirm="Trình duyệt hợp đồng?"><?= csrf_field() ?><button class="btn btn-warning"><i class="bi bi-send"></i> Trình duyệt</button></form><?php endif; ?>
        <?php if ($canC && in_array($c['status'], ['draft', 'pending_approval', 'active'], true)): ?><button class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#termModal">Chấm dứt</button><?php endif; ?>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-3"><div class="kpi k1"><div class="l">Giá trị hợp đồng</div><div class="v"><?= money($c['value'], false) ?></div><i class="bi bi-file-earmark-text"></i></div></div>
    <div class="col-md-3"><div class="kpi k3"><div class="l">Đã đặt hàng / đã xuất hóa đơn</div><div class="v" style="font-size:1.1rem"><?= money($stats['ordered'], false) ?> / <?= money($stats['invoiced'], false) ?></div><i class="bi bi-receipt"></i></div></div>
    <div class="col-md-3"><div class="kpi k2"><div class="l">Đã giải ngân</div><div class="v"><?= money($stats['paid'], false) ?></div><div class="l"><?= $paidPct ?>% giá trị HĐ</div><i class="bi bi-cash-coin"></i></div></div>
    <div class="col-md-3"><div class="kpi k4"><div class="l"><?= $c['settled_amount'] !== null ? 'Còn phải trả theo quyết toán' : 'Còn lại theo hợp đồng' ?></div><div class="v"><?= money(($c['settled_amount'] ?? $c['value']) - $stats['paid'], false) ?></div><i class="bi bi-hourglass"></i></div></div>
</div>

<ul class="nav nav-tabs mb-3">
    <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-info">Hợp đồng</button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-orders">Đơn hàng (<?= count($orders) ?>)</button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-acceptance">Nghiệm thu (<?= count($acceptances) ?>)</button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-invoices">Hóa đơn (<?= count($invoices) ?>)</button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-payments">Thanh toán & giải ngân (<?= count($payments) ?>)</button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-settlement">Quyết toán</button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-docs">Tài liệu (<?= count($documents) ?>)</button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-approval">Phê duyệt & lịch sử</button></li>
</ul>
<div class="tab-content">

<div class="tab-pane fade show active" id="tab-info"><div class="row">
    <div class="col-lg-6"><div class="card card-body"><dl class="dl-grid">
        <dt>Bên A</dt><dd><?= e(setting('org_display_name')) ?></dd>
        <dt>Bên B</dt><dd><a href="<?= url('suppliers/view', ['id' => $c['supplier_id']]) ?>"><?= e($c['supplier_name']) ?></a><br><small>MST <?= e($c['tax_code']) ?> · Đại diện: <?= e($c['representative']) ?></small></dd>
        <dt>Tài khoản bên B</dt><dd><?= e($c['bank_account']) ?> — <?= e($c['bank_name']) ?></dd>
        <dt>Giá trị</dt><dd class="fw-bold"><?= money($c['value']) ?></dd>
        <dt>Ngày ký</dt><dd><?= fdate($c['sign_date']) ?></dd>
        <dt>Thời gian thực hiện</dt><dd><?= fdate($c['start_date']) ?> → <?= fdate($c['end_date']) ?></dd>
        <dt>Điều khoản thanh toán</dt><dd class="pre-wrap"><?= e($c['payment_terms']) ?></dd>
        <dt>Người lập</dt><dd><?= e($c['creator']) ?> · <?= fdatetime($c['created_at']) ?></dd>
    </dl></div></div>
    <?php if ($canC && $isDraft): ?>
    <div class="col-lg-6"><form method="post" action="<?= url('contracts/edit', ['id' => $c['id']]) ?>" class="card card-body">
        <?= csrf_field() ?>
        <h6>Hoàn thiện dự thảo hợp đồng</h6>
        <div class="row g-2">
            <div class="col-12"><label class="form-label">Tên hợp đồng</label><input class="form-control" name="title" value="<?= e($c['title']) ?>"></div>
            <div class="col-md-6"><label class="form-label">Giá trị (đ)</label><input class="form-control money" name="value" value="<?= money_input($c['value']) ?>"></div>
            <div class="col-md-6"><label class="form-label">Ngày ký *</label><input class="form-control" type="date" name="sign_date" value="<?= e($c['sign_date']) ?>"></div>
            <div class="col-md-6"><label class="form-label">Ngày bắt đầu</label><input class="form-control" type="date" name="start_date" value="<?= e($c['start_date']) ?>"></div>
            <div class="col-md-6"><label class="form-label">Ngày kết thúc *</label><input class="form-control" type="date" name="end_date" value="<?= e($c['end_date']) ?>"></div>
            <div class="col-12"><label class="form-label">Điều khoản thanh toán</label><textarea class="form-control" name="payment_terms" rows="4"><?= e($c['payment_terms']) ?></textarea></div>
        </div>
        <button class="btn btn-primary mt-2">Lưu</button>
    </form></div>
    <?php endif; ?>
</div></div>

<div class="tab-pane fade" id="tab-orders"><div class="card">
    <table class="table table-sm align-middle mb-0">
        <thead><tr><th>Số đơn</th><th>Ngày đặt</th><th>Giao hàng</th><th>Nội dung</th><th class="text-end">Giá trị</th><th>Phản hồi NCC</th><th>Trạng thái</th><?php if ($canC): ?><th></th><?php endif; ?></tr></thead>
        <?php foreach ($orders as $o): ?>
            <tr><td><?= e($o['code']) ?></td><td><?= fdate($o['order_date']) ?></td><td><?= fdate($o['delivery_date']) ?></td><td class="small"><?= e($o['description']) ?></td>
                <td class="text-end"><?= money($o['amount']) ?></td><td class="small"><?= e($o['supplier_note']) ?><?= $o['confirmed_at'] ? '<br>' . fdatetime($o['confirmed_at']) : '' ?></td>
                <td><?= badge('po_status', $o['status']) ?></td>
                <?php if ($canC): ?><td class="text-nowrap"><?php if ($o['status'] === 'confirmed'): ?>
                    <form class="d-inline" method="post" action="<?= url('contracts/orderStatus') ?>"><?= csrf_field() ?><input type="hidden" name="order_id" value="<?= (int) $o['id'] ?>"><button class="btn btn-sm btn-outline-success" name="status" value="delivered">Đã giao</button></form><?php endif; ?>
                    <?php if (in_array($o['status'], ['sent', 'confirmed'], true)): ?><form class="d-inline" method="post" action="<?= url('contracts/orderStatus') ?>" data-confirm="Hủy đơn hàng?"><?= csrf_field() ?><input type="hidden" name="order_id" value="<?= (int) $o['id'] ?>"><button class="btn btn-sm btn-link text-danger" name="status" value="cancelled">Hủy</button></form><?php endif; ?>
                </td><?php endif; ?></tr>
        <?php endforeach; if (!$orders): ?><tr><td colspan="8" class="text-muted">Chưa có đơn hàng</td></tr><?php endif; ?>
    </table>
    <?php if ($canC && $isActive): ?>
    <form method="post" action="<?= url('contracts/addOrder') ?>" class="card-body border-top row g-2">
        <?= csrf_field() ?><input type="hidden" name="contract_id" value="<?= (int) $c['id'] ?>">
        <div class="col-md-2"><label class="form-label small">Ngày đặt</label><input class="form-control form-control-sm" type="date" name="order_date" value="<?= date('Y-m-d') ?>"></div>
        <div class="col-md-2"><label class="form-label small">Hạn giao</label><input class="form-control form-control-sm" type="date" name="delivery_date"></div>
        <div class="col-md-4"><label class="form-label small">Nội dung</label><input class="form-control form-control-sm" name="description" required></div>
        <div class="col-md-2"><label class="form-label small">Giá trị</label><input class="form-control form-control-sm money" name="amount" required></div>
        <div class="col-md-2 d-flex align-items-end"><button class="btn btn-sm btn-primary w-100">Phát hành đơn</button></div>
    </form>
    <?php endif; ?>
</div></div>

<div class="tab-pane fade" id="tab-acceptance"><div class="card">
    <table class="table table-sm mb-0">
        <thead><tr><th>Ngày</th><th>Nội dung nghiệm thu</th><th>Kết quả</th><th>Ghi chú</th><th>Biên bản</th><th>Người lập</th></tr></thead>
        <?php foreach ($acceptances as $a): ?>
            <tr><td><?= fdate($a['accept_date']) ?></td><td><?= e($a['title']) ?></td><td><?= badge('acceptance_result', $a['result']) ?></td><td class="small"><?= e($a['note']) ?></td>
                <td><?= $a['document_id'] ? '<a href="' . url('documents/view', ['id' => $a['document_id']]) . '"><i class="bi bi-file-earmark-check"></i> Xem</a>' : '' ?></td><td class="small"><?= e($a['full_name']) ?></td></tr>
        <?php endforeach; if (!$acceptances): ?><tr><td colspan="6" class="text-muted">Chưa nghiệm thu</td></tr><?php endif; ?>
    </table>
    <?php if ($canC && $isActive): ?>
    <form method="post" enctype="multipart/form-data" action="<?= url('contracts/addAcceptance') ?>" class="card-body border-top row g-2">
        <?= csrf_field() ?><input type="hidden" name="contract_id" value="<?= (int) $c['id'] ?>">
        <div class="col-md-2"><input class="form-control form-control-sm" type="date" name="accept_date" value="<?= date('Y-m-d') ?>"></div>
        <div class="col-md-3"><input class="form-control form-control-sm" name="title" placeholder="Nội dung nghiệm thu" required></div>
        <div class="col-md-2"><select class="form-select form-select-sm" name="result"><?= select_options(labels('acceptance_result')) ?></select></div>
        <div class="col-md-2"><input class="form-control form-control-sm" name="note" placeholder="Ghi chú"></div>
        <div class="col-md-2"><input class="form-control form-control-sm" type="file" name="file" title="Biên bản (scan)"></div>
        <div class="col-md-1"><button class="btn btn-sm btn-primary w-100">Lập</button></div>
    </form>
    <?php endif; ?>
</div></div>

<div class="tab-pane fade" id="tab-invoices"><div class="card">
    <table class="table table-sm align-middle mb-0">
        <thead><tr><th>Số hóa đơn</th><th>Ngày</th><th class="text-end">Tiền hàng</th><th class="text-end">VAT</th><th class="text-end">Tổng</th><th>Tệp</th><th>Người gửi</th><th>Trạng thái</th><?php if ($canP): ?><th style="width:280px">Kiểm tra</th><?php endif; ?></tr></thead>
        <?php foreach ($invoices as $i): ?>
            <tr><td class="fw-semibold"><?= e($i['invoice_no']) ?></td><td><?= fdate($i['invoice_date']) ?></td><td class="text-end"><?= money($i['amount'], false) ?></td><td class="text-end"><?= money($i['vat_amount'], false) ?></td>
                <td class="text-end fw-bold"><?= money($i['amount'] + $i['vat_amount'], false) ?></td>
                <td><?= $i['document_id'] ? '<a href="' . url('documents/download', ['id' => $i['document_id']]) . '"><i class="bi bi-file-earmark-pdf"></i></a>' : '' ?></td>
                <td class="small"><?= e($i['submitter']) ?><br><?= fdatetime($i['submitted_at']) ?></td>
                <td><?= badge('invoice_status', $i['status']) ?><div class="small text-muted"><?= e($i['review_note']) ?></div></td>
                <?php if ($canP): ?><td><?php if ($i['status'] === 'submitted'): ?>
                    <form method="post" action="<?= url('contracts/reviewInvoice') ?>" class="d-flex gap-1"><?= csrf_field() ?><input type="hidden" name="invoice_id" value="<?= (int) $i['id'] ?>">
                        <input class="form-control form-control-sm" name="review_note" placeholder="Ghi chú">
                        <button class="btn btn-sm btn-outline-success" name="status" value="verified" title="Hợp lệ"><i class="bi bi-check"></i></button>
                        <button class="btn btn-sm btn-outline-danger" name="status" value="rejected" title="Từ chối"><i class="bi bi-x"></i></button></form>
                <?php endif; ?></td><?php endif; ?></tr>
        <?php endforeach; if (!$invoices): ?><tr><td colspan="9" class="text-muted">Nhà cung cấp chưa gửi hóa đơn (gửi qua Cổng nhà cung cấp)</td></tr><?php endif; ?>
    </table>
</div></div>

<div class="tab-pane fade" id="tab-payments"><div class="card">
    <div class="card-body pb-0"><div class="progress" style="height:18px">
        <div class="progress-bar bg-success" style="width:<?= min(100, $paidPct) ?>%">Đã giải ngân <?= $paidPct ?>%</div>
        <div class="progress-bar bg-info" style="width:<?= max(0, min(100 - $paidPct, $c['value'] > 0 ? ($stats['planned'] - $stats['paid']) * 100 / $c['value'] : 0)) ?>%">Kế hoạch</div>
    </div><p class="small text-muted mt-1">Quy trình: Kế hoạch → Đề nghị thanh toán (kèm hóa đơn hợp lệ, biên bản nghiệm thu) → Duyệt chi → Giải ngân (tự hạch toán chi phí dự án)</p></div>
    <div class="table-responsive"><table class="table align-middle mb-0">
        <thead><tr><th>Đợt</th><th class="text-end">Số tiền</th><th>Hạn</th><th>Hóa đơn / Nghiệm thu</th><th>Trạng thái</th><th class="text-end">Đã chi</th><?php if ($canP): ?><th style="width:360px">Thao tác</th><?php endif; ?></tr></thead>
        <?php foreach ($payments as $p): $d = days_left($p['due_date']); ?>
            <tr>
                <td><strong><?= e($p['milestone']) ?></strong><div class="small text-muted"><?= e($p['note']) ?></div></td>
                <td class="text-end"><?= money($p['amount']) ?></td>
                <td class="small"><?= fdate($p['due_date']) ?><?php if ($p['status'] !== 'paid' && $d !== null && $d <= 7): ?><br><span class="badge bg-<?= $d < 0 ? 'danger' : 'warning text-dark' ?>"><?= $d < 0 ? 'Quá hạn' : 'Sắp đến hạn' ?></span><?php endif; ?></td>
                <td class="small"><?= $p['invoice_no'] ? 'HĐơn ' . e($p['invoice_no']) : '' ?><?= $p['acceptance_title'] ? '<br>NT: ' . e($p['acceptance_title']) : '' ?></td>
                <td><?= badge('payment_status', $p['status']) ?></td>
                <td class="text-end"><?= $p['status'] === 'paid' ? money($p['paid_amount']) . '<br><small>' . fdate($p['paid_at']) . '</small>' : '' ?></td>
                <?php if ($canP): ?><td>
                    <?php if (in_array($p['status'], ['planned', 'rejected'], true) && in_array($c['status'], ['active', 'completed'], true)): ?>
                        <form method="post" action="<?= url('contracts/requestPayment') ?>" class="d-flex gap-1">
                            <?= csrf_field() ?><input type="hidden" name="payment_id" value="<?= (int) $p['id'] ?>">
                            <select class="form-select form-select-sm" name="invoice_id"><option value="">-- Hóa đơn --</option>
                                <?php foreach ($verifiedInvoices as $vi): if (in_array($vi['id'], $usedInvoiceIds)) continue; ?><option value="<?= (int) $vi['id'] ?>"><?= e($vi['invoice_no']) ?> (<?= money($vi['amount'] + $vi['vat_amount'], false) ?>)</option><?php endforeach; ?></select>
                            <select class="form-select form-select-sm" name="acceptance_id"><?= select_options(array_column($acceptances, 'title', 'id'), null, '-- Nghiệm thu --') ?></select>
                            <button class="btn btn-sm btn-warning text-nowrap">Đề nghị</button>
                        </form>
                    <?php elseif ($p['status'] === 'approved'): ?>
                        <form method="post" action="<?= url('contracts/disburse') ?>" class="d-flex gap-1" data-confirm="Xác nhận giải ngân?">
                            <?= csrf_field() ?><input type="hidden" name="payment_id" value="<?= (int) $p['id'] ?>">
                            <input class="form-control form-control-sm money" name="paid_amount" value="<?= money_input($p['amount']) ?>">
                            <input class="form-control form-control-sm" type="date" name="paid_at" value="<?= date('Y-m-d') ?>">
                            <button class="btn btn-sm btn-success text-nowrap"><i class="bi bi-cash"></i> Giải ngân</button>
                        </form>
                    <?php elseif ($p['status'] === 'pending_approval'): ?><span class="small text-muted">Đang chờ duyệt chi — <a href="<?= url('approvals/index') ?>">xem</a></span><?php endif; ?>
                    <?php if (in_array($p['status'], ['planned', 'rejected'], true)): ?><form class="d-inline" method="post" action="<?= url('contracts/deletePayment') ?>" data-confirm="Xóa đợt thanh toán?"><?= csrf_field() ?><input type="hidden" name="payment_id" value="<?= (int) $p['id'] ?>"><button class="btn btn-link btn-sm text-danger p-0">Xóa</button></form><?php endif; ?>
                </td><?php endif; ?>
            </tr>
        <?php endforeach; ?>
        <tr class="table-light fw-bold"><td>Tổng</td><td class="text-end"><?= money($stats['planned']) ?></td><td colspan="3"></td><td class="text-end"><?= money($stats['paid']) ?></td><?php if ($canP): ?><td></td><?php endif; ?></tr>
    </table></div>
    <?php if ($canP && in_array($c['status'], ['draft', 'pending_approval', 'active'], true)): ?>
    <form method="post" action="<?= url('contracts/addPayment') ?>" class="card-body border-top row g-2">
        <?= csrf_field() ?><input type="hidden" name="contract_id" value="<?= (int) $c['id'] ?>">
        <div class="col-md-4"><input class="form-control form-control-sm" name="milestone" placeholder="Đợt thanh toán (vd: Tạm ứng 30%)" required></div>
        <div class="col-md-2"><input class="form-control form-control-sm money" name="amount" placeholder="Số tiền" required></div>
        <div class="col-md-2"><input class="form-control form-control-sm" type="date" name="due_date"></div>
        <div class="col-md-2"><input class="form-control form-control-sm" name="note" placeholder="Ghi chú"></div>
        <div class="col-md-2"><button class="btn btn-sm btn-primary w-100">+ Thêm đợt</button></div>
    </form>
    <?php endif; ?>
    <?php if ($paymentHistory): ?>
    <div class="card-body border-top"><h6>Lịch sử duyệt chi</h6>
        <table class="table table-sm mb-0"><?php foreach ($paymentHistory as $h): ?><tr><td class="small"><?= e($h['title']) ?></td><td><?= e($h['step_name']) ?> (<?= e($h['role_name']) ?>)</td><td><?= badge('approval_status', $h['status']) ?></td><td class="small"><?= e($h['full_name']) ?> <?= fdatetime($h['acted_at']) ?></td><td class="small"><?= e($h['comment']) ?></td></tr><?php endforeach; ?></table>
    </div>
    <?php endif; ?>
</div></div>

<div class="tab-pane fade" id="tab-settlement"><div class="row">
    <div class="col-lg-6"><div class="card card-body">
        <h6>Bảng đối chiếu quyết toán</h6>
        <table class="table table-sm">
            <tr><td>Giá trị hợp đồng</td><td class="text-end"><?= money($c['value']) ?></td></tr>
            <tr><td>Giá trị đã đặt hàng</td><td class="text-end"><?= money($stats['ordered']) ?></td></tr>
            <tr><td>Giá trị hóa đơn đã nhận (trừ bị từ chối)</td><td class="text-end"><?= money($stats['invoiced']) ?></td></tr>
            <tr><td>Số lần nghiệm thu đạt</td><td class="text-end"><?= count(array_filter($acceptances, fn($a) => $a['result'] !== 'failed')) ?>/<?= count($acceptances) ?></td></tr>
            <tr><td>Đã giải ngân</td><td class="text-end"><?= money($stats['paid']) ?></td></tr>
            <?php if ($c['settled_amount'] !== null): ?>
                <tr class="table-success"><td><strong>Giá trị quyết toán</strong> (<?= fdatetime($c['settled_at']) ?>)</td><td class="text-end fw-bold"><?= money($c['settled_amount']) ?></td></tr>
                <tr><td>Còn phải thanh toán</td><td class="text-end fw-bold"><?= money($c['settled_amount'] - $stats['paid']) ?></td></tr>
                <tr><td>Ghi chú</td><td><?= e($c['settlement_note']) ?></td></tr>
            <?php endif; ?>
        </table>
        <?php if ($canP && $isActive): ?>
        <form method="post" action="<?= url('contracts/settle', ['id' => $c['id']]) ?>" data-confirm="Quyết toán sẽ đóng hợp đồng. Tiếp tục?">
            <?= csrf_field() ?>
            <div class="row g-2">
                <div class="col-md-6"><label class="form-label">Giá trị quyết toán</label><input class="form-control money" name="settled_amount" value="<?= money_input($c['value']) ?>" required><div class="form-text">Không nhỏ hơn số đã giải ngân (<?= money($stats['paid']) ?>), không vượt giá trị HĐ.</div></div>
                <div class="col-md-6"><label class="form-label">Ghi chú</label><input class="form-control" name="settlement_note"></div>
            </div>
            <button class="btn btn-success mt-2"><i class="bi bi-check2-square"></i> Quyết toán hợp đồng</button>
        </form>
        <?php elseif ($canP && $c['status'] === 'completed' && $c['settled_amount'] - $stats['paid'] > 0): ?>
            <form method="post" action="<?= url('contracts/finalPayment', ['id' => $c['id']]) ?>"><?= csrf_field() ?><button class="btn btn-primary">Tạo đợt thanh toán quyết toán (<?= money($c['settled_amount'] - $stats['paid']) ?>)</button></form>
        <?php endif; ?>
    </div></div>
    <div class="col-lg-6"><div class="card card-body">
        <h6>Đánh giá nhà cung cấp sau hợp đồng</h6>
        <?php if ($evaluated): ?><div class="alert alert-success mb-0">Đã đánh giá hiệu suất nhà cung cấp cho hợp đồng này.</div>
        <?php elseif (can('suppliers.evaluate') && $c['status'] === 'completed'): ?>
        <form method="post" action="<?= url('suppliers/evaluate') ?>">
            <?= csrf_field() ?><input type="hidden" name="supplier_id" value="<?= (int) $c['supplier_id'] ?>"><input type="hidden" name="contract_id" value="<?= (int) $c['id'] ?>">
            <?php foreach (['quality_score' => 'Chất lượng', 'schedule_score' => 'Tiến độ', 'price_score' => 'Giá cả', 'service_score' => 'Dịch vụ'] as $k => $lbl): ?>
                <div class="d-flex align-items-center gap-2 mb-1"><span style="width:90px"><?= $lbl ?></span><input type="range" class="form-range" min="1" max="10" value="8" name="<?= $k ?>" oninput="this.nextElementSibling.textContent=this.value"><span>8</span></div>
            <?php endforeach; ?>
            <textarea class="form-control mb-2" name="comment" placeholder="Nhận xét"></textarea>
            <button class="btn btn-primary">Lưu đánh giá</button>
        </form>
        <?php elseif ($c['status'] !== 'completed'): ?><p class="text-muted mb-0">Có thể đánh giá sau khi hợp đồng được quyết toán.</p>
        <?php else: ?><p class="text-muted mb-0">Hợp đồng đã quyết toán. Lãnh đạo hoặc cán bộ đấu thầu (người có quyền đánh giá nhà cung cấp) sẽ thực hiện đánh giá.</p><?php endif; ?>
    </div></div>
</div></div>

<div class="tab-pane fade" id="tab-docs"><div class="card card-body">
    <?php partial('documents/_list', ['docs' => $documents]); ?>
    <?php if ($canC && can('documents.manage')) partial('documents/_upload_inline', ['entityType' => 'contract', 'entityId' => $c['id'], 'category' => 'contract', 'back' => url('contracts/view', ['id' => $c['id'], 'tab' => 'docs'])]); ?>
</div></div>

<div class="tab-pane fade" id="tab-approval"><div class="row">
    <div class="col-lg-6"><div class="card card-body"><h6>Phê duyệt hợp đồng</h6><?php partial('approvals/_history', ['history' => $history]); ?></div></div>
    <div class="col-lg-6"><div class="card"><div class="card-header">Nhật ký</div><table class="table table-sm mb-0">
        <?php foreach ($logs as $l): ?><tr><td class="small text-nowrap"><?= fdatetime($l['created_at']) ?></td><td class="small"><?= e($l['full_name'] ?: $l['username']) ?></td><td class="small"><?= e(Audit::actionLabel($l['action'])) ?>: <?= e($l['details']) ?></td></tr><?php endforeach; ?>
    </table></div></div>
</div></div>
</div>

<div class="modal fade" id="termModal"><div class="modal-dialog"><form method="post" action="<?= url('contracts/terminate', ['id' => $c['id']]) ?>" class="modal-content">
    <?= csrf_field() ?>
    <div class="modal-header"><h5 class="modal-title">Chấm dứt hợp đồng</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body"><label class="form-label">Lý do *</label><textarea class="form-control" name="reason" rows="3" required></textarea></div>
    <div class="modal-footer"><button class="btn btn-danger">Xác nhận</button></div>
</form></div></div>
