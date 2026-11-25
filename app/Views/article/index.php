<?php
/**
 * TechHub - 高校技术团队展示与管理平台
 *
 * 文件：app/Views/article/index.php
 * 模块：技术文章列表
 * 职责：展示分类筛选、关键词搜索和分页的文章列表。
 * 作者：待团队填写
 * 创建日期：2026-11-25
 * 最后修改：2026-11-25
 */

declare(strict_types=1);
?>
<section class="page-hero">
    <div class="container">
        <div class="page-hero-grid">
            <div>
                <nav class="mini-breadcrumb" aria-label="面包屑">
                    <a href="<?= e(url('/')) ?>">首页</a>
                    <i class="bi bi-chevron-right" aria-hidden="true"></i>
                    <span>技术文章</span>
                </nav>
                <div class="eyebrow mt-4">KNOWLEDGE</div>
                <h1>技术文章</h1>
                <p class="page-hero-lead">记录实践、复盘问题，也分享团队对技术的理解。</p>
            </div>
            <div class="page-hero-side">
                <span class="page-hero-number"><?= e((string) $pagination['total']) ?></span>
                <span class="page-hero-label">篇已发布文章</span>
            </div>
        </div>
    </div>
</section>

<section class="section-block">
    <div class="container">
        <div class="toolbar-card toolbar-card-wrap mb-4">
            <div class="filter-chips">
                <a class="filter-chip <?= $categorySlug === '' ? 'active' : '' ?>"
                   href="<?= e(url_with_query('/articles', ['q' => $q])) ?>">全部</a>
                <?php foreach ($categories as $category): ?>
                    <a class="filter-chip <?= $categorySlug === $category['slug'] ? 'active' : '' ?>"
                       href="<?= e(url_with_query('/articles', ['category' => $category['slug'], 'q' => $q])) ?>">
                        <?= e($category['name']) ?>
                        <span class="chip-count"><?= e((string) $category['article_count']) ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
            <form method="get" action="<?= e(url('/articles')) ?>" class="toolbar-search">
                <?php if ($categorySlug !== ''): ?>
                    <input type="hidden" name="category" value="<?= e($categorySlug) ?>">
                <?php endif; ?>
                <label class="visually-hidden" for="article-search">搜索文章</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-search" aria-hidden="true"></i></span>
                    <input class="form-control" id="article-search" name="q" type="search"
                           value="<?= e($q) ?>" maxlength="80" placeholder="搜索标题、摘要或正文">
                    <button class="btn btn-primary" type="submit">搜索</button>
                </div>
            </form>
        </div>

        <?php if ($q !== '' || $categorySlug !== ''): ?>
            <div class="results-note mb-4">
                <i class="bi bi-funnel" aria-hidden="true"></i>
                当前筛选：<?= $activeCategory !== null ? '分类“' . e($activeCategory['name']) . '”' : '全部分类' ?>
                <?= $q !== '' ? '，关键词“' . e($q) . '”' : '' ?>
                <a class="ms-2" href="<?= e(url('/articles')) ?>">重置</a>
            </div>
        <?php endif; ?>

        <?php if ($articles === []): ?>
            <div class="empty-state">
                <i class="bi bi-journal-x display-5 d-block mb-3" aria-hidden="true"></i>
                没有找到符合条件的文章，请尝试更换关键词或分类。
            </div>
        <?php else: ?>
            <div class="row g-4">
                <?php foreach ($articles as $article): ?>
                    <?php $coverUrl = media_url($article['cover_path'] ?? null); ?>
                    <div class="col-md-6">
                        <article class="article-card h-100">
                            <a class="article-cover" href="<?= e(url('/articles/' . $article['slug'])) ?>"
                               aria-label="阅读文章 <?= e($article['title']) ?>">
                                <?php if ($coverUrl !== null): ?>
                                    <img src="<?= e($coverUrl) ?>" alt="<?= e($article['title']) ?>封面" loading="lazy">
                                <?php else: ?>
                                    <span class="cover-fallback"><i class="bi bi-journal-text" aria-hidden="true"></i></span>
                                <?php endif; ?>
                            </a>
                            <div class="article-card-body">
                                <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                                    <a class="category-label" href="<?= e(url_with_query('/articles', ['category' => $article['category_slug']])) ?>">
                                        <?= e($article['category_name']) ?>
                                    </a>
                                    <?php if ((int) $article['is_featured'] === 1): ?>
                                        <span class="mini-label"><i class="bi bi-star-fill" aria-hidden="true"></i> 精选</span>
                                    <?php endif; ?>
                                </div>
                                <h2 class="h4">
                                    <a href="<?= e(url('/articles/' . $article['slug'])) ?>"><?= e($article['title']) ?></a>
                                </h2>
                                <p class="text-secondary"><?= e(excerpt_text($article['summary'], 120)) ?></p>
                                <div class="meta-line">
                                    <span><i class="bi bi-person" aria-hidden="true"></i> <?= e($article['author_name']) ?></span>
                                    <span><i class="bi bi-calendar3" aria-hidden="true"></i> <?= e(format_datetime($article['published_at'], 'Y-m-d')) ?></span>
                                    <span><i class="bi bi-eye" aria-hidden="true"></i> <?= e((string) $article['view_count']) ?></span>
                                </div>
                            </div>
                        </article>
                    </div>
                <?php endforeach; ?>
            </div>

            <?php require BASE_PATH . '/app/Views/partials/pagination.php'; ?>
        <?php endif; ?>
    </div>
</section>
