<?php
/**
 * TechHub - 高校技术团队展示与管理平台
 *
 * 文件：app/Views/admin/roles/form.php
 * 模块：角色权限
 * 职责：新增/编辑角色并配置权限矩阵。
 * 作者：待团队填写
 * 创建日期：2026-11-25
 * 最后修改：2026-11-25
 */

declare(strict_types=1);

$r = static function (string $key, mixed $default = '') use ($formOld, $role): mixed {
    return $formOld[$key] ?? $role[$key] ?? $default;
};
$isSuperAdminRole = $editing && ($role['code'] ?? '') === 'super_admin';
$selectedPermissions = array_map('intval', $rolePermissionIds);
if ($isSuperAdminRole) {
    $selectedPermissions = array_map(static fn (array $p): int => (int) $p['id'], $permissionItems);
}
$moduleLabels = [
    'dashboard' => '概览',
    'profile' => '个人资料',
    'stats' => '统计',
    'team' => '团队',
    'members' => '成员',
    'projects' => '项目',
    'articles' => '文章',
    'categories' => '分类',
    'announcements' => '公告',
    'messages' => '留言',
    'files' => '文件',
    'users' => '用户',
    'roles' => '角色',
    'activity' => '操作日志',
];
$grouped = [];
foreach ($permissionItems as $permission) {
    $grouped[(string) $permission['module']][] = $permission;
}
$heading = $editing ? '编辑角色' : '新增角色';
$description = $editing ? '角色代码创建后不可修改；权限保存后立即影响菜单与服务端校验。' : '创建角色后可在编辑页配置权限。';
$kicker = 'ROLE & PERMISSION';
$actions = '<a class="btn btn-sm btn-outline-secondary" href="' . e(url('/admin/roles')) . '">返回角色列表</a>';
include BASE_PATH . '/app/Views/admin/partials/page-header.php';
?>

<div class="row g-3">
    <div class="col-xl-5">
        <div class="admin-form-card">
            <?php include BASE_PATH . '/app/Views/admin/partials/form-errors.php'; ?>
            <form method="post" action="<?= e($editing ? url('/admin/roles/' . $role['id'] . '/edit') : url('/admin/roles')) ?>">
                <?= csrf_field() ?>

                <div class="admin-form-section">
                    <h2>角色信息</h2>
                    <div class="mb-3">
                        <label class="form-label" for="name">角色名称 <span class="text-danger">*</span></label>
                        <input class="form-control" id="name" name="name" type="text" maxlength="50"
                               value="<?= e($r('name')) ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="code">角色代码 <span class="text-danger">*</span></label>
                        <input class="form-control" id="code" name="code" type="text" maxlength="50"
                               value="<?= e($r('code')) ?>" pattern="[a-z][a-z0-9_]{2,49}"
                               <?= $editing ? 'readonly' : 'required' ?>>
                        <div class="form-text">小写字母开头，可使用数字和下划线；创建后不可修改。</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="description">说明</label>
                        <textarea class="form-control" id="description" name="description" rows="3" maxlength="255"><?= e($r('description')) ?></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="sort_order">排序</label>
                        <input class="form-control" id="sort_order" name="sort_order" type="number" min="-999" max="999"
                               value="<?= e((string) $r('sort_order', 0)) ?>">
                    </div>
                </div>

                <div class="d-flex flex-wrap gap-2">
                    <button class="btn btn-primary" type="submit"><?= $editing ? '保存角色信息' : '创建角色' ?></button>
                    <a class="btn btn-outline-secondary" href="<?= e(url('/admin/roles')) ?>">取消</a>
                </div>
            </form>
        </div>
    </div>

    <div class="col-xl-7">
        <div class="admin-form-card">
            <div class="admin-form-section">
                <h2>权限矩阵</h2>
                <?php if (!$editing): ?>
                    <div class="admin-note">创建角色后可在此配置权限。</div>
                <?php elseif ($isSuperAdminRole): ?>
                    <div class="alert alert-info small">
                        <i class="bi bi-shield-lock me-1" aria-hidden="true"></i>
                        超级管理员角色固定拥有全部权限，不能在界面中降权，以防止系统失去管理入口。
                    </div>
                <?php endif; ?>

                <?php if ($editing): ?>
                    <form method="post" action="<?= e(url('/admin/roles/' . $role['id'] . '/permissions')) ?>">
                        <?= csrf_field() ?>
                        <?php foreach ($grouped as $module => $items): ?>
                            <div class="mb-3">
                                <div class="small fw-bold text-secondary mb-2"><?= e($moduleLabels[$module] ?? $module) ?></div>
                                <div class="admin-permission-grid">
                                    <?php foreach ($items as $permission): ?>
                                        <div class="admin-permission-item">
                                            <input class="form-check-input" type="checkbox"
                                                   id="permission-<?= e((string) $permission['id']) ?>"
                                                   name="permissions[]" value="<?= e((string) $permission['id']) ?>"
                                                   <?= in_array((int) $permission['id'], $selectedPermissions, true) ? 'checked' : '' ?>
                                                   <?= $isSuperAdminRole ? 'disabled' : '' ?>>
                                            <label for="permission-<?= e((string) $permission['id']) ?>">
                                                <?= e($permission['name']) ?>
                                                <small><code class="admin-code"><?= e($permission['code']) ?></code></small>
                                            </label>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>

                        <?php if (!$isSuperAdminRole): ?>
                            <button class="btn btn-primary" type="submit">
                                <i class="bi bi-check2-circle me-1" aria-hidden="true"></i>保存权限
                            </button>
                        <?php endif; ?>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
