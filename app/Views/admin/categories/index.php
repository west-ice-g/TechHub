<?php
/**
 * TechHub - 高校技术团队展示与管理平台
 *
 * 文件：app/Views/admin/categories/index.php
 * 模块：文章分类
 * 职责：显示分类层级、文章数、状态和操作入口。
 * 作者：待团队填写
 * 创建日期：2026-11-25
 * 最后修改：2026-11-25
 */

declare(strict_types=1);

$actions = '<a class="btn btn-sm btn-primary" href="' . e(url('/admin/categories/create')) . '"><i class="bi bi-plus-lg me-1" aria-hidden="true"></i>新增分类</a>';
$heading = '文章分类';
$description = '分类可嵌套一层或多层；停用分类不再出现在前台筛选，但已有文章保留。';
$kicker = 'CATEGORIES';
include BASE_PATH . '/app/Views/admin/partials/page-header.php';
?>

<div class="admin-card">
    <div class="table-responsive">
        <table class="table admin-table">
            <thead>
            <tr>
                <th>ID</th>
                <th>分类名称</th>
                <th>别名</th>
                <th>父分类</th>
                <th>文章数</th>
                <th>子分类</th>
                <th>状态</th>
                <th>排序</th>
                <th class="text-end">操作</th>
            </tr>
            </thead>
            <tbody>
            <?php if ($categories === []): ?>
                <tr><td colspan="9"><div class="admin-empty"><i class="bi bi-tags" aria-hidden="true"></i>暂无分类。</div></td></tr>
            <?php endif; ?>
            <?php foreach ($categories as $category): ?>
                <tr>
                    <td class="text-secondary"><?= e((string) $category['id']) ?></td>
                    <td>
                        <strong><?= e($category['name']) ?></strong>
                        <?php if (!empty($category['description'])): ?>
                            <div class="small text-secondary"><?= e(excerpt_text((string) $category['description'], 50)) ?></div>
                        <?php endif; ?>
                    </td>
                    <td><code class="admin-code"><?= e($category['slug']) ?></code></td>
                    <td><?= e($category['parent_name'] ?? '—') ?></td>
                    <td><?= e((string) $category['article_count']) ?></td>
                    <td><?= e((string) $category['child_count']) ?></td>
                    <td>
                        <span class="badge <?= $category['status'] === 'enabled' ? 'text-bg-success' : 'text-bg-secondary' ?>">
                            <?= $category['status'] === 'enabled' ? '启用' : '停用' ?>
                        </span>
                    </td>
                    <td><?= e((string) $category['sort_order']) ?></td>
                    <td class="text-end">
                        <div class="table-actions justify-content-end">
                            <a class="btn btn-outline-primary" href="<?= e(url('/admin/categories/' . $category['id'] . '/edit')) ?>">编辑</a>
                            <form method="post" action="<?= e(url('/admin/categories/' . $category['id'] . '/status')) ?>" class="d-inline"
                                  data-confirm="确定要<?= $category['status'] === 'enabled' ? '停用' : '启用' ?>该分类吗？">
                                <?= csrf_field() ?>
                                <button class="btn btn-outline-<?= $category['status'] === 'enabled' ? 'warning' : 'success' ?>" type="submit">
                                    <?= $category['status'] === 'enabled' ? '停用' : '启用' ?>
                                </button>
                            </form>
                            <?php if ((int) $category['article_count'] === 0 && (int) $category['child_count'] === 0): ?>
                                <form method="post" action="<?= e(url('/admin/categories/' . $category['id'] . '/delete')) ?>" class="d-inline"
                                      data-confirm="确定删除该分类吗？">
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
