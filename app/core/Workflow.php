<?php
/**
 * Quy trình phê duyệt nhiều bước, cấu hình được (Cài đặt > Quy trình phê duyệt).
 * Áp dụng cho: HSMT, hợp đồng, thanh toán/giải ngân, kế hoạch đấu thầu.
 */
class Workflow
{
    const ENTITIES = [
        'tender'   => 'Hồ sơ mời thầu',
        'contract' => 'Hợp đồng',
        'payment'  => 'Thanh toán / giải ngân',
        'plan'     => 'Kế hoạch đấu thầu',
    ];

    /** Trình duyệt. Trả về true nếu cần phê duyệt, false nếu không cấu hình bước nào (tự duyệt) */
    public static function start(string $type, int $id, string $title): bool
    {
        $steps = rows('SELECT * FROM workflow_steps WHERE entity_type = ? ORDER BY step_no', [$type]);
        q("UPDATE approvals SET status = 'cancelled' WHERE entity_type = ? AND entity_id = ? AND status IN ('waiting','pending')", [$type, $id]);
        Audit::log('submit_approval', $type, $id, $title);
        if (!$steps) {
            self::onComplete($type, $id, user_id());
            return false;
        }
        $round = (int) val('SELECT COALESCE(MAX(round_no), 0) + 1 FROM approvals WHERE entity_type = ? AND entity_id = ?', [$type, $id]);
        foreach ($steps as $i => $s) {
            insert('approvals', [
                'entity_type' => $type, 'entity_id' => $id, 'title' => $title, 'round_no' => $round,
                'step_no' => $s['step_no'], 'step_name' => $s['name'], 'role_id' => $s['role_id'],
                'status' => $i === 0 ? 'pending' : 'waiting', 'requested_by' => user_id(),
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        }
        Notifier::send(Notifier::usersWithRole((int) $steps[0]['role_id']), 'Có hồ sơ cần phê duyệt', $title . ' - ' . $steps[0]['name'], url('approvals/index'), 'warning');
        return true;
    }

    public static function canAct(array $approval): bool
    {
        $u = auth();
        return $u && $approval['status'] === 'pending' && can('approvals.act')
            && ((int) $u['role_id'] === (int) $approval['role_id'] || is_admin());
    }

    public static function act(int $approvalId, string $decision, string $comment = ''): void
    {
        $a = row('SELECT * FROM approvals WHERE id = ?', [$approvalId]);
        if (!$a || !self::canAct($a)) {
            throw new RuntimeException('Bạn không có quyền xử lý bước phê duyệt này.');
        }
        if ($decision === 'reject' && $comment === '') {
            throw new RuntimeException('Vui lòng nhập lý do từ chối.');
        }
        transaction(function () use ($a, $decision, $comment) {
            update_row('approvals', [
                'status' => $decision === 'approve' ? 'approved' : 'rejected',
                'acted_by' => user_id(), 'comment' => $comment, 'acted_at' => date('Y-m-d H:i:s'),
            ], 'id = ?', [$a['id']]);
            Audit::log($decision === 'approve' ? 'approve' : 'reject', $a['entity_type'], $a['entity_id'], $a['step_name'] . ($comment ? ': ' . $comment : ''));

            if ($decision === 'approve') {
                $next = row("SELECT * FROM approvals WHERE entity_type = ? AND entity_id = ? AND round_no = ? AND status = 'waiting' ORDER BY step_no LIMIT 1",
                    [$a['entity_type'], $a['entity_id'], $a['round_no']]);
                if ($next) {
                    update_row('approvals', ['status' => 'pending', 'created_at' => date('Y-m-d H:i:s')], 'id = ?', [$next['id']]);
                    Notifier::send(Notifier::usersWithRole((int) $next['role_id']), 'Có hồ sơ cần phê duyệt', $a['title'] . ' - ' . $next['step_name'], url('approvals/index'), 'warning');
                } else {
                    self::onComplete($a['entity_type'], (int) $a['entity_id'], (int) $a['requested_by']);
                }
            } else {
                q("UPDATE approvals SET status = 'cancelled' WHERE entity_type = ? AND entity_id = ? AND round_no = ? AND status = 'waiting'",
                    [$a['entity_type'], $a['entity_id'], $a['round_no']]);
                self::onReject($a['entity_type'], (int) $a['entity_id']);
                Notifier::send([$a['requested_by']], 'Hồ sơ bị từ chối phê duyệt', $a['title'] . ' - Lý do: ' . $comment, self::link($a['entity_type'], (int) $a['entity_id']), 'danger');
            }
        });
    }

    private static function onComplete(string $type, int $id, ?int $requester): void
    {
        $now = date('Y-m-d H:i:s');
        switch ($type) {
            case 'tender':
                update_row('tenders', ['status' => 'approved', 'updated_at' => $now], 'id = ?', [$id]);
                break;
            case 'contract':
                update_row('contracts', ['status' => 'active', 'updated_at' => $now], 'id = ?', [$id]);
                break;
            case 'payment':
                update_row('payments', ['status' => 'approved'], 'id = ?', [$id]);
                Notifier::send(Notifier::usersWithPermission('payments.manage'), 'Khoản thanh toán đã được duyệt chi', 'Có thể thực hiện giải ngân.', self::link($type, $id), 'success');
                break;
            case 'plan':
                update_row('tender_plans', ['status' => 'approved'], 'id = ?', [$id]);
                break;
        }
        if ($requester) {
            Notifier::send([$requester], 'Hồ sơ đã được phê duyệt', self::ENTITIES[$type] . ' #' . $id . ' đã hoàn tất phê duyệt.', self::link($type, $id), 'success');
        }
    }

    private static function onReject(string $type, int $id): void
    {
        switch ($type) {
            case 'tender':
                update_row('tenders', ['status' => 'draft'], 'id = ?', [$id]);
                break;
            case 'contract':
                update_row('contracts', ['status' => 'draft'], 'id = ?', [$id]);
                break;
            case 'payment':
                update_row('payments', ['status' => 'rejected'], 'id = ?', [$id]);
                break;
            case 'plan':
                update_row('tender_plans', ['status' => 'draft'], 'id = ?', [$id]);
                break;
        }
    }

    public static function link(string $type, int $id): string
    {
        switch ($type) {
            case 'tender':
                return url('tenders/view', ['id' => $id]);
            case 'contract':
                return url('contracts/view', ['id' => $id]);
            case 'payment':
                $cid = (int) val('SELECT contract_id FROM payments WHERE id = ?', [$id]);
                return url('contracts/view', ['id' => $cid, 'tab' => 'payments']);
            case 'plan':
                return url('plans/view', ['id' => $id]);
        }
        return url('approvals/index');
    }

    public static function history(string $type, int $id): array
    {
        return rows('SELECT a.*, r.name AS role_name, u.full_name AS actor, rq.full_name AS requester
                     FROM approvals a JOIN roles r ON r.id = a.role_id
                     LEFT JOIN users u ON u.id = a.acted_by
                     LEFT JOIN users rq ON rq.id = a.requested_by
                     WHERE a.entity_type = ? AND a.entity_id = ? ORDER BY a.round_no DESC, a.step_no ASC', [$type, $id]);
    }

    public static function pendingFor(string $type, int $id): ?array
    {
        return row("SELECT * FROM approvals WHERE entity_type = ? AND entity_id = ? AND status = 'pending' LIMIT 1", [$type, $id]);
    }
}
