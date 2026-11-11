<?php
class PlansController extends Controller
{
    /** Các giai đoạn chuẩn của một gói thầu: [giai đoạn, công việc, ngày bắt đầu (offset), số ngày] */
    const STANDARD_STAGES = [
        ['Chuẩn bị',   'Lập hồ sơ mời thầu',                         0,  7],
        ['Thẩm định',  'Thẩm định & phê duyệt HSMT',                 7,  5],
        ['Phát hành',  'Công bố HSMT, phát hành tới nhà thầu',       12, 1],
        ['Mời thầu',   'Giải đáp làm rõ HSMT, nhận HSDT',            13, 20],
        ['Mở thầu',    'Đóng thầu & mở thầu',                        33, 1],
        ['Đánh giá',   'Đánh giá HSDT (tổ chuyên gia)',              34, 10],
        ['Thương thảo','Thương thảo hợp đồng',                        44, 5],
        ['Phê duyệt',  'Thẩm định, phê duyệt kết quả LCNT',           49, 5],
        ['Ký kết',     'Công khai kết quả, ký hợp đồng',              54, 5],
    ];

    public function index(): void
    {
        require_perm('plans.view');
        $where = ['1=1'];
        $params = [];
        if ($pid = input_int('project_id')) {
            $where[] = 'pl.project_id = ?';
            $params[] = $pid;
        }
        if ($st = input('status')) {
            $where[] = 'pl.status = ?';
            $params[] = $st;
        }
        $w = implode(' AND ', $where);
        $plans = rows("SELECT pl.*, p.code AS project_code, p.name AS project_name, u.full_name AS creator,
                (SELECT COUNT(*) FROM plan_tasks t WHERE t.plan_id = pl.id) AS task_count,
                (SELECT COUNT(*) FROM plan_tasks t WHERE t.plan_id = pl.id AND t.status = 'done') AS done_count,
                (SELECT COUNT(*) FROM plan_tasks t WHERE t.plan_id = pl.id AND t.status <> 'done' AND t.due_date < CURDATE()) AS late_count,
                (SELECT COALESCE(AVG(progress),0) FROM plan_tasks t WHERE t.plan_id = pl.id) AS progress
            FROM tender_plans pl JOIN projects p ON p.id = pl.project_id LEFT JOIN users u ON u.id = pl.created_by
            WHERE $w ORDER BY pl.id DESC", $params);
        view('plans/index', ['title' => 'Kế hoạch đấu thầu & tiến độ', 'plans' => $plans, 'projects' => pairs('SELECT id, code FROM projects ORDER BY id DESC')]);
    }

    public function form(): void
    {
        require_perm('plans.manage');
        $plan = input_int('id') ? $this->requireId('tender_plans', 'id', 'kế hoạch') : null;
        if (is_post()) {
            $data = ['project_id' => input_int('project_id'), 'name' => input('name'), 'description' => input('description'),
                     'start_date' => input_date('start_date'), 'end_date' => input_date('end_date')];
            $id = $this->attempt(function () use ($data, $plan) {
                if (!$data['project_id'] || !$data['name']) {
                    $this->fail('Chọn dự án và nhập tên kế hoạch.');
                }
                if ($plan) {
                    update_row('tender_plans', $data, 'id = ?', [$plan['id']]);
                    Audit::log('update', 'plan', $plan['id'], $data['name']);
                    return (int) $plan['id'];
                }
                $data += ['code' => gen_code('KH', 'tender_plans'), 'status' => 'draft', 'created_by' => user_id()];
                $id = insert('tender_plans', $data);
                Audit::log('create', 'plan', $id, $data['name']);
                return $id;
            }, 'Đã lưu kế hoạch.');
            if ($id) {
                redirect('plans/view', ['id' => $id]);
            }
            $plan = array_merge($plan ?? [], $data);
        }
        view('plans/form', ['title' => !empty($plan['id']) ? 'Sửa kế hoạch' : 'Lập kế hoạch đấu thầu', 'plan' => $plan ?? ['project_id' => input_int('project_id')],
            'projects' => pairs("SELECT id, CONCAT(code, ' - ', name) FROM projects ORDER BY id DESC")]);
    }

    public function view(): void
    {
        require_perm('plans.view');
        $plan = row('SELECT pl.*, p.code AS project_code, p.name AS project_name, u.full_name AS creator FROM tender_plans pl
            JOIN projects p ON p.id = pl.project_id LEFT JOIN users u ON u.id = pl.created_by WHERE pl.id = ?', [input_int('id')]);
        if (!$plan) {
            abort(404, 'Không tìm thấy kế hoạch.');
        }
        $tasks = rows('SELECT t.*, u.full_name AS assignee, d.name AS dept, td.code AS tender_code, td.status AS tender_status
            FROM plan_tasks t LEFT JOIN users u ON u.id = t.assignee_id LEFT JOIN departments d ON d.id = t.department_id
            LEFT JOIN tenders td ON td.id = t.tender_id WHERE t.plan_id = ? ORDER BY t.sort_order, t.start_date, t.id', [$plan['id']]);
        $updates = rows('SELECT tu.*, u.full_name, t.name AS task_name FROM task_updates tu JOIN plan_tasks t ON t.id = tu.task_id
            LEFT JOIN users u ON u.id = tu.user_id WHERE t.plan_id = ? ORDER BY tu.id DESC LIMIT 30', [$plan['id']]);
        $dates = array_filter(array_merge(array_column($tasks, 'start_date'), array_column($tasks, 'due_date'), [$plan['start_date'], $plan['end_date']]));
        $range = $dates ? [min($dates), max($dates)] : [date('Y-m-d'), date('Y-m-d', strtotime('+30 days'))];
        view('plans/view', [
            'title' => 'Kế hoạch ' . $plan['code'], 'plan' => $plan, 'tasks' => $tasks, 'updates' => $updates, 'range' => $range,
            'history' => Workflow::history('plan', (int) $plan['id']),
            'users' => pairs("SELECT u.id, u.full_name FROM users u JOIN roles r ON r.id = u.role_id WHERE r.code <> 'supplier' AND u.is_active = 1 ORDER BY u.full_name"),
            'departments' => pairs('SELECT id, name FROM departments ORDER BY name'),
            'tenders' => pairs("SELECT id, CONCAT(code, ' - ', title) FROM tenders WHERE project_id = ? ORDER BY id", [$plan['project_id']]),
        ]);
    }

    public function saveTask(): void
    {
        require_perm('plans.manage');
        $plan = $this->requireId('tender_plans', 'plan_id', 'kế hoạch');
        $data = [
            'plan_id' => $plan['id'], 'tender_id' => input_int('tender_id') ?: null, 'stage' => input('stage'), 'name' => input('name'),
            'assignee_id' => input_int('assignee_id') ?: null, 'department_id' => input_int('department_id') ?: null,
            'start_date' => input_date('start_date'), 'due_date' => input_date('due_date'), 'note' => input('note'),
        ];
        $this->attempt(function () use ($data, $plan) {
            if (!$data['name'] || !$data['due_date']) {
                $this->fail('Nhập tên công việc và hạn hoàn thành.');
            }
            if ($data['start_date'] && $data['start_date'] > $data['due_date']) {
                $this->fail('Ngày bắt đầu phải trước hạn hoàn thành.');
            }
            if ($tid = input_int('task_id')) {
                $data['updated_at'] = date('Y-m-d H:i:s');
                update_row('plan_tasks', $data, 'id = ? AND plan_id = ?', [$tid, $plan['id']]);
            } else {
                $data['sort_order'] = (int) val('SELECT COALESCE(MAX(sort_order),0)+1 FROM plan_tasks WHERE plan_id = ?', [$plan['id']]);
                $tid = insert('plan_tasks', $data);
            }
            if ($data['assignee_id'] && (int) $data['assignee_id'] !== user_id()) {
                Notifier::send([$data['assignee_id']], 'Bạn được giao việc: ' . $data['name'], 'Hạn: ' . fdate($data['due_date']) . ' — ' . $plan['name'], url('plans/view', ['id' => $plan['id']]));
            }
            Audit::log('update', 'plan', $plan['id'], 'Lưu công việc: ' . $data['name']);
        }, 'Đã lưu công việc.');
        redirect('plans/view', ['id' => $plan['id']]);
    }

    /** Cập nhật tiến độ (người thực hiện hoặc người quản lý kế hoạch) */
    public function updateTask(): void
    {
        $task = $this->requireId('plan_tasks', 'task_id', 'công việc');
        if (!can('plans.manage') && (int) $task['assignee_id'] !== user_id()) {
            abort(403);
        }
        $progress = max(0, min(100, input_int('progress')));
        $status = input('status');
        if (!isset(labels('task_status')[$status])) {
            $status = $progress >= 100 ? 'done' : ($progress > 0 ? 'in_progress' : 'todo');
        }
        if ($status === 'done') {
            $progress = 100;
        }
        update_row('plan_tasks', ['progress' => $progress, 'status' => $status, 'updated_at' => date('Y-m-d H:i:s'),
            'completed_at' => $status === 'done' ? date('Y-m-d H:i:s') : null], 'id = ?', [$task['id']]);
        insert('task_updates', ['task_id' => $task['id'], 'user_id' => user_id(), 'progress' => $progress, 'status' => $status, 'note' => input('note'), 'created_at' => date('Y-m-d H:i:s')]);
        $plan = row('SELECT * FROM tender_plans WHERE id = ?', [$task['plan_id']]);
        if ($plan['status'] === 'approved' && $progress > 0) {
            update_row('tender_plans', ['status' => 'in_progress'], 'id = ?', [$plan['id']]);
        }
        if ($status === 'blocked' || $status === 'done') {
            Notifier::send([$plan['created_by']], ($status === 'done' ? 'Hoàn thành: ' : 'Vướng mắc: ') . $task['name'], (string) input('note'), url('plans/view', ['id' => $plan['id']]), $status === 'done' ? 'success' : 'danger');
        }
        Audit::log('update', 'plan', $plan['id'], "Tiến độ '{$task['name']}': {$progress}% - " . label('task_status', $status));
        flash('success', 'Đã cập nhật tiến độ.');
        redirect_back('plans/view');
    }

    public function deleteTask(): void
    {
        require_perm('plans.manage');
        $task = $this->requireId('plan_tasks', 'task_id', 'công việc');
        q('DELETE FROM plan_tasks WHERE id = ?', [$task['id']]);
        Audit::log('delete', 'plan', $task['plan_id'], 'Xóa công việc: ' . $task['name']);
        redirect('plans/view', ['id' => $task['plan_id']]);
    }

    /** Tự động sinh các giai đoạn chuẩn cho một gói thầu */
    public function generate(): void
    {
        require_perm('plans.manage');
        $plan = $this->requireId('tender_plans', 'plan_id', 'kế hoạch');
        $start = input_date('start_date') ?: date('Y-m-d');
        $tenderId = input_int('tender_id') ?: null;
        $prefix = $tenderId ? (val('SELECT code FROM tenders WHERE id = ?', [$tenderId]) . ': ') : '';
        $order = (int) val('SELECT COALESCE(MAX(sort_order),0) FROM plan_tasks WHERE plan_id = ?', [$plan['id']]);
        foreach (self::STANDARD_STAGES as [$stage, $name, $offset, $days]) {
            insert('plan_tasks', [
                'plan_id' => $plan['id'], 'tender_id' => $tenderId, 'stage' => $stage, 'name' => $prefix . $name,
                'assignee_id' => input_int('assignee_id') ?: null, 'department_id' => auth()['department_id'],
                'start_date' => date('Y-m-d', strtotime("$start +$offset days")),
                'due_date' => date('Y-m-d', strtotime("$start +" . ($offset + $days - 1) . ' days')),
                'sort_order' => ++$order,
            ]);
        }
        Audit::log('update', 'plan', $plan['id'], 'Sinh tự động ' . count(self::STANDARD_STAGES) . ' giai đoạn chuẩn');
        flash('success', 'Đã sinh ' . count(self::STANDARD_STAGES) . ' công việc theo quy trình chuẩn (tổng ~2 tháng).');
        redirect('plans/view', ['id' => $plan['id']]);
    }

    public function submitApproval(): void
    {
        require_perm('plans.manage');
        $plan = $this->requireId('tender_plans', 'id', 'kế hoạch');
        if ($plan['status'] !== 'draft') {
            flash('warning', 'Kế hoạch không ở trạng thái nháp.');
        } elseif (!val('SELECT 1 FROM plan_tasks WHERE plan_id = ?', [$plan['id']])) {
            flash('danger', 'Kế hoạch chưa có công việc nào.');
        } else {
            update_row('tender_plans', ['status' => 'pending_approval'], 'id = ?', [$plan['id']]);
            Workflow::start('plan', (int) $plan['id'], 'Kế hoạch ' . $plan['code'] . ': ' . $plan['name']);
            flash('success', 'Đã trình phê duyệt kế hoạch.');
        }
        redirect('plans/view', ['id' => $plan['id']]);
    }

    public function complete(): void
    {
        require_perm('plans.manage');
        $plan = $this->requireId('tender_plans', 'id', 'kế hoạch');
        update_row('tender_plans', ['status' => 'completed'], 'id = ?', [$plan['id']]);
        Audit::log('status', 'plan', $plan['id'], 'Hoàn thành kế hoạch');
        redirect('plans/view', ['id' => $plan['id']]);
    }

    public function mytasks(): void
    {
        $tasks = rows("SELECT t.*, pl.name AS plan_name, pl.code AS plan_code, td.code AS tender_code FROM plan_tasks t
            JOIN tender_plans pl ON pl.id = t.plan_id LEFT JOIN tenders td ON td.id = t.tender_id
            WHERE t.assignee_id = ? ORDER BY t.status = 'done', t.due_date", [user_id()]);
        view('plans/mytasks', ['title' => 'Việc của tôi', 'tasks' => $tasks]);
    }
}
