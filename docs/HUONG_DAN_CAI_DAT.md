# Hướng dẫn cài đặt

## 1. Yêu cầu

| Thành phần | Yêu cầu |
|---|---|
| PHP | 8.0 trở lên (XAMPP 8.x) với các extension: `pdo_mysql`, `openssl`, `mbstring`; khuyến nghị `zip`, `fileinfo` |
| CSDL | MySQL 5.7+ hoặc MariaDB 10.3+ (có hỗ trợ TRIGGER) |
| Web server | Apache (có `.htaccess`) hoặc Nginx |
| Trình duyệt | Chrome, Edge, Firefox bản mới (cần Internet để tải Bootstrap/Chart.js từ CDN) |

## 2. Cài đặt trên XAMPP (Windows)

1. Tải và cài XAMPP (bản PHP 8.x) từ apachefriends.org.
2. Giải nén thư mục `qldauthau` vào `C:\xampp\htdocs\qldauthau`.
3. Mở **XAMPP Control Panel** → Start **Apache** và **MySQL**.
4. Truy cập `http://localhost/qldauthau/install.php`:
   - Bước 1 kiểm tra môi trường (các mục bắt buộc phải "Đạt").
   - Bước 2 nhập thông tin MySQL (mặc định: host `127.0.0.1`, cổng `3306`, CSDL `qldauthau`, tài khoản `root`, mật khẩu trống).
   - Bấm **Cài đặt**. Trình cài đặt sẽ:
     1. Tạo CSDL.
     2. Tạo 40 bảng và 2 trigger chống sửa/xóa nhật ký.
     3. Nạp dữ liệu mẫu.
     4. Sinh khóa chủ `app_key` ngẫu nhiên, ghi vào `config/config.php`.
     5. Tạo khóa mã hóa tài liệu đầu tiên.
     6. Tạo tài liệu mẫu (đã mã hóa).
5. Vào `http://localhost/qldauthau/`, đăng nhập bằng tài khoản demo (mật khẩu `123456`).

> Nếu `config/config.php` không ghi được, trình cài đặt hiển thị đoạn cấu hình để bạn dán thủ công.

### Cài lại từ đầu

Xóa tệp `config/installed.lock` rồi mở lại `install.php`. Toàn bộ bảng sẽ bị tạo lại. Nên xóa các tệp cũ trong `storage/documents/`.

### Cài thủ công bằng phpMyAdmin (tùy chọn)

1. Tạo CSDL `qldauthau` (utf8mb4_unicode_ci).
2. Import `database/schema.sql`, rồi `database/seed.sql`.
3. Sửa `config/config.php`:
   - Nhập thông tin CSDL.
   - Đặt `app_key` bằng chuỗi base64 32 byte, sinh bằng lệnh `php -r "echo base64_encode(random_bytes(32));"`.
4. Tạo tệp rỗng `config/installed.lock`.

Khóa mã hóa tài liệu sẽ được tự tạo khi tải tệp lên lần đầu.

## 3. Nhắc việc tự động

Hệ thống tự chạy nhắc việc mỗi giờ khi có người truy cập. Để chạy đúng giờ, bạn có thể đặt lịch định kỳ:

- **Windows (Task Scheduler)**, mỗi 15 phút:
  `C:\xampp\php\php.exe C:\xampp\htdocs\qldauthau\cron\reminders.php`
- **Linux (crontab)**, mỗi 15 phút:
  `php /var/www/qldauthau/cron/reminders.php`

Công việc tự động gồm:

- Tự đóng thầu khi quá hạn.
- Nhắc hạn nộp HSDT (cho tổ đấu thầu và các nhà cung cấp chưa nộp).
- Nhắc việc sắp đến hạn hoặc quá hạn.
- Nhắc thanh toán đến hạn và hợp đồng sắp hết hạn.
- Nhắc hồ sơ chờ phê duyệt tồn đọng.

## 4. Triển khai thực tế: bảo mật

1. **Mã hóa khi truyền tải**:
   - Cài chứng chỉ SSL và đặt `'force_https' => true` trong `config/config.php`. Hệ thống sẽ chuyển hướng sang HTTPS và gửi header HSTS.
   - Có thể bỏ chú thích 2 dòng RewriteRule HTTPS trong `.htaccess`.
2. **Mã hóa khi lưu trữ**:
   - Mỗi tệp được mã hóa AES-256-GCM bằng khóa dữ liệu. Khóa dữ liệu được bọc bởi `app_key`.
   - **Sao lưu `app_key` riêng biệt** với bản sao lưu CSDL.
3. **Khóa khách hàng (BYOK)**: vào **Cấu hình → Khóa mã hóa**.
   - Nhập khóa 256-bit do khách hàng tự sinh bằng `openssl rand -base64 32`.
   - Có thể kích hoạt, xoay vòng (mã hóa lại toàn bộ tài liệu) hoặc thu hồi khóa. Thu hồi sẽ vô hiệu hóa vĩnh viễn các tài liệu dùng khóa đó.
4. **Cấu hình khác**:
   - Đặt `'debug' => false`.
   - Đổi mật khẩu các tài khoản demo.
   - Đặt quyền ghi chỉ cho `storage/` và `config/config.php`.
5. **Máy chủ web**:
   - Các thư mục `app`, `config`, `database`, `storage`, `cron`, `docs` đã được chặn truy cập trực tiếp bằng `.htaccess`.
   - Nếu dùng Nginx, cần cấu hình chặn tương đương.
6. **Giới hạn tải lên**: tăng `upload_max_filesize` và `post_max_size` trong `php.ini` (≥ 20M) để tải tệp lớn.

## 5. Xử lý sự cố

| Hiện tượng | Cách xử lý |
|---|---|
| Lỗi "app_key chưa được cấu hình" | Chạy lại `install.php` hoặc đặt `app_key` hợp lệ trong `config/config.php` |
| Không tạo được trigger | Tài khoản MySQL cần quyền TRIGGER. Trên XAMPP dùng `root` |
| Tải tệp báo lỗi kích thước | Tăng `upload_max_filesize` và `post_max_size` trong `php.ini`, rồi khởi động lại Apache |
| Giao diện không có kiểu dáng | Máy cần Internet để tải Bootstrap từ CDN |
| "Giải mã thất bại" | `app_key` đã bị thay đổi sau khi có dữ liệu. Khôi phục `app_key` cũ |
| Không tìm kiếm được nội dung DOCX/XLSX | Bật extension `zip` trong `php.ini` |
