<?php
/**
 * Đánh giá hồ sơ dự thầu:
 *  - Kiểm tra tính hợp lệ, chấm điểm kỹ thuật theo tổ chuyên gia (nhiều người chấm, lấy trung bình)
 *  - Tính điểm giá, điểm tổng hợp theo phương pháp QCS / giá thấp nhất / kỹ thuật
 *  - So sánh báo giá chi tiết theo hạng mục, xếp hạng, đề xuất & phê duyệt trúng thầu
 */
class EvaluationController extends Controller
{
    public function index(): void
    {
        require_perm('bids.view');
        [$scope, $params] = Access::tenderScope();
        $tenders = rows("SELECT t.*, p.code AS project_code,
                (SELECT COUNT(*) FROM bids b WHERE b.tender_id = t.id AND b.status NOT IN ('draft','withdrawn')) AS bid_count,
                (SELECT COUNT(DISTINCT evaluator_id) FROM bid_scores s JOIN bids b ON b.id = s.bid_id WHERE b.tender_id = t.id) AS evaluators
            FROM tenders t JOIN projects p ON p.id = t.project_id
            WHERE t.status IN ('published','closed','evaluating','awarded') AND $scope ORDER BY FIELD(t.status,'evaluating','closed','published','awarded'), t.close_at DESC", $params);
        view('evaluation/index', ['title' => 'Đánh giá hồ sơ dự thầu', 'tenders' => $tenders]);
    }

    public function tender(): void
    {
        require_perm('bids.view');
        $t = Access::requireTender(Access::findTender(input_int('id')), 'view');
        if (Access::bidsSealed($t)) {
            flash('warning', 'HSDT còn niêm phong đến thời điểm đóng thầu.');
            redirect('tenders/view', ['id' => $t['id'], 'tab' => 'bids']);
        }
        $bids = rows("SELECT b.*, s.name AS supplier_name, s.rating, s.code AS supplier_code FROM bids b JOIN suppliers s ON s.id = b.supplier_id
                      WHERE b.tender_id = ? AND b.status NOT IN ('draft','withdrawn') ORDER BY COALESCE(b.rank_no, 999), b.total_price", [$t['id']]);
        $criteria = rows('SELECT * FROM tender_criteria WHERE tender_id = ? ORDER BY sort_order, id', [$t['id']]);
        $items = rows('SELECT * FROM tender_items WHERE tender_id = ? ORDER BY id', [$t['id']]);
        $bidItems = [];
        foreach (rows('SELECT bi.* FROM bid_items bi JOIN bids b ON b.id = bi.bid_id WHERE b.tender_id = ?', [$t['id']]) as $bi) {
            $bidItems[$bi['bid_id']][$bi['tender_item_id']] = $bi;
        }
        $myScores = [];
        foreach (rows('SELECT s.* FROM bid_scores s JOIN bids b ON b.id = s.bid_id WHERE b.tender_id = ? AND s.evaluator_id = ?', [$t['id'], user_id()]) as $s) {
            $myScores[$s['bid_id']][$s['criterion_id']] = $s;
        }
        $avg = [];
        foreach (rows('SELECT s.bid_id, s.criterion_id, AVG(s.score) a, COUNT(*) n FROM bid_scores s JOIN bids b ON b.id = s.bid_id WHERE b.tender_id = ? GROUP BY s.bid_id, s.criterion_id', [$t['id']]) as $r) {
            $avg[$r['bid_id']][$r['criterion_id']] = $r;
        }
        $evaluators = rows('SELECT u.id, u.full_name, COUNT(*) n, MAX(s.created_at) last FROM bid_scores s JOIN bids b ON b.id = s.bid_id JOIN users u ON u.id = s.evaluator_id WHERE b.tender_id = ? GROUP BY u.id, u.full_name', [$t['id']]);
        $progress = self::scoringProgress($t);
        $panel = rows("SELECT u.id, u.full_name, d.name AS dept FROM tender_members m JOIN users u ON u.id = m.user_id
            LEFT JOIN departments d ON d.id = u.department_id WHERE m.tender_id = ? AND m.access IN ('evaluate','edit') ORDER BY u.full_name", [$t['id']]);
        $bidDocs = [];
        foreach ($bids as $b) {
            $bidDocs[$b['id']] = DocumentService::forEntity('bid', (int) $b['id']);
        }
        $canEvaluate = $t['status'] === 'evaluating' && Access::tender($t, 'evaluate');
        view('evaluation/tender', compact('t', 'bids', 'criteria', 'items', 'bidItems', 'myScores', 'avg', 'evaluators', 'bidDocs', 'canEvaluate', 'progress', 'panel') + ['title' => 'Đánh giá ' . $t['code']]);
    }

    private function evaluatingTender(): array
    {
        $t = Access::requireTender(Access::findTender(input_int('id')), 'evaluate');
        if ($t['status'] !== 'evaluating') {
            flash('danger', 'Gói thầu không ở giai đoạn đánh giá.');
            redirect('evaluation/tender', ['id' => $t['id']]);
        }
        return $t;
    }

    /** Kiểm tra tính hợp lệ HSDT (sơ bộ) */
    public function validity(): void
    {
        $t = $this->evaluatingTender();
        $bid = row('SELECT * FROM bids WHERE id = ? AND tender_id = ?', [input_int('bid_id'), $t['id']]);
        $status = input('valid') === '1' ? 'valid' : 'invalid';
        if ($bid && in_array($bid['status'], ['submitted', 'valid', 'invalid'], true)) {
            if ($status === 'invalid' && !input('note')) {
                flash('danger', 'Nhập lý do HSDT không hợp lệ.');
            } else {
                update_row('bids', ['status' => $status, 'review_note' => input('note'), 'updated_at' => date('Y-m-d H:i:s')], 'id = ?', [$bid['id']]);
                Audit::log('evaluate', 'bid', $bid['id'], label('bid_status', $status) . ': ' . input('note'));
                flash('success', 'Đã cập nhật kết quả kiểm tra tính hợp lệ.');
            }
        }
        redirect('evaluation/tender', ['id' => $t['id'], 'tab' => 'validity']);
    }

    /** Lưu điểm chấm của chuyên gia hiện tại */
    public function score(): void
    {
        $t = $this->evaluatingTender();
        $criteria = [];
        foreach (rows('SELECT id, max_score FROM tender_criteria WHERE tender_id = ?', [$t['id']]) as $c) {
            $criteria[$c['id']] = (float) $c['max_score'];
        }
        $bidIds = array_column(rows("SELECT id FROM bids WHERE tender_id = ? AND status IN ('submitted','valid')", [$t['id']]), 'id');
        $count = 0;
        transaction(function () use ($criteria, $bidIds, &$count) {
            foreach ((array) ($_POST['score'] ?? []) as $bidId => $row) {
                if (!in_array((int) $bidId, array_map('intval', $bidIds), true)) {
                    continue;
                }
                foreach ((array) $row as $cid => $value) {
                    if (!isset($criteria[(int) $cid]) || $value === '') {
                        continue;
                    }
                    $score = max(0, min($criteria[(int) $cid], (float) str_replace(',', '.', (string) $value)));
                    q('INSERT INTO bid_scores (bid_id, criterion_id, evaluator_id, score, comment, created_at) VALUES (?, ?, ?, ?, ?, NOW())
                       ON DUPLICATE KEY UPDATE score = VALUES(score), comment = VALUES(comment), created_at = NOW()',
                        [(int) $bidId, (int) $cid, user_id(), $score, $_POST['comment'][$bidId][$cid] ?? null]);
                    $count++;
                }
            }
        });
        Audit::log('score', 'tender', $t['id'], "Chấm $count điểm thành phần");
        flash('success', "Đã lưu $count điểm chấm của bạn.");
        redirect('evaluation/tender', ['id' => $t['id'], 'tab' => 'scoring']);
    }

    /** Tính điểm tổng hợp & xếp hạng */
    public function calculate(): void
    {
        $t = $this->evaluatingTender();
        $progress = self::scoringProgress($t);
        if ($progress['complete'] < $progress['min']) {
            flash('danger', 'Chưa thể tính điểm: cần tối thiểu ' . $progress['min'] . ' chuyên gia chấm đủ tất cả tiêu chí cho mọi HSDT hợp lệ, hiện mới có '
                . $progress['complete'] . '. (Số tối thiểu đặt tại Cấu hình → Cấu hình chung.)');
            redirect('evaluation/tender', ['id' => $t['id'], 'tab' => 'scoring']);
        }
        $result = self::compute($t);
        Audit::log('evaluate', 'tender', $t['id'], 'Tính kết quả (' . $progress['complete'] . ' chuyên gia): ' . $result['ranked'] . ' HSDT đạt, ' . $result['failed'] . ' không đạt');
        flash('success', 'Đã tính điểm và xếp hạng: ' . $result['ranked'] . ' hồ sơ đạt kỹ thuật, ' . $result['failed'] . ' không đạt.');
        redirect('evaluation/tender', ['id' => $t['id'], 'tab' => 'result']);
    }

    /**
     * Tiến độ chấm của tổ chuyên gia: số điểm thành phần cần cho 1 chuyên gia (tiêu chí × HSDT còn hiệu lực),
     * số chuyên gia đã chấm đủ và số tối thiểu theo cấu hình (min_evaluators).
     */
    public static function scoringProgress(array $t): array
    {
        $criteria = (int) val('SELECT COUNT(*) FROM tender_criteria WHERE tender_id = ?', [$t['id']]);
        $activeBids = (int) val("SELECT COUNT(*) FROM bids WHERE tender_id = ? AND status IN ('submitted','valid')", [$t['id']]);
        $need = $criteria * $activeBids;
        $perEvaluator = pairs("SELECT s.evaluator_id, COUNT(*) FROM bid_scores s JOIN bids b ON b.id = s.bid_id
            JOIN tender_criteria c ON c.id = s.criterion_id AND c.tender_id = b.tender_id
            WHERE b.tender_id = ? AND b.status IN ('submitted','valid') GROUP BY s.evaluator_id", [$t['id']]);
        $complete = $need > 0 ? count(array_filter($perEvaluator, fn($n) => (int) $n >= $need)) : 0;
        return [
            'need' => $need,
            'per' => array_map('intval', $perEvaluator),
            'complete' => $complete,
            'min' => max(1, (int) setting('min_evaluators', '1')),
        ];
    }

    public static function compute(array $t): array
    {
        $maxTotal = (float) val('SELECT COALESCE(SUM(max_score),0) FROM tender_criteria WHERE tender_id = ?', [$t['id']]);
        $bids = rows("SELECT * FROM bids WHERE tender_id = ? AND status IN ('submitted','valid')", [$t['id']]);
        q("UPDATE bids SET tech_score = NULL, price_score = NULL, final_score = NULL, passed = 0, rank_no = NULL WHERE tender_id = ? AND status = 'invalid'", [$t['id']]);
        $calc = [];
        foreach ($bids as $b) {
            $sum = (float) val('SELECT COALESCE(SUM(a),0) FROM (SELECT AVG(score) a FROM bid_scores WHERE bid_id = ? GROUP BY criterion_id) x', [$b['id']]);
            $tech = $maxTotal > 0 ? round($sum * 100 / $maxTotal, 2) : 0;
            $calc[$b['id']] = ['tech' => $tech, 'price' => (float) $b['total_price'], 'passed' => $tech >= (float) $t['pass_tech_score'] && $b['total_price'] > 0];
        }
        $passedPrices = array_column(array_filter($calc, fn($c) => $c['passed']), 'price');
        $lowest = $passedPrices ? min($passedPrices) : 0;
        foreach ($calc as $id => &$c) {
            $c['price_score'] = $c['passed'] && $lowest > 0 ? round($lowest * 100 / $c['price'], 2) : null;
            if (!$c['passed']) {
                $c['final'] = null;
            } elseif ($t['evaluation_method'] === 'qcs') {
                $c['final'] = round($c['tech'] * $t['tech_weight'] / 100 + $c['price_score'] * $t['price_weight'] / 100, 2);
            } elseif ($t['evaluation_method'] === 'lowest_price') {
                $c['final'] = $c['price_score'];
            } else {
                $c['final'] = $c['tech'];
            }
        }
        unset($c);
        $ranked = array_filter($calc, fn($c) => $c['passed']);
        uasort($ranked, function ($a, $b) {
            return [$b['final'], $a['price']] <=> [$a['final'], $b['price']];
        });
        $rank = 0;
        $ranks = [];
        foreach (array_keys($ranked) as $id) {
            $ranks[$id] = ++$rank;
        }
        foreach ($calc as $id => $c) {
            update_row('bids', [
                'tech_score' => $c['tech'], 'price_score' => $c['price_score'], 'final_score' => $c['final'],
                'passed' => $c['passed'] ? 1 : 0, 'rank_no' => $ranks[$id] ?? null, 'updated_at' => date('Y-m-d H:i:s'),
            ], 'id = ?', [$id]);
        }
        return ['ranked' => count($ranked), 'failed' => count($calc) - count($ranked)];
    }

    /** Phê duyệt kết quả lựa chọn nhà thầu & tạo hợp đồng */
    public function award(): void
    {
        require_perm('bids.award');
        $t = Access::requireTender(Access::findTender(input_int('id')), 'view');
        if ($t['status'] !== 'evaluating') {
            flash('danger', 'Gói thầu không ở giai đoạn đánh giá.');
            redirect('evaluation/tender', ['id' => $t['id']]);
        }
        $bid = row('SELECT b.*, s.name AS supplier_name FROM bids b JOIN suppliers s ON s.id = b.supplier_id WHERE b.id = ? AND b.tender_id = ?', [input_int('bid_id'), $t['id']]);
        $ok = $this->attempt(function () use ($t, $bid) {
            if (!$bid || !$bid['passed'] || !$bid['rank_no'] || !in_array($bid['status'], ['submitted', 'valid'], true)) {
                $this->fail('HSDT chưa được tính điểm hoặc không đạt yêu cầu.');
            }
            if ((int) $bid['rank_no'] !== 1 && !input('justification')) {
                $this->fail('Nhà thầu không xếp hạng nhất — cần nhập giải trình.');
            }
            if ((float) $bid['total_price'] > (float) $t['budget']) {
                $this->fail('Giá dự thầu (' . money($bid['total_price']) . ') vượt giá gói thầu được duyệt (' . money($t['budget']) . ').');
            }
            return transaction(function () use ($t, $bid) {
                q("UPDATE bids SET status = 'rejected', updated_at = NOW() WHERE tender_id = ? AND id <> ? AND status IN ('submitted','valid','invalid')", [$t['id'], $bid['id']]);
                update_row('bids', ['status' => 'awarded', 'updated_at' => date('Y-m-d H:i:s')], 'id = ?', [$bid['id']]);
                update_row('tenders', ['status' => 'awarded', 'awarded_bid_id' => $bid['id'], 'updated_at' => date('Y-m-d H:i:s')], 'id = ?', [$t['id']]);
                $cid = insert('contracts', [
                    'code' => gen_code('HD', 'contracts'), 'title' => 'Hợp đồng ' . mb_strtolower(mb_substr($t['title'], 0, 1)) . mb_substr($t['title'], 1),
                    'tender_id' => $t['id'], 'bid_id' => $bid['id'], 'supplier_id' => $bid['supplier_id'], 'project_id' => $t['project_id'],
                    'value' => $bid['total_price'], 'start_date' => date('Y-m-d'),
                    'end_date' => date('Y-m-d', strtotime('+' . max(30, (int) $bid['delivery_days'] + 30) . ' days')),
                    'payment_terms' => 'Theo điều kiện hợp đồng trong HSMT ' . $t['code'], 'status' => 'draft',
                    'created_by' => user_id(), 'created_at' => date('Y-m-d H:i:s'),
                ]);
                $text = "QUYẾT ĐỊNH PHÊ DUYỆT KẾT QUẢ LỰA CHỌN NHÀ THẦU\n\nGói thầu: {$t['code']} - {$t['title']}\nGiá gói thầu: " . money($t['budget'])
                    . "\n\nNhà thầu trúng thầu: {$bid['supplier_name']}\nGiá trúng thầu: " . money($bid['total_price'])
                    . "\nĐiểm kỹ thuật: {$bid['tech_score']} - Điểm tổng hợp: {$bid['final_score']} - Xếp hạng: {$bid['rank_no']}"
                    . (input('justification') ? "\nGiải trình: " . input('justification') : '')
                    . "\n\nNgười phê duyệt: " . auth()['full_name'] . "\nNgày: " . date('d/m/Y H:i');
                DocumentService::createFromContent([
                    'title' => 'QĐ phê duyệt KQLCNT ' . $t['code'], 'category' => 'decision', 'entity_type' => 'tender', 'entity_id' => $t['id'],
                    'project_id' => $t['project_id'], 'department_id' => $t['department_id'], 'confidentiality' => 'public', 'tags' => 'KQLCNT',
                ], 'QD_KQLCNT_' . $t['code'] . '.txt', $text, 'text/plain', 'Tự động lập khi trao thầu');
                Audit::log('award', 'tender', $t['id'], 'Trao thầu cho ' . $bid['supplier_name'] . ' - ' . money($bid['total_price']) . (input('justification') ? ' - GT: ' . input('justification') : ''));
                return $cid;
            });
        }, 'Đã phê duyệt kết quả trúng thầu và tạo dự thảo hợp đồng.');
        if ($ok) {
            $all = array_column(rows('SELECT supplier_id FROM bids WHERE tender_id = ? AND status IN (\'awarded\',\'rejected\')', [$t['id']]), 'supplier_id');
            Notifier::send(Notifier::supplierUsers([(int) $bid['supplier_id']]), 'Chúc mừng! Bạn trúng thầu ' . $t['code'], $t['title'] . ' — giá trúng thầu ' . money($bid['total_price']), url('portal/bids'), 'success');
            Notifier::send(Notifier::supplierUsers(array_diff($all, [$bid['supplier_id']])), 'Kết quả lựa chọn nhà thầu ' . $t['code'], 'Rất tiếc, hồ sơ của bạn không được lựa chọn. Nhà thầu trúng thầu: ' . $bid['supplier_name'], url('portal/bids'));
            Notifier::send(Notifier::tenderTeam($t), 'Đã trao thầu ' . $t['code'], 'Dự thảo hợp đồng đã được tạo, vui lòng hoàn thiện và trình duyệt.', url('contracts/view', ['id' => $ok]), 'success');
            redirect('contracts/view', ['id' => $ok]);
        }
        redirect('evaluation/tender', ['id' => $t['id'], 'tab' => 'result']);
    }

    /** Xuất bảng so sánh ra CSV */
    public function export(): void
    {
        require_perm('bids.view');
        $t = Access::requireTender(Access::findTender(input_int('id')), 'view');
        if (Access::bidsSealed($t)) {
            abort(403, 'HSDT còn niêm phong.');
        }
        $data = rows("SELECT b.rank_no, s.name, b.code, b.total_price, b.tech_score, b.price_score, b.final_score, b.status
            FROM bids b JOIN suppliers s ON s.id = b.supplier_id WHERE b.tender_id = ? AND b.status NOT IN ('draft','withdrawn') ORDER BY COALESCE(b.rank_no, 999)", [$t['id']]);
        foreach ($data as &$r) {
            $r['status'] = label('bid_status', $r['status']);
        }
        unset($r);
        Audit::log('export', 'tender', $t['id'], 'Xuất bảng đánh giá');
        csv_download('danh-gia-' . $t['code'] . '.csv', ['Hạng', 'Nhà thầu', 'Mã HSDT', 'Giá dự thầu', 'Điểm KT', 'Điểm giá', 'Điểm tổng', 'Trạng thái'], $data);
    }
}
