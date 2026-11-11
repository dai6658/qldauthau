<?php
/**
 * Khởi tạo ứng dụng: cấu hình, session an toàn, header bảo mật, autoload.
 */
define('ROOT_PATH', dirname(__DIR__));
define('APP_PATH', ROOT_PATH . '/app');
define('STORAGE_PATH', ROOT_PATH . '/storage');

$GLOBALS['CONFIG'] = require ROOT_PATH . '/config/config.php';

date_default_timezone_set($GLOBALS['CONFIG']['timezone'] ?? 'Asia/Ho_Chi_Minh');
mb_internal_encoding('UTF-8');

if (!empty($GLOBALS['CONFIG']['debug'])) {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
}

require APP_PATH . '/core/helpers.php';

spl_autoload_register(function ($class) {
    foreach (['/core/', '/controllers/'] as $dir) {
        $file = APP_PATH . $dir . $class . '.php';
        if (is_file($file)) {
            require $file;
            return;
        }
    }
});

if (PHP_SAPI !== 'cli') {
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');

    // Mã hóa khi truyền tải: bắt buộc HTTPS + HSTS
    if (config('security.force_https') && !$https) {
        header('Location: https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'], true, 301);
        exit;
    }
    if ($https) {
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
    }
    header('X-Frame-Options: SAMEORIGIN');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: strict-origin-when-cross-origin');

    session_name('EPROCSESSID');
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => $https,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();

    // Hết hạn phiên khi không hoạt động
    $lifetime = (int) config('security.session_lifetime', 7200);
    if (isset($_SESSION['last_activity']) && time() - $_SESSION['last_activity'] > $lifetime) {
        session_unset();
        session_destroy();
        session_start();
        flash('warning', 'Phiên làm việc đã hết hạn, vui lòng đăng nhập lại.');
    }
    $_SESSION['last_activity'] = time();
}
