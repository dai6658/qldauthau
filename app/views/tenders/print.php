<div class="d-flex justify-content-between mb-4">
    <div class="text-center"><strong><?= e(mb_strtoupper($t['investor'] ?: setting('org_display_name', ''))) ?></strong><br>———</div>
    <div class="text-center"><strong>CỘNG HÒA XÃ HỘI CHỦ NGHĨA VIỆT NAM</strong><br><u>Độc lập - Tự do - Hạnh phúc</u></div>
</div>
<h3 class="text-center fw-bold">HỒ SƠ MỜI THẦU</h3>
<p class="text-center">Gói thầu: <strong><?= e($t['title']) ?></strong><br>Mã: <?= e($t['code']) ?> · Dự án: <?= e($t['project_name']) ?></p>
<div class="doc-content mb-4"><?= e($t['content']) ?></div>
<h5>PHỤ LỤC 1. BẢNG PHẠM VI CUNG CẤP</h5>
<table class="table table-bordered table-sm">
    <thead><tr><th>STT</th><th>Hạng mục</th><th>Yêu cầu kỹ thuật</th><th>ĐVT</th><th>Số lượng</th></tr></thead>
    <?php foreach ($items as $i => $it): ?><tr><td><?= $i + 1 ?></td><td><?= e($it['name']) ?></td><td><?= e($it['specs']) ?></td><td><?= e($it['unit']) ?></td><td class="text-end"><?= num($it['quantity'], 0) ?></td></tr><?php endforeach; ?>
</table>
<h5>PHỤ LỤC 2. TIÊU CHUẨN ĐÁNH GIÁ VỀ KỸ THUẬT</h5>
<table class="table table-bordered table-sm">
    <thead><tr><th>STT</th><th>Tiêu chí</th><th>Điểm tối đa</th></tr></thead>
    <?php foreach ($criteria as $i => $c): ?><tr><td><?= $i + 1 ?></td><td><?= e($c['name']) ?> <?= $c['description'] ? '<br><small>' . e($c['description']) . '</small>' : '' ?></td><td class="text-end"><?= (int) $c['max_score'] ?></td></tr><?php endforeach; ?>
    <tr><th colspan="2" class="text-end">Tổng</th><th class="text-end"><?= array_sum(array_column($criteria, 'max_score')) ?></th></tr>
</table>
<div class="d-flex justify-content-end mt-5"><div class="text-center">Ngày ..... tháng ..... năm <?= date('Y') ?><br><strong>ĐẠI DIỆN BÊN MỜI THẦU</strong><br><br><br><br></div></div>
