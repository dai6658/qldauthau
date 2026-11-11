<?php
/**
 * Thông báo trong hệ thống & nhắc việc tự động (hạn nộp thầu, hạn công việc, hạn thanh toán, phê duyệt tồn đọng).
 * Giao diện tự hỏi (polling) mỗi 30 giây để hiển thị thông báo gần thời gian thực.
 */
class Notifier
{
    public static function send($userIds, string $title, string $message = '', ?string $link = null, string $type = 'info'): void
    {
        $ids = array_unique(array_filter(array_map('intval', (array) $userIds)));
        foreach ($ids as $id) {
            insert('notifications', [
                'user_id' => $id, 'title' => mb_substr($title, 0, 255), 'message' => mb_substr($message, 0, 1000),
                'link' => $link, 'type' => $type, 'created_at' => date('Y-m-d H:i:s'),
            ]);
        }
    }

    public static function usersWithRole(int $roleId): array
    {
        return array_column(rows('SELECT id FROM users WHERE role_id = ? AND is_active = 1', [$roleId]), 'id');
    }

    public static function usersWithPermission(string $perm): array
    {
        return array_column(rows("SELECT DISTINCT u.id FROM users u
            JOIN roles r ON r.id = u.role_id
            LEFT JOIN role_permissions rp ON rp.role_id = u.role_id
            LEFT JOIN permissions p ON p.id = rp.permission_id
            WHERE u.is_active = 1 AND (p.code = ? OR r.code = 'admin')", [$perm]), 'id');
    }

    public static function supplierUsers(array $supplierIds): array
    {
        if (!$supplierIds) {
            return [];
        }
        $in = implode(',', array_fill(0, count($supplierIds), '?'));
        return array_column(rows("SELECT id FROM users WHERE is_active = 1 AND supplier_id IN ($in)", array_values($supplierIds)), 'id');
    }

    /** Nhà cung cấp liên quan tới gói thầu: được mời, hoặc tất cả NCC đã duyệt nếu đấu thầu rộng rãi */
    public static function tenderSuppliers(array $tender): array
    {
        if ($tender['method'] === 'open') {
            return array_column(rows("SELECT id FROM suppliers WHERE status = 'approved'"), 'id');
        }
        return array_column(rows('SELECT supplier_id FROM tender_invitations WHERE tender_id = ?', [$tender['id']]), 'supplier_id');
    }

    public static function tenderTeam(array $tender): array
    {
        $ids = array_column(rows('SELECT user_id FROM tender_members WHERE tender_id = ?', [$tender['id']]), 'user_id');
        $ids[] = $tender['created_by'];
        return $ids;
    }

    private static function remindOnce(string $kind, int $entityId, int $userId, string $title, string $msg, string $link, string $type = 'warning'): void
    {
        $st = q('INSERT IGNORE INTO reminder_log (kind, entity_id, user_id, sent_on) VALUES (?, ?, ?, CURDATE())', [$kind, $entityId, $userId]);
        if ($st->rowCount() > 0) {
            self::send([$userId], $title, $msg, $link, $type);
        }
    }

    /** Chạy định kỳ (cron) hoặc tự động mỗi giờ khi có người truy cập */
    public static function runReminders(): array
    {
        $days = (int) setting('reminder_days', 3);
        $stats = ['closed' => 0, 'reminders' => 0];
        $before = (int) val('SELECT COUNT(*) FROM notifications');

        // 1. Tự động đóng thầu khi quá hạn nộp
        foreach (rows("SELECT * FROM tenders WHERE status = 'published' AND close_at IS NOT NULL AND close_at <= NOW()") as $t) {
            update_row('tenders', ['status' => 'closed', 'updated_at' => date('Y-m-d H:i:s')], 'id = ?', [$t['id']]);
            q("UPDATE bids SET status = 'withdrawn', updated_at = NOW() WHERE tender_id = ? AND status = 'draft'", [$t['id']]);
            Audit::log('close', 'tender', $t['id'], 'Tự động đóng thầu khi đến hạn');
            self::send(self::tenderTeam($t), 'Gói thầu đã đóng: ' . $t['code'], 'Đã đến thời điểm đóng thầu, có thể tiến hành mở thầu và đánh giá.', url('tenders/view', ['id' => $t['id']]), 'info');
            $stats['closed']++;
        }

        // 2. Sắp hết hạn nộp HSDT
        foreach (rows("SELECT * FROM tenders WHERE status = 'published' AND close_at BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL ? DAY)", [$days]) as $t) {
            $left = days_left($t['close_at']);
            foreach (self::tenderTeam($t) as $uid) {
                self::remindOnce('tender_close_team', (int) $t['id'], (int) $uid, 'Sắp đóng thầu: ' . $t['code'], "Còn {$left} ngày đến thời điểm đóng thầu ({$t['close_at']}).", url('tenders/view', ['id' => $t['id']]));
            }
            $submitted = array_column(rows("SELECT supplier_id FROM bids WHERE tender_id = ? AND status = 'submitted'", [$t['id']]), 'supplier_id');
            $targets = array_diff(self::tenderSuppliers($t), $submitted);
            foreach (self::supplierUsers($targets) as $uid) {
                self::remindOnce('tender_close_supplier', (int) $t['id'], (int) $uid, 'Nhắc hạn nộp HSDT: ' . $t['code'], "Gói thầu \"{$t['title']}\" đóng thầu lúc {$t['close_at']}. Bạn chưa nộp hồ sơ.", url('portal/tender', ['id' => $t['id']]));
            }
        }

        // 3. Công việc trong kế hoạch sắp đến hạn / quá hạn
        foreach (rows("SELECT pt.*, p.created_by AS plan_owner, p.name AS plan_name FROM plan_tasks pt JOIN tender_plans p ON p.id = pt.plan_id
                       WHERE pt.status <> 'done' AND pt.due_date IS NOT NULL AND pt.due_date <= DATE_ADD(CURDATE(), INTERVAL ? DAY)", [$days]) as $task) {
            $left = days_left($task['due_date']);
            $link = url('plans/view', ['id' => $task['plan_id']]);
            if ($left < 0) {
                foreach ([$task['assignee_id'], $task['plan_owner']] as $uid) {
                    if ($uid) {
                        self::remindOnce('task_overdue', (int) $task['id'], (int) $uid, 'Công việc quá hạn: ' . $task['name'], 'Quá hạn ' . abs($left) . ' ngày (' . fdate($task['due_date']) . ') - ' . $task['plan_name'], $link, 'danger');
                    }
                }
            } elseif ($task['assignee_id']) {
                self::remindOnce('task_due', (int) $task['id'], (int) $task['assignee_id'], 'Công việc sắp đến hạn: ' . $task['name'], "Còn {$left} ngày (hạn " . fdate($task['due_date']) . ')', $link);
            }
        }

        // 4. Thanh toán sắp đến hạn
        $finance = self::usersWithPermission('payments.manage');
        foreach (rows("SELECT pm.*, c.code AS contract_code FROM payments pm JOIN contracts c ON c.id = pm.contract_id
                       WHERE pm.status IN ('planned','pending_approval','approved') AND pm.due_date IS NOT NULL
                       AND pm.due_date <= DATE_ADD(CURDATE(), INTERVAL ? DAY)", [$days]) as $pm) {
            $left = days_left($pm['due_date']);
            $title = ($left < 0 ? 'Thanh toán quá hạn: ' : 'Thanh toán sắp đến hạn: ') . $pm['contract_code'];
            foreach ($finance as $uid) {
                self::remindOnce('payment_due', (int) $pm['id'], (int) $uid, $title, $pm['milestone'] . ' - ' . money($pm['amount']) . ' - hạn ' . fdate($pm['due_date']), url('contracts/view', ['id' => $pm['contract_id'], 'tab' => 'payments']), $left < 0 ? 'danger' : 'warning');
            }
        }

        // 5. Hợp đồng sắp hết hạn
        foreach (rows("SELECT * FROM contracts WHERE status = 'active' AND end_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL ? DAY)", [$days * 5]) as $c) {
            if ($c['created_by']) {
                self::remindOnce('contract_end', (int) $c['id'], (int) $c['created_by'], 'Hợp đồng sắp hết hạn: ' . $c['code'], 'Ngày kết thúc: ' . fdate($c['end_date']), url('contracts/view', ['id' => $c['id']]));
            }
        }

        // 6. Phê duyệt tồn đọng
        $apDays = (int) setting('approval_reminder_days', 2);
        foreach (rows("SELECT * FROM approvals WHERE status = 'pending' AND created_at <= DATE_SUB(NOW(), INTERVAL ? DAY)", [$apDays]) as $a) {
            foreach (self::usersWithRole((int) $a['role_id']) as $uid) {
                self::remindOnce('approval_pending', (int) $a['id'], (int) $uid, 'Hồ sơ chờ phê duyệt quá ' . $apDays . ' ngày', $a['title'], url('approvals/index'));
            }
        }

        $stats['reminders'] = (int) val('SELECT COUNT(*) FROM notifications') - $before;
        set_setting('last_cron_run', date('Y-m-d H:i:s'));
        return $stats;
    }
}
