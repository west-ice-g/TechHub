<?php
/**
 * TechHub - 高校技术团队展示与管理平台
 *
 * 文件：app/Views/profile/index.php
 * 模块：个人资料
 * 职责：展示账号信息、公开成员资料、团队关系和参与项目。
 * 作者：待团队填写
 * 创建日期：2026-11-25
 * 最后修改：2026-11-25
 */

declare(strict_types=1);

use Core\Auth;

$avatarUrl = media_url($profile['avatar_path'] ?? null);
$avatarName = (string) $profile['display_name'];
$avatarClass = 'avatar-xl';
$skills = split_tags($profile['skills'] ?? null);
$github = safe_external_url($profile['github_url'] ?? null);
$memberStatusMap = [
    'active' => ['在队成员', 'text-bg-success'],
    'alumni' => ['已离队', 'text-bg-secondary'],
    'removed' => ['已移除', 'text-bg-dark'],
];
$memberStatus = $memberStatusMap[$profile['member_status'] ?? ''] ?? ['未关联成员档案', 'text-bg-light'];
$projectStatusMap = [
    'planning' => ['筹备中', 'text-bg-secondary'],
    'ongoing' => ['进行中', 'text-bg-primary'],
    'completed' => ['已完成', 'text-bg-success'],
    'archived' => ['已归档', 'text-bg-dark'],
];
?>
<section class="profile-hero">
    <div class="container">
        <div class="profile-hero-main">
            <?php require BASE_PATH . '/app/Views/partials/avatar.php'; ?>
            <div class="flex-grow-1">
                <div class="eyebrow">MY PROFILE</div>
                <h1><?= e($profile['display_name']) ?></h1>
                <div class="d-flex flex-wrap align-items-center gap-2 mt-2">
                    <span class="badge text-bg-dark"><?= e($profile['role_name']) ?></span>
                    <span class="badge <?= e($memberStatus[1]) ?>"><?= e($memberStatus[0]) ?></span>
                    <?php if (!empty($profile['team_name'])): ?>
                        <span class="text-secondary small"><?= e($profile['team_name']) ?></span>
                    <?php endif; ?>
                </div>
            </div>
            <div class="profile-actions">
                <?php if (!empty($profile['membership_id'])): ?>
                    <a class="btn btn-outline-primary" href="<?= e(url('/members/' . $profile['membership_id'])) ?>">
                        <i class="bi bi-person-badge me-1" aria-hidden="true"></i>查看公开主页
                    </a>
                <?php endif; ?>
                <?php if (Auth::can('dashboard.view')): ?>
                    <a class="btn btn-primary" href="<?= e(url('/admin')) ?>">
                        <i class="bi bi-speedometer2 me-1" aria-hidden="true"></i>管理后台
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<section class="section-block">
    <div class="container">
        <div class="row g-4">
            <div class="col-lg-4">
                <article class="content-card h-100">
                    <div class="eyebrow">ACCOUNT</div>
                    <h2 class="h4 mt-2 mb-3">账号信息</h2>
                    <dl class="detail-list detail-list-stack">
                        <div><dt>用户名</dt><dd><code><?= e($profile['username']) ?></code></dd></div>
                        <div><dt>账号角色</dt><dd><?= e($profile['role_name']) ?></dd></div>
                        <div><dt>账号邮箱</dt><dd><?= e($profile['email'] ?? '未填写') ?></dd></div>
                        <div><dt>账号创建</dt><dd><?= e(format_datetime($profile['created_at'], 'Y-m-d')) ?></dd></div>
                        <div><dt>最近登录</dt><dd><?= e(format_datetime($profile['last_login_at'] ?? null)) ?></dd></div>
                    </dl>
                    <?php if (!empty($profile['team_name'])): ?>
                        <hr class="section-divider">
                        <h3 class="h6 mb-2">团队关系</h3>
                        <p class="mb-1"><strong><?= e($profile['team_name']) ?></strong></p>
                        <p class="small text-secondary mb-1"><?= e($profile['position_title'] ?? '团队成员') ?></p>
                        <p class="small text-secondary mb-0">
                            加入时间：<?= e(format_datetime($profile['joined_at'] ?? null, 'Y-m-d')) ?>
                        </p>
                    <?php endif; ?>
                </article>
            </div>

            <div class="col-lg-8">
                <article class="content-card h-100">
                    <div class="eyebrow">PUBLIC PROFILE</div>
                    <h2 class="h4 mt-2 mb-3">公开资料</h2>

                    <?php if (empty($profile['membership_id'])): ?>
                        <div class="empty-state empty-state-sm text-start">
                            当前账号尚未关联公开成员档案。公开资料由管理员在成员管理中维护。
                        </div>
                    <?php else: ?>
                        <div class="prose-block">
                            <?= \App\Support\Markdown::render((string) ($profile['bio'] ?? '尚未填写个人介绍。')) ?>
                        </div>
                        <hr class="section-divider">
                        <div class="row g-4">
                            <div class="col-md-7">
                                <h3 class="h6 mb-2">研究方向</h3>
                                <div class="tag-cloud">
                                    <?php if ($skills === []): ?>
                                        <span class="text-secondary small">待补充</span>
                                    <?php else: ?>
                                        <?php foreach ($skills as $skill): ?>
                                            <span class="tag-pill"><?= e($skill) ?></span>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="col-md-5">
                                <h3 class="h6 mb-2">公开联系方式</h3>
                                <ul class="contact-list contact-list-compact">
                                    <?php if (!empty($profile['public_email'])): ?>
                                        <li>
                                            <i class="bi bi-envelope" aria-hidden="true"></i>
                                            <a href="mailto:<?= e($profile['public_email']) ?>"><?= e($profile['public_email']) ?></a>
                                        </li>
                                    <?php endif; ?>
                                    <?php if ($github !== null): ?>
                                        <li>
                                            <i class="bi bi-github" aria-hidden="true"></i>
                                            <a href="<?= e($github) ?>" target="_blank" rel="noopener noreferrer">GitHub</a>
                                        </li>
                                    <?php endif; ?>
                                    <?php if (empty($profile['public_email']) && $github === null): ?>
                                        <li class="text-secondary small">未公开联系方式</li>
                                    <?php endif; ?>
                                </ul>
                            </div>
                        </div>
                    <?php endif; ?>
                </article>
            </div>
        </div>

        <?php if (!empty($profile['membership_id'])): ?>
            <div class="section-heading mt-5">
                <div>
                    <div class="eyebrow">MY PROJECTS</div>
                    <h2>参与项目</h2>
                </div>
            </div>

            <?php if ($projects === []): ?>
                <div class="empty-state">暂无公开展示的参与项目。</div>
            <?php else: ?>
                <div class="row g-4">
                    <?php foreach ($projects as $project): ?>
                        <?php
                        $coverUrl = media_url($project['cover_path'] ?? null);
                        $projectStatus = $projectStatusMap[$project['status']] ?? ['项目', 'text-bg-secondary'];
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
                                        <span class="badge <?= e($projectStatus[1]) ?>"><?= e($projectStatus[0]) ?></span>
                                        <?php if (!empty($project['role_in_project'])): ?>
                                            <span class="small text-secondary"><?= e($project['role_in_project']) ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <h3 class="h5"><a href="<?= e(url('/projects/' . $project['slug'])) ?>"><?= e($project['title']) ?></a></h3>
                                    <p class="text-secondary small mb-0"><?= e(excerpt_text($project['summary'], 90)) ?></p>
                                </div>
                            </article>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</section>
