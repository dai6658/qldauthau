<?php
/**
 * Cài đặt hệ thống: cấu hình chung, quy trình phê duyệt tùy biến, quản lý khóa mã hóa (BYOK)
 */
class SettingsController extends Controller
{
    const GENERAL = [
        'org_display_name'            => ['Tên tổ chức (bên mời thầu)', 'text'],
        'reminder_days'               => ['Nhắc trước hạn (ngày) — đóng thầu, công việc, thanh toán', 'number'],
        'approval_reminder_days'      => ['Nhắc phê duyệt tồn đọng sau (ngày)', 'number'],
        'default_tech_weight'         => ['Trọng số kỹ thuật mặc định (%)', 'number'],
        'default_pass_score'          => ['Điểm kỹ thuật tối thiểu mặc định', 'number'],
        'min_evaluators'              => ['Số chuyên gia chấm điểm tối thiểu cho mỗi gói thầu', 'number'],
        'allow_supplier_register'     => ['Cho phép nhà cung cấp tự đăng ký', 'bool'],
        'index_document_content'      => ['Trích xuất nội dung tài liệu để tìm kiếm toàn văn', 'bool'],
        'require_invoice_for_payment' => ['Bắt buộc hóa đơn hợp lệ khi đề nghị thanh toán', 'bool'],
    ];

    public function index(): void
    {
        require_perm('settings.manage');
        $values = [];
        foreach (array_keys(self::GENERAL) as $k) {
            $values[$k] = setting($k, '');
        }
        $steps = [];
        foreach (rows('SELECT * FROM workflow_steps ORDER BY entity_type, step_no') as $s) {
            $steps[$s['entity_type']][] = $s;
        }
        $keys = rows('SELECT k.*, u.full_name, (SELECT COUNT(*) FROM document_versions v WHERE v.key_id = k.id) AS version_count
            FROM encryption_keys k LEFT JOIN users u ON u.id = k.created_by ORDER BY k.id DESC');
        $system = [
            'PHP' => PHP_VERSION,
            'MySQL' => (string) val('SELECT VERSION()'),
            'OpenSSL' => OPENSSL_VERSION_TEXT,
            'ZipArchive (trích xuất DOCX/XLSX)' => class_exists('ZipArchive') ? 'Có' : 'Không',
            'HTTPS' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'Đang dùng HTTPS' : 'Chưa bật (khuyến nghị bật khi triển khai)',
            'Thư mục lưu trữ ghi được' => is_writable(STORAGE_PATH . '/documents') ? 'Có' : 'KHÔNG',
            'Dung lượng kho tài liệu (mã hóa)' => file_size((float) val('SELECT COALESCE(SUM(size_bytes),0) FROM document_versions')),
            'Lần chạy nhắc việc gần nhất' => fdatetime(setting('last_cron_run')),
        ];
        view('settings/index', [
            'title' => 'Cài đặt hệ thống', 'values' => $values, 'fields' => self::GENERAL, 'steps' => $steps,
            'entities' => Workflow::ENTITIES, 'roles' => pairs("SELECT id, name FROM roles WHERE code <> 'supplier' ORDER BY id"),
            'keys' => $keys, 'system' => $system, 'newKey' => $_SESSION['generated_key'] ?? null,
        ]);
        unset($_SESSION['generated_key']);
    }

    public function saveGeneral(): void
    {
        require_perm('settings.manage');
        $changes = [];
        foreach (self::GENERAL as $k => [$label, $type]) {
            $v = $type === 'bool' ? (input($k) ? '1' : '0') : (string) input($k, '');
            if ($type === 'number') {
                $v = (string) max(0, min(100, (int) $v));
            }
            if ((string) setting($k, '') !== $v) {
                $changes[$k] = [setting($k), $v];
                set_setting($k, $v);
            }
        }
        if ($changes) {
            Audit::log('settings', 'system', null, $changes);
        }
        flash('success', 'Đã lưu cấu hình.');
        redirect('settings/index');
    }

    /** Cấu hình quy trình phê duyệt nhiều bước cho từng loại hồ sơ */
    public function saveWorkflow(): void
    {
        require_perm('settings.manage');
        $type = input('entity_type');
        if (!isset(Workflow::ENTITIES[$type])) {
            abort(400, 'Loại hồ sơ không hợp lệ');
        }
        $names = (array) ($_POST['step_name'] ?? []);
        $roles = (array) ($_POST['step_role'] ?? []);
        $steps = [];
        foreach ($names as $i => $name) {
            $name = trim((string) $name);
            $role = (int) ($roles[$i] ?? 0);
            if ($name !== '' && $role > 0) {
                $steps[] = [$name, $role];
            }
        }
        $pending = (int) val("SELECT COUNT(*) FROM approvals WHERE entity_type = ? AND status IN ('pending','waiting')", [$type]);
        transaction(function () use ($type, $steps) {
            q('DELETE FROM workflow_steps WHERE entity_type = ?', [$type]);
            foreach ($steps as $i => [$name, $role]) {
                insert('workflow_steps', ['entity_type' => $type, 'step_no' => $i + 1, 'name' => $name, 'role_id' => $role]);
            }
        });
        Audit::log('workflow', 'system', null, Workflow::ENTITIES[$type] . ': ' . ($steps ? implode(' → ', array_column($steps, 0)) : 'không cần phê duyệt'));
        flash('success', 'Đã cập nhật quy trình "' . Workflow::ENTITIES[$type] . '".' . ($pending ? " Lưu ý: $pending hồ sơ đang chờ duyệt vẫn theo quy trình cũ." : ''));
        redirect('settings/index', ['tab' => 'workflow']);
    }

    // ------------------------------------------------------------ Khóa mã hóa
    public function createKey(): void
    {
        require_perm('settings.manage');
        $this->attempt(function () {
            $name = input('name') ?: 'Khóa hệ thống ' . date('d/m/Y H:i');
            $id = Crypto::createSystemKey($name, (bool) input('activate'));
            Audit::log('key_create', 'system', $id, $name . (input('activate') ? ' (kích hoạt)' : ''));
        }, 'Đã tạo khóa mã hóa mới.');
        redirect('settings/index', ['tab' => 'keys']);
    }

    /** BYOK: nhập khóa do khách hàng tự sinh và quản lý */
    public function importKey(): void
    {
        require_perm('settings.manage');
        $this->attempt(function () {
            $name = input('name') ?: 'Khóa khách hàng ' . date('d/m/Y H:i');
            $id = Crypto::importCustomerKey($name, (string) ($_POST['material'] ?? ''), (bool) input('activate'));
            $fp = val('SELECT fingerprint FROM encryption_keys WHERE id = ?', [$id]);
            Audit::log('key_import', 'system', $id, "$name — dấu vân tay $fp");
        }, 'Đã nhập khóa của khách hàng. Tài liệu tải lên từ nay sẽ được mã hóa bằng khóa này (nếu đã kích hoạt).');
        redirect('settings/index', ['tab' => 'keys']);
    }

    /** Sinh sẵn khóa ngẫu nhiên để khách hàng lưu giữ rồi nhập vào (minh họa BYOK) */
    public function generateMaterial(): void
    {
        require_perm('settings.manage');
        $_SESSION['generated_key'] = base64_encode(random_bytes(32));
        redirect('settings/index', ['tab' => 'keys']);
    }

    public function activateKey(): void
    {
        require_perm('settings.manage');
        $k = $this->requireId('encryption_keys', 'key_id', 'khóa');
        if ($k['status'] === 'revoked') {
            flash('danger', 'Không thể kích hoạt khóa đã thu hồi.');
        } else {
            transaction(function () use ($k) {
                q("UPDATE encryption_keys SET status = 'inactive' WHERE status = 'active'");
                q("UPDATE encryption_keys SET status = 'active' WHERE id = ?", [$k['id']]);
            });
            Audit::log('key_activate', 'system', $k['id'], $k['name']);
            flash('success', 'Đã kích hoạt khóa "' . $k['name'] . '".');
        }
        redirect('settings/index', ['tab' => 'keys']);
    }

    /** Thu hồi khóa: tài liệu mã hóa bằng khóa này sẽ không thể giải mã (crypto-shredding) */
    public function revokeKey(): void
    {
        require_perm('settings.manage');
        $k = $this->requireId('encryption_keys', 'key_id', 'khóa');
        if ($k['status'] === 'active') {
            flash('danger', 'Hãy kích hoạt khóa khác trước khi thu hồi khóa đang hoạt động.');
        } elseif (input('confirm_name') !== $k['name']) {
            flash('danger', 'Tên khóa xác nhận không khớp.');
        } else {
            q("UPDATE encryption_keys SET status = 'revoked' WHERE id = ?", [$k['id']]);
            $n = (int) val('SELECT COUNT(*) FROM document_versions WHERE key_id = ?', [$k['id']]);
            Audit::log('key_revoke', 'system', $k['id'], $k['name'] . " — $n phiên bản tài liệu không còn giải mã được");
            flash('warning', "Đã thu hồi khóa. $n phiên bản tài liệu mã hóa bằng khóa này không còn truy cập được.");
        }
        redirect('settings/index', ['tab' => 'keys']);
    }

    /** Xoay vòng khóa: mã hóa lại toàn bộ tài liệu bằng khóa đang hoạt động */
    public function rotate(): void
    {
        require_perm('settings.manage');
        @set_time_limit(0);
        $active = Crypto::activeKey();
        $done = 0;
        $failed = 0;
        $list = rows("SELECT v.* FROM document_versions v JOIN encryption_keys k ON k.id = v.key_id WHERE v.key_id <> ? AND k.status <> 'revoked'", [$active['id']]);
        foreach ($list as $v) {
            try {
                $plain = DocumentService::read($v);
                $enc = Crypto::encryptFile($plain);
                $abs = STORAGE_PATH . '/documents/' . $v['storage_path'];
                $tmp = $abs . '.tmp';
                if (file_put_contents($tmp, $enc['cipher']) === false) {
                    throw new RuntimeException('write');
                }
                transaction(function () use ($v, $enc, $tmp, $abs) {
                    update_row('document_versions', ['iv' => $enc['iv'], 'tag' => $enc['tag'], 'key_id' => $enc['key_id']], 'id = ?', [$v['id']]);
                    if (!rename($tmp, $abs)) {
                        throw new RuntimeException('rename');
                    }
                });
                $done++;
            } catch (Throwable $e) {
                $failed++;
            }
        }
        Audit::log('key_activate', 'system', $active['id'], "Xoay vòng khóa: mã hóa lại $done phiên bản, lỗi $failed");
        flash($failed ? 'warning' : 'success', "Đã mã hóa lại $done phiên bản tài liệu bằng khóa \"{$active['name']}\"" . ($failed ? ", $failed lỗi." : '.'));
        redirect('settings/index', ['tab' => 'keys']);
    }

    public function runReminders(): void
    {
        require_perm('settings.manage');
        $r = Notifier::runReminders();
        flash('success', 'Đã chạy nhắc việc: ' . json_encode($r, JSON_UNESCAPED_UNICODE));
        redirect('settings/index', ['tab' => 'system']);
    }
}
