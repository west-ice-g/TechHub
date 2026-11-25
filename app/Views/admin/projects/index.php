<?php
/**
 * TechHub - 高校技术团队展示与管理平台
 *
 * 文件：app/Views/admin/projects/index.php
 * 模块：项目管理
 * 职责：显示项目列表、状态、发布状态和操作入口。
 * 作者：待团队填写
 * 创建日期：2026-11-25
 * 最后修改：2026-11-25
 */

declare(strict_types=1);

$statusLabels = ['planning' => '规划中', 'ongoing' => '进行中', 'completed' => '已完成', 'archived' => '已归档'];
$statusBadges = ['planning' => 'text-bg-secondary', 'ongoing' => 'text-bg-primary', 'completed' => 'text-bg-success', 'archived' => 'text-bg-dark'];
$actions = '<a class="btn btn-sm btn-primary" href="' . e(url('/admin/projects/create')) . '"><i class="bi bi-plus-lg me-1" aria-hidden="true"></i>新增项目</a>';
$heading = '项目管理';
$description = '维护项目资料、封面、成员关联、状态与前台发布状态。';
$kicker = 'PROJECTS';
include BASE_PATH . '/app/Views/admin/partials/page-header.php';
?>

<form class="admin-filter" method="get" action="<?= e(url('/admin/projects')) ?>">
    <div class="row g-2 align-items-end">
        <div class="col-md-5">
            <label class="form-label small mb-1" for="q">搜索</label>
            <input class="form-control form-control-sm" id="q" name="q" type="search" maxlength="80"
                   value="<?= e($query) ?>" placeholder="标题、别名、摘要或技术栈">
        </div>
        <div class="col-md-2 col-6">
            <label class="form-label small mb-1" for="status">状态</label>
            <select class="form-select form-select-sm" id="status" name="status">
                <option value="">全部状态</option>
                <?php foreach ($statusLabels as $key => $label): ?>
                    <option value="<?= e($key) ?>" <?= $status === $key ? 'selected' : '' ?>><?= e($label) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2 col-6">
            <label class="form-label small mb-1" for="visibility">发布</label>
            <select class="form-select form-select-sm" id="visibility" name="visibility">
                <option value="">全部</option>
                <option value="public" <?= $visibility === 'public' ? 'selected' : '' ?>>已发布</option>
                <option value="hidden" <?= $visibility === 'hidden' ? 'selected' : '' ?>>已下线</option>
            </select>
        </div>
        <div class="col-md-3 d-flex gap-2">
            <button class="btn btn-sm btn-primary flex-fill" type="submit">筛选</button>
            <a class="btn btn-sm btn-outline-secondary" href="<?= e(url('/admin/projects')) ?>">重置</a>
        </div>
    </div>
</form>

<div class="admin-card">
    <div class="table-responsive">
        <table class="table admin-table">
            <thead>
            <tr>
                <th>项目</th>
                <th>状态</th>
                <th>发布</th>
                <th>成员</th>
                <th>开始 / 结束</th>
                <th>排序</th>
                <th class="text-end">操作</th>
            </tr>
            </thead>
            <tbody>
            <?php if ($projects === []): ?>
                <tr><td colspan="7"><div class="admin-empty"><i class="bi bi-kanban" aria-hidden="true"></i>没有符合条件的项目。</div></td></tr>
            <?php endif; ?>
            <?php foreach ($projects as $project): ?>
                <tr>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <?php $cover = media_url((string) ($project['cover_path'] ?? '')); ?>
                            <?php if ($cover !== null): ?>
                                <img class="admin-mini-avatar" src="<?= e($cover) ?>" alt="" style="width:44px;height:32px;border-radius:6px;object-fit:cover;">
                            <?php endif; ?>
                            <div class="min-w-0">
                                <strong><?= e($project['title']) ?></strong>
                                <div class="small text-secondary"><code class="admin-code"><?= e($project['slug']) ?></code></div>
                            </div>
                        </div>
                    </td>
                    <td><span class="badge <?= e($statusBadges[$project['status']] ?? 'text-bg-secondary') ?>"><?= e($statusLabels[$project['status']] ?? $project['status']) ?></span></td>
                    <td>
                        <span class="badge <?= (int) $project['is_public'] === 1 ? 'text-bg-success' : 'text-bg-secondary' ?>">
                            <?= (int) $project['is_public'] === 1 ? '已发布' : '已下线' ?>
                        </span>
                        <?php if ((int) $project['is_featured'] === 1): ?>
                            <span class="badge text-bg-warning">推荐</span>
                        <?php endif; ?>
                    </td>
                    <td><?= e((string) $project['member_count']) ?></td>
                    <td class="small text-nowrap">
                        <?= e($project['start_date'] ?? '—') ?><br>
                        <span class="text-secondary"><?= e($project['end_date'] ?? '—') ?></span>
                    </td>
                    <td><?= e((string) $project['sort_order']) ?></td>
                    <td class="text-end">
                        <div class="table-actions justify-content-end">
                            <a class="btn btn-outline-primary" href="<?= e(url('/admin/projects/' . $project['id'] . '/edit')) ?>">编辑</a>
                            <form method="post" action="<?= e(url('/admin/projects/' . $project['id'] . '/visibility')) ?>" class="d-inline"
                                  data-confirm="确定要<?= (int) $project['is_public'] === 1 ? '下线' : '发布' ?>该项目吗？">
                                <?= csrf_field() ?>
                                <button class="btn btn-outline-<?= (int) $project['is_public'] === 1 ? 'warning' : 'success' ?>" type="submit">
                                    <?= (int) $project['is_public'] === 1 ? '下线' : '发布' ?>
                                </button>
                            </form>
                            <form method="post" action="<?= e(url('/admin/projects/' . $project['id'] . '/delete')) ?>" class="d-inline"
                                  data-confirm="删除项目会同时解除项目成员关联，且不可恢复。确定删除吗？">
                                <?= csrf_field() ?>
                                <button class="btn btn-outline-danger" type="submit">删除</button>
                            </form>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <?php
    $paginationPath = '/admin/projects';
    $paginationQuery = ['q' => $query, 'status' => $status, 'visibility' => $visibility];
    include BASE_PATH . '/app/Views/admin/partials/pagination.php';
    ?>
</div>
