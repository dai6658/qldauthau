<div class="page-head"><div><h2>Đơn đặt hàng</h2><div class="sub">Xác nhận đơn hàng và thời gian giao hàng dự kiến</div></div></div>
<div class="card"><div class="table-responsive"><table class="table align-middle mb-0">
    <thead><tr><th>Số đơn</th><th>Hợp đồng</th><th>Ngày đặt</th><th>Nội dung</th><th class="text-end">Giá trị</th><th>Giao hàng</th><th>Trạng thái</th><th style="width:320px">Xác nhận</th></tr></thead>
    <?php foreach ($orders as $o): ?>
        <tr>
            <td class="fw-semibold"><?= e($o['code']) ?></td><td class="small"><?= e($o['contract_code']) ?><br><?= e($o['contract_title']) ?></td>
            <td><?= fdate($o['order_date']) ?></td><td class="small"><?= e($o['description']) ?></td><td class="text-end"><?= money($o['amount']) ?></td>
            <td><?= fdate($o['delivery_date']) ?></td><td><?= badge('po_status', $o['status']) ?></td>
            <td><?php if ($o['status'] === 'sent'): ?>
                <form method="post" action="<?= url('portal/confirmOrder') ?>" class="d-flex gap-1">
                    <?= csrf_field() ?><input type="hidden" name="order_id" value="<?= (int) $o['id'] ?>">
                    <input class="form-control form-control-sm" type="date" name="delivery_date" value="<?= e($o['delivery_date']) ?>" title="Ngày giao dự kiến">
                    <input class="form-control form-control-sm" name="supplier_note" placeholder="Ghi chú">
                    <button class="btn btn-sm btn-success text-nowrap"><i class="bi bi-check2"></i> Xác nhận</button>
                </form>
            <?php else: ?><span class="small text-muted"><?= e($o['supplier_note']) ?> <?= fdatetime($o['confirmed_at']) ?></span><?php endif; ?></td>
        </tr>
    <?php endforeach; if (!$orders): ?><tr><td colspan="8" class="text-center text-muted py-4">Chưa có đơn hàng</td></tr><?php endif; ?>
</table></div></div>
