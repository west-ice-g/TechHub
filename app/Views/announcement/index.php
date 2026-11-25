<?php
/**
 * TechHub - 高校技术团队展示与管理平台
 *
 * 文件：app/Views/announcement/index.php
 * 模块：团队公告列表
 * 职责：展示按置顶和发布时间排序的有效公告。
 * 作者：待团队填写
 * 创建日期：2026-11-25
 * 最后修改：2026-11-25
 */

declare(strict_types=1);

$typeMap = [
    'general' => ['通知', 'bi-info-circle'],
    'event' => ['活动', 'bi-calendar-event'],
    'recruitment' => ['招募', 'bi-person-plus'],
    'system' => ['系统', 'bi-gear'],
];
?>
<section class="page-hero">
    <div class="container">
        <div class="page-hero-grid">
            <div>
                <nav class="mini-breadcrumb" aria-label="面包屑">
                    <a href="<?= e(url('/')) ?>">首页</a>
                    <i class="bi bi-chevron-right" aria-hidden="true"></i>
                    <span>团队公告</span>
                </nav>
                <div class="eyebrow mt-4">ANNOUNCEMENTS</div>
                <h1>团队公告</h1>
                <p class="page-hero-lead">团队通知、活动安排与阶段性信息。</p>
            </div>
            <div class="page-hero-side">
                <span class="page-hero-number"><?= e((string) $pagination['total']) ?></span>
                <span class="page-hero-label">条有效公告</span>
            </div>
        </div>
    </div>
</section>

<section class="section-block">
    <div class="container">
        <?php if ($announcements === []): ?>
            <div class="empty-state">
                <i class="bi bi-megaphone display-5 d-block mb-3" aria-hidden="true"></i>
                暂无有效公告。
            </div>
        <?php else: ?>
            <div class="announcement-stack">
                <?php foreach ($announcements as $announcement): ?>
                    <?php $typeInfo = $typeMap[$announcement['type']] ?? ['公告', 'bi-megaphone']; ?>
                    <article class="announcement-card <?= (int) $announcement['is_pinned'] === 1 ? 'pinned' : '' ?>">
                        <div class="announcement-marker">
                            <i class="bi <?= e($typeInfo[1]) ?>" aria-hidden="true"></i>
                        </div>
                        <div class="flex-grow-1">
                            <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                                <?php if ((int) $announcement['is_pinned'] === 1): ?>
                                    <span class="badge text-bg-primary"><i class="bi bi-pin-angle me-1" aria-hidden="true"></i>置顶</span>
                                <?php endif; ?>
                                <span class="badge text-bg-light"><?= e($typeInfo[0]) ?></span>
                                <span class="small text-secondary">
                                    <i class="bi bi-person me-1" aria-hidden="true"></i><?= e($announcement['publisher_name']) ?>
                                </span>
                                <span class="small text-secondary">
                                    <i class="bi bi-calendar3 me-1" aria-hidden="true"></i><?= e(format_datetime($announcement['published_at'], 'Y-m-d')) ?>
                                </span>
                            </div>
                            <h2 class="h4 mb-2">
                                <a href="<?= e(url('/announcements/' . $announcement['id'])) ?>"><?= e($announcement['title']) ?></a>
                            </h2>
                            <?php if (!empty($announcement['summary'])): ?>
                                <p class="text-secondary mb-2"><?= e(excerpt_text($announcement['summary'], 160)) ?></p>
                            <?php endif; ?>
                            <a class="text-link small" href="<?= e(url('/announcements/' . $announcement['id'])) ?>">
                                阅读公告 <i class="bi bi-arrow-right" aria-hidden="true"></i>
                            </a>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>

            <?php require BASE_PATH . '/app/Views/partials/pagination.php'; ?>
        <?php endif; ?>
    </div>
</section>
