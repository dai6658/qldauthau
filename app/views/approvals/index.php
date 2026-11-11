<div class="page-head"><div><h2>Hồ sơ chờ phê duyệt</h2><div class="sub">Quy trình phê duyệt nhiều bước theo cấu hình của tổ chức</div></div></div>
<div class="card">
    <div class="card-header">Đang chờ bạn xử lý (<?= count($pending) ?>)</div>
    <div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
        <thead><tr><th>Loại</th><th>Hồ sơ</th><th>Bước</th><th>Người trình</th><th>Ngày trình</th><th style="width:360px">Xử lý</th></tr></thead>
        <?php foreach ($pending as $a): ?>
            <tr>
                <td><?= badge('entity', $a['entity_type']) ?></td>
                <td><a href="<?= Workflow::link($a['entity_type'], (int) $a['entity_id']) ?>" class="fw-semibold"><?= e($a['title']) ?></a><div class="small text-muted"><?= e($a['summary']) ?></div></td>
                <td>Bước <?= (int) $a['step_no'] ?>: <?= e($a['step_name']) ?><div class="small text-muted"><?= e($a['role_name']) ?></div></td>
                <td><?= e($a['requester']) ?></td>
                <td><?= fdatetime($a['created_at']) ?><div class="small text-muted"><?= time_ago($a['created_at']) ?></div></td>
                <td>
                    <form method="post" action="<?= url('approvals/act') ?>" class="d-flex gap-1">
                        <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $a['id'] ?>">
                        <input class="form-control form-control-sm" name="comment" placeholder="Ý kiến (bắt buộc khi từ chối)">
                        <button class="btn btn-success btn-sm" name="decision" value="approve" title="Phê duyệt"><i class="bi bi-check-lg"></i></button>
                        <button class="btn btn-danger btn-sm" name="decision" value="reject" title="Từ chối"><i class="bi bi-x-lg"></i></button>
                    </form>
                </td>
            </tr>
        <?php endforeach; if (!$pending): ?><tr><td colspan="6" class="text-center text-muted py-4">Không có hồ sơ nào chờ phê duyệt 🎉</td></tr><?php endif; ?>
    </table>
    </div>
</div>
<div class="card">
    <div class="card-header">Lịch sử phê duyệt</div>
    <table class="table table-sm mb-0">
        <thead><tr><th>Hồ sơ</th><th>Bước</th><th>Kết quả</th><th>Ý kiến</th><th>Người xử lý</th><th>Thời gian</th></tr></thead>
        <?php foreach ($history as $a): ?>
            <tr><td><a href="<?= Workflow::link($a['entity_type'], (int) $a['entity_id']) ?>"><?= e($a['title']) ?></a></td><td><?= e($a['step_name']) ?></td>
                <td><?= badge('approval_status', $a['status']) ?></td><td><?= e($a['comment']) ?></td><td><?= e($a['actor']) ?></td><td><?= fdatetime($a['acted_at']) ?></td></tr>
        <?php endforeach; ?>
    </table>
</div>
