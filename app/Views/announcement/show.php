<?php
/**
 * TechHub - 高校技术团队展示与管理平台
 *
 * 文件：app/Views/announcement/show.php
 * 模块：团队公告详情
 * 职责：展示公告全文、类型、发布人与有效期。
 * 作者：待团队填写
 * 创建日期：2026-11-25
 * 最后修改：2026-11-25
 */

declare(strict_types=1);

$typeMap = [
    'general' => '通知',
    'event' => '活动',
    'recruitment' => '招募',
    'system' => '系统',
];
$typeLabel = $typeMap[$announcement['type']] ?? '公告';
?>
<section class="article-header">
    <div class="container article-header-inner">
        <a class="back-link" href="<?= e(url('/announcements')) ?>">
            <i class="bi bi-arrow-left" aria-hidden="true"></i> 返回公告列表
        </a>
        <div class="article-title-block">
            <div class="d-flex flex-wrap gap-2 mb-3">
                <span class="badge text-bg-primary"><?= e($typeLabel) ?></span>
                <?php if ((int) $announcement['is_pinned'] === 1): ?>
                    <span class="badge text-bg-light"><i class="bi bi-pin-angle me-1" aria-hidden="true"></i>置顶公告</span>
                <?php endif; ?>
            </div>
            <h1><?= e($announcement['title']) ?></h1>
            <?php if (!empty($announcement['summary'])): ?>
                <p class="article-summary"><?= e($announcement['summary']) ?></p>
            <?php endif; ?>
            <div class="article-meta">
                <span><i class="bi bi-person" aria-hidden="true"></i> <?= e($announcement['publisher_name']) ?></span>
                <span><i class="bi bi-calendar3" aria-hidden="true"></i> <?= e(format_datetime($announcement['published_at'], 'Y 年 m 月 d 日')) ?></span>
                <?php if (!empty($announcement['expires_at'])): ?>
                    <span><i class="bi bi-hourglass-split" aria-hidden="true"></i> 有效期至 <?= e(format_datetime($announcement['expires_at'], 'Y-m-d')) ?></span>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<section class="section-block pt-4">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-9 col-xl-8">
                <article class="article-content content-card">
                    <?= \App\Support\Markdown::render((string) $announcement['content']) ?>
                </article>
                <div class="article-footer-actions">
                    <a class="btn btn-outline-secondary" href="<?= e(url('/announcements')) ?>">
                        <i class="bi bi-arrow-left me-1" aria-hidden="true"></i>返回公告列表
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>
