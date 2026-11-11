<?php
/**
 * Báo cáo & phân tích: hiệu quả đấu thầu, tiết kiệm ngân sách, thời gian xử lý,
 * hiệu suất nhà cung cấp, chi phí theo dự án
 */
class ReportsController extends Controller
{
    private function period(): array
    {
        $from = input_date('from') ?: date('Y-01-01');
        $to = input_date('to') ?: date('Y-12-31');
        return [$from, $to];
    }

    public function index(): void
    {
        require_perm('reports.view');
        [$from, $to] = $this->period();
        $range = [$from . ' 00:00:00', $to . ' 23:59:59'];

        $tenders = rows("SELECT t.*, b.total_price AS awarded_price,
                (SELECT COUNT(*) FROM bids x WHERE x.tender_id = t.id AND x.status NOT IN ('draft','withdrawn')) AS bid_count,
                (SELECT MIN(created_at) FROM audit_logs a WHERE a.entity_type = 'tender' AND a.entity_id = t.id AND a.action = 'award') AS awarded_at,
                (SELECT MIN(created_at) FROM audit_logs a WHERE a.entity_type = 'tender' AND a.entity_id = t.id AND a.action = 'submit_approval') AS submitted_at
            FROM tenders t LEFT JOIN bids b ON b.id = t.awarded_bid_id
            WHERE t.created_at BETWEEN ? AND ?", $range);

        $byStatus = [];
        foreach ($tenders as $t) {
            $byStatus[$t['status']] = ($byStatus[$t['status']] ?? 0) + 1;
        }
        $awarded = array_filter($tenders, fn($t) => $t['status'] === 'awarded');
        $finished = count($awarded) + (int) ($byStatus['cancelled'] ?? 0);
        $budgetAwarded = array_sum(array_column($awarded, 'budget'));
        $valueAwarded = array_sum(array_column($awarded, 'awarded_price'));
        $published = array_filter($tenders, fn($t) => $t['publish_at']);

        $avgDays = function (array $list, string $a, string $b) {
            $d = [];
            foreach ($list as $t) {
                if (!empty($t[$a]) && !empty($t[$b]) && strtotime($t[$b]) >= strtotime($t[$a])) {
                    $d[] = (strtotime($t[$b]) - strtotime($t[$a])) / 86400;
                }
            }
            return $d ? round(array_sum($d) / count($d), 1) : null;
        };
        $stages = [
            'Lập HSMT → trình duyệt' => $avgDays($tenders, 'created_at', 'submitted_at'),
            'Trình duyệt → công bố' => $avgDays($tenders, 'submitted_at', 'publish_at'),
            'Công bố → mở thầu' => $avgDays($tenders, 'publish_at', 'opening_at'),
            'Mở thầu → trao thầu' => $avgDays($tenders, 'opening_at', 'awarded_at'),
            'Tổng chu kỳ (lập → trao thầu)' => $avgDays($tenders, 'created_at', 'awarded_at'),
        ];

        $kpi = [
            'total' => count($tenders),
            'published' => count($published),
            'awarded' => count($awarded),
            'success_rate' => $finished ? round(count($awarded) * 100 / $finished, 1) : null,
            'avg_bids' => $published ? round(array_sum(array_column($published, 'bid_count')) / count($published), 1) : 0,
            'budget_awarded' => $budgetAwarded,
            'value_awarded' => $valueAwarded,
            'saving' => $budgetAwarded - $valueAwarded,
            'saving_rate' => $budgetAwarded > 0 ? round(($budgetAwarded - $valueAwarded) * 100 / $budgetAwarded, 2) : 0,
            'single_bid' => count(array_filter($published, fn($t) => (int) $t['bid_count'] === 1 && $t['status'] !== 'published')),
        ];

        $byCategory = rows("SELECT t.category, COUNT(*) n, SUM(t.budget) budget, SUM(CASE WHEN t.status='awarded' THEN b.total_price END) awarded,
                SUM(CASE WHEN t.status='awarded' THEN t.budget END) awarded_budget
            FROM tenders t LEFT JOIN bids b ON b.id = t.awarded_bid_id WHERE t.created_at BETWEEN ? AND ? GROUP BY t.category", $range);
        $byMethod = rows("SELECT t.method, COUNT(*) n, AVG((SELECT COUNT(*) FROM bids x WHERE x.tender_id = t.id AND x.status NOT IN ('draft','withdrawn'))) avg_bids
            FROM tenders t WHERE t.created_at BETWEEN ? AND ? GROUP BY t.method", $range);
        $monthly = rows("SELECT DATE_FORMAT(publish_at, '%Y-%m') ym, COUNT(*) n, SUM(budget) budget FROM tenders
            WHERE publish_at BETWEEN ? AND ? GROUP BY ym ORDER BY ym", $range);
        $disbursed = rows("SELECT DATE_FORMAT(paid_at, '%Y-%m') ym, SUM(paid_amount) s FROM payments
            WHERE status = 'paid' AND paid_at BETWEEN ? AND ? GROUP BY ym ORDER BY ym", [$from, $to]);

        $suppliers = rows("SELECT s.id, s.code, s.name, s.rating, s.status,
                COUNT(DISTINCT CASE WHEN b.status NOT IN ('draft','withdrawn') THEN b.id END) bids,
                COUNT(DISTINCT CASE WHEN b.status = 'awarded' THEN b.id END) wins,
                AVG(CASE WHEN b.tech_score IS NOT NULL THEN b.tech_score END) avg_tech,
                (SELECT COALESCE(SUM(value),0) FROM contracts c WHERE c.supplier_id = s.id AND c.status IN ('active','completed')) contract_value,
                (SELECT COUNT(*) FROM supplier_evaluations e WHERE e.supplier_id = s.id) evals,
                (SELECT COUNT(*) FROM contracts c WHERE c.supplier_id = s.id AND c.status = 'active' AND c.end_date < CURDATE()) late_contracts
            FROM suppliers s LEFT JOIN bids b ON b.supplier_id = s.id
            GROUP BY s.id, s.code, s.name, s.rating, s.status ORDER BY wins DESC, s.rating DESC LIMIT 15");

        $projects = rows("SELECT p.code, p.name,
                COALESCE((SELECT SUM(approved_amount) FROM investment_items i WHERE i.project_id = p.id),0) approved,
                COALESCE((SELECT SUM(value) FROM contracts c WHERE c.project_id = p.id AND c.status IN ('pending_approval','active','completed')),0) committed,
                COALESCE((SELECT SUM(amount) FROM expenses e WHERE e.project_id = p.id),0) spent,
                (SELECT COUNT(*) FROM plan_tasks pt JOIN tender_plans tp ON tp.id = pt.plan_id WHERE tp.project_id = p.id AND pt.status <> 'done' AND pt.due_date < CURDATE()) late_tasks
            FROM projects p ORDER BY p.id");

        $contracts = row("SELECT COUNT(*) n, COALESCE(SUM(value),0) v,
                SUM(status = 'completed') completed, SUM(status = 'active') active,
                SUM(status = 'active' AND end_date < CURDATE()) overdue,
                COALESCE(SUM(CASE WHEN status = 'completed' THEN value - settled_amount END),0) settle_diff
            FROM contracts");

        view('reports/index', compact('from', 'to', 'kpi', 'byStatus', 'stages', 'byCategory', 'byMethod', 'monthly', 'disbursed', 'suppliers', 'projects', 'contracts', 'tenders')
            + ['title' => 'Báo cáo & phân tích']);
    }

    public function export(): void
    {
        require_perm('reports.view');
        [$from, $to] = $this->period();
        $type = input('type', 'tenders');
        Audit::log('export', 'report', null, "Xuất báo cáo $type ($from - $to)");
        if ($type === 'suppliers') {
            $data = rows("SELECT s.code, s.name, s.tax_code, s.status, s.rating,
                    (SELECT COUNT(*) FROM bids b WHERE b.supplier_id = s.id AND b.status NOT IN ('draft','withdrawn')),
                    (SELECT COUNT(*) FROM bids b WHERE b.supplier_id = s.id AND b.status = 'awarded'),
                    (SELECT COALESCE(SUM(value),0) FROM contracts c WHERE c.supplier_id = s.id)
                FROM suppliers s ORDER BY s.id");
            foreach ($data as &$r) {
                $r['status'] = label('supplier_status', $r['status']);
            }
            unset($r);
            csv_download('bao-cao-nha-cung-cap.csv', ['Mã', 'Tên', 'MST', 'Trạng thái', 'Điểm', 'Số HSDT', 'Trúng thầu', 'Giá trị HĐ'], $data);
        }
        if ($type === 'projects') {
            $data = rows("SELECT p.code, p.name,
                    COALESCE((SELECT SUM(approved_amount) FROM investment_items i WHERE i.project_id = p.id),0),
                    COALESCE((SELECT SUM(value) FROM contracts c WHERE c.project_id = p.id AND c.status IN ('pending_approval','active','completed')),0),
                    COALESCE((SELECT SUM(amount) FROM expenses e WHERE e.project_id = p.id),0)
                FROM projects p ORDER BY p.id");
            csv_download('bao-cao-chi-phi-du-an.csv', ['Mã dự án', 'Tên', 'TMĐT duyệt', 'Cam kết', 'Đã chi'], $data);
        }
        $data = rows("SELECT t.code, t.title, p.code pc, t.category, t.method, t.budget, t.status, t.publish_at, t.close_at,
                (SELECT COUNT(*) FROM bids x WHERE x.tender_id = t.id AND x.status NOT IN ('draft','withdrawn')),
                b.total_price, s.name
            FROM tenders t JOIN projects p ON p.id = t.project_id LEFT JOIN bids b ON b.id = t.awarded_bid_id LEFT JOIN suppliers s ON s.id = b.supplier_id
            WHERE t.created_at BETWEEN ? AND ? ORDER BY t.id", [$from . ' 00:00:00', $to . ' 23:59:59']);
        foreach ($data as &$r) {
            $r['category'] = label('category', $r['category']);
            $r['method'] = label('method', $r['method']);
            $r['status'] = label('tender_status', $r['status']);
        }
        unset($r);
        csv_download('bao-cao-goi-thau.csv', ['Mã', 'Tên gói thầu', 'Dự án', 'Lĩnh vực', 'Hình thức', 'Giá gói thầu', 'Trạng thái', 'Công bố', 'Đóng thầu', 'Số HSDT', 'Giá trúng thầu', 'Nhà thầu trúng'], $data);
    }
}
