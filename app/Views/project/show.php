<?php
/**
 * TechHub - 高校技术团队展示与管理平台
 *
 * 文件：app/Views/project/show.php
 * 模块：项目详情
 * 职责：展示项目封面、介绍、技术栈、成员、状态、成果和外部链接。
 * 作者：待团队填写
 * 创建日期：2026-11-25
 * 最后修改：2026-11-25
 */

declare(strict_types=1);

$coverUrl = media_url($project['cover_path'] ?? null);
$techTags = split_tags($project['tech_stack'] ?? null);
$repoUrl = safe_external_url($project['repo_url'] ?? null);
$demoUrl = safe_external_url($project['demo_url'] ?? null);
$statusMap = [
    'planning' => ['筹备中', 'text-bg-secondary'],
    'ongoing' => ['进行中', 'text-bg-primary'],
    'completed' => ['已完成', 'text-bg-success'],
    'archived' => ['已归档', 'text-bg-dark'],
];
$statusInfo = $statusMap[$project['status']] ?? ['项目', 'text-bg-secondary'];
?>
<section class="detail-cover-section">
    <div class="container">
        <a class="back-link" href="<?= e(url('/projects')) ?>">
            <i class="bi bi-arrow-left" aria-hidden="true"></i> 返回项目列表
        </a>
        <div class="detail-cover">
            <?php if ($coverUrl !== null): ?>
                <img src="<?= e($coverUrl) ?>" alt="<?= e($project['title']) ?>项目封面">
            <?php else: ?>
                <span class="cover-fallback cover-fallback-large"><i class="bi bi-kanban" aria-hidden="true"></i></span>
            <?php endif; ?>
            <div class="detail-cover-overlay">
                <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
                    <span class="badge <?= e($statusInfo[1]) ?>"><?= e($statusInfo[0]) ?></span>
                    <span class="text-white-50 small"><?= e($project['team_name']) ?></span>
                </div>
                <h1><?= e($project['title']) ?></h1>
                <p><?= e($project['summary']) ?></p>
            </div>
        </div>
    </div>
</section>

<section class="section-block">
    <div class="container">
        <div class="row g-4">
            <div class="col-lg-8">
                <article class="content-card">
                    <div class="eyebrow">OVERVIEW</div>
                    <h2 class="h4 mt-2 mb-3">项目介绍</h2>
                    <div class="prose-block">
                        <?= \App\Support\Markdown::render((string) $project['description']) ?>
                    </div>

                    <?php if (!empty($project['outcomes'])): ?>
                        <hr class="section-divider">
                        <div class="eyebrow">OUTCOMES</div>
                        <h2 class="h4 mt-2 mb-3">项目成果</h2>
                        <div class="outcome-card">
                            <i class="bi bi-award" aria-hidden="true"></i>
                            <div class="prose-block mb-0">
                                <?= \App\Support\Markdown::render((string) $project['outcomes']) ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if ($repoUrl !== null || $demoUrl !== null): ?>
                        <hr class="section-divider">
                        <div class="d-flex flex-wrap gap-2">
                            <?php if ($repoUrl !== null): ?>
                                <a class="btn btn-dark" href="<?= e($repoUrl) ?>" target="_blank" rel="noopener noreferrer">
                                    <i class="bi bi-github me-1" aria-hidden="true"></i>查看代码仓库
                                </a>
                            <?php endif; ?>
                            <?php if ($demoUrl !== null): ?>
                                <a class="btn btn-outline-primary" href="<?= e($demoUrl) ?>" target="_blank" rel="noopener noreferrer">
                                    <i class="bi bi-box-arrow-up-right me-1" aria-hidden="true"></i>访问演示地址
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </article>
            </div>

            <div class="col-lg-4">
                <aside class="content-card project-side-card mb-4">
                    <h2 class="h5 mb-3">项目信息</h2>
                    <dl class="detail-list">
                        <div><dt>项目状态</dt><dd><span class="badge <?= e($statusInfo[1]) ?>"><?= e($statusInfo[0]) ?></span></dd></div>
                        <div><dt>开始日期</dt><dd><?= e(format_datetime($project['start_date'] ?? null, 'Y-m-d')) ?></dd></div>
                        <div><dt>结束日期</dt><dd><?= e($project['end_date'] ? format_datetime($project['end_date'], 'Y-m-d') : '持续进行中') ?></dd></div>
                        <div><dt>所属团队</dt><dd><?= e($project['team_name']) ?></dd></div>
                    </dl>
                    <?php if ($techTags !== []): ?>
                        <hr class="section-divider">
                        <h3 class="h6 mb-2">使用技术</h3>
                        <div class="tag-cloud tag-cloud-sm">
                            <?php foreach ($techTags as $tag): ?>
                                <span class="tag-pill tag-pill-alt"><?= e($tag) ?></span>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </aside>

                <aside class="content-card">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h2 class="h5 mb-0">项目成员</h2>
                        <span class="small text-secondary"><?= e((string) count($members)) ?> 人</span>
                    </div>
                    <?php if ($members === []): ?>
                        <div class="empty-state empty-state-sm">暂无公开项目成员。</div>
                    <?php else: ?>
                        <div class="member-mini-list">
                            <?php foreach ($members as $member): ?>
                                <?php
                                $avatarUrl = media_url($member['avatar_path'] ?? null);
                                $avatarName = (string) $member['display_name'];
                                $avatarClass = 'avatar-sm';
                                ?>
                                <a class="member-mini-item" href="<?= e(url('/members/' . $member['membership_id'])) ?>">
                                    <?php require BASE_PATH . '/app/Views/partials/avatar.php'; ?>
                                    <span class="flex-grow-1">
                                        <strong><?= e($member['display_name']) ?></strong>
                                        <small><?= e($member['role_in_project'] ?? $member['position_title'] ?? '项目成员') ?></small>
                                    </span>
                                    <i class="bi bi-chevron-right" aria-hidden="true"></i>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </aside>
            </div>
        </div>
    </div>
</section>
