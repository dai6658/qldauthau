<div class="card">
    <div class="card-body text-center py-5">
        <div class="display-4 text-danger fw-bold"><?= (int) $code ?></div>
        <p class="lead mt-3"><?= e($message) ?></p>
        <a href="index.php" class="btn btn-primary"><i class="bi bi-house"></i> Về trang chủ</a>
        <button onclick="history.back()" class="btn btn-outline-secondary">Quay lại</button>
    </div>
</div>
