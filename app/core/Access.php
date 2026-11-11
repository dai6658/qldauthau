<?php
/**
 * Kiểm soát truy cập theo vai trò (RBAC) + thuộc tính (ABAC):
 *  - HSMT gắn cơ cấu tổ chức (công ty / phòng ban / mã dự án) và danh sách thành viên riêng.
 *  - Tài liệu: mức mật, phòng ban, quyền cấp riêng cho người dùng / vai trò / phòng ban,
 *    kế thừa quyền từ HSMT mà tài liệu gắn vào.
 */
class Access
{
    // ---------------------------------------------------------------- HSMT
    public static function findTender(int $id): ?array
    {
        return row('SELECT t.*, p.code AS project_code, p.name AS project_name, p.company_id, p.department_id,
                           p.investor, c.name AS company_name, d.name AS department_name, u.full_name AS creator_name
                    FROM tenders t JOIN projects p ON p.id = t.project_id
                    LEFT JOIN companies c ON c.id = p.company_id
                    LEFT JOIN departments d ON d.id = p.department_id
                    LEFT JOIN users u ON u.id = t.created_by
                    WHERE t.id = ?', [$id]);
    }

    public static function memberAccess(int $tenderId): ?string
    {
        $uid = user_id();
        return $uid ? val('SELECT access FROM tender_members WHERE tender_id = ? AND user_id = ?', [$tenderId, $uid]) : null;
    }

    /** $need: view | edit | evaluate */
    public static function tender(array $t, string $need = 'view'): bool
    {
        $u = auth();
        if (!$u || is_supplier_user()) {
            return false;
        }
        if (is_admin()) {
            return true;
        }
        $isCreator = (int) $t['created_by'] === (int) $u['id'];
        $member = self::memberAccess((int) $t['id']);
        $sameDept = $u['department_id'] && (int) $u['department_id'] === (int) ($t['department_id'] ?? 0);

        switch ($need) {
            case 'view':
                return $isCreator || $member !== null || can('tenders.view_all') || (can('tenders.view') && $sameDept);
            case 'edit':
                return can('tenders.manage') && ($isCreator || $member === 'edit' || $sameDept);
            case 'evaluate':
                return can('bids.evaluate') && ($isCreator || in_array($member, ['edit', 'evaluate'], true));
        }
        return false;
    }

    public static function requireTender(?array $t, string $need = 'view'): array
    {
        if (!$t) {
            abort(404, 'Không tìm thấy hồ sơ mời thầu.');
        }
        if (!self::tender($t, $need)) {
            abort(403, 'Bạn không được phân quyền ' . ($need === 'view' ? 'xem' : ($need === 'edit' ? 'chỉnh sửa' : 'đánh giá')) . ' hồ sơ thầu này.');
        }
        return $t;
    }

    /** Điều kiện SQL lọc danh sách HSMT theo quyền (alias t = tenders, p = projects) */
    public static function tenderScope(): array
    {
        if (is_admin() || can('tenders.view_all')) {
            return ['1=1', []];
        }
        $u = auth();
        return ['(t.created_by = ? OR t.id IN (SELECT tender_id FROM tender_members WHERE user_id = ?) OR (p.department_id = ? AND ?))',
            [$u['id'], $u['id'], (int) $u['department_id'], can('tenders.view') ? 1 : 0]];
    }

    /** HSDT được niêm phong cho tới thời điểm đóng thầu */
    public static function bidsSealed(array $t): bool
    {
        return in_array($t['status'], ['draft', 'pending_approval', 'approved', 'published'], true);
    }

    // ---------------------------------------------------------------- Nhà cung cấp
    public static function supplierId(): ?int
    {
        $u = auth();
        return $u && $u['supplier_id'] ? (int) $u['supplier_id'] : null;
    }

    public static function supplierCanSeeTender(array $t, int $supplierId): bool
    {
        if (!in_array($t['status'], ['published', 'closed', 'evaluating', 'awarded'], true)) {
            return false;
        }
        if ($t['method'] === 'open') {
            return true;
        }
        return (bool) val('SELECT 1 FROM tender_invitations WHERE tender_id = ? AND supplier_id = ?', [$t['id'], $supplierId])
            || (bool) val('SELECT 1 FROM bids WHERE tender_id = ? AND supplier_id = ?', [$t['id'], $supplierId]);
    }

    // ---------------------------------------------------------------- Tài liệu
    /** $need: view | download | edit */
    public static function document(array $doc, string $need = 'view'): bool
    {
        $u = auth();
        if (!$u || $doc['is_deleted']) {
            return false;
        }
        if (is_admin()) {
            return true;
        }
        if (is_supplier_user()) {
            // Nhà cung cấp chỉ được xem/tải, không sửa/xóa qua kho tài liệu (kể cả tệp do mình nộp)
            if ($need === 'edit') {
                return false;
            }
            $sid = self::supplierId();
            switch ($doc['entity_type']) {
                case 'tender':
                    $t = row('SELECT * FROM tenders WHERE id = ?', [$doc['entity_id']]);
                    return $doc['confidentiality'] === 'public' && $t && self::supplierCanSeeTender($t, $sid);
                case 'supplier':
                    return (int) $doc['entity_id'] === $sid;
                case 'bid':
                    return (int) val('SELECT supplier_id FROM bids WHERE id = ?', [$doc['entity_id']]) === $sid;
                case 'contract':
                    return $doc['confidentiality'] !== 'confidential' && (int) val('SELECT supplier_id FROM contracts WHERE id = ?', [$doc['entity_id']]) === $sid;
                case 'invoice':
                    return (int) val('SELECT supplier_id FROM invoices WHERE id = ?', [$doc['entity_id']]) === $sid;
            }
            return false;
        }

        if ((int) $doc['created_by'] === (int) $u['id']) {
            return true;
        }

        // Quyền cấp riêng
        $col = $need === 'edit' ? 'can_edit' : ($need === 'download' ? 'can_download' : 'can_view');
        $explicit = val("SELECT 1 FROM document_permissions WHERE document_id = ? AND $col = 1
                         AND (user_id = ? OR role_id = ? OR department_id = ?) LIMIT 1",
            [$doc['id'], $u['id'], $u['role_id'], (int) $u['department_id']]);
        if ($explicit) {
            return true;
        }
        if ($doc['confidentiality'] === 'confidential') {
            return false;
        }

        // Kế thừa từ hồ sơ thầu
        if ($doc['entity_type'] === 'tender') {
            $t = self::findTender((int) $doc['entity_id']);
            if ($t && self::tender($t, $need === 'edit' ? 'edit' : 'view')) {
                return $need !== 'edit' || can('documents.manage');
            }
        }
        // Tài liệu do nhà cung cấp nộp: theo quyền nghiệp vụ tương ứng
        if ($doc['entity_type'] === 'bid') {
            if (self::bidDocumentLocked((int) $doc['entity_id'])) {
                return false; // niêm phong đến thời điểm đóng thầu
            }
            if ($need !== 'edit' && can('bids.view')) {
                return true;
            }
        }
        if ($need !== 'edit' && in_array($doc['entity_type'], ['contract', 'invoice'], true) && can('contracts.view')) {
            return true;
        }
        if ($need !== 'edit' && $doc['entity_type'] === 'supplier' && can('suppliers.view')) {
            return true;
        }

        $sameDept = $u['department_id'] && (int) $u['department_id'] === (int) $doc['department_id'];
        if ($need === 'edit') {
            return can('documents.manage') && ($sameDept || can('documents.view_all'));
        }
        return can('documents.view_all') || (can('documents.view') && $sameDept);
    }

    public static function bidDocumentLocked(int $bidId): bool
    {
        $t = row('SELECT t.* FROM bids b JOIN tenders t ON t.id = b.tender_id WHERE b.id = ?', [$bidId]);
        return !$t || self::bidsSealed($t);
    }

    public static function requireDocument(?array $doc, string $need = 'view'): array
    {
        if (!$doc || $doc['is_deleted']) {
            abort(404, 'Không tìm thấy tài liệu.');
        }
        if (!self::document($doc, $need)) {
            Audit::log('access_denied', 'document', $doc['id'], 'Từ chối quyền: ' . $need);
            abort(403, 'Bạn không có quyền ' . $need . ' tài liệu này.');
        }
        return $doc;
    }
}
