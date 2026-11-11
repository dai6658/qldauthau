<?php
$u = auth();
$current = $_GET['r'] ?? '';
$ctrl = explode('/', $current)[0] ?? '';
$pendingApprovals = ($u && can('approvals.act'))
    ? (int) val("SELECT COUNT(*) FROM approvals WHERE status = 'pending'" . (is_admin() ? '' : ' AND role_id = ' . (int) $u['role_id'])) : 0;
$myOpenTasks = $u ? (int) val("SELECT COUNT(*) FROM plan_tasks WHERE assignee_id = ? AND status <> 'done'", [$u['id']]) : 0;

if ($u && is_supplier_user()) {
    $menu = [
        'Cổng nhà cung cấp' => [
            ['portal/index', 'bi-speedometer2', 'Tổng quan', 'portal', null],
            ['portal/profile', 'bi-building', 'Hồ sơ doanh nghiệp', 'portal', null],
            ['portal/tenders', 'bi-megaphone', 'Gói thầu đang mời', 'portal', null],
            ['portal/bids', 'bi-file-earmark-text', 'Hồ sơ dự thầu / báo giá', 'portal', null],
            ['portal/contracts', 'bi-file-earmark-check', 'Hợp đồng & thanh toán', 'portal', null],
            ['portal/orders', 'bi-truck', 'Đơn đặt hàng', 'portal', null],
            ['portal/invoices', 'bi-receipt', 'Hóa đơn điện tử', 'portal', null],
        ],
    ];
} else {
    $menu = [
        'Tổng quan' => [
            ['dashboard/index', 'bi-speedometer2', 'Bảng điều khiển', 'dashboard', 'dashboard.view'],
            ['approvals/index', 'bi-check2-square', 'Phê duyệt' . ($pendingApprovals ? ' <span class="badge bg-danger ms-1">' . $pendingApprovals . '</span>' : ''), 'approvals', 'approvals.act'],
            ['plans/mytasks', 'bi-list-task', 'Việc của tôi' . ($myOpenTasks ? ' <span class="badge bg-secondary ms-1">' . $myOpenTasks . '</span>' : ''), '__mytasks', null],
        ],
        'Đấu thầu' => [
            ['projects/index', 'bi-kanban', 'Dự án', 'projects', 'projects.view'],
            ['plans/index', 'bi-calendar3', 'Kế hoạch & tiến độ', 'plans', 'plans.view'],
            ['tenders/index', 'bi-megaphone', 'Hồ sơ mời thầu', 'tenders', 'tenders.view'],
            ['evaluation/index', 'bi-clipboard-data', 'Đánh giá HSDT', 'evaluation', 'bids.view'],
            ['templates/index', 'bi-file-earmark-ruled', 'Mẫu HSMT', 'templates', 'tenders.manage'],
            ['suppliers/index', 'bi-people', 'Nhà cung cấp', 'suppliers', 'suppliers.view'],
        ],
        'Tài chính & hợp đồng' => [
            ['budget/index', 'bi-cash-stack', 'TMĐT & chi phí', 'budget', 'budget.view'],
            ['contracts/index', 'bi-file-earmark-check', 'Hợp đồng & giải ngân', 'contracts', 'contracts.view'],
        ],
        'Tài liệu & báo cáo' => [
            ['documents/index', 'bi-folder2-open', 'Kho tài liệu', 'documents', 'documents.view'],
            ['reports/index', 'bi-bar-chart-line', 'Báo cáo & phân tích', 'reports', 'reports.view'],
        ],
        'Hệ thống' => [
            ['users/index', 'bi-person-gear', 'Người dùng & phân quyền', 'users', 'users.manage'],
            ['org/index', 'bi-diagram-3', 'Cơ cấu tổ chức', 'org', 'org.manage'],
            ['audit/index', 'bi-shield-check', 'Nhật ký kiểm toán', 'audit', 'audit.view'],
            ['settings/index', 'bi-gear', 'Cấu hình', 'settings', 'settings.manage'],
        ],
    ];
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?= csrf_token() ?>">
    <title><?= e(($title ?? '') ? $title . ' | ' : '') ?><?= e(config('app_short')) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="<?= asset('css/app.css') ?>" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
</head>
<body>
<div class="app-wrapper">
    <aside class="sidebar" id="sidebar">
        <a class="brand" href="index.php">
            <i class="bi bi-briefcase-fill"></i>
            <span><?= e(config('app_short')) ?><small><?= e(setting('org_display_name', 'Quản lý đấu thầu')) ?></small></span>
        </a>
        <nav>
            <?php foreach ($menu as $section => $items):
                $visible = array_filter($items, fn($i) => $i[4] === null || can($i[4]));
                if (!$visible) continue; ?>
                <div class="nav-section"><?= e($section) ?></div>
                <?php foreach ($visible as [$r, $icon, $text, $c, $perm]):
                    $active = ($current === $r) || ($c === $ctrl && !in_array($ctrl, ['portal', 'plans'], true)) || ($ctrl === 'plans' && $c === 'plans' && $current !== 'plans/mytasks') || ($c === '__mytasks' && $current === 'plans/mytasks'); ?>
                    <a class="nav-link<?= $active ? ' active' : '' ?>" href="<?= url($r) ?>"><i class="bi <?= $icon ?>"></i><span><?= $text ?></span></a>
                <?php endforeach; ?>
            <?php endforeach; ?>
        </nav>
    </aside>

    <div class="main">
        <header class="topbar">
            <button class="btn btn-sm btn-light d-lg-none" onclick="document.getElementById('sidebar').classList.toggle('show')"><i class="bi bi-list"></i></button>
            <div class="topbar-title"><?= e($title ?? '') ?></div>
            <div class="ms-auto d-flex align-items-center gap-2">
                <div class="dropdown">
                    <button class="btn btn-light position-relative" data-bs-toggle="dropdown" id="notiBtn" title="Thông báo">
                        <i class="bi bi-bell"></i>
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger d-none" id="notiCount">0</span>
                    </button>
                    <div class="dropdown-menu dropdown-menu-end noti-menu p-0">
                        <div class="d-flex justify-content-between align-items-center px-3 py-2 border-bottom">
                            <strong>Thông báo</strong>
                            <form method="post" action="<?= url('notifications/readAll') ?>" class="m-0"><?= csrf_field() ?><button class="btn btn-link btn-sm p-0">Đánh dấu đã đọc</button></form>
                        </div>
                        <div id="notiList"><div class="p-3 text-muted small">Đang tải...</div></div>
                        <a class="d-block text-center small py-2 border-top" href="<?= url('notifications/index') ?>">Xem tất cả</a>
                    </div>
                </div>
                <div class="dropdown">
                    <button class="btn btn-light dropdown-toggle d-flex align-items-center gap-2" data-bs-toggle="dropdown">
                        <span class="avatar"><?= e(mb_strtoupper(mb_substr($u['full_name'] ?? '?', 0, 1))) ?></span>
                        <span class="d-none d-md-inline text-start lh-sm"><?= e($u['full_name'] ?? '') ?><br><small class="text-muted"><?= e($u['role_name'] ?? '') ?></small></span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="<?= url('auth/profile') ?>"><i class="bi bi-person me-2"></i>Tài khoản</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><form method="post" action="<?= url('auth/logout') ?>"><?= csrf_field() ?><button class="dropdown-item text-danger"><i class="bi bi-box-arrow-right me-2"></i>Đăng xuất</button></form></li>
                    </ul>
                </div>
            </div>
        </header>

        <main class="content">
            <?php foreach (get_flashes() as $f): ?>
                <div class="alert alert-<?= e($f['type']) ?> alert-dismissible fade show"><?= e($f['message']) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
            <?php endforeach; ?>
            <?= $content ?>
        </main>
        <footer class="footer">© <?= date('Y') ?> <?= e(config('app_name')) ?> · Phiên bản 1.0</footer>
    </div>
</div>
<div class="toast-container position-fixed bottom-0 end-0 p-3" id="toastBox"></div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>window.APP = { pollUrl: '<?= url('notifications/poll') ?>' };</script>
<script src="<?= asset('js/app.js') ?>"></script>
</body>
</html>
