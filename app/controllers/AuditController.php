<?php
class AuditController extends Controller
{
    private function filters(): array
    {
        $where = ['1=1'];
        $params = [];
        if ($u = input_int('user_id')) {
            $where[] = 'a.user_id = ?';
            $params[] = $u;
        }
        foreach (['action' => 'a.action', 'entity_type' => 'a.entity_type'] as $k => $col) {
            if ($v = input($k)) {
                $where[] = "$col = ?";
                $params[] = $v;
            }
        }
        if ($id = input_int('entity_id')) {
            $where[] = 'a.entity_id = ?';
            $params[] = $id;
        }
        if ($f = input_date('from')) {
            $where[] = 'a.created_at >= ?';
            $params[] = $f . ' 00:00:00';
        }
        if ($t = input_date('to')) {
            $where[] = 'a.created_at <= ?';
            $params[] = $t . ' 23:59:59';
        }
        if ($kw = input('q')) {
            $where[] = '(a.details LIKE ? OR a.username LIKE ? OR a.ip LIKE ?)';
            array_push($params, "%$kw%", "%$kw%", "%$kw%");
        }
        return [implode(' AND ', $where), $params];
    }

    public function index(): void
    {
        require_perm('audit.view');
        [$w, $params] = $this->filters();
        $p = paginate("SELECT COUNT(*) FROM audit_logs a WHERE $w",
            "SELECT a.*, u.full_name FROM audit_logs a LEFT JOIN users u ON u.id = a.user_id WHERE $w ORDER BY a.id DESC", $params, 30);
        $actions = [];
        foreach (array_column(rows('SELECT DISTINCT action FROM audit_logs ORDER BY action'), 'action') as $a) {
            $actions[$a] = Audit::actionLabel($a);
        }
        view('audit/index', [
            'title' => 'Nhật ký kiểm toán', 'p' => $p, 'actions' => $actions,
            'users' => pairs('SELECT id, full_name FROM users ORDER BY full_name'),
            'entities' => pairs('SELECT DISTINCT entity_type, entity_type FROM audit_logs WHERE entity_type IS NOT NULL ORDER BY entity_type'),
            'verify' => $_SESSION['audit_verify'] ?? null,
            'stats' => row("SELECT COUNT(*) n, MIN(created_at) first_at, SUM(action = 'login_failed') failed, SUM(action = 'access_denied') denied,
                SUM(created_at >= CURDATE()) today FROM audit_logs"),
        ]);
        unset($_SESSION['audit_verify']);
    }

    public function verify(): void
    {
        require_perm('audit.view');
        $started = microtime(true);
        $result = Audit::verify();
        $result['ms'] = round((microtime(true) - $started) * 1000);
        $result['at'] = date('Y-m-d H:i:s');
        Audit::log('audit_verify', 'system', null, $result['ok'] ? "Toàn vẹn: {$result['checked']} bản ghi" : "PHÁT HIỆN SAI LỆCH tại #{$result['broken_id']}: {$result['reason']}");
        if (!$result['ok']) {
            Notifier::send(Notifier::usersWithPermission('settings.manage'), 'CẢNH BÁO: Nhật ký kiểm toán bị can thiệp', $result['reason'] . ' (bản ghi #' . $result['broken_id'] . ')', url('audit/index'), 'danger');
        }
        $_SESSION['audit_verify'] = $result;
        redirect('audit/index');
    }

    public function export(): void
    {
        require_perm('audit.view');
        [$w, $params] = $this->filters();
        $data = rows("SELECT a.id, a.created_at, a.username, u.full_name, a.action, a.entity_type, a.entity_id, a.details, a.ip, a.hash
            FROM audit_logs a LEFT JOIN users u ON u.id = a.user_id WHERE $w ORDER BY a.id DESC LIMIT 50000", $params);
        foreach ($data as &$r) {
            $r['action'] = Audit::actionLabel($r['action']);
        }
        unset($r);
        Audit::log('export', 'system', null, 'Xuất nhật ký kiểm toán (' . count($data) . ' dòng)');
        csv_download('nhat-ky-kiem-toan-' . date('Ymd-His') . '.csv', ['ID', 'Thời gian', 'Tài khoản', 'Họ tên', 'Hành động', 'Đối tượng', 'ID đối tượng', 'Chi tiết', 'IP', 'Mã băm'], $data);
    }
}
