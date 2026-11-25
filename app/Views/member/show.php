<?php
/**
 * TechHub - 高校技术团队展示与管理平台
 *
 * 文件：app/Views/member/show.php
 * 模块：团队成员详情
 * 职责：展示成员头像、职位、介绍、方向、联系方式和参与项目。
 * 作者：待团队填写
 * 创建日期：2026-11-25
 * 最后修改：2026-11-25
 */

declare(strict_types=1);

$avatarUrl = media_url($member['avatar_path'] ?? null);
$avatarName = (string) $member['display_name'];
$avatarClass = 'avatar-xl';
$skills = split_tags($member['skills'] ?? null);
$github = safe_external_url($member['github_url'] ?? null);
$statusMap = [
    'active' => ['在队成员', 'text-bg-success'],
    'alumni' => ['已离队', 'text-bg-secondary'],
    'removed' => ['已移除', 'text-bg-dark'],
];
$memberStatus = $statusMap[$member['member_status']] ?? ['团队成员', 'text-bg-light'];
$projectStatusMap = [
    'planning' => ['筹备中', 'text-bg-secondary'],
    'ongoing' => ['进行中', 'text-bg-primary'],
    'completed' => ['已完成', 'text-bg-success'],
    'archived' => ['已归档', 'text-bg-dark'],
];
?>
<section class="profile-hero">
    <div class="container">
        <a class="back-link" href="<?= e(url('/members')) ?>">
            <i class="bi bi-arrow-left" aria-hidden="true"></i> 返回成员列表
        </a>
        <div class="profile-hero-main">
            <?php require BASE_PATH . '/app/Views/partials/avatar.php'; ?>
            <div class="flex-grow-1">
                <div class="d-flex flex-wrap gap-2 align-items-center mb-2">
                    <span class="badge <?= e($memberStatus[1]) ?>"><?= e($memberStatus[0]) ?></span>
                    <span class="text-secondary small"><?= e($member['team_name']) ?></span>
                </div>
                <h1><?= e($member['display_name']) ?></h1>
                <p class="profile-role mb-2"><?= e($member['position_title'] ?? '团队成员') ?></p>
                <?php if (!empty($member['responsibilities'])): ?>
                    <p class="text-secondary mb-0"><?= e($member['responsibilities']) ?></p>
                <?php endif; ?>
            </div>
            <div class="profile-meta">
                <div><small>加入团队</small><strong><?= e(format_datetime($member['joined_at'] ?? null, 'Y-m-d')) ?></strong></div>
                <?php if (!empty($member['public_email'])): ?>
                    <div><small>公开邮箱</small><a href="mailto:<?= e($member['public_email']) ?>"><?= e($member['public_email']) ?></a></div>
                <?php endif; ?>
                <?php if ($github !== null): ?>
                    <div><small>GitHub</small><a href="<?= e($github) ?>" target="_blank" rel="noopener noreferrer">访问主页</a></div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<section class="section-block">
    <div class="container">
        <div class="row g-4">
            <div class="col-lg-7">
                <article class="content-card h-100">
                    <div class="eyebrow">ABOUT</div>
                    <h2 class="h4 mt-2 mb-3">个人介绍</h2>
                    <div class="prose-block">
                        <?= \App\Support\Markdown::render((string) ($member['bio'] ?? '这位成员还没有填写个人介绍。')) ?>
                    </div>
                </article>
            </div>
            <div class="col-lg-5">
                <article class="content-card h-100">
                    <div class="eyebrow">FOCUS</div>
                    <h2 class="h4 mt-2 mb-3">研究方向</h2>
                    <div class="tag-cloud">
                        <?php if ($skills === []): ?>
                            <span class="text-secondary small">研究方向待补充。</span>
                        <?php else: ?>
                            <?php foreach ($skills as $skill): ?>
                                <span class="tag-pill"><?= e($skill) ?></span>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    <hr class="section-divider">
                    <div class="small text-secondary">
                        成员公开资料由本人或管理员维护，联系方式仅展示主动公开的信息。
                    </div>
                </article>
            </div>
        </div>

        <div class="section-heading mt-5">
            <div>
                <div class="eyebrow">PROJECTS</div>
                <h2>参与项目</h2>
            </div>
            <a class="text-link" href="<?= e(url('/projects')) ?>">全部项目 <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
        </div>

        <?php if ($projects === []): ?>
            <div class="empty-state">
                <i class="bi bi-kanban display-5 d-block mb-3" aria-hidden="true"></i>
                暂无可公开展示的参与项目。
            </div>
        <?php else: ?>
            <div class="row g-4">
                <?php foreach ($projects as $project): ?>
                    <?php
                    $coverUrl = media_url($project['cover_path'] ?? null);
                    $statusInfo = $projectStatusMap[$project['status']] ?? ['项目', 'text-bg-secondary'];
                    ?>
                    <div class="col-md-6">
                        <article class="project-card project-card-compact h-100">
                            <a class="project-cover project-cover-sm" href="<?= e(url('/projects/' . $project['slug'])) ?>">
                                <?php if ($coverUrl !== null): ?>
                                    <img src="<?= e($coverUrl) ?>" alt="<?= e($project['title']) ?>封面" loading="lazy">
                                <?php else: ?>
                                    <span class="cover-fallback"><i class="bi bi-kanban" aria-hidden="true"></i></span>
                                <?php endif; ?>
                            </a>
                            <div class="project-card-body">
                                <div class="d-flex gap-2 align-items-center mb-2">
                                    <span class="badge <?= e($statusInfo[1]) ?>"><?= e($statusInfo[0]) ?></span>
                                    <?php if (!empty($project['role_in_project'])): ?>
                                        <span class="text-secondary small"><?= e($project['role_in_project']) ?></span>
                                    <?php endif; ?>
                                </div>
                                <h3 class="h5"><a href="<?= e(url('/projects/' . $project['slug'])) ?>"><?= e($project['title']) ?></a></h3>
                                <p class="text-secondary small mb-2"><?= e(excerpt_text($project['summary'], 94)) ?></p>
                                <a class="text-link small" href="<?= e(url('/projects/' . $project['slug'])) ?>">查看项目 <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
                            </div>
                        </article>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>
