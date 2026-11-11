<?php
class DocumentsController extends Controller
{
    public function index(): void
    {
        require_perm('documents.view');
        $u = auth();
        $where = ['d.is_deleted = 0'];
        $params = [];
        if ($kw = input('q')) {
            // Tìm theo tiêu đề, mã, mô tả, thẻ, tên tệp và nội dung văn bản đã trích xuất
            $where[] = '(d.title LIKE ? OR d.code LIKE ? OR d.description LIKE ? OR d.tags LIKE ?
                        OR EXISTS (SELECT 1 FROM document_versions v2 WHERE v2.document_id = d.id AND (v2.original_name LIKE ? OR v2.content_text LIKE ?)))';
            array_push($params, "%$kw%", "%$kw%", "%$kw%", "%$kw%", "%$kw%", "%$kw%");
        }
        foreach (['category' => 'd.category', 'entity_type' => 'd.entity_type', 'confidentiality' => 'd.confidentiality'] as $k => $col) {
            if ($v = input($k)) {
                $where[] = "$col = ?";
                $params[] = $v;
            }
        }
        foreach (['project_id' => 'd.project_id', 'department_id' => 'd.department_id'] as $k => $col) {
            if ($v = input_int($k)) {
                $where[] = "$col = ?";
                $params[] = $v;
            }
        }
        if ($ext = input('ext')) {
            $where[] = 'v.extension = ?';
            $params[] = $ext;
        }
        // Lọc sơ bộ theo quyền ở mức SQL (kiểm tra chi tiết bằng Access::document)
        if (!is_admin() && !can('documents.view_all')) {
            $where[] = "(d.created_by = ? OR d.department_id = ? OR d.entity_type IN ('tender','bid','contract','invoice','supplier')
                        OR EXISTS (SELECT 1 FROM document_permissions dp WHERE dp.document_id = d.id AND (dp.user_id = ? OR dp.role_id = ? OR dp.department_id = ?)))";
            array_push($params, $u['id'], (int) $u['department_id'], $u['id'], $u['role_id'], (int) $u['department_id']);
        }
        $w = implode(' AND ', $where);
        $all = rows("SELECT d.*, v.original_name, v.extension, v.size_bytes, u.full_name AS creator, p.code AS project_code, dp.name AS department_name
            FROM documents d
            LEFT JOIN document_versions v ON v.document_id = d.id AND v.version_no = d.current_version
            LEFT JOIN users u ON u.id = d.created_by
            LEFT JOIN projects p ON p.id = d.project_id
            LEFT JOIN departments dp ON dp.id = d.department_id
            WHERE $w ORDER BY COALESCE(d.updated_at, d.created_at) DESC LIMIT 500", $params);
        $docs = array_values(array_filter($all, fn($d) => Access::document($d)));
        $perPage = 20;
        $page = max(1, input_int('page', 1));
        $p = ['items' => array_slice($docs, ($page - 1) * $perPage, $perPage), 'page' => $page,
              'pages' => max(1, (int) ceil(count($docs) / $perPage)), 'total' => count($docs)];
        view('documents/index', [
            'title' => 'Kho tài liệu', 'p' => $p,
            'projects' => pairs("SELECT id, CONCAT(code, ' - ', name) FROM projects ORDER BY id DESC"),
            'departments' => pairs('SELECT id, name FROM departments ORDER BY name'),
            'stats' => row('SELECT COUNT(*) n, COALESCE(SUM(v.size_bytes),0) sz, (SELECT COUNT(*) FROM document_versions) nv
                            FROM documents d LEFT JOIN document_versions v ON v.document_id = d.id AND v.version_no = d.current_version WHERE d.is_deleted = 0'),
        ]);
    }

    public function upload(): void
    {
        require_perm('documents.manage');
        if (is_post()) {
            $meta = $this->meta();
            $files = DocumentService::files('files');
            $ids = $this->attempt(function () use ($meta, $files) {
                if (!$files) {
                    $this->fail('Vui lòng chọn ít nhất một tệp.');
                }
                if ($meta['entity_type'] === 'tender' && $meta['entity_id']) {
                    Access::requireTender(Access::findTender((int) $meta['entity_id']), 'edit');
                }
                $ids = [];
                foreach ($files as $f) {
                    $m = $meta;
                    if (count($files) > 1 || !$m['title']) {
                        $m['title'] = ($m['title'] ? $m['title'] . ' - ' : '') . pathinfo($f['name'], PATHINFO_FILENAME);
                    }
                    $ids[] = DocumentService::create($m, $f, (string) input('change_note', 'Phiên bản đầu tiên'));
                }
                return $ids;
            }, 'Đã tải lên và mã hóa tài liệu.');
            if ($ids) {
                $back = input('back');
                if ($back && str_starts_with($back, 'index.php?')) {
                    header('Location: ' . $back);
                    exit;
                }
                redirect(count($ids) === 1 ? 'documents/view' : 'documents/index', count($ids) === 1 ? ['id' => $ids[0]] : []);
            }
            if (input('back')) {
                header('Location: ' . (str_starts_with((string) input('back'), 'index.php?') ? input('back') : 'index.php'));
                exit;
            }
        }
        view('documents/upload', [
            'title' => 'Tải lên tài liệu',
            'projects' => pairs("SELECT id, CONCAT(code, ' - ', name) FROM projects ORDER BY id DESC"),
            'departments' => pairs('SELECT id, name FROM departments ORDER BY name'),
            'tenders' => pairs("SELECT id, CONCAT(code, ' - ', title) FROM tenders ORDER BY id DESC"),
            'contracts' => pairs("SELECT id, CONCAT(code, ' - ', title) FROM contracts ORDER BY id DESC"),
        ]);
    }

    private function meta(): array
    {
        $entityType = input('entity_type', 'general');
        if (!isset(labels('entity')[$entityType])) {
            $entityType = 'general';
        }
        $entityId = input_int('entity_id') ?: null;
        $projectId = input_int('project_id') ?: null;
        $deptId = input_int('department_id') ?: (auth()['department_id'] ?? null);
        if ($entityType === 'tender' && $entityId) {
            $t = Access::findTender($entityId);
            $projectId = $t['project_id'] ?? $projectId;
            $deptId = $t['department_id'] ?? $deptId;
        } elseif ($entityType === 'contract' && $entityId) {
            $projectId = val('SELECT project_id FROM contracts WHERE id = ?', [$entityId]) ?: $projectId;
        } elseif ($entityType === 'project') {
            $entityId = $projectId;
        }
        $conf = input('confidentiality', 'internal');
        return [
            'title' => (string) input('title', ''), 'category' => isset(labels('doc_category')[input('category')]) ? input('category') : 'other',
            'description' => input('description'), 'tags' => input('tags'),
            'entity_type' => $entityType, 'entity_id' => $entityId, 'project_id' => $projectId,
            'department_id' => $deptId, 'confidentiality' => isset(labels('confidentiality')[$conf]) ? $conf : 'internal',
        ];
    }

    private function find(): array
    {
        $d = row('SELECT d.*, u.full_name AS creator, p.code AS project_code, p.name AS project_name, dp.name AS department_name
                  FROM documents d LEFT JOIN users u ON u.id = d.created_by LEFT JOIN projects p ON p.id = d.project_id
                  LEFT JOIN departments dp ON dp.id = d.department_id WHERE d.id = ?', [input_int('id')]);
        return $d ?? [];
    }

    public function view(): void
    {
        $doc = Access::requireDocument($this->find() ?: null, 'view');
        Audit::log('view', 'document', $doc['id']);
        $versions = rows('SELECT v.*, u.full_name, k.name AS key_name, k.key_type, k.status AS key_status
            FROM document_versions v LEFT JOIN users u ON u.id = v.uploaded_by LEFT JOIN encryption_keys k ON k.id = v.key_id
            WHERE v.document_id = ? ORDER BY v.version_no DESC', [$doc['id']]);
        $perms = rows('SELECT dp.*, u.full_name, r.name AS role_name, d.name AS dept_name FROM document_permissions dp
            LEFT JOIN users u ON u.id = dp.user_id LEFT JOIN roles r ON r.id = dp.role_id LEFT JOIN departments d ON d.id = dp.department_id
            WHERE dp.document_id = ?', [$doc['id']]);
        $shares = rows('SELECT s.*, u.full_name FROM share_links s LEFT JOIN users u ON u.id = s.created_by WHERE s.document_id = ? ORDER BY s.id DESC', [$doc['id']]);
        $logs = Audit::forEntity('document', (int) $doc['id'], 100);
        $canEdit = Access::document($doc, 'edit');
        $canDownload = Access::document($doc, 'download');
        $preview = null;
        if (!empty($versions[0]['content_text']) && in_array($versions[0]['extension'], ['txt', 'csv', 'xml'], true)) {
            $preview = mb_substr($versions[0]['content_text'], 0, 3000);
        }
        view('documents/view', [
            'title' => $doc['title'], 'doc' => $doc, 'versions' => $versions, 'perms' => $perms, 'shares' => $shares,
            'logs' => $logs, 'canEdit' => $canEdit, 'canDownload' => $canDownload, 'preview' => $preview,
            'users' => pairs("SELECT u.id, u.full_name FROM users u JOIN roles r ON r.id = u.role_id WHERE r.code <> 'supplier' ORDER BY u.full_name"),
            'roles' => pairs("SELECT id, name FROM roles WHERE code <> 'supplier'"),
            'departments' => pairs('SELECT id, name FROM departments ORDER BY name'),
            'newShare' => $_SESSION['new_share'] ?? null,
        ]);
        unset($_SESSION['new_share']);
    }

    public function download(): void
    {
        $doc = Access::requireDocument($this->find() ?: null, 'download');
        $vid = input_int('version');
        $version = $vid ? row('SELECT * FROM document_versions WHERE id = ? AND document_id = ?', [$vid, $doc['id']]) : DocumentService::currentVersion((int) $doc['id']);
        if (!$version) {
            abort(404, 'Không tìm thấy phiên bản');
        }
        try {
            $inline = input('inline') === '1' && in_array($version['extension'], ['pdf', 'jpg', 'jpeg', 'png', 'gif', 'txt'], true);
            Audit::log('download', 'document', $doc['id'], ($inline ? 'Xem trực tuyến' : 'Tải xuống') . ' phiên bản ' . $version['version_no']);
            DocumentService::send($version, $inline);
        } catch (RuntimeException $e) {
            abort(410, $e->getMessage());
        }
    }

    public function edit(): void
    {
        $doc = Access::requireDocument($this->find() ?: null, 'edit');
        $data = [
            'title' => input('title') ?: $doc['title'], 'description' => input('description'), 'tags' => input('tags'),
            'category' => isset(labels('doc_category')[input('category')]) ? input('category') : $doc['category'],
            'confidentiality' => isset(labels('confidentiality')[input('confidentiality')]) ? input('confidentiality') : $doc['confidentiality'],
            'updated_at' => date('Y-m-d H:i:s'),
        ];
        update_row('documents', $data, 'id = ?', [$doc['id']]);
        $changes = [];
        foreach (['title', 'category', 'confidentiality', 'tags'] as $k) {
            if ((string) $doc[$k] !== (string) $data[$k]) {
                $changes[$k] = [$doc[$k], $data[$k]];
            }
        }
        Audit::log('update', 'document', $doc['id'], $changes ?: 'Cập nhật mô tả');
        flash('success', 'Đã cập nhật thông tin tài liệu.');
        redirect('documents/view', ['id' => $doc['id']]);
    }

    public function newVersion(): void
    {
        $doc = Access::requireDocument($this->find() ?: null, 'edit');
        $files = DocumentService::files('file');
        $this->attempt(function () use ($doc, $files) {
            if (!$files) {
                $this->fail('Chọn tệp cho phiên bản mới.');
            }
            if (!input('change_note')) {
                $this->fail('Vui lòng nhập ghi chú thay đổi.');
            }
            $v = DocumentService::addVersion((int) $doc['id'], $files[0], (string) input('change_note'));
            Notifier::send([$doc['created_by']], 'Tài liệu có phiên bản mới: ' . $doc['title'], 'Phiên bản ' . $v . ': ' . input('change_note'), url('documents/view', ['id' => $doc['id']]));
        }, 'Đã tạo phiên bản mới.');
        redirect('documents/view', ['id' => $doc['id'], 'tab' => 'versions']);
    }

    public function restore(): void
    {
        $doc = Access::requireDocument($this->find() ?: null, 'edit');
        $this->attempt(function () use ($doc) {
            DocumentService::restore((int) $doc['id'], input_int('version'), (string) input('note', ''));
        }, 'Đã khôi phục phiên bản (tạo thành phiên bản mới).');
        redirect('documents/view', ['id' => $doc['id'], 'tab' => 'versions']);
    }

    public function delete(): void
    {
        $doc = Access::requireDocument($this->find() ?: null, 'edit');
        update_row('documents', ['is_deleted' => 1, 'updated_at' => date('Y-m-d H:i:s')], 'id = ?', [$doc['id']]);
        q('UPDATE share_links SET is_revoked = 1 WHERE document_id = ?', [$doc['id']]);
        Audit::log('delete', 'document', $doc['id'], 'Xóa (lưu trữ) tài liệu: ' . $doc['title']);
        flash('success', 'Đã xóa tài liệu (vẫn lưu vết trong nhật ký kiểm toán).');
        redirect('documents/index');
    }

    public function grant(): void
    {
        $doc = Access::requireDocument($this->find() ?: null, 'edit');
        $target = input('target_type');
        $tid = input_int('target_id');
        if (!in_array($target, ['user', 'role', 'department'], true) || !$tid) {
            flash('danger', 'Chọn đối tượng được cấp quyền.');
        } else {
            insert('document_permissions', [
                'document_id' => $doc['id'], $target . '_id' => $tid,
                'can_view' => 1, 'can_download' => input('can_download') ? 1 : 0, 'can_edit' => input('can_edit') ? 1 : 0,
                'created_by' => user_id(), 'created_at' => date('Y-m-d H:i:s'),
            ]);
            Audit::log('permission', 'document', $doc['id'], "Cấp quyền cho $target #$tid (tải: " . (input('can_download') ? 'có' : 'không') . ', sửa: ' . (input('can_edit') ? 'có' : 'không') . ')');
            if ($target === 'user') {
                Notifier::send([$tid], 'Bạn được chia sẻ tài liệu', $doc['title'], url('documents/view', ['id' => $doc['id']]));
            }
            flash('success', 'Đã cấp quyền.');
        }
        redirect('documents/view', ['id' => $doc['id'], 'tab' => 'access']);
    }

    public function revoke(): void
    {
        $doc = Access::requireDocument($this->find() ?: null, 'edit');
        q('DELETE FROM document_permissions WHERE id = ? AND document_id = ?', [input_int('perm_id'), $doc['id']]);
        Audit::log('permission', 'document', $doc['id'], 'Thu hồi quyền #' . input_int('perm_id'));
        flash('success', 'Đã thu hồi quyền.');
        redirect('documents/view', ['id' => $doc['id'], 'tab' => 'access']);
    }

    /** Tạo liên kết chia sẻ an toàn: có thời hạn, mật khẩu, giới hạn lượt tải */
    public function share(): void
    {
        $doc = Access::requireDocument($this->find() ?: null, 'edit');
        if ($doc['confidentiality'] === 'confidential' && !is_admin()) {
            flash('danger', 'Tài liệu mật không được chia sẻ ra bên ngoài.');
            redirect('documents/view', ['id' => $doc['id'], 'tab' => 'access']);
        }
        $hours = max(1, min(24 * 30, input_int('hours', 72)));
        $token = bin2hex(random_bytes(24));
        $pass = (string) ($_POST['password'] ?? '');
        insert('share_links', [
            'document_id' => $doc['id'], 'token' => $token,
            'password_hash' => $pass !== '' ? password_hash($pass, PASSWORD_DEFAULT) : null,
            'expires_at' => date('Y-m-d H:i:s', time() + $hours * 3600),
            'max_downloads' => input_int('max_downloads') ?: null,
            'created_by' => user_id(), 'created_at' => date('Y-m-d H:i:s'),
        ]);
        Audit::log('share', 'document', $doc['id'], "Tạo liên kết chia sẻ {$hours} giờ" . ($pass !== '' ? ', có mật khẩu' : ''));
        $_SESSION['new_share'] = $token;
        redirect('documents/view', ['id' => $doc['id'], 'tab' => 'access']);
    }

    public function unshare(): void
    {
        $doc = Access::requireDocument($this->find() ?: null, 'edit');
        q('UPDATE share_links SET is_revoked = 1 WHERE id = ? AND document_id = ?', [input_int('share_id'), $doc['id']]);
        Audit::log('share_revoke', 'document', $doc['id'], 'Thu hồi liên kết #' . input_int('share_id'));
        flash('success', 'Đã thu hồi liên kết chia sẻ.');
        redirect('documents/view', ['id' => $doc['id'], 'tab' => 'access']);
    }
}
