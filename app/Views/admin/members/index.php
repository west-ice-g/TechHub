<?php
/**
 * TechHub - 高校技术团队展示与管理平台
 *
 * 文件：app/Views/admin/members/index.php
 * 模块：成员管理
 * 职责：显示团队成员列表、状态、参与项目数和操作入口。
 * 作者：待团队填写
 * 创建日期：2026-11-25
 * 最后修改：2026-11-25
 */

declare(strict_types=1);

$statusLabels = ['active' => '在队', 'alumni' => '已毕业', 'removed' => '已移除'];
$actions = '<a class="btn btn-sm btn-primary" href="' . e(url('/admin/members/create')) . '"><i class="bi bi-person-plus me-1" aria-hidden="true"></i>添加成员</a>';
$heading = '团队成员';
$description = '管理成员关系、职位、介绍、头像和离队状态；账号本身在用户管理中维护。';
$kicker = 'TEAM MEMBERS';
include BASE_PATH . '/app/Views/admin/partials/page-header.php';
?>

<form class="admin-filter" method="get" action="<?= e(url('/admin/members')) ?>">
    <div class="row g-2 align-items-end">
        <div class="col-md-7">
            <label class="form-label small mb-1" for="q">搜索</label>
            <input class="form-control form-control-sm" id="q" name="q" type="search" maxlength="80"
                   value="<?= e($query) ?>" placeholder="姓名、账号、职位或技能">
        </div>
        <div class="col-md-3">
            <label class="form-label small mb-1" for="status">状态</label>
            <select class="form-select form-select-sm" id="status" name="status">
                <option value="">全部状态</option>
                <?php foreach ($statusLabels as $key => $label): ?>
                    <option value="<?= e($key) ?>" <?= $status === $key ? 'selected' : '' ?>><?= e($label) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2 d-flex gap-2">
            <button class="btn btn-sm btn-primary flex-fill" type="submit">筛选</button>
            <a class="btn btn-sm btn-outline-secondary" href="<?= e(url('/admin/members')) ?>">重置</a>
        </div>
    </div>
</form>

<div class="admin-card">
    <div class="table-responsive">
        <table class="table admin-table">
            <thead>
            <tr>
                <th>成员</th>
                <th>职位</th>
                <th>加入日期</th>
                <th>状态</th>
                <th>参与项目</th>
                <th>排序</th>
                <th class="text-end">操作</th>
            </tr>
            </thead>
            <tbody>
            <?php if ($members === []): ?>
                <tr><td colspan="7"><div class="admin-empty"><i class="bi bi-person-badge" aria-hidden="true"></i>没有符合条件的成员。</div></td></tr>
            <?php endif; ?>
            <?php foreach ($members as $member): ?>
                <tr>
                    <td>
                        <div class="d-flex align-items-center">
                            <?php if (!empty($member['avatar_path']) && media_url((string) $member['avatar_path']) !== null): ?>
                                <img class="admin-mini-avatar" src="<?= e((string) media_url((string) $member['avatar_path'])) ?>" alt="">
                            <?php else: ?>
                                <span class="admin-avatar me-2" style="width:28px;height:28px;flex-basis:28px;font-size:.72rem;" aria-hidden="true"><?= e(mb_strtoupper(mb_substr((string) $member['display_name'], 0, 1))) ?></span>
                            <?php endif; ?>
                            <div class="min-w-0">
                                <strong><?= e($member['display_name']) ?></strong>
                                <div class="small text-secondary"><code class="admin-code"><?= e($member['username']) ?></code></div>
                            </div>
                        </div>
                    </td>
                    <td>
                        <?= e($member['position_title'] ?? '—') ?>
                        <?php if (!empty($member['skills'])): ?>
                            <div class="small text-secondary"><?= e(excerpt_text((string) $member['skills'], 40)) ?></div>
                        <?php endif; ?>
                    </td>
                    <td class="text-nowrap small"><?= e($member['joined_at'] ?? '—') ?></td>
                    <td>
                        <span class="badge <?= $member['status'] === 'active' ? 'text-bg-success' : ($member['status'] === 'alumni' ? 'text-bg-warning' : 'text-bg-secondary') ?>">
                            <?= e($statusLabels[$member['status']] ?? $member['status']) ?>
                        </span>
                    </td>
                    <td><?= e((string) $member['project_count']) ?></td>
                    <td><?= e((string) $member['sort_order']) ?></td>
                    <td class="text-end">
                        <div class="table-actions justify-content-end">
                            <a class="btn btn-outline-primary" href="<?= e(url('/admin/members/' . $member['id'] . '/edit')) ?>">编辑</a>
                            <?php if ($member['status'] !== 'removed'): ?>
                                <form method="post" action="<?= e(url('/admin/members/' . $member['id'] . '/remove')) ?>" class="d-inline"
                                      data-confirm="标记离队后前台不再显示为在队成员，但项目历史保留。确定吗？">
                                    <?= csrf_field() ?>
                                    <button class="btn btn-outline-warning" type="submit">离队</button>
                                </form>
                            <?php endif; ?>
                            <?php if ((int) $member['project_count'] === 0): ?>
                                <form method="post" action="<?= e(url('/admin/members/' . $member['id'] . '/delete')) ?>" class="d-inline"
                                      data-confirm="删除成员关系不会删除账号或个人资料。确定删除吗？">
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
    $paginationPath = '/admin/members';
    $paginationQuery = ['q' => $query, 'status' => $status];
    include BASE_PATH . '/app/Views/admin/partials/pagination.php';
    ?>
</div>
