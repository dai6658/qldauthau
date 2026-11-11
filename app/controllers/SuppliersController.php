<?php
class SuppliersController extends Controller
{
    public function index(): void
    {
        require_perm('suppliers.view');
        $where = ['1=1'];
        $params = [];
        if ($kw = input('q')) {
            $where[] = '(s.name LIKE ? OR s.tax_code LIKE ? OR s.code LIKE ? OR s.business_field LIKE ?)';
            array_push($params, "%$kw%", "%$kw%", "%$kw%", "%$kw%");
        }
        if ($st = input('status')) {
            $where[] = 's.status = ?';
            $params[] = $st;
        }
        $order = input('sort') === 'rating' ? 's.rating DESC' : 's.id DESC';
        $w = implode(' AND ', $where);
        $p = paginate("SELECT COUNT(*) FROM suppliers s WHERE $w",
            "SELECT s.*,
                (SELECT COUNT(*) FROM bids b WHERE b.supplier_id = s.id AND b.status NOT IN ('draft','withdrawn')) AS bid_count,
                (SELECT COUNT(*) FROM bids b WHERE b.supplier_id = s.id AND b.status = 'awarded') AS win_count,
                (SELECT COALESCE(SUM(value),0) FROM contracts c WHERE c.supplier_id = s.id AND c.status IN ('active','completed')) AS contract_value
             FROM suppliers s WHERE $w ORDER BY $order", $params);
        view('suppliers/index', ['title' => 'Nhà cung cấp', 'p' => $p]);
    }

    public function form(): void
    {
        require_perm('suppliers.manage');
        $s = input_int('id') ? $this->requireId('suppliers', 'id', 'nhà cung cấp') : null;
        if (is_post()) {
            $data = $this->collect();
            $id = $this->attempt(function () use ($data, $s) {
                return self::save($data, $s);
            }, 'Đã lưu nhà cung cấp.');
            if ($id) {
                redirect('suppliers/view', ['id' => $id]);
            }
            $s = array_merge($s ?? [], $data);
        }
        view('suppliers/form', ['title' => !empty($s['id']) ? 'Sửa nhà cung cấp' : 'Thêm nhà cung cấp', 's' => $s, 'action' => url('suppliers/form', ['id' => $s['id'] ?? ''])]);
    }

    /** Dùng chung cho cổng NCC */
    public static function collectFields(): array
    {
        return [
            'name' => input('name'), 'tax_code' => input('tax_code'), 'address' => input('address'),
            'phone' => input('phone'), 'email' => input('email'), 'website' => input('website'),
            'representative' => input('representative'), 'business_field' => input('business_field'),
            'established_year' => input_int('established_year') ?: null,
            'charter_capital' => input_money('charter_capital') ?: null,
            'annual_revenue' => input_money('annual_revenue') ?: null,
            'employees' => input_int('employees') ?: null,
            'bank_account' => input('bank_account'), 'bank_name' => input('bank_name'),
        ];
    }

    private function collect(): array
    {
        return self::collectFields();
    }

    public static function save(array $data, ?array $existing): int
    {
        if (!$data['name'] || !preg_match('/^\d{10}(-\d{3})?$/', (string) $data['tax_code'])) {
            throw new RuntimeException('Tên doanh nghiệp và mã số thuế hợp lệ (10 hoặc 13 số) là bắt buộc.');
        }
        if (val('SELECT 1 FROM suppliers WHERE tax_code = ? AND id <> ?', [$data['tax_code'], $existing['id'] ?? 0])) {
            throw new RuntimeException('Mã số thuế đã tồn tại trong hệ thống.');
        }
        $data['updated_at'] = date('Y-m-d H:i:s');
        if ($existing) {
            update_row('suppliers', $data, 'id = ?', [$existing['id']]);
            Audit::log('update', 'supplier', $existing['id'], 'Cập nhật hồ sơ NCC');
            return (int) $existing['id'];
        }
        $data['code'] = 'NCC-' . str_pad((string) ((int) val('SELECT COALESCE(MAX(id),0)+1 FROM suppliers')), 4, '0', STR_PAD_LEFT);
        $data['status'] = 'approved';
        $id = insert('suppliers', $data);
        Audit::log('create', 'supplier', $id, $data['name']);
        return $id;
    }

    public function view(): void
    {
        require_perm('suppliers.view');
        $s = $this->requireId('suppliers', 'id', 'nhà cung cấp');
        $caps = rows('SELECT * FROM supplier_capabilities WHERE supplier_id = ? ORDER BY cap_type, year DESC', [$s['id']]);
        $evals = rows('SELECT e.*, u.full_name, c.code AS contract_code FROM supplier_evaluations e LEFT JOIN users u ON u.id = e.evaluated_by
                       LEFT JOIN contracts c ON c.id = e.contract_id WHERE e.supplier_id = ? ORDER BY e.id DESC', [$s['id']]);
        $bids = rows("SELECT b.*, t.code AS tender_code, t.title AS tender_title, t.status AS tender_status FROM bids b JOIN tenders t ON t.id = b.tender_id
                      WHERE b.supplier_id = ? AND b.status <> 'draft' ORDER BY b.id DESC", [$s['id']]);
        $contracts = rows('SELECT c.*, (SELECT COALESCE(SUM(paid_amount),0) FROM payments p WHERE p.contract_id = c.id AND p.status = \'paid\') AS paid
                           FROM contracts c WHERE c.supplier_id = ? ORDER BY c.id DESC', [$s['id']]);
        $users = rows('SELECT username, full_name, email, last_login_at FROM users WHERE supplier_id = ?', [$s['id']]);
        $documents = DocumentService::forEntity('supplier', (int) $s['id']);
        $stats = [
            'bids' => count(array_filter($bids, fn($b) => $b['status'] !== 'withdrawn')),
            'wins' => count(array_filter($bids, fn($b) => $b['status'] === 'awarded')),
            'avg_tech' => ($t = array_filter(array_column($bids, 'tech_score'))) ? array_sum($t) / count($t) : null,
            'contract_value' => array_sum(array_column($contracts, 'value')),
            'avg' => [
                'quality' => $evals ? array_sum(array_column($evals, 'quality_score')) / count($evals) : 0,
                'schedule' => $evals ? array_sum(array_column($evals, 'schedule_score')) / count($evals) : 0,
                'price' => $evals ? array_sum(array_column($evals, 'price_score')) / count($evals) : 0,
                'service' => $evals ? array_sum(array_column($evals, 'service_score')) / count($evals) : 0,
            ],
        ];
        view('suppliers/view', compact('s', 'caps', 'evals', 'bids', 'contracts', 'users', 'documents', 'stats') + ['title' => $s['name']]);
    }

    public function setStatus(): void
    {
        require_perm('suppliers.manage');
        $s = $this->requireId('suppliers');
        $status = input('status');
        if (!isset(labels('supplier_status')[$status])) {
            abort(400, 'Trạng thái không hợp lệ');
        }
        update_row('suppliers', ['status' => $status, 'status_note' => input('status_note'), 'updated_at' => date('Y-m-d H:i:s')], 'id = ?', [$s['id']]);
        Audit::log('status', 'supplier', $s['id'], label('supplier_status', $status) . ': ' . input('status_note'));
        $users = array_column(rows('SELECT id FROM users WHERE supplier_id = ?', [$s['id']]), 'id');
        Notifier::send($users, 'Trạng thái hồ sơ nhà cung cấp: ' . label('supplier_status', $status), (string) input('status_note'), url('portal/profile'), $status === 'approved' ? 'success' : 'warning');
        flash('success', 'Đã cập nhật trạng thái nhà cung cấp.');
        redirect('suppliers/view', ['id' => $s['id']]);
    }

    public function addCapability(): void
    {
        require_perm('suppliers.manage');
        $s = $this->requireId('suppliers', 'supplier_id');
        self::insertCapability((int) $s['id']);
        redirect('suppliers/view', ['id' => $s['id'], 'tab' => 'caps']);
    }

    public static function insertCapability(int $supplierId): void
    {
        if (!input('title') || !isset(labels('cap_type')[input('cap_type')])) {
            flash('danger', 'Nhập loại và nội dung năng lực.');
            return;
        }
        insert('supplier_capabilities', [
            'supplier_id' => $supplierId, 'cap_type' => input('cap_type'), 'title' => input('title'),
            'description' => input('description'), 'value_amount' => input_money('value_amount') ?: null,
            'year' => input_int('year') ?: null, 'created_at' => date('Y-m-d H:i:s'),
        ]);
        Audit::log('update', 'supplier', $supplierId, 'Thêm năng lực: ' . input('title'));
        flash('success', 'Đã thêm hồ sơ năng lực.');
    }

    public function deleteCapability(): void
    {
        require_perm('suppliers.manage');
        $c = $this->requireId('supplier_capabilities');
        q('DELETE FROM supplier_capabilities WHERE id = ?', [$c['id']]);
        Audit::log('delete', 'supplier', $c['supplier_id'], 'Xóa năng lực: ' . $c['title']);
        redirect('suppliers/view', ['id' => $c['supplier_id'], 'tab' => 'caps']);
    }

    /** Đánh giá hiệu suất nhà cung cấp (sau hợp đồng) */
    public function evaluate(): void
    {
        require_perm('suppliers.evaluate');
        $s = $this->requireId('suppliers', 'supplier_id');
        $scores = [];
        foreach (['quality_score', 'schedule_score', 'price_score', 'service_score'] as $k) {
            $scores[$k] = max(1, min(10, input_int($k, 5)));
        }
        insert('supplier_evaluations', $scores + [
            'supplier_id' => $s['id'], 'contract_id' => input_int('contract_id') ?: null,
            'overall' => round(array_sum($scores) / 4, 2), 'comment' => input('comment'),
            'evaluated_by' => user_id(), 'created_at' => date('Y-m-d H:i:s'),
        ]);
        self::refreshRating((int) $s['id']);
        Audit::log('evaluate', 'supplier', $s['id'], 'Đánh giá hiệu suất: ' . round(array_sum($scores) / 4, 2));
        flash('success', 'Đã ghi nhận đánh giá.');
        redirect_back('suppliers/index');
    }

    public static function refreshRating(int $supplierId): void
    {
        $avg = (float) val('SELECT COALESCE(AVG(overall),0) FROM supplier_evaluations WHERE supplier_id = ?', [$supplierId]);
        update_row('suppliers', ['rating' => round($avg, 2)], 'id = ?', [$supplierId]);
    }

    public function uploadDoc(): void
    {
        require_perm('suppliers.manage');
        $s = $this->requireId('suppliers', 'supplier_id');
        $this->attempt(function () use ($s) {
            foreach (DocumentService::files('files') as $f) {
                DocumentService::create([
                    'title' => input('title') ?: $f['name'], 'category' => 'capability', 'entity_type' => 'supplier',
                    'entity_id' => $s['id'], 'confidentiality' => 'internal',
                ], $f);
            }
        }, 'Đã tải lên tài liệu.');
        redirect('suppliers/view', ['id' => $s['id'], 'tab' => 'docs']);
    }
}
