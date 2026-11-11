<?php
class AuthController extends Controller
{
    public function login(): void
    {
        if (auth()) {
            redirect(is_supplier_user() ? 'portal/index' : 'dashboard/index');
        }
        if (is_post()) {
            $username = (string) input('username');
            $password = (string) ($_POST['password'] ?? '');
            $user = row('SELECT * FROM users WHERE username = ?', [$username]);

            if ($user && $user['locked_until'] && strtotime($user['locked_until']) > time()) {
                flash('danger', 'Tài khoản tạm khóa do đăng nhập sai nhiều lần. Thử lại sau ' . fdatetime($user['locked_until']) . '.');
            } elseif ($user && $user['is_active'] && password_verify($password, $user['password_hash'])) {
                session_regenerate_id(true);
                $_SESSION['uid'] = (int) $user['id'];
                update_row('users', ['failed_attempts' => 0, 'locked_until' => null, 'last_login_at' => date('Y-m-d H:i:s')], 'id = ?', [$user['id']]);
                if (password_needs_rehash($user['password_hash'], PASSWORD_DEFAULT)) {
                    update_row('users', ['password_hash' => password_hash($password, PASSWORD_DEFAULT)], 'id = ?', [$user['id']]);
                }
                Audit::log('login', 'user', $user['id']);
                $intended = $_SESSION['intended'] ?? null;
                unset($_SESSION['intended']);
                if ($intended && str_contains($intended, 'index.php?r=') && !str_contains($intended, 'auth/')) {
                    header('Location: ' . 'index.php' . substr($intended, strpos($intended, '?')));
                    exit;
                }
                header('Location: index.php');
                exit;
            } else {
                if ($user) {
                    $fails = (int) $user['failed_attempts'] + 1;
                    $lock = $fails >= (int) config('security.max_login_fails', 5)
                        ? date('Y-m-d H:i:s', time() + 60 * (int) config('security.lock_minutes', 15)) : null;
                    update_row('users', ['failed_attempts' => $lock ? 0 : $fails, 'locked_until' => $lock], 'id = ?', [$user['id']]);
                }
                Audit::log('login_failed', 'user', $user['id'] ?? null, 'Tên đăng nhập: ' . $username, $username);
                flash('danger', $user && !$user['is_active'] ? 'Tài khoản đã bị vô hiệu hóa.' : 'Tên đăng nhập hoặc mật khẩu không đúng.');
            }
            redirect('auth/login');
        }
        view('auth/login', ['title' => 'Đăng nhập'], 'layouts/guest');
    }

    public function logout(): void
    {
        if (auth()) {
            Audit::log('logout', 'user', user_id());
        }
        session_unset();
        session_destroy();
        session_start();
        flash('success', 'Bạn đã đăng xuất.');
        redirect('auth/login');
    }

    /** Nhà cung cấp tự đăng ký (chờ duyệt) */
    public function register(): void
    {
        if (setting('allow_supplier_register', '1') !== '1') {
            abort(403, 'Chức năng đăng ký nhà cung cấp đang tắt.');
        }
        if (is_post()) {
            $ok = $this->attempt(function () {
                $name = input('name');
                $tax = input('tax_code');
                $username = input('username');
                $pass = (string) ($_POST['password'] ?? '');
                if (!$name || !$tax || !$username || !input('email')) {
                    $this->fail('Vui lòng nhập đầy đủ các trường bắt buộc.');
                }
                if (!preg_match('/^\d{10}(-\d{3})?$/', $tax)) {
                    $this->fail('Mã số thuế không hợp lệ (10 hoặc 13 số).');
                }
                if (!preg_match('/^[a-zA-Z0-9_.]{4,50}$/', $username)) {
                    $this->fail('Tên đăng nhập 4-50 ký tự, chỉ gồm chữ, số, dấu chấm, gạch dưới.');
                }
                if (strlen($pass) < 8 || $pass !== ($_POST['password2'] ?? '')) {
                    $this->fail('Mật khẩu tối thiểu 8 ký tự và phải khớp phần xác nhận.');
                }
                if (val('SELECT 1 FROM suppliers WHERE tax_code = ?', [$tax])) {
                    $this->fail('Mã số thuế đã được đăng ký.');
                }
                if (val('SELECT 1 FROM users WHERE username = ?', [$username])) {
                    $this->fail('Tên đăng nhập đã tồn tại.');
                }
                transaction(function () use ($name, $tax, $username, $pass) {
                    $sid = insert('suppliers', [
                        'code' => 'NCC-' . str_pad((string) ((int) val('SELECT COALESCE(MAX(id),0)+1 FROM suppliers')), 4, '0', STR_PAD_LEFT),
                        'name' => $name, 'tax_code' => $tax, 'address' => input('address'), 'phone' => input('phone'),
                        'email' => input('email'), 'representative' => input('representative'),
                        'business_field' => input('business_field'), 'status' => 'pending', 'created_at' => date('Y-m-d H:i:s'),
                    ]);
                    $roleId = (int) val("SELECT id FROM roles WHERE code = 'supplier'");
                    $uid = insert('users', [
                        'username' => $username, 'password_hash' => password_hash($pass, PASSWORD_DEFAULT),
                        'full_name' => input('representative') ?: $name, 'email' => input('email'), 'phone' => input('phone'),
                        'role_id' => $roleId, 'supplier_id' => $sid, 'created_at' => date('Y-m-d H:i:s'),
                    ]);
                    Audit::log('create', 'supplier', $sid, 'Nhà cung cấp tự đăng ký: ' . $name, $username);
                    Notifier::send(Notifier::usersWithPermission('suppliers.manage'), 'Nhà cung cấp mới đăng ký', $name . ' (MST ' . $tax . ') đang chờ duyệt', url('suppliers/view', ['id' => $sid]));
                    return $uid;
                });
            }, 'Đăng ký thành công! Hồ sơ đang chờ bên mời thầu thẩm định. Bạn có thể đăng nhập để bổ sung hồ sơ năng lực.');
            if ($ok) {
                redirect('auth/login');
            }
        }
        view('auth/register', ['title' => 'Đăng ký nhà cung cấp', 'wide' => 'wide'], 'layouts/guest');
    }

    public function profile(): void
    {
        $u = auth();
        if (is_post()) {
            $this->attempt(function () use ($u) {
                $data = ['full_name' => input('full_name') ?: $u['full_name'], 'email' => input('email'), 'phone' => input('phone')];
                $new = (string) ($_POST['new_password'] ?? '');
                if ($new !== '') {
                    if (!password_verify((string) ($_POST['current_password'] ?? ''), $u['password_hash'])) {
                        $this->fail('Mật khẩu hiện tại không đúng.');
                    }
                    if (strlen($new) < 8 || $new !== ($_POST['new_password2'] ?? '')) {
                        $this->fail('Mật khẩu mới tối thiểu 8 ký tự và phải khớp phần xác nhận.');
                    }
                    $data['password_hash'] = password_hash($new, PASSWORD_DEFAULT);
                }
                update_row('users', $data, 'id = ?', [$u['id']]);
                Audit::log('update', 'user', $u['id'], $new !== '' ? 'Cập nhật hồ sơ & đổi mật khẩu' : 'Cập nhật hồ sơ cá nhân');
            }, 'Đã cập nhật tài khoản.');
            redirect('auth/profile');
        }
        $logins = rows("SELECT action, ip, created_at FROM audit_logs WHERE user_id = ? AND action IN ('login','logout') ORDER BY id DESC LIMIT 10", [$u['id']]);
        view('auth/profile', ['title' => 'Tài khoản của tôi', 'u' => $u, 'logins' => $logins]);
    }
}
