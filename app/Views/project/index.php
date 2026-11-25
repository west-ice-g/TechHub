<?php
/**
 * TechHub - 高校技术团队展示与管理平台
 *
 * 文件：app/Views/project/index.php
 * 模块：项目成果列表
 * 职责：展示可筛选、可搜索、分页的公开项目卡片。
 * 作者：待团队填写
 * 创建日期：2026-11-25
 * 最后修改：2026-11-25
 */

declare(strict_types=1);

$statusOptions = [
    '' => '全部项目',
    'planning' => '筹备中',
    'ongoing' => '进行中',
    'completed' => '已完成',
    'archived' => '已归档',
];
$statusMap = [
    'planning' => ['筹备中', 'text-bg-secondary'],
    'ongoing' => ['进行中', 'text-bg-primary'],
    'completed' => ['已完成', 'text-bg-success'],
    'archived' => ['已归档', 'text-bg-dark'],
];
?>
<section class="page-hero">
    <div class="container">
        <div class="page-hero-grid">
            <div>
                <nav class="mini-breadcrumb" aria-label="面包屑">
                    <a href="<?= e(url('/')) ?>">首页</a>
                    <i class="bi bi-chevron-right" aria-hidden="true"></i>
                    <span>项目成果</span>
                </nav>
                <div class="eyebrow mt-4">PROJECTS</div>
                <h1>项目成果</h1>
                <p class="page-hero-lead">从想法到实现，记录团队真实做过的技术项目。</p>
            </div>
            <div class="page-hero-side">
                <span class="page-hero-number"><?= e((string) $pagination['total']) ?></span>
                <span class="page-hero-label">个公开项目</span>
            </div>
        </div>
    </div>
</section>

<section class="section-block">
    <div class="container">
        <div class="toolbar-card toolbar-card-wrap mb-4">
            <div class="filter-chips">
                <?php foreach ($statusOptions as $value => $label): ?>
                    <?php
                    $isActive = $status === $value;
                    $chipQuery = ['status' => $value, 'q' => $q];
                    ?>
                    <a class="filter-chip <?= $isActive ? 'active' : '' ?>"
                       href="<?= e(url_with_query('/projects', $chipQuery)) ?>">
                        <?= e($label) ?>
                    </a>
                <?php endforeach; ?>
            </div>
            <form method="get" action="<?= e(url('/projects')) ?>" class="toolbar-search">
                <?php if ($status !== ''): ?>
                    <input type="hidden" name="status" value="<?= e($status) ?>">
                <?php endif; ?>
                <label class="visually-hidden" for="project-search">搜索项目</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-search" aria-hidden="true"></i></span>
                    <input class="form-control" id="project-search" name="q" type="search"
                           value="<?= e($q) ?>" maxlength="80" placeholder="搜索项目名称或技术">
                    <button class="btn btn-primary" type="submit">搜索</button>
                </div>
            </form>
        </div>

        <?php if ($projects === []): ?>
            <div class="empty-state">
                <i class="bi bi-kanban display-5 d-block mb-3" aria-hidden="true"></i>
                <?= ($q !== '' || $status !== '') ? '没有找到符合条件的项目，请调整筛选条件。' : '暂无可展示的项目。' ?>
            </div>
        <?php else: ?>
            <div class="row g-4">
                <?php foreach ($projects as $project): ?>
                    <?php
                    $coverUrl = media_url($project['cover_path'] ?? null);
                    $tags = split_tags($project['tech_stack'] ?? null);
                    $statusInfo = $statusMap[$project['status']] ?? ['项目', 'text-bg-secondary'];
                    ?>
                    <div class="col-md-6 col-xl-4">
                        <article class="project-card h-100">
                            <a class="project-cover" href="<?= e(url('/projects/' . $project['slug'])) ?>" aria-label="查看项目 <?= e($project['title']) ?>">
                                <?php if ($coverUrl !== null): ?>
                                    <img src="<?= e($coverUrl) ?>" alt="<?= e($project['title']) ?>封面" loading="lazy">
                                <?php else: ?>
                                    <span class="cover-fallback"><i class="bi bi-kanban" aria-hidden="true"></i></span>
                                <?php endif; ?>
                                <span class="badge project-status <?= e($statusInfo[1]) ?>"><?= e($statusInfo[0]) ?></span>
                            </a>
                            <div class="project-card-body">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="mini-label"><?= e((string) $project['member_count']) ?> 位成员参与</span>
                                    <?php if ((int) $project['is_featured'] === 1): ?>
                                        <span class="mini-label text-warning-emphasis"><i class="bi bi-star-fill" aria-hidden="true"></i> 推荐</span>
                                    <?php endif; ?>
                                </div>
                                <h2 class="h5">
                                    <a href="<?= e(url('/projects/' . $project['slug'])) ?>"><?= e($project['title']) ?></a>
                                </h2>
                                <p class="text-secondary small"><?= e(excerpt_text($project['summary'], 110)) ?></p>
                                <?php if ($tags !== []): ?>
                                    <div class="tag-cloud tag-cloud-sm mt-3">
                                        <?php foreach (array_slice($tags, 0, 3) as $tag): ?>
                                            <span class="tag-pill tag-pill-alt"><?= e($tag) ?></span>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                                <div class="mt-3">
                                    <a class="text-link" href="<?= e(url('/projects/' . $project['slug'])) ?>">
                                        查看项目详情 <i class="bi bi-arrow-right" aria-hidden="true"></i>
                                    </a>
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
