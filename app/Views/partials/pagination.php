<?php
/**
 * TechHub - 高校技术团队展示与管理平台
 *
 * 文件：app/Views/partials/pagination.php
 * 模块：公共组件
 * 职责：根据分页元数据输出上一页、页码、下一页及结果范围。
 * 作者：待团队填写
 * 创建日期：2026-11-25
 * 最后修改：2026-11-25
 */

declare(strict_types=1);

if (!isset($pagination) || !is_array($pagination) || (int) ($pagination['total'] ?? 0) === 0) {
    return;
}

$currentPage = (int) $pagination['page'];
$lastPage = (int) $pagination['last_page'];
$path = (string) ($paginationPath ?? '/');
$query = is_array($paginationQuery ?? null) ? $paginationQuery : [];
$pageUrl = static function (int $page) use ($path, $query): string {
    $query['page'] = $page;
    return url_with_query($path, $query);
};

$start = max(1, $currentPage - 2);
$end = min($lastPage, $currentPage + 2);
?>
<div class="pagination-wrap">
    <div class="pagination-summary">
        显示第 <?= e((string) $pagination['from']) ?>–<?= e((string) $pagination['to']) ?> 条，
        共 <?= e((string) $pagination['total']) ?> 条
    </div>

    <?php if ($lastPage > 1): ?>
        <nav aria-label="分页导航">
            <ul class="pagination pagination-sm mb-0">
                <li class="page-item <?= $currentPage <= 1 ? 'disabled' : '' ?>">
                    <a class="page-link" href="<?= e($pageUrl(max(1, $currentPage - 1))) ?>"
                       aria-label="上一页" <?= $currentPage <= 1 ? 'tabindex="-1" aria-disabled="true"' : '' ?>>
                        <i class="bi bi-chevron-left" aria-hidden="true"></i>
                        <span class="d-none d-sm-inline">上一页</span>
                    </a>
                </li>

                <?php if ($start > 1): ?>
                    <li class="page-item"><a class="page-link" href="<?= e($pageUrl(1)) ?>">1</a></li>
                    <?php if ($start > 2): ?>
                        <li class="page-item disabled"><span class="page-link">…</span></li>
                    <?php endif; ?>
                <?php endif; ?>

                <?php for ($page = $start; $page <= $end; $page++): ?>
                    <li class="page-item <?= $page === $currentPage ? 'active' : '' ?>">
                        <a class="page-link" href="<?= e($pageUrl($page)) ?>"
                           <?= $page === $currentPage ? 'aria-current="page"' : '' ?>><?= e((string) $page) ?></a>
                    </li>
                <?php endfor; ?>

                <?php if ($end < $lastPage): ?>
                    <?php if ($end < $lastPage - 1): ?>
                        <li class="page-item disabled"><span class="page-link">…</span></li>
                    <?php endif; ?>
                    <li class="page-item">
                        <a class="page-link" href="<?= e($pageUrl($lastPage)) ?>"><?= e((string) $lastPage) ?></a>
                    </li>
                <?php endif; ?>

                <li class="page-item <?= $currentPage >= $lastPage ? 'disabled' : '' ?>">
                    <a class="page-link" href="<?= e($pageUrl(min($lastPage, $currentPage + 1))) ?>"
                       aria-label="下一页" <?= $currentPage >= $lastPage ? 'tabindex="-1" aria-disabled="true"' : '' ?>>
                        <span class="d-none d-sm-inline">下一页</span>
                        <i class="bi bi-chevron-right" aria-hidden="true"></i>
                    </a>
                </li>
            </ul>
        </nav>
    <?php endif; ?>
</div>
