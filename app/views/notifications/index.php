<div class="page-head"><h2>Thông báo của tôi</h2>
    <form method="post" action="<?= url('notifications/readAll') ?>"><?= csrf_field() ?><button class="btn btn-outline-secondary btn-sm">Đánh dấu tất cả đã đọc</button></form></div>
<div class="card">
    <div class="list-group list-group-flush">
        <?php foreach ($p['items'] as $n): ?>
            <a class="list-group-item list-group-item-action <?= $n['is_read'] ? '' : 'list-group-item-primary' ?>" href="<?= url('notifications/open', ['id' => $n['id']]) ?>">
                <div class="d-flex justify-content-between"><strong><i class="bi bi-circle-fill text-<?= e($n['type']) ?> small"></i> <?= e($n['title']) ?></strong><small><?= fdatetime($n['created_at']) ?></small></div>
                <div class="small"><?= e($n['message']) ?></div>
            </a>
        <?php endforeach; if (!$p['items']): ?><div class="p-4 text-center text-muted">Chưa có thông báo</div><?php endif; ?>
    </div>
    <div class="card-footer"><?= pager($p) ?></div>
</div>
