<?php
class TendersController extends Controller
{
    public function index(): void
    {
        require_perm('tenders.view', 'tenders.view_all');
        [$scope, $params] = Access::tenderScope();
        $where = [$scope];
        if ($kw = input('q')) {
            $where[] = '(t.code LIKE ? OR t.title LIKE ?)';
            array_push($params, "%$kw%", "%$kw%");
        }
        foreach (['status' => 't.status', 'category' => 't.category', 'method' => 't.method'] as $k => $col) {
            if ($v = input($k)) {
                $where[] = "$col = ?";
                $params[] = $v;
            }
        }
        if ($pid = input_int('project_id')) {
            $where[] = 't.project_id = ?';
            $params[] = $pid;
        }
        $w = implode(' AND ', $where);
        $p = paginate("SELECT COUNT(*) FROM tenders t JOIN projects p ON p.id = t.project_id WHERE $w",
            "SELECT t.*, p.code AS project_code, d.name AS department_name, u.full_name AS creator,
                (SELECT COUNT(*) FROM bids b WHERE b.tender_id = t.id AND b.status NOT IN ('draft','withdrawn')) AS bid_count
             FROM tenders t JOIN projects p ON p.id = t.project_id LEFT JOIN departments d ON d.id = p.department_id
             LEFT JOIN users u ON u.id = t.created_by WHERE $w ORDER BY t.id DESC", $params);
        $counts = pairs("SELECT t.status, COUNT(*) FROM tenders t JOIN projects p ON p.id = t.project_id WHERE $scope GROUP BY t.status", Access::tenderScope()[1]);
        view('tenders/index', ['title' => 'Hồ sơ mời thầu', 'p' => $p, 'counts' => $counts,
            'projects' => pairs('SELECT id, code FROM projects ORDER BY id DESC')]);
    }

    public function create(): void
    {
        require_perm('tenders.manage');
        $template = input_int('template_id') ? row('SELECT * FROM tender_templates WHERE id = ?', [input_int('template_id')]) : null;
        if (is_post()) {
            $data = $this->collect();
            $id = $this->attempt(function () use ($data, $template) {
                $this->validate($data);
                return transaction(function () use ($data, $template) {
                    $data['code'] = gen_code('GT', 'tenders');
                    $data['template_id'] = $template['id'] ?? null;
                    $data['status'] = 'draft';
                    $data['created_by'] = user_id();
                    $data['created_at'] = date('Y-m-d H:i:s');
                    $id = insert('tenders', $data);
                    // Hạng mục nhập kèm
                    foreach ((array) ($_POST['item_name'] ?? []) as $i => $name) {
                        if (trim((string) $name) === '') {
                            continue;
                        }
                        insert('tender_items', [
                            'tender_id' => $id, 'name' => trim($name), 'unit' => $_POST['item_unit'][$i] ?? '',
                            'quantity' => (float) str_replace(',', '.', $_POST['item_qty'][$i] ?? 1) ?: 1,
                            'estimated_price' => (float) preg_replace('/[^\d]/', '', $_POST['item_price'][$i] ?? '0'),
                            'specs' => $_POST['item_specs'][$i] ?? '',
                        ]);
                    }
                    // Tiêu chí từ mẫu
                    foreach (json_decode($template['criteria_json'] ?? '[]', true) ?: [] as $i => $c) {
                        insert('tender_criteria', ['tender_id' => $id, 'name' => $c['name'], 'max_score' => (int) $c['max_score'], 'sort_order' => $i + 1]);
                    }
                    q('INSERT IGNORE INTO tender_members (tender_id, user_id, access) VALUES (?, ?, ?)', [$id, user_id(), 'edit']);
                    if ($template && $template['content']) {
                        $t = Access::findTender($id);
                        update_row('tenders', ['content' => TemplatesController::render($template['content'], $t)], 'id = ?', [$id]);
                    }
                    Audit::log('create', 'tender', $id, $data['title'] . ($template ? ' (từ mẫu: ' . $template['name'] . ')' : ''));
                    $this->budgetWarning($data, $id);
                    return $id;
                });
            }, 'Đã tạo hồ sơ mời thầu' . ($template ? ' và sinh nội dung tự động từ mẫu.' : '.'));
            if ($id) {
                redirect('tenders/view', ['id' => $id]);
            }
            $tender = $data;
        } else {
            $tender = [
                'project_id' => input_int('project_id') ?: null,
                'category' => $template['category'] ?? 'goods', 'method' => $template['method'] ?? 'open',
                'evaluation_method' => $template['evaluation_method'] ?? 'qcs',
                'tech_weight' => $template['tech_weight'] ?? setting('default_tech_weight', 70),
                'pass_tech_score' => $template['pass_tech_score'] ?? setting('default_pass_score', 70),
                'close_at' => date('Y-m-d 09:00:00', strtotime('+21 days')),
                'opening_at' => date('Y-m-d 09:30:00', strtotime('+21 days')),
            ];
        }
        view('tenders/form', $this->formData($tender) + ['title' => 'Tạo hồ sơ mời thầu', 'template' => $template, 'isNew' => true,
            'templates' => pairs('SELECT id, name FROM tender_templates ORDER BY id')]);
    }

    public function edit(): void
    {
        $t = Access::requireTender(Access::findTender(input_int('id')), 'edit');
        if ($t['status'] !== 'draft') {
            flash('warning', 'Chỉ sửa được hồ sơ ở trạng thái Nháp.');
            redirect('tenders/view', ['id' => $t['id']]);
        }
        if (is_post()) {
            $data = $this->collect();
            $ok = $this->attempt(function () use ($data, $t) {
                $this->validate($data);
                $data['updated_at'] = date('Y-m-d H:i:s');
                update_row('tenders', $data, 'id = ?', [$t['id']]);
                $changes = [];
                foreach (['title', 'budget', 'close_at', 'method', 'evaluation_method', 'tech_weight'] as $k) {
                    if ((string) $t[$k] !== (string) $data[$k]) {
                        $changes[$k] = [$t[$k], $data[$k]];
                    }
                }
                Audit::log('update', 'tender', $t['id'], $changes ?: 'Cập nhật thông tin');
                $this->budgetWarning($data, (int) $t['id']);
            }, 'Đã cập nhật hồ sơ mời thầu.');
            if ($ok) {
                redirect('tenders/view', ['id' => $t['id']]);
            }
            $t = array_merge($t, $data);
        }
        view('tenders/form', $this->formData($t) + ['title' => 'Sửa HSMT ' . $t['code'], 'template' => null, 'isNew' => false, 'templates' => []]);
    }

    private function collect(): array
    {
        $tw = max(0, min(100, input_int('tech_weight', 70)));
        $method = input('evaluation_method', 'qcs');
        if ($method === 'lowest_price') {
            $tw = 0;
        } elseif ($method === 'quality') {
            $tw = 100;
        }
        return [
            'project_id' => input_int('project_id'), 'investment_item_id' => input_int('investment_item_id') ?: null,
            'title' => input('title'), 'category' => input('category', 'goods'), 'method' => input('method', 'open'),
            'evaluation_method' => $method, 'tech_weight' => $tw, 'price_weight' => 100 - $tw,
            'pass_tech_score' => max(0, min(100, input_int('pass_tech_score', 70))),
            'budget' => input_money('budget'), 'bid_security' => input_money('bid_security') ?: null,
            'description' => input('description'), 'close_at' => input_datetime('close_at'), 'opening_at' => input_datetime('opening_at'),
        ];
    }

    private function validate(array $d): void
    {
        if (!$d['title'] || !$d['project_id']) {
            $this->fail('Tên gói thầu và dự án là bắt buộc.');
        }
        if (!isset(labels('category')[$d['category']]) || !isset(labels('method')[$d['method']]) || !isset(labels('eval_method')[$d['evaluation_method']])) {
            $this->fail('Dữ liệu phân loại không hợp lệ.');
        }
        if ($d['budget'] <= 0) {
            $this->fail('Giá gói thầu phải lớn hơn 0.');
        }
        if ($d['close_at'] && $d['opening_at'] && strtotime($d['opening_at']) < strtotime($d['close_at'])) {
            $this->fail('Thời điểm mở thầu phải sau thời điểm đóng thầu.');
        }
    }

    /** Kiểm soát ngân sách: cảnh báo nếu giá gói thầu vượt hạn mức TMĐT còn lại */
    private function budgetWarning(array $d, int $tenderId): void
    {
        if (!$d['investment_item_id']) {
            return;
        }
        $item = row('SELECT * FROM investment_items WHERE id = ?', [$d['investment_item_id']]);
        $used = (float) val("SELECT COALESCE(SUM(budget),0) FROM tenders WHERE investment_item_id = ? AND id <> ? AND status <> 'cancelled'", [$d['investment_item_id'], $tenderId]);
        $remain = (float) $item['approved_amount'] - $used;
        if ($d['budget'] > $remain) {
            flash('warning', 'Cảnh báo ngân sách: giá gói thầu ' . money($d['budget']) . ' vượt hạn mức còn lại của khoản mục "' . $item['name'] . '" (' . money($remain) . ').');
        }
    }

    private function formData(array $tender): array
    {
        return [
            'tender' => $tender,
            'projects' => pairs("SELECT id, CONCAT_WS(' - ', code, name) FROM projects ORDER BY id DESC"),
            'investItems' => rows('SELECT i.id, i.project_id, i.name, i.approved_amount,
                (SELECT COALESCE(SUM(budget),0) FROM tenders t WHERE t.investment_item_id = i.id AND t.status <> \'cancelled\') AS used
                FROM investment_items i ORDER BY i.project_id, i.id'),
        ];
    }

    public function view(): void
    {
        $t = Access::requireTender(Access::findTender(input_int('id')), 'view');
        $id = (int) $t['id'];
        $items = rows('SELECT * FROM tender_items WHERE tender_id = ? ORDER BY id', [$id]);
        $criteria = rows('SELECT * FROM tender_criteria WHERE tender_id = ? ORDER BY sort_order, id', [$id]);
        $members = rows('SELECT m.*, u.full_name, u.username, d.name AS dept FROM tender_members m JOIN users u ON u.id = m.user_id LEFT JOIN departments d ON d.id = u.department_id WHERE m.tender_id = ?', [$id]);
        $invites = rows('SELECT i.*, s.name, s.code FROM tender_invitations i JOIN suppliers s ON s.id = i.supplier_id WHERE i.tender_id = ?', [$id]);
        $bids = rows("SELECT b.*, s.name AS supplier_name, s.code AS supplier_code FROM bids b JOIN suppliers s ON s.id = b.supplier_id
                      WHERE b.tender_id = ? AND b.status <> 'draft' ORDER BY COALESCE(b.rank_no, 999), b.submitted_at", [$id]);
        $documents = DocumentService::forEntity('tender', $id);
        $history = Workflow::history('tender', $id);
        $logs = Audit::forEntity('tender', $id, 60);
        $contract = row('SELECT * FROM contracts WHERE tender_id = ? ORDER BY id DESC LIMIT 1', [$id]);
        $sealed = Access::bidsSealed($t);
        $canEdit = Access::tender($t, 'edit');
        $item = $t['investment_item_id'] ? row('SELECT * FROM investment_items WHERE id = ?', [$t['investment_item_id']]) : null;
        view('tenders/view', compact('t', 'items', 'criteria', 'members', 'invites', 'bids', 'documents', 'history', 'logs', 'contract', 'sealed', 'canEdit', 'item') + [
            'title' => 'HSMT ' . $t['code'],
            'users' => pairs("SELECT u.id, CONCAT(u.full_name, ' (', r.name, ')') FROM users u JOIN roles r ON r.id = u.role_id WHERE r.code <> 'supplier' AND u.is_active = 1 ORDER BY u.full_name"),
            'suppliers' => pairs("SELECT id, CONCAT(code, ' - ', name) FROM suppliers WHERE status = 'approved' ORDER BY name"),
        ]);
    }

    private function draftTender(string $need = 'edit'): array
    {
        $t = Access::requireTender(Access::findTender(input_int('tender_id') ?: input_int('id')), $need);
        if ($t['status'] !== 'draft') {
            flash('warning', 'Hồ sơ không ở trạng thái Nháp, không thể chỉnh sửa nội dung này.');
            redirect('tenders/view', ['id' => $t['id']]);
        }
        return $t;
    }

    public function saveContent(): void
    {
        $t = $this->draftTender();
        update_row('tenders', ['content' => (string) ($_POST['content'] ?? ''), 'updated_at' => date('Y-m-d H:i:s')], 'id = ?', [$t['id']]);
        Audit::log('update', 'tender', $t['id'], 'Chỉnh sửa nội dung HSMT');
        flash('success', 'Đã lưu nội dung HSMT.');
        redirect('tenders/view', ['id' => $t['id'], 'tab' => 'content']);
    }

    public function regenerate(): void
    {
        $t = $this->draftTender();
        $tplId = input_int('template_id') ?: (int) $t['template_id'];
        $tpl = row('SELECT * FROM tender_templates WHERE id = ?', [$tplId]);
        if (!$tpl) {
            flash('danger', 'Chưa chọn mẫu HSMT.');
        } else {
            update_row('tenders', ['content' => TemplatesController::render((string) $tpl['content'], $t), 'template_id' => $tpl['id'], 'updated_at' => date('Y-m-d H:i:s')], 'id = ?', [$t['id']]);
            if (input('with_criteria') && !val('SELECT 1 FROM tender_criteria WHERE tender_id = ?', [$t['id']])) {
                foreach (json_decode($tpl['criteria_json'] ?? '[]', true) ?: [] as $i => $c) {
                    insert('tender_criteria', ['tender_id' => $t['id'], 'name' => $c['name'], 'max_score' => (int) $c['max_score'], 'sort_order' => $i + 1]);
                }
            }
            Audit::log('update', 'tender', $t['id'], 'Sinh tự động nội dung HSMT từ mẫu: ' . $tpl['name']);
            flash('success', 'Đã sinh lại nội dung HSMT từ mẫu "' . $tpl['name'] . '".');
        }
        redirect('tenders/view', ['id' => $t['id'], 'tab' => 'content']);
    }

    public function addItem(): void
    {
        $t = $this->draftTender();
        if (!input('name')) {
            flash('danger', 'Nhập tên hạng mục.');
        } else {
            insert('tender_items', ['tender_id' => $t['id'], 'name' => input('name'), 'specs' => input('specs'), 'unit' => input('unit'),
                'quantity' => max(0.01, (float) str_replace(',', '.', (string) input('quantity', '1'))), 'estimated_price' => input_money('estimated_price')]);
            Audit::log('update', 'tender', $t['id'], 'Thêm hạng mục: ' . input('name'));
        }
        redirect('tenders/view', ['id' => $t['id'], 'tab' => 'items']);
    }

    public function deleteItem(): void
    {
        $t = $this->draftTender();
        $item = row('SELECT * FROM tender_items WHERE id = ? AND tender_id = ?', [input_int('item_id'), $t['id']]);
        if ($item) {
            q('DELETE FROM tender_items WHERE id = ?', [$item['id']]);
            Audit::log('update', 'tender', $t['id'], 'Xóa hạng mục: ' . $item['name']);
        }
        redirect('tenders/view', ['id' => $t['id'], 'tab' => 'items']);
    }

    public function addCriterion(): void
    {
        $t = $this->draftTender();
        if (!input('name') || input_int('max_score') <= 0) {
            flash('danger', 'Nhập tên tiêu chí và điểm tối đa.');
        } else {
            insert('tender_criteria', ['tender_id' => $t['id'], 'name' => input('name'), 'description' => input('description'),
                'max_score' => input_int('max_score'), 'sort_order' => (int) val('SELECT COALESCE(MAX(sort_order),0)+1 FROM tender_criteria WHERE tender_id = ?', [$t['id']])]);
            Audit::log('update', 'tender', $t['id'], 'Thêm tiêu chí: ' . input('name'));
        }
        redirect('tenders/view', ['id' => $t['id'], 'tab' => 'criteria']);
    }

    public function deleteCriterion(): void
    {
        $t = $this->draftTender();
        q('DELETE FROM tender_criteria WHERE id = ? AND tender_id = ?', [input_int('criterion_id'), $t['id']]);
        Audit::log('update', 'tender', $t['id'], 'Xóa tiêu chí #' . input_int('criterion_id'));
        redirect('tenders/view', ['id' => $t['id'], 'tab' => 'criteria']);
    }

    /** Phân quyền truy cập theo từng hồ sơ thầu */
    public function addMember(): void
    {
        $t = Access::requireTender(Access::findTender(input_int('id')), 'edit');
        $access = input('access');
        if (input_int('user_id') && isset(labels('member_access')[$access])) {
            q('INSERT INTO tender_members (tender_id, user_id, access) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE access = VALUES(access)', [$t['id'], input_int('user_id'), $access]);
            Audit::log('permission', 'tender', $t['id'], 'Phân quyền user #' . input_int('user_id') . ': ' . label('member_access', $access));
            Notifier::send([input_int('user_id')], 'Bạn được phân công vào gói thầu ' . $t['code'], $t['title'] . ' — vai trò: ' . label('member_access', $access), url('tenders/view', ['id' => $t['id']]));
            flash('success', 'Đã cập nhật thành viên.');
        }
        redirect('tenders/view', ['id' => $t['id'], 'tab' => 'team']);
    }

    public function removeMember(): void
    {
        $t = Access::requireTender(Access::findTender(input_int('id')), 'edit');
        q('DELETE FROM tender_members WHERE tender_id = ? AND user_id = ?', [$t['id'], input_int('user_id')]);
        Audit::log('permission', 'tender', $t['id'], 'Gỡ thành viên user #' . input_int('user_id'));
        redirect('tenders/view', ['id' => $t['id'], 'tab' => 'team']);
    }

    public function invite(): void
    {
        $t = Access::requireTender(Access::findTender(input_int('id')), 'edit');
        foreach ((array) ($_POST['supplier_ids'] ?? []) as $sid) {
            $st = q('INSERT IGNORE INTO tender_invitations (tender_id, supplier_id) VALUES (?, ?)', [$t['id'], (int) $sid]);
            if ($st->rowCount() && $t['status'] === 'published') {
                Notifier::send(Notifier::supplierUsers([(int) $sid]), 'Thư mời thầu: ' . $t['code'], $t['title'] . ' — đóng thầu ' . fdatetime($t['close_at']), url('portal/tender', ['id' => $t['id']]));
            }
        }
        Audit::log('update', 'tender', $t['id'], 'Mời nhà thầu: ' . implode(',', (array) ($_POST['supplier_ids'] ?? [])));
        flash('success', 'Đã cập nhật danh sách mời thầu.');
        redirect('tenders/view', ['id' => $t['id'], 'tab' => 'team']);
    }

    public function uninvite(): void
    {
        $t = Access::requireTender(Access::findTender(input_int('id')), 'edit');
        if ($t['status'] === 'draft' || $t['status'] === 'approved') {
            q('DELETE FROM tender_invitations WHERE tender_id = ? AND supplier_id = ?', [$t['id'], input_int('supplier_id')]);
        }
        redirect('tenders/view', ['id' => $t['id'], 'tab' => 'team']);
    }

    private function readinessErrors(array $t): array
    {
        $errors = [];
        $items = (int) val('SELECT COUNT(*) FROM tender_items WHERE tender_id = ?', [$t['id']]);
        $crit = (int) val('SELECT COALESCE(SUM(max_score),0) FROM tender_criteria WHERE tender_id = ?', [$t['id']]);
        if (!$items) {
            $errors[] = 'Chưa có hạng mục / phạm vi cung cấp.';
        }
        if ($crit <= 0) {
            $errors[] = 'Chưa có tiêu chí đánh giá kỹ thuật.';
        }
        if (!$t['close_at'] || strtotime($t['close_at']) < time() + 3600) {
            $errors[] = 'Thời điểm đóng thầu phải sau hiện tại ít nhất 1 giờ.';
        }
        if (trim((string) $t['content']) === '') {
            $errors[] = 'Chưa có nội dung HSMT (có thể sinh tự động từ mẫu).';
        }
        if ($t['method'] !== 'open' && !val('SELECT 1 FROM tender_invitations WHERE tender_id = ?', [$t['id']])) {
            $errors[] = 'Hình thức ' . label('method', $t['method']) . ' cần có danh sách nhà thầu được mời.';
        }
        return $errors;
    }

    public function submitApproval(): void
    {
        $t = $this->draftTender();
        if ($errors = $this->readinessErrors($t)) {
            flash('danger', 'Chưa thể trình duyệt: ' . implode(' ', $errors));
            redirect('tenders/view', ['id' => $t['id']]);
        }
        update_row('tenders', ['status' => 'pending_approval', 'updated_at' => date('Y-m-d H:i:s')], 'id = ?', [$t['id']]);
        $needs = Workflow::start('tender', (int) $t['id'], 'HSMT ' . $t['code'] . ': ' . $t['title']);
        flash('success', $needs ? 'Đã trình phê duyệt HSMT.' : 'Không có bước phê duyệt nào được cấu hình — HSMT được duyệt tự động.');
        redirect('tenders/view', ['id' => $t['id']]);
    }

    /** Công bố HSMT: sinh tệp HSMT vào kho tài liệu (công khai), thông báo nhà thầu */
    public function publish(): void
    {
        require_perm('tenders.publish');
        $t = Access::requireTender(Access::findTender(input_int('id')), 'view');
        if ($t['status'] !== 'approved') {
            flash('danger', 'Chỉ công bố được HSMT đã được phê duyệt.');
            redirect('tenders/view', ['id' => $t['id']]);
        }
        if ($errors = $this->readinessErrors($t)) {
            flash('danger', 'Chưa thể công bố: ' . implode(' ', $errors));
            redirect('tenders/view', ['id' => $t['id']]);
        }
        transaction(function () use ($t) {
            update_row('tenders', ['status' => 'published', 'publish_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s')], 'id = ?', [$t['id']]);
            $t['publish_at'] = date('Y-m-d H:i:s');
            $text = "HỒ SƠ MỜI THẦU\n" . $t['code'] . ' - ' . $t['title'] . "\nNgày phát hành: " . fdatetime($t['publish_at']) . "\n\n" . $t['content'];
            DocumentService::createFromContent([
                'title' => 'HSMT ' . $t['code'] . ' (bản công bố)', 'category' => 'tender_doc', 'entity_type' => 'tender',
                'entity_id' => $t['id'], 'project_id' => $t['project_id'], 'department_id' => $t['department_id'],
                'confidentiality' => 'public', 'tags' => 'HSMT, công bố',
            ], 'HSMT_' . $t['code'] . '.txt', $text, 'text/plain', 'Bản công bố tự động');
            q("UPDATE documents SET confidentiality = 'public' WHERE entity_type = 'tender' AND entity_id = ? AND category IN ('tender_doc','notice','drawing','design') AND confidentiality = 'internal'", [$t['id']]);
            Audit::log('publish', 'tender', $t['id'], 'Công bố HSMT, đóng thầu ' . $t['close_at']);
        });
        $supplierUsers = Notifier::supplierUsers(Notifier::tenderSuppliers($t));
        Notifier::send($supplierUsers, ($t['method'] === 'open' ? 'Gói thầu mới: ' : 'Thư mời thầu: ') . $t['code'], $t['title'] . ' — đóng thầu ' . fdatetime($t['close_at']), url('portal/tender', ['id' => $t['id']]));
        Notifier::send(Notifier::tenderTeam($t), 'Đã công bố HSMT ' . $t['code'], 'Đã gửi thông báo tới ' . count($supplierUsers) . ' tài khoản nhà thầu.', url('tenders/view', ['id' => $t['id']]), 'success');
        flash('success', 'Đã công bố HSMT và gửi thông báo tới ' . count($supplierUsers) . ' tài khoản nhà thầu.');
        redirect('tenders/view', ['id' => $t['id']]);
    }

    public function close(): void
    {
        require_perm('tenders.publish');
        $t = Access::requireTender(Access::findTender(input_int('id')), 'view');
        if ($t['status'] !== 'published') {
            flash('danger', 'Gói thầu không ở trạng thái mời thầu.');
        } elseif (strtotime($t['close_at']) > time() && !is_admin()) {
            flash('danger', 'Chưa đến thời điểm đóng thầu (' . fdatetime($t['close_at']) . ').');
        } else {
            update_row('tenders', ['status' => 'closed', 'updated_at' => date('Y-m-d H:i:s')], 'id = ?', [$t['id']]);
            q("UPDATE bids SET status = 'withdrawn', updated_at = NOW() WHERE tender_id = ? AND status = 'draft'", [$t['id']]);
            Audit::log('close', 'tender', $t['id'], 'Đóng thầu thủ công');
            flash('success', 'Đã đóng thầu. Có thể tiến hành mở thầu.');
        }
        redirect('tenders/view', ['id' => $t['id']]);
    }

    /** Mở thầu: giải niêm phong HSDT, lập biên bản mở thầu */
    public function openBids(): void
    {
        $t = Access::requireTender(Access::findTender(input_int('id')), 'evaluate');
        if ($t['status'] !== 'closed') {
            flash('danger', 'Chỉ mở thầu sau khi đã đóng thầu.');
            redirect('tenders/view', ['id' => $t['id']]);
        }
        $bids = rows("SELECT b.code, b.total_price, s.name FROM bids b JOIN suppliers s ON s.id = b.supplier_id WHERE b.tender_id = ? AND b.status = 'submitted'", [$t['id']]);
        transaction(function () use ($t, $bids) {
            q("UPDATE bids SET opened_at = NOW() WHERE tender_id = ? AND status = 'submitted'", [$t['id']]);
            update_row('tenders', ['status' => 'evaluating', 'opening_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s')], 'id = ?', [$t['id']]);
            $lines = ["BIÊN BẢN MỞ THẦU", "Gói thầu: {$t['code']} - {$t['title']}", 'Thời điểm mở thầu: ' . date('d/m/Y H:i'), 'Chủ trì: ' . auth()['full_name'], '', 'Danh sách HSDT:'];
            foreach ($bids as $i => $b) {
                $lines[] = ($i + 1) . ". {$b['name']} — HSDT {$b['code']} — Giá dự thầu: " . money($b['total_price']);
            }
            $lines[] = '';
            $lines[] = 'Tổng số HSDT: ' . count($bids);
            DocumentService::createFromContent([
                'title' => 'Biên bản mở thầu ' . $t['code'], 'category' => 'decision', 'entity_type' => 'tender', 'entity_id' => $t['id'],
                'project_id' => $t['project_id'], 'department_id' => $t['department_id'], 'confidentiality' => 'internal', 'tags' => 'mở thầu',
            ], 'BienBanMoThau_' . $t['code'] . '.txt', implode("\n", $lines), 'text/plain', 'Tự động lập khi mở thầu');
            Audit::log('open_bids', 'tender', $t['id'], 'Mở ' . count($bids) . ' HSDT');
        });
        flash('success', 'Đã mở thầu ' . count($bids) . ' HSDT và lập biên bản mở thầu.');
        redirect('evaluation/tender', ['id' => $t['id']]);
    }

    public function cancel(): void
    {
        require_perm('tenders.publish');
        $t = Access::requireTender(Access::findTender(input_int('id')), 'view');
        $reason = (string) input('reason');
        if ($reason === '' || in_array($t['status'], ['awarded', 'cancelled'], true)) {
            flash('danger', 'Nhập lý do hủy (không thể hủy gói thầu đã trao thầu).');
            redirect('tenders/view', ['id' => $t['id']]);
        }
        $suppliers = array_column(rows("SELECT supplier_id FROM bids WHERE tender_id = ? AND status NOT IN ('withdrawn')", [$t['id']]), 'supplier_id');
        update_row('tenders', ['status' => 'cancelled', 'cancel_reason' => $reason, 'updated_at' => date('Y-m-d H:i:s')], 'id = ?', [$t['id']]);
        q("UPDATE bids SET status = 'rejected', review_note = ? WHERE tender_id = ? AND status NOT IN ('withdrawn')", ['Gói thầu bị hủy: ' . $reason, $t['id']]);
        q("UPDATE approvals SET status = 'cancelled' WHERE entity_type = 'tender' AND entity_id = ? AND status IN ('pending','waiting')", [$t['id']]);
        Audit::log('cancel', 'tender', $t['id'], $reason);
        Notifier::send(Notifier::supplierUsers($suppliers), 'Hủy thầu: ' . $t['code'], $reason, url('portal/bids'), 'danger');
        flash('success', 'Đã hủy gói thầu.');
        redirect('tenders/view', ['id' => $t['id']]);
    }

    public function print(): void
    {
        $t = Access::requireTender(Access::findTender(input_int('id')), 'view');
        Audit::log('view', 'tender', $t['id'], 'In/xuất HSMT');
        view('tenders/print', [
            't' => $t, 'title' => 'HSMT ' . $t['code'],
            'items' => rows('SELECT * FROM tender_items WHERE tender_id = ? ORDER BY id', [$t['id']]),
            'criteria' => rows('SELECT * FROM tender_criteria WHERE tender_id = ? ORDER BY sort_order, id', [$t['id']]),
        ], 'layouts/print');
    }
}
