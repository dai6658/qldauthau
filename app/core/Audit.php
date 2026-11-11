<?php
/**
 * Nhật ký kiểm toán bất biến:
 *  - Mỗi bản ghi chứa prev_hash + hash = HMAC-SHA256(khóa chủ, nội dung) tạo thành chuỗi băm.
 *  - Trigger CSDL chặn UPDATE/DELETE bảng audit_logs.
 *  - Chức năng verify() phát hiện mọi chỉnh sửa/xóa trái phép.
 */
class Audit
{
    public static function log(string $action, ?string $entityType = null, $entityId = null, $details = null, ?string $username = null): void
    {
        try {
            $u = auth();
            if (is_array($details)) {
                $details = json_encode($details, JSON_UNESCAPED_UNICODE);
            }
            q("SELECT GET_LOCK('audit_chain', 5)");
            $prev = (string) (val('SELECT hash FROM audit_logs ORDER BY id DESC LIMIT 1') ?? '');
            $data = [
                'user_id'     => $u['id'] ?? null,
                'username'    => $u['username'] ?? ($username ?? 'system'),
                'action'      => $action,
                'entity_type' => $entityType,
                'entity_id'   => $entityId !== null ? (int) $entityId : null,
                'details'     => $details,
                'ip'          => client_ip(),
                'user_agent'  => substr($_SERVER['HTTP_USER_AGENT'] ?? 'cli', 0, 255),
                'created_at'  => date('Y-m-d H:i:s'),
                'prev_hash'   => $prev,
            ];
            $data['hash'] = self::hash($data);
            insert('audit_logs', $data);
            q("SELECT RELEASE_LOCK('audit_chain')");
        } catch (Throwable $e) {
            error_log('Audit error: ' . $e->getMessage());
        }
    }

    public static function hash(array $d): string
    {
        $payload = implode('|', [
            $d['prev_hash'], $d['user_id'] ?? '', $d['username'] ?? '', $d['action'], $d['entity_type'] ?? '',
            $d['entity_id'] ?? '', $d['details'] ?? '', $d['ip'] ?? '', $d['created_at'],
        ]);
        return hash_hmac('sha256', $payload, Crypto::masterKey());
    }

    /** Kiểm tra toàn vẹn chuỗi nhật ký */
    public static function verify(): array
    {
        $prev = '';
        $count = 0;
        $st = q('SELECT * FROM audit_logs ORDER BY id ASC');
        while ($r = $st->fetch()) {
            $count++;
            if ($r['prev_hash'] !== $prev) {
                return ['ok' => false, 'checked' => $count, 'broken_id' => $r['id'], 'reason' => 'Chuỗi liên kết bị đứt (có bản ghi bị xóa/chèn)'];
            }
            if (!hash_equals($r['hash'], self::hash($r))) {
                return ['ok' => false, 'checked' => $count, 'broken_id' => $r['id'], 'reason' => 'Nội dung bản ghi đã bị thay đổi'];
            }
            $prev = $r['hash'];
        }
        return ['ok' => true, 'checked' => $count, 'broken_id' => null, 'reason' => null];
    }

    public static function forEntity(string $type, int $id, int $limit = 50): array
    {
        return rows("SELECT a.*, u.full_name FROM audit_logs a LEFT JOIN users u ON u.id = a.user_id
                     WHERE a.entity_type = ? AND a.entity_id = ? ORDER BY a.id DESC LIMIT $limit", [$type, $id]);
    }

    public static function actionLabel(string $action): string
    {
        static $map = [
            'login' => 'Đăng nhập', 'logout' => 'Đăng xuất', 'login_failed' => 'Đăng nhập thất bại',
            'create' => 'Tạo mới', 'update' => 'Cập nhật', 'delete' => 'Xóa', 'view' => 'Xem',
            'download' => 'Tải xuống', 'share' => 'Chia sẻ', 'share_revoke' => 'Thu hồi chia sẻ',
            'share_download' => 'Tải qua liên kết chia sẻ', 'new_version' => 'Tạo phiên bản mới',
            'restore_version' => 'Khôi phục phiên bản', 'permission' => 'Phân quyền', 'publish' => 'Công bố',
            'close' => 'Đóng thầu', 'cancel' => 'Hủy', 'submit' => 'Nộp/Gửi', 'withdraw' => 'Rút hồ sơ',
            'approve' => 'Phê duyệt', 'reject' => 'Từ chối', 'submit_approval' => 'Trình duyệt',
            'score' => 'Chấm điểm', 'evaluate' => 'Tính kết quả đánh giá', 'award' => 'Trao thầu',
            'open_bids' => 'Mở thầu', 'pay' => 'Giải ngân', 'settle' => 'Quyết toán', 'confirm' => 'Xác nhận',
            'key_create' => 'Tạo khóa mã hóa', 'key_import' => 'Nhập khóa khách hàng', 'key_activate' => 'Kích hoạt khóa',
            'key_revoke' => 'Thu hồi khóa', 'settings' => 'Đổi cấu hình', 'audit_verify' => 'Kiểm tra toàn vẹn nhật ký',
            'export' => 'Xuất dữ liệu', 'status' => 'Đổi trạng thái', 'install' => 'Cài đặt hệ thống',
            'disburse' => 'Giải ngân', 'accept' => 'Nghiệm thu', 'bid_submit' => 'Nộp HSDT', 'bid_withdraw' => 'Rút HSDT',
            'po_confirm' => 'NCC xác nhận đơn hàng', 'invoice_submit' => 'Gửi hóa đơn', 'access_denied' => 'Từ chối truy cập',
            'register' => 'Đăng ký NCC', 'password' => 'Đổi mật khẩu',
            'workflow' => 'Cấu hình quy trình', 'reminder' => 'Nhắc việc tự động',
        ];
        return $map[$action] ?? $action;
    }
}
