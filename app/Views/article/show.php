<?php
/**
 * TechHub - 高校技术团队展示与管理平台
 *
 * 文件：app/Views/article/show.php
 * 模块：技术文章详情
 * 职责：展示文章封面、发布时间、作者信息和安全渲染的正文。
 * 作者：待团队填写
 * 创建日期：2026-11-25
 * 最后修改：2026-11-25
 */

declare(strict_types=1);

$coverUrl = media_url($article['cover_path'] ?? null);
$avatarUrl = media_url($article['author_avatar'] ?? null);
$avatarName = (string) $article['author_name'];
$avatarClass = 'avatar-md';
?>
<section class="article-header">
    <div class="container article-header-inner">
        <a class="back-link" href="<?= e(url('/articles')) ?>">
            <i class="bi bi-arrow-left" aria-hidden="true"></i> 返回文章列表
        </a>
        <nav class="mini-breadcrumb mt-3" aria-label="面包屑">
            <a href="<?= e(url('/')) ?>">首页</a>
            <i class="bi bi-chevron-right" aria-hidden="true"></i>
            <a href="<?= e(url('/articles')) ?>">技术文章</a>
            <i class="bi bi-chevron-right" aria-hidden="true"></i>
            <span><?= e($article['category_name']) ?></span>
        </nav>
        <div class="article-title-block">
            <a class="category-label" href="<?= e(url_with_query('/articles', ['category' => $article['category_slug']])) ?>">
                <?= e($article['category_name']) ?>
            </a>
            <h1><?= e($article['title']) ?></h1>
            <p class="article-summary"><?= e($article['summary']) ?></p>
            <div class="article-meta">
                <span><i class="bi bi-person" aria-hidden="true"></i> <?= e($article['author_name']) ?></span>
                <span><i class="bi bi-calendar3" aria-hidden="true"></i> <?= e(format_datetime($article['published_at'], 'Y 年 m 月 d 日')) ?></span>
                <span><i class="bi bi-eye" aria-hidden="true"></i> <?= e((string) $article['view_count']) ?> 次浏览</span>
                <span><i class="bi bi-clock" aria-hidden="true"></i> 约 <?= e((string) max(1, (int) ceil(mb_strlen(strip_tags($article['content'])) / 500))) ?> 分钟阅读</span>
            </div>
        </div>
    </div>
</section>

<?php if ($coverUrl !== null): ?>
    <div class="container article-cover-wide">
        <img src="<?= e($coverUrl) ?>" alt="<?= e($article['title']) ?>封面">
    </div>
<?php endif; ?>

<section class="section-block pt-4">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-9 col-xl-8">
                <article class="article-content content-card">
                    <?= \App\Support\Markdown::render((string) $article['content']) ?>
                </article>

                <aside class="author-card mt-4">
                    <?php require BASE_PATH . '/app/Views/partials/avatar.php'; ?>
                    <div>
                        <div class="eyebrow">AUTHOR</div>
                        <h2 class="h5 mb-1"><?= e($article['author_name']) ?></h2>
                        <div class="small text-secondary">
                            <?= e($article['author_role'] ?? '团队成员') ?>
                            <?php if (!empty($article['author_bio'])): ?>
                                · <?= e(excerpt_text($article['author_bio'], 100)) ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </aside>

                <div class="article-footer-actions">
                    <a class="btn btn-outline-secondary" href="<?= e(url('/articles')) ?>">
                        <i class="bi bi-arrow-left me-1" aria-hidden="true"></i>继续阅读其他文章
                    </a>
                    <a class="btn btn-primary" href="<?= e(url('/messages')) ?>">
                        <i class="bi bi-chat-square-text me-1" aria-hidden="true"></i>前往留言板
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>
