<?php
/**
 * Hàm tiện ích dùng chung: CSDL, request, xác thực, phân quyền, CSRF, view, định dạng.
 */

// ------------------------------------------------------------------ Cấu hình
function config(string $key, $default = null)
{
    $value = $GLOBALS['CONFIG'] ?? [];
    foreach (explode('.', $key) as $part) {
        if (!is_array($value) || !array_key_exists($part, $value)) {
            return $default;
        }
        $value = $value[$part];
    }
    return $value;
}

// ------------------------------------------------------------------ CSDL (PDO)
function db(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        $c = config('db');
        $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=%s', $c['host'], $c['port'], $c['name'], $c['charset']);
        $pdo = new PDO($dsn, $c['user'], $c['pass'], [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
        $pdo->exec("SET time_zone = '" . date('P') . "'");
    }
    return $pdo;
}

function q(string $sql, array $params = []): PDOStatement
{
    $st = db()->prepare($sql);
    $st->execute(array_values($params));
    return $st;
}

function row(string $sql, array $params = []): ?array
{
    $r = q($sql, $params)->fetch();
    return $r === false ? null : $r;
}

function rows(string $sql, array $params = []): array
{
    return q($sql, $params)->fetchAll();
}

function val(string $sql, array $params = [])
{
    $v = q($sql, $params)->fetchColumn();
    return $v === false ? null : $v;
}

/** Thêm bản ghi (tên bảng/cột do code định nghĩa, không lấy từ người dùng) */
function insert(string $table, array $data): int
{
    $cols = array_keys($data);
    $sql = sprintf('INSERT INTO `%s` (`%s`) VALUES (%s)', $table, implode('`,`', $cols), implode(',', array_fill(0, count($cols), '?')));
    q($sql, array_values($data));
    return (int) db()->lastInsertId();
}

function update_row(string $table, array $data, string $where, array $whereParams = []): int
{
    $sets = [];
    foreach (array_keys($data) as $col) {
        $sets[] = "`$col` = ?";
    }
    $sql = sprintf('UPDATE `%s` SET %s WHERE %s', $table, implode(', ', $sets), $where);
    return q($sql, array_merge(array_values($data), $whereParams))->rowCount();
}

function transaction(callable $fn)
{
    $pdo = db();
    $pdo->beginTransaction();
    try {
        $result = $fn();
        $pdo->commit();
        return $result;
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
}

/** Sinh mã chứng từ: PREFIX-YYYY-0001 */
function gen_code(string $prefix, string $table): string
{
    $year = date('Y');
    $like = $prefix . '-' . $year . '-%';
    $last = val("SELECT code FROM `$table` WHERE code LIKE ? ORDER BY code DESC LIMIT 1", [$like]);
    $n = $last ? ((int) substr($last, strrpos($last, '-') + 1)) + 1 : 1;
    return sprintf('%s-%s-%04d', $prefix, $year, $n);
}

// ------------------------------------------------------------------ Cài đặt hệ thống
function setting(string $key, $default = null)
{
    static $cache = null;
    if ($cache === null) {
        $cache = [];
        try {
            foreach (rows('SELECT skey, svalue FROM settings') as $r) {
                $cache[$r['skey']] = $r['svalue'];
            }
        } catch (Throwable $e) {
            $cache = [];
        }
    }
    return array_key_exists($key, $cache) && $cache[$key] !== null ? $cache[$key] : $default;
}

function set_setting(string $key, $value): void
{
    q('INSERT INTO settings (skey, svalue) VALUES (?, ?) ON DUPLICATE KEY UPDATE svalue = VALUES(svalue)', [$key, (string) $value]);
}

// ------------------------------------------------------------------ Request
function is_post(): bool
{
    return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}

function input(string $key, $default = null)
{
    $v = $_POST[$key] ?? $_GET[$key] ?? $default;
    return is_string($v) ? trim($v) : $v;
}

function input_int(string $key, int $default = 0): int
{
    $v = input($key);
    return is_numeric($v) ? (int) $v : $default;
}

/** Số tiền nhập dạng "1.200.000" hoặc "1200000" */
function input_money(string $key): float
{
    $v = (string) input($key, '0');
    $v = str_replace(['.', ' ', 'đ', ','], ['', '', '', '.'], $v);
    return is_numeric($v) ? (float) $v : 0.0;
}

function input_date(string $key): ?string
{
    $v = (string) input($key, '');
    if ($v === '') {
        return null;
    }
    $t = strtotime($v);
    return $t ? date('Y-m-d', $t) : null;
}

function input_datetime(string $key): ?string
{
    $v = (string) input($key, '');
    if ($v === '') {
        return null;
    }
    $t = strtotime($v);
    return $t ? date('Y-m-d H:i:s', $t) : null;
}

function client_ip(): string
{
    return substr($_SERVER['REMOTE_ADDR'] ?? 'cli', 0, 45);
}

// ------------------------------------------------------------------ URL & điều hướng
function url(string $route, array $params = []): string
{
    $q = array_merge(['r' => $route], $params);
    return 'index.php?' . http_build_query($q);
}

function asset(string $path): string
{
    return 'assets/' . ltrim($path, '/');
}

function redirect(string $route, array $params = []): void
{
    header('Location: ' . url($route, $params));
    exit;
}

function redirect_back(string $fallback = 'dashboard/index'): void
{
    $ref = $_SERVER['HTTP_REFERER'] ?? '';
    $host = $_SERVER['HTTP_HOST'] ?? '';
    if ($ref !== '' && parse_url($ref, PHP_URL_HOST) === $host) {
        header('Location: ' . $ref);
        exit;
    }
    redirect($fallback);
}

function abort(int $code = 403, string $message = 'Bạn không có quyền truy cập chức năng này.'): void
{
    http_response_code($code);
    render_error($code, $message);
    exit;
}

function render_error(int $code, string $message): void
{
    try {
        $layout = auth() ? 'layouts/main' : 'layouts/guest';
    } catch (Throwable $e) {
        $layout = 'layouts/guest';
    }
    view('errors/error', ['code' => $code, 'message' => $message, 'title' => 'Lỗi ' . $code], $layout);
}

function json_response($data, int $code = 200): void
{
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

// ------------------------------------------------------------------ Flash message
function flash(string $type, string $message): void
{
    $_SESSION['_flash'][] = ['type' => $type, 'message' => $message];
}

function get_flashes(): array
{
    $f = $_SESSION['_flash'] ?? [];
    unset($_SESSION['_flash']);
    return $f;
}

function old(string $key, $default = '')
{
    return $_POST[$key] ?? $default;
}

// ------------------------------------------------------------------ CSRF
function csrf_token(): string
{
    if (empty($_SESSION['_csrf'])) {
        $_SESSION['_csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['_csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . csrf_token() . '">';
}

function verify_csrf(): void
{
    $token = $_POST['_csrf'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    if (!is_string($token) || !hash_equals(csrf_token(), $token)) {
        abort(419, 'Phiên làm việc đã hết hạn hoặc yêu cầu không hợp lệ (CSRF). Vui lòng tải lại trang.');
    }
}

// ------------------------------------------------------------------ Xác thực & phân quyền (RBAC)
function auth(): ?array
{
    static $user = false;
    if ($user === false) {
        $user = null;
        if (!empty($_SESSION['uid'])) {
            $user = row('SELECT u.*, r.code AS role_code, r.name AS role_name, d.name AS department_name
                         FROM users u JOIN roles r ON r.id = u.role_id
                         LEFT JOIN departments d ON d.id = u.department_id
                         WHERE u.id = ? AND u.is_active = 1', [$_SESSION['uid']]);
        }
    }
    return $user;
}

function user_id(): ?int
{
    $u = auth();
    return $u ? (int) $u['id'] : null;
}

function is_role(string ...$codes): bool
{
    $u = auth();
    return $u && in_array($u['role_code'], $codes, true);
}

function is_admin(): bool
{
    return is_role('admin');
}

function is_supplier_user(): bool
{
    return is_role('supplier');
}

function permissions(): array
{
    static $perms = null;
    if ($perms === null) {
        $u = auth();
        $perms = $u ? array_column(rows('SELECT p.code FROM role_permissions rp JOIN permissions p ON p.id = rp.permission_id WHERE rp.role_id = ?', [$u['role_id']]), 'code') : [];
    }
    return $perms;
}

function can(string $perm): bool
{
    if (!auth()) {
        return false;
    }
    if (is_admin() && $perm !== 'portal.access') {
        return true;
    }
    return in_array($perm, permissions(), true);
}

function can_any(string ...$perms): bool
{
    foreach ($perms as $p) {
        if (can($p)) {
            return true;
        }
    }
    return false;
}

function require_perm(string ...$perms): void
{
    if (!can_any(...$perms)) {
        abort(403);
    }
}

// ------------------------------------------------------------------ View
function view(string $name, array $data = [], string $layout = 'layouts/main'): void
{
    $viewFile = APP_PATH . '/views/' . $name . '.php';
    if (!is_file($viewFile)) {
        throw new RuntimeException('View không tồn tại: ' . $name);
    }
    extract($data, EXTR_SKIP);
    ob_start();
    include $viewFile;
    $content = ob_get_clean();
    if ($layout === '') {
        echo $content;
        return;
    }
    include APP_PATH . '/views/' . $layout . '.php';
}

function partial(string $name, array $data = []): void
{
    extract($data, EXTR_SKIP);
    include APP_PATH . '/views/' . $name . '.php';
}

// ------------------------------------------------------------------ Định dạng hiển thị
function e($s): string
{
    return htmlspecialchars((string) ($s ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function money($n, bool $suffix = true): string
{
    if ($n === null || $n === '') {
        return '—';
    }
    return number_format((float) $n, 0, ',', '.') . ($suffix ? ' đ' : '');
}

/** Giá trị điền sẵn cho ô nhập tiền (định dạng 1.575.000.000, tránh hiểu nhầm phần thập phân của DECIMAL) */
function money_input($n): string
{
    if ($n === null || $n === '') {
        return '';
    }
    return number_format(round((float) $n), 0, ',', '.');
}
function num($n, int $dec = 2): string
{
    if ($n === null || $n === '') {
        return '—';
    }
    return number_format((float) $n, $dec, ',', '.');
}

function fdate($d): string
{
    return $d ? date('d/m/Y', strtotime($d)) : '—';
}

function fdatetime($d): string
{
    return $d ? date('d/m/Y H:i', strtotime($d)) : '—';
}

function dt_local($d): string
{
    return $d ? date('Y-m-d\TH:i', strtotime($d)) : '';
}

function file_size($bytes): string
{
    $bytes = (float) $bytes;
    $units = ['B', 'KB', 'MB', 'GB'];
    $i = 0;
    while ($bytes >= 1024 && $i < count($units) - 1) {
        $bytes /= 1024;
        $i++;
    }
    return round($bytes, 1) . ' ' . $units[$i];
}

function time_ago($d): string
{
    if (!$d) {
        return '—';
    }
    $diff = time() - strtotime((string) $d);
    if ($diff < 60) {
        return 'vừa xong';
    }
    if ($diff < 3600) {
        return floor($diff / 60) . ' phút trước';
    }
    if ($diff < 86400) {
        return floor($diff / 3600) . ' giờ trước';
    }
    return floor($diff / 86400) . ' ngày trước';
}

function days_left($d): ?int
{
    if (!$d) {
        return null;
    }
    return (int) floor((strtotime(date('Y-m-d', strtotime($d))) - strtotime(date('Y-m-d'))) / 86400);
}

/** Nhãn tiếng Việt cho các giá trị enum */
function labels(string $group): array
{
    static $L = [
        'tender_status' => [
            'draft' => ['Nháp', 'secondary'], 'pending_approval' => ['Chờ phê duyệt', 'warning'],
            'approved' => ['Đã phê duyệt', 'info'], 'published' => ['Đang mời thầu', 'primary'],
            'closed' => ['Đã đóng thầu', 'dark'], 'evaluating' => ['Đang đánh giá', 'warning'],
            'awarded' => ['Đã trao thầu', 'success'], 'cancelled' => ['Đã hủy', 'danger'],
        ],
        'bid_status' => [
            'draft' => ['Nháp', 'secondary'], 'submitted' => ['Đã nộp', 'primary'],
            'valid' => ['Hợp lệ', 'info'], 'invalid' => ['Không hợp lệ', 'danger'],
            'awarded' => ['Trúng thầu', 'success'], 'rejected' => ['Không trúng thầu', 'dark'],
            'withdrawn' => ['Đã rút', 'secondary'],
        ],
        'supplier_status' => [
            'pending' => ['Chờ duyệt', 'warning'], 'approved' => ['Đã duyệt', 'success'],
            'suspended' => ['Tạm ngưng', 'secondary'], 'blacklisted' => ['Danh sách đen', 'danger'],
        ],
        'project_status' => [
            'planning' => ['Chuẩn bị', 'secondary'], 'active' => ['Đang thực hiện', 'primary'],
            'completed' => ['Hoàn thành', 'success'], 'suspended' => ['Tạm dừng', 'danger'],
        ],
        'category' => [
            'goods' => ['Mua sắm hàng hóa', 'info'], 'construction' => ['Xây lắp', 'warning'],
            'consulting' => ['Tư vấn', 'primary'], 'non_consulting' => ['Phi tư vấn', 'secondary'],
        ],
        'method' => [
            'open' => ['Đấu thầu rộng rãi', 'primary'], 'limited' => ['Đấu thầu hạn chế', 'info'],
            'direct' => ['Chỉ định thầu', 'warning'], 'competitive' => ['Chào hàng cạnh tranh', 'secondary'],
        ],
        'eval_method' => [
            'qcs' => ['Kết hợp kỹ thuật - giá (QCS)', 'primary'],
            'lowest_price' => ['Giá thấp nhất', 'info'],
            'quality' => ['Dựa trên kỹ thuật (QBS)', 'secondary'],
        ],
        'plan_status' => [
            'draft' => ['Nháp', 'secondary'], 'pending_approval' => ['Chờ phê duyệt', 'warning'],
            'approved' => ['Đã phê duyệt', 'info'], 'in_progress' => ['Đang triển khai', 'primary'],
            'completed' => ['Hoàn thành', 'success'],
        ],
        'task_status' => [
            'todo' => ['Chưa bắt đầu', 'secondary'], 'in_progress' => ['Đang làm', 'primary'],
            'done' => ['Hoàn thành', 'success'], 'blocked' => ['Vướng mắc', 'danger'],
        ],
        'contract_status' => [
            'draft' => ['Nháp', 'secondary'], 'pending_approval' => ['Chờ phê duyệt', 'warning'],
            'active' => ['Đang thực hiện', 'primary'], 'completed' => ['Đã quyết toán', 'success'],
            'terminated' => ['Chấm dứt', 'danger'],
        ],
        'payment_status' => [
            'planned' => ['Kế hoạch', 'secondary'], 'pending_approval' => ['Chờ duyệt chi', 'warning'],
            'approved' => ['Đã duyệt chi', 'info'], 'paid' => ['Đã giải ngân', 'success'],
            'rejected' => ['Bị từ chối', 'danger'],
        ],
        'po_status' => [
            'sent' => ['Đã gửi NCC', 'warning'], 'confirmed' => ['NCC đã xác nhận', 'info'],
            'delivered' => ['Đã giao hàng', 'success'], 'cancelled' => ['Đã hủy', 'danger'],
        ],
        'invoice_status' => [
            'submitted' => ['Chờ kiểm tra', 'warning'], 'verified' => ['Hợp lệ', 'info'],
            'rejected' => ['Bị trả lại', 'danger'], 'paid' => ['Đã thanh toán', 'success'],
        ],
        'acceptance_result' => [
            'passed' => ['Đạt', 'success'], 'conditional' => ['Đạt có điều kiện', 'warning'], 'failed' => ['Không đạt', 'danger'],
        ],
        'approval_status' => [
            'waiting' => ['Chờ bước trước', 'light'], 'pending' => ['Chờ xử lý', 'warning'],
            'approved' => ['Đã duyệt', 'success'], 'rejected' => ['Từ chối', 'danger'], 'cancelled' => ['Đã hủy', 'secondary'],
        ],
        'confidentiality' => [
            'public' => ['Công khai (NCC xem được)', 'success'], 'internal' => ['Nội bộ', 'info'], 'confidential' => ['Mật', 'danger'],
        ],
        'doc_category' => [
            'tender_doc' => ['Hồ sơ mời thầu', 'primary'], 'notice' => ['Thông báo mời thầu', 'info'],
            'bid_doc' => ['Hồ sơ dự thầu', 'primary'], 'design' => ['Hồ sơ thiết kế', 'secondary'],
            'proposal' => ['Giải pháp đề xuất', 'secondary'], 'drawing' => ['Bản vẽ kỹ thuật', 'secondary'],
            'contract' => ['Hợp đồng', 'success'], 'acceptance' => ['Biên bản nghiệm thu', 'success'],
            'decision' => ['Quyết định phê duyệt', 'warning'], 'legal' => ['Hồ sơ pháp lý', 'dark'],
            'invoice' => ['Hóa đơn', 'info'], 'capability' => ['Hồ sơ năng lực', 'info'], 'other' => ['Khác', 'light'],
        ],
        'cap_type' => [
            'experience' => ['Kinh nghiệm / HĐ tương tự', 'primary'], 'finance' => ['Năng lực tài chính', 'success'],
            'personnel' => ['Nhân sự', 'info'], 'equipment' => ['Thiết bị', 'secondary'], 'certificate' => ['Chứng chỉ', 'warning'],
        ],
        'invest_category' => [
            'construction' => ['Chi phí xây dựng', 'primary'], 'equipment' => ['Chi phí thiết bị', 'info'],
            'land' => ['Bồi thường, GPMB', 'secondary'], 'management' => ['Quản lý dự án', 'dark'],
            'consulting' => ['Tư vấn đầu tư xây dựng', 'warning'], 'other' => ['Chi phí khác', 'light'],
            'contingency' => ['Chi phí dự phòng', 'danger'],
        ],
        'member_access' => [
            'view' => ['Chỉ xem', 'secondary'], 'edit' => ['Biên soạn', 'primary'], 'evaluate' => ['Tổ chuyên gia (chấm điểm)', 'warning'],
        ],
        'entity' => [
            'tender' => ['HSMT', 'primary'], 'bid' => ['HSDT', 'info'], 'contract' => ['Hợp đồng', 'success'],
            'supplier' => ['Nhà cung cấp', 'secondary'], 'project' => ['Dự án', 'dark'], 'payment' => ['Thanh toán', 'warning'],
            'plan' => ['Kế hoạch', 'secondary'], 'invoice' => ['Hóa đơn', 'info'], 'general' => ['Chung', 'light'],
        ],
    ];
    return $L[$group] ?? [];
}

function label(string $group, $value): string
{
    $l = labels($group);
    return $l[$value][0] ?? (string) $value;
}

function badge(string $group, $value): string
{
    $l = labels($group);
    [$text, $color] = $l[$value] ?? [(string) $value, 'secondary'];
    $textClass = in_array($color, ['warning', 'light', 'info'], true) ? ' text-dark' : '';
    return '<span class="badge bg-' . $color . $textClass . '">' . e($text) . '</span>';
}

function select_options(array $options, $selected = null, ?string $placeholder = null): string
{
    $html = $placeholder !== null ? '<option value="">' . e($placeholder) . '</option>' : '';
    foreach ($options as $value => $text) {
        if (is_array($text)) {
            $text = $text[0];
        }
        $sel = ((string) $value === (string) $selected) ? ' selected' : '';
        $html .= '<option value="' . e($value) . '"' . $sel . '>' . e($text) . '</option>';
    }
    return $html;
}

function pairs(string $sql, array $params = []): array
{
    $out = [];
    foreach (q($sql, $params)->fetchAll(PDO::FETCH_NUM) as $r) {
        $out[$r[0]] = $r[1];
    }
    return $out;
}

// ------------------------------------------------------------------ Phân trang
function paginate(string $countSql, string $dataSql, array $params = [], int $perPage = 15): array
{
    $page = max(1, input_int('page', 1));
    $total = (int) val($countSql, $params);
    $pages = max(1, (int) ceil($total / $perPage));
    $page = min($page, $pages);
    $offset = ($page - 1) * $perPage;
    $items = rows($dataSql . " LIMIT $perPage OFFSET $offset", $params);
    return ['items' => $items, 'page' => $page, 'pages' => $pages, 'total' => $total];
}

function pager(array $p): string
{
    if ($p['pages'] <= 1) {
        return '<div class="small text-muted">Tổng: ' . $p['total'] . ' bản ghi</div>';
    }
    $qs = $_GET;
    $html = '<nav class="d-flex justify-content-between align-items-center"><div class="small text-muted">Tổng: ' . $p['total'] . ' bản ghi</div><ul class="pagination pagination-sm mb-0">';
    for ($i = 1; $i <= $p['pages']; $i++) {
        $qs['page'] = $i;
        $active = $i === $p['page'] ? ' active' : '';
        $html .= '<li class="page-item' . $active . '"><a class="page-link" href="index.php?' . e(http_build_query($qs)) . '">' . $i . '</a></li>';
    }
    return $html . '</ul></nav>';
}

// ------------------------------------------------------------------ Xuất CSV (Excel đọc được tiếng Việt)
function csv_download(string $filename, array $headers, array $data): void
{
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF");
    fputcsv($out, $headers);
    foreach ($data as $r) {
        fputcsv($out, array_values($r));
    }
    fclose($out);
    exit;
}
