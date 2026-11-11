<?php
$steps = ['draft' => 'Lập HSMT', 'pending_approval' => 'Trình duyệt', 'approved' => 'Đã duyệt', 'published' => 'Mời thầu', 'closed' => 'Đóng thầu', 'evaluating' => 'Đánh giá', 'awarded' => 'Trao thầu'];
$keys = array_keys($steps);
$curIdx = array_search($t['status'], $keys, true);
$critTotal = array_sum(array_column($criteria, 'max_score'));
$itemsTotal = array_sum(array_map(fn($i) => $i['quantity'] * $i['estimated_price'], $items));
$isDraft = $t['status'] === 'draft';
?>
<div class="page-head">
    <div><h2><?= e($t['code']) ?> — <?= e($t['title']) ?></h2>
        <div class="sub"><?= badge('tender_status', $t['status']) ?> · <?= e($t['project_code']) ?> · <?= e($t['department_name']) ?> · lập bởi <?= e($t['creator_name']) ?></div></div>
    <div class="d-flex flex-wrap gap-2">
        <a class="btn btn-outline-secondary" target="_blank" href="<?= url('tenders/print', ['id' => $t['id']]) ?>"><i class="bi bi-printer"></i> In / PDF</a>
        <?php if ($canEdit && $isDraft): ?>
            <a class="btn btn-outline-primary" href="<?= url('tenders/edit', ['id' => $t['id']]) ?>"><i class="bi bi-pencil"></i> Sửa</a>
            <form method="post" action="<?= url('tenders/submitApproval', ['id' => $t['id']]) ?>" data-confirm="Trình phê duyệt HSMT này?"><?= csrf_field() ?><button class="btn btn-warning"><i class="bi bi-send"></i> Trình duyệt</button></form>
        <?php endif; ?>
        <?php if (can('tenders.publish') && $t['status'] === 'approved'): ?>
            <form method="post" action="<?= url('tenders/publish', ['id' => $t['id']]) ?>" data-confirm="Công bố HSMT và gửi thông báo tới nhà thầu?"><?= csrf_field() ?><button class="btn btn-primary"><i class="bi bi-megaphone"></i> Công bố</button></form>
        <?php endif; ?>
        <?php if (can('tenders.publish') && $t['status'] === 'published'): ?>
            <form method="post" action="<?= url('tenders/close', ['id' => $t['id']]) ?>" data-confirm="Đóng thầu?"><?= csrf_field() ?><button class="btn btn-dark"><i class="bi bi-lock"></i> Đóng thầu</button></form>
        <?php endif; ?>
        <?php if ($t['status'] === 'closed' && Access::tender($t, 'evaluate')): ?>
            <form method="post" action="<?= url('tenders/openBids', ['id' => $t['id']]) ?>" data-confirm="Mở thầu (giải niêm phong HSDT)?"><?= csrf_field() ?><button class="btn btn-success"><i class="bi bi-unlock"></i> Mở thầu</button></form>
        <?php endif; ?>
        <?php if (in_array($t['status'], ['evaluating', 'awarded'], true) && can('bids.view')): ?>
            <a class="btn btn-success" href="<?= url('evaluation/tender', ['id' => $t['id']]) ?>"><i class="bi bi-clipboard-data"></i> Đánh giá HSDT</a>
        <?php endif; ?>
        <?php if (can('tenders.publish') && !in_array($t['status'], ['awarded', 'cancelled'], true)): ?>
            <button class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#cancelModal"><i class="bi bi-x-circle"></i> Hủy thầu</button>
        <?php endif; ?>
    </div>
</div>

<?php if ($t['status'] === 'cancelled'): ?>
    <div class="alert alert-danger">Gói thầu đã bị hủy. Lý do: <?= e($t['cancel_reason']) ?></div>
<?php else: ?>
<div class="card card-body py-2 mb-3">
    <div class="d-flex justify-content-between flex-wrap small">
        <?php foreach ($steps as $k => $lbl): $i = array_search($k, $keys, true); ?>
            <div class="text-center flex-fill <?= $i < $curIdx ? 'text-success' : ($i === $curIdx ? 'text-primary fw-bold' : 'text-muted') ?>">
                <i class="bi <?= $i < $curIdx ? 'bi-check-circle-fill' : ($i === $curIdx ? 'bi-record-circle-fill' : 'bi-circle') ?> fs-5"></i><br><?= $lbl ?>
            </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<ul class="nav nav-tabs mb-3">
    <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-overview">Tổng quan</button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-content">Nội dung HSMT</button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-items">Hạng mục (<?= count($items) ?>)</button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-criteria">Tiêu chí (<?= $critTotal ?>đ)</button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-team">Phân quyền & mời thầu</button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-docs">Tài liệu (<?= count($documents) ?>)</button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-bids">HSDT (<?= count($bids) ?>)</button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-approval">Phê duyệt</button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-log">Lịch sử</button></li>
</ul>

<div class="tab-content">
<div class="tab-pane fade show active" id="tab-overview"><div class="row">
    <div class="col-lg-7"><div class="card card-body"><dl class="dl-grid">
        <dt>Dự án</dt><dd><a href="<?= url('projects/view', ['id' => $t['project_id']]) ?>"><?= e($t['project_code']) ?> – <?= e($t['project_name']) ?></a></dd>
        <dt>Đơn vị / phòng ban</dt><dd><?= e($t['company_name']) ?> / <?= e($t['department_name']) ?></dd>
        <dt>Lĩnh vực</dt><dd><?= e(label('category', $t['category'])) ?></dd>
        <dt>Hình thức</dt><dd><?= e(label('method', $t['method'])) ?></dd>
        <dt>Phương pháp đánh giá</dt><dd><?= e(label('eval_method', $t['evaluation_method'])) ?> — KT <?= (int) $t['tech_weight'] ?>% / Giá <?= (int) $t['price_weight'] ?>% · điểm KT tối thiểu <?= (int) $t['pass_tech_score'] ?></dd>
        <dt>Giá gói thầu</dt><dd class="fw-bold"><?= money($t['budget']) ?></dd>
        <dt>Bảo đảm dự thầu</dt><dd><?= money($t['bid_security']) ?></dd>
        <dt>Công bố</dt><dd><?= fdatetime($t['publish_at']) ?></dd>
        <dt>Đóng thầu</dt><dd><?= fdatetime($t['close_at']) ?> <?php if ($t['status'] === 'published'): ?><span class="badge bg-danger">còn <?= days_left($t['close_at']) ?> ngày</span><?php endif; ?></dd>
        <dt>Mở thầu</dt><dd><?= fdatetime($t['opening_at']) ?></dd>
        <dt>Mô tả</dt><dd class="pre-wrap"><?= e($t['description']) ?></dd>
    </dl></div></div>
    <div class="col-lg-5">
        <div class="card"><div class="card-header">Kiểm soát ngân sách</div><div class="card-body">
            <?php if ($item): ?>
                <div class="small text-muted">Khoản mục TMĐT</div><div class="fw-semibold mb-2"><?= e($item['name']) ?></div>
                <div class="d-flex justify-content-between"><span>Hạn mức được duyệt</span><strong><?= money($item['approved_amount']) ?></strong></div>
            <?php else: ?><div class="text-muted small">Chưa gắn khoản mục TMĐT.</div><?php endif; ?>
            <div class="d-flex justify-content-between"><span>Giá gói thầu</span><strong><?= money($t['budget']) ?></strong></div>
            <div class="d-flex justify-content-between"><span>Tổng dự toán hạng mục</span><strong class="<?= $itemsTotal > $t['budget'] ? 'text-danger' : '' ?>"><?= money($itemsTotal) ?></strong></div>
            <?php if ($itemsTotal > $t['budget']): ?><div class="alert alert-warning small mt-2 mb-0">Tổng dự toán hạng mục vượt giá gói thầu.</div><?php endif; ?>
        </div></div>
        <?php if ($contract): ?>
        <div class="card"><div class="card-header">Kết quả</div><div class="card-body">
            Hợp đồng <a href="<?= url('contracts/view', ['id' => $contract['id']]) ?>"><?= e($contract['code']) ?></a> — <?= money($contract['value']) ?> <?= badge('contract_status', $contract['status']) ?>
            <div class="small text-muted mt-1">Tiết kiệm so với giá gói thầu: <?= money($t['budget'] - $contract['value']) ?> (<?= $t['budget'] > 0 ? round(($t['budget'] - $contract['value']) * 100 / $t['budget'], 1) : 0 ?>%)</div>
        </div></div>
        <?php endif; ?>
    </div>
</div></div>

<div class="tab-pane fade" id="tab-content"><div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span>Nội dung hồ sơ mời thầu</span>
        <?php if ($canEdit && $isDraft): ?>
        <form method="post" action="<?= url('tenders/regenerate', ['id' => $t['id']]) ?>" class="d-flex gap-2" data-confirm="Sinh lại nội dung sẽ ghi đè nội dung hiện tại. Tiếp tục?">
            <?= csrf_field() ?>
            <select class="form-select form-select-sm" name="template_id"><?= select_options(pairs('SELECT id, name FROM tender_templates'), $t['template_id']) ?></select>
            <input type="hidden" name="with_criteria" value="1">
            <button class="btn btn-sm btn-outline-primary text-nowrap"><i class="bi bi-magic"></i> Sinh tự động</button>
        </form>
        <?php endif; ?>
    </div>
    <div class="card-body">
        <?php if ($canEdit && $isDraft): ?>
            <form method="post" action="<?= url('tenders/saveContent', ['id' => $t['id']]) ?>">
                <?= csrf_field() ?>
                <textarea class="form-control doc-content" name="content" rows="24"><?= e($t['content']) ?></textarea>
                <button class="btn btn-primary mt-2"><i class="bi bi-save"></i> Lưu nội dung</button>
            </form>
        <?php else: ?>
            <div class="doc-content border rounded p-3"><?= $t['content'] ? e($t['content']) : '<span class="text-muted">Chưa có nội dung</span>' ?></div>
        <?php endif; ?>
    </div>
</div></div>

<div class="tab-pane fade" id="tab-items"><div class="card">
    <table class="table table-sm align-middle mb-0">
        <thead><tr><th>#</th><th>Hạng mục</th><th>Yêu cầu kỹ thuật</th><th>ĐVT</th><th class="text-end">SL</th><th class="text-end">Đơn giá dự toán</th><th class="text-end">Thành tiền</th><?php if ($canEdit && $isDraft): ?><th></th><?php endif; ?></tr></thead>
        <?php foreach ($items as $i => $it): ?>
            <tr><td><?= $i + 1 ?></td><td class="fw-semibold"><?= e($it['name']) ?></td><td class="small"><?= e($it['specs']) ?></td><td><?= e($it['unit']) ?></td>
                <td class="text-end"><?= num($it['quantity'], 0) ?></td><td class="text-end"><?= money($it['estimated_price']) ?></td><td class="text-end"><?= money($it['quantity'] * $it['estimated_price']) ?></td>
                <?php if ($canEdit && $isDraft): ?><td><form method="post" action="<?= url('tenders/deleteItem', ['id' => $t['id']]) ?>"><?= csrf_field() ?><input type="hidden" name="item_id" value="<?= (int) $it['id'] ?>"><button class="btn btn-sm btn-link text-danger"><i class="bi bi-trash"></i></button></form></td><?php endif; ?></tr>
        <?php endforeach; ?>
        <tr class="table-light"><td colspan="6" class="text-end fw-bold">Tổng dự toán</td><td class="text-end fw-bold"><?= money($itemsTotal) ?></td><?php if ($canEdit && $isDraft): ?><td></td><?php endif; ?></tr>
    </table>
    <?php if ($canEdit && $isDraft): ?>
    <form method="post" action="<?= url('tenders/addItem', ['id' => $t['id']]) ?>" class="card-body border-top row g-2">
        <?= csrf_field() ?>
        <div class="col-md-3"><input class="form-control form-control-sm" name="name" placeholder="Tên hạng mục" required></div>
        <div class="col-md-3"><input class="form-control form-control-sm" name="specs" placeholder="Yêu cầu kỹ thuật"></div>
        <div class="col-md-1"><input class="form-control form-control-sm" name="unit" placeholder="ĐVT"></div>
        <div class="col-md-1"><input class="form-control form-control-sm" name="quantity" value="1"></div>
        <div class="col-md-2"><input class="form-control form-control-sm money" name="estimated_price" placeholder="Đơn giá"></div>
        <div class="col-md-2"><button class="btn btn-sm btn-primary w-100">+ Thêm</button></div>
    </form>
    <?php endif; ?>
</div></div>

<div class="tab-pane fade" id="tab-criteria"><div class="card">
    <?php if ($critTotal !== 100 && $critTotal > 0): ?><div class="alert alert-warning m-3 mb-0 small">Tổng điểm tối đa hiện là <?= $critTotal ?> (khuyến nghị 100). Điểm kỹ thuật sẽ được quy đổi về thang 100.</div><?php endif; ?>
    <table class="table table-sm mb-0">
        <thead><tr><th>#</th><th>Tiêu chí</th><th>Mô tả / cách chấm</th><th class="text-end">Điểm tối đa</th><?php if ($canEdit && $isDraft): ?><th></th><?php endif; ?></tr></thead>
        <?php foreach ($criteria as $i => $c): ?>
            <tr><td><?= $i + 1 ?></td><td><?= e($c['name']) ?></td><td class="small"><?= e($c['description']) ?></td><td class="text-end"><?= (int) $c['max_score'] ?></td>
                <?php if ($canEdit && $isDraft): ?><td><form method="post" action="<?= url('tenders/deleteCriterion', ['id' => $t['id']]) ?>"><?= csrf_field() ?><input type="hidden" name="criterion_id" value="<?= (int) $c['id'] ?>"><button class="btn btn-sm btn-link text-danger"><i class="bi bi-trash"></i></button></form></td><?php endif; ?></tr>
        <?php endforeach; ?>
    </table>
    <?php if ($canEdit && $isDraft): ?>
    <form method="post" action="<?= url('tenders/addCriterion', ['id' => $t['id']]) ?>" class="card-body border-top row g-2">
        <?= csrf_field() ?>
        <div class="col-md-4"><input class="form-control form-control-sm" name="name" placeholder="Tiêu chí" required></div>
        <div class="col-md-4"><input class="form-control form-control-sm" name="description" placeholder="Mô tả cách chấm"></div>
        <div class="col-md-2"><input class="form-control form-control-sm" type="number" name="max_score" placeholder="Điểm tối đa" required></div>
        <div class="col-md-2"><button class="btn btn-sm btn-primary w-100">+ Thêm</button></div>
    </form>
    <?php endif; ?>
</div></div>

<div class="tab-pane fade" id="tab-team"><div class="row">
    <div class="col-lg-6"><div class="card">
        <div class="card-header">Thành viên được phân quyền trên hồ sơ này</div>
        <table class="table table-sm mb-0">
            <?php foreach ($members as $m): ?>
                <tr><td><?= e($m['full_name']) ?><div class="small text-muted"><?= e($m['dept']) ?></div></td><td><?= badge('member_access', $m['access']) ?></td>
                    <td><?php if ($canEdit): ?><form method="post" action="<?= url('tenders/removeMember', ['id' => $t['id']]) ?>"><?= csrf_field() ?><input type="hidden" name="user_id" value="<?= (int) $m['user_id'] ?>"><button class="btn btn-sm btn-link text-danger p-0">Gỡ</button></form><?php endif; ?></td></tr>
            <?php endforeach; ?>
        </table>
        <?php if ($canEdit): ?>
        <form method="post" action="<?= url('tenders/addMember', ['id' => $t['id']]) ?>" class="card-body border-top row g-2">
            <?= csrf_field() ?>
            <div class="col-md-6"><select class="form-select form-select-sm" name="user_id"><?= select_options($users) ?></select></div>
            <div class="col-md-4"><select class="form-select form-select-sm" name="access"><?= select_options(labels('member_access'), 'evaluate') ?></select></div>
            <div class="col-md-2"><button class="btn btn-sm btn-primary w-100">Thêm</button></div>
        </form>
        <?php endif; ?>
        <div class="card-footer small text-muted">Ngoài thành viên, người thuộc phòng ban phụ trách dự án có quyền xem; lãnh đạo/kiểm soát có quyền xem tất cả.</div>
    </div></div>
    <div class="col-lg-6"><div class="card">
        <div class="card-header">Nhà thầu được mời <?= $t['method'] === 'open' ? '<span class="badge bg-info text-dark">Đấu thầu rộng rãi — mọi NCC đã duyệt đều tham gia được</span>' : '' ?></div>
        <table class="table table-sm mb-0">
            <?php foreach ($invites as $iv): ?>
                <tr><td><?= e($iv['code']) ?></td><td><?= e($iv['name']) ?></td><td class="small"><?= fdatetime($iv['invited_at']) ?></td>
                    <td><?php if ($canEdit && in_array($t['status'], ['draft', 'approved'], true)): ?><form method="post" action="<?= url('tenders/uninvite', ['id' => $t['id']]) ?>"><?= csrf_field() ?><input type="hidden" name="supplier_id" value="<?= (int) $iv['supplier_id'] ?>"><button class="btn btn-sm btn-link text-danger p-0">Bỏ</button></form><?php endif; ?></td></tr>
            <?php endforeach; if (!$invites): ?><tr><td class="text-muted">Chưa mời nhà thầu nào</td></tr><?php endif; ?>
        </table>
        <?php if ($canEdit && !in_array($t['status'], ['awarded', 'cancelled', 'closed', 'evaluating'], true)): ?>
        <form method="post" action="<?= url('tenders/invite', ['id' => $t['id']]) ?>" class="card-body border-top">
            <?= csrf_field() ?>
            <select class="form-select form-select-sm mb-2" name="supplier_ids[]" multiple size="5"><?= select_options($suppliers) ?></select>
            <button class="btn btn-sm btn-primary">Gửi thư mời</button> <small class="text-muted">Giữ Ctrl để chọn nhiều</small>
        </form>
        <?php endif; ?>
    </div></div>
</div></div>

<div class="tab-pane fade" id="tab-docs"><div class="card card-body">
    <p class="small text-muted">Hồ sơ thiết kế, bản vẽ kỹ thuật, thông báo mời thầu, quyết định phê duyệt... Tài liệu mức <b>Công khai</b> sẽ hiển thị cho nhà thầu sau khi công bố.</p>
    <?php partial('documents/_list', ['docs' => $documents]); ?>
    <?php if ($canEdit && can('documents.manage')) partial('documents/_upload_inline', ['entityType' => 'tender', 'entityId' => $t['id'], 'category' => 'tender_doc', 'back' => url('tenders/view', ['id' => $t['id'], 'tab' => 'docs'])]); ?>
</div></div>

<div class="tab-pane fade" id="tab-bids"><div class="card">
    <?php if ($sealed): ?>
        <div class="card-body text-center py-5">
            <i class="bi bi-lock-fill display-5 text-secondary"></i>
            <p class="mt-2 mb-0">HSDT đang được <strong>niêm phong điện tử</strong> đến thời điểm đóng thầu (<?= fdatetime($t['close_at']) ?>).</p>
            <p class="text-muted">Đã nhận <strong><?= count(array_filter($bids, fn($b) => $b['status'] === 'submitted')) ?></strong> hồ sơ.</p>
        </div>
    <?php elseif (!can('bids.view')): ?>
        <div class="card-body text-muted">Bạn không có quyền xem HSDT.</div>
    <?php else: ?>
        <table class="table table-sm align-middle mb-0">
            <thead><tr><th>Hạng</th><th>Nhà thầu</th><th>Mã HSDT</th><th>Nộp lúc</th><th class="text-end">Giá dự thầu</th><th>Điểm KT</th><th>Điểm giá</th><th>Tổng</th><th>Trạng thái</th></tr></thead>
            <?php foreach ($bids as $b): ?>
                <tr class="<?= $b['status'] === 'awarded' ? 'rank-1' : '' ?>"><td><?= $b['rank_no'] ? '#' . (int) $b['rank_no'] : '—' ?></td><td><?= e($b['supplier_name']) ?></td><td><?= e($b['code']) ?></td><td class="small"><?= fdatetime($b['submitted_at']) ?></td>
                    <td class="text-end"><?= money($b['total_price']) ?></td><td><?= num($b['tech_score']) ?></td><td><?= num($b['price_score']) ?></td><td class="fw-bold"><?= num($b['final_score']) ?></td><td><?= badge('bid_status', $b['status']) ?></td></tr>
            <?php endforeach; ?>
        </table>
    <?php endif; ?>
</div></div>

<div class="tab-pane fade" id="tab-approval"><div class="card card-body"><?php partial('approvals/_history', ['history' => $history]); ?></div></div>

<div class="tab-pane fade" id="tab-log"><div class="card"><table class="table table-sm mb-0">
    <?php foreach ($logs as $l): ?><tr><td class="small text-nowrap"><?= fdatetime($l['created_at']) ?></td><td><?= e($l['full_name'] ?: $l['username']) ?></td><td><?= e(Audit::actionLabel($l['action'])) ?></td><td class="small"><?= e($l['details']) ?></td></tr><?php endforeach; ?>
</table></div></div>
</div>

<div class="modal fade" id="cancelModal"><div class="modal-dialog"><form method="post" action="<?= url('tenders/cancel', ['id' => $t['id']]) ?>" class="modal-content">
    <?= csrf_field() ?>
    <div class="modal-header"><h5 class="modal-title">Hủy gói thầu</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body"><label class="form-label">Lý do hủy thầu *</label><textarea class="form-control" name="reason" rows="3" required></textarea></div>
    <div class="modal-footer"><button class="btn btn-danger">Xác nhận hủy</button></div>
</form></div></div>
