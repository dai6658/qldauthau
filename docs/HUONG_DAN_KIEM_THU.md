# Hướng dẫn kiểm thử từng chức năng

Tài liệu mô tả **cách thử từng chức năng** của phần mềm Quản lý đấu thầu, mua sắm. Mỗi ca kiểm thử (test case) gồm: tài khoản dùng, thao tác, và **kết quả mong đợi**. Nếu kết quả thực tế khác, ghi lại mã ca và thông báo lỗi để sửa.

Các con số trong bảng được tính từ **dữ liệu mẫu** của trình cài đặt. Hãy làm **theo đúng thứ tự các phần**, vì phần sau dùng dữ liệu phần trước tạo ra.

---

## 0. Chuẩn bị

1. Cài mới hệ thống theo `HUONG_DAN_CAI_DAT.md`, tích chọn "Tạo tài liệu mẫu".
2. Chuẩn bị 2 trình duyệt, hoặc 1 cửa sổ thường và 1 cửa sổ ẩn danh:
   - Trình duyệt **A**: tài khoản nội bộ.
   - Trình duyệt **B**: tài khoản nhà cung cấp.
3. Mở **phpMyAdmin** (`http://localhost/phpmyadmin`) để kiểm tra dữ liệu và thử các ca bảo mật.
4. Chuẩn bị tệp thử:
   - `test.pdf`: một tệp PDF bất kỳ.
   - `test.docx`: tệp Word có câu **"máy phát điện dự phòng công suất 500kVA"**.
   - `test.exe`: tệp bất kỳ, đổi đuôi thành `.exe`.
5. Tài khoản (mật khẩu chung `123456`):

| Tài khoản | Vai trò | Phòng ban / NCC |
|---|---|---|
| `admin` | Quản trị hệ thống | Ban Giám đốc |
| `giamdoc` | Lãnh đạo | Ban Giám đốc |
| `dauthau` | Cán bộ đấu thầu | Phòng Kế hoạch – Đấu thầu |
| `kythuat` | Cán bộ đấu thầu (tổ chuyên gia – kỹ thuật) | Phòng Kỹ thuật |
| `chuyengia` | Cán bộ đấu thầu (tổ chuyên gia – tài chính, thương mại) | Phòng Tài chính – Kế toán |
| `ketoan` | Kế toán | Phòng Tài chính – Kế toán |
| `kiemsoat` | Kiểm soát nội bộ | Ban Kiểm soát |
| `ncc1` | Nhà cung cấp | NCC-0001 Sao Việt |
| `ncc2` | Nhà cung cấp | NCC-0002 Minh Phát |
| `ncc3` | Nhà cung cấp | NCC-0003 |

6. Dữ liệu mẫu chính:

| Mã | Nội dung | Trạng thái ban đầu |
|---|---|---|
| GT-2026-0001 | Máy chủ, giá gói 3.300.000.000 đ | Đã trao thầu cho NCC-0001 |
| GT-2026-0002 | Thiết bị mạng, giá gói 1.700.000.000 đ, QCS 60/40 | Đang mời thầu, đóng sau 10 ngày; `ncc2` đã nộp 1.640.000.000 đ |
| GT-2026-0003 | Cải tạo tòa nhà B, đấu thầu hạn chế | Nháp; đã mời NCC-0003 |
| GT-2026-0004 | Tư vấn giám sát, chào hàng cạnh tranh | Chờ giám đốc duyệt (đã 3 ngày) |
| HD-2026-0001 | Hợp đồng máy chủ, 3.150.000.000 đ | Đang thực hiện; đã tạm ứng 945.000.000 đ; hóa đơn 0002156 chờ kiểm tra |

> **Muốn thử lại từ đầu:**
> 1. Xóa `config/installed.lock` và các thư mục con trong `storage/documents/`.
> 2. Chạy lại `install.php`.

---

## 1. Cài đặt (INS)

| Mã | Tài khoản | Thao tác | Kết quả mong đợi |
|---|---|---|---|
| INS-01 | – | Mở `http://localhost/qldauthau/` khi chưa cài | Tự chuyển sang `install.php` |
| INS-02 | – | Xem bảng "Kiểm tra môi trường" | Các mục PHP ≥ 8.0, PDO MySQL, OpenSSL, mbstring, thư mục storage đều "Đạt" |
| INS-03 | – | Nhập sai mật khẩu MySQL → Cài đặt | Báo lỗi kết nối (Access denied), không tạo lock |
| INS-04 | – | Nhập đúng → Cài đặt | Trang "Cài đặt thành công" liệt kê các bước: số câu lệnh schema/seed, sinh app_key, tạo khóa, sinh nội dung HSMT, tạo tài liệu mẫu |
| INS-05 | – | Mở `config/config.php` bằng Notepad | `app_key` là chuỗi base64 44 ký tự (không còn `CHANGE_ME...`) |
| INS-06 | – | Mở lại `install.php` | Thông báo "Hệ thống đã được cài đặt…" và không cho cài lại |
| INS-07 | – | phpMyAdmin → CSDL `qldauthau` | Có 40 bảng; tab *Triggers* của `audit_logs` có 2 trigger |

---

## 2. Đăng nhập & tài khoản (AUTH)

| Mã | Tài khoản | Thao tác | Kết quả mong đợi |
|---|---|---|---|
| AUTH-01 | admin | Đăng nhập đúng | Vào Bảng điều khiển; menu đầy đủ 5 nhóm |
| AUTH-02 | ncc1 | Đăng nhập | Vào **Cổng nhà cung cấp**; menu chỉ có 7 mục dành cho NCC |
| AUTH-03 | – | Nhập sai mật khẩu | Báo "Tên đăng nhập hoặc mật khẩu không đúng" |
| AUTH-04 | kythuat | Nhập sai mật khẩu **5 lần** liên tiếp, lần 6 nhập **đúng** | Lần 6 vẫn bị chặn: "Tài khoản tạm khóa do đăng nhập sai nhiều lần. Thử lại sau …" (15 phút) |
| AUTH-05 | admin | Người dùng & phân quyền → dòng `kythuat` | Có nhãn đỏ **Đang khóa** và nút mở khóa → bấm → nhãn mất; `kythuat` đăng nhập lại được |
| AUTH-06 | – | Mở trực tiếp `index.php?r=tenders/index` khi chưa đăng nhập, rồi đăng nhập `dauthau` | Sau đăng nhập được đưa **đúng** tới trang Hồ sơ mời thầu |
| AUTH-07 | dauthau | Góc phải → Tài khoản → đổi mật khẩu với mật khẩu hiện tại **sai** | Báo "Mật khẩu hiện tại không đúng" |
| AUTH-08 | dauthau | Đổi mật khẩu mới 6 ký tự | Báo "tối thiểu 8 ký tự" |
| AUTH-09 | dauthau | Đổi mật khẩu thành `12345678`, đăng xuất, đăng nhập bằng mật khẩu mới | Thành công; trang Tài khoản hiển thị lịch sử đăng nhập/đăng xuất kèm IP. *(Có thể đổi lại `123456` qua admin → Người dùng → Sửa)* |
| AUTH-10 | – | Trang đăng nhập → **Đăng ký nhà cung cấp**: MST `12345` | Báo "Mã số thuế không hợp lệ (10 hoặc 13 số)" |
| AUTH-11 | – | Đăng ký với MST `0101234567` (trùng NCC-0001) | Báo "Mã số thuế đã được đăng ký" |
| AUTH-12 | – | Đăng ký hợp lệ: tên "Công ty TNHH Kiểm Thử", MST `0109999999`, tài khoản `ncctest`, mật khẩu `12345678` (nhập 2 lần), email bất kỳ | "Đăng ký thành công! Hồ sơ đang chờ…" |
| AUTH-13 | ncctest | Đăng nhập | Vào cổng NCC; hộp vàng "Hồ sơ doanh nghiệp đang ở trạng thái **Chờ duyệt**" |
| AUTH-14 | dauthau | Chuông thông báo | Có thông báo "Nhà cung cấp mới đăng ký" |
| AUTH-15 | bất kỳ | Để trình duyệt không thao tác quá 2 giờ (hoặc tạm sửa `session_lifetime` = 60 giây trong config) rồi bấm menu | Bị đưa về đăng nhập: "Phiên làm việc đã hết hạn" |

---

## 3. Thông báo & nhắc việc tự động (TB)

> Làm phần này **trước** các phần sau: GT-2026-0002 còn đang mời thầu, GT-2026-0004 còn chờ duyệt, và đợt thanh toán 50% của HD-2026-0001 (đến hạn sau 2 ngày) chưa chi.

| Mã | Tài khoản | Thao tác | Kết quả mong đợi |
|---|---|---|---|
| TB-01 | giamdoc | Đăng nhập lần đầu, xem chuông | Có số đỏ; có "Có hồ sơ cần phê duyệt" (GT-2026-0004). Hệ thống tự chạy nhắc việc khi có người truy cập, nên có thể có thêm "**Hồ sơ chờ phê duyệt quá 2 ngày**" |
| TB-02 | ketoan | Xem chuông | Có "**Thanh toán sắp đến hạn: HD-2026-0001**" (đợt 50%, hạn sau 2 ngày) |
| TB-03 | admin | Cấu hình → Cấu hình chung → "Nhắc trước hạn" = `15` → Lưu → tab **Hệ thống** → **Chạy nhắc việc ngay** | Thông báo kết quả dạng `{"closed":0,"reminders":N}` với N > 0 |
| TB-04 | dauthau, kythuat, chuyengia | Xem chuông | Có "**Sắp đóng thầu: GT-2026-0002** – Còn 10 ngày…" |
| TB-05 | ncc1, ncc3 (trình duyệt B) | Xem chuông / Tổng quan | Có "**Nhắc hạn nộp HSDT: GT-2026-0002** … Bạn chưa nộp hồ sơ". `ncc2` **không** nhận vì đã nộp |
| TB-06 | admin | Bấm **Chạy nhắc việc ngay** lần 2 | `reminders` = 0: không gửi trùng trong cùng ngày |
| TB-07 | A: dauthau, B: ncc1 | Để trang của `dauthau` mở. Ở B, `ncc1` nộp HSDT (xem CNCC-05) | Trong ≤ 30 giây, trang `dauthau` hiện **toast** thông báo mới và số trên chuông tăng mà không cần tải lại |
| TB-08 | dauthau | Bấm một thông báo | Chuyển tới đúng trang liên quan; thông báo chuyển thành "đã đọc" |
| TB-09 | dauthau | Thông báo → **Đánh dấu tất cả đã đọc** | Số đỏ trên chuông biến mất |
| TB-10 | – | Mở CMD: `C:\xampp\php\php.exe C:\xampp\htdocs\qldauthau\cron\reminders.php` | In ra `[thời gian] {"closed":…,"reminders":…}` |
| TB-11 | – | Mở `http://localhost/qldauthau/cron/reminders.php` trên trình duyệt | Bị chặn (403) |
| TB-12 | admin | Đặt lại "Nhắc trước hạn" = `3` | Lưu thành công |

---

## 4. Phân quyền, tổ chức, dự án (PQ)

| Mã | Tài khoản | Thao tác | Kết quả mong đợi |
|---|---|---|---|
| PQ-01 | admin | Người dùng & phân quyền → **Vai trò & quyền** | Ma trận 6 vai trò × 30 quyền; cột Quản trị luôn tích xanh (không sửa được) |
| PQ-02 | admin | Bỏ tích "Xem TMĐT & chi phí" ở cột **Kế toán** → Lưu | Đăng nhập `ketoan`: menu **TMĐT & chi phí** biến mất; mở `index.php?r=budget/index` → trang lỗi 403 |
| PQ-03 | admin | Tích lại quyền vừa bỏ → Lưu | `ketoan` thấy lại menu |
| PQ-04 | admin | Thêm vai trò mới: mã `truongphong`, tên "Trưởng phòng" → Lưu | Cột mới xuất hiện trong ma trận |
| PQ-05 | admin | Người dùng → Thêm: `test_kt`, vai trò **Cán bộ đấu thầu**, phòng **Kỹ thuật**, **không** nhập mật khẩu | Báo "Nhập mật khẩu cho tài khoản mới" |
| PQ-06 | admin | Nhập lại mật khẩu `123456` → Lưu | Tạo thành công |
| PQ-07 | test_kt | Hồ sơ mời thầu | **Chỉ thấy GT-2026-0003** (dự án DA-2026-02 thuộc phòng Kỹ thuật). Không thấy 0001, 0002, 0004 → kiểm soát theo **thuộc tính phòng ban/dự án** |
| PQ-08 | test_kt | Mở trực tiếp `index.php?r=tenders/view&id=2` | 403 "Bạn không được phân quyền xem hồ sơ thầu này" |
| PQ-09 | dauthau | GT-2026-0002 → tab **Phân quyền & mời thầu** → thêm `test_kt` quyền "Chỉ xem" | `test_kt` nhận thông báo "Bạn được phân công vào gói thầu…" và nay thấy GT-2026-0002 (**phân quyền theo từng hồ sơ**) |
| PQ-10 | admin | Thêm người dùng vai trò **Nhà cung cấp** nhưng không chọn NCC | Báo "Tài khoản nhà cung cấp phải gắn với một nhà cung cấp" |
| PQ-11 | admin | Sửa chính tài khoản `admin`, bỏ tích "Đang hoạt động" | Báo "Không thể tự vô hiệu hóa tài khoản của mình" |
| PQ-12 | admin | Sửa `test_kt`, bỏ "Đang hoạt động" → Lưu; đăng nhập `test_kt` | Báo "Tài khoản đã bị vô hiệu hóa" |
| PQ-13 | admin | Cơ cấu tổ chức → thêm phòng ban "PBTEST" → xóa | Xóa thành công |
| PQ-14 | admin | Xóa phòng **Kế hoạch – Đấu thầu** | Báo "Phòng ban đang có người dùng hoặc dự án, không thể xóa" |
| PQ-15 | dauthau | Dự án → Thêm dự án với mã `DA-2026-01` (trùng) | Báo "Mã dự án đã tồn tại" |
| PQ-16 | dauthau | Thêm dự án `DA-TEST`, tên "Dự án kiểm thử", phòng Kế hoạch – Đấu thầu | Mở trang chi tiết dự án: KPI TMĐT = 0; các khối Kế hoạch / Gói thầu / Hợp đồng / Tài liệu |
| PQ-17 | kiemsoat | Mở `index.php?r=projects/form` | 403 (không có quyền quản lý dự án) |

---

## 5. Nhà cung cấp (NCC)

| Mã | Tài khoản | Thao tác | Kết quả mong đợi |
|---|---|---|---|
| NCC-01 | dauthau | Nhà cung cấp → tìm "Sao Việt"; lọc trạng thái "Chờ duyệt" | Tìm đúng NCC-0001; bộ lọc chỉ hiện các NCC chờ duyệt (có NCC tự đăng ký ở AUTH-12) |
| NCC-02 | dauthau | Thêm NCC với MST `abc` | Báo "…mã số thuế hợp lệ (10 hoặc 13 số)…" |
| NCC-03 | dauthau | Mở NCC-0001 | 6 tab. KPI tỷ lệ trúng thầu 1/1; tab Lịch sử đấu thầu có HSDT-2026-0001; tab Hợp đồng có HD-2026-0001 |
| NCC-04 | dauthau | Tab **Năng lực** → thêm "Kinh nghiệm / HĐ tương tự", nội dung "HĐ cung cấp máy chủ cho Ngân hàng X", giá trị 2.000.000.000, năm 2025 | Dòng mới xuất hiện; xóa được |
| NCC-05 | dauthau | Tab **Tài liệu** → tải `test.pdf` | Tài liệu mới loại "Hồ sơ năng lực" trong danh sách |
| NCC-06 | dauthau | Mở NCC "Công ty TNHH Kiểm Thử" → **Thẩm định** → "Đã duyệt", ghi chú "Đủ hồ sơ" | Trạng thái xanh "Đã duyệt"; `ncctest` nhận thông báo; cổng của `ncctest` hết hộp cảnh báo |
| NCC-07 | dauthau | Đổi NCC-0003 sang **Tạm ngưng** | `ncc3` mở GT-2026-0002: ô chào giá bị khóa, không có nút nộp, có thông báo "Hồ sơ doanh nghiệp chưa được duyệt nên chưa thể nộp HSDT" → **sau đó đổi lại "Đã duyệt"** |
| NCC-08 | giamdoc | Mở NCC-0002 → **Đánh giá hiệu suất** → kéo 4 thanh (vd 9, 7, 8, 8) → Lưu | Tab Đánh giá có dòng mới, trung bình 8,00; điểm hiệu suất ở đầu trang và biểu đồ **radar** cập nhật |
| NCC-09 | kiemsoat | Mở một NCC | Không có nút Sửa / Thẩm định / Đánh giá (chỉ xem) |

---

## 6. Mẫu HSMT & hồ sơ mời thầu (HSMT)

| Mã | Tài khoản | Thao tác | Kết quả mong đợi |
|---|---|---|---|
| HSMT-01 | dauthau | Mẫu HSMT | 3 thẻ mẫu, mỗi thẻ có lĩnh vực, hình thức, phương pháp, trọng số, danh sách tiêu chí |
| HSMT-02 | dauthau | Thêm mẫu: tên "Mẫu thử", nội dung có `{{ten_goi_thau}}` và `{{gia_goi_thau}}`, 2 tiêu chí (60đ, 40đ) → Lưu | Mẫu mới xuất hiện; bảng bên phải liệt kê 14 biến thay thế |
| HSMT-03 | dauthau | Trên thẻ "Mẫu thử" → **Tạo HSMT từ mẫu**: dự án DA-2026-01, khoản mục "**Thiết bị mạng, bảo mật (còn 50.000.000 đ)**", giá gói **100.000.000**, tên "Gói thử ngân sách", nhập 1 hạng mục → Tạo hồ sơ | Tạo thành công kèm **cảnh báo vàng**: "Cảnh báo ngân sách: giá gói thầu 100.000.000 đ vượt hạn mức còn lại… (50.000.000 đ)" |
| HSMT-04 | dauthau | Tab **Nội dung HSMT** của gói vừa tạo | Biến đã được thay bằng tên gói và "100.000.000 đ" (**tự động tạo HSMT**). Tab Tiêu chí có 2 tiêu chí từ mẫu |
| HSMT-05 | dauthau | Tạo HSMT với "Thời điểm mở thầu" **trước** "Thời điểm đóng thầu" | Báo "Thời điểm mở thầu phải sau thời điểm đóng thầu" |
| HSMT-06 | dauthau | Tạo HSMT với giá gói = 0 | Báo "Giá gói thầu phải lớn hơn 0" |
| HSMT-07 | dauthau | Gói "Gói thử ngân sách" → **Hủy thầu** → để trống lý do | Không cho gửi (trường bắt buộc). Nhập "Thử nghiệm" → trạng thái **Đã hủy** kèm lý do |
| HSMT-08 | dauthau | Mở **GT-2026-0003** (Nháp) → **Trình duyệt** ngay | Báo "Chưa thể trình duyệt: Thời điểm đóng thầu phải sau hiện tại ít nhất 1 giờ." |
| HSMT-09 | dauthau | GT-2026-0003 → **Sửa** → Thời điểm đóng thầu = hôm nay + 7 ngày 09:00, mở thầu 09:30 cùng ngày → Lưu | Lưu thành công; tab Lịch sử ghi thay đổi `close_at` |
| HSMT-10 | dauthau | Tab **Hạng mục**: thêm "Hệ thống PCCC", ĐVT Gói, SL 1, đơn giá 500.000.000 | Tổng dự toán tăng lên 6.400.000.000 và **vượt** giá gói 5.900.000.000 → tab Tổng quan có cảnh báo "Tổng dự toán hạng mục vượt giá gói thầu". Xóa hạng mục vừa thêm để hết cảnh báo |
| HSMT-11 | dauthau | Tab **Tiêu chí**: thêm "Bảo hành" 10đ | Tổng = 110đ → cảnh báo "khuyến nghị 100"; xóa tiêu chí vừa thêm |
| HSMT-12 | dauthau | Tab Nội dung → chọn mẫu → **Sinh tự động** → xác nhận | Nội dung được sinh lại: có tên gói, giá "5.900.000.000 đ", thời điểm đóng thầu vừa đặt, bảng 3 hạng mục, 4 tiêu chí và công thức "Điểm tổng hợp = 60% × …" |
| HSMT-13 | dauthau | Sửa tay một dòng nội dung → **Lưu nội dung** | Lưu thành công |
| HSMT-14 | dauthau | Tab **Phân quyền & mời thầu**: thêm `kythuat` quyền "**Tổ chuyên gia (chấm điểm)**" | Danh sách thành viên cập nhật; `kythuat` nhận thông báo |
| HSMT-15 | dauthau | Tab **Tài liệu** → tải `test.pdf`, loại "Bản vẽ kỹ thuật", mức **Công khai** | Tài liệu xuất hiện trong tab |
| HSMT-16 | dauthau | **In / PDF** | Trang in theo thể thức văn bản hành chính (quốc hiệu, phụ lục hạng mục, tiêu chí); Ctrl+P lưu được PDF |
| HSMT-17 | dauthau | **Trình duyệt** GT-2026-0003 | "Đã trình phê duyệt HSMT"; trạng thái **Chờ duyệt**; thanh tiến trình sáng bước 2; nút Sửa biến mất |
| HSMT-18 | dauthau | Mở trực tiếp `index.php?r=tenders/edit&id=3` | Báo "Chỉ sửa được hồ sơ ở trạng thái Nháp." |
| HSMT-19 | dauthau | Mở GT-2026-0002 → **Đóng thầu** (trước hạn) | Báo "Chưa đến thời điểm đóng thầu (…)" |
| HSMT-20 | dauthau | Hồ sơ mời thầu → bấm các nút lọc trạng thái, tìm "mạng" | Số đếm đúng theo trạng thái; tìm ra GT-2026-0002 |

---

## 7. Phê duyệt nhiều bước (PD)

| Mã | Tài khoản | Thao tác | Kết quả mong đợi |
|---|---|---|---|
| PD-01 | giamdoc | Menu **Phê duyệt** (có số đỏ) | Danh sách chờ: GT-2026-0004 và GT-2026-0003, kèm tóm tắt (giá gói, thời điểm đóng thầu) |
| PD-02 | giamdoc | GT-2026-0004 → bấm **Từ chối** khi để trống ý kiến | Báo "Vui lòng nhập lý do từ chối." |
| PD-03 | giamdoc | Từ chối GT-2026-0004 với ý kiến "Bổ sung danh sách mời thầu" | "Đã từ chối hồ sơ"; GT-2026-0004 về **Nháp**; `dauthau` nhận thông báo đỏ "Hồ sơ bị từ chối phê duyệt – Lý do: …" |
| PD-04 | dauthau | GT-2026-0004 → Trình duyệt lại | Báo "Hình thức Chào hàng cạnh tranh cần có danh sách nhà thầu được mời." |
| PD-05 | dauthau | Tab Phân quyền & mời thầu → chọn NCC-0001, NCC-0002 → Gửi thư mời → Trình duyệt | Thành công; tab **Phê duyệt** hiển thị vòng 2 |
| PD-06 | giamdoc | Phê duyệt GT-2026-0003 và GT-2026-0004 (ý kiến tùy chọn) | Cả hai chuyển **Đã duyệt**; `dauthau` nhận "Hồ sơ đã được phê duyệt"; lịch sử phê duyệt ở trang Phê duyệt |
| PD-07 | ketoan | Mở menu Phê duyệt | Không thấy các bước dành cho vai trò Lãnh đạo |
| PD-08 | dauthau | GT-2026-0003 → **Công bố** → xác nhận | "Đã công bố HSMT và gửi thông báo tới 1 tài khoản nhà thầu". Tab Tài liệu có "**HSMT GT-2026-0003 (bản công bố)**" mức Công khai (**tự động công bố vào kho**) |
| PD-09 | ncc3 (B) | Xem chuông | "Thư mời thầu: GT-2026-0003" |
| PD-10 | admin | Cấu hình → **Quy trình phê duyệt** → "Hồ sơ mời thầu" → thêm Bước 2 "Kế toán thẩm định giá gói thầu", vai trò **Kế toán** → Lưu. Tạo một HSMT mới từ mẫu (có hạng mục) rồi trình duyệt | Sau khi `giamdoc` duyệt bước 1, `ketoan` nhận "Có hồ sơ cần phê duyệt"; HSMT chỉ thành "Đã duyệt" sau khi `ketoan` duyệt bước 2 → **xóa Bước 2** để trả về như cũ. *(Vai trò được chọn phải có quyền "Tham gia phê duyệt")* |

---

## 8. Cổng nhà cung cấp – nộp HSDT (CNCC)

Thực hiện ở trình duyệt **B**.

| Mã | Tài khoản | Thao tác | Kết quả mong đợi |
|---|---|---|---|
| CNCC-01 | ncc1 | **Gói thầu đang mời** | Thấy GT-2026-0002 ("Đang nhận HSDT", còn ~10 ngày) và GT-2026-0001 (đã trao thầu). **Không** thấy GT-2026-0003 (không được mời) |
| CNCC-02 | ncc1 | Mở trực tiếp `index.php?r=portal/tender&id=3` | "Gói thầu không tồn tại hoặc bạn không được mời tham gia." |
| CNCC-03 | ncc1 | Mở GT-2026-0002 | Có **đồng hồ đếm ngược**; khối "Tải hồ sơ mời thầu" có tệp HSMT và yêu cầu kỹ thuật; tải về đọc được nội dung |
| CNCC-04 | ncc1 | Chỉ nhập đơn giá dòng 1 = 330.000.000 → **Nộp HSDT** | Báo "Vui lòng chào giá cho tất cả hạng mục (thiếu: Switch core…)" |
| CNCC-05 | ncc1 | Nhập đơn giá **330.000.000 / 265.000.000 / 41.000.000**; hiệu lực 90; giao hàng 45; bảo hành 36; đề xuất "Fortinet FortiGate 600F, Cisco Catalyst"; đính kèm `test.pdf` → **Nộp HSDT** → xác nhận | Cột thành tiền và tổng **tự tính 1.600.000.000 đ**. Sau khi nộp: hộp xanh "HSDT … Đã nộp · nộp lúc …"; chuông có "Xác nhận nộp HSDT" |
| CNCC-06 | ncc1 | Sửa đơn giá dòng 3 thành 40.000.000 → **Nộp lại (sửa đổi)** | Tổng 1.590.000.000 đ; thời điểm nộp cập nhật; `dauthau` nhận "HSDT được sửa đổi". **Đặt lại 41.000.000 và nộp lại** để khớp số liệu các phần sau |
| CNCC-07 | ncc1 | **Rút HSDT** → xác nhận | Trạng thái "Đã rút"; `dauthau` nhận "Nhà thầu rút HSDT". Sau đó **nộp lại** (CNCC-05) để tiếp tục |
| CNCC-08 | ncc1 | Tệp đính kèm → **Gỡ** | Tệp biến mất khỏi danh sách (chỉ làm được trước giờ đóng thầu). Sau đó **đính kèm lại `test.pdf` → Nộp lại** để dùng ở DG-05 |
| CNCC-09 | dauthau (A) | GT-2026-0002 → tab **HSDT** | Biểu tượng khóa: "HSDT đang được **niêm phong điện tử**… Đã nhận **2** hồ sơ" và **không** hiện giá |
| CNCC-10 | dauthau (A) | Mở `index.php?r=evaluation/tender&id=2` | Bị chuyển về, cảnh báo "HSDT còn niêm phong đến thời điểm đóng thầu." |
| CNCC-11 | ncc1 | **HSDT của tôi** | Danh sách HSDT kèm trạng thái gói và kết quả |
| CNCC-12 | ncc1 | **Hồ sơ doanh nghiệp** → sửa địa chỉ → Cập nhật | Lưu thành công; ô MST **không sửa được** |
| CNCC-13 | ncc1 | Tab Năng lực → thêm "Chứng chỉ ISO 9001:2015"; tab Tài liệu → tải `test.pdf` loại "Hồ sơ pháp lý" | Hiện trong hồ sơ; `dauthau` thấy ở trang NCC-0001 |
| CNCC-14 | ncc1 | Mở trực tiếp `index.php?r=tenders/index`, `index.php?r=documents/index` | Đều bị đưa về Tổng quan cổng NCC |
| CNCC-15 | ncc3 | Gói thầu → GT-2026-0003 (được mời) | Thấy gói, tải được HSMT bản công bố và bản vẽ công khai, có form nộp HSDT |

---

## 9. Đóng thầu, mở thầu, đánh giá, trao thầu (DG)

| Mã | Tài khoản | Thao tác | Kết quả mong đợi |
|---|---|---|---|
| DG-01 | admin | GT-2026-0002 → **Đóng thầu** → xác nhận. *(Quản trị được đóng sớm để thử. Cách thật: phpMyAdmin chạy `UPDATE tenders SET close_at = NOW() - INTERVAL 1 MINUTE WHERE code='GT-2026-0002';` rồi Cấu hình → Chạy nhắc việc → hệ thống **tự đóng**, kết quả `closed: 1`)* | Trạng thái **Đóng thầu**; `dauthau` nhận "Gói thầu đã đóng" (nếu tự đóng) |
| DG-02 | ncc1 (B) | Mở lại GT-2026-0002 | Form chào giá bị khóa (ô nhập mờ), không còn nút nộp/rút |
| DG-03 | dauthau | GT-2026-0002 → **Mở thầu** → xác nhận | Chuyển sang trang Đánh giá, "Đã mở thầu 2 HSDT…". Tab Tài liệu của HSMT có "**Biên bản mở thầu GT-2026-0002**" liệt kê 2 nhà thầu và giá |
| DG-04 | dauthau | Tab 1 **Tính hợp lệ**: bấm ✗ với HSDT của Minh Phát mà **không** nhập ghi chú | Báo "Nhập lý do HSDT không hợp lệ." |
| DG-05 | dauthau | Bấm ✓ (Hợp lệ) cho cả 2 HSDT | Trạng thái "Hợp lệ"; tải được tệp đính kèm của `ncc1` (niêm phong đã mở) |
| DG-06 | dauthau | Tab 2 **Chấm điểm**, nhập theo bảng dưới → **Lưu phiếu chấm** | "Đã lưu 8 điểm chấm của bạn"; mỗi ô hiện "TB: … (1 người)". Bảng **Tổ chuyên gia** liệt kê 3 thành viên: Trần Thị Lan "Đã chấm đủ 8/8", Hoàng Minh Tuấn và Đặng Thu Trang "Chưa chấm". Nhãn góc trên: **Đã chấm đủ: 1/3 chuyên gia** (màu vàng) |
| DG-06A | dauthau | Bấm **Tính điểm & xếp hạng** ngay | Bị chặn: "Chưa thể tính điểm: cần tối thiểu 3 chuyên gia chấm đủ tất cả tiêu chí cho mọi HSDT hợp lệ, hiện mới có 1…" |
| DG-07 | kythuat | Mở Đánh giá HSDT → GT-2026-0002 → tab 2, nhập **đúng các điểm như trên** → Lưu | Mỗi ô hiện "(2 người)"; nhãn **Đã chấm đủ: 2/3** |
| DG-07A | chuyengia | Đăng nhập `chuyengia` → Đánh giá HSDT → GT-2026-0002 → tab 2. Thử chỉ nhập 4 ô của Sao Việt → Lưu | Bảng Tổ chuyên gia: Đặng Thu Trang **Chấm dở 4/8**; nhãn vẫn 2/3 |
| DG-07B | chuyengia | Nhập nốt 4 ô của Minh Phát (đúng bảng điểm) → Lưu | Mỗi ô "(3 người)"; cả 3 thành viên **Đã chấm đủ**; nhãn **Đã chấm đủ: 3/3** chuyển màu xanh |
| DG-08 | dauthau | Tab 3 **So sánh báo giá** | Ô **xanh** = giá thấp nhất từng hạng mục (Sao Việt: 330tr, 265tr, 41tr). % chênh so dự toán màu xanh (âm). Biểu đồ thanh ngang so sánh giá |
| DG-09 | dauthau | **Tính điểm & xếp hạng** → tab 4 | Đúng số liệu ở bảng kết quả bên dưới |
| DG-10 | dauthau | **Xuất Excel** | Tải `danh-gia-GT-2026-0002.csv`, mở bằng Excel hiển thị đúng tiếng Việt |
| DG-11 | dauthau | Tab 4 | **Không** có nút Trao thầu (chỉ Lãnh đạo có quyền `bids.award`) |
| DG-12 | giamdoc | Tab 4 → dòng **#2** → bấm Trao thầu khi để trống giải trình | Trình duyệt không cho gửi (giải trình bắt buộc khi chọn nhà thầu không xếp hạng nhất) |
| DG-13 | giamdoc | Dòng 🥇 Sao Việt → **Trao thầu** → xác nhận | Chuyển tới **dự thảo hợp đồng mới** (giá trị 1.600.000.000 đ, trạng thái **Nháp**). GT-2026-0002 thành **Đã trao thầu**; Tài liệu HSMT có "**QĐ phê duyệt KQLCNT GT-2026-0002**" |
| DG-14 | ncc1 / ncc2 (B) | Xem chuông và HSDT của tôi | `ncc1`: "Chúc mừng! Bạn trúng thầu…", trạng thái **Trúng thầu**, hạng #1, điểm. `ncc2`: "Rất tiếc…", trạng thái **Không trúng thầu** |
| DG-15 | ncc1 (B) | Mở GT-2026-0002 | Hộp xanh "Kết quả lựa chọn nhà thầu: Công ty CP Công nghệ Sao Việt — 1.600.000.000 đ" |

Bảng điểm nhập ở DG-06, DG-07 và DG-07B — cả 3 chuyên gia nhập giống nhau (GT-2026-0002 có 4 tiêu chí, tối đa 40/20/20/20):

| Tiêu chí | Sao Việt (ncc1) | Minh Phát (ncc2) |
|---|---|---|
| Đáp ứng thông số kỹ thuật (40) | 35 | 32 |
| Kinh nghiệm & năng lực (20) | 18 | 16 |
| Giải pháp triển khai, tiến độ (20) | 17 | 15 |
| Bảo hành, hỗ trợ kỹ thuật (20) | 18 | 16 |
| **Tổng** | **88** | **79** |

Kết quả mong đợi ở DG-09 (QCS: KT 60%, giá 40%, KT tối thiểu 70):

| Nhà thầu | Giá dự thầu | Điểm KT | Điểm giá | Điểm tổng hợp | Hạng |
|---|---|---|---|---|---|
| Sao Việt | 1.600.000.000 | 88,00 | 100,00 | **92,80** (= 0,6×88 + 0,4×100) | 🥇 1 |
| Minh Phát | 1.640.000.000 | 79,00 | 97,56 (= 1,60/1,64×100) | **86,42** | 2 |

> **Biến thể kiểm tra điểm sàn:** nếu một chuyên gia chấm Minh Phát tổng dưới 70 (ví dụ 15/10/10/10 = 45) thì điểm KT trung bình = (79+79+45)/3 = 67,67 < 70. Khi đó Minh Phát "**Không đạt**", không có hạng, không có nút trao thầu. Chấm lại 79 rồi tính lại để quay về kết quả trên.

---

## 10. Hợp đồng, đơn hàng, nghiệm thu, hóa đơn, giải ngân, quyết toán (HD)

### 10.1. Hợp đồng mới (từ GT-2026-0002)

| Mã | Tài khoản | Thao tác | Kết quả mong đợi |
|---|---|---|---|
| HD-01 | dauthau | Hợp đồng mới → tab Hợp đồng → nhập giá trị **1.800.000.000** → Lưu | Báo "Giá trị hợp đồng không được vượt giá gói thầu." |
| HD-02 | dauthau | **Trình duyệt** khi chưa có ngày ký | Báo "Cần nhập ngày ký và ngày kết thúc…" |
| HD-03 | dauthau | Nhập ngày ký hôm nay, ngày kết thúc +90 ngày, giữ giá trị 1.600.000.000 → Lưu → **Trình duyệt** | "Đã trình phê duyệt hợp đồng"; trạng thái **Chờ phê duyệt** |
| HD-04 | ketoan | Phê duyệt (bước 1 "Kế toán kiểm tra điều khoản thanh toán") | `giamdoc` nhận "Có hồ sơ cần phê duyệt" (bước 2) |
| HD-05 | giamdoc | Phê duyệt bước 2 | Hợp đồng **Đang thực hiện**; tab Phê duyệt & lịch sử hiển thị 2 bước |
| HD-06 | dauthau | Tab **Đơn hàng** → giá trị **2.000.000.000** | Báo "Giá trị đơn hàng … vượt giá trị hợp đồng" |
| HD-07 | dauthau | Đơn hàng: nội dung "Giao NGFW và switch core", giá trị 800.000.000, hạn giao +30 ngày → Phát hành | Đơn **Đã gửi NCC**; `ncc1` nhận "Đơn đặt hàng mới cần xác nhận" |
| HD-08 | ncc1 (B) | **Đơn đặt hàng** → sửa ngày giao, ghi chú "Giao 2 đợt" → **Xác nhận** | Trạng thái "NCC đã xác nhận"; `dauthau` nhận thông báo |
| HD-09 | dauthau | Đơn hàng → **Đã giao** | Trạng thái "Đã giao hàng" |
| HD-10 | dauthau | Tab **Nghiệm thu**: "Nghiệm thu lắp đặt NGFW", kết quả Đạt, đính kèm `test.pdf` → Lập | Dòng mới có liên kết "Xem" biên bản (tài liệu loại Biên bản nghiệm thu); `ncc1` nhận "Kết quả nghiệm thu: Đạt" |
| HD-11 | ketoan | Tab **Thanh toán** → thêm đợt "Tạm ứng 30%" 480.000.000, hạn +7 ngày; thêm đợt 2 với số tiền 1.200.000.000 | Đợt 1 thêm được. Đợt 2 báo "Tổng các đợt thanh toán (1.680.000.000 đ) vượt giá trị hợp đồng." |

### 10.2. Hợp đồng HD-2026-0001 (dữ liệu mẫu)

| Mã | Tài khoản | Thao tác | Kết quả mong đợi |
|---|---|---|---|
| HD-12 | ketoan | Hợp đồng & giải ngân → HD-2026-0001 có nhãn "1 HĐơn chờ kiểm tra" → mở | KPI đã giải ngân 945.000.000 đ (30%) |
| HD-13 | ketoan | Tab **Đơn hàng** → phát hành đơn 100.000.000 | Báo **vượt giá trị hợp đồng (đã đặt 3.150.000.000 đ)**: đơn hàng mẫu đã đặt đủ 100% |
| HD-14 | ketoan | Tab **Thanh toán** → đợt "Thanh toán 50%…" → không chọn hóa đơn → **Đề nghị** | Báo "Chưa thể trình duyệt chi: Cần gắn hóa đơn đã được kiểm tra hợp lệ." (danh sách hóa đơn trống vì hóa đơn 0002156 chưa kiểm tra) |
| HD-15 | ketoan | Tab **Hóa đơn** → 0002156 → bấm ✓ Hợp lệ | Trạng thái "Hợp lệ"; `ncc1` nhận thông báo |
| HD-16 | ketoan | Tab Thanh toán → đợt 50%: chọn hóa đơn 0002156 (1.732.500.000) và nghiệm thu "Biên bản giao nhận…" → **Đề nghị** | "Đã trình duyệt chi"; trạng thái **Chờ duyệt chi**; `ketoan` nhận "Có hồ sơ cần phê duyệt" (bước 1 của quy trình thanh toán) |
| HD-17 | ketoan, rồi giamdoc | Phê duyệt → trang Phê duyệt hiển thị tóm tắt "HD-2026-0001 · … · đã chi 945.000.000/3.150.000.000" | Sau bước của `giamdoc`: trạng thái **Đã duyệt chi**; người có quyền thanh toán nhận "Khoản thanh toán đã được duyệt chi" |
| HD-18 | ketoan | Đợt 50% → nhập số tiền 2.000.000.000 → Giải ngân | Báo "Số tiền giải ngân không hợp lệ (tối đa 1.575.000.000 đ)" |
| HD-19 | ketoan | Giữ 1.575.000.000, ngày hôm nay → **Giải ngân** | "Đã giải ngân … và hạch toán vào chi phí dự án". Đợt → **Đã giải ngân**; hóa đơn 0002156 → **Đã thanh toán**; KPI đã giải ngân 2.520.000.000 đ (80%) |
| HD-20 | ketoan | TMĐT & chi phí → DA-2026-01 → tab **Chi phí thực tế** | Có dòng "Giải ngân HĐ HD-2026-0001: Thanh toán 50%…" 1.575.000.000 với nhãn **giải ngân HĐ**, không có nút xóa |
| HD-21 | ncc1 (B) | **Hợp đồng & thanh toán** | HD-2026-0001: Đã nhận 80%; đợt 50% "Đã giải ngân". Chuông: "Đã thanh toán 1.575.000.000 đ" |

### 10.3. Hóa đơn điện tử từ cổng NCC

| Mã | Tài khoản | Thao tác | Kết quả mong đợi |
|---|---|---|---|
| HD-22 | ncc1 (B) | **Hóa đơn điện tử** → HĐ HD-2026-0001, số `0002156`, điền đủ ngày, tiền hàng 100.000.000, tệp PDF → Gửi | Báo "Số hóa đơn đã được gửi trước đó." |
| HD-23 | ncc1 | Số `0003001`, tiền hàng 2.000.000.000, kèm PDF | Báo "Tổng giá trị hóa đơn (trước thuế) vượt giá trị hợp đồng." |
| HD-24 | ncc1 | Số `0003001`, tiền hàng 700.000.000, VAT 70.000.000, **không** kèm tệp | Trình duyệt yêu cầu chọn tệp. Nếu vượt qua được thì server báo "Vui lòng đính kèm tệp hóa đơn điện tử" |
| HD-25 | ncc1 | Như trên, kèm `test.pdf` → Gửi | Hóa đơn "Chờ kiểm tra"; `ketoan` nhận "Hóa đơn mới cần kiểm tra" |
| HD-26 | ketoan | HD-2026-0001 → tab Hóa đơn → 0003001 → bấm ✗ không ghi chú; sau đó ghi "Sai số tiền so với biên bản" → ✗ | Lần 1 báo "Nhập lý do từ chối hóa đơn"; lần 2 → **Bị trả lại**; `ncc1` thấy lý do trong danh sách hóa đơn |

### 10.4. Quyết toán và thanh toán phần còn lại

| Mã | Tài khoản | Thao tác | Kết quả mong đợi |
|---|---|---|---|
| HD-27 | ketoan | HD-2026-0001 → tab **Quyết toán** → giá trị 2.000.000.000 → Quyết toán | Báo "Giá trị quyết toán nhỏ hơn số đã thanh toán (2.520.000.000 đ)…" |
| HD-28 | ketoan | Giá trị **3.150.000.000**, ghi chú "Quyết toán theo hợp đồng" → Quyết toán → xác nhận | Hợp đồng **Đã quyết toán**. Thông báo "…Còn phải thanh toán 630.000.000 đ". Đợt 20% chưa chi tự chuyển **Bị từ chối** với ghi chú "[Hủy do quyết toán]". `ncc1` nhận "Hợp đồng … đã được quyết toán" |
| HD-29 | ketoan | Tab Quyết toán → **Tạo đợt thanh toán quyết toán (630.000.000 đ)** | Tab Thanh toán có đợt mới "Thanh toán quyết toán" 630.000.000 |
| HD-30 | ncc1 (B) | Gửi hóa đơn số `0003002`, tiền hàng 600.000.000, VAT 60.000.000, kèm PDF | Gửi thành công (hợp đồng đã quyết toán vẫn nhận hóa đơn) |
| HD-31 | ketoan → giamdoc → ketoan | Kiểm tra hóa đơn 0003002 hợp lệ → đề nghị thanh toán đợt quyết toán kèm hóa đơn → 2 bước duyệt → giải ngân 630.000.000 | Đã giải ngân 3.150.000.000 đ (100%); ô "Còn phải trả theo quyết toán" = 0 |
| HD-32 | ketoan | Tab Quyết toán | Không có form đánh giá NCC (Kế toán không có quyền đánh giá) |
| HD-33 | giamdoc | Tab Quyết toán → đánh giá 4 tiêu chí → Lưu | Hộp xanh "Đã đánh giá hiệu suất nhà cung cấp cho hợp đồng này"; trang NCC-0001 có đánh giá gắn mã HD-2026-0001 |
| HD-34 | dauthau | Hợp đồng mới (GT-2026-0002) → **Chấm dứt** → lý do "Thử nghiệm" *(tùy chọn, làm cuối)* | Trạng thái **Chấm dứt**; `ncc1` nhận thông báo đỏ |
| HD-35 | dauthau | Hợp đồng & giải ngân → **Xuất Excel** | Tệp `hop-dong.csv` đúng tiếng Việt |

---

## 11. Kế hoạch đấu thầu & tiến độ (KH)

| Mã | Tài khoản | Thao tác | Kết quả mong đợi |
|---|---|---|---|
| KH-01 | dauthau | Kế hoạch & tiến độ | Thẻ KH-2026-0001 (Đang thực hiện) và KH-2026-0002 (Nháp) với thanh tiến độ, số việc hoàn thành/trễ |
| KH-02 | dauthau | Lập kế hoạch: dự án DA-2026-02, tên "Kế hoạch kiểm thử" → Lưu → **Trình duyệt** ngay | Báo "Kế hoạch chưa có công việc nào." |
| KH-03 | dauthau | Khối **Tự động sinh các giai đoạn chuẩn**: gói GT-2026-0003, phụ trách **Hoàng Minh Tuấn (kythuat)**, ngày bắt đầu hôm nay → Sinh kế hoạch | "Đã sinh 9 công việc…". **Gantt** hiển thị 9 thanh nối tiếp khoảng 2 tháng, có vạch đỏ "hôm nay". Tên việc có tiền tố "GT-2026-0003:" |
| KH-04 | kythuat | Chuông | Có thông báo "Bạn được giao việc" *(nếu giao qua form "Thêm công việc")*. Menu **Việc của tôi** có số việc |
| KH-05 | dauthau | Thêm công việc: "Khảo sát hiện trạng", phụ trách kythuat, bắt đầu 5 ngày trước, hạn **hôm qua** | Dòng việc tô **đỏ** kèm "Trễ 1 ngày"; KPI "Trễ hạn" = 1; thanh Gantt màu đỏ. `kythuat` nhận "Bạn được giao việc…" |
| KH-06 | dauthau | Thêm công việc với ngày bắt đầu sau hạn | Báo "Ngày bắt đầu phải trước hạn hoàn thành." |
| KH-07 | admin | Cấu hình → Chạy nhắc việc | `kythuat` và `dauthau` nhận "**Công việc quá hạn**: Khảo sát hiện trạng". `kythuat` cũng nhận "Công việc sắp đến hạn" cho việc đầu tiên nếu hạn trong 3 ngày |
| KH-08 | kythuat | **Việc của tôi** → việc "Lập hồ sơ mời thầu": tiến độ 50, trạng thái Đang làm, ghi chú "Đã xong phần kỹ thuật" → ✓ | Thanh tiến độ 50%; lịch sử cập nhật ở trang kế hoạch |
| KH-09 | kythuat | Việc "Khảo sát hiện trạng" → trạng thái **Vướng mắc**, ghi chú "Chưa có mặt bằng" | `dauthau` (người lập kế hoạch) nhận thông báo đỏ "Vướng mắc: Khảo sát hiện trạng" |
| KH-10 | kythuat | Việc "Khảo sát hiện trạng" → trạng thái **Hoàn thành** | Tiến độ tự thành 100%; dòng hết đỏ; `dauthau` nhận "Hoàn thành: …" |
| KH-11 | kiemsoat | Mở kế hoạch vừa tạo | Chỉ xem: không có form cập nhật tiến độ, thêm/xóa việc hay trình duyệt (Kiểm soát nội bộ chỉ có quyền xem kế hoạch) |
| KH-12 | dauthau | **Trình duyệt** kế hoạch | Trạng thái Chờ duyệt |
| KH-13 | giamdoc | Phê duyệt kế hoạch | Kế hoạch **Đã duyệt**; lần cập nhật tiến độ tiếp theo chuyển sang **Đang thực hiện** |
| KH-14 | dauthau | Cập nhật tất cả việc lên 100% | Xuất hiện nút **Hoàn thành kế hoạch** → bấm → trạng thái Hoàn thành |

---

## 12. TMĐT & kiểm soát chi phí (TM)

| Mã | Tài khoản | Thao tác | Kết quả mong đợi |
|---|---|---|---|
| TM-01 | ketoan | TMĐT & chi phí | Tổng hợp theo dự án: dự toán, được duyệt, cam kết, đã chi, còn lại; thanh tỷ lệ (xanh = đã chi, vàng = cam kết) |
| TM-02 | ketoan | DA-2026-02 → nhập "Dự phòng **5**%" → **Tính tự động** | "Đã tính chi phí dự phòng 5%: **376.500.000 đ**". Khoản mục dự phòng thành "Chi phí dự phòng (5%)": dự toán 390.000.000, duyệt 376.500.000 |
| TM-03 | ketoan | Bấm ✎ một khoản mục → sửa giá trị được duyệt → Cập nhật | Giá trị đổi; nhật ký ghi giá trị cũ/mới |
| TM-04 | ketoan | Xóa khoản mục "Chi phí xây dựng, cải tạo" (đang gắn GT-2026-0003) | Báo "Khoản mục đã phát sinh gói thầu/chi phí, không thể xóa." |
| TM-05 | ketoan | DA-2026-01 → tab Chi phí thực tế → ghi chi "Phí bảo hiểm công trình", khoản mục "**Chi phí khác (thẩm định, bảo hiểm)**", số tiền **70.000.000** | Ghi nhận thành công kèm **cảnh báo vàng** "…vượt dự toán được duyệt (**85.000.000 đ / 80.000.000 đ**)". Dòng khoản mục tô đỏ ở tab Cơ cấu. `giamdoc` nhận thông báo đỏ "Cảnh báo vượt dự toán" |
| TM-06 | ketoan | Xóa khoản chi vừa ghi | Xóa được. Dòng giải ngân HĐ không có nút xóa |
| TM-07 | ketoan | Tab **So sánh giá thầu** | GT-2026-0001: giá gói 3.300.000.000, trúng 3.150.000.000, tiết kiệm 150.000.000 (4,5%). GT-2026-0002: 1.700.000.000 → 1.600.000.000, tiết kiệm 100.000.000 (5,9%) |
| TM-08 | ketoan | Tab **Biểu đồ** | Biểu đồ ngang Được duyệt / Cam kết / Đã chi theo khoản mục; đường lũy kế giải ngân |
| TM-09 | ketoan | **Xuất Excel** | Tệp `TMDT-DA-2026-01.csv` |
| TM-10 | ketoan | TMĐT & chi phí → dòng DA-TEST (tạo ở PQ-16) → Chi tiết → **Khởi tạo cơ cấu TMĐT** | Tạo 7 khoản mục theo 7 nhóm chi phí, giá trị 0; bấm lần nữa → "Dự án đã có khoản mục TMĐT" |
| TM-11 | dauthau | Mở trang TMĐT của DA-2026-01; sau đó mở trực tiếp `index.php?r=budget/addExpense` | Xem được số liệu nhưng **không** có form thêm khoản mục/ghi chi (Cán bộ đấu thầu không có quyền lập TMĐT); URL trực tiếp → 403 |

---

## 13. Kho tài liệu (TL)

| Mã | Tài khoản | Thao tác | Kết quả mong đợi |
|---|---|---|---|
| TL-01 | admin | Kho tài liệu | Dòng tiêu đề: số tài liệu, phiên bản, dung lượng, "mã hóa AES-256-GCM"; danh sách tài liệu mẫu |
| TL-02 | dauthau | **Tải lên** `test.exe` | Báo "Định dạng .exe không được phép." |
| TL-03 | dauthau | Tải lên tệp > 20MB | Báo vượt dung lượng *(hoặc lỗi upload_max_filesize nếu php.ini nhỏ hơn)* |
| TL-04 | dauthau | Tải lên `test.docx` + `test.pdf` cùng lúc, gắn với "Hồ sơ mời thầu" → GT-2026-0003, thẻ "PCCC, điện" | "Đã tải lên và mã hóa tài liệu"; 2 tài liệu mới, tiêu đề lấy theo tên tệp |
| TL-05 | dauthau | Tìm kiếm "**máy phát điện**" | Ra `test.docx`: tìm theo **nội dung bên trong** tệp Word (cần extension `zip`) |
| TL-06 | admin | Tìm "**Dell**" | Ra "Thuyết minh đề xuất kỹ thuật - HSDT-2026-0001" (tìm trong nội dung tệp txt) |
| TL-07 | dauthau | Lọc Định dạng = PDF; Gắn với = HSMT; Dự án = DA-2026-01 | Kết quả thu hẹp đúng |
| TL-08 | dauthau | Mở `test.docx` → tab **Phiên bản** → chọn tệp khác, ghi chú "Cập nhật công suất" → Phiên bản mới | Có v2 tô xanh; phiên bản hiện tại = v2; người tạo nhận thông báo |
| TL-09 | dauthau | Tạo phiên bản mới mà không ghi chú | Trình duyệt/Server yêu cầu ghi chú thay đổi |
| TL-10 | dauthau | Tải phiên bản v1 | Tải đúng tệp gốc |
| TL-11 | dauthau | v1 → **Khôi phục** | Tạo **v3** với ghi chú "Khôi phục từ phiên bản 1"; nội dung v3 = v1 |
| TL-12 | dauthau | Tab **Thông tin** | Có SHA-256, khóa mã hóa "Khóa hệ thống mặc định (hệ thống)"; form sửa tiêu đề/loại/mức mật/thẻ |
| TL-13 | dauthau | Mở tài liệu PDF → **Xem** | Mở PDF ngay trên trình duyệt (tab mới) |
| TL-14 | dauthau | Kho tài liệu → tìm "Quy chế" | **Không thấy** "Quy chế đấu thầu nội bộ" (mức **Mật**) |
| TL-15 | admin | Mở "Quy chế đấu thầu nội bộ" → tab **Phân quyền & chia sẻ** → cấp cho người dùng Trần Thị Lan (dauthau), tích "Được tải" | `dauthau` nhận "Bạn được chia sẻ tài liệu" và giờ tìm thấy, tải được tài liệu |
| TL-16 | admin | Cấp quyền theo **Phòng ban** "Phòng Tài chính – Kế toán" cho một tài liệu nội bộ của phòng khác | `ketoan` xem được tài liệu đó |
| TL-17 | admin | **Thu hồi** quyền của dauthau ở TL-15 | `dauthau` không còn thấy tài liệu; mở bằng URL → 403 "Bạn không có quyền view tài liệu này"; nhật ký có "Từ chối truy cập" |
| TL-18 | kythuat | Mở tài liệu gắn GT-2026-0003 (thành viên) | Xem được (**kế thừa quyền từ HSMT**) |
| TL-19 | dauthau | Tab **Nhật ký** của tài liệu | Các thao tác xem, tải, tạo phiên bản, khôi phục, cấp quyền kèm người, thời gian, IP |
| TL-20 | dauthau | **Xóa** `test.pdf` | Biến mất khỏi kho; nhật ký kiểm toán vẫn ghi "Xóa (lưu trữ) tài liệu" |
| TL-21 | ncc1 (B) | Mở `index.php?r=documents/view&id=1` | Bị đưa về cổng NCC (NCC chỉ được **tải** tài liệu được phép) |
| TL-22 | ncc2 (B) | Lấy `id` một tài liệu HSDT của `ncc1` (phpMyAdmin bảng `documents`, `entity_type='bid'`), mở `index.php?r=documents/download&id=<id>` | 403: NCC không tải được hồ sơ của NCC khác |

---

## 14. Liên kết chia sẻ an toàn (CS)

| Mã | Tài khoản | Thao tác | Kết quả mong đợi |
|---|---|---|---|
| CS-01 | dauthau | Mở một tài liệu nội bộ → tab Phân quyền & chia sẻ → thời hạn 24 giờ, mật khẩu `abc123`, tối đa **2** lượt tải → Tạo liên kết | Hộp xanh chứa đường dẫn `…index.php?r=share/open&token=…` (bấm để chọn, sao chép) |
| CS-02 | – | Mở liên kết ở cửa sổ ẩn danh (chưa đăng nhập) | Trang tải tài liệu: tên, dung lượng, hạn, "còn 2 lượt tải", ô mật khẩu |
| CS-03 | – | Nhập sai mật khẩu | "Mật khẩu không đúng." |
| CS-04 | – | Nhập đúng → Tải | Tải được tệp gốc; bảng liên kết ở trang tài liệu: lượt tải 1/2 |
| CS-05 | – | Tải lần 2, rồi mở lại liên kết | Lần 2 tải được; lần mở tiếp theo: "Liên kết đã hết lượt tải." |
| CS-06 | dauthau → khách | Tạo liên kết mới có mật khẩu; ở cửa sổ ẩn danh nhập sai 5 lần, lần 6 nhập đúng | Lần 6 vẫn bị chặn: "Nhập sai mật khẩu quá nhiều lần." (chống dò mật khẩu) |
| CS-07 | dauthau | Bấm **Thu hồi** một liên kết, mở lại liên kết đó | "Liên kết không tồn tại hoặc đã bị thu hồi." |
| CS-08 | – | phpMyAdmin: `UPDATE share_links SET expires_at = NOW() - INTERVAL 1 HOUR WHERE id = <id>;` → mở liên kết | "Liên kết đã hết hạn." |
| CS-09 | admin → dauthau | Admin cấp cho `dauthau` quyền **Được sửa** trên "Quy chế đấu thầu nội bộ"; `dauthau` tạo liên kết chia sẻ | Báo "Tài liệu mật không được chia sẻ ra bên ngoài." |
| CS-10 | kiemsoat | Nhật ký kiểm toán → lọc hành động "Tải qua liên kết chia sẻ" | Có bản ghi kèm IP, người dùng "guest" |

---

## 15. Mã hóa khi lưu trữ & khóa khách hàng quản lý – BYOK (MH)

> Làm **đúng thứ tự** MH-01 → MH-12.

| Mã | Tài khoản | Thao tác | Kết quả mong đợi |
|---|---|---|---|
| MH-01 | – | Mở thư mục `storage/documents/<năm>/<tháng>/` | Các tệp `.enc` tên ngẫu nhiên; mở bằng Notepad thấy ký tự **vô nghĩa** (không đọc được nội dung) |
| MH-02 | – | phpMyAdmin → bảng `encryption_keys` | Cột `wrapped_key` là chuỗi đã mã hóa; **không** có khóa dạng rõ |
| MH-03 | admin | Cấu hình → **Khóa mã hóa (BYOK)** | 1 khóa "Khóa hệ thống mặc định" **Đang hoạt động**, có dấu vân tay và số phiên bản tài liệu dùng khóa |
| MH-04 | admin | Nhập khóa khách hàng với chuỗi `abc` | Báo "Khóa phải là 32 byte (256-bit) ở dạng base64 hoặc 64 ký tự hex." |
| MH-05 | admin | Bấm **Sinh khóa mẫu** → ô khóa tự điền (44 ký tự) → tên "Khóa KH Thăng Long" → tích Kích hoạt → **Nhập khóa** | Khóa mới loại **Khách hàng quản lý**, Đang hoạt động; khóa hệ thống chuyển "Không hoạt động" |
| MH-06 | dauthau | Tải lên tài liệu mới "Tài liệu BYOK" → tab Thông tin | Dòng Mã hóa: khóa "Khóa KH Thăng Long" (**khách hàng quản lý**) |
| MH-07 | admin | Tạo khóa hệ thống "Khóa tạm" (Kích hoạt) → tải lên tài liệu "Tài liệu khóa tạm" → **Kích hoạt** lại "Khóa KH Thăng Long" | "Tài liệu khóa tạm" được mã hóa bằng "Khóa tạm" (xem tab Thông tin) |
| MH-08 | admin | Thu hồi "Khóa KH Thăng Long" (đang hoạt động) | Không có nút thu hồi cho khóa đang hoạt động (phải kích hoạt khóa khác trước) |
| MH-09 | admin | "Khóa tạm" → nhập sai tên xác nhận → Thu hồi | Báo "Tên khóa xác nhận không khớp." |
| MH-10 | admin | Nhập đúng "Khóa tạm" → Thu hồi → xác nhận | "Đã thu hồi khóa. 1 phiên bản tài liệu … không còn truy cập được." Khóa có nhãn đỏ **Đã thu hồi** |
| MH-11 | admin | Tải "Tài liệu khóa tạm" | Trang lỗi 410: "Khóa mã hóa "Khóa tạm" đã bị thu hồi, tài liệu không thể giải mã." → **crypto-shredding** |
| MH-12 | admin | **Xoay vòng khóa** → xác nhận | "Đã mã hóa lại N phiên bản tài liệu bằng khóa "Khóa KH Thăng Long"". Tài liệu cũ vẫn tải được bình thường; số phiên bản của khóa hệ thống về 0. Tài liệu khóa đã thu hồi **không** được mã hóa lại |
| MH-13 | – | *(Kiểm tra toàn vẹn tệp)* phpMyAdmin tìm `storage_path` của một tài liệu thử → mở tệp `.enc` bằng Notepad++, sửa vài ký tự, lưu → tải tài liệu | Lỗi 410 "Giải mã thất bại: dữ liệu đã bị thay đổi hoặc sai khóa." (AES-GCM phát hiện tệp bị sửa). *Chỉ làm với tài liệu thử* |
| MH-14 | – | *(Mã hóa khi truyền tải)* Bật SSL trong XAMPP, đặt `'force_https' => true`, mở `http://localhost/qldauthau/` | Tự chuyển sang `https://`; phản hồi có header `Strict-Transport-Security` (F12 → Network). Đặt lại `false` nếu chưa cấu hình SSL |

---

## 16. Cấu hình hệ thống (CH)

| Mã | Tài khoản | Thao tác | Kết quả mong đợi |
|---|---|---|---|
| CH-01 | dauthau | Mở `index.php?r=settings/index` | 403 |
| CH-02 | admin | Tắt "Cho phép nhà cung cấp tự đăng ký" → Lưu → đăng xuất | Trang đăng nhập **không** còn liên kết đăng ký; mở `index.php?r=auth/register` → 403 "Chức năng đăng ký nhà cung cấp đang tắt." → **bật lại** |
| CH-03 | admin | Đổi "Tên tổ chức" → Lưu | Tên mới xuất hiện ở "Bên A" trong trang hợp đồng. *(HSMT ưu tiên dùng "Chủ đầu tư" của dự án; tên tổ chức chỉ dùng khi dự án để trống trường này)* |
| CH-04 | admin | Đổi "Trọng số kỹ thuật mặc định" = 80 | Form Tạo HSMT (không từ mẫu) mặc định 80 |
| CH-05 | admin | Tắt "Bắt buộc hóa đơn hợp lệ khi đề nghị thanh toán" | Đề nghị thanh toán không kèm hóa đơn được chấp nhận → **bật lại** |
| CH-06 | admin | Tắt "Trích xuất nội dung tài liệu…" → tải tệp docx mới → tìm theo nội dung | Không tìm thấy theo nội dung (chỉ theo tên) → **bật lại** |
| CH-07 | admin | Quy trình "Thanh toán / giải ngân" → xóa hết tên các bước → Lưu quy trình | Đề nghị thanh toán mới được "duyệt tự động" ngay. **Khôi phục** 2 bước: "Kế toán trưởng kiểm tra hồ sơ thanh toán" (Kế toán), "Giám đốc phê duyệt chi" (Lãnh đạo) |
| CH-08 | admin | Tab **Hệ thống** | Phiên bản PHP, MySQL, OpenSSL, ZipArchive, HTTPS, thư mục ghi được, dung lượng kho, lần chạy nhắc việc gần nhất |
| CH-09 | kiemsoat | Nhật ký kiểm toán → lọc "Đổi cấu hình" / "Cấu hình quy trình" | Có bản ghi thay đổi dạng JSON giá trị cũ → mới |
| CH-10 | admin | "Số chuyên gia chấm điểm tối thiểu cho mỗi gói thầu" = `1` → Lưu; mở một gói đang đánh giá | Nhãn thành "Đã chấm đủ: x/1", chỉ cần 1 người chấm đủ là tính điểm được → **đặt lại `3`** |

---

## 17. Bảng điều khiển & báo cáo (BC)

| Mã | Tài khoản | Thao tác | Kết quả mong đợi |
|---|---|---|---|
| BC-01 | giamdoc | Bảng điều khiển | KPI (gói thầu đang mở, giá trị trúng thầu, tiết kiệm, tỷ lệ thành công, TMĐT/đã chi, số NCC); biểu đồ tròn trạng thái; cột TMĐT–cam kết–thực chi theo dự án; đường giải ngân theo tháng; danh sách gói sắp đóng, việc đến hạn, thanh toán sắp tới, top NCC, hoạt động gần đây |
| BC-02 | ncc1 | Mở `index.php?r=dashboard/index` | Bị đưa về cổng NCC |
| BC-03 | kiemsoat | Báo cáo & phân tích, kỳ 01/01–31/12 năm chứa ngày tạo các gói mẫu | KPI: số gói trong kỳ, tỷ lệ thành công, HSDT bình quân/gói. **Giá trị trúng thầu 4.750.000.000** trên giá gói 5.000.000.000 → **tiết kiệm 250.000.000 (5,00%)** (nếu cả GT-0001 và GT-0002 nằm trong kỳ) |
| BC-04 | kiemsoat | Khối "Thời gian xử lý bình quân" | Có số ngày cho các giai đoạn đã phát sinh (lập → trình duyệt, mở thầu → trao thầu…), tính từ nhật ký kiểm toán |
| BC-05 | kiemsoat | Bảng "Hiệu suất nhà cung cấp" | NCC-0001: 2 HSDT, 2 trúng, 100%; NCC-0002: 2 HSDT, 0 trúng; điểm hiệu suất theo các đánh giá đã nhập |
| BC-06 | kiemsoat | Bảng "Kiểm soát chi phí theo dự án" | Số liệu khớp trang TMĐT; cột "Việc trễ" khớp trang kế hoạch |
| BC-07 | kiemsoat | Đổi kỳ báo cáo sang năm trước → Xem | Các chỉ số về 0 / "—" |
| BC-08 | kiemsoat | Xuất Excel 3 loại (gói thầu, NCC, chi phí dự án); nút **In** | 3 tệp CSV hiển thị đúng tiếng Việt; bản in ẩn thanh lọc/nút |

---

## 18. Bảo mật chung (BM)

| Mã | Tài khoản | Thao tác | Kết quả mong đợi |
|---|---|---|---|
| BM-01 | – | Mở `http://localhost/qldauthau/config/config.php`, `/database/seed.sql`, `/storage/`, `/app/core/Crypto.php`, `/docs/BAO_CAO.md` | Tất cả **403 Forbidden** |
| BM-02 | dauthau | F12 → tab Elements → trong một form (vd Thêm hạng mục) sửa giá trị ô ẩn `_csrf` thành `x` → gửi | Lỗi 419 "…yêu cầu không hợp lệ (CSRF)…"; dữ liệu không đổi |
| BM-03 | dauthau | Thêm dự án tên `<script>alert('XSS')</script>` | Tên hiển thị **nguyên văn** dạng chữ, **không** bật hộp thoại |
| BM-04 | dauthau | Ô tìm kiếm HSMT/tài liệu nhập `' OR 1=1 --` | Không lỗi SQL, không lộ dữ liệu ngoài quyền (chỉ kết quả rỗng/hợp lệ) |
| BM-05 | – | phpMyAdmin → bảng `users` | Cột `password_hash` dạng `$2y$10$…` (bcrypt), không có mật khẩu rõ |
| BM-06 | – | Mở `index.php?r=abc/xyz`, `index.php?r=tenders/validate`, `index.php?r=suppliers/save` | 404 "Không tìm thấy chức năng" (không gọi được hàm nội bộ/tĩnh) |
| BM-07 | – | F12 → Application → Cookies | Cookie `EPROCSESSID` có cờ **HttpOnly**, **SameSite=Lax** |
| BM-08 | ncc1 | Trình duyệt B: sửa `order_id` trong form xác nhận đơn hàng thành id đơn của hợp đồng khác NCC (nếu có) | "Đơn hàng không hợp lệ hoặc đã được xử lý." |
| BM-09 | dauthau | Đăng nhập cùng lúc ở 2 trình duyệt rồi Đăng xuất ở 1 bên | Bên đã đăng xuất phải đăng nhập lại; mã phiên được tạo mới khi đăng nhập (chống cố định phiên) |

---

## 19. Nhật ký kiểm toán bất biến (NK)

> **NK-06, NK-07 làm cuối cùng**: sau khi cố tình phá chuỗi nhật ký, cần cài lại để có chuỗi sạch.

| Mã | Tài khoản | Thao tác | Kết quả mong đợi |
|---|---|---|---|
| NK-01 | kiemsoat | Nhật ký kiểm toán | KPI tổng bản ghi, hôm nay, đăng nhập thất bại (≥ 5 từ AUTH-04), truy cập bị từ chối; bảng có cột Hash; dòng đăng nhập thất bại/từ chối tô vàng |
| NK-02 | kiemsoat | Lọc: người dùng = Trần Thị Lan; hành động = "Công bố"; đối tượng = tender, ID = 3; khoảng ngày | Kết quả đúng bộ lọc; bấm "#3" mở GT-2026-0003 |
| NK-03 | kiemsoat | **Kiểm tra toàn vẹn** | Hộp xanh "**Nhật ký toàn vẹn.** Đã kiểm tra N bản ghi…" |
| NK-04 | kiemsoat | **Xuất** | CSV gồm thời gian, tài khoản, hành động, chi tiết, IP, mã băm |
| NK-05 | – | phpMyAdmin → SQL: `UPDATE audit_logs SET details = 'sua' WHERE id = 1;` rồi `DELETE FROM audit_logs WHERE id = 1;` | Cả hai báo lỗi **#1644 – audit_logs is immutable (UPDATE/DELETE not allowed)**: CSDL **chặn** sửa/xóa |
| NK-06 | – | *(Mô phỏng kẻ có quyền DBA)* chạy lần lượt: `DROP TRIGGER trg_audit_no_update;` rồi `UPDATE audit_logs SET details = 'sua trai phep' WHERE id = 5;` | Lệnh UPDATE thành công (vì trigger đã bị gỡ) |
| NK-07 | kiemsoat | **Kiểm tra toàn vẹn** | Hộp đỏ "**PHÁT HIỆN SAI LỆCH!** Nội dung bản ghi đã bị thay đổi tại bản ghi #5". Tài khoản có quyền cấu hình (admin) nhận thông báo "CẢNH BÁO: Nhật ký kiểm toán bị can thiệp". *Thử thêm:* `DROP TRIGGER trg_audit_no_delete;` → `DELETE FROM audit_logs WHERE id = 3;` → kiểm tra lại → "Chuỗi liên kết bị đứt (có bản ghi bị xóa/chèn)" tại bản ghi #4. Sau ca này hãy **cài lại** hệ thống |

---

## 20. Bảng tổng hợp kết quả

Đánh dấu ✅ (đạt) / ❌ (lỗi) và ghi chú thông báo lỗi nếu có.

| Nhóm | Số ca | Đạt | Lỗi | Ghi chú |
|---|---|---|---|---|
| 1. Cài đặt (INS) | 7 | | | |
| 2. Đăng nhập & tài khoản (AUTH) | 15 | | | |
| 3. Thông báo & nhắc việc (TB) | 12 | | | |
| 4. Phân quyền, tổ chức, dự án (PQ) | 17 | | | |
| 5. Nhà cung cấp (NCC) | 9 | | | |
| 6. Mẫu HSMT & HSMT (HSMT) | 20 | | | |
| 7. Phê duyệt (PD) | 10 | | | |
| 8. Cổng NCC – HSDT (CNCC) | 15 | | | |
| 9. Đánh giá & trao thầu (DG) | 18 | | | |
| 10. Hợp đồng → quyết toán (HD) | 35 | | | |
| 11. Kế hoạch & tiến độ (KH) | 14 | | | |
| 12. TMĐT & chi phí (TM) | 11 | | | |
| 13. Kho tài liệu (TL) | 22 | | | |
| 14. Chia sẻ an toàn (CS) | 10 | | | |
| 15. Mã hóa & BYOK (MH) | 14 | | | |
| 16. Cấu hình (CH) | 10 | | | |
| 17. Dashboard & báo cáo (BC) | 8 | | | |
| 18. Bảo mật chung (BM) | 9 | | | |
| 19. Nhật ký kiểm toán (NK) | 7 | | | |
| **Tổng** | **263** | | | |

### Khi gặp lỗi

- Trang lỗi 500 hiển thị thông báo kèm **tên tệp:số dòng** (vì `debug = true`). Hãy chép nguyên văn.
- Xem thêm log PHP của Apache: `C:\xampp\apache\logs\error.log`.
- Ghi lại mã ca kiểm thử, tài khoản, các bước đã làm, rồi gửi để sửa.
