<div class="page-head"><h2><?= e($title) ?></h2><a class="btn btn-light" href="<?= url('suppliers/index') ?>">← Danh sách</a></div>
<form method="post" action="<?= $action ?>" class="card card-body">
    <?= csrf_field() ?>
    <?php partial('suppliers/_fields', ['s' => $s]); ?>
    <div class="mt-3"><button class="btn btn-primary"><i class="bi bi-save"></i> Lưu</button></div>
</form>
