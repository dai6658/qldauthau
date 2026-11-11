<?php
/**
 * Front controller: index.php?r=controller/action
 */
if (!is_file(__DIR__ . '/config/installed.lock')) {
    header('Location: install.php');
    exit;
}

require __DIR__ . '/app/bootstrap.php';

$route = (string) ($_GET['r'] ?? '');
if ($route === '') {
    $route = auth() ? (is_supplier_user() ? 'portal/index' : 'dashboard/index') : 'auth/login';
}
if (!preg_match('#^([a-z]+)/([a-zA-Z]+)$#', $route, $m)) {
    abort(404, 'Đường dẫn không hợp lệ.');
}
[$controllerName, $action] = [$m[1], $m[2]];

// Các route không cần đăng nhập
$public = ['auth/login', 'auth/register', 'share/open'];

try {
    if (!in_array($route, $public, true)) {
        if (!auth()) {
            $_SESSION['intended'] = $_SERVER['REQUEST_URI'] ?? null;
            redirect('auth/login');
        }
        // Nhà cung cấp chỉ được dùng cổng thông tin nhà cung cấp
        $supplierAllowed = ['portal', 'auth', 'notifications', 'documents'];
        if (is_supplier_user() && !in_array($controllerName, $supplierAllowed, true)) {
            redirect('portal/index');
        }
        if (is_supplier_user() && $controllerName === 'documents' && $action !== 'download') {
            redirect('portal/index');
        }
        // Nhắc việc tự động mỗi giờ (không cần cấu hình cron)
        $last = setting('last_cron_run');
        if (!$last || strtotime($last) < time() - 3600) {
            Notifier::runReminders();
        }
    }

    if (is_post()) {
        verify_csrf();
    }

    $class = ucfirst($controllerName) . 'Controller';
    if (!is_file(APP_PATH . '/controllers/' . $class . '.php')) {
        abort(404, 'Không tìm thấy chức năng.');
    }
    $controller = new $class();
    $ref = method_exists($controller, $action) ? new ReflectionMethod($controller, $action) : null;
    if (!$ref || !$ref->isPublic() || $ref->isStatic() || $ref->getNumberOfRequiredParameters() > 0) {
        abort(404, 'Không tìm thấy chức năng.');
    }
    $controller->$action();
} catch (Throwable $e) {
    error_log($e->getMessage() . "\n" . $e->getTraceAsString());
    http_response_code(500);
    $msg = config('debug') ? $e->getMessage() . ' @ ' . basename($e->getFile()) . ':' . $e->getLine() : 'Đã xảy ra lỗi hệ thống.';
    render_error(500, $msg);
}
