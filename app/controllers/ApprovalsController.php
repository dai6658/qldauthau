<?php
class ApprovalsController extends Controller
{
    public function index(): void
    {
        require_perm('approvals.act');
        $u = auth();
        // Quản trị xem được mọi bước; người khác chỉ thấy bước thuộc vai trò của mình
        $mine = is_admin() ? '' : ' AND a.role_id = ' . (int) $u['role_id'];
        $pending = rows("SELECT a.*, r.name AS role_name, u.full_name AS requester FROM approvals a
            JOIN roles r ON r.id = a.role_id LEFT JOIN users u ON u.id = a.requested_by
            WHERE a.status = 'pending'$mine ORDER BY a.created_at");
        $mineActed = is_admin() ? '' : ' AND a.acted_by = ' . (int) $u['id'];
        $history = rows("SELECT a.*, r.name AS role_name, u.full_name AS actor FROM approvals a
            JOIN roles r ON r.id = a.role_id LEFT JOIN users u ON u.id = a.acted_by
            WHERE a.status IN ('approved','rejected')$mineActed ORDER BY a.acted_at DESC LIMIT 30");
        foreach ($pending as &$a) {
            $a['summary'] = $this->summary($a['entity_type'], (int) $a['entity_id']);
        }
        unset($a);
        view('approvals/index', ['title' => 'Phê duyệt', 'pending' => $pending, 'history' => $history]);
    }

    public function act(): void
    {
        $decision = input('decision') === 'approve' ? 'approve' : 'reject';
        $this->attempt(function () use ($decision) {
            Workflow::act(input_int('id'), $decision, (string) input('comment', ''));
        }, $decision === 'approve' ? 'Đã phê duyệt.' : 'Đã từ chối hồ sơ.');
        redirect_back('approvals/index');
    }

    private function summary(string $type, int $id): string
    {
        switch ($type) {
            case 'tender':
                $t = row('SELECT code, title, budget, close_at FROM tenders WHERE id = ?', [$id]);
                return $t ? 'Giá gói thầu ' . money($t['budget']) . ' · đóng thầu ' . fdatetime($t['close_at']) : '';
            case 'contract':
                $c = row('SELECT c.value, s.name FROM contracts c JOIN suppliers s ON s.id = c.supplier_id WHERE c.id = ?', [$id]);
                return $c ? 'Giá trị ' . money($c['value']) . ' · ' . $c['name'] : '';
            case 'payment':
                $p = row('SELECT p.amount, p.milestone, c.code, c.value,
                        (SELECT COALESCE(SUM(paid_amount),0) FROM payments x WHERE x.contract_id = c.id AND x.status = \'paid\') AS paid
                        FROM payments p JOIN contracts c ON c.id = p.contract_id WHERE p.id = ?', [$id]);
                return $p ? $p['code'] . ' · ' . $p['milestone'] . ' · ' . money($p['amount']) . ' (đã chi ' . money($p['paid']) . '/' . money($p['value']) . ')' : '';
            case 'plan':
                $p = row('SELECT (SELECT COUNT(*) FROM plan_tasks WHERE plan_id = ?) AS n', [$id]);
                return $p ? $p['n'] . ' công việc' : '';
        }
        return '';
    }
}
