<?php
/**
 * TechHub - 高校技术团队展示与管理平台
 *
 * 文件：app/Views/admin/roles/index.php
 * 模块：角色权限
 * 职责：显示角色、账号数、权限数和操作入口。
 * 作者：待团队填写
 * 创建日期：2026-11-25
 * 最后修改：2026-11-25
 */

declare(strict_types=1);

$actions = '<a class="btn btn-sm btn-primary" href="' . e(url('/admin/roles/create')) . '"><i class="bi bi-plus-lg me-1" aria-hidden="true"></i>新增角色</a>';
$heading = '角色权限';
$description = '角色决定后台菜单可见性和服务端接口权限；超级管理员权限固定不可降级。';
$kicker = 'RBAC';
include BASE_PATH . '/app/Views/admin/partials/page-header.php';
?>

<div class="admin-card">
    <div class="table-responsive">
        <table class="table admin-table">
            <thead>
            <tr>
                <th>ID</th>
                <th>角色</th>
                <th>代码</th>
                <th>账号数</th>
                <th>权限数</th>
                <th>类型</th>
                <th>排序</th>
                <th class="text-end">操作</th>
            </tr>
            </thead>
            <tbody>
            <?php if ($roles === []): ?>
                <tr><td colspan="8"><div class="admin-empty"><i class="bi bi-shield-lock" aria-hidden="true"></i>暂无角色。</div></td></tr>
            <?php endif; ?>
            <?php foreach ($roles as $role): ?>
                <tr>
                    <td class="text-secondary"><?= e((string) $role['id']) ?></td>
                    <td>
                        <strong><?= e($role['name']) ?></strong>
                        <div class="small text-secondary"><?= e($role['description'] ?? '—') ?></div>
                    </td>
                    <td><code class="admin-code"><?= e($role['code']) ?></code></td>
                    <td><?= e((string) $role['user_count']) ?></td>
                    <td><?= e((string) $role['permission_count']) ?></td>
                    <td>
                        <?php if ((int) $role['is_system'] === 1): ?>
                            <span class="badge text-bg-primary">系统角色</span>
                        <?php else: ?>
                            <span class="badge text-bg-light border">自定义</span>
                        <?php endif; ?>
                    </td>
                    <td><?= e((string) $role['sort_order']) ?></td>
                    <td class="text-end">
                        <div class="table-actions justify-content-end">
                            <a class="btn btn-outline-primary" href="<?= e(url('/admin/roles/' . $role['id'] . '/edit')) ?>">编辑</a>
                            <?php if ((int) $role['is_system'] === 0 && (int) $role['user_count'] === 0): ?>
                                <form method="post" action="<?= e(url('/admin/roles/' . $role['id'] . '/delete')) ?>" class="d-inline"
                                      data-confirm="确定删除该角色吗？角色权限映射会一并删除。">
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
</div>

<div class="admin-note mt-3">
    编辑角色时可以勾选权限矩阵。超级管理员角色固定拥有全部权限；普通团队成员默认只能访问数据首页和本人资料。
</div>
