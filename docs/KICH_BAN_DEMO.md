# Kịch bản demo chi tiết
## Phần mềm Quản lý đấu thầu, mua sắm

Kịch bản đi trọn một vòng đời: **lập HSMT → phê duyệt → công bố → nhà thầu nộp hồ sơ → mở thầu → chấm điểm → trao thầu → hợp đồng → đơn hàng → thanh toán, giải ngân → quyết toán**, rồi đến kho tài liệu, mã hóa, nhật ký kiểm toán và báo cáo.

- **Thời lượng**: khoảng 27 phút. Bản rút gọn 17 phút: bỏ các bước có ghi *(tùy chọn)*.
- **Mỗi cảnh gồm**:
  - Thời gian.
  - Cửa sổ trình duyệt và tài khoản.
  - Các bước bấm cụ thể.
  - **Người xem cần thấy**: điểm cần chỉ chuột vào.
  - *Lời dẫn*: câu gợi ý khi trình bày.

---

## A. Chuẩn bị (làm trước buổi demo)

### A1. Dữ liệu

1. **Diễn tập toàn bộ kịch bản ít nhất 1 lần** trước ngày demo.
2. Sau khi diễn tập, **cài lại từ đầu** để dữ liệu sạch:
   1. Xóa tệp `config/installed.lock`.
   2. Xóa các thư mục con trong `storage/documents/`.
   3. Mở `http://localhost/qldauthau/install.php` → tích "Tạo tài liệu mẫu" → **Cài đặt**.
3. Bật extension `zip` trong `php.ini` để tìm được theo nội dung tệp Word (xem `HUONG_DAN_CAI_DAT.md`).
4. Chuẩn bị 2 tệp trên Desktop:
   - `ban-ve.pdf`: một tệp PDF bất kỳ.
   - `yeu-cau-pccc.docx`: tệp Word có câu **"Hệ thống máy phát điện dự phòng công suất 500kVA"**.

### A2. Bố trí 3 cửa sổ trình duyệt

| Cửa sổ | Trình duyệt | Tài khoản | Vai trò trong demo |
|---|---|---|---|
| **A** | Chrome (thường) | `admin`, `dauthau`, `kythuat`, `chuyengia`, `ketoan` (đổi lần lượt) | Người dùng nội bộ |
| **B** | Chrome **ẩn danh** (Ctrl+Shift+N) | `ncc1` | Nhà cung cấp Sao Việt |
| **C** | Edge (hoặc Firefox) | `giamdoc` | Lãnh đạo phê duyệt |

- Mật khẩu mọi tài khoản: `123456`.
- **Đổi tài khoản ở cửa sổ A**: bấm tên người dùng (góc trên phải) → **Đăng xuất** → đăng nhập tài khoản mới.
- Đăng nhập sẵn trước khi bắt đầu: A = `admin`, B = `ncc1`, C = `giamdoc`.
- Mở sẵn: một tab **phpMyAdmin** (`http://localhost/phpmyadmin`) và cửa sổ thư mục `C:\xampp\htdocs\qldauthau\storage\documents`.

### A3. Dữ liệu mẫu có sẵn sau khi cài

| Mã | Nội dung | Trạng thái ban đầu |
|---|---|---|
| GT-2026-0001 | Máy chủ, giá gói 3,3 tỷ | Đã trao thầu cho Sao Việt → hợp đồng **HD-2026-0001** đang thực hiện |
| GT-2026-0002 | Thiết bị mạng, giá gói 1,7 tỷ, QCS 60/40 | Đang mời thầu, còn 10 ngày; Minh Phát đã nộp 1,64 tỷ |
| GT-2026-0003 | Cải tạo tòa nhà B, 5,9 tỷ, đấu thầu hạn chế | Nháp, đã mời NCC Hòa Bình Xanh |
| GT-2026-0004 | Tư vấn giám sát | Chờ giám đốc phê duyệt |
| HD-2026-0001 | 3,15 tỷ | Đã tạm ứng 945 triệu; hóa đơn 0002156 chờ kiểm tra; đợt 50% đến hạn sau 2 ngày |

---

## B. Kịch bản

### Cảnh 1. Mở đầu & phân quyền — 2 phút
**Cửa sổ A — `admin`**

1. Đăng xuất để hiện trang đăng nhập.
   - Chỉ bảng **tài khoản demo** và liên kết **Đăng ký tài khoản** dành cho nhà cung cấp.
   - Đăng nhập lại `admin`.
2. Menu **Bảng điều khiển**. Lướt từ trên xuống:
   - Hàng KPI: gói thầu đang mở, giá trị trúng thầu, tiết kiệm, tỷ lệ thành công, TMĐT / đã chi.
   - Biểu đồ tròn trạng thái gói thầu.
   - Biểu đồ cột TMĐT – cam kết – thực chi theo dự án.
   - Các khối "Gói thầu sắp đóng", "Thanh toán sắp tới", "Hoạt động gần đây".
3. Menu **Người dùng & phân quyền** → nút **Vai trò & quyền**.
   - **Người xem cần thấy:** ma trận 6 vai trò × 30 quyền, tick/bỏ tick được.
4. Menu **Cơ cấu tổ chức**.
   - **Người xem cần thấy:** công ty → 5 phòng ban.

*Lời dẫn: "Phân quyền có 2 lớp. Vai trò quyết định được làm gì; thuộc tính công ty – phòng ban – mã dự án quyết định được thấy hồ sơ nào. Tổ chức tự thêm vai trò, chỉnh quyền mà không cần sửa code."*

---

### Cảnh 2. Lập hồ sơ mời thầu từ mẫu — 3 phút
**Cửa sổ A — đăng xuất, đăng nhập `dauthau`**

1. Menu **Mẫu HSMT**: giới thiệu 3 mẫu chuẩn; bấm biểu tượng bút ở 1 mẫu để thấy bảng **biến thay thế** `{{ten_goi_thau}}`, `{{gia_goi_thau}}`… ở cột phải. Quay lại.
2. Menu **Hồ sơ mời thầu** → bấm mã **GT-2026-0003**.
3. Bấm **Trình duyệt** (nút vàng) → xác nhận.
   - **Người xem cần thấy:** hộp đỏ "Chưa thể trình duyệt: Thời điểm đóng thầu phải sau hiện tại ít nhất 1 giờ."
   - *Lời dẫn: "Hệ thống tự kiểm tra hồ sơ đủ điều kiện rồi mới cho trình."*
4. Bấm **Sửa**:
   - **Thời điểm đóng thầu**: hôm nay **+ 7 ngày**, 09:00.
   - **Thời điểm mở thầu**: cùng ngày, 09:30.
   - Bấm **Lưu thay đổi**.
5. Tab **Nội dung HSMT** → giữ nguyên mẫu đang chọn → bấm **Sinh tự động** → xác nhận.
   - **Người xem cần thấy:** nội dung được điền:
     - tên gói và giá "5.900.000.000 đ";
     - thời điểm đóng thầu vừa nhập;
     - bảng 3 hạng mục và 4 tiêu chí;
     - công thức "Điểm tổng hợp = 60% × điểm kỹ thuật + 40% × điểm giá".
6. Lướt tab **Hạng mục** (tổng dự toán 5,9 tỷ) và tab **Tiêu chí** (tổng 100 điểm).
7. Tab **Phân quyền & mời thầu**:
   - Khối trái: chọn **Hoàng Minh Tuấn (Chuyên viên đấu thầu)**, quyền **Tổ chuyên gia (chấm điểm)** → **Thêm**.
   - Khối phải: đã mời "Hòa Bình Xanh", vì đây là hình thức đấu thầu hạn chế.
8. Tab **Tài liệu**, dòng tải lên phía dưới:
   - Loại **Bản vẽ kỹ thuật**, mức mật **Công khai**.
   - Chọn tệp `ban-ve.pdf` → **Tải lên**.
9. Bấm **Trình duyệt** → xác nhận.

**Người xem cần thấy:**
- Thông báo "Đã trình phê duyệt HSMT"; trạng thái **Chờ duyệt**; thanh tiến trình sáng bước 2.
- Nút **Sửa** biến mất: hồ sơ bị khóa khi đang duyệt.

*Lời dẫn: "Mỗi hồ sơ thầu có danh sách phân quyền riêng — người biên soạn, tổ chuyên gia, người chỉ xem — bên cạnh quyền theo phòng ban."*

---

### Cảnh 3. Kế hoạch đấu thầu & giao việc — 2 phút
**Cửa sổ A — `dauthau`**

1. Menu **Kế hoạch & tiến độ** → bấm thẻ **Kế hoạch lựa chọn nhà thầu cải tạo tòa nhà B** (KH-2026-0002).
2. Kéo xuống khối **Tự động sinh các giai đoạn chuẩn** (cuối trang, bên phải):
   - Gói thầu **GT-2026-0003**.
   - Phụ trách **Hoàng Minh Tuấn**.
   - Ngày để mặc định là hôm nay.
   - Bấm **Sinh kế hoạch**.
3. Kéo lên **biểu đồ Gantt**.
   - **Người xem cần thấy:** 9 thanh nối tiếp từ "Lập hồ sơ mời thầu" đến "Công khai kết quả, ký hợp đồng" (khoảng 2 tháng); vạch đỏ dọc là **hôm nay**.
4. Bảng công việc, dòng đầu tiên:
   - Tiến độ `50`, trạng thái **Đang làm**, ghi chú "Đang hoàn thiện HSMT".
   - Bấm ✓.
   - **Người xem cần thấy:** thanh tiến độ và Gantt cập nhật; khối "Lịch sử cập nhật tiến độ" có dòng mới.

*Lời dẫn: "Việc trễ hạn tự tô đỏ; người phụ trách và người lập kế hoạch nhận nhắc việc tự động."*

---

### Cảnh 4. Phê duyệt — 1,5 phút
**Cửa sổ C — `giamdoc`** (bấm F5 để tải lại)

1. Bấm **chuông** góc trên: có thông báo "Có hồ sơ cần phê duyệt".
2. Menu **Phê duyệt** (có số đỏ).
   - **Người xem cần thấy:** GT-2026-0003 và GT-2026-0004, kèm tóm tắt giá gói, thời điểm đóng thầu.
3. *(Tùy chọn)* Dòng **GT-2026-0004**: để trống ô ý kiến, bấm nút đỏ ✗.
   - **Người xem cần thấy:** "Vui lòng nhập lý do từ chối."
4. Dòng **GT-2026-0003**: nhập ý kiến "Đồng ý" → bấm nút xanh ✓.
   - **Người xem cần thấy:** "Đã phê duyệt"; hồ sơ chuyển sang bảng **Lịch sử phê duyệt**.

*Lời dẫn: "Quy trình phê duyệt nhiều bước cấu hình được cho HSMT, hợp đồng, thanh toán và kế hoạch."*

---

### Cảnh 5. Công bố HSMT — 1 phút
**Cửa sổ A — `dauthau`**

1. Mở **GT-2026-0003** (trạng thái **Đã duyệt**) → bấm **Công bố** → xác nhận.
   - **Người xem cần thấy:** "Đã công bố HSMT và gửi thông báo tới 1 tài khoản nhà thầu". Tab **Tài liệu** có thêm **"HSMT GT-2026-0003 (bản công bố)"** mức *Công khai*, do hệ thống tự tạo.
2. Bấm **In / PDF** (mở tab mới): bản in theo thể thức văn bản hành chính, có quốc hiệu, phụ lục hạng mục và tiêu chí. Đóng tab.

*Lời dẫn: "Đây là đấu thầu hạn chế nên chỉ nhà thầu được mời nhận thông báo và nhìn thấy gói này."*

---

### Cảnh 6. Nhà cung cấp nộp hồ sơ dự thầu — 3 phút
**Cửa sổ B — `ncc1`** (F5)

1. Menu **Tổng quan**: các ô KPI, danh sách gói đang mời, hợp đồng và thông báo gần đây.
2. Menu **Gói thầu đang mời**.
   - **Người xem cần thấy:** có GT-2026-0002; **không** có GT-2026-0003, vì Sao Việt không được mời.
3. Dòng GT-2026-0002 → bấm **Tham dự**.
   - **Người xem cần thấy:** đồng hồ đếm ngược màu đỏ; khối **Tải hồ sơ mời thầu** (bấm thử 1 tệp để tải).
4. Bấm **Nộp HSDT** khi chưa nhập gì.
   - **Người xem cần thấy:** "Vui lòng chào giá cho tất cả hạng mục…".
5. Nhập bảng chào giá (chỉ gõ số, hệ thống tự thêm dấu chấm):

| Hạng mục | Đơn giá |
|---|---|
| Tường lửa thế hệ mới (NGFW) | 330000000 |
| Switch core 48 cổng 10G | 265000000 |
| Switch truy cập 48 cổng PoE+ | 41000000 |

   - **Người xem cần thấy:** cột thành tiền và **tổng 1.600.000.000 đ** tự tính.
6. Nhập tiếp:
   - **Hiệu lực HSDT** `90`, **Giao hàng** `45`, **Bảo hành** `36`.
   - **Đề xuất kỹ thuật**: "Fortinet FortiGate 600F, Cisco Catalyst 9500/9300, triển khai 45 ngày".
   - Phần **Tài liệu đính kèm**: chọn `ban-ve.pdf`.
7. Bấm **Nộp HSDT** → xác nhận.
   - **Người xem cần thấy:** hộp xanh "HSDT … Đã nộp · nộp lúc …"; chuông có "Xác nhận nộp HSDT".

**Chuyển sang cửa sổ A — `dauthau`**

8. Mở **GT-2026-0002** → tab **HSDT**.
   - **Người xem cần thấy:** biểu tượng khóa và dòng "HSDT đang được **niêm phong điện tử** đến thời điểm đóng thầu… Đã nhận **2** hồ sơ". Bảng **không** hiện giá.

*Lời dẫn: "Trước giờ đóng thầu, chính bên mời thầu cũng không xem được giá. Nhà thầu được sửa hoặc rút hồ sơ đến đúng thời điểm đóng thầu; tệp đính kèm được mã hóa AES-256."*

---

### Cảnh 7. Đóng thầu, mở thầu, tổ chuyên gia 3 người chấm điểm — 5 phút
**Cửa sổ A — đăng xuất, đăng nhập `admin`**

1. Mở **GT-2026-0002** → bấm **Đóng thầu** → xác nhận. Trạng thái chuyển **Đóng thầu**.

*Lời dẫn: "Thực tế hệ thống tự đóng thầu khi đến giờ; ở đây quản trị đóng sớm để demo."*

**Cửa sổ A — đăng xuất, đăng nhập `dauthau`** (chuyên gia 1 — Trần Thị Lan, Phòng Kế hoạch – Đấu thầu)

2. Mở **GT-2026-0002** → bấm **Mở thầu** (nút xanh lá) → xác nhận.
   - **Người xem cần thấy:** chuyển sang trang **Đánh giá HSDT – GT-2026-0002** với thông báo "Đã mở thầu 2 HSDT và lập biên bản mở thầu". *(Có thể mở nhanh tab Tài liệu của HSMT để thấy **Biên bản mở thầu** tự lập.)*
3. Tab **1. Tính hợp lệ**: bấm ✓ ở cả 2 dòng. Bấm vào tệp đính kèm của Sao Việt để chứng minh đã giải niêm phong.
4. Tab **2. Chấm điểm kỹ thuật**. Kéo xuống bảng **Tổ chuyên gia**.
   - **Người xem cần thấy:** 3 thành viên — Trần Thị Lan, Hoàng Minh Tuấn (Phòng Kỹ thuật), Đặng Thu Trang (Phòng Tài chính – Kế toán) — đều "Chưa chấm"; mỗi người cần chấm **8 điểm thành phần** (4 tiêu chí × 2 HSDT).
5. Nhập điểm theo bảng dưới → **Lưu phiếu chấm**. **Cả 3 chuyên gia nhập cùng bảng điểm này.**

| Tiêu chí (điểm tối đa) | Sao Việt | Minh Phát |
|---|---|---|
| Đáp ứng thông số kỹ thuật (40) | 35 | 32 |
| Kinh nghiệm & năng lực nhà thầu (20) | 18 | 16 |
| Giải pháp triển khai, tiến độ (20) | 17 | 15 |
| Bảo hành, hỗ trợ kỹ thuật (20) | 18 | 16 |

   - **Người xem cần thấy:** "Đã lưu 8 điểm chấm của bạn"; dưới mỗi ô "TB: … (1 người)"; nhãn góc trên **Đã chấm đủ: 1/3 chuyên gia** (màu vàng).
6. Bấm **Tính điểm & xếp hạng**.
   - **Người xem cần thấy:** bị chặn — "Chưa thể tính điểm: cần tối thiểu 3 chuyên gia chấm đủ… hiện mới có 1".

*Lời dẫn: "Luật Đấu thầu không ấn định số thành viên tổ chuyên gia mà để chủ đầu tư quyết định theo quy mô gói thầu. Phần mềm cho tổ chức tự cấu hình số người chấm tối thiểu — ở đây là 3 — và chặn tính kết quả khi chưa đủ người chấm. Mỗi chuyên gia chấm độc lập, không ai sửa được phiếu của người khác."*

**Cửa sổ A — đăng xuất, đăng nhập `kythuat`** (chuyên gia 2 — Hoàng Minh Tuấn, Phòng Kỹ thuật)

7. Menu **Đánh giá HSDT** → dòng GT-2026-0002 → **Đánh giá** → tab **2. Chấm điểm kỹ thuật**.
   - **Người xem cần thấy:** ô nhập của Tuấn **trống** (phiếu riêng); dưới mỗi ô vẫn hiện "TB: … (1 người)".
8. Nhập **đúng bảng điểm ở bước 5** → **Lưu phiếu chấm**.
   - **Người xem cần thấy:** mỗi ô "(2 người)"; nhãn **Đã chấm đủ: 2/3**.

**Cửa sổ A — đăng xuất, đăng nhập `chuyengia`** (chuyên gia 3 — Đặng Thu Trang, Phòng Tài chính – Kế toán)

9. Menu **Đánh giá HSDT** → GT-2026-0002 → **Đánh giá** → tab **2. Chấm điểm kỹ thuật** → nhập **đúng bảng điểm ở bước 5** → **Lưu phiếu chấm**.
   - **Người xem cần thấy:** mỗi ô "(3 người)"; bảng Tổ chuyên gia cả 3 người **Đã chấm đủ 8/8**; nhãn **Đã chấm đủ: 3/3** chuyển **màu xanh**.
10. Tab **3. So sánh báo giá**.
    - **Người xem cần thấy:** ô **xanh** là giá thấp nhất từng hạng mục (đều thuộc Sao Việt); % chênh so với dự toán; biểu đồ so sánh giá gói và 2 HSDT.
11. Bấm **Tính điểm & xếp hạng** → tab **4. Kết quả & xếp hạng**.
    - **Người xem cần thấy:**

| Hạng | Nhà thầu | Điểm KT (TB 3 chuyên gia) | Điểm giá | Điểm tổng hợp |
|---|---|---|---|---|
| 🥇 | Sao Việt | 88,00 | 100,00 | **92,80** |
| #2 | Minh Phát | 79,00 | 97,56 | **86,42** |

12. Chỉ dòng công thức ở cuối bảng.

*Lời dẫn: "Điểm kỹ thuật là trung bình của 3 chuyên gia, quy về thang 100. Điểm giá bằng giá thấp nhất chia giá của nhà thầu, nhân 100. Điểm tổng hợp = 60% kỹ thuật + 40% giá. Nhà thầu dưới 70 điểm kỹ thuật bị loại. Thành viên tổ chuyên gia không có nút trao thầu — quyền đó thuộc lãnh đạo."*

> **Biến thể (tùy chọn) — ý kiến khác biệt:** nếu ở bước 9 Đặng Thu Trang chấm Minh Phát 15/10/10/10 (= 45), điểm KT trung bình của Minh Phát = (79 + 79 + 45) / 3 = **67,67 < 70** → Minh Phát **Không đạt**, không được xếp hạng. Minh họa cách hệ thống tổng hợp khi một chuyên gia có ý kiến khác đa số. Muốn quay lại kết quả chuẩn: chấm lại 79 rồi bấm Tính điểm lần nữa.

---

### Cảnh 8. Trao thầu — 1,5 phút
**Cửa sổ C — `giamdoc`**

1. Menu **Đánh giá HSDT** → dòng GT-2026-0002 → nút **Đánh giá** → tab **4. Kết quả & xếp hạng**.
2. *(Tùy chọn)* Dòng **#2 Minh Phát**: bấm **Trao thầu** khi để trống ô giải trình.
   - **Người xem cần thấy:** trình duyệt bắt buộc nhập giải trình khi không chọn nhà thầu xếp hạng nhất.
3. Dòng **🥇 Sao Việt** → **Trao thầu** → xác nhận.
   - **Người xem cần thấy:**
     - Chuyển thẳng tới hợp đồng mới **HD-2026-0002**, giá trị 1.600.000.000 đ, trạng thái **Nháp**.
     - GT-2026-0002 chuyển **Đã trao thầu**.
     - Tab Tài liệu của HSMT có **"QĐ phê duyệt KQLCNT"** tự lập.

**Cửa sổ B — `ncc1`** (F5)

4. Chuông: "**Chúc mừng! Bạn trúng thầu GT-2026-0002**".
5. Menu **Hồ sơ dự thầu / báo giá**: trạng thái **Trúng thầu**, hạng #1 và điểm.

*Lời dẫn: "Nhà thầu không trúng cũng nhận thông báo kết quả tự động."*

---

### Cảnh 9. Hợp đồng mới & đơn đặt hàng — 3 phút
**Cửa sổ A — đăng xuất `chuyengia`, đăng nhập `dauthau`**

1. Menu **Hợp đồng & giải ngân** → **HD-2026-0002** → tab **Hợp đồng**.
2. Khối **Hoàn thiện dự thảo hợp đồng**:
   - **Ngày ký** = hôm nay.
   - **Ngày kết thúc** đã có sẵn.
   - Bấm **Lưu**.
3. Bấm **Trình duyệt** (góc trên) → xác nhận. Trạng thái **Chờ phê duyệt**.

**Cửa sổ A — đăng xuất, đăng nhập `ketoan`**

4. Menu **Phê duyệt** → dòng HD-2026-0002, bước 1 "Kế toán kiểm tra điều khoản thanh toán" → ✓.

**Cửa sổ C — `giamdoc`** (F5)

5. Menu **Phê duyệt** → dòng HD-2026-0002, bước 2 "Giám đốc ký duyệt hợp đồng" → ✓.

**Cửa sổ A — `ketoan`**

6. Mở lại **HD-2026-0002**: trạng thái **Đang thực hiện**; tab **Phê duyệt & lịch sử** có đủ 2 bước.
7. Tab **Đơn hàng**:
   - **Hạn giao** = hôm nay + 30 ngày.
   - **Nội dung** "Giao tường lửa và switch core".
   - **Giá trị** `800000000`.
   - Bấm **Phát hành đơn**.
   - *(Tùy chọn: phát hành thêm đơn 900000000 → bị chặn vì tổng vượt giá trị hợp đồng.)*

**Cửa sổ B — `ncc1`** (F5)

8. Menu **Đơn đặt hàng** → dòng đơn mới → ô ghi chú "Giao làm 2 đợt" → **Xác nhận**.

**Cửa sổ A — `ketoan`**

9. Tải lại tab **Đơn hàng**.
   - **Người xem cần thấy:** trạng thái **NCC đã xác nhận**, kèm ghi chú và thời điểm xác nhận.

---

### Cảnh 10. Hóa đơn, duyệt chi và giải ngân — 3 phút
**Cửa sổ A — `ketoan`**

1. Menu **Hợp đồng & giải ngân** → **HD-2026-0001** (có nhãn "1 HĐơn chờ kiểm tra"). Chỉ KPI: đã giải ngân 945.000.000 đ (30%).
2. Tab **Thanh toán & giải ngân** → dòng **"Thanh toán 50% sau giao hàng, nghiệm thu lắp đặt"** → bấm **Đề nghị** khi chưa chọn hóa đơn.
   - **Người xem cần thấy:** "Chưa thể trình duyệt chi: Cần gắn hóa đơn đã được kiểm tra hợp lệ."
3. Tab **Hóa đơn** → dòng **0002156** → bấm ✓ (Hợp lệ).
4. Tab **Thanh toán & giải ngân** → dòng 50%:
   - Ô hóa đơn chọn **0002156 (1.732.500.000)**.
   - Ô nghiệm thu chọn **Biên bản giao nhận, lắp đặt thiết bị đợt 1**.
   - Bấm **Đề nghị**.
   - Trạng thái chuyển **Chờ duyệt chi**.
5. Menu **Phê duyệt** → dòng "Thanh toán HD-2026-0001…", bước 1 "Kế toán trưởng kiểm tra hồ sơ thanh toán" → ✓.

*Lời dẫn: "Bước 1 do kế toán trưởng kiểm tra hồ sơ. Chỉ khi bước 1 xong, hồ sơ mới chuyển tới giám đốc."*

**Cửa sổ C — `giamdoc`** (F5)

6. Menu **Phê duyệt** → dòng thanh toán, bước 2 "Giám đốc phê duyệt chi".
   - **Người xem cần thấy:** tóm tắt "1.575.000.000 đ (đã chi 945.000.000 đ/3.150.000.000 đ)".
   - Bấm ✓.

**Cửa sổ A — `ketoan`**

7. Mở lại HD-2026-0001 → tab **Thanh toán & giải ngân** → dòng 50% (trạng thái **Đã duyệt chi**):
   - Ô số tiền hiện sẵn **1.575.000.000**, ngày là hôm nay.
   - Bấm **Giải ngân** → xác nhận.
   - **Người xem cần thấy:**
     - Dòng chuyển **Đã giải ngân**.
     - KPI đã giải ngân **2.520.000.000 đ (80%)**.
     - Tab **Hóa đơn**: 0002156 thành **Đã thanh toán**.
8. Menu **TMĐT & chi phí** → bấm **Chi tiết** ở dòng DA-2026-01 → tab **Chi phí thực tế**.
   - **Người xem cần thấy:** dòng "Giải ngân HĐ HD-2026-0001: Thanh toán 50%…" có nhãn **giải ngân HĐ**, tự hạch toán và không xóa tay được.

**Cửa sổ B — `ncc1`** *(tùy chọn)*

9. Chuông: "Đã thanh toán 1.575.000.000 đ". Menu **Hợp đồng & thanh toán**: HD-2026-0001 đã nhận 80%.

---

### Cảnh 11. Hóa đơn điện tử từ nhà cung cấp — 1 phút *(tùy chọn)*
**Cửa sổ B — `ncc1`**

1. Menu **Hóa đơn điện tử** → khối **Gửi hóa đơn mới**:
   - Hợp đồng **HD-2026-0001**.
   - Số hóa đơn `0003001`, ngày hôm nay.
   - Tiền hàng `500000000`, thuế GTGT `50000000`.
   - Tệp `ban-ve.pdf`.
   - Bấm **Gửi hóa đơn**.
   - **Người xem cần thấy:** hóa đơn ở trạng thái **Chờ kiểm tra**; kế toán nhận thông báo "Hóa đơn mới cần kiểm tra".

---

### Cảnh 12. Quyết toán & đánh giá nhà cung cấp — 3 phút

**Điều kiện trước:** đã xong Cảnh 10 (đợt 50% của HD-2026-0001 đã **Đã giải ngân**). Các con số dưới đây tính theo trạng thái đó.

**Cửa sổ A — `ketoan`**

1. Menu **Hợp đồng & giải ngân** → bấm **HD-2026-0001**.
   - **Người xem cần thấy** 4 ô KPI:
     - Giá trị hợp đồng **3.150.000.000**.
     - Đã đặt hàng / đã xuất hóa đơn **3.150.000.000 / 1.732.500.000** (cộng thêm hóa đơn gửi ở Cảnh 11 nếu gửi cho HD này).
     - Đã giải ngân **2.520.000.000 (80%)**.
     - Còn lại theo hợp đồng **630.000.000**.
2. Tab **Thanh toán & giải ngân**: kiểm tra trước khi quyết toán.
   - Đợt tạm ứng 30% và đợt 50% đều **Đã giải ngân**; đợt 20% còn lại ở **Kế hoạch**.
   - Không được có đợt nào **Chờ duyệt chi** hoặc **Đã duyệt chi** (nếu có, quyết toán bị chặn: "Còn x khoản thanh toán đang xử lý").
3. Tab **Nghiệm thu**: có "Biên bản giao nhận, lắp đặt thiết bị đợt 1" kết quả **Đạt**. Đây là điều kiện bắt buộc để quyết toán.
4. Tab **Quyết toán** → chỉ **Bảng đối chiếu quyết toán**:

| Dòng | Giá trị |
|---|---|
| Giá trị hợp đồng | 3.150.000.000 đ |
| Giá trị đã đặt hàng | 3.150.000.000 đ |
| Giá trị hóa đơn đã nhận (trừ bị từ chối) | 1.732.500.000 đ |
| Số lần nghiệm thu đạt | 1/1 |
| Đã giải ngân | 2.520.000.000 đ |

*Lời dẫn: "Trước khi quyết toán, hệ thống đối chiếu đủ 4 nguồn: hợp đồng, đơn hàng, hóa đơn và nghiệm thu với số tiền đã chi."*

5. *(Tùy chọn — chứng minh ràng buộc)*:
   1. Ô **Giá trị quyết toán** gõ `2000000000` → **Quyết toán hợp đồng** → OK. Kết quả: hộp đỏ "Giá trị quyết toán nhỏ hơn số đã thanh toán (2.520.000.000 đ) — cần thu hồi trước khi quyết toán."
   2. Gõ `3200000000` → **Quyết toán hợp đồng** → OK. Kết quả: hộp đỏ "Giá trị quyết toán phải > 0 và không vượt giá trị hợp đồng."
   3. Hợp đồng vẫn **Đang thực hiện** sau cả hai lần thử.
6. Ô **Giá trị quyết toán** nhập lại **3.150.000.000** (hoặc tải lại trang để lấy giá trị mặc định).
   - **Ghi chú**: "Quyết toán theo hợp đồng".
   - Bấm **Quyết toán hợp đồng** → hộp thoại "Quyết toán sẽ đóng hợp đồng. Tiếp tục?" → **OK**.
7. **Người xem cần thấy:**
   - Hộp xanh: "Đã quyết toán hợp đồng. Còn phải thanh toán 630.000.000 đ — hãy thêm đợt thanh toán quyết toán."
   - Nhãn trạng thái đầu trang: **Đã quyết toán**.
   - Ô KPI thứ 4 đổi thành **Còn phải trả theo quyết toán: 630.000.000**.
   - Bảng đối chiếu thêm dòng xanh **Giá trị quyết toán** 3.150.000.000 đ (kèm thời điểm), dòng **Còn phải thanh toán** 630.000.000 đ và dòng **Ghi chú**.
   - Form quyết toán biến mất, thay bằng nút **Tạo đợt thanh toán quyết toán (630.000.000 đ)**.
   - Khối "Đánh giá nhà cung cấp" bên phải ghi: lãnh đạo hoặc cán bộ đấu thầu sẽ đánh giá (Kế toán không có quyền này).
8. Tab **Thanh toán & giải ngân**: đợt 20% chuyển **Bị từ chối**, ghi chú có thêm "[Hủy do quyết toán]".

*Lời dẫn: "Đợt kế hoạch cũ tự hủy để tránh chi trùng; phần còn lại được thanh toán theo đúng giá trị quyết toán."*

9. *(Tùy chọn)* Tab Quyết toán → **Tạo đợt thanh toán quyết toán (630.000.000 đ)**.
   - Tab Thanh toán có đợt mới "Thanh toán quyết toán" 630.000.000 đ, hạn sau 15 ngày, trạng thái **Kế hoạch**.
   - Muốn chi tiếp thì làm như Cảnh 10, cần hóa đơn hợp lệ ≥ 630.000.000 đ (không cần trong demo).

**Cửa sổ B — `ncc1`** (F5) *(tùy chọn)*

10. Chuông: "**Hợp đồng HD-2026-0001 đã được quyết toán** — Giá trị quyết toán: 3.150.000.000 đ".
11. Menu **Hợp đồng & thanh toán**: HD-2026-0001 nhãn **Đã quyết toán**, có dòng Quyết toán 3.150.000.000 đ.

**Cửa sổ C — `giamdoc`** (F5)

12. Menu **Hợp đồng & giải ngân** → **HD-2026-0001** → tab **Quyết toán**.
    - **Người xem cần thấy:** khối **Đánh giá nhà cung cấp sau hợp đồng** có 4 thanh trượt, mặc định 8.
13. Kéo các thanh (số bên phải đổi theo):
    - **Chất lượng** 9, **Tiến độ** 8, **Giá cả** 8, **Dịch vụ** 9.
    - Ô **Nhận xét**: "Giao hàng đúng hạn, hỗ trợ kỹ thuật tốt".
    - Bấm **Lưu đánh giá**.
14. **Người xem cần thấy:**
    - Thông báo "Đã ghi nhận đánh giá."
    - Nếu trang quay về tab Hợp đồng thì bấm lại tab **Quyết toán**: hộp xanh "Đã đánh giá hiệu suất nhà cung cấp cho hợp đồng này". Mỗi hợp đồng chỉ đánh giá 1 lần.
15. Menu **Nhà cung cấp** → **Công ty CP Công nghệ Sao Việt**.
    - **Người xem cần thấy:** ô KPI **Điểm hiệu suất** từ 8,25 lên **8,38/10** (trung bình 2 lần đánh giá: 8,25 trước đây và 8,50 lần này).
16. Tab **Đánh giá hiệu suất (2)**.
    - **Người xem cần thấy:**
      - Biểu đồ **radar** 4 tiêu chí.
      - Dòng mới: ngày hôm nay, hợp đồng **HD-2026-0001**, điểm 9 / 8 / 8 / 9, trung bình **8,50**, nhận xét vừa nhập, người đánh giá **Nguyễn Văn Minh**.

*Lời dẫn: "Điểm hiệu suất tích lũy qua từng hợp đồng và hiển thị ngay khi chấm thầu các gói sau, giúp lựa chọn nhà thầu dựa trên dữ liệu thực tế."*

---

### Cảnh 13. Kho tài liệu tập trung — 3 phút
**Cửa sổ A — đăng xuất, đăng nhập `admin`**

1. Menu **Kho tài liệu** → nút **Tải lên**:
   - **Gắn với**: Hồ sơ mời thầu → **GT-2026-0003**.
   - **Loại**: Hồ sơ thiết kế. **Thẻ**: `PCCC, điện`.
   - Chọn tệp `yeu-cau-pccc.docx` → **Tải lên**.
2. Quay lại **Kho tài liệu**, tìm theo nội dung:
   - Gõ **máy phát điện** → **Tìm kiếm**. **Người xem cần thấy:** tìm ra tệp Word theo **nội dung bên trong**.
   - Gõ tiếp **Dell**: ra "Thuyết minh đề xuất kỹ thuật" của nhà thầu.
3. Mở tài liệu `yeu-cau-pccc` → tab **Phiên bản**:
   - Chọn lại tệp (hoặc tệp khác), ghi chú "Bổ sung công suất" → **Phiên bản mới**. Có **v2**.
   - Ở dòng v1 bấm biểu tượng ↺ (**Khôi phục**). Có **v3** "Khôi phục từ phiên bản 1".
4. Tab **Thông tin**.
   - **Người xem cần thấy:** dòng **SHA-256** và **Mã hóa: AES-256-GCM · khóa "Khóa hệ thống mặc định"**.
5. Tab **Phân quyền & chia sẻ** → khối **Liên kết chia sẻ an toàn**:
   - Thời hạn **24 giờ**, mật khẩu `demo123`, lượt tải tối đa `1`.
   - Bấm **Tạo liên kết** → bấm vào ô liên kết để chọn → Ctrl+C.
6. Dán liên kết vào **tab mới của cửa sổ B** (không cần đăng nhập):
   1. Nhập sai mật khẩu → "Mật khẩu không đúng".
   2. Nhập `demo123` → tải được tệp.
   3. Bấm F5 trang liên kết → "**Liên kết đã hết lượt tải**".
7. *(Tùy chọn)* Phân quyền tài liệu mật:
   - Đăng nhập `dauthau` ở A → **Kho tài liệu** → tìm "Quy chế": không thấy tài liệu **Mật**.
   - Admin cấp quyền cho Trần Thị Lan ở tab Phân quyền của tài liệu → `dauthau` mới thấy.

*Lời dẫn: "Kho chứa mọi định dạng — PDF, Office, bản vẽ DWG, email, ảnh — có phiên bản, phân quyền đến từng tài liệu, chia sẻ ra ngoài bằng liên kết có hạn, có mật khẩu, giới hạn lượt tải. Mọi lượt truy cập đều được ghi nhật ký."*

---

### Cảnh 14. Mã hóa & khóa do khách hàng quản lý (BYOK) — 2 phút
**Cửa sổ A — `admin`**

1. Mở thư mục `storage\documents\<năm>\<tháng>`, mở một tệp `.enc` bằng Notepad.
   - **Người xem cần thấy:** toàn ký tự vô nghĩa.
2. Menu **Cấu hình** → tab **Khóa mã hóa (BYOK)**. Chỉ hộp giải thích mô hình mã hóa phong bì.
3. Khối **Nhập khóa của khách hàng (BYOK)**:
   - Bấm **Sinh khóa mẫu**: ô khóa tự điền 44 ký tự.
   - Đặt tên "Khóa KMS của Thăng Long", tích **Kích hoạt ngay** → **Nhập khóa**.
   - **Người xem cần thấy:** khóa mới loại **Khách hàng quản lý**, **Đang hoạt động**; khóa cũ chuyển **Không hoạt động**.
4. Tải lên nhanh một tài liệu bất kỳ → tab **Thông tin**.
   - **Người xem cần thấy:** dòng Mã hóa ghi khóa "Khóa KMS của Thăng Long (khách hàng quản lý)".
5. Quay lại **Cấu hình** → tab Khóa mã hóa → **Xoay vòng khóa** → xác nhận.
   - **Người xem cần thấy:** "Đã mã hóa lại N phiên bản tài liệu…"; số phiên bản của khóa hệ thống về 0.

*Lời dẫn: "Khóa chủ nằm ngoài cơ sở dữ liệu. Khách hàng có thể dùng khóa riêng, xoay vòng định kỳ hoặc thu hồi khóa — khi đó tài liệu mã hóa bằng khóa ấy không ai giải mã được nữa. Đường truyền dùng HTTPS bắt buộc khi bật force_https."*

---

### Cảnh 15. Nhật ký kiểm toán bất biến — 2 phút
**Cửa sổ A — `admin`** (hoặc `kiemsoat`)

1. Menu **Nhật ký kiểm toán**.
   - Chỉ cột **Hash** và các dòng tô vàng (đăng nhập thất bại, từ chối truy cập).
   - Lọc **Hành động = Giải ngân** → thấy bản ghi `ketoan` vừa làm.
2. Bấm **Kiểm tra toàn vẹn**.
   - **Người xem cần thấy:** hộp xanh "**Nhật ký toàn vẹn.** Đã kiểm tra N bản ghi…".
3. Chuyển sang tab **phpMyAdmin** → CSDL `qldauthau` → tab **SQL** → chạy:

```sql
UPDATE audit_logs SET details = 'sua trai phep' WHERE id = 1;
```

   - **Người xem cần thấy:** MySQL báo lỗi **#1644 – audit_logs is immutable (UPDATE/DELETE not allowed)**.

*Lời dẫn: "Kể cả người vào thẳng cơ sở dữ liệu cũng không sửa hay xóa được nhật ký. Mỗi bản ghi còn nối chuỗi băm HMAC với bản trước, nên nếu ai gỡ trigger để sửa, nút Kiểm tra toàn vẹn sẽ chỉ ra đúng bản ghi bị can thiệp."*

---

### Cảnh 16. Báo cáo & phân tích — 1,5 phút
**Cửa sổ C — `giamdoc`**

1. Menu **Báo cáo & phân tích** (kỳ mặc định là năm hiện tại). Chỉ lần lượt:
   - **Tiết kiệm ngân sách 250.000.000 đ, tỷ lệ 5,00%**: GT-0001 từ 3,3 tỷ xuống 3,15 tỷ; GT-0002 từ 1,7 tỷ xuống 1,6 tỷ.
   - **Thời gian xử lý bình quân** các giai đoạn đã phát sinh, tính từ nhật ký.
   - Bảng **Hiệu suất nhà cung cấp** và **Kiểm soát chi phí theo dự án**.
2. Nút **Xuất Excel** → **Báo cáo gói thầu** → mở tệp CSV bằng Excel: tiếng Việt hiển thị đúng.
3. Menu **Bảng điều khiển**: KPI đã cập nhật theo các thao tác vừa demo.

*Lời dẫn: "Lãnh đạo thấy hiệu quả đấu thầu, điểm nghẽn quy trình và tình hình giải ngân theo thời gian thực."*

---

### Cảnh 17. Kết thúc — 30 giây

Tóm tắt 4 nhóm chức năng đề bài vừa trình diễn:

1. Quản lý hồ sơ đấu thầu, kho tài liệu bảo mật, nhà cung cấp, thông báo, phê duyệt.
2. Kế hoạch và theo dõi tiến độ.
3. TMĐT, chi phí, so sánh báo giá, báo cáo phân tích.
4. Lựa chọn nhà thầu (tổ chuyên gia 3 người chấm độc lập), hợp đồng, giải ngân, quyết toán, cổng nhà cung cấp.

---

## C. Xử lý sự cố khi đang demo

| Tình huống | Cách xử lý nhanh |
|---|---|
| Giám đốc vào **Phê duyệt** không thấy hồ sơ | Bước trước chưa xong: kiểm tra Kế toán đã bấm ✓ bước 1 chưa (quy trình chạy tuần tự), rồi F5 |
| Không thấy thông báo mới | Chuông tự cập nhật mỗi 30 giây; bấm F5 để tải ngay |
| Tìm "máy phát điện" không ra | Chưa bật extension `zip`; dùng từ khóa "Dell" thay thế |
| **Đóng thầu** báo "Chưa đến thời điểm" | Đang đăng nhập `dauthau`; phải dùng `admin` để đóng sớm |
| **Tính điểm** báo chưa đủ chuyên gia | Xem bảng **Tổ chuyên gia** ở tab 2: ai còn "Chấm dở/Chưa chấm" thì đăng nhập người đó nhập đủ 8 ô. Gấp thì tạm đặt **Cấu hình → Số chuyên gia chấm điểm tối thiểu** = 1 |
| Tab Quyết toán của giám đốc báo "Đã đánh giá…" mà chưa đánh giá | Dữ liệu cài từ bản cũ: chạy `UPDATE supplier_evaluations SET contract_id = NULL WHERE supplier_id = 1 AND contract_id = 1;` trong CSDL `qldauthau` |
| Không thấy nút **Trao thầu** | Chưa bấm **Tính điểm & xếp hạng**, hoặc không phải tài khoản `giamdoc` |
| Đề nghị thanh toán bị chặn | Chưa kiểm tra hóa đơn hợp lệ (tab Hóa đơn → ✓) |
| Lỡ thao tác sai làm hỏng dữ liệu | Cài lại theo mục A1 (khoảng 1 phút) |

## D. Bảng đổi tài khoản theo cảnh (in ra để cầm tay)

| Cảnh | Cửa sổ A | Cửa sổ B | Cửa sổ C |
|---|---|---|---|
| 1 | admin | – | – |
| 2, 3 | dauthau | – | – |
| 4 | – | – | giamdoc |
| 5 | dauthau | – | – |
| 6 | dauthau (bước 8) | ncc1 | – |
| 7 | admin → dauthau → kythuat → chuyengia | – | – |
| 8 | – | ncc1 | giamdoc |
| 9 | chuyengia → dauthau → ketoan | ncc1 | giamdoc |
| 10, 11, 12 | ketoan | ncc1 | giamdoc |
| 13, 14, 15 | admin | mở liên kết chia sẻ | – |
| 16 | – | – | giamdoc |
