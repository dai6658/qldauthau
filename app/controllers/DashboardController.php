<?php
class DashboardController extends Controller
{
    public function index(): void
    {
        require_perm('dashboard.view');
        $year = (int) date('Y');

        $kpi = [
            'tenders_open'   => (int) val("SELECT COUNT(*) FROM tenders WHERE status = 'published'"),
            'tenders_total'  => (int) val('SELECT COUNT(*) FROM tenders'),
            'tenders_eval'   => (int) val("SELECT COUNT(*) FROM tenders WHERE status IN ('closed','evaluating')"),
            'awarded_value'  => (float) val("SELECT COALESCE(SUM(b.total_price),0) FROM tenders t JOIN bids b ON b.id = t.awarded_bid_id WHERE t.status = 'awarded'"),
            'awarded_budget' => (float) val("SELECT COALESCE(SUM(t.budget),0) FROM tenders t WHERE t.status = 'awarded'"),
            'suppliers'      => (int) val("SELECT COUNT(*) FROM suppliers WHERE status = 'approved'"),
            'suppliers_pending' => (int) val("SELECT COUNT(*) FROM suppliers WHERE status = 'pending'"),
            'contracts_value'=> (float) val("SELECT COALESCE(SUM(value),0) FROM contracts WHERE status IN ('active','completed')"),
            'paid'           => (float) val("SELECT COALESCE(SUM(paid_amount),0) FROM payments WHERE status = 'paid'"),
            'tmdt'           => (float) val('SELECT COALESCE(SUM(approved_amount),0) FROM investment_items'),
            'spent'          => (float) val('SELECT COALESCE(SUM(amount),0) FROM expenses'),
            'approvals'      => (int) val("SELECT COUNT(*) FROM approvals WHERE status = 'pending'"),
            'awarded_count'  => (int) val("SELECT COUNT(*) FROM tenders WHERE status = 'awarded'"),
            'cancelled_count'=> (int) val("SELECT COUNT(*) FROM tenders WHERE status = 'cancelled'"),
        ];
        $kpi['savings'] = $kpi['awarded_budget'] - $kpi['awarded_value'];
        $finished = $kpi['awarded_count'] + $kpi['cancelled_count'];
        $kpi['success_rate'] = $finished ? round($kpi['awarded_count'] * 100 / $finished, 1) : 0;

        $byStatus = pairs('SELECT status, COUNT(*) FROM tenders GROUP BY status');
        $budgetByProject = rows("SELECT p.code, p.name,
                COALESCE((SELECT SUM(approved_amount) FROM investment_items i WHERE i.project_id = p.id),0) AS tmdt,
                COALESCE((SELECT SUM(amount) FROM expenses e WHERE e.project_id = p.id),0) AS spent,
                COALESCE((SELECT SUM(value) FROM contracts c WHERE c.project_id = p.id AND c.status IN ('active','completed','pending_approval')),0) AS committed
            FROM projects p ORDER BY p.id");
        $monthly = array_fill(1, 12, 0);
        foreach (rows('SELECT MONTH(expense_date) m, SUM(amount) s FROM expenses WHERE YEAR(expense_date) = ? GROUP BY MONTH(expense_date)', [$year]) as $r) {
            $monthly[(int) $r['m']] = (float) $r['s'];
        }
        $topSuppliers = rows("SELECT s.id, s.name, s.rating,
                (SELECT COUNT(*) FROM bids b WHERE b.supplier_id = s.id AND b.status NOT IN ('draft','withdrawn')) AS bids,
                (SELECT COUNT(*) FROM bids b WHERE b.supplier_id = s.id AND b.status = 'awarded') AS wins
            FROM suppliers s WHERE s.status = 'approved' ORDER BY s.rating DESC LIMIT 5");

        $closingSoon = rows("SELECT id, code, title, close_at, (SELECT COUNT(*) FROM bids b WHERE b.tender_id = t.id AND b.status = 'submitted') AS bid_count
            FROM tenders t WHERE status = 'published' ORDER BY close_at LIMIT 6");
        $tasksDue = rows("SELECT pt.*, u.full_name FROM plan_tasks pt LEFT JOIN users u ON u.id = pt.assignee_id
            WHERE pt.status <> 'done' AND pt.due_date <= DATE_ADD(CURDATE(), INTERVAL 7 DAY) ORDER BY pt.due_date LIMIT 6");
        $paymentsDue = rows("SELECT pm.*, c.code AS contract_code FROM payments pm JOIN contracts c ON c.id = pm.contract_id
            WHERE pm.status IN ('planned','pending_approval','approved') ORDER BY pm.due_date LIMIT 6");
        $recent = rows('SELECT a.*, u.full_name FROM audit_logs a LEFT JOIN users u ON u.id = a.user_id
            WHERE a.action NOT IN (\'login\',\'logout\',\'view\',\'login_failed\') ORDER BY a.id DESC LIMIT 8');

        view('dashboard/index', compact('kpi', 'byStatus', 'budgetByProject', 'monthly', 'topSuppliers', 'closingSoon', 'tasksDue', 'paymentsDue', 'recent', 'year') + ['title' => 'Bảng điều khiển']);
    }
}
