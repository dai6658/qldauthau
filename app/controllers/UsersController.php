<?php
class UsersController extends Controller
{
    public function index(): void
    {
        require_perm('users.manage');
        $where = ['1=1'];
        $params = [];
        if ($kw = input('q')) {
            $where[] = '(u.username LIKE ? OR u.full_name LIKE ? OR u.email LIKE ?)';
            array_push($params, "%$kw%", "%$kw%", "%$kw%");
        }
        if ($r = input_int('role_id')) {
            $where[] = 'u.role_id = ?';
            $params[] = $r;
        }
        $w = implode(' AND ', $where);
        $p = paginate("SELECT COUNT(*) FROM users u WHERE $w",
            "SELECT u.*, r.name AS role_name, d.name AS department_name, s.name AS supplier_name
             FROM users u JOIN roles r ON r.id = u.role_id LEFT JOIN departments d ON d.id = u.department_id
             LEFT JOIN suppliers s ON s.id = u.supplier_id WHERE $w ORDER BY u.id", $params, 20);
        view('users/index', ['title' => 'Người dùng', 'p' => $p, 'roles' => pairs('SELECT id, name FROM roles ORDER BY id')]);
    }

    public function form(): void
    {
        require_perm('users.manage');
        $user = input_int('id') ? $this->requireId('users', 'id', 'người dùng') : null;
        if (is_post()) {
            $data = [
                'username' => input('username'), 'full_name' => input('full_name'), 'email' => input('email'),
                'phone' => input('phone'), 'role_id' => input_int('role_id'),
                'company_id' => input_int('company_id') ?: null, 'department_id' => input_int('department_id') ?: null,
                'supplier_id' => input_int('supplier_id') ?: null, 'is_active' => input('is_active') ? 1 : 0,
            ];
            $ok = $this->attempt(function () use (&$data, $user) {
                if (!preg_match('/^[a-zA-Z0-9_.]{3,50}$/', (string) $data['username']) || !$data['full_name'] || !$data['role_id']) {
                    $this->fail('Tên đăng nhập (3-50 ký tự), họ tên và vai trò là bắt buộc.');
                }
                if (val('SELECT 1 FROM users WHERE username = ? AND id <> ?', [$data['username'], $user['id'] ?? 0])) {
                    $this->fail('Tên đăng nhập đã tồn tại.');
                }
                $isSupplierRole = val('SELECT code FROM roles WHERE id = ?', [$data['role_id']]) === 'supplier';
                if ($isSupplierRole && !$data['supplier_id']) {
                    $this->fail('Tài khoản nhà cung cấp phải gắn với một nhà cung cấp.');
                }
                if (!$isSupplierRole) {
                    $data['supplier_id'] = null;
                }
                $pass = (string) ($_POST['password'] ?? '');
                if ($pass !== '') {
                    if (strlen($pass) < 6) {
                        $this->fail('Mật khẩu tối thiểu 6 ký tự.');
                    }
                    $data['password_hash'] = password_hash($pass, PASSWORD_DEFAULT);
                } elseif (!$user) {
                    $this->fail('Nhập mật khẩu cho tài khoản mới.');
                }
                if ($user) {
                    if ((int) $user['id'] === user_id() && !$data['is_active']) {
                        $this->fail('Không thể tự vô hiệu hóa tài khoản của mình.');
                    }
                    update_row('users', $data, 'id = ?', [$user['id']]);
                    Audit::log('update', 'user', $user['id'], 'Cập nhật tài khoản ' . $data['username']);
                } else {
                    $id = insert('users', $data);
                    Audit::log('create', 'user', $id, 'Tạo tài khoản ' . $data['username']);
                }
            }, 'Đã lưu người dùng.');
            if ($ok) {
                redirect('users/index');
            }
            $user = array_merge($user ?? [], $data);
        }
        view('users/form', [
            'title' => !empty($user['id']) ? 'Sửa người dùng' : 'Thêm người dùng', 'user' => $user,
            'roles' => pairs('SELECT id, name FROM roles ORDER BY id'),
            'companies' => pairs('SELECT id, name FROM companies'),
            'departments' => pairs('SELECT id, name FROM departments ORDER BY name'),
            'suppliers' => pairs('SELECT id, name FROM suppliers ORDER BY name'),
        ]);
    }

    public function unlock(): void
    {
        require_perm('users.manage');
        $u = $this->requireId('users');
        update_row('users', ['failed_attempts' => 0, 'locked_until' => null], 'id = ?', [$u['id']]);
        Audit::log('update', 'user', $u['id'], 'Mở khóa tài khoản');
        flash('success', 'Đã mở khóa tài khoản ' . $u['username']);
        redirect('users/index');
    }

    /** Ma trận phân quyền vai trò - quyền (tùy biến) */
    public function roles(): void
    {
        require_perm('users.manage');
        if (is_post()) {
            $this->attempt(function () {
                $matrix = $_POST['perm'] ?? [];
                transaction(function () use ($matrix) {
                    foreach (rows("SELECT id, code FROM roles WHERE code <> 'admin'") as $role) {
                        q('DELETE FROM role_permissions WHERE role_id = ?', [$role['id']]);
                        foreach (array_keys((array) ($matrix[$role['id']] ?? [])) as $pid) {
                            q('INSERT INTO role_permissions (role_id, permission_id) VALUES (?, ?)', [$role['id'], (int) $pid]);
                        }
                    }
                });
                if ($name = input('new_role_name')) {
                    $code = strtolower(preg_replace('/[^a-z0-9_]/i', '_', (string) input('new_role_code')));
                    if (!$code || val('SELECT 1 FROM roles WHERE code = ?', [$code])) {
                        $this->fail('Mã vai trò mới không hợp lệ hoặc đã tồn tại.');
                    }
                    insert('roles', ['code' => $code, 'name' => $name, 'description' => input('new_role_desc')]);
                }
                Audit::log('permission', 'role', null, 'Cập nhật ma trận phân quyền');
            }, 'Đã cập nhật phân quyền.');
            redirect('users/roles');
        }
        $roles = rows('SELECT * FROM roles ORDER BY id');
        $perms = rows('SELECT * FROM permissions ORDER BY module, id');
        $granted = [];
        foreach (rows('SELECT role_id, permission_id FROM role_permissions') as $rp) {
            $granted[$rp['role_id']][$rp['permission_id']] = true;
        }
        view('users/roles', ['title' => 'Vai trò & phân quyền', 'roles' => $roles, 'perms' => $perms, 'granted' => $granted]);
    }
}
