-- =====================================================================
--  Dữ liệu mẫu (demo). Mật khẩu tất cả tài khoản: 123456
-- =====================================================================
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

INSERT INTO companies (id, code, name, tax_code, address) VALUES
(1, 'HTG', 'Tổng Công ty Hạ tầng Thăng Long', '0100100100', '25 Lý Thường Kiệt, Hoàn Kiếm, Hà Nội');

INSERT INTO departments (id, company_id, code, name) VALUES
(1, 1, 'BGD',  'Ban Giám đốc'),
(2, 1, 'KHDT', 'Phòng Kế hoạch - Đấu thầu'),
(3, 1, 'TCKT', 'Phòng Tài chính - Kế toán'),
(4, 1, 'KT',   'Phòng Kỹ thuật'),
(5, 1, 'KS',   'Ban Kiểm soát nội bộ');

INSERT INTO roles (id, code, name, description) VALUES
(1, 'admin',       'Quản trị hệ thống',      'Toàn quyền cấu hình, người dùng, khóa mã hóa'),
(2, 'manager',     'Ban Giám đốc',           'Phê duyệt HSMT, kết quả lựa chọn nhà thầu, giải ngân'),
(3, 'procurement', 'Chuyên viên đấu thầu',   'Lập kế hoạch, HSMT, đánh giá HSDT, quản lý nhà cung cấp'),
(4, 'finance',     'Tài chính - Kế toán',    'Quản lý TMĐT, chi phí, thanh toán, quyết toán'),
(5, 'auditor',     'Kiểm soát nội bộ',       'Xem toàn bộ dữ liệu và nhật ký kiểm toán'),
(6, 'supplier',    'Nhà cung cấp / Nhà thầu','Truy cập cổng thông tin nhà cung cấp');

INSERT INTO permissions (id, code, name, module) VALUES
(1,  'dashboard.view',     'Xem bảng điều khiển',              'Tổng quan'),
(2,  'projects.view',      'Xem dự án',                         'Dự án'),
(3,  'projects.manage',    'Quản lý dự án',                     'Dự án'),
(4,  'org.manage',         'Quản lý cơ cấu tổ chức',            'Hệ thống'),
(5,  'users.manage',       'Quản lý người dùng & vai trò',      'Hệ thống'),
(6,  'suppliers.view',     'Xem nhà cung cấp',                  'Nhà cung cấp'),
(7,  'suppliers.manage',   'Quản lý / duyệt nhà cung cấp',      'Nhà cung cấp'),
(8,  'suppliers.evaluate', 'Đánh giá hiệu suất nhà cung cấp',   'Nhà cung cấp'),
(9,  'tenders.view',       'Xem HSMT (theo phòng ban/được giao)','Đấu thầu'),
(10, 'tenders.view_all',   'Xem tất cả HSMT',                   'Đấu thầu'),
(11, 'tenders.manage',     'Tạo / sửa HSMT',                    'Đấu thầu'),
(12, 'tenders.publish',    'Công bố / đóng / hủy thầu',         'Đấu thầu'),
(13, 'bids.view',          'Xem HSDT (sau mở thầu)',            'Đấu thầu'),
(14, 'bids.evaluate',      'Chấm điểm, đánh giá HSDT',          'Đấu thầu'),
(15, 'bids.award',         'Phê duyệt kết quả trúng thầu',      'Đấu thầu'),
(16, 'documents.view',     'Xem tài liệu phòng ban',            'Tài liệu'),
(17, 'documents.view_all', 'Xem tất cả tài liệu (trừ mật)',     'Tài liệu'),
(18, 'documents.manage',   'Tải lên / sửa / chia sẻ tài liệu',  'Tài liệu'),
(19, 'plans.view',         'Xem kế hoạch đấu thầu',             'Kế hoạch'),
(20, 'plans.manage',       'Lập kế hoạch, giao việc',           'Kế hoạch'),
(21, 'budget.view',        'Xem TMĐT & chi phí',                'Tài chính'),
(22, 'budget.manage',      'Lập TMĐT, ghi nhận chi phí',        'Tài chính'),
(23, 'contracts.view',     'Xem hợp đồng',                      'Hợp đồng'),
(24, 'contracts.manage',   'Quản lý hợp đồng, đơn hàng, nghiệm thu','Hợp đồng'),
(25, 'payments.manage',    'Thanh toán, giải ngân, quyết toán', 'Hợp đồng'),
(26, 'approvals.act',      'Tham gia phê duyệt',                'Phê duyệt'),
(27, 'reports.view',       'Xem báo cáo & phân tích',           'Báo cáo'),
(28, 'audit.view',         'Xem nhật ký kiểm toán',             'Hệ thống'),
(29, 'settings.manage',    'Cấu hình hệ thống, quy trình, khóa','Hệ thống'),
(30, 'portal.access',      'Truy cập cổng nhà cung cấp',        'Cổng NCC');

INSERT INTO role_permissions (role_id, permission_id)
SELECT 1, id FROM permissions WHERE code <> 'portal.access';
INSERT INTO role_permissions (role_id, permission_id) VALUES
(2,1),(2,2),(2,6),(2,8),(2,9),(2,10),(2,13),(2,15),(2,16),(2,17),(2,19),(2,21),(2,23),(2,26),(2,27),
(3,1),(3,2),(3,3),(3,6),(3,7),(3,8),(3,9),(3,11),(3,12),(3,13),(3,14),(3,16),(3,18),(3,19),(3,20),(3,21),(3,23),(3,24),(3,27),
(4,1),(4,2),(4,6),(4,9),(4,10),(4,13),(4,16),(4,18),(4,19),(4,21),(4,22),(4,23),(4,24),(4,25),(4,26),(4,27),
(5,1),(5,2),(5,6),(5,9),(5,10),(5,13),(5,16),(5,17),(5,19),(5,21),(5,23),(5,27),(5,28),
(6,30);

-- Mật khẩu: 123456 (bcrypt)
INSERT INTO users (id, username, password_hash, full_name, email, phone, role_id, company_id, department_id, supplier_id) VALUES
(1, 'admin',    '$2y$10$rYceQxvuacJFfh0/8kLsluUE755xGbSgtBRwz2sWLP8/03WO5D8q2', 'Quản trị hệ thống', 'admin@htg.vn',    '0901000001', 1, 1, 1, NULL),
(2, 'giamdoc',  '$2y$10$rYceQxvuacJFfh0/8kLsluUE755xGbSgtBRwz2sWLP8/03WO5D8q2', 'Nguyễn Văn Minh',   'minh.nv@htg.vn',  '0901000002', 2, 1, 1, NULL),
(3, 'dauthau',  '$2y$10$rYceQxvuacJFfh0/8kLsluUE755xGbSgtBRwz2sWLP8/03WO5D8q2', 'Trần Thị Lan',      'lan.tt@htg.vn',   '0901000003', 3, 1, 2, NULL),
(4, 'ketoan',   '$2y$10$rYceQxvuacJFfh0/8kLsluUE755xGbSgtBRwz2sWLP8/03WO5D8q2', 'Lê Văn Hùng',       'hung.lv@htg.vn',  '0901000004', 4, 1, 3, NULL),
(5, 'kiemsoat', '$2y$10$rYceQxvuacJFfh0/8kLsluUE755xGbSgtBRwz2sWLP8/03WO5D8q2', 'Phạm Thu Hà',       'ha.pt@htg.vn',    '0901000005', 5, 1, 5, NULL),
(6, 'kythuat',  '$2y$10$rYceQxvuacJFfh0/8kLsluUE755xGbSgtBRwz2sWLP8/03WO5D8q2', 'Hoàng Minh Tuấn',   'tuan.hm@htg.vn',  '0901000006', 3, 1, 4, NULL),
(7, 'ncc1',     '$2y$10$rYceQxvuacJFfh0/8kLsluUE755xGbSgtBRwz2sWLP8/03WO5D8q2', 'Đỗ Quang Huy',      'sales@saoviet.vn','0912000001', 6, NULL, NULL, 1),
(8, 'ncc2',     '$2y$10$rYceQxvuacJFfh0/8kLsluUE755xGbSgtBRwz2sWLP8/03WO5D8q2', 'Vũ Thị Mai',        'kd@minhphat.vn',  '0912000002', 6, NULL, NULL, 2),
(9, 'ncc3',     '$2y$10$rYceQxvuacJFfh0/8kLsluUE755xGbSgtBRwz2sWLP8/03WO5D8q2', 'Bùi Đức Long',      'duan@hbx.vn',     '0912000003', 6, NULL, NULL, 3),
(10, 'chuyengia', '$2y$10$rYceQxvuacJFfh0/8kLsluUE755xGbSgtBRwz2sWLP8/03WO5D8q2', 'Đặng Thu Trang',  'trang.dt@htg.vn', '0901000010', 3, 1, 3, NULL);

INSERT INTO suppliers (id, code, name, tax_code, address, phone, email, website, representative, business_field, established_year, charter_capital, annual_revenue, employees, bank_account, bank_name, status, rating) VALUES
(1, 'NCC-0001', 'Công ty CP Công nghệ Sao Việt', '0101234567', '12 Duy Tân, Cầu Giấy, Hà Nội', '02437001234', 'sales@saoviet.vn', 'saoviet.vn', 'Đỗ Quang Huy', 'Thiết bị CNTT, máy chủ, mạng', 2008, 50000000000, 320000000000, 180, '0011000123456', 'Vietcombank - CN Hà Nội', 'approved', 8.25),
(2, 'NCC-0002', 'Công ty TNHH Thiết bị Minh Phát', '0107654321', '88 Láng Hạ, Đống Đa, Hà Nội', '02438005678', 'kd@minhphat.vn', 'minhphat.vn', 'Vũ Thị Mai', 'Thiết bị CNTT, thiết bị văn phòng', 2012, 20000000000, 150000000000, 75, '1200123456789', 'BIDV - CN Đống Đa', 'approved', 7.50),
(3, 'NCC-0003', 'Công ty CP Xây dựng Hòa Bình Xanh', '0109988776', '5 Phạm Hùng, Nam Từ Liêm, Hà Nội', '02439112233', 'duan@hbx.vn', 'hbx.vn', 'Bùi Đức Long', 'Xây lắp, cải tạo công trình dân dụng', 2005, 120000000000, 800000000000, 450, '1903456789012', 'Techcombank', 'approved', 7.75),
(4, 'NCC-0004', 'Công ty TNHH Giải pháp Số An Phát', '0102233445', '20 Trần Thái Tông, Cầu Giấy, Hà Nội', '0243555666', 'info@anphat.vn', NULL, 'Ngô Văn Bình', 'Phần mềm, tích hợp hệ thống', 2019, 5000000000, 30000000000, 25, NULL, NULL, 'pending', 0);

INSERT INTO supplier_capabilities (supplier_id, cap_type, title, description, value_amount, year) VALUES
(1, 'experience',  'Cung cấp hệ thống máy chủ cho Ngân hàng ABC', 'Hợp đồng tương tự, hoàn thành đúng tiến độ', 4200000000, 2024),
(1, 'experience',  'Triển khai trung tâm dữ liệu Sở TT&TT', 'Nhà thầu chính', 6800000000, 2023),
(1, 'finance',     'Doanh thu bình quân 3 năm', 'Theo BCTC đã kiểm toán', 320000000000, 2025),
(1, 'certificate', 'ISO 9001:2015, ISO 27001:2022', 'Còn hiệu lực đến 2027', NULL, 2024),
(1, 'personnel',   '35 kỹ sư có chứng chỉ hãng (Dell, Cisco, VMware)', NULL, NULL, 2025),
(2, 'experience',  'Cung cấp thiết bị văn phòng cho UBND quận', NULL, 1500000000, 2024),
(2, 'finance',     'Doanh thu bình quân 3 năm', NULL, 150000000000, 2025),
(3, 'experience',  'Cải tạo trụ sở Công ty Điện lực', 'Giá trị 12 tỷ', 12000000000, 2023),
(3, 'equipment',   'Máy móc thi công: 3 cẩu tháp, 10 máy trộn', NULL, NULL, 2025);

INSERT INTO supplier_evaluations (supplier_id, contract_id, quality_score, schedule_score, price_score, service_score, overall, comment, evaluated_by) VALUES
(1, NULL, 9, 8, 8, 8, 8.25, 'Đánh giá từ các hợp đồng cung cấp thiết bị năm 2025', 3),
(2, NULL, 7, 8, 7, 8, 7.50, 'Đánh giá từ các đơn hàng nhỏ năm 2025', 3),
(3, NULL, 8, 7, 8, 8, 7.75, 'Đánh giá sơ tuyển năng lực', 6);

INSERT INTO projects (id, code, name, company_id, department_id, investor, location, description, start_date, end_date, status, manager_id, created_by) VALUES
(1, 'DA-2026-01', 'Hiện đại hóa hạ tầng CNTT trụ sở chính', 1, 2, 'Tổng Công ty Hạ tầng Thăng Long', 'Hà Nội', 'Đầu tư máy chủ, lưu trữ, mạng và bảo mật cho trụ sở chính', '2026-01-15', '2026-12-31', 'active', 3, 1),
(2, 'DA-2026-02', 'Cải tạo, nâng cấp tòa nhà văn phòng B', 1, 4, 'Tổng Công ty Hạ tầng Thăng Long', 'Hà Nội', 'Cải tạo kết cấu, nội thất, hệ thống điện nước, PCCC', '2026-07-01', '2027-06-30', 'planning', 6, 1);

INSERT INTO investment_items (id, project_id, category, name, estimated_amount, approved_amount, note) VALUES
(1, 1, 'equipment',   'Mua sắm máy chủ, hệ thống lưu trữ', 3500000000, 3400000000, NULL),
(2, 1, 'equipment',   'Thiết bị mạng, bảo mật',             1800000000, 1750000000, NULL),
(3, 1, 'consulting',  'Tư vấn thiết kế, giám sát',          350000000,  320000000,  NULL),
(4, 1, 'management',  'Chi phí quản lý dự án',              150000000,  150000000,  NULL),
(5, 1, 'other',       'Chi phí khác (thẩm định, bảo hiểm)', 80000000,   80000000,   NULL),
(6, 1, 'contingency', 'Chi phí dự phòng (5%)',              294000000,  285000000,  NULL),
(7, 2, 'construction','Chi phí xây dựng, cải tạo',          6200000000, 6000000000, NULL),
(8, 2, 'equipment',   'Hệ thống điều hòa, PCCC',            1200000000, 1150000000, NULL),
(9, 2, 'consulting',  'Tư vấn thiết kế',                    400000000,  380000000,  NULL),
(10,2, 'contingency', 'Chi phí dự phòng (5%)',              390000000,  376000000,  NULL);

INSERT INTO tender_templates (id, name, category, method, evaluation_method, tech_weight, price_weight, pass_tech_score, content, criteria_json, created_by) VALUES
(1, 'Mẫu HSMT mua sắm hàng hóa (phương pháp kết hợp kỹ thuật - giá QCS)', 'goods', 'open', 'qcs', 70, 30, 70,
'CHƯƠNG I. THÔNG TIN CHUNG
1. Bên mời thầu: {{chu_dau_tu}}
2. Dự án: {{du_an}} (mã {{ma_du_an}})
3. Tên gói thầu: {{ten_goi_thau}} - Mã gói thầu: {{ma_goi_thau}}
4. Giá gói thầu: {{gia_goi_thau}}
5. Hình thức lựa chọn nhà thầu: {{hinh_thuc}}
6. Phương pháp đánh giá: {{phuong_phap}}
7. Thời điểm đóng thầu: {{han_nop}} - Thời điểm mở thầu: {{mo_thau}}
8. Bảo đảm dự thầu: {{bao_dam_du_thau}}

CHƯƠNG II. CHỈ DẪN NHÀ THẦU
- Nhà thầu nộp hồ sơ dự thầu (HSDT) trực tuyến qua Cổng thông tin nhà cung cấp trước thời điểm đóng thầu.
- HSDT gồm: đơn dự thầu, bảng giá chi tiết theo từng hạng mục, đề xuất kỹ thuật, hồ sơ năng lực, kinh nghiệm.
- Hiệu lực HSDT tối thiểu 90 ngày kể từ thời điểm đóng thầu.
- HSDT được niêm phong điện tử, chỉ được mở sau thời điểm đóng thầu.

CHƯƠNG III. PHẠM VI CUNG CẤP
{{danh_sach_hang_hoa}}

CHƯƠNG IV. TIÊU CHUẨN ĐÁNH GIÁ HSDT
{{tieu_chi_danh_gia}}

CHƯƠNG V. ĐIỀU KIỆN HỢP ĐỒNG
- Tạm ứng 30% giá trị hợp đồng sau khi ký hợp đồng và nộp bảo lãnh tạm ứng.
- Thanh toán 50% sau khi giao hàng, lắp đặt và nghiệm thu.
- Thanh toán 20% còn lại sau khi bàn giao đưa vào sử dụng và nộp bảo lãnh bảo hành.',
'[{"name":"Đáp ứng thông số kỹ thuật","max_score":40},{"name":"Kinh nghiệm & năng lực nhà thầu","max_score":20},{"name":"Giải pháp triển khai, tiến độ","max_score":20},{"name":"Bảo hành, hỗ trợ kỹ thuật","max_score":20}]', 1),
(2, 'Mẫu HSMT gói thầu xây lắp', 'construction', 'open', 'qcs', 60, 40, 70,
'CHƯƠNG I. THÔNG TIN GÓI THẦU
- Bên mời thầu: {{chu_dau_tu}}
- Dự án: {{du_an}} ({{ma_du_an}})
- Gói thầu: {{ten_goi_thau}} ({{ma_goi_thau}}), giá gói thầu {{gia_goi_thau}}
- Hình thức: {{hinh_thuc}} - Phương pháp đánh giá: {{phuong_phap}}
- Đóng thầu: {{han_nop}}; Mở thầu: {{mo_thau}}; Bảo đảm dự thầu: {{bao_dam_du_thau}}

CHƯƠNG II. KHỐI LƯỢNG MỜI THẦU
{{danh_sach_hang_hoa}}

CHƯƠNG III. TIÊU CHUẨN ĐÁNH GIÁ
{{tieu_chi_danh_gia}}

CHƯƠNG IV. YÊU CẦU KỸ THUẬT
- Tuân thủ quy chuẩn, tiêu chuẩn xây dựng hiện hành.
- Có biện pháp đảm bảo an toàn lao động, vệ sinh môi trường, PCCC trong quá trình thi công.',
'[{"name":"Giải pháp kỹ thuật, biện pháp thi công","max_score":35},{"name":"Năng lực, kinh nghiệm nhà thầu","max_score":25},{"name":"Tiến độ thi công","max_score":20},{"name":"An toàn lao động, môi trường","max_score":20}]', 1),
(3, 'Mẫu chào hàng cạnh tranh (giá thấp nhất)', 'goods', 'competitive', 'lowest_price', 0, 100, 60,
'YÊU CẦU BÁO GIÁ
- Bên mời chào hàng: {{chu_dau_tu}}
- Nội dung: {{ten_goi_thau}} ({{ma_goi_thau}}) thuộc dự án {{du_an}}
- Hạn gửi báo giá: {{han_nop}}

DANH MỤC HÀNG HÓA/DỊCH VỤ
{{danh_sach_hang_hoa}}

ĐIỀU KIỆN ĐẠT KỸ THUẬT (đạt/không đạt)
{{tieu_chi_danh_gia}}

Nhà cung cấp đáp ứng kỹ thuật và có giá chào thấp nhất sẽ được lựa chọn.',
'[{"name":"Đáp ứng yêu cầu kỹ thuật","max_score":60},{"name":"Năng lực cung cấp dịch vụ","max_score":40}]', 1);

INSERT INTO tenders (id, code, project_id, investment_item_id, template_id, title, category, method, evaluation_method, tech_weight, price_weight, pass_tech_score, budget, bid_security, description, status, publish_at, close_at, opening_at, awarded_bid_id, created_by, created_at) VALUES
(1, 'GT-2026-0001', 1, 1, 1, 'Mua sắm máy chủ và hệ thống lưu trữ', 'goods', 'open', 'qcs', 70, 30, 70, 3300000000, 33000000,
 'Cung cấp, lắp đặt 04 máy chủ, 01 hệ thống SAN và tủ rack cho trung tâm dữ liệu', 'awarded', '2026-02-10 08:00:00', '2026-03-05 09:00:00', '2026-03-05 09:30:00', 1, 3, '2026-02-01 08:00:00'),
(2, 'GT-2026-0002', 1, 2, 1, 'Cung cấp thiết bị mạng và tường lửa', 'goods', 'open', 'qcs', 60, 40, 70, 1700000000, 17000000,
 'Cung cấp NGFW, switch core, switch truy cập PoE', 'published', DATE_SUB(NOW(), INTERVAL 5 DAY), DATE_ADD(NOW(), INTERVAL 10 DAY), DATE_ADD(NOW(), INTERVAL 10 DAY), NULL, 3, DATE_SUB(NOW(), INTERVAL 20 DAY)),
(3, 'GT-2026-0003', 2, 7, 2, 'Thi công cải tạo tòa nhà văn phòng B', 'construction', 'limited', 'qcs', 60, 40, 70, 5900000000, 59000000,
 'Phá dỡ, cải tạo kết cấu, hoàn thiện nội thất, điện nước', 'draft', NULL, NULL, NULL, NULL, 6, NOW()),
(4, 'GT-2026-0004', 1, 3, 3, 'Tư vấn giám sát lắp đặt hệ thống CNTT', 'consulting', 'competitive', 'lowest_price', 0, 100, 60, 320000000, NULL,
 'Tư vấn giám sát lắp đặt, vận hành thử', 'pending_approval', NULL, DATE_ADD(NOW(), INTERVAL 20 DAY), NULL, NULL, 3, DATE_SUB(NOW(), INTERVAL 3 DAY));

INSERT INTO tender_items (id, tender_id, name, specs, unit, quantity, estimated_price) VALUES
(1, 1, 'Máy chủ rack 2U', '2x CPU 32 core, RAM 512GB, 4x SSD 1.92TB, nguồn kép', 'Bộ', 4, 450000000),
(2, 1, 'Hệ thống lưu trữ SAN', 'Dung lượng khả dụng 100TB, FC 32Gb, dual controller', 'Hệ thống', 1, 1200000000),
(3, 1, 'Tủ rack 42U & phụ kiện', 'PDU thông minh, quạt, thanh quản lý cáp', 'Bộ', 2, 150000000),
(4, 2, 'Tường lửa thế hệ mới (NGFW)', 'Throughput >= 10Gbps, IPS, license 3 năm', 'Thiết bị', 2, 350000000),
(5, 2, 'Switch core 48 cổng 10G', 'Layer 3, stack, nguồn kép', 'Thiết bị', 2, 280000000),
(6, 2, 'Switch truy cập 48 cổng PoE+', 'Uplink 4x10G, PoE budget 740W', 'Thiết bị', 10, 44000000),
(7, 3, 'Phá dỡ, cải tạo kết cấu', 'Theo hồ sơ thiết kế', 'Gói', 1, 1800000000),
(8, 3, 'Hoàn thiện nội thất', 'Sàn, trần, vách ngăn, sơn bả', 'Gói', 1, 2600000000),
(9, 3, 'Hệ thống điện, nước', 'Tủ điện, chiếu sáng, cấp thoát nước', 'Gói', 1, 1500000000),
(10,4, 'Dịch vụ tư vấn giám sát', 'Giám sát lắp đặt, vận hành thử, nghiệm thu', 'Gói', 1, 320000000);

INSERT INTO tender_criteria (id, tender_id, name, max_score, sort_order) VALUES
(1, 1, 'Đáp ứng thông số kỹ thuật', 40, 1),
(2, 1, 'Kinh nghiệm & năng lực nhà thầu', 20, 2),
(3, 1, 'Giải pháp triển khai, tiến độ', 20, 3),
(4, 1, 'Bảo hành, hỗ trợ kỹ thuật', 20, 4),
(5, 2, 'Đáp ứng thông số kỹ thuật', 40, 1),
(6, 2, 'Kinh nghiệm & năng lực nhà thầu', 20, 2),
(7, 2, 'Giải pháp triển khai, tiến độ', 20, 3),
(8, 2, 'Bảo hành, hỗ trợ kỹ thuật', 20, 4),
(9, 3, 'Giải pháp kỹ thuật, biện pháp thi công', 35, 1),
(10,3, 'Năng lực, kinh nghiệm nhà thầu', 25, 2),
(11,3, 'Tiến độ thi công', 20, 3),
(12,3, 'An toàn lao động, môi trường', 20, 4),
(13,4, 'Đáp ứng yêu cầu kỹ thuật', 60, 1),
(14,4, 'Năng lực cung cấp dịch vụ', 40, 2);

INSERT INTO tender_members (tender_id, user_id, access) VALUES
(1, 6, 'evaluate'), (2, 6, 'evaluate'), (2, 10, 'evaluate'), (3, 3, 'edit'), (4, 6, 'view');

INSERT INTO tender_invitations (tender_id, supplier_id) VALUES (3, 3);

INSERT INTO bids (id, code, tender_id, supplier_id, total_price, validity_days, delivery_days, warranty_months, technical_proposal, status, submitted_at, opened_at, tech_score, price_score, final_score, passed, rank_no, created_at) VALUES
(1, 'HSDT-2026-0001', 1, 1, 3150000000, 90, 60, 36, 'Đề xuất thiết bị Dell PowerEdge R760 và Dell PowerStore; triển khai trong 60 ngày; bảo hành 36 tháng tại chỗ.', 'awarded', '2026-03-03 15:20:00', '2026-03-05 09:30:00', 88.50, 97.78, 91.28, 1, 1, '2026-03-01 10:00:00'),
(2, 'HSDT-2026-0002', 1, 2, 3080000000, 90, 75, 24, 'Đề xuất thiết bị HPE ProLiant DL380 và HPE MSA; bảo hành 24 tháng.', 'rejected', '2026-03-04 10:05:00', '2026-03-05 09:30:00', 76.00, 100.00, 83.20, 1, 2, '2026-03-02 09:00:00'),
(3, 'HSDT-2026-0003', 2, 2, 1640000000, 90, 45, 36, 'Đề xuất Fortinet FortiGate 600F, switch Aruba CX; bảo hành 36 tháng.', 'submitted', DATE_SUB(NOW(), INTERVAL 1 DAY), NULL, NULL, NULL, NULL, NULL, NULL, DATE_SUB(NOW(), INTERVAL 2 DAY));

INSERT INTO bid_items (bid_id, tender_item_id, unit_price, amount) VALUES
(1, 1, 440000000, 1760000000), (1, 2, 1120000000, 1120000000), (1, 3, 135000000, 270000000),
(2, 1, 425000000, 1700000000), (2, 2, 1150000000, 1150000000), (2, 3, 115000000, 230000000),
(3, 4, 340000000, 680000000),  (3, 5, 270000000, 540000000),   (3, 6, 42000000, 420000000);

INSERT INTO bid_scores (bid_id, criterion_id, evaluator_id, score) VALUES
(1,1,3,36),(1,2,3,18),(1,3,3,17),(1,4,3,18),
(1,1,6,36),(1,2,6,18),(1,3,6,17),(1,4,6,17),
(2,1,3,31),(2,2,3,15),(2,3,3,15),(2,4,3,15),
(2,1,6,31),(2,2,6,15),(2,3,6,15),(2,4,6,15);

INSERT INTO contracts (id, code, title, tender_id, bid_id, supplier_id, project_id, value, sign_date, start_date, end_date, payment_terms, status, created_by, created_at) VALUES
(1, 'HD-2026-0001', 'Hợp đồng cung cấp máy chủ và hệ thống lưu trữ', 1, 1, 1, 1, 3150000000, '2026-03-20', '2026-03-25', '2026-10-30',
 'Tạm ứng 30%; thanh toán 50% sau nghiệm thu lắp đặt; 20% sau bàn giao và bảo lãnh bảo hành', 'active', 3, '2026-03-18 10:00:00');

INSERT INTO acceptances (id, contract_id, title, accept_date, result, note, created_by) VALUES
(1, 1, 'Biên bản giao nhận, lắp đặt thiết bị đợt 1', '2026-06-15', 'passed', 'Thiết bị đúng chủng loại, đủ số lượng', 6);

INSERT INTO purchase_orders (id, code, contract_id, order_date, delivery_date, amount, description, status, confirmed_at, created_by) VALUES
(1, 'PO-2026-0001', 1, '2026-03-26', '2026-06-10', 3150000000, 'Giao toàn bộ thiết bị theo phụ lục hợp đồng', 'delivered', '2026-03-27 09:00:00', 3);

INSERT INTO invoices (id, contract_id, supplier_id, invoice_no, invoice_date, amount, vat_amount, status, note, submitted_by, submitted_at) VALUES
(1, 1, 1, '0002156', '2026-06-16', 1575000000, 157500000, 'submitted', 'Hóa đơn điện tử đợt 2 (50%)', 7, '2026-06-16 14:00:00');

INSERT INTO payments (id, contract_id, milestone, amount, due_date, status, invoice_id, acceptance_id, paid_amount, paid_at, note, created_by) VALUES
(1, 1, 'Tạm ứng 30% sau ký hợp đồng', 945000000, '2026-03-30', 'paid', NULL, NULL, 945000000, '2026-03-28', 'Đã có bảo lãnh tạm ứng', 4),
(2, 1, 'Thanh toán 50% sau giao hàng, nghiệm thu lắp đặt', 1575000000, DATE_ADD(CURDATE(), INTERVAL 2 DAY), 'planned', 1, 1, NULL, NULL, NULL, 4),
(3, 1, 'Thanh toán 20% sau bàn giao, bảo lãnh bảo hành', 630000000, '2026-11-15', 'planned', NULL, NULL, NULL, NULL, NULL, 4);

INSERT INTO expenses (project_id, investment_item_id, contract_id, payment_id, description, amount, expense_date, created_by) VALUES
(1, 1, 1, 1, 'Tạm ứng hợp đồng HD-2026-0001', 945000000, '2026-03-28', 4),
(1, 3, NULL, NULL, 'Tư vấn lập thiết kế cơ sở', 120000000, '2026-02-01', 4),
(1, 4, NULL, NULL, 'Chi phí quản lý dự án 6 tháng đầu năm', 60000000, '2026-06-30', 4),
(1, 5, NULL, NULL, 'Phí thẩm định HSMT, kết quả LCNT', 15000000, '2026-03-10', 4),
(2, 9, NULL, NULL, 'Tạm ứng tư vấn thiết kế cải tạo', 114000000, '2026-07-20', 4);

INSERT INTO tender_plans (id, code, project_id, name, description, start_date, end_date, status, created_by) VALUES
(1, 'KH-2026-0001', 1, 'Kế hoạch lựa chọn nhà thầu dự án CNTT 2026', 'Bao gồm 3 gói thầu: máy chủ, thiết bị mạng, tư vấn giám sát', '2026-02-01', '2026-12-31', 'in_progress', 3),
(2, 'KH-2026-0002', 2, 'Kế hoạch lựa chọn nhà thầu cải tạo tòa nhà B', NULL, '2026-08-01', '2027-03-31', 'draft', 6);

INSERT INTO plan_tasks (plan_id, tender_id, stage, name, assignee_id, department_id, start_date, due_date, progress, status, sort_order, completed_at) VALUES
(1, 1, 'Chuẩn bị',  'Lập HSMT gói máy chủ',                 3, 2, '2026-02-01', '2026-02-08', 100, 'done', 1, '2026-02-08 17:00:00'),
(1, 1, 'Đánh giá',  'Đánh giá HSDT gói máy chủ',            6, 4, '2026-03-06', '2026-03-15', 100, 'done', 2, '2026-03-14 17:00:00'),
(1, 2, 'Phát hành', 'Lập & phát hành HSMT thiết bị mạng',   3, 2, DATE_SUB(CURDATE(), INTERVAL 25 DAY), DATE_SUB(CURDATE(), INTERVAL 5 DAY), 100, 'done', 3, DATE_SUB(NOW(), INTERVAL 5 DAY)),
(1, 2, 'Mở thầu',   'Đóng thầu, mở thầu thiết bị mạng',     3, 2, DATE_ADD(CURDATE(), INTERVAL 10 DAY), DATE_ADD(CURDATE(), INTERVAL 10 DAY), 0, 'todo', 4, NULL),
(1, 2, 'Đánh giá',  'Đánh giá HSDT thiết bị mạng',          6, 4, DATE_ADD(CURDATE(), INTERVAL 11 DAY), DATE_ADD(CURDATE(), INTERVAL 20 DAY), 0, 'todo', 5, NULL),
(1, 4, 'Thẩm định', 'Thẩm định HSMT tư vấn giám sát',       2, 1, DATE_SUB(CURDATE(), INTERVAL 3 DAY), DATE_ADD(CURDATE(), INTERVAL 2 DAY), 40, 'in_progress', 6, NULL),
(2, 3, 'Chuẩn bị',  'Lập HSMT gói thi công cải tạo',        6, 4, DATE_SUB(CURDATE(), INTERVAL 10 DAY), DATE_SUB(CURDATE(), INTERVAL 1 DAY), 70, 'in_progress', 1, NULL);

INSERT INTO workflow_steps (entity_type, step_no, name, role_id) VALUES
('tender',   1, 'Giám đốc phê duyệt HSMT', 2),
('contract', 1, 'Kế toán kiểm tra điều khoản thanh toán', 4),
('contract', 2, 'Giám đốc ký duyệt hợp đồng', 2),
('payment',  1, 'Kế toán trưởng kiểm tra hồ sơ thanh toán', 4),
('payment',  2, 'Giám đốc phê duyệt chi', 2),
('plan',     1, 'Giám đốc phê duyệt kế hoạch', 2);

INSERT INTO approvals (entity_type, entity_id, title, round_no, step_no, step_name, role_id, status, requested_by, created_at) VALUES
('tender', 4, 'HSMT GT-2026-0004: Tư vấn giám sát lắp đặt hệ thống CNTT', 1, 1, 'Giám đốc phê duyệt HSMT', 2, 'pending', 3, DATE_SUB(NOW(), INTERVAL 3 DAY));

INSERT INTO notifications (user_id, title, message, link, type) VALUES
(2, 'Có hồ sơ cần phê duyệt', 'HSMT GT-2026-0004 đang chờ bạn phê duyệt', 'index.php?r=approvals/index', 'warning'),
(7, 'Gói thầu mới được công bố', 'GT-2026-0002: Cung cấp thiết bị mạng và tường lửa', 'index.php?r=portal/tender&id=2', 'info');

INSERT INTO settings (skey, svalue) VALUES
('org_display_name', 'Tổng Công ty Hạ tầng Thăng Long'),
('reminder_days', '3'),
('approval_reminder_days', '2'),
('default_tech_weight', '70'),
('default_pass_score', '70'),
('index_document_content', '1'),
('require_invoice_for_payment', '1'),
('allow_supplier_register', '1'),
('min_evaluators', '3'),
('last_cron_run', '');

SET FOREIGN_KEY_CHECKS = 1;
