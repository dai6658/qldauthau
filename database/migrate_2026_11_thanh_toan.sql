-- Nâng cấp CSDL cho bản vá hợp đồng & giải ngân (16/11/2026)
-- Chạy MỘT LẦN trong phpMyAdmin: chọn CSDL qldauthau -> tab SQL -> dán toàn bộ -> Thực hiện.
-- Cài mới bằng install.php thì KHÔNG cần chạy file này.

-- 1) Thêm trạng thái "Đã hủy" cho đợt thanh toán (tách khỏi "Bị từ chối")
ALTER TABLE payments
  MODIFY status ENUM('planned','pending_approval','approved','paid','rejected','cancelled') NOT NULL DEFAULT 'planned';

-- 2) Các đợt trước đây bị hủy khi quyết toán đang mang trạng thái 'rejected' -> chuyển sang 'cancelled'
UPDATE payments SET status = 'cancelled'
WHERE status = 'rejected' AND note LIKE '%Hủy do quyết toán%';

-- 3) Đợt chưa chi của hợp đồng đã quyết toán hoặc chấm dứt -> 'cancelled'
UPDATE payments p JOIN contracts c ON c.id = p.contract_id
SET p.status = 'cancelled', p.note = CONCAT(COALESCE(p.note, ''), ' [Hủy do hợp đồng đã kết thúc]')
WHERE c.status IN ('completed', 'terminated') AND p.status IN ('planned', 'rejected')
  AND NOT (c.status = 'completed' AND p.milestone = 'Thanh toán quyết toán');

-- 4) Dữ liệu mẫu GT-2026-0001: bổ sung chuyên gia thứ 3 (Đặng Thu Trang) để tổ chuyên gia đủ 3 người.
--    Điểm trung bình kỹ thuật không đổi (88,50 và 76,00). Chạy lại nhiều lần không bị trùng.
INSERT IGNORE INTO tender_members (tender_id, user_id, access) VALUES (1, 10, 'evaluate');
INSERT IGNORE INTO bid_scores (bid_id, criterion_id, evaluator_id, score) VALUES
(1,1,10,36.5),(1,2,10,18),(1,3,10,17),(1,4,10,17),
(2,1,10,30),(2,2,10,16),(2,3,10,15),(2,4,10,15);

-- 5) Người lập HSMT luôn là thành viên "Biên soạn" của hồ sơ mình lập
--    (dữ liệu mẫu cũ thiếu bản ghi này nên tab Phân quyền chỉ hiện 2 người). Không ghi đè quyền đã có.
INSERT IGNORE INTO tender_members (tender_id, user_id, access)
SELECT id, created_by, 'edit' FROM tenders WHERE created_by IS NOT NULL;
