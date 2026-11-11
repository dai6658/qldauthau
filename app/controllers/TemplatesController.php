<?php
class TemplatesController extends Controller
{
    const PLACEHOLDERS = [
        '{{ten_goi_thau}}' => 'Tên gói thầu', '{{ma_goi_thau}}' => 'Mã gói thầu', '{{du_an}}' => 'Tên dự án',
        '{{ma_du_an}}' => 'Mã dự án', '{{chu_dau_tu}}' => 'Chủ đầu tư / bên mời thầu', '{{gia_goi_thau}}' => 'Giá gói thầu',
        '{{hinh_thuc}}' => 'Hình thức lựa chọn', '{{phuong_phap}}' => 'Phương pháp đánh giá', '{{han_nop}}' => 'Thời điểm đóng thầu',
        '{{mo_thau}}' => 'Thời điểm mở thầu', '{{bao_dam_du_thau}}' => 'Bảo đảm dự thầu',
        '{{danh_sach_hang_hoa}}' => 'Bảng phạm vi cung cấp (tự sinh)', '{{tieu_chi_danh_gia}}' => 'Bảng tiêu chí đánh giá (tự sinh)',
        '{{ngay_lap}}' => 'Ngày lập',
    ];

    public function index(): void
    {
        require_perm('tenders.manage');
        $items = rows('SELECT t.*, u.full_name, (SELECT COUNT(*) FROM tenders x WHERE x.template_id = t.id) AS used FROM tender_templates t LEFT JOIN users u ON u.id = t.created_by ORDER BY t.id');
        view('templates/index', ['title' => 'Mẫu hồ sơ mời thầu', 'items' => $items]);
    }

    public function form(): void
    {
        require_perm('tenders.manage');
        $tpl = input_int('id') ? $this->requireId('tender_templates', 'id', 'mẫu') : null;
        if (is_post()) {
            $criteria = [];
            foreach ((array) ($_POST['crit_name'] ?? []) as $i => $name) {
                $name = trim((string) $name);
                if ($name !== '') {
                    $criteria[] = ['name' => $name, 'max_score' => max(1, (int) ($_POST['crit_score'][$i] ?? 10))];
                }
            }
            $data = [
                'name' => input('name'), 'category' => input('category'), 'method' => input('method'),
                'evaluation_method' => input('evaluation_method'), 'tech_weight' => input_int('tech_weight'),
                'price_weight' => 100 - input_int('tech_weight'), 'pass_tech_score' => input_int('pass_tech_score'),
                'content' => (string) ($_POST['content'] ?? ''), 'criteria_json' => json_encode($criteria, JSON_UNESCAPED_UNICODE),
            ];
            $ok = $this->attempt(function () use ($data, $tpl) {
                if (!$data['name']) {
                    $this->fail('Nhập tên mẫu.');
                }
                if ($tpl) {
                    update_row('tender_templates', $data, 'id = ?', [$tpl['id']]);
                    Audit::log('update', 'template', $tpl['id'], $data['name']);
                } else {
                    $data['created_by'] = user_id();
                    $id = insert('tender_templates', $data);
                    Audit::log('create', 'template', $id, $data['name']);
                }
            }, 'Đã lưu mẫu HSMT.');
            if ($ok) {
                redirect('templates/index');
            }
            $tpl = array_merge($tpl ?? [], $data);
        }
        view('templates/form', ['title' => $tpl ? 'Sửa mẫu HSMT' : 'Thêm mẫu HSMT', 'tpl' => $tpl,
            'criteria' => json_decode($tpl['criteria_json'] ?? '[]', true) ?: [], 'placeholders' => self::PLACEHOLDERS]);
    }

    public function delete(): void
    {
        require_perm('tenders.manage');
        $tpl = $this->requireId('tender_templates');
        q('DELETE FROM tender_templates WHERE id = ?', [$tpl['id']]);
        q('UPDATE tenders SET template_id = NULL WHERE template_id = ?', [$tpl['id']]);
        Audit::log('delete', 'template', $tpl['id'], $tpl['name']);
        flash('success', 'Đã xóa mẫu.');
        redirect('templates/index');
    }

    /** Sinh nội dung HSMT tự động từ mẫu + dữ liệu gói thầu */
    public static function render(string $content, array $tender): string
    {
        $items = rows('SELECT * FROM tender_items WHERE tender_id = ? ORDER BY id', [$tender['id']]);
        $criteria = rows('SELECT * FROM tender_criteria WHERE tender_id = ? ORDER BY sort_order, id', [$tender['id']]);
        $itemText = '';
        foreach ($items as $i => $it) {
            $itemText .= sprintf("%d. %s — SL: %s %s\n   Yêu cầu kỹ thuật: %s\n", $i + 1, $it['name'], num($it['quantity'], 0), $it['unit'], $it['specs'] ?: 'Theo hồ sơ thiết kế');
        }
        $critText = '';
        foreach ($criteria as $i => $c) {
            $critText .= sprintf("%d. %s: tối đa %d điểm%s\n", $i + 1, $c['name'], $c['max_score'], $c['description'] ? ' (' . $c['description'] . ')' : '');
        }
        if ($tender['evaluation_method'] === 'qcs') {
            $critText .= sprintf("\nĐiểm tổng hợp = %d%% × điểm kỹ thuật + %d%% × điểm giá. Điểm kỹ thuật tối thiểu để được xét: %d.\nĐiểm giá = (giá dự thầu thấp nhất / giá dự thầu của nhà thầu) × 100.", $tender['tech_weight'], $tender['price_weight'], $tender['pass_tech_score']);
        } elseif ($tender['evaluation_method'] === 'lowest_price') {
            $critText .= sprintf("\nHồ sơ đạt điểm kỹ thuật tối thiểu %d sẽ được xếp hạng theo giá dự thầu từ thấp đến cao.", $tender['pass_tech_score']);
        } else {
            $critText .= sprintf("\nXếp hạng theo điểm kỹ thuật (tối thiểu %d).", $tender['pass_tech_score']);
        }
        $map = [
            '{{ten_goi_thau}}' => $tender['title'], '{{ma_goi_thau}}' => $tender['code'],
            '{{du_an}}' => $tender['project_name'] ?? '', '{{ma_du_an}}' => $tender['project_code'] ?? '',
            '{{chu_dau_tu}}' => $tender['investor'] ?? setting('org_display_name', ''),
            '{{gia_goi_thau}}' => money($tender['budget']), '{{hinh_thuc}}' => label('method', $tender['method']),
            '{{phuong_phap}}' => label('eval_method', $tender['evaluation_method']),
            '{{han_nop}}' => $tender['close_at'] ? fdatetime($tender['close_at']) : '(chưa xác định)',
            '{{mo_thau}}' => $tender['opening_at'] ? fdatetime($tender['opening_at']) : '(ngay sau thời điểm đóng thầu)',
            '{{bao_dam_du_thau}}' => $tender['bid_security'] ? money($tender['bid_security']) : 'Không yêu cầu',
            '{{danh_sach_hang_hoa}}' => rtrim($itemText) ?: '(chưa có hạng mục)',
            '{{tieu_chi_danh_gia}}' => rtrim($critText), '{{ngay_lap}}' => date('d/m/Y'),
        ];
        return strtr($content, $map);
    }
}
