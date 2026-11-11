<?php /* $docs */ ?>
<table class="table table-sm align-middle">
    <thead><tr><th>Tài liệu</th><th>Loại</th><th>Tệp</th><th>Phiên bản</th><th>Mức mật</th><th>Người tải</th><th></th></tr></thead>
    <?php foreach ($docs as $d): if (!Access::document($d)) continue; ?>
        <tr>
            <td><?= is_supplier_user() ? e($d['title']) : '<a href="' . url('documents/view', ['id' => $d['id']]) . '">' . e($d['title']) . '</a>' ?><div class="small text-muted"><?= e($d['code']) ?></div></td>
            <td><?= badge('doc_category', $d['category']) ?></td>
            <td class="small"><?= e($d['original_name'] ?? '') ?> <?= isset($d['size_bytes']) ? '(' . file_size($d['size_bytes']) . ')' : '' ?></td>
            <td>v<?= (int) $d['current_version'] ?></td>
            <td><?= badge('confidentiality', $d['confidentiality']) ?></td>
            <td class="small"><?= e($d['creator'] ?? '') ?><br><?= fdatetime($d['updated_at'] ?: $d['created_at']) ?></td>
            <td class="text-nowrap"><?php if (Access::document($d, 'download')): ?><a class="btn btn-sm btn-outline-primary" href="<?= url('documents/download', ['id' => $d['id']]) ?>"><i class="bi bi-download"></i></a><?php endif; ?></td>
        </tr>
    <?php endforeach; if (!$docs): ?><tr><td colspan="7" class="text-muted">Chưa có tài liệu</td></tr><?php endif; ?>
</table>
