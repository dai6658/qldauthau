<?php /* Dùng chung: $history = Workflow::history(type, id) */ ?>
<?php if (!$history): ?>
    <p class="text-muted mb-0">Chưa trình phê duyệt.</p>
<?php else: ?>
<table class="table table-sm mb-0">
    <thead><tr><th>Lần</th><th>Bước</th><th>Vai trò</th><th>Trạng thái</th><th>Người xử lý</th><th>Ý kiến</th><th>Thời gian</th></tr></thead>
    <?php foreach ($history as $h): ?>
        <tr><td><?= (int) $h['round_no'] ?></td><td><?= (int) $h['step_no'] ?>. <?= e($h['step_name']) ?></td><td><?= e($h['role_name']) ?></td>
            <td><?= badge('approval_status', $h['status']) ?></td><td><?= e($h['actor']) ?></td><td><?= e($h['comment']) ?></td><td><?= fdatetime($h['acted_at'] ?: $h['created_at']) ?></td></tr>
    <?php endforeach; ?>
</table>
<?php if ($pa = array_values(array_filter($history, fn($h) => $h['status'] === 'pending'))): if (Workflow::canAct($pa[0])): ?>
    <form method="post" action="<?= url('approvals/act') ?>" class="d-flex gap-2 mt-3 no-print">
        <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $pa[0]['id'] ?>">
        <input class="form-control" name="comment" placeholder="Ý kiến phê duyệt">
        <button class="btn btn-success" name="decision" value="approve"><i class="bi bi-check-lg"></i> Phê duyệt</button>
        <button class="btn btn-danger" name="decision" value="reject"><i class="bi bi-x-lg"></i> Từ chối</button>
    </form>
<?php endif; endif; ?>
<?php endif; ?>
