<?php
/**
 * TechHub - 高校技术团队展示与管理平台
 *
 * 文件：app/Views/admin/articles/index.php
 * 模块：文章管理
 * 职责：显示文章列表、分类/状态筛选、发布与草稿操作。
 * 作者：待团队填写
 * 创建日期：2026-11-25
 * 最后修改：2026-11-25
 */

declare(strict_types=1);

$statusLabels = ['draft' => '草稿', 'published' => '已发布', 'archived' => '已归档'];
$statusBadges = ['draft' => 'text-bg-secondary', 'published' => 'text-bg-success', 'archived' => 'text-bg-dark'];
$actions = '<a class="btn btn-sm btn-primary" href="' . e(url('/admin/articles/create')) . '"><i class="bi bi-pencil-square me-1" aria-hidden="true"></i>新增文章</a>';
$heading = '文章管理';
$description = '维护文章内容、分类、封面、发布与草稿状态。';
$kicker = 'ARTICLES';
include BASE_PATH . '/app/Views/admin/partials/page-header.php';
?>

<form class="admin-filter" method="get" action="<?= e(url('/admin/articles')) ?>">
    <div class="row g-2 align-items-end">
        <div class="col-md-4">
            <label class="form-label small mb-1" for="q">搜索</label>
            <input class="form-control form-control-sm" id="q" name="q" type="search" maxlength="100"
                   value="<?= e($query) ?>" placeholder="标题、别名、摘要或正文">
        </div>
        <div class="col-md-3 col-6">
            <label class="form-label small mb-1" for="category_id">分类</label>
            <select class="form-select form-select-sm" id="category_id" name="category_id">
                <option value="0">全部分类</option>
                <?php foreach ($categories as $category): ?>
                    <option value="<?= e((string) $category['id']) ?>" <?= $categoryId === (int) $category['id'] ? 'selected' : '' ?>>
                        <?= e($category['name']) ?>
                    </option>
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
            <a class="btn btn-sm btn-outline-secondary" href="<?= e(url('/admin/articles')) ?>">重置</a>
        </div>
    </div>
</form>

<div class="admin-card">
    <div class="table-responsive">
        <table class="table admin-table">
            <thead>
            <tr>
                <th>文章</th>
                <th>分类</th>
                <th>作者</th>
                <th>状态</th>
                <th>浏览</th>
                <th>发布时间</th>
                <th class="text-end">操作</th>
            </tr>
            </thead>
            <tbody>
            <?php if ($articles === []): ?>
                <tr><td colspan="7"><div class="admin-empty"><i class="bi bi-journal-text" aria-hidden="true"></i>没有符合条件的文章。</div></td></tr>
            <?php endif; ?>
            <?php foreach ($articles as $article): ?>
                <tr>
                    <td>
                        <strong><?= e($article['title']) ?></strong>
                        <div class="small text-secondary"><code class="admin-code"><?= e($article['slug']) ?></code>
                            <?php if ((int) $article['is_featured'] === 1): ?><span class="badge text-bg-warning ms-1">推荐</span><?php endif; ?>
                        </div>
                    </td>
                    <td><?= e($article['category_name']) ?></td>
                    <td><?= e($article['author_name']) ?></td>
                    <td><span class="badge <?= e($statusBadges[$article['status']] ?? 'text-bg-secondary') ?>"><?= e($statusLabels[$article['status']] ?? $article['status']) ?></span></td>
                    <td><?= e((string) $article['view_count']) ?></td>
                    <td class="small text-secondary text-nowrap"><?= e(format_datetime($article['published_at'] ?? null)) ?></td>
                    <td class="text-end">
                        <div class="table-actions justify-content-end">
                            <?php if ($article['status'] === 'published'): ?>
                                <a class="btn btn-outline-secondary" href="<?= e(url('/articles/' . $article['slug'])) ?>" target="_blank" rel="noopener">查看</a>
                            <?php endif; ?>
                            <a class="btn btn-outline-primary" href="<?= e(url('/admin/articles/' . $article['id'] . '/edit')) ?>">编辑</a>
                            <?php if ($article['status'] !== 'published'): ?>
                                <form method="post" action="<?= e(url('/admin/articles/' . $article['id'] . '/status')) ?>" class="d-inline"
                                      data-confirm="确定发布该文章吗？">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="status" value="published">
                                    <button class="btn btn-outline-success" type="submit">发布</button>
                                </form>
                            <?php else: ?>
                                <form method="post" action="<?= e(url('/admin/articles/' . $article['id'] . '/status')) ?>" class="d-inline"
                                      data-confirm="转为草稿后前台将不可见。确定吗？">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="status" value="draft">
                                    <button class="btn btn-outline-warning" type="submit">转草稿</button>
                                </form>
                                <form method="post" action="<?= e(url('/admin/articles/' . $article['id'] . '/status')) ?>" class="d-inline"
                                      data-confirm="确定归档该文章吗？">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="status" value="archived">
                                    <button class="btn btn-outline-secondary" type="submit">归档</button>
                                </form>
                            <?php endif; ?>
                            <form method="post" action="<?= e(url('/admin/articles/' . $article['id'] . '/delete')) ?>" class="d-inline"
                                  data-confirm="删除文章不可恢复。确定删除吗？">
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
    $paginationPath = '/admin/articles';
    $paginationQuery = ['q' => $query, 'status' => $status, 'category_id' => $categoryId > 0 ? $categoryId : null];
    include BASE_PATH . '/app/Views/admin/partials/pagination.php';
    ?>
</div>
