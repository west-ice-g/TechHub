<?php
/**
 * TechHub - 高校技术团队展示与管理平台
 *
 * 文件：app/Views/admin/users/index.php
 * 模块：用户管理
 * 职责：显示账号列表、筛选、角色、状态和操作入口。
 * 作者：待团队填写
 * 创建日期：2026-11-25
 * 最后修改：2026-11-25
 */

declare(strict_types=1);

$actions = '<a class="btn btn-sm btn-primary" href="' . e(url('/admin/users/create')) . '"><i class="bi bi-plus-lg me-1" aria-hidden="true"></i>新增账号</a>';
$heading = '用户管理';
$description = '创建后台账号、分配角色、启用或停用账号。仅超级管理员可操作。';
$kicker = 'USERS & RBAC';
include BASE_PATH . '/app/Views/admin/partials/page-header.php';
?>

<form class="admin-filter" method="get" action="<?= e(url('/admin/users')) ?>">
    <div class="row g-2 align-items-end">
        <div class="col-md-5">
            <label class="form-label small mb-1" for="q">搜索</label>
            <input class="form-control form-control-sm" id="q" name="q" type="search" maxlength="80"
                   value="<?= e($query) ?>" placeholder="用户名、显示名或邮箱">
        </div>
        <div class="col-md-3 col-6">
            <label class="form-label small mb-1" for="role_id">角色</label>
            <select class="form-select form-select-sm" id="role_id" name="role_id">
                <option value="0">全部角色</option>
                <?php foreach ($roles as $role): ?>
                    <option value="<?= e((string) $role['id']) ?>" <?= $roleId === (int) $role['id'] ? 'selected' : '' ?>>
                        <?= e($role['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2 col-6">
            <label class="form-label small mb-1" for="status">状态</label>
            <select class="form-select form-select-sm" id="status" name="status">
                <option value="">全部状态</option>
                <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>启用</option>
                <option value="disabled" <?= $status === 'disabled' ? 'selected' : '' ?>>停用</option>
            </select>
        </div>
        <div class="col-md-2 d-flex gap-2">
            <button class="btn btn-sm btn-primary flex-fill" type="submit">筛选</button>
            <a class="btn btn-sm btn-outline-secondary" href="<?= e(url('/admin/users')) ?>">重置</a>
        </div>
    </div>
</form>

<div class="admin-card">
    <div class="table-responsive">
        <table class="table admin-table">
            <thead>
            <tr>
                <th>ID</th>
                <th>账号</th>
                <th>显示名</th>
                <th>角色</th>
                <th>状态</th>
                <th>最近登录</th>
                <th class="text-end">操作</th>
            </tr>
            </thead>
            <tbody>
            <?php if ($users === []): ?>
                <tr><td colspan="7"><div class="admin-empty"><i class="bi bi-people" aria-hidden="true"></i>没有符合条件的账号。</div></td></tr>
            <?php endif; ?>
            <?php foreach ($users as $user): ?>
                <tr>
                    <td class="text-secondary"><?= e((string) $user['id']) ?></td>
                    <td>
                        <code class="admin-code"><?= e($user['username']) ?></code>
                        <?php if ((int) $user['id'] === (int) ($currentUser['id'] ?? 0)): ?>
                            <span class="badge text-bg-primary ms-1">当前</span>
                        <?php endif; ?>
                    </td>
                    <td><?= e($user['display_name']) ?></td>
                    <td>
                        <?= e($user['role_name']) ?>
                        <div class="small text-secondary"><?= e($user['role_code']) ?></div>
                    </td>
                    <td>
                        <span class="badge <?= $user['status'] === 'active' ? 'text-bg-success' : 'text-bg-secondary' ?>">
                            <?= $user['status'] === 'active' ? '启用' : '停用' ?>
                        </span>
                    </td>
                    <td class="small text-secondary text-nowrap"><?= e(format_datetime($user['last_login_at'] ?? null)) ?></td>
                    <td class="text-end">
                        <div class="table-actions justify-content-end">
                            <a class="btn btn-outline-primary" href="<?= e(url('/admin/users/' . $user['id'] . '/edit')) ?>">编辑</a>
                            <?php if ((int) $user['id'] !== (int) ($currentUser['id'] ?? 0)): ?>
                                <form method="post" action="<?= e(url('/admin/users/' . $user['id'] . '/status')) ?>" class="d-inline"
                                      data-confirm="确定要<?= $user['status'] === 'active' ? '停用' : '启用' ?>该账号吗？">
                                    <?= csrf_field() ?>
                                    <button class="btn btn-outline-<?= $user['status'] === 'active' ? 'warning' : 'success' ?>" type="submit">
                                        <?= $user['status'] === 'active' ? '停用' : '启用' ?>
                                    </button>
                                </form>
                                <form method="post" action="<?= e(url('/admin/users/' . $user['id'] . '/delete')) ?>" class="d-inline"
                                      data-confirm="删除账号不可恢复。已关联业务数据的账号必须改为停用。确定继续吗？">
                                    <?= csrf_field() ?>
                                    <button class="btn btn-outline-danger" type="submit">删除</button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <?php
    $paginationPath = '/admin/users';
    $paginationQuery = ['q' => $query, 'role_id' => $roleId > 0 ? $roleId : null, 'status' => $status];
    include BASE_PATH . '/app/Views/admin/partials/pagination.php';
    ?>
</div>

<div class="admin-note mt-3">
    <i class="bi bi-shield-check me-1" aria-hidden="true"></i>
    服务端同时执行 <code class="admin-code">user.manage</code> 路由权限和“仅超级管理员”控制器校验；隐藏按钮不能替代后端拦截。
</div>
