<?php
class ProjectsController extends Controller
{
    public function index(): void
    {
        require_perm('projects.view');
        $where = ['1=1'];
        $params = [];
        if ($kw = input('q')) {
            $where[] = '(p.code LIKE ? OR p.name LIKE ?)';
            array_push($params, "%$kw%", "%$kw%");
        }
        if ($st = input('status')) {
            $where[] = 'p.status = ?';
            $params[] = $st;
        }
        if ($d = input_int('department_id')) {
            $where[] = 'p.department_id = ?';
            $params[] = $d;
        }
        $w = implode(' AND ', $where);
        $p = paginate("SELECT COUNT(*) FROM projects p WHERE $w",
            "SELECT p.*, d.name AS department_name, u.full_name AS manager_name,
                (SELECT COUNT(*) FROM tenders t WHERE t.project_id = p.id) AS tender_count,
                (SELECT COALESCE(SUM(approved_amount),0) FROM investment_items i WHERE i.project_id = p.id) AS tmdt,
                (SELECT COALESCE(SUM(amount),0) FROM expenses x WHERE x.project_id = p.id) AS spent
             FROM projects p LEFT JOIN departments d ON d.id = p.department_id LEFT JOIN users u ON u.id = p.manager_id
             WHERE $w ORDER BY p.id DESC", $params);
        $departments = pairs('SELECT id, name FROM departments ORDER BY name');
        view('projects/index', ['title' => 'Dự án', 'p' => $p, 'departments' => $departments]);
    }

    public function form(): void
    {
        require_perm('projects.manage');
        $project = input_int('id') ? $this->requireId('projects', 'id', 'dự án') : null;
        if (is_post()) {
            $data = [
                'code' => strtoupper((string) input('code')), 'name' => input('name'),
                'company_id' => input_int('company_id') ?: 1, 'department_id' => input_int('department_id') ?: null,
                'investor' => input('investor'), 'location' => input('location'), 'description' => input('description'),
                'start_date' => input_date('start_date'), 'end_date' => input_date('end_date'),
                'status' => input('status', 'planning'), 'manager_id' => input_int('manager_id') ?: null,
            ];
            $id = $this->attempt(function () use ($data, $project) {
                if (!$data['code'] || !$data['name']) {
                    $this->fail('Mã và tên dự án là bắt buộc.');
                }
                if (val('SELECT 1 FROM projects WHERE code = ? AND id <> ?', [$data['code'], $project['id'] ?? 0])) {
                    $this->fail('Mã dự án đã tồn tại.');
                }
                if ($project) {
                    update_row('projects', $data, 'id = ?', [$project['id']]);
                    Audit::log('update', 'project', $project['id'], $data['name']);
                    return (int) $project['id'];
                }
                $data['created_by'] = user_id();
                $id = insert('projects', $data);
                Audit::log('create', 'project', $id, $data['name']);
                return $id;
            }, 'Đã lưu dự án.');
            if ($id) {
                redirect('projects/view', ['id' => $id]);
            }
            $project = array_merge($project ?? [], $data);
        }
        view('projects/form', [
            'title' => $project ? 'Sửa dự án' : 'Thêm dự án', 'project' => $project,
            'companies' => pairs('SELECT id, name FROM companies'),
            'departments' => pairs('SELECT id, name FROM departments ORDER BY name'),
            'users' => pairs("SELECT u.id, u.full_name FROM users u JOIN roles r ON r.id = u.role_id WHERE r.code <> 'supplier' AND u.is_active = 1 ORDER BY u.full_name"),
        ]);
    }

    public function view(): void
    {
        require_perm('projects.view');
        $project = row('SELECT p.*, c.name AS company_name, d.name AS department_name, u.full_name AS manager_name
            FROM projects p LEFT JOIN companies c ON c.id = p.company_id LEFT JOIN departments d ON d.id = p.department_id
            LEFT JOIN users u ON u.id = p.manager_id WHERE p.id = ?', [input_int('id')]);
        if (!$project) {
            abort(404, 'Không tìm thấy dự án.');
        }
        [$scope, $sp] = Access::tenderScope();
        $tenders = rows("SELECT t.* FROM tenders t JOIN projects p ON p.id = t.project_id WHERE t.project_id = ? AND $scope ORDER BY t.id DESC", array_merge([$project['id']], $sp));
        $plans = rows('SELECT * FROM tender_plans WHERE project_id = ? ORDER BY id DESC', [$project['id']]);
        $contracts = rows('SELECT c.*, s.name AS supplier_name FROM contracts c JOIN suppliers s ON s.id = c.supplier_id WHERE c.project_id = ? ORDER BY c.id DESC', [$project['id']]);
        $budget = row('SELECT COALESCE(SUM(estimated_amount),0) est, COALESCE(SUM(approved_amount),0) appr FROM investment_items WHERE project_id = ?', [$project['id']]);
        $spent = (float) val('SELECT COALESCE(SUM(amount),0) FROM expenses WHERE project_id = ?', [$project['id']]);
        $documents = can('documents.view') ? rows('SELECT d.* FROM documents d WHERE d.project_id = ? AND d.is_deleted = 0 ORDER BY d.id DESC LIMIT 20', [$project['id']]) : [];
        $documents = array_values(array_filter($documents, fn($d) => Access::document($d)));
        view('projects/view', compact('project', 'tenders', 'plans', 'contracts', 'budget', 'spent', 'documents') + ['title' => 'Dự án ' . $project['code']]);
    }
}
