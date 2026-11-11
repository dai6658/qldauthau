# Phần mềm Quản lý Đấu thầu, Mua sắm (eProcurement)

Ứng dụng web quản lý toàn bộ vòng đời đấu thầu – mua sắm: **kế hoạch → hồ sơ mời thầu → nộp hồ sơ dự thầu trực tuyến → đánh giá, lựa chọn nhà thầu → hợp đồng → thanh toán, giải ngân → quyết toán → báo cáo**.
Tài liệu được **mã hóa khi lưu trữ** (AES-256-GCM), hỗ trợ khóa do khách hàng quản lý (BYOK). Mọi thao tác được ghi **nhật ký kiểm toán bất biến**.

- Công nghệ: PHP 8 (MVC thuần, không framework), MySQL/MariaDB (PDO), Bootstrap 5, Chart.js.
- Chạy trên XAMPP/WAMP/LAMP, không cần Composer.

## Cài đặt nhanh (XAMPP)

1. Chép thư mục `qldauthau` vào `C:\xampp\htdocs\`.
2. Khởi động **Apache** và **MySQL** trong XAMPP Control Panel.
3. Mở trình duyệt: `http://localhost/qldauthau/install.php`, giữ thông số mặc định (root / không mật khẩu) → **Cài đặt**.
4. Đăng nhập tại `http://localhost/qldauthau/` với tài khoản demo (mật khẩu `123456`).

| Tài khoản | Vai trò |
|---|---|
| `admin` | Quản trị hệ thống (toàn quyền) |
| `giamdoc` | Lãnh đạo – phê duyệt HSMT, hợp đồng, chi; trao thầu |
| `dauthau` | Cán bộ đấu thầu – lập HSMT, kế hoạch, hợp đồng |
| `kythuat` | Cán bộ kỹ thuật – tổ chuyên gia chấm điểm |
| `chuyengia` | Chuyên viên tài chính – thương mại – tổ chuyên gia chấm điểm |
| `ketoan` | Kế toán – TMĐT, hóa đơn, thanh toán, giải ngân, quyết toán |
| `kiemsoat` | Kiểm soát nội bộ – nhật ký kiểm toán, báo cáo |
| `ncc1`, `ncc2`, `ncc3` | Nhà cung cấp – cổng thông tin nhà cung cấp |

Chi tiết: [docs/HUONG_DAN_CAI_DAT.md](docs/HUONG_DAN_CAI_DAT.md) · Kiểm thử: [docs/HUONG_DAN_KIEM_THU.md](docs/HUONG_DAN_KIEM_THU.md) · Báo cáo: [docs/BAO_CAO.md](docs/BAO_CAO.md) · Kịch bản demo: [docs/KICH_BAN_DEMO.md](docs/KICH_BAN_DEMO.md)

## Cấu trúc thư mục

```
qldauthau/
├── index.php              Front controller (index.php?r=controller/action)
├── install.php            Trình cài đặt
├── config/config.php      Cấu hình CSDL, app_key (khóa chủ), upload, bảo mật
├── app/
│   ├── bootstrap.php      Khởi tạo, session an toàn, header bảo mật, autoload
│   ├── core/              helpers, Crypto, Audit, Access, Workflow, Notifier, DocumentService
│   ├── controllers/       20 controller nghiệp vụ
│   └── views/             Giao diện (Bootstrap 5, tiếng Việt)
├── assets/                CSS, JS
├── database/              schema.sql (40 bảng + trigger), seed.sql (dữ liệu mẫu)
├── storage/documents/     Kho tệp đã mã hóa (*.enc)
├── cron/reminders.php     Nhắc việc định kỳ (CLI)
└── docs/                  Tài liệu
```

## Các module

| Module | Chức năng chính |
|---|---|
| Bảng điều khiển | KPI, biểu đồ trạng thái, TMĐT – cam kết – thực chi, giải ngân theo tháng, việc/thanh toán sắp đến hạn |
| Dự án & tổ chức | Công ty → phòng ban → dự án; gắn hồ sơ theo mã dự án |
| Kế hoạch & tiến độ | Lập kế hoạch, sinh 9 giai đoạn chuẩn, giao việc, cập nhật tiến độ, Gantt, cảnh báo trễ hạn |
| Hồ sơ mời thầu | Tạo từ mẫu (tự sinh nội dung), hạng mục, tiêu chí, phân quyền theo hồ sơ, mời thầu, trình duyệt, công bố, đóng/mở thầu, hủy, in |
| Đánh giá HSDT | Niêm phong điện tử, kiểm tra hợp lệ, chấm điểm nhiều chuyên gia (cấu hình số người tối thiểu, mặc định 3), so sánh báo giá theo hạng mục, QCS / giá thấp nhất / kỹ thuật, xếp hạng, trao thầu |
| Nhà cung cấp | CSDL tập trung, tự đăng ký, thẩm định, hồ sơ năng lực, đánh giá hiệu suất (radar) |
| Cổng nhà cung cấp | Hồ sơ doanh nghiệp, xem/tải HSMT, nộp – sửa – rút HSDT, theo dõi kết quả, hợp đồng, xác nhận đơn hàng, gửi hóa đơn điện tử |
| TMĐT & chi phí | Cơ cấu 7 nhóm chi phí, dự phòng tự động, kiểm soát ngân sách, chi phí thực tế, so sánh giá thầu |
| Hợp đồng & giải ngân | Duyệt hợp đồng, đơn hàng, nghiệm thu, kiểm tra hóa đơn, đề nghị – duyệt chi – giải ngân, quyết toán |
| Kho tài liệu | Mọi định dạng (PDF, Office, bản vẽ, email, ảnh…), tìm kiếm toàn văn, phiên bản, phân quyền, liên kết chia sẻ an toàn |
| Phê duyệt | Quy trình nhiều bước cấu hình được cho HSMT, hợp đồng, thanh toán, kế hoạch |
| Báo cáo | Hiệu quả đấu thầu, tiết kiệm, thời gian xử lý từng giai đoạn, hiệu suất NCC, chi phí dự án, xuất Excel |
| Bảo mật | RBAC + ABAC, CSRF, khóa tài khoản, mã hóa AES-256-GCM, BYOK, xoay vòng / thu hồi khóa, nhật ký bất biến có kiểm tra toàn vẹn |
