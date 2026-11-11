<?php
/**
 * Cấu hình hệ thống Quản lý đấu thầu, mua sắm
 * - Sửa thông tin CSDL cho phù hợp máy của bạn (XAMPP mặc định: root / không mật khẩu)
 * - app_key: khóa chủ (master key / KEK) dùng để bọc (wrap) các khóa mã hóa tài liệu.
 *   install.php sẽ tự sinh khóa mới. KHÔNG thay đổi sau khi đã có dữ liệu, nếu không
 *   sẽ không giải mã được tài liệu đã lưu.
 */
return [
    'app_name'  => 'Hệ thống Quản lý Đấu thầu & Mua sắm',
    'app_short' => 'eProcurement',
    'base_url'  => '',            // để trống = tự nhận diện
    'timezone'  => 'Asia/Ho_Chi_Minh',
    'debug'     => true,          // đặt false khi triển khai thật

    'db' => [
        'host'    => '127.0.0.1',
        'port'    => 3306,
        'name'    => 'qldauthau',
        'user'    => 'root',
        'pass'    => '',
        'charset' => 'utf8mb4',
    ],

    // Khóa chủ AES-256 (base64 của 32 byte). install.php sẽ ghi đè giá trị này.
    'app_key' => 'CHANGE_ME_RUN_INSTALL',

    'upload' => [
        'max_size' => 20 * 1024 * 1024, // 20MB
        'allowed'  => ['pdf','doc','docx','xls','xlsx','ppt','pptx','txt','csv','jpg','jpeg','png','gif',
                       'zip','rar','7z','dwg','dxf','eml','msg','xml'],
    ],

    'security' => [
        'force_https'      => false,  // bật true khi đã cài chứng chỉ SSL (mã hóa khi truyền tải)
        'session_lifetime' => 7200,
        'max_login_fails'  => 5,
        'lock_minutes'     => 15,
    ],
];
