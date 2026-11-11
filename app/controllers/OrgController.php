<?php
class OrgController extends Controller
{
    public function index(): void
    {
        require_perm('org.manage');
        $companies = rows('SELECT c.*, (SELECT COUNT(*) FROM departments d WHERE d.company_id = c.id) AS dept_count FROM companies c ORDER BY c.id');
        $departments = rows('SELECT d.*, c.name AS company_name,
            (SELECT COUNT(*) FROM users u WHERE u.department_id = d.id) AS user_count,
            (SELECT COUNT(*) FROM projects p WHERE p.department_id = d.id) AS project_count
            FROM departments d JOIN companies c ON c.id = d.company_id ORDER BY c.id, d.code');
        view('org/index', ['title' => 'Cơ cấu tổ chức', 'companies' => $companies, 'departments' => $departments]);
    }

    public function saveCompany(): void
    {
        require_perm('org.manage');
        $this->attempt(function () {
            $data = ['code' => strtoupper((string) input('code')), 'name' => input('name'), 'tax_code' => input('tax_code'), 'address' => input('address')];
            if (!$data['code'] || !$data['name']) {
                $this->fail('Nhập mã và tên công ty.');
            }
            if ($id = input_int('id')) {
                update_row('companies', $data, 'id = ?', [$id]);
            } else {
                $id = insert('companies', $data);
            }
            Audit::log('update', 'company', $id, $data['name']);
        }, 'Đã lưu công ty.');
        redirect('org/index');
    }

    public function saveDepartment(): void
    {
        require_perm('org.manage');
        $this->attempt(function () {
            $data = ['company_id' => input_int('company_id'), 'code' => strtoupper((string) input('code')), 'name' => input('name')];
            if (!$data['company_id'] || !$data['code'] || !$data['name']) {
                $this->fail('Nhập đủ công ty, mã và tên phòng ban.');
            }
            if ($id = input_int('id')) {
                update_row('departments', $data, 'id = ?', [$id]);
            } else {
                $id = insert('departments', $data);
            }
            Audit::log('update', 'department', $id, $data['name']);
        }, 'Đã lưu phòng ban.');
        redirect('org/index');
    }

    public function deleteDepartment(): void
    {
        require_perm('org.manage');
        $d = $this->requireId('departments');
        $this->attempt(function () use ($d) {
            if (val('SELECT 1 FROM users WHERE department_id = ? UNION SELECT 1 FROM projects WHERE department_id = ?', [$d['id'], $d['id']])) {
                $this->fail('Phòng ban đang có người dùng hoặc dự án, không thể xóa.');
            }
            q('DELETE FROM departments WHERE id = ?', [$d['id']]);
            Audit::log('delete', 'department', $d['id'], $d['name']);
        }, 'Đã xóa phòng ban.');
        redirect('org/index');
    }
}
