<?php
/**
 * TechHub - 高校技术团队展示与管理平台
 *
 * 文件：app/Views/download/index.php
 * 模块：资料下载
 * 职责：区分团队作业和个人作业，展示文件信息并提供受控下载入口。
 * 作者：待团队填写
 * 创建日期：2026-11-25
 * 最后修改：2026-11-25
 */

declare(strict_types=1);

$fileIcon = static function (string $extension): string {
    return match (strtolower($extension)) {
        'pdf' => 'bi-file-earmark-pdf',
        'doc', 'docx' => 'bi-file-earmark-word',
        'ppt', 'pptx' => 'bi-file-earmark-slides',
        'zip' => 'bi-file-earmark-zip',
        'txt' => 'bi-file-earmark-text',
        'png', 'jpg', 'jpeg', 'webp', 'gif' => 'bi-file-earmark-image',
        default => 'bi-file-earmark',
    };
};
$activeType = $types[$type];
?>
<section class="page-hero">
    <div class="container">
        <div class="page-hero-grid">
            <div>
                <nav class="mini-breadcrumb" aria-label="面包屑">
                    <a href="<?= e(url('/')) ?>">首页</a>
                    <i class="bi bi-chevron-right" aria-hidden="true"></i>
                    <span>资料下载</span>
                </nav>
                <div class="eyebrow mt-4">COURSE FILES</div>
                <h1>作业与资料下载</h1>
                <p class="page-hero-lead">
                    团队作业与个人作业分别存放、分别展示，所有下载都经过路径和公开状态校验。
                </p>
            </div>
            <div class="page-hero-side">
                <span class="page-hero-number"><?= e((string) ($counts['team'] + $counts['personal'])) ?></span>
                <span class="page-hero-label">份公开文件</span>
            </div>
        </div>
    </div>
</section>

<section class="section-block">
    <div class="container">
        <div class="download-tabs" role="tablist" aria-label="资料分类">
            <?php foreach ($types as $typeKey => $typeConfig): ?>
                <a class="download-tab <?= $type === $typeKey ? 'active' : '' ?>"
                   href="<?= e(url_with_query('/downloads', ['type' => $typeKey])) ?>"
                   role="tab" aria-selected="<?= $type === $typeKey ? 'true' : 'false' ?>">
                    <span class="download-tab-icon">
                        <i class="bi <?= $typeKey === 'team' ? 'bi-people' : 'bi-person' ?>" aria-hidden="true"></i>
                    </span>
                    <span>
                        <strong><?= e($typeConfig['label']) ?></strong>
                        <small><?= e((string) $counts[$typeKey]) ?> 份文件</small>
                    </span>
                </a>
            <?php endforeach; ?>
        </div>

        <div class="download-heading">
            <div>
                <div class="eyebrow"><?= $type === 'team' ? 'TEAM WORK' : 'PERSONAL WORK' ?></div>
                <h2 class="h3 mb-1"><?= e($activeType['label']) ?></h2>
                <p class="text-secondary mb-0"><?= e($activeType['description']) ?></p>
            </div>
            <span class="badge text-bg-light">
                <i class="bi bi-folder2-open me-1" aria-hidden="true"></i><?= e($activeType['directory']) ?>
            </span>
        </div>

        <?php if ($files === []): ?>
            <div class="empty-state">
                <i class="bi bi-folder2-open display-5 d-block mb-3" aria-hidden="true"></i>
                当前分类暂无公开文件。
            </div>
        <?php else: ?>
            <div class="file-list">
                <?php foreach ($files as $file): ?>
                    <article class="file-row">
                        <span class="file-icon">
                            <i class="bi <?= e($fileIcon((string) $file['extension'])) ?>" aria-hidden="true"></i>
                        </span>
                        <div class="file-main">
                            <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                                <h3 class="h5 mb-0"><?= e($file['title']) ?></h3>
                                <span class="badge text-bg-light text-uppercase"><?= e($file['extension']) ?></span>
                            </div>
                            <?php if (!empty($file['description'])): ?>
                                <p class="text-secondary small mb-2"><?= e($file['description']) ?></p>
                            <?php endif; ?>
                            <div class="file-meta">
                                <span><i class="bi bi-hdd" aria-hidden="true"></i> <?= e(format_file_size((int) $file['size_bytes'])) ?></span>
                                <span><i class="bi bi-calendar3" aria-hidden="true"></i> <?= e(format_datetime($file['created_at'], 'Y-m-d')) ?></span>
                                <span><i class="bi bi-download" aria-hidden="true"></i> <?= e((string) $file['download_count']) ?> 次下载</span>
                                <span class="text-truncate" title="<?= e($file['original_name']) ?>">
                                    <i class="bi bi-file-earmark" aria-hidden="true"></i> <?= e($file['original_name']) ?>
                                </span>
                            </div>
                        </div>
                        <div class="file-action">
                            <a class="btn btn-primary" href="<?= e(url('/downloads/' . $file['id'] . '/file')) ?>"
                               aria-label="下载 <?= e($file['title']) ?>">
                                <i class="bi bi-download me-1" aria-hidden="true"></i>下载
                            </a>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>

            <?php require BASE_PATH . '/app/Views/partials/pagination.php'; ?>
        <?php endif; ?>

        <div class="security-note mt-4">
            <i class="bi bi-shield-lock" aria-hidden="true"></i>
            <div>
                <strong>下载安全说明</strong>
                <p class="mb-0 small">
                    文件实体只允许位于 <code>data/team/</code> 或 <code>data/personal/</code>。
                    下载会校验数据库公开状态、文件存在性、真实路径和目录范围，拒绝目录遍历。
                </p>
            </div>
        </div>
    </div>
</section>
