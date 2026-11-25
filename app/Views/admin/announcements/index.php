<?php
/**
 * TechHub - 高校技术团队展示与管理平台
 *
 * 文件：app/Views/admin/announcements/index.php
 * 模块：公告管理
 * 职责：显示公告列表、类型、置顶、状态和发布操作。
 * 作者：待团队填写
 * 创建日期：2026-11-25
 * 最后修改：2026-11-25
 */

declare(strict_types=1);

$statusLabels = ['draft' => '草稿', 'published' => '已发布', 'archived' => '已下线'];
$statusBadges = ['draft' => 'text-bg-secondary', 'published' => 'text-bg-success', 'archived' => 'text-bg-dark'];
$typeLabels = ['general' => '普通', 'event' => '活动', 'recruitment' => '招募', 'system' => '系统'];
$actions = '<a class="btn btn-sm btn-primary" href="' . e(url('/admin/announcements/create')) . '"><i class="bi bi-plus-lg me-1" aria-hidden="true"></i>新增公告</a>';
$heading = '公告管理';
$description = '公告支持置顶、有效期、发布与下线；过期公告自动从前台隐藏。';
$kicker = 'ANNOUNCEMENTS';
include BASE_PATH . '/app/Views/admin/partials/page-header.php';
?>

<form class="admin-filter" method="get" action="<?= e(url('/admin/announcements')) ?>">
    <div class="row g-2 align-items-end">
        <div class="col-md-5">
            <label class="form-label small mb-1" for="q">搜索</label>
            <input class="form-control form-control-sm" id="q" name="q" type="search" maxlength="100"
                   value="<?= e($query) ?>" placeholder="标题、摘要或正文">
        </div>
        <div class="col-md-2 col-6">
            <label class="form-label small mb-1" for="type">类型</label>
            <select class="form-select form-select-sm" id="type" name="type">
                <option value="">全部类型</option>
                <?php foreach ($typeLabels as $key => $label): ?>
                    <option value="<?= e($key) ?>" <?= $type === $key ? 'selected' : '' ?>><?= e($label) ?></option>
                <?php endforeach; ?>
            </select>
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
        <div class="col-md-3 d-flex gap-2">
            <button class="btn btn-sm btn-primary flex-fill" type="submit">筛选</button>
            <a class="btn btn-sm btn-outline-secondary" href="<?= e(url('/admin/announcements')) ?>">重置</a>
        </div>
    </div>
</form>

<div class="admin-card">
    <div class="table-responsive">
        <table class="table admin-table">
            <thead>
            <tr>
                <th>公告</th>
                <th>类型</th>
                <th>状态</th>
                <th>发布人</th>
                <th>发布时间</th>
                <th>过期时间</th>
                <th class="text-end">操作</th>
            </tr>
            </thead>
            <tbody>
            <?php if ($announcements === []): ?>
                <tr><td colspan="7"><div class="admin-empty"><i class="bi bi-megaphone" aria-hidden="true"></i>没有符合条件的公告。</div></td></tr>
            <?php endif; ?>
            <?php foreach ($announcements as $announcement): ?>
                <tr>
                    <td>
                        <?php if ((int) $announcement['is_pinned'] === 1): ?><span class="badge text-bg-warning me-1">置顶</span><?php endif; ?>
                        <strong><?= e($announcement['title']) ?></strong>
                        <div class="small text-secondary"><?= e(excerpt_text((string) ($announcement['summary'] ?? ''), 60)) ?></div>
                    </td>
                    <td><?= e($typeLabels[$announcement['type']] ?? $announcement['type']) ?></td>
                    <td><span class="badge <?= e($statusBadges[$announcement['status']] ?? 'text-bg-secondary') ?>"><?= e($statusLabels[$announcement['status']] ?? $announcement['status']) ?></span></td>
                    <td><?= e($announcement['publisher_name']) ?></td>
                    <td class="small text-secondary text-nowrap"><?= e(format_datetime($announcement['published_at'] ?? null)) ?></td>
                    <td class="small text-secondary text-nowrap"><?= e(format_datetime($announcement['expires_at'] ?? null)) ?></td>
                    <td class="text-end">
                        <div class="table-actions justify-content-end">
                            <?php if ($announcement['status'] === 'published'): ?>
                                <a class="btn btn-outline-secondary" href="<?= e(url('/announcements/' . $announcement['id'])) ?>" target="_blank" rel="noopener">查看</a>
                            <?php endif; ?>
                            <a class="btn btn-outline-primary" href="<?= e(url('/admin/announcements/' . $announcement['id'] . '/edit')) ?>">编辑</a>
                            <?php if ($announcement['status'] !== 'published'): ?>
                                <form method="post" action="<?= e(url('/admin/announcements/' . $announcement['id'] . '/status')) ?>" class="d-inline"
                                      data-confirm="确定发布该公告吗？">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="status" value="published">
                                    <button class="btn btn-outline-success" type="submit">发布</button>
                                </form>
                            <?php else: ?>
                                <form method="post" action="<?= e(url('/admin/announcements/' . $announcement['id'] . '/status')) ?>" class="d-inline"
                                      data-confirm="下线后前台将不再显示该公告。确定吗？">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="status" value="archived">
                                    <button class="btn btn-outline-warning" type="submit">下线</button>
                                </form>
                            <?php endif; ?>
                            <form method="post" action="<?= e(url('/admin/announcements/' . $announcement['id'] . '/delete')) ?>" class="d-inline"
                                  data-confirm="删除公告不可恢复。确定删除吗？">
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
    $paginationPath = '/admin/announcements';
    $paginationQuery = ['q' => $query, 'status' => $status, 'type' => $type];
    include BASE_PATH . '/app/Views/admin/partials/pagination.php';
    ?>
</div>
