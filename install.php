<?php
/**
 * Trình cài đặt: kiểm tra môi trường, tạo CSDL, nạp dữ liệu mẫu, sinh khóa chủ (app_key),
 * tạo khóa mã hóa đầu tiên và tài liệu mẫu (đã mã hóa).
 * Sau khi cài xong, tệp config/installed.lock được tạo và trình cài đặt bị khóa.
 */
require __DIR__ . '/app/bootstrap.php';

$lockFile = ROOT_PATH . '/config/installed.lock';
$configFile = ROOT_PATH . '/config/config.php';

/** Tách tệp SQL thành từng câu lệnh (hiểu chuỗi nhiều dòng, chú thích, DELIMITER) */
function split_sql(string $sql): array
{
    $sql = str_replace("\r\n", "\n", $sql);
    $stmts = [];
    $buf = '';
    $delim = ';';
    $len = strlen($sql);
    $i = 0;
    $quote = null;
    $lineStart = true;
    while ($i < $len) {
        $ch = $sql[$i];
        if ($quote === null) {
            // Lệnh DELIMITER ở đầu dòng
            if ($lineStart && preg_match('/\GDELIMITER\s+(\S+)[ \t]*(\n|$)/Ai', $sql, $m, 0, $i)) {
                $delim = $m[1];
                $i += strlen($m[0]);
                $lineStart = true;
                continue;
            }
            // Chú thích
            if ($ch === '-' && substr($sql, $i, 3) === '-- ' || $ch === '#') {
                $end = strpos($sql, "\n", $i);
                $i = $end === false ? $len : $end + 1;
                $lineStart = true;
                continue;
            }
            if ($ch === '/' && ($sql[$i + 1] ?? '') === '*') {
                $end = strpos($sql, '*/', $i + 2);
                $i = $end === false ? $len : $end + 2;
                continue;
            }
            if ($ch === "'" || $ch === '"' || $ch === '`') {
                $quote = $ch;
            } elseif (substr($sql, $i, strlen($delim)) === $delim) {
                if (trim($buf) !== '') {
                    $stmts[] = trim($buf);
                }
                $buf = '';
                $i += strlen($delim);
                $lineStart = false;
                continue;
            }
        } else {
            if ($ch === '\\' && $quote !== '`') {
                $buf .= $ch . ($sql[$i + 1] ?? '');
                $i += 2;
                continue;
            }
            if ($ch === $quote) {
                if (($sql[$i + 1] ?? '') === $quote) {
                    $buf .= $ch . $ch;
                    $i += 2;
                    continue;
                }
                $quote = null;
            }
        }
        $buf .= $ch;
        $lineStart = ($ch === "\n") || ($lineStart && ($ch === ' ' || $ch === "\t"));
        $i++;
    }
    if (trim($buf) !== '') {
        $stmts[] = trim($buf);
    }
    return $stmts;
}

function write_config(string $file, array $db, string $appKey): bool
{
    $src = file_get_contents($file);
    $map = ['host' => $db['host'], 'name' => $db['name'], 'user' => $db['user'], 'pass' => $db['pass']];
    foreach ($map as $k => $v) {
        $src = preg_replace("/('" . $k . "'\s*=>\s*)'[^']*'/", '${1}' . var_export($v, true), $src, 1);
    }
    $src = preg_replace("/('port'\s*=>\s*)\d+/", '${1}' . (int) $db['port'], $src, 1);
    $src = preg_replace("/('app_key'\s*=>\s*)'[^']*'/", '${1}' . var_export($appKey, true), $src, 1);
    return is_writable($file) && file_put_contents($file, $src) !== false;
}

$checks = [
    'PHP >= 8.0' => version_compare(PHP_VERSION, '8.0.0', '>='),
    'PDO MySQL' => extension_loaded('pdo_mysql'),
    'OpenSSL (AES-256-GCM)' => extension_loaded('openssl') && in_array('aes-256-gcm', openssl_get_cipher_methods(), true),
    'mbstring' => extension_loaded('mbstring'),
    'ZipArchive (khuyến nghị — tìm kiếm nội dung DOCX/XLSX)' => class_exists('ZipArchive'),
    'Ghi được config/config.php' => is_writable($configFile),
    'Ghi được thư mục storage/documents' => is_writable(STORAGE_PATH . '/documents'),
];
$required = ['PHP >= 8.0', 'PDO MySQL', 'OpenSSL (AES-256-GCM)', 'mbstring', 'Ghi được thư mục storage/documents'];
$ready = !in_array(false, array_intersect_key($checks, array_flip($required)), true);

$errors = [];
$log = [];
$done = false;
$manualConfig = null;
$form = [
    'host' => $_POST['host'] ?? '127.0.0.1', 'port' => $_POST['port'] ?? '3306',
    'name' => $_POST['name'] ?? 'qldauthau', 'user' => $_POST['user'] ?? 'root', 'pass' => $_POST['pass'] ?? '',
];

if (is_file($lockFile)) {
    $errors[] = 'Hệ thống đã được cài đặt. Để cài lại, hãy xóa tệp config/installed.lock (toàn bộ dữ liệu sẽ bị ghi đè).';
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && $ready) {
    @set_time_limit(300);
    try {
        if (!preg_match('/^[A-Za-z0-9_]+$/', $form['name'])) {
            throw new RuntimeException('Tên CSDL chỉ gồm chữ, số và dấu gạch dưới.');
        }
        $pdo = new PDO(sprintf('mysql:host=%s;port=%d;charset=utf8mb4', $form['host'], (int) $form['port']), $form['user'], $form['pass'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$form['name']}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $pdo->exec("USE `{$form['name']}`");
        $pdo->exec("SET time_zone = '" . date('P') . "'");
        $log[] = 'Đã kết nối MySQL ' . $pdo->getAttribute(PDO::ATTR_SERVER_VERSION) . ' và tạo CSDL ' . $form['name'];

        foreach (['schema.sql' => 'Cấu trúc bảng + trigger bất biến', 'seed.sql' => 'Dữ liệu mẫu'] as $file => $label) {
            $stmts = split_sql(file_get_contents(ROOT_PATH . '/database/' . $file));
            foreach ($stmts as $s) {
                $pdo->exec($s);
            }
            $log[] = "$label: " . count($stmts) . ' câu lệnh';
        }

        // Khóa chủ mới
        $appKey = base64_encode(random_bytes(32));
        $dbCfg = ['host' => $form['host'], 'port' => (int) $form['port'], 'name' => $form['name'], 'user' => $form['user'], 'pass' => $form['pass']];
        if (write_config($configFile, $dbCfg, $appKey)) {
            $log[] = 'Đã ghi cấu hình và sinh khóa chủ (app_key) ngẫu nhiên vào config/config.php';
        } else {
            $manualConfig = "'db' => ['host' => " . var_export($form['host'], true) . ", 'port' => " . (int) $form['port'] . ", 'name' => " . var_export($form['name'], true)
                . ", 'user' => " . var_export($form['user'], true) . ", 'pass' => " . var_export($form['pass'], true) . ", 'charset' => 'utf8mb4'],\n'app_key' => " . var_export($appKey, true) . ',';
            $log[] = 'KHÔNG ghi được config/config.php — hãy cập nhật thủ công theo hướng dẫn bên dưới';
        }
        $GLOBALS['CONFIG']['db'] = $dbCfg + ['charset' => 'utf8mb4'];
        $GLOBALS['CONFIG']['app_key'] = $appKey;

        // Khóa mã hóa tài liệu đầu tiên + nhật ký
        $keyId = Crypto::createSystemKey('Khóa hệ thống mặc định', true);
        Audit::log('install', 'system', null, 'Cài đặt hệ thống, PHP ' . PHP_VERSION, 'installer');
        Audit::log('key_create', 'system', $keyId, 'Khóa hệ thống mặc định', 'installer');
        $log[] = 'Đã tạo khóa mã hóa tài liệu AES-256 (bọc bởi khóa chủ)';

        // Sinh nội dung HSMT từ mẫu cho dữ liệu mẫu
        foreach (rows("SELECT id, template_id FROM tenders WHERE (content IS NULL OR content = '') AND template_id IS NOT NULL") as $row) {
            $tplContent = (string) val('SELECT content FROM tender_templates WHERE id = ?', [$row['template_id']]);
            update_row('tenders', ['content' => TemplatesController::render($tplContent, Access::findTender((int) $row['id']))], 'id = ?', [$row['id']]);
        }
        $log[] = 'Đã sinh nội dung HSMT tự động từ mẫu cho các gói thầu mẫu';

        // Tài liệu mẫu (mã hóa)
        if (!empty($_POST['demo_docs'])) {
            $n = 0;
            foreach (rows("SELECT t.*, p.name AS project_name, p.code AS project_code, p.department_id FROM tenders t JOIN projects p ON p.id = t.project_id WHERE t.status IN ('published','awarded')") as $t) {
                DocumentService::createFromContent([
                    'title' => 'HSMT ' . $t['code'] . ' (bản công bố)', 'category' => 'tender_doc', 'entity_type' => 'tender', 'entity_id' => $t['id'],
                    'project_id' => $t['project_id'], 'department_id' => $t['department_id'], 'company_id' => 1, 'confidentiality' => 'public', 'tags' => 'HSMT, công bố',
                ], 'HSMT_' . $t['code'] . '.txt', "HỒ SƠ MỜI THẦU\n{$t['code']} - {$t['title']}\n\n" . $t['content'], 'text/plain', 'Bản công bố');
                DocumentService::createFromContent([
                    'title' => 'Bản vẽ / yêu cầu kỹ thuật ' . $t['code'], 'category' => 'drawing', 'entity_type' => 'tender', 'entity_id' => $t['id'],
                    'project_id' => $t['project_id'], 'department_id' => $t['department_id'], 'company_id' => 1, 'confidentiality' => 'public', 'tags' => 'kỹ thuật',
                ], 'YeuCauKyThuat_' . $t['code'] . '.csv', "Hạng mục,ĐVT,Số lượng,Yêu cầu kỹ thuật\n" . implode("\n", array_map(
                    fn($i) => '"' . str_replace('"', '""', $i['name']) . '",' . $i['unit'] . ',' . (float) $i['quantity'] . ',"' . str_replace('"', '""', (string) $i['specs']) . '"',
                    rows('SELECT * FROM tender_items WHERE tender_id = ?', [$t['id']])
                )), 'text/csv');
                $n += 2;
            }
            foreach (rows('SELECT b.id, b.code, b.technical_proposal, s.name FROM bids b JOIN suppliers s ON s.id = b.supplier_id') as $b) {
                DocumentService::createFromContent([
                    'title' => 'Thuyết minh đề xuất kỹ thuật - ' . $b['code'], 'category' => 'proposal', 'entity_type' => 'bid', 'entity_id' => $b['id'],
                    'department_id' => null, 'company_id' => null, 'confidentiality' => 'internal',
                ], 'DeXuatKyThuat_' . $b['code'] . '.txt', "ĐỀ XUẤT KỸ THUẬT\nNhà thầu: {$b['name']}\nHSDT: {$b['code']}\n\n" . $b['technical_proposal'] . "\n\nGiải pháp triển khai, tiến độ, nhân sự, bảo hành chi tiết theo phụ lục.", 'text/plain');
                $n++;
            }
            foreach (rows('SELECT id, name FROM suppliers') as $s) {
                DocumentService::createFromContent([
                    'title' => 'Giấy chứng nhận đăng ký doanh nghiệp', 'category' => 'legal', 'entity_type' => 'supplier', 'entity_id' => $s['id'],
                    'department_id' => null, 'company_id' => null, 'confidentiality' => 'internal',
                ], 'GCN_DKKD.txt', "GIẤY CHỨNG NHẬN ĐĂNG KÝ DOANH NGHIỆP (bản mẫu)\n" . $s['name'], 'text/plain');
                $n++;
            }
            foreach (rows('SELECT c.*, s.name AS supplier_name FROM contracts c JOIN suppliers s ON s.id = c.supplier_id') as $c) {
                DocumentService::createFromContent([
                    'title' => 'Hợp đồng ' . $c['code'] . ' (bản ký)', 'category' => 'contract', 'entity_type' => 'contract', 'entity_id' => $c['id'],
                    'project_id' => $c['project_id'], 'department_id' => 2, 'company_id' => 1, 'confidentiality' => 'internal', 'tags' => 'hợp đồng',
                ], 'HopDong_' . $c['code'] . '.txt', "HỢP ĐỒNG {$c['code']}\n{$c['title']}\nBên B: {$c['supplier_name']}\nGiá trị: " . money($c['value']) . "\n\n" . $c['payment_terms'], 'text/plain');
                $n++;
            }
            DocumentService::createFromContent([
                'title' => 'Quy chế đấu thầu nội bộ', 'category' => 'legal', 'entity_type' => 'general', 'department_id' => 2, 'company_id' => 1,
                'confidentiality' => 'confidential', 'tags' => 'quy chế, mật',
            ], 'QuyCheDauThau.txt', "QUY CHẾ ĐẤU THẦU NỘI BỘ (TÀI LIỆU MẬT)\nChỉ những người được cấp quyền riêng mới xem được.", 'text/plain');
            $n++;
            $log[] = "Đã tạo $n tài liệu mẫu (lưu trữ mã hóa AES-256-GCM)";
        }

        file_put_contents($lockFile, 'Installed at ' . date('c'));
        $done = true;
    } catch (Throwable $e) {
        $errors[] = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Cài đặt hệ thống Quản lý đấu thầu & mua sắm</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-5" style="max-width:820px">
    <h2 class="mb-1"><i class="bi bi-gear-wide-connected text-primary"></i> Cài đặt hệ thống</h2>
    <p class="text-muted">Phần mềm Quản lý đấu thầu, mua sắm — PHP + MySQL</p>

    <?php foreach ($errors as $err): ?><div class="alert alert-danger"><?= e($err) ?></div><?php endforeach; ?>

    <?php if ($done): ?>
        <div class="card card-body mb-3">
            <h4 class="text-success"><i class="bi bi-check-circle-fill"></i> Cài đặt thành công</h4>
            <ul class="mb-3"><?php foreach ($log as $l): ?><li><?= e($l) ?></li><?php endforeach; ?></ul>
            <?php if ($manualConfig): ?>
                <div class="alert alert-warning">Mở <code>config/config.php</code> và thay các dòng tương ứng bằng:<pre class="mb-0 mt-2"><?= e($manualConfig) ?></pre></div>
            <?php endif; ?>
            <div class="alert alert-info mb-3"><strong>Quan trọng:</strong> sao lưu <code>app_key</code> trong <code>config/config.php</code>. Mất khóa này sẽ không giải mã được tài liệu.</div>
            <h6>Tài khoản demo (mật khẩu: <code>123456</code>)</h6>
            <table class="table table-sm">
                <tr><td><code>admin</code></td><td>Quản trị hệ thống</td></tr>
                <tr><td><code>giamdoc</code></td><td>Lãnh đạo — phê duyệt</td></tr>
                <tr><td><code>dauthau</code></td><td>Cán bộ đấu thầu</td></tr>
                <tr><td><code>ketoan</code></td><td>Kế toán — thanh toán, giải ngân</td></tr>
                <tr><td><code>kiemsoat</code></td><td>Kiểm soát nội bộ — nhật ký kiểm toán</td></tr>
                <tr><td><code>kythuat</code></td><td>Cán bộ kỹ thuật (tổ chuyên gia)</td></tr>
                <tr><td><code>chuyengia</code></td><td>Chuyên viên tài chính – thương mại (tổ chuyên gia)</td></tr>
                <tr><td><code>ncc1</code>, <code>ncc2</code>, <code>ncc3</code></td><td>Nhà cung cấp — cổng NCC</td></tr>
            </table>
            <a class="btn btn-primary" href="index.php">Vào hệ thống →</a>
        </div>
    <?php elseif (!is_file($lockFile)): ?>
        <div class="card mb-3"><div class="card-header">1. Kiểm tra môi trường</div>
            <table class="table mb-0">
                <?php foreach ($checks as $name => $ok): ?>
                    <tr><td><?= e($name) ?></td><td class="text-end"><?= $ok ? '<span class="text-success"><i class="bi bi-check-circle-fill"></i> Đạt</span>' : (in_array($name, $required, true) ? '<span class="text-danger"><i class="bi bi-x-circle-fill"></i> Thiếu</span>' : '<span class="text-warning"><i class="bi bi-exclamation-triangle-fill"></i> Khuyến nghị</span>') ?></td></tr>
                <?php endforeach; ?>
            </table>
        </div>
        <form method="post" class="card">
            <div class="card-header">2. Thông tin CSDL MySQL</div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-8"><label class="form-label">Máy chủ</label><input class="form-control" name="host" value="<?= e($form['host']) ?>"></div>
                    <div class="col-md-4"><label class="form-label">Cổng</label><input class="form-control" name="port" value="<?= e($form['port']) ?>"></div>
                    <div class="col-md-4"><label class="form-label">Tên CSDL</label><input class="form-control" name="name" value="<?= e($form['name']) ?>"></div>
                    <div class="col-md-4"><label class="form-label">Tài khoản</label><input class="form-control" name="user" value="<?= e($form['user']) ?>"></div>
                    <div class="col-md-4"><label class="form-label">Mật khẩu</label><input class="form-control" type="password" name="pass" value="<?= e($form['pass']) ?>"></div>
                    <div class="col-12"><div class="form-check"><input class="form-check-input" type="checkbox" name="demo_docs" value="1" id="demo" checked><label class="form-check-label" for="demo">Tạo tài liệu mẫu (đã mã hóa) để demo kho tài liệu</label></div></div>
                </div>
                <div class="alert alert-warning mt-3 mb-0 small">Nếu CSDL đã tồn tại, các bảng của hệ thống sẽ bị <strong>xóa và tạo lại</strong>.</div>
            </div>
            <div class="card-footer"><button class="btn btn-primary" <?= $ready ? '' : 'disabled' ?>><i class="bi bi-play-fill"></i> Cài đặt</button></div>
        </form>
    <?php else: ?>
        <a class="btn btn-primary" href="index.php">Vào hệ thống →</a>
    <?php endif; ?>
</div>
</body>
</html>
