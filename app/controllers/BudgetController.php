<?php
/**
 * Lập & kiểm soát Tổng mức đầu tư (TMĐT), ngân sách và chi phí thực tế
 */
class BudgetController extends Controller
{
    public function index(): void
    {
        require_perm('budget.view');
        $projects = rows("SELECT p.*,
                COALESCE((SELECT SUM(estimated_amount) FROM investment_items i WHERE i.project_id = p.id),0) AS estimated,
                COALESCE((SELECT SUM(approved_amount) FROM investment_items i WHERE i.project_id = p.id),0) AS approved,
                COALESCE((SELECT SUM(value) FROM contracts c WHERE c.project_id = p.id AND c.status IN ('pending_approval','active','completed')),0) AS committed,
                COALESCE((SELECT SUM(amount) FROM expenses e WHERE e.project_id = p.id),0) AS spent
            FROM projects p ORDER BY p.id DESC");
        view('budget/index', ['title' => 'Tổng mức đầu tư & chi phí', 'projects' => $projects]);
    }

    private function project(): array
    {
        $p = row('SELECT * FROM projects WHERE id = ?', [input_int('project_id') ?: input_int('id')]);
        if (!$p) {
            abort(404, 'Không tìm thấy dự án.');
        }
        return $p;
    }

    public static function projectItems(int $projectId): array
    {
        return rows("SELECT i.*,
                COALESCE((SELECT SUM(t.budget) FROM tenders t WHERE t.investment_item_id = i.id AND t.status NOT IN ('cancelled','awarded')),0) AS tender_budget,
                COALESCE((SELECT SUM(c.value) FROM contracts c JOIN tenders t ON t.id = c.tender_id WHERE t.investment_item_id = i.id AND c.status IN ('pending_approval','active','completed')),0) AS committed,
                COALESCE((SELECT SUM(e.amount) FROM expenses e WHERE e.investment_item_id = i.id),0) AS spent
            FROM investment_items i WHERE i.project_id = ? ORDER BY FIELD(i.category,'construction','equipment','land','management','consulting','other','contingency'), i.id", [$projectId]);
    }

    public function projectView(): void
    {
        require_perm('budget.view');
        $project = $this->project();
        $items = self::projectItems((int) $project['id']);
        $expenses = rows('SELECT e.*, i.name AS item_name, c.code AS contract_code, u.full_name FROM expenses e
            LEFT JOIN investment_items i ON i.id = e.investment_item_id LEFT JOIN contracts c ON c.id = e.contract_id
            LEFT JOIN users u ON u.id = e.created_by WHERE e.project_id = ? ORDER BY e.expense_date DESC, e.id DESC', [$project['id']]);
        $tenders = rows("SELECT t.code, t.title, t.budget, t.status, b.total_price AS awarded_price, s.name AS supplier,
                (SELECT MIN(total_price) FROM bids x WHERE x.tender_id = t.id AND x.status NOT IN ('draft','withdrawn','invalid')) AS min_price,
                (SELECT MAX(total_price) FROM bids x WHERE x.tender_id = t.id AND x.status NOT IN ('draft','withdrawn','invalid')) AS max_price,
                (SELECT COUNT(*) FROM bids x WHERE x.tender_id = t.id AND x.status NOT IN ('draft','withdrawn')) AS bid_count
            FROM tenders t LEFT JOIN bids b ON b.id = t.awarded_bid_id LEFT JOIN suppliers s ON s.id = b.supplier_id
            WHERE t.project_id = ? AND t.status IN ('evaluating','awarded') ORDER BY t.id", [$project['id']]);
        $monthly = rows("SELECT DATE_FORMAT(expense_date, '%Y-%m') ym, SUM(amount) s FROM expenses WHERE project_id = ? GROUP BY ym ORDER BY ym", [$project['id']]);
        view('budget/project', compact('project', 'items', 'expenses', 'tenders', 'monthly') + [
            'title' => 'TMĐT ' . $project['code'],
            'contracts' => pairs("SELECT id, code FROM contracts WHERE project_id = ?", [$project['id']]),
        ]);
    }

    public function saveItem(): void
    {
        require_perm('budget.manage');
        $project = $this->project();
        $data = ['project_id' => $project['id'], 'category' => input('category'), 'name' => input('name'),
                 'estimated_amount' => input_money('estimated_amount'), 'approved_amount' => input_money('approved_amount'), 'note' => input('note')];
        $this->attempt(function () use ($data) {
            if (!$data['name'] || !isset(labels('invest_category')[$data['category']])) {
                $this->fail('Nhập tên khoản mục và nhóm chi phí.');
            }
            if ($id = input_int('item_id')) {
                $old = row('SELECT * FROM investment_items WHERE id = ?', [$id]);
                update_row('investment_items', $data, 'id = ? AND project_id = ?', [$id, $data['project_id']]);
                Audit::log('update', 'project', $data['project_id'], ['khoản mục' => $data['name'], 'duyệt cũ' => $old['approved_amount'] ?? null, 'duyệt mới' => $data['approved_amount']]);
            } else {
                insert('investment_items', $data);
                Audit::log('create', 'project', $data['project_id'], 'Thêm khoản mục TMĐT: ' . $data['name'] . ' - ' . money($data['approved_amount']));
            }
        }, 'Đã lưu khoản mục TMĐT.');
        redirect('budget/projectView', ['id' => $project['id']]);
    }

    public function deleteItem(): void
    {
        require_perm('budget.manage');
        $item = $this->requireId('investment_items', 'item_id', 'khoản mục');
        if (val('SELECT 1 FROM tenders WHERE investment_item_id = ? UNION SELECT 1 FROM expenses WHERE investment_item_id = ?', [$item['id'], $item['id']])) {
            flash('danger', 'Khoản mục đã phát sinh gói thầu/chi phí, không thể xóa.');
        } else {
            q('DELETE FROM investment_items WHERE id = ?', [$item['id']]);
            Audit::log('delete', 'project', $item['project_id'], 'Xóa khoản mục TMĐT: ' . $item['name']);
        }
        redirect('budget/projectView', ['id' => $item['project_id']]);
    }

    /** Tự động tính chi phí dự phòng = tỷ lệ % × tổng các chi phí khác */
    public function contingency(): void
    {
        require_perm('budget.manage');
        $project = $this->project();
        $rate = max(0, min(30, (float) str_replace(',', '.', (string) input('rate', '5'))));
        $base = row("SELECT COALESCE(SUM(estimated_amount),0) est, COALESCE(SUM(approved_amount),0) appr FROM investment_items WHERE project_id = ? AND category <> 'contingency'", [$project['id']]);
        $data = ['estimated_amount' => round($base['est'] * $rate / 100, -3), 'approved_amount' => round($base['appr'] * $rate / 100, -3), 'name' => "Chi phí dự phòng ({$rate}%)"];
        $existing = val("SELECT id FROM investment_items WHERE project_id = ? AND category = 'contingency' ORDER BY id LIMIT 1", [$project['id']]);
        if ($existing) {
            update_row('investment_items', $data, 'id = ?', [$existing]);
        } else {
            insert('investment_items', $data + ['project_id' => $project['id'], 'category' => 'contingency']);
        }
        Audit::log('update', 'project', $project['id'], "Tính dự phòng {$rate}%: " . money($data['approved_amount']));
        flash('success', 'Đã tính chi phí dự phòng ' . $rate . '%: ' . money($data['approved_amount']));
        redirect('budget/projectView', ['id' => $project['id']]);
    }

    /** Khởi tạo nhanh cơ cấu TMĐT chuẩn */
    public function scaffold(): void
    {
        require_perm('budget.manage');
        $project = $this->project();
        if (val('SELECT 1 FROM investment_items WHERE project_id = ?', [$project['id']])) {
            flash('warning', 'Dự án đã có khoản mục TMĐT.');
        } else {
            foreach (labels('invest_category') as $cat => [$name]) {
                insert('investment_items', ['project_id' => $project['id'], 'category' => $cat, 'name' => $name]);
            }
            Audit::log('create', 'project', $project['id'], 'Khởi tạo cơ cấu TMĐT chuẩn');
            flash('success', 'Đã tạo cơ cấu TMĐT gồm 7 nhóm chi phí theo quy định. Nhập giá trị dự toán cho từng khoản mục.');
        }
        redirect('budget/projectView', ['id' => $project['id']]);
    }

    public function addExpense(): void
    {
        require_perm('budget.manage');
        $project = $this->project();
        $data = ['project_id' => $project['id'], 'investment_item_id' => input_int('investment_item_id') ?: null,
                 'contract_id' => input_int('contract_id') ?: null, 'description' => input('description'),
                 'amount' => input_money('amount'), 'expense_date' => input_date('expense_date') ?: date('Y-m-d'),
                 'created_by' => user_id(), 'created_at' => date('Y-m-d H:i:s')];
        $this->attempt(function () use ($data) {
            if (!$data['description'] || $data['amount'] <= 0) {
                $this->fail('Nhập nội dung và số tiền chi.');
            }
            if ($data['investment_item_id']) {
                $item = row('SELECT * FROM investment_items WHERE id = ?', [$data['investment_item_id']]);
                $spent = (float) val('SELECT COALESCE(SUM(amount),0) FROM expenses WHERE investment_item_id = ?', [$item['id']]);
                if ($spent + $data['amount'] > (float) $item['approved_amount']) {
                    flash('warning', 'Cảnh báo: chi phí khoản mục "' . $item['name'] . '" vượt dự toán được duyệt (' . money($spent + $data['amount']) . ' / ' . money($item['approved_amount']) . ').');
                    Notifier::send(Notifier::usersWithRole((int) val("SELECT id FROM roles WHERE code = 'manager'")), 'Cảnh báo vượt dự toán', $item['name'] . ': ' . money($spent + $data['amount']) . ' / ' . money($item['approved_amount']), url('budget/projectView', ['id' => $data['project_id']]), 'danger');
                }
            }
            $id = insert('expenses', $data);
            Audit::log('create', 'project', $data['project_id'], 'Ghi nhận chi phí #' . $id . ': ' . $data['description'] . ' - ' . money($data['amount']));
        }, 'Đã ghi nhận chi phí.');
        redirect('budget/projectView', ['id' => $project['id'], 'tab' => 'expenses']);
    }

    public function deleteExpense(): void
    {
        require_perm('budget.manage');
        $e = $this->requireId('expenses', 'expense_id', 'chi phí');
        if ($e['payment_id']) {
            flash('danger', 'Chi phí phát sinh từ giải ngân hợp đồng, không thể xóa trực tiếp.');
        } else {
            q('DELETE FROM expenses WHERE id = ?', [$e['id']]);
            Audit::log('delete', 'project', $e['project_id'], 'Xóa chi phí: ' . $e['description'] . ' - ' . money($e['amount']));
        }
        redirect('budget/projectView', ['id' => $e['project_id'], 'tab' => 'expenses']);
    }

    public function export(): void
    {
        require_perm('budget.view');
        $project = $this->project();
        $data = [];
        foreach (self::projectItems((int) $project['id']) as $i) {
            $data[] = [label('invest_category', $i['category']), $i['name'], $i['estimated_amount'], $i['approved_amount'], $i['committed'], $i['spent'], $i['approved_amount'] - $i['spent']];
        }
        Audit::log('export', 'project', $project['id'], 'Xuất báo cáo TMĐT');
        csv_download('TMDT-' . $project['code'] . '.csv', ['Nhóm chi phí', 'Khoản mục', 'Dự toán', 'Được duyệt', 'Đã cam kết (HĐ)', 'Đã chi', 'Còn lại'], $data);
    }
}
