<?php
/**
 * Nhắc việc định kỳ (chạy bằng CLI):
 *   php cron/reminders.php
 * - Windows (XAMPP): Task Scheduler → C:\xampp\php\php.exe C:\xampp\htdocs\qldauthau\cron\reminders.php (mỗi 15–60 phút)
 * - Linux (crontab, mỗi 15 phút): php /var/www/qldauthau/cron/reminders.php
 * Công việc: tự đóng thầu khi hết hạn, nhắc hạn nộp HSDT, việc sắp/quá hạn, thanh toán đến hạn,
 * hợp đồng sắp hết hạn, hồ sơ chờ phê duyệt tồn đọng.
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('CLI only');
}
require dirname(__DIR__) . '/app/bootstrap.php';

if (!is_file(ROOT_PATH . '/config/installed.lock')) {
    fwrite(STDERR, "Hệ thống chưa được cài đặt.\n");
    exit(1);
}
try {
    $stats = Notifier::runReminders();
    echo '[' . date('Y-m-d H:i:s') . '] ' . json_encode($stats, JSON_UNESCAPED_UNICODE) . PHP_EOL;
} catch (Throwable $e) {
    fwrite(STDERR, 'Lỗi: ' . $e->getMessage() . PHP_EOL);
    exit(1);
}
