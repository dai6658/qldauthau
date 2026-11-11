<div class="page-head"><div><h2>Cơ cấu tổ chức</h2><div class="sub">Công ty → Phòng ban → Dự án. Dùng để phân quyền truy cập hồ sơ thầu và tài liệu.</div></div></div>
<div class="row">
    <div class="col-lg-5">
        <div class="card"><div class="card-header">Công ty / đơn vị</div>
            <table class="table table-sm mb-0">
                <?php foreach ($companies as $c): ?>
                    <tr><td><strong><?= e($c['code']) ?></strong></td><td><?= e($c['name']) ?><div class="small text-muted">MST <?= e($c['tax_code']) ?> · <?= (int) $c['dept_count'] ?> phòng ban</div></td></tr>
                <?php endforeach; ?>
            </table>
            <form method="post" action="<?= url('org/saveCompany') ?>" class="card-body border-top">
                <?= csrf_field() ?>
                <div class="row g-2">
                    <div class="col-4"><input class="form-control form-control-sm" name="code" placeholder="Mã" required></div>
                    <div class="col-8"><input class="form-control form-control-sm" name="name" placeholder="Tên công ty" required></div>
                    <div class="col-4"><input class="form-control form-control-sm" name="tax_code" placeholder="MST"></div>
                    <div class="col-8"><input class="form-control form-control-sm" name="address" placeholder="Địa chỉ"></div>
                </div>
                <button class="btn btn-sm btn-primary mt-2"><i class="bi bi-plus"></i> Thêm công ty</button>
            </form>
        </div>
    </div>
    <div class="col-lg-7">
        <div class="card"><div class="card-header">Phòng ban</div>
            <table class="table table-sm align-middle mb-0">
                <thead><tr><th>Công ty</th><th>Mã</th><th>Tên phòng ban</th><th>Người dùng</th><th>Dự án</th><th></th></tr></thead>
                <?php foreach ($departments as $d): ?>
                    <tr>
                        <td><?= e($d['company_name']) ?></td><td><?= e($d['code']) ?></td><td><?= e($d['name']) ?></td>
                        <td><?= (int) $d['user_count'] ?></td><td><?= (int) $d['project_count'] ?></td>
                        <td><form method="post" action="<?= url('org/deleteDepartment', ['id' => $d['id']]) ?>" data-confirm="Xóa phòng ban này?"><?= csrf_field() ?><button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button></form></td>
                    </tr>
                <?php endforeach; ?>
            </table>
            <form method="post" action="<?= url('org/saveDepartment') ?>" class="card-body border-top">
                <?= csrf_field() ?>
                <div class="row g-2">
                    <div class="col-md-4"><select class="form-select form-select-sm" name="company_id"><?= select_options(array_column($companies, 'name', 'id')) ?></select></div>
                    <div class="col-md-2"><input class="form-control form-control-sm" name="code" placeholder="Mã" required></div>
                    <div class="col-md-4"><input class="form-control form-control-sm" name="name" placeholder="Tên phòng ban" required></div>
                    <div class="col-md-2"><button class="btn btn-sm btn-primary w-100">Thêm</button></div>
                </div>
            </form>
        </div>
    </div>
</div>
