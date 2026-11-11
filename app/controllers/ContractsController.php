<?php
/**
 * Hợp đồng, đơn đặt hàng, nghiệm thu, hóa đơn, thanh toán – giải ngân và quyết toán
 */
class ContractsController extends Controller
{
    public function index(): void
    {
        require_perm('contracts.view');
        $where = ['1=1'];
        $params = [];
        if ($kw = input('q')) {
            $where[] = '(c.code LIKE ? OR c.title LIKE ? OR s.name LIKE ?)';
            array_push($params, "%$kw%", "%$kw%", "%$kw%");
        }
        foreach (['status' => 'c.status'] as $k => $col) {
            if ($v = input($k)) {
                $where[] = "$col = ?";
                $params[] = $v;
            }
        }
        foreach (['project_id' => 'c.project_id', 'supplier_id' => 'c.supplier_id'] as $k => $col) {
            if ($v = input_int($k)) {
                $where[] = "$col = ?";
                $params[] = $v;
            }
        }
        $w = implode(' AND ', $where);
        $p = paginate("SELECT COUNT(*) FROM contracts c JOIN suppliers s ON s.id = c.supplier_id WHERE $w",
            "SELECT c.*, s.name AS supplier_name, p.code AS project_code, t.code AS tender_code,
                (SELECT COALESCE(SUM(paid_amount),0) FROM payments x WHERE x.contract_id = c.id AND x.status = 'paid') AS paid,
                (SELECT COUNT(*) FROM invoices i WHERE i.contract_id = c.id AND i.status = 'submitted') AS pending_invoices
             FROM contracts c JOIN suppliers s ON s.id = c.supplier_id JOIN projects p ON p.id = c.project_id
             LEFT JOIN tenders t ON t.id = c.tender_id WHERE $w ORDER BY c.id DESC", $params);
        view('contracts/index', ['title' => 'Hợp đồng & thanh toán', 'p' => $p,
            'projects' => pairs('SELECT id, code FROM projects ORDER BY id DESC'),
            'suppliers' => pairs('SELECT id, name FROM suppliers ORDER BY name')]);
    }

    private function find(string $param = 'id'): array
    {
        $c = row('SELECT c.*, s.name AS supplier_name, s.tax_code, s.bank_account, s.bank_name, s.representative,
                    p.code AS project_code, p.name AS project_name, t.code AS tender_code, t.title AS tender_title, t.investment_item_id,
                    u.full_name AS creator
                  FROM contracts c JOIN suppliers s ON s.id = c.supplier_id JOIN projects p ON p.id = c.project_id
                  LEFT JOIN tenders t ON t.id = c.tender_id LEFT JOIN users u ON u.id = c.created_by WHERE c.id = ?', [input_int($param)]);
        if (!$c) {
            abort(404, 'Không tìm thấy hợp đồng.');
        }
        return $c;
    }

    private function back(array $c, string $tab = ''): void
    {
        redirect('contracts/view', $tab ? ['id' => $c['id'], 'tab' => $tab] : ['id' => $c['id']]);
    }

    public function view(): void
    {
        require_perm('contracts.view');
        $c = $this->find();
        $id = (int) $c['id'];
        $payments = rows('SELECT p.*, i.invoice_no, a.title AS acceptance_title, a.result AS acceptance_result, u.full_name
            FROM payments p LEFT JOIN invoices i ON i.id = p.invoice_id LEFT JOIN acceptances a ON a.id = p.acceptance_id
            LEFT JOIN users u ON u.id = p.created_by WHERE p.contract_id = ? ORDER BY p.due_date IS NULL, p.due_date, p.id', [$id]);
        $acceptances = rows('SELECT a.*, u.full_name FROM acceptances a LEFT JOIN users u ON u.id = a.created_by WHERE a.contract_id = ? ORDER BY a.accept_date', [$id]);
        $orders = rows('SELECT o.*, u.full_name FROM purchase_orders o LEFT JOIN users u ON u.id = o.created_by WHERE o.contract_id = ? ORDER BY o.id DESC', [$id]);
        $invoices = rows('SELECT i.*, u.full_name AS submitter, r.full_name AS reviewer FROM invoices i LEFT JOIN users u ON u.id = i.submitted_by
            LEFT JOIN users r ON r.id = i.reviewed_by WHERE i.contract_id = ? ORDER BY i.id DESC', [$id]);
        $documents = DocumentService::forEntity('contract', $id);
        $stats = [
            'planned' => array_sum(array_column($payments, 'amount')),
            'paid' => array_sum(array_map(fn($p) => $p['status'] === 'paid' ? (float) $p['paid_amount'] : 0, $payments)),
            'invoiced' => array_sum(array_map(fn($i) => $i['status'] !== 'rejected' ? (float) $i['amount'] + (float) $i['vat_amount'] : 0, $invoices)),
            'ordered' => array_sum(array_map(fn($o) => $o['status'] !== 'cancelled' ? (float) $o['amount'] : 0, $orders)),
        ];
        view('contracts/view', compact('c', 'payments', 'acceptances', 'orders', 'invoices', 'documents', 'stats') + [
            'title' => 'Hợp đồng ' . $c['code'],
            'history' => Workflow::history('contract', $id),
            'paymentHistory' => rows("SELECT a.*, u.full_name, r.name AS role_name FROM approvals a LEFT JOIN users u ON u.id = a.acted_by
                LEFT JOIN roles r ON r.id = a.role_id WHERE a.entity_type = 'payment' AND a.entity_id IN (SELECT id FROM payments WHERE contract_id = ?)
                AND a.status <> 'cancelled' ORDER BY a.id DESC", [$id]),
            'logs' => Audit::forEntity('contract', $id, 50),
            'evaluated' => (bool) val('SELECT 1 FROM supplier_evaluations WHERE contract_id = ?', [$id]),
        ]);
    }

    public function edit(): void
    {
        require_perm('contracts.manage');
        $c = $this->find();
        if ($c['status'] !== 'draft') {
            flash('warning', 'Chỉ sửa được hợp đồng ở trạng thái Dự thảo.');
            $this->back($c);
        }
        $data = ['title' => input('title') ?: $c['title'], 'value' => input_money('value') ?: $c['value'],
                 'sign_date' => input_date('sign_date'), 'start_date' => input_date('start_date'), 'end_date' => input_date('end_date'),
                 'payment_terms' => input('payment_terms'), 'updated_at' => date('Y-m-d H:i:s')];
        if ($c['bid_id'] && $data['value'] > (float) val('SELECT budget FROM tenders WHERE id = ?', [$c['tender_id']])) {
            flash('danger', 'Giá trị hợp đồng không được vượt giá gói thầu.');
            $this->back($c);
        }
        update_row('contracts', $data, 'id = ?', [$c['id']]);
        $changes = [];
        foreach (['value', 'sign_date', 'end_date'] as $k) {
            if ((string) $c[$k] !== (string) $data[$k]) {
                $changes[$k] = [$c[$k], $data[$k]];
            }
        }
        Audit::log('update', 'contract', $c['id'], $changes ?: 'Cập nhật hợp đồng');
        flash('success', 'Đã cập nhật hợp đồng.');
        $this->back($c);
    }

    public function submitApproval(): void
    {
        require_perm('contracts.manage');
        $c = $this->find();
        if ($c['status'] !== 'draft') {
            flash('warning', 'Hợp đồng không ở trạng thái Dự thảo.');
        } elseif (!$c['sign_date'] || !$c['end_date']) {
            flash('danger', 'Cần nhập ngày ký và ngày kết thúc trước khi trình duyệt.');
        } else {
            update_row('contracts', ['status' => 'pending_approval'], 'id = ?', [$c['id']]);
            $needs = Workflow::start('contract', (int) $c['id'], 'Hợp đồng ' . $c['code'] . ' - ' . $c['supplier_name'] . ' - ' . money($c['value']));
            flash('success', $needs ? 'Đã trình phê duyệt hợp đồng.' : 'Hợp đồng được kích hoạt (không có bước phê duyệt).');
        }
        $this->back($c);
    }

    // ------------------------------------------------------------ Đơn đặt hàng
    public function addOrder(): void
    {
        require_perm('contracts.manage');
        $c = $this->find('contract_id');
        if ($c['status'] !== 'active') {
            flash('danger', 'Chỉ phát hành đơn hàng cho hợp đồng đang hiệu lực.');
            $this->back($c, 'orders');
        }
        $amount = input_money('amount');
        $ordered = (float) val("SELECT COALESCE(SUM(amount),0) FROM purchase_orders WHERE contract_id = ? AND status <> 'cancelled'", [$c['id']]);
        if ($amount <= 0 || $ordered + $amount > (float) $c['value']) {
            flash('danger', 'Giá trị đơn hàng không hợp lệ hoặc vượt giá trị hợp đồng (đã đặt ' . money($ordered) . ').');
            $this->back($c, 'orders');
        }
        $id = insert('purchase_orders', [
            'code' => gen_code('PO', 'purchase_orders'), 'contract_id' => $c['id'], 'order_date' => input_date('order_date') ?: date('Y-m-d'),
            'delivery_date' => input_date('delivery_date'), 'amount' => $amount, 'description' => input('description'),
            'status' => 'sent', 'created_by' => user_id(), 'created_at' => date('Y-m-d H:i:s'),
        ]);
        Audit::log('create', 'contract', $c['id'], 'Phát hành đơn hàng #' . $id . ' - ' . money($amount));
        Notifier::send(Notifier::supplierUsers([(int) $c['supplier_id']]), 'Đơn đặt hàng mới cần xác nhận', 'Hợp đồng ' . $c['code'] . ' - ' . money($amount), url('portal/orders'), 'warning');
        flash('success', 'Đã phát hành đơn hàng tới nhà cung cấp.');
        $this->back($c, 'orders');
    }

    public function orderStatus(): void
    {
        require_perm('contracts.manage');
        $o = $this->requireId('purchase_orders', 'order_id', 'đơn hàng');
        $status = input('status');
        if (in_array($status, ['delivered', 'cancelled'], true) && $o['status'] !== 'cancelled') {
            update_row('purchase_orders', ['status' => $status], 'id = ?', [$o['id']]);
            Audit::log('status', 'contract', $o['contract_id'], 'Đơn hàng ' . $o['code'] . ': ' . label('po_status', $status));
        }
        redirect('contracts/view', ['id' => $o['contract_id'], 'tab' => 'orders']);
    }

    // ------------------------------------------------------------ Nghiệm thu
    public function addAcceptance(): void
    {
        require_perm('contracts.manage');
        $c = $this->find('contract_id');
        $this->attempt(function () use ($c) {
            if (!input('title') || !isset(labels('acceptance_result')[input('result')])) {
                $this->fail('Nhập nội dung nghiệm thu.');
            }
            $docId = null;
            if ($files = DocumentService::files('file')) {
                $docId = DocumentService::create([
                    'title' => 'Biên bản nghiệm thu: ' . input('title'), 'category' => 'acceptance', 'entity_type' => 'contract',
                    'entity_id' => $c['id'], 'project_id' => $c['project_id'], 'confidentiality' => 'internal',
                ], $files[0]);
            }
            $id = insert('acceptances', [
                'contract_id' => $c['id'], 'title' => input('title'), 'accept_date' => input_date('accept_date') ?: date('Y-m-d'),
                'result' => input('result'), 'note' => input('note'), 'document_id' => $docId,
                'created_by' => user_id(), 'created_at' => date('Y-m-d H:i:s'),
            ]);
            Audit::log('accept', 'contract', $c['id'], 'Nghiệm thu #' . $id . ': ' . input('title') . ' - ' . label('acceptance_result', input('result')));
            Notifier::send(Notifier::supplierUsers([(int) $c['supplier_id']]), 'Kết quả nghiệm thu: ' . label('acceptance_result', input('result')), $c['code'] . ' - ' . input('title'), url('portal/contracts'));
        }, 'Đã lập biên bản nghiệm thu.');
        $this->back($c, 'acceptance');
    }

    // ------------------------------------------------------------ Hóa đơn điện tử
    public function reviewInvoice(): void
    {
        require_perm('payments.manage');
        $inv = $this->requireId('invoices', 'invoice_id', 'hóa đơn');
        $status = input('status');
        if ($inv['status'] !== 'submitted' || !in_array($status, ['verified', 'rejected'], true)) {
            flash('danger', 'Hóa đơn đã được xử lý.');
        } elseif ($status === 'rejected' && !input('review_note')) {
            flash('danger', 'Nhập lý do từ chối hóa đơn.');
        } else {
            update_row('invoices', ['status' => $status, 'review_note' => input('review_note'), 'reviewed_by' => user_id(), 'reviewed_at' => date('Y-m-d H:i:s')], 'id = ?', [$inv['id']]);
            Audit::log('status', 'invoice', $inv['id'], 'Hóa đơn ' . $inv['invoice_no'] . ': ' . label('invoice_status', $status) . ' ' . input('review_note'));
            Notifier::send(Notifier::supplierUsers([(int) $inv['supplier_id']]), 'Hóa đơn ' . $inv['invoice_no'] . ': ' . label('invoice_status', $status), (string) input('review_note'), url('portal/invoices'), $status === 'verified' ? 'success' : 'danger');
            flash('success', 'Đã cập nhật hóa đơn.');
        }
        redirect('contracts/view', ['id' => $inv['contract_id'], 'tab' => 'invoices']);
    }

    // ------------------------------------------------------------ Thanh toán & giải ngân
    public function addPayment(): void
    {
        require_perm('payments.manage');
        $c = $this->find('contract_id');
        $amount = input_money('amount');
        $planned = (float) val("SELECT COALESCE(SUM(amount),0) FROM payments WHERE contract_id = ? AND status <> 'rejected'", [$c['id']]);
        if (!in_array($c['status'], ['active', 'pending_approval', 'draft'], true)) {
            flash('danger', 'Hợp đồng đã kết thúc.');
        } elseif (!input('milestone') || $amount <= 0) {
            flash('danger', 'Nhập nội dung đợt thanh toán và số tiền.');
        } elseif ($planned + $amount > (float) $c['value']) {
            flash('danger', 'Tổng các đợt thanh toán (' . money($planned + $amount) . ') vượt giá trị hợp đồng.');
        } else {
            insert('payments', ['contract_id' => $c['id'], 'milestone' => input('milestone'), 'amount' => $amount,
                'due_date' => input_date('due_date'), 'note' => input('note'), 'status' => 'planned', 'created_by' => user_id(), 'created_at' => date('Y-m-d H:i:s')]);
            Audit::log('create', 'contract', $c['id'], 'Thêm đợt thanh toán: ' . input('milestone') . ' - ' . money($amount));
            flash('success', 'Đã thêm đợt thanh toán.');
        }
        $this->back($c, 'payments');
    }

    public function deletePayment(): void
    {
        require_perm('payments.manage');
        $p = $this->requireId('payments', 'payment_id', 'đợt thanh toán');
        if (in_array($p['status'], ['planned', 'rejected'], true)) {
            q('DELETE FROM payments WHERE id = ?', [$p['id']]);
            Audit::log('delete', 'contract', $p['contract_id'], 'Xóa đợt thanh toán: ' . $p['milestone']);
        }
        redirect('contracts/view', ['id' => $p['contract_id'], 'tab' => 'payments']);
    }

    /** Lập hồ sơ đề nghị thanh toán & trình duyệt chi */
    public function requestPayment(): void
    {
        require_perm('payments.manage');
        $p = $this->requireId('payments', 'payment_id', 'đợt thanh toán');
        $c = row('SELECT c.*, s.name AS supplier_name FROM contracts c JOIN suppliers s ON s.id = c.supplier_id WHERE c.id = ?', [$p['contract_id']]);
        $invoiceId = input_int('invoice_id') ?: null;
        $acceptId = input_int('acceptance_id') ?: null;
        $invoice = $invoiceId ? row('SELECT * FROM invoices WHERE id = ? AND contract_id = ?', [$invoiceId, $c['id']]) : null;
        $errors = [];
        if (!in_array($c['status'], ['active', 'completed'], true)) {
            $errors[] = 'Hợp đồng chưa có hiệu lực hoặc đã chấm dứt.';
        }
        if (!in_array($p['status'], ['planned', 'rejected'], true)) {
            $errors[] = 'Đợt thanh toán đã được trình.';
        }
        if (setting('require_invoice_for_payment', '1') === '1' && (!$invoice || $invoice['status'] !== 'verified')) {
            $errors[] = 'Cần gắn hóa đơn đã được kiểm tra hợp lệ.';
        }
        if ($invoice && (float) $invoice['amount'] + (float) $invoice['vat_amount'] < (float) $p['amount']) {
            $errors[] = 'Giá trị hóa đơn nhỏ hơn số tiền đề nghị thanh toán.';
        }
        if ($acceptId && val('SELECT result FROM acceptances WHERE id = ?', [$acceptId]) === 'failed') {
            $errors[] = 'Biên bản nghiệm thu không đạt.';
        }
        if ($errors) {
            flash('danger', 'Chưa thể trình duyệt chi: ' . implode(' ', $errors));
        } else {
            update_row('payments', ['status' => 'pending_approval', 'invoice_id' => $invoiceId, 'acceptance_id' => $acceptId], 'id = ?', [$p['id']]);
            $needs = Workflow::start('payment', (int) $p['id'], 'Thanh toán ' . $c['code'] . ' - ' . $p['milestone'] . ' - ' . money($p['amount']) . ' cho ' . $c['supplier_name']);
            flash('success', $needs ? 'Đã trình duyệt chi.' : 'Khoản thanh toán được duyệt tự động.');
        }
        redirect('contracts/view', ['id' => $p['contract_id'], 'tab' => 'payments']);
    }

    /** Giải ngân: ghi nhận chi trả, tự động hạch toán vào chi phí thực tế của dự án */
    public function disburse(): void
    {
        require_perm('payments.manage');
        $p = $this->requireId('payments', 'payment_id', 'đợt thanh toán');
        $c = row('SELECT c.*, t.investment_item_id FROM contracts c LEFT JOIN tenders t ON t.id = c.tender_id WHERE c.id = ?', [$p['contract_id']]);
        $amount = input_money('paid_amount') ?: (float) $p['amount'];
        if ($p['status'] !== 'approved') {
            flash('danger', 'Khoản thanh toán chưa được duyệt chi.');
        } elseif ($amount <= 0 || $amount > (float) $p['amount']) {
            flash('danger', 'Số tiền giải ngân không hợp lệ (tối đa ' . money($p['amount']) . ').');
        } else {
            transaction(function () use ($p, $c, $amount) {
                $paidAt = input_date('paid_at') ?: date('Y-m-d');
                update_row('payments', ['status' => 'paid', 'paid_amount' => $amount, 'paid_at' => $paidAt, 'note' => input('note') ?: $p['note']], 'id = ?', [$p['id']]);
                insert('expenses', [
                    'project_id' => $c['project_id'], 'investment_item_id' => $c['investment_item_id'], 'contract_id' => $c['id'],
                    'payment_id' => $p['id'], 'description' => 'Giải ngân HĐ ' . $c['code'] . ': ' . $p['milestone'],
                    'amount' => $amount, 'expense_date' => $paidAt, 'created_by' => user_id(), 'created_at' => date('Y-m-d H:i:s'),
                ]);
                if ($p['invoice_id']) {
                    update_row('invoices', ['status' => 'paid'], 'id = ?', [$p['invoice_id']]);
                }
                Audit::log('disburse', 'payment', $p['id'], 'Giải ngân ' . money($amount) . ' - HĐ ' . $c['code'] . ' - ' . $p['milestone']);
            });
            Notifier::send(Notifier::supplierUsers([(int) $c['supplier_id']]), 'Đã thanh toán ' . money($amount), 'Hợp đồng ' . $c['code'] . ' - ' . $p['milestone'], url('portal/contracts'), 'success');
            flash('success', 'Đã giải ngân ' . money($amount) . ' và hạch toán vào chi phí dự án.');
        }
        redirect('contracts/view', ['id' => $p['contract_id'], 'tab' => 'payments']);
    }

    /** Quyết toán hợp đồng */
    public function settle(): void
    {
        require_perm('payments.manage');
        $c = $this->find();
        $paid = (float) val("SELECT COALESCE(SUM(paid_amount),0) FROM payments WHERE contract_id = ? AND status = 'paid'", [$c['id']]);
        $pending = (int) val("SELECT COUNT(*) FROM payments WHERE contract_id = ? AND status IN ('pending_approval','approved')", [$c['id']]);
        $settled = input_money('settled_amount');
        $ok = $this->attempt(function () use ($c, $paid, $pending, $settled) {
            if ($c['status'] !== 'active') {
                $this->fail('Chỉ quyết toán hợp đồng đang hiệu lực.');
            }
            if ($pending) {
                $this->fail('Còn ' . $pending . ' khoản thanh toán đang xử lý.');
            }
            if (!val("SELECT 1 FROM acceptances WHERE contract_id = ? AND result IN ('passed','conditional')", [$c['id']])) {
                $this->fail('Chưa có biên bản nghiệm thu đạt.');
            }
            if ($settled <= 0 || $settled > (float) $c['value']) {
                $this->fail('Giá trị quyết toán phải > 0 và không vượt giá trị hợp đồng.');
            }
            if ($settled < $paid) {
                $this->fail('Giá trị quyết toán nhỏ hơn số đã thanh toán (' . money($paid) . ') — cần thu hồi trước khi quyết toán.');
            }
            update_row('contracts', ['status' => 'completed', 'settled_amount' => $settled, 'settled_at' => date('Y-m-d H:i:s'),
                'settlement_note' => input('settlement_note'), 'updated_at' => date('Y-m-d H:i:s')], 'id = ?', [$c['id']]);
            q("UPDATE payments SET status = 'rejected', note = CONCAT(COALESCE(note,''), ' [Hủy do quyết toán]') WHERE contract_id = ? AND status = 'planned'", [$c['id']]);
            Audit::log('settle', 'contract', $c['id'], 'Quyết toán ' . money($settled) . ', đã thanh toán ' . money($paid) . ', còn phải trả ' . money($settled - $paid));
        }, 'Đã quyết toán hợp đồng.' . ($settled - $paid > 0 ? ' Còn phải thanh toán ' . money($settled - $paid) . ' — hãy thêm đợt thanh toán quyết toán.' : ''));
        if ($ok) {
            Notifier::send(Notifier::supplierUsers([(int) $c['supplier_id']]), 'Hợp đồng ' . $c['code'] . ' đã được quyết toán', 'Giá trị quyết toán: ' . money($settled), url('portal/contracts'), 'success');
            if (can('suppliers.evaluate')) {
                flash('info', 'Hãy đánh giá hiệu suất nhà cung cấp cho hợp đồng này (tab Quyết toán).');
            }
        }
        $this->back($c, 'settlement');
    }

    /** Thanh toán phần còn lại sau quyết toán */
    public function finalPayment(): void
    {
        require_perm('payments.manage');
        $c = $this->find();
        $paid = (float) val("SELECT COALESCE(SUM(paid_amount),0) FROM payments WHERE contract_id = ? AND status = 'paid'", [$c['id']]);
        $open = (float) val("SELECT COALESCE(SUM(amount),0) FROM payments WHERE contract_id = ? AND status IN ('pending_approval','approved')", [$c['id']]);
        $remain = (float) $c['settled_amount'] - $paid - $open;
        if ($c['status'] === 'completed' && $remain > 0) {
            insert('payments', ['contract_id' => $c['id'], 'milestone' => 'Thanh toán quyết toán', 'amount' => $remain,
                'due_date' => date('Y-m-d', strtotime('+15 days')), 'status' => 'planned', 'created_by' => user_id(), 'created_at' => date('Y-m-d H:i:s')]);
            Audit::log('create', 'contract', $c['id'], 'Tạo đợt thanh toán quyết toán ' . money($remain));
            flash('success', 'Đã tạo đợt thanh toán quyết toán ' . money($remain));
        }
        $this->back($c, 'payments');
    }

    public function terminate(): void
    {
        require_perm('contracts.manage');
        $c = $this->find();
        if (!input('reason') || !in_array($c['status'], ['draft', 'pending_approval', 'active'], true)) {
            flash('danger', 'Nhập lý do chấm dứt hợp đồng.');
        } else {
            update_row('contracts', ['status' => 'terminated', 'settlement_note' => 'Chấm dứt: ' . input('reason'), 'updated_at' => date('Y-m-d H:i:s')], 'id = ?', [$c['id']]);
            q("UPDATE approvals SET status = 'cancelled' WHERE entity_type = 'contract' AND entity_id = ? AND status IN ('waiting','pending')", [$c['id']]);
            Audit::log('status', 'contract', $c['id'], 'Chấm dứt hợp đồng: ' . input('reason'));
            Notifier::send(Notifier::supplierUsers([(int) $c['supplier_id']]), 'Hợp đồng ' . $c['code'] . ' bị chấm dứt', (string) input('reason'), url('portal/contracts'), 'danger');
            flash('success', 'Đã chấm dứt hợp đồng.');
        }
        $this->back($c);
    }

    public function export(): void
    {
        require_perm('contracts.view');
        $data = rows("SELECT c.code, c.title, s.name, p.code pc, c.value, c.sign_date, c.end_date,
            (SELECT COALESCE(SUM(paid_amount),0) FROM payments x WHERE x.contract_id = c.id AND x.status = 'paid') paid, c.settled_amount, c.status
            FROM contracts c JOIN suppliers s ON s.id = c.supplier_id JOIN projects p ON p.id = c.project_id ORDER BY c.id");
        foreach ($data as &$r) {
            $r['status'] = label('contract_status', $r['status']);
        }
        unset($r);
        Audit::log('export', 'contract', null, 'Xuất danh sách hợp đồng');
        csv_download('hop-dong.csv', ['Số HĐ', 'Tên', 'Nhà thầu', 'Dự án', 'Giá trị', 'Ngày ký', 'Ngày kết thúc', 'Đã thanh toán', 'Quyết toán', 'Trạng thái'], $data);
    }
}
