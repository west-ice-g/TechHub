<?php
/**
 * TechHub - 高校技术团队展示与管理平台
 *
 * 文件：app/Views/home/index.php
 * 模块：首页
 * 职责：使用数据库查询结果展示团队、核心数据、项目、成员、文章、公告和下载入口。
 * 作者：待团队填写
 * 创建日期：2026-11-25
 * 最后修改：2026-11-25
 */

declare(strict_types=1);

$projectStatusMap = [
    'planning' => ['筹备中', 'text-bg-secondary'],
    'ongoing' => ['进行中', 'text-bg-primary'],
    'completed' => ['已完成', 'text-bg-success'],
    'archived' => ['已归档', 'text-bg-dark'],
];
$announcementTypeLabels = [
    'general' => '通知',
    'event' => '活动',
    'recruitment' => '招募',
    'system' => '系统',
];
$researchTags = split_tags($team['research_areas'] ?? null);
$teamLogo = media_url($team['logo_path'] ?? null);
$teamGithub = safe_external_url($team['github_url'] ?? null);
?>
<section class="hero-section">
    <div class="container py-5 py-lg-6">
        <div class="row align-items-center g-5">
            <div class="col-lg-7">
                <div class="eyebrow">UNIVERSITY TECH TEAM</div>
                <h1 class="display-4 fw-bold mt-3 mb-3">
                    <?= e($team['name'] ?? 'TechHub 示例技术团队') ?>
                </h1>
                <p class="hero-slogan mb-4">
                    <?= e($team['slogan'] ?? '把想法做成可以运行的作品') ?>
                </p>
                <p class="hero-copy">
                    <?= e(excerpt_text((string) ($team['description'] ?? '团队介绍待补充。'), 150)) ?>
                </p>
                <div class="d-flex flex-wrap gap-2 mt-4">
                    <a class="btn btn-light btn-lg" href="<?= e(url('/team')) ?>">
                        了解团队 <i class="bi bi-arrow-right ms-1" aria-hidden="true"></i>
                    </a>
                    <a class="btn btn-outline-light btn-lg" href="<?= e(url('/projects')) ?>">浏览项目</a>
                </div>
                <div class="hero-meta mt-4">
                    <?php if (!empty($team['location'])): ?>
                        <span><i class="bi bi-geo-alt me-1" aria-hidden="true"></i><?= e($team['location']) ?></span>
                    <?php endif; ?>
                    <?php if (!empty($team['email'])): ?>
                        <span><i class="bi bi-envelope me-1" aria-hidden="true"></i><?= e($team['email']) ?></span>
                    <?php endif; ?>
                    <?php if ($teamGithub !== null): ?>
                        <span><i class="bi bi-github me-1" aria-hidden="true"></i>开源仓库</span>
                    <?php endif; ?>
                </div>
                <?php if ($researchTags !== []): ?>
                    <div class="hero-tags">
                        <?php foreach (array_slice($researchTags, 0, 4) as $tag): ?>
                            <span><?= e($tag) ?></span>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="col-lg-5">
                <div class="hero-panel">
                    <div class="hero-panel-head">
                        <div class="d-flex align-items-center gap-3">
                            <?php if ($teamLogo !== null): ?>
                                <img class="hero-team-logo" src="<?= e($teamLogo) ?>" alt="<?= e($team['name'] ?? '团队') ?> Logo" width="64" height="64">
                            <?php endif; ?>
                            <div>
                                <div class="eyebrow text-info">TEAM AT A GLANCE</div>
                                <h2 class="h6 mb-0 mt-1">团队核心数据</h2>
                            </div>
                        </div>
                        <span class="status-dot" title="数据库已连接"></span>
                    </div>

                    <div class="hero-stat-grid">
                        <div class="hero-stat">
                            <i class="bi bi-people" aria-hidden="true"></i>
                            <strong><?= e((string) $stats['members']) ?></strong>
                            <span>团队成员</span>
                        </div>
                        <div class="hero-stat">
                            <i class="bi bi-kanban" aria-hidden="true"></i>
                            <strong><?= e((string) $stats['projects']) ?></strong>
                            <span>公开项目</span>
                        </div>
                        <div class="hero-stat">
                            <i class="bi bi-journal-text" aria-hidden="true"></i>
                            <strong><?= e((string) $stats['articles']) ?></strong>
                            <span>技术文章</span>
                        </div>
                        <div class="hero-stat">
                            <i class="bi bi-folder2-open" aria-hidden="true"></i>
                            <strong><?= e((string) $stats['files']) ?></strong>
                            <span>公开文件</span>
                        </div>
                    </div>

                    <div class="hero-panel-links">
                        <a href="<?= e(url('/downloads?type=team')) ?>">
                            <i class="bi bi-people" aria-hidden="true"></i>团队作业
                            <span><?= e((string) ($stats['team_files'] ?? 0)) ?></span>
                        </a>
                        <a href="<?= e(url('/downloads?type=personal')) ?>">
                            <i class="bi bi-person" aria-hidden="true"></i>个人作业
                            <span><?= e((string) ($stats['personal_files'] ?? 0)) ?></span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<section id="projects" class="section-block">
    <div class="container">
        <div class="section-heading">
            <div>
                <div class="eyebrow">PROJECTS</div>
                <h2>优秀项目</h2>
            </div>
            <a class="text-link" href="<?= e(url('/projects')) ?>">全部项目 <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
        </div>

        <?php if ($projects === []): ?>
            <div class="empty-state"><i class="bi bi-kanban me-2" aria-hidden="true"></i>暂无公开项目。</div>
        <?php else: ?>
            <div class="row g-4">
                <?php foreach ($projects as $project): ?>
                    <?php
                    $coverUrl = media_url($project['cover_path'] ?? null);
                    $statusInfo = $projectStatusMap[$project['status']] ?? ['项目', 'text-bg-secondary'];
                    $techTags = split_tags($project['tech_stack'] ?? null);
                    ?>
                    <div class="col-md-6 col-xl-4">
                        <article class="project-card h-100">
                            <a class="project-cover" href="<?= e(url('/projects/' . $project['slug'])) ?>">
                                <?php if ($coverUrl !== null): ?>
                                    <img src="<?= e($coverUrl) ?>" alt="<?= e($project['title']) ?>封面" loading="lazy">
                                <?php else: ?>
                                    <span class="cover-fallback"><i class="bi bi-kanban" aria-hidden="true"></i></span>
                                <?php endif; ?>
                                <span class="badge project-status <?= e($statusInfo[1]) ?>"><?= e($statusInfo[0]) ?></span>
                            </a>
                            <div class="project-card-body">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="mini-label">PROJECT</span>
                                    <?php if ((int) $project['is_featured'] === 1): ?>
                                        <span class="mini-label text-warning-emphasis"><i class="bi bi-star-fill" aria-hidden="true"></i> 推荐</span>
                                    <?php endif; ?>
                                </div>
                                <h3 class="h5"><a href="<?= e(url('/projects/' . $project['slug'])) ?>"><?= e($project['title']) ?></a></h3>
                                <p class="text-secondary small"><?= e(excerpt_text($project['summary'], 105)) ?></p>
                                <?php if ($techTags !== []): ?>
                                    <div class="tag-cloud tag-cloud-sm">
                                        <?php foreach (array_slice($techTags, 0, 3) as $tag): ?>
                                            <span class="tag-pill tag-pill-alt"><?= e($tag) ?></span>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </article>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<section id="members" class="section-block section-alt">
    <div class="container">
        <div class="section-heading">
            <div>
                <div class="eyebrow">PEOPLE</div>
                <h2>团队成员</h2>
            </div>
            <a class="text-link" href="<?= e(url('/members')) ?>">全部成员 <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
        </div>

        <?php if ($members === []): ?>
            <div class="empty-state"><i class="bi bi-person-x me-2" aria-hidden="true"></i>暂无公开成员资料。</div>
        <?php else: ?>
            <div class="row g-4">
                <?php foreach ($members as $member): ?>
                    <?php
                    $avatarUrl = media_url($member['avatar_path'] ?? null);
                    $avatarName = (string) $member['display_name'];
                    $avatarClass = 'avatar-lg';
                    ?>
                    <div class="col-md-6 col-xl-3">
                        <article class="member-card member-card-home h-100">
                            <a href="<?= e(url('/members/' . $member['membership_id'])) ?>">
                                <?php require BASE_PATH . '/app/Views/partials/avatar.php'; ?>
                            </a>
                            <div class="member-card-body">
                                <div class="d-flex justify-content-between align-items-start gap-2">
                                    <div>
                                        <h3 class="h5 mb-1">
                                            <a href="<?= e(url('/members/' . $member['membership_id'])) ?>"><?= e($member['display_name']) ?></a>
                                        </h3>
                                        <div class="small text-primary"><?= e($member['position_title'] ?? '团队成员') ?></div>
                                    </div>
                                    <span class="badge <?= $member['member_status'] === 'active' ? 'text-bg-success' : 'text-bg-secondary' ?>">
                                        <?= $member['member_status'] === 'active' ? '在队' : '已离队' ?>
                                    </span>
                                </div>
                                <?php if (!empty($member['skills'])): ?>
                                    <div class="skills-line mt-2"><?= e($member['skills']) ?></div>
                                <?php endif; ?>
                                <a class="text-link small mt-3 d-inline-block" href="<?= e(url('/members/' . $member['membership_id'])) ?>">
                                    查看资料 <i class="bi bi-arrow-right" aria-hidden="true"></i>
                                </a>
                            </div>
                        </article>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<section id="articles" class="section-block">
    <div class="container">
        <div class="section-heading">
            <div>
                <div class="eyebrow">KNOWLEDGE</div>
                <h2>最新技术文章</h2>
            </div>
            <a class="text-link" href="<?= e(url('/articles')) ?>">全部文章 <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
        </div>

        <?php if ($articles === []): ?>
            <div class="empty-state"><i class="bi bi-journal-x me-2" aria-hidden="true"></i>暂无已发布文章。</div>
        <?php else: ?>
            <div class="row g-4">
                <?php foreach ($articles as $article): ?>
                    <?php $coverUrl = media_url($article['cover_path'] ?? null); ?>
                    <div class="col-md-6 col-lg-4">
                        <article class="article-card h-100">
                            <a class="article-cover" href="<?= e(url('/articles/' . $article['slug'])) ?>">
                                <?php if ($coverUrl !== null): ?>
                                    <img src="<?= e($coverUrl) ?>" alt="<?= e($article['title']) ?>封面" loading="lazy">
                                <?php else: ?>
                                    <span class="cover-fallback"><i class="bi bi-journal-text" aria-hidden="true"></i></span>
                                <?php endif; ?>
                            </a>
                            <div class="article-card-body">
                                <div class="d-flex justify-content-between text-secondary small mb-2">
                                    <span class="category-label"><?= e($article['category_name']) ?></span>
                                    <span><?= e(format_datetime($article['published_at'], 'Y-m-d')) ?></span>
                                </div>
                                <h3 class="h5"><a href="<?= e(url('/articles/' . $article['slug'])) ?>"><?= e($article['title']) ?></a></h3>
                                <p class="text-secondary small"><?= e(excerpt_text($article['summary'], 92)) ?></p>
                                <div class="meta-line">
                                    <span><i class="bi bi-person" aria-hidden="true"></i> <?= e($article['author_name']) ?></span>
                                    <span><i class="bi bi-eye" aria-hidden="true"></i> <?= e((string) $article['view_count']) ?></span>
                                </div>
                            </div>
                        </article>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<section id="announcements" class="section-block section-alt">
    <div class="container">
        <div class="row g-5">
            <div class="col-lg-4">
                <div class="eyebrow">ANNOUNCEMENTS</div>
                <h2>团队公告</h2>
                <p class="text-secondary">
                    团队通知、活动安排与阶段性信息，按置顶和发布时间展示。
                </p>
                <a class="btn btn-outline-secondary" href="<?= e(url('/announcements')) ?>">查看全部公告</a>
            </div>
            <div class="col-lg-8">
                <?php if ($announcements === []): ?>
                    <div class="empty-state">暂无有效公告。</div>
                <?php else: ?>
                    <div class="announcement-stack">
                        <?php foreach ($announcements as $announcement): ?>
                            <article class="announcement-card <?= (int) $announcement['is_pinned'] === 1 ? 'pinned' : '' ?>">
                                <div class="announcement-marker">
                                    <i class="bi bi-megaphone" aria-hidden="true"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                                        <?php if ((int) $announcement['is_pinned'] === 1): ?>
                                            <span class="badge text-bg-primary"><i class="bi bi-pin-angle me-1" aria-hidden="true"></i>置顶</span>
                                        <?php endif; ?>
                                        <span class="badge text-bg-light"><?= e($announcementTypeLabels[$announcement['type']] ?? '公告') ?></span>
                                        <span class="small text-secondary"><?= e(format_datetime($announcement['published_at'], 'Y-m-d')) ?></span>
                                    </div>
                                    <h3 class="h5 mb-1">
                                        <a href="<?= e(url('/announcements/' . $announcement['id'])) ?>"><?= e($announcement['title']) ?></a>
                                    </h3>
                                    <?php if (!empty($announcement['summary'])): ?>
                                        <p class="small text-secondary mb-0"><?= e(excerpt_text($announcement['summary'], 120)) ?></p>
                                    <?php endif; ?>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<section class="section-block">
    <div class="container">
        <div class="download-cta">
            <div class="download-cta-copy">
                <div class="eyebrow">COURSE MATERIALS</div>
                <h2>团队作业与个人作业</h2>
                <p>课程资料按类型归档，点击进入专门下载页查看文件信息并下载。</p>
            </div>
            <div class="download-cta-actions">
                <a class="download-action-card" href="<?= e(url('/downloads?type=team')) ?>">
                    <span class="icon-tile"><i class="bi bi-people" aria-hidden="true"></i></span>
                    <span><strong>团队作业</strong><small><?= e((string) ($stats['team_files'] ?? 0)) ?> 份公开文件</small></span>
                    <i class="bi bi-arrow-right ms-auto" aria-hidden="true"></i>
                </a>
                <a class="download-action-card" href="<?= e(url('/downloads?type=personal')) ?>">
                    <span class="icon-tile"><i class="bi bi-person" aria-hidden="true"></i></span>
                    <span><strong>个人作业</strong><small><?= e((string) ($stats['personal_files'] ?? 0)) ?> 份公开文件</small></span>
                    <i class="bi bi-arrow-right ms-auto" aria-hidden="true"></i>
                </a>
            </div>
        </div>
    </div>
</section>
