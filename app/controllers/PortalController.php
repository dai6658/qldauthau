<?php
/**
 * Cổng thông tin nhà cung cấp: hồ sơ năng lực, gói thầu, nộp HSDT/báo giá trực tuyến,
 * theo dõi kết quả, hợp đồng, xác nhận đơn hàng, gửi hóa đơn điện tử
 */
class PortalController extends Controller
{
    private int $sid;
    private array $supplier;

    public function __construct()
    {
        if (!is_supplier_user() || !Access::supplierId()) {
            abort(403, 'Chức năng dành cho tài khoản nhà cung cấp.');
        }
        $this->sid = (int) Access::supplierId();
        $supplier = row('SELECT * FROM suppliers WHERE id = ?', [$this->sid]);
        if (!$supplier) {
            abort(404, 'Không tìm thấy hồ sơ nhà cung cấp.');
        }
        $this->supplier = $supplier;
    }

    private function approved(): bool
    {
        return $this->supplier['status'] === 'approved';
    }

    private function visibleTenders(): array
    {
        $list = rows("SELECT t.*, p.name AS project_name,
                (SELECT status FROM bids b WHERE b.tender_id = t.id AND b.supplier_id = ?) AS my_bid_status
            FROM tenders t JOIN projects p ON p.id = t.project_id
            WHERE t.status IN ('published','closed','evaluating','awarded') ORDER BY t.status = 'published' DESC, t.close_at DESC", [$this->sid]);
        return array_values(array_filter($list, fn($t) => Access::supplierCanSeeTender($t, $this->sid)));
    }

    public function index(): void
    {
        $tenders = array_filter($this->visibleTenders(), fn($t) => $t['status'] === 'published');
        $bidStats = pairs('SELECT status, COUNT(*) FROM bids WHERE supplier_id = ? GROUP BY status', [$this->sid]);
        $contracts = rows("SELECT c.*, (SELECT COALESCE(SUM(paid_amount),0) FROM payments p WHERE p.contract_id = c.id AND p.status = 'paid') AS paid
            FROM contracts c WHERE c.supplier_id = ? AND c.status IN ('active','completed') ORDER BY c.id DESC LIMIT 5", [$this->sid]);
        $pendingOrders = (int) val("SELECT COUNT(*) FROM purchase_orders o JOIN contracts c ON c.id = o.contract_id WHERE c.supplier_id = ? AND o.status = 'sent'", [$this->sid]);
        $invoiceStats = pairs('SELECT status, COUNT(*) FROM invoices WHERE supplier_id = ? GROUP BY status', [$this->sid]);
        $notifications = rows('SELECT * FROM notifications WHERE user_id = ? ORDER BY id DESC LIMIT 8', [user_id()]);
        view('portal/index', ['title' => 'Cổng nhà cung cấp', 's' => $this->supplier] + compact('tenders', 'bidStats', 'contracts', 'pendingOrders', 'invoiceStats', 'notifications'));
    }

    // ------------------------------------------------------------ Hồ sơ doanh nghiệp
    public function profile(): void
    {
        $s = $this->supplier;
        if (is_post()) {
            $data = SuppliersController::collectFields();
            $data['tax_code'] = $s['tax_code'];
            $ok = $this->attempt(function () use ($data, $s) {
                SuppliersController::save($data, $s);
            }, 'Đã cập nhật hồ sơ doanh nghiệp.');
            if ($ok && $s['status'] !== 'approved') {
                Notifier::send(Notifier::usersWithPermission('suppliers.manage'), 'NCC cập nhật hồ sơ chờ thẩm định', $s['name'], url('suppliers/view', ['id' => $s['id']]), 'warning');
            }
            redirect('portal/profile');
        }
        view('portal/profile', [
            'title' => 'Hồ sơ doanh nghiệp', 's' => $s,
            'caps' => rows('SELECT * FROM supplier_capabilities WHERE supplier_id = ? ORDER BY cap_type, year DESC', [$s['id']]),
            'documents' => DocumentService::forEntity('supplier', (int) $s['id']),
            'evals' => rows('SELECT overall, created_at FROM supplier_evaluations WHERE supplier_id = ? ORDER BY id DESC LIMIT 5', [$s['id']]),
        ]);
    }

    public function addCapability(): void
    {
        SuppliersController::insertCapability($this->sid);
        redirect('portal/profile', ['tab' => 'caps']);
    }

    public function deleteCapability(): void
    {
        q('DELETE FROM supplier_capabilities WHERE id = ? AND supplier_id = ?', [input_int('id'), $this->sid]);
        Audit::log('delete', 'supplier', $this->sid, 'NCC xóa năng lực #' . input_int('id'));
        redirect('portal/profile', ['tab' => 'caps']);
    }

    public function uploadDoc(): void
    {
        $this->attempt(function () {
            $files = DocumentService::files('files');
            if (!$files) {
                $this->fail('Chọn tệp để tải lên.');
            }
            foreach ($files as $f) {
                DocumentService::create([
                    'title' => input('title') ?: $f['name'], 'category' => isset(labels('doc_category')[input('category')]) ? input('category') : 'capability',
                    'entity_type' => 'supplier', 'entity_id' => $this->sid, 'confidentiality' => 'internal', 'department_id' => null, 'company_id' => null,
                ], $f);
            }
        }, 'Đã tải lên hồ sơ năng lực.');
        redirect('portal/profile', ['tab' => 'docs']);
    }

    // ------------------------------------------------------------ Gói thầu & HSDT
    public function tenders(): void
    {
        view('portal/tenders', ['title' => 'Gói thầu', 'tenders' => $this->visibleTenders(), 's' => $this->supplier]);
    }

    private function tenderOrFail(): array
    {
        $t = Access::findTender(input_int('id') ?: input_int('tender_id'));
        if (!$t || !Access::supplierCanSeeTender($t, $this->sid)) {
            abort(404, 'Gói thầu không tồn tại hoặc bạn không được mời tham gia.');
        }
        return $t;
    }

    private function canBid(array $t): bool
    {
        return $this->approved() && $t['status'] === 'published' && strtotime($t['close_at']) > time();
    }

    public function tender(): void
    {
        $t = $this->tenderOrFail();
        $bid = row('SELECT * FROM bids WHERE tender_id = ? AND supplier_id = ?', [$t['id'], $this->sid]);
        $bidItems = $bid ? pairs('SELECT tender_item_id, unit_price FROM bid_items WHERE bid_id = ?', [$bid['id']]) : [];
        $docs = array_values(array_filter(DocumentService::forEntity('tender', (int) $t['id']), fn($d) => $d['confidentiality'] === 'public'));
        Audit::log('view', 'tender', $t['id'], 'NCC xem HSMT');
        view('portal/tender', [
            'title' => $t['code'], 't' => $t, 'bid' => $bid, 'bidItems' => $bidItems, 'docs' => $docs,
            'items' => rows('SELECT * FROM tender_items WHERE tender_id = ? ORDER BY id', [$t['id']]),
            'criteria' => rows('SELECT name, description, max_score FROM tender_criteria WHERE tender_id = ? ORDER BY sort_order, id', [$t['id']]),
            'bidDocs' => $bid ? DocumentService::forEntity('bid', (int) $bid['id']) : [],
            'canBid' => $this->canBid($t), 's' => $this->supplier,
            'result' => $t['status'] === 'awarded' ? row('SELECT s.name, b.total_price FROM bids b JOIN suppliers s ON s.id = b.supplier_id WHERE b.id = ?', [$t['awarded_bid_id']]) : null,
        ]);
    }

    /** Lưu nháp hoặc nộp HSDT (có thể sửa đổi trước thời điểm đóng thầu) */
    public function saveBid(): void
    {
        $t = $this->tenderOrFail();
        if (!$this->canBid($t)) {
            flash('danger', $this->approved() ? 'Đã hết thời gian nộp hồ sơ dự thầu.' : 'Hồ sơ doanh nghiệp chưa được duyệt, chưa thể dự thầu.');
            redirect('portal/tender', ['id' => $t['id']]);
        }
        $submit = input('action') === 'submit';
        $items = rows('SELECT * FROM tender_items WHERE tender_id = ?', [$t['id']]);
        $prices = (array) ($_POST['unit_price'] ?? []);
        $ok = $this->attempt(function () use ($t, $submit, $items, $prices) {
            $total = 0.0;
            $lines = [];
            foreach ($items as $it) {
                $unit = (float) preg_replace('/[^\d]/', '', (string) ($prices[$it['id']] ?? '0'));
                if ($submit && $unit <= 0) {
                    $this->fail('Vui lòng chào giá cho tất cả hạng mục (thiếu: ' . $it['name'] . ').');
                }
                $amount = round($unit * (float) $it['quantity']);
                $total += $amount;
                $lines[] = [$it['id'], $unit, $amount, $_POST['item_note'][$it['id']] ?? null];
            }
            $data = [
                'total_price' => $total, 'validity_days' => input_int('validity_days') ?: null,
                'delivery_days' => input_int('delivery_days') ?: null, 'warranty_months' => input_int('warranty_months') ?: null,
                'technical_proposal' => input('technical_proposal'), 'updated_at' => date('Y-m-d H:i:s'),
            ];
            if ($submit && (!$data['validity_days'] || !$data['technical_proposal'])) {
                $this->fail('Cần nhập thời gian hiệu lực HSDT và đề xuất kỹ thuật.');
            }
            return transaction(function () use ($t, $submit, $data, $lines) {
                $bid = row('SELECT * FROM bids WHERE tender_id = ? AND supplier_id = ? FOR UPDATE', [$t['id'], $this->sid]);
                $wasSubmitted = $bid && $bid['status'] === 'submitted';
                if ($submit) {
                    $data['status'] = 'submitted';
                    $data['submitted_at'] = date('Y-m-d H:i:s');
                } elseif (!$wasSubmitted) {
                    $data['status'] = 'draft';
                }
                if ($bid) {
                    update_row('bids', $data, 'id = ?', [$bid['id']]);
                    $bidId = (int) $bid['id'];
                } else {
                    $bidId = insert('bids', $data + ['code' => gen_code('HSDT', 'bids'), 'tender_id' => $t['id'], 'supplier_id' => $this->sid, 'created_at' => date('Y-m-d H:i:s')]);
                }
                foreach ($lines as [$itemId, $unit, $amount, $note]) {
                    q('INSERT INTO bid_items (bid_id, tender_item_id, unit_price, amount, note) VALUES (?, ?, ?, ?, ?)
                       ON DUPLICATE KEY UPDATE unit_price = VALUES(unit_price), amount = VALUES(amount), note = VALUES(note)', [$bidId, $itemId, $unit, $amount, $note]);
                }
                foreach (DocumentService::files('files') as $f) {
                    DocumentService::create([
                        'title' => 'HSDT ' . $t['code'] . ' - ' . $f['name'], 'category' => isset(labels('doc_category')[input('doc_category')]) ? input('doc_category') : 'bid_doc',
                        'entity_type' => 'bid', 'entity_id' => $bidId, 'project_id' => $t['project_id'],
                        'department_id' => null, 'company_id' => null, 'confidentiality' => 'internal',
                    ], $f);
                }
                // Nội dung HSDT chỉ ghi tổng quát để giữ bí mật giá đến khi mở thầu
                Audit::log($submit ? 'bid_submit' : 'update', 'bid', $bidId, $submit ? ($wasSubmitted ? 'Sửa đổi, nộp lại HSDT' : 'Nộp HSDT') . ' gói ' . $t['code'] : 'Lưu nháp HSDT');
                return ['id' => $bidId, 'resubmit' => $wasSubmitted];
            });
        }, $submit ? 'Đã nộp hồ sơ dự thầu thành công. HSDT được niêm phong đến thời điểm đóng thầu.' : 'Đã lưu nháp hồ sơ dự thầu.');
        if ($ok && $submit) {
            Notifier::send(Notifier::tenderTeam($t), ($ok['resubmit'] ? 'HSDT được sửa đổi: ' : 'Nhận HSDT mới: ') . $t['code'], $this->supplier['name'] . ' đã nộp hồ sơ lúc ' . date('H:i d/m/Y'), url('tenders/view', ['id' => $t['id'], 'tab' => 'bids']));
            Notifier::send([user_id()], 'Xác nhận nộp HSDT ' . $t['code'], 'Hệ thống đã ghi nhận HSDT lúc ' . date('H:i:s d/m/Y') . '.', url('portal/tender', ['id' => $t['id']]), 'success');
        }
        redirect('portal/tender', ['id' => $t['id']]);
    }

    public function withdrawBid(): void
    {
        $t = $this->tenderOrFail();
        $bid = row('SELECT * FROM bids WHERE tender_id = ? AND supplier_id = ?', [$t['id'], $this->sid]);
        if ($bid && $this->canBid($t) && in_array($bid['status'], ['draft', 'submitted'], true)) {
            update_row('bids', ['status' => 'withdrawn', 'updated_at' => date('Y-m-d H:i:s')], 'id = ?', [$bid['id']]);
            Audit::log('bid_withdraw', 'bid', $bid['id'], 'Rút HSDT gói ' . $t['code']);
            if ($bid['status'] === 'submitted') {
                Notifier::send(Notifier::tenderTeam($t), 'Nhà thầu rút HSDT: ' . $t['code'], $this->supplier['name'], url('tenders/view', ['id' => $t['id']]), 'warning');
            }
            flash('success', 'Đã rút hồ sơ dự thầu. Bạn có thể nộp lại trước thời điểm đóng thầu.');
        } else {
            flash('danger', 'Không thể rút hồ sơ.');
        }
        redirect('portal/tender', ['id' => $t['id']]);
    }

    public function deleteBidDoc(): void
    {
        $t = $this->tenderOrFail();
        $bidId = (int) val('SELECT id FROM bids WHERE tender_id = ? AND supplier_id = ?', [$t['id'], $this->sid]);
        $doc = row("SELECT * FROM documents WHERE id = ? AND entity_type = 'bid' AND entity_id = ? AND is_deleted = 0", [input_int('doc_id'), $bidId]);
        if ($doc && $this->canBid($t)) {
            update_row('documents', ['is_deleted' => 1, 'updated_at' => date('Y-m-d H:i:s')], 'id = ?', [$doc['id']]);
            Audit::log('delete', 'document', $doc['id'], 'NCC gỡ tệp HSDT trước đóng thầu');
        }
        redirect('portal/tender', ['id' => $t['id']]);
    }

    public function bids(): void
    {
        $bids = rows("SELECT b.*, t.code AS tender_code, t.title AS tender_title, t.status AS tender_status, t.close_at, t.id AS tid
            FROM bids b JOIN tenders t ON t.id = b.tender_id WHERE b.supplier_id = ? ORDER BY b.id DESC", [$this->sid]);
        view('portal/bids', ['title' => 'Hồ sơ dự thầu của tôi', 'bids' => $bids]);
    }

    // ------------------------------------------------------------ Hợp đồng, đơn hàng, hóa đơn
    public function contracts(): void
    {
        $contracts = rows("SELECT c.*, p.name AS project_name,
                (SELECT COALESCE(SUM(paid_amount),0) FROM payments x WHERE x.contract_id = c.id AND x.status = 'paid') AS paid
            FROM contracts c JOIN projects p ON p.id = c.project_id WHERE c.supplier_id = ? AND c.status <> 'draft' ORDER BY c.id DESC", [$this->sid]);
        $payments = [];
        $acceptances = [];
        $docs = [];
        foreach ($contracts as $c) {
            $payments[$c['id']] = rows('SELECT milestone, amount, due_date, status, paid_amount, paid_at FROM payments WHERE contract_id = ? ORDER BY due_date IS NULL, due_date', [$c['id']]);
            $acceptances[$c['id']] = rows('SELECT title, accept_date, result, note FROM acceptances WHERE contract_id = ? ORDER BY accept_date', [$c['id']]);
            $docs[$c['id']] = DocumentService::forEntity('contract', (int) $c['id']);
        }
        view('portal/contracts', ['title' => 'Hợp đồng & thanh toán'] + compact('contracts', 'payments', 'acceptances', 'docs'));
    }

    public function orders(): void
    {
        $orders = rows('SELECT o.*, c.code AS contract_code, c.title AS contract_title FROM purchase_orders o JOIN contracts c ON c.id = o.contract_id
            WHERE c.supplier_id = ? ORDER BY o.status = \'sent\' DESC, o.id DESC', [$this->sid]);
        view('portal/orders', ['title' => 'Đơn đặt hàng', 'orders' => $orders]);
    }

    public function confirmOrder(): void
    {
        $o = row("SELECT o.*, c.code AS contract_code, c.created_by FROM purchase_orders o JOIN contracts c ON c.id = o.contract_id WHERE o.id = ? AND c.supplier_id = ?", [input_int('order_id'), $this->sid]);
        if (!$o || $o['status'] !== 'sent') {
            flash('danger', 'Đơn hàng không hợp lệ hoặc đã được xử lý.');
        } else {
            update_row('purchase_orders', ['status' => 'confirmed', 'confirmed_at' => date('Y-m-d H:i:s'), 'supplier_note' => input('supplier_note'),
                'delivery_date' => input_date('delivery_date') ?: $o['delivery_date']], 'id = ?', [$o['id']]);
            Audit::log('po_confirm', 'contract', $o['contract_id'], 'NCC xác nhận đơn hàng ' . $o['code'] . ' ' . input('supplier_note'));
            Notifier::send(array_unique(array_merge([(int) $o['created_by']], Notifier::usersWithPermission('contracts.manage'))), 'NCC đã xác nhận đơn hàng ' . $o['code'], $this->supplier['name'] . ': ' . input('supplier_note'), url('contracts/view', ['id' => $o['contract_id'], 'tab' => 'orders']), 'success');
            flash('success', 'Đã xác nhận đơn hàng.');
        }
        redirect('portal/orders');
    }

    public function invoices(): void
    {
        $invoices = rows('SELECT i.*, c.code AS contract_code FROM invoices i JOIN contracts c ON c.id = i.contract_id WHERE i.supplier_id = ? ORDER BY i.id DESC', [$this->sid]);
        $contracts = pairs("SELECT id, CONCAT(code, ' - ', title) FROM contracts WHERE supplier_id = ? AND status IN ('active','completed') ORDER BY id DESC", [$this->sid]);
        view('portal/invoices', ['title' => 'Hóa đơn điện tử', 'invoices' => $invoices, 'contracts' => $contracts]);
    }

    public function submitInvoice(): void
    {
        $c = row("SELECT * FROM contracts WHERE id = ? AND supplier_id = ? AND status IN ('active','completed')", [input_int('contract_id'), $this->sid]);
        $this->attempt(function () use ($c) {
            if (!$c) {
                $this->fail('Hợp đồng không hợp lệ.');
            }
            $no = strtoupper(preg_replace('/\s+/', '', (string) input('invoice_no')));
            $amount = input_money('amount');
            $vat = input_money('vat_amount');
            if ($no === '' || $amount <= 0 || !input_date('invoice_date')) {
                $this->fail('Nhập số hóa đơn, ngày và giá trị.');
            }
            if (val('SELECT 1 FROM invoices WHERE supplier_id = ? AND invoice_no = ?', [$this->sid, $no])) {
                $this->fail('Số hóa đơn đã được gửi trước đó.');
            }
            $invoiced = (float) val("SELECT COALESCE(SUM(amount),0) FROM invoices WHERE contract_id = ? AND status <> 'rejected'", [$c['id']]);
            if ($invoiced + $amount > (float) $c['value'] * 1.0001) {
                $this->fail('Tổng giá trị hóa đơn (trước thuế) vượt giá trị hợp đồng.');
            }
            $files = DocumentService::files('file');
            if (!$files) {
                $this->fail('Vui lòng đính kèm tệp hóa đơn điện tử (PDF/XML).');
            }
            $id = insert('invoices', [
                'contract_id' => $c['id'], 'supplier_id' => $this->sid, 'invoice_no' => $no, 'invoice_date' => input_date('invoice_date'),
                'amount' => $amount, 'vat_amount' => $vat, 'status' => 'submitted', 'note' => input('note'),
                'submitted_by' => user_id(), 'submitted_at' => date('Y-m-d H:i:s'),
            ]);
            $docId = DocumentService::create([
                'title' => 'Hóa đơn ' . $no . ' - HĐ ' . $c['code'], 'category' => 'invoice', 'entity_type' => 'invoice', 'entity_id' => $id,
                'project_id' => $c['project_id'], 'department_id' => null, 'company_id' => null, 'confidentiality' => 'internal',
            ], $files[0]);
            update_row('invoices', ['document_id' => $docId], 'id = ?', [$id]);
            Audit::log('invoice_submit', 'invoice', $id, 'Gửi hóa đơn ' . $no . ' - ' . money($amount + $vat));
            Notifier::send(Notifier::usersWithPermission('payments.manage'), 'Hóa đơn mới cần kiểm tra', $this->supplier['name'] . ' - ' . $no . ' - ' . money($amount + $vat), url('contracts/view', ['id' => $c['id'], 'tab' => 'invoices']), 'warning');
        }, 'Đã gửi hóa đơn điện tử. Bên mời thầu sẽ kiểm tra và phản hồi.');
        redirect('portal/invoices');
    }
}
