<?php $v = fn($k) => e($s[$k] ?? ''); ?>
<div class="row g-3">
    <div class="col-md-8"><label class="form-label">Tên doanh nghiệp *</label><input class="form-control" name="name" value="<?= $v('name') ?>" required></div>
    <div class="col-md-4"><label class="form-label">Mã số thuế *</label><input class="form-control" name="tax_code" value="<?= $v('tax_code') ?>" required <?= !empty($lockTax) ? 'readonly' : '' ?>></div>
    <div class="col-md-8"><label class="form-label">Địa chỉ</label><input class="form-control" name="address" value="<?= $v('address') ?>"></div>
    <div class="col-md-4"><label class="form-label">Người đại diện</label><input class="form-control" name="representative" value="<?= $v('representative') ?>"></div>
    <div class="col-md-4"><label class="form-label">Điện thoại</label><input class="form-control" name="phone" value="<?= $v('phone') ?>"></div>
    <div class="col-md-4"><label class="form-label">Email</label><input class="form-control" type="email" name="email" value="<?= $v('email') ?>"></div>
    <div class="col-md-4"><label class="form-label">Website</label><input class="form-control" name="website" value="<?= $v('website') ?>"></div>
    <div class="col-md-8"><label class="form-label">Lĩnh vực kinh doanh</label><input class="form-control" name="business_field" value="<?= $v('business_field') ?>"></div>
    <div class="col-md-4"><label class="form-label">Năm thành lập</label><input class="form-control" type="number" name="established_year" value="<?= $v('established_year') ?>"></div>
    <div class="col-md-4"><label class="form-label">Vốn điều lệ (đ)</label><input class="form-control money" name="charter_capital" value="<?= money_input($s['charter_capital'] ?? null) ?>"></div>
    <div class="col-md-4"><label class="form-label">Doanh thu bình quân năm (đ)</label><input class="form-control money" name="annual_revenue" value="<?= money_input($s['annual_revenue'] ?? null) ?>"></div>
    <div class="col-md-4"><label class="form-label">Số lao động</label><input class="form-control" type="number" name="employees" value="<?= $v('employees') ?>"></div>
    <div class="col-md-6"><label class="form-label">Số tài khoản</label><input class="form-control" name="bank_account" value="<?= $v('bank_account') ?>"></div>
    <div class="col-md-6"><label class="form-label">Ngân hàng</label><input class="form-control" name="bank_name" value="<?= $v('bank_name') ?>"></div>
</div>
