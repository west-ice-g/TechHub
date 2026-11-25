<?php
/**
 * TechHub - 高校技术团队展示与管理平台
 *
 * 文件：app/Views/admin/users/form.php
 * 模块：用户管理
 * 职责：新增或编辑后台账号表单。
 * 作者：待团队填写
 * 创建日期：2026-11-25
 * 最后修改：2026-11-25
 */

declare(strict_types=1);

$u = static function (string $key, mixed $default = '') use ($formOld, $user): mixed {
    return $formOld[$key] ?? $user[$key] ?? $default;
};
$currentRoleId = (int) $u('role_id', 3);
$currentStatus = (string) $u('status', 'active');
$formAction = $editing
    ? url('/admin/users/' . $user['id'] . '/edit')
    : url('/admin/users');
$heading = $editing ? '编辑账号' : '新增账号';
$description = $editing ? '用户名不可修改；密码留空表示保持原密码。' : '创建后可分配角色并由成员管理页面建立团队关系。';
$kicker = 'ACCOUNT';
$actions = '<a class="btn btn-sm btn-outline-secondary" href="' . e(url('/admin/users')) . '">返回列表</a>';
include BASE_PATH . '/app/Views/admin/partials/page-header.php';
?>

<div class="row justify-content-center">
    <div class="col-xl-9">
        <div class="admin-form-card">
            <?php include BASE_PATH . '/app/Views/admin/partials/form-errors.php'; ?>

            <form method="post" action="<?= e($formAction) ?>" autocomplete="off">
                <?= csrf_field() ?>

                <div class="admin-form-section">
                    <h2>账号信息</h2>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label" for="username">用户名 <span class="text-danger">*</span></label>
                            <input class="form-control" id="username" name="username" type="text" maxlength="50"
                                   value="<?= e($u('username')) ?>" <?= $editing ? 'readonly' : 'required' ?>
                                   pattern="[A-Za-z0-9_.-]{3,50}" autocomplete="off">
                            <div class="form-text">3–50 位字母、数字、下划线、点或连字符。</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="display_name">显示名称 <span class="text-danger">*</span></label>
                            <input class="form-control" id="display_name" name="display_name" type="text" maxlength="50"
                                   value="<?= e($u('display_name')) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="email">邮箱</label>
                            <input class="form-control" id="email" name="email" type="email" maxlength="120"
                                   value="<?= e($u('email')) ?>" autocomplete="off">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" for="role_id">角色 <span class="text-danger">*</span></label>
                            <select class="form-select" id="role_id" name="role_id" required>
                                <?php foreach ($roles as $role): ?>
                                    <option value="<?= e((string) $role['id']) ?>" <?= $currentRoleId === (int) $role['id'] ? 'selected' : '' ?>>
                                        <?= e($role['name']) ?>（<?= e($role['code']) ?>）
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" for="status">状态 <span class="text-danger">*</span></label>
                            <select class="form-select" id="status" name="status" required>
                                <option value="active" <?= $currentStatus === 'active' ? 'selected' : '' ?>>启用</option>
                                <option value="disabled" <?= $currentStatus === 'disabled' ? 'selected' : '' ?>>停用</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="admin-form-section">
                    <h2><?= $editing ? '重置密码（可选）' : '初始密码' ?></h2>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label" for="password">密码 <?= $editing ? '' : '<span class="text-danger">*</span>' ?></label>
                            <input class="form-control" id="password" name="password" type="password"
                                   minlength="8" maxlength="72" autocomplete="new-password" <?= $editing ? '' : 'required' ?>>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="password_confirm">确认密码 <?= $editing ? '' : '<span class="text-danger">*</span>' ?></label>
                            <input class="form-control" id="password_confirm" name="password_confirm" type="password"
                                   minlength="8" maxlength="72" autocomplete="new-password" <?= $editing ? '' : 'required' ?>>
                        </div>
                    </div>
                    <div class="form-text">密码只保存 password_hash 哈希；请勿使用示例密码作为正式凭据。</div>
                </div>

                <div class="d-flex flex-wrap gap-2">
                    <button class="btn btn-primary" type="submit"><?= $editing ? '保存修改' : '创建账号' ?></button>
                    <a class="btn btn-outline-secondary" href="<?= e(url('/admin/users')) ?>">取消</a>
                </div>
            </form>
        </div>
    </div>
</div>
