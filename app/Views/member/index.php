<?php
/**
 * TechHub - 高校技术团队展示与管理平台
 *
 * 文件：app/Views/member/index.php
 * 模块：团队成员列表
 * 职责：显示可搜索、分页的公开成员卡片。
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
                    <span>团队成员</span>
                </nav>
                <div class="eyebrow mt-4">PEOPLE</div>
                <h1>团队成员</h1>
                <p class="page-hero-lead">
                    <?= e($team['name'] ?? 'TechHub') ?> 的成员、方向与协作关系。
                </p>
            </div>
            <div class="page-hero-side">
                <span class="page-hero-number"><?= e((string) $pagination['total']) ?></span>
                <span class="page-hero-label">位公开成员</span>
            </div>
        </div>
    </div>
</section>

<section class="section-block">
    <div class="container">
        <div class="toolbar-card mb-4">
            <form method="get" action="<?= e(url('/members')) ?>" class="toolbar-search">
                <label class="visually-hidden" for="member-search">搜索成员</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-search" aria-hidden="true"></i></span>
                    <input class="form-control" id="member-search" name="q" type="search"
                           value="<?= e($q) ?>" maxlength="80" placeholder="搜索姓名、职位或技术方向">
                    <button class="btn btn-primary" type="submit">搜索</button>
                </div>
            </form>
            <?php if ($q !== ''): ?>
                <a class="btn btn-outline-secondary btn-sm" href="<?= e(url('/members')) ?>">清除搜索</a>
            <?php endif; ?>
        </div>

        <?php if ($members === []): ?>
            <div class="empty-state">
                <i class="bi bi-person-x display-5 d-block mb-3" aria-hidden="true"></i>
                <?= $q === '' ? '暂无公开成员资料。' : '没有找到匹配的成员，请尝试其他关键词。' ?>
            </div>
        <?php else: ?>
            <div class="row g-4">
                <?php foreach ($members as $member): ?>
                    <?php
                    $avatarUrl = media_url($member['avatar_path'] ?? null);
                    $avatarName = (string) $member['display_name'];
                    $avatarClass = 'avatar-lg';
                    $skills = split_tags($member['skills'] ?? null);
                    ?>
                    <div class="col-md-6 col-xl-4">
                        <article class="member-card member-card-lg h-100">
                            <?php require BASE_PATH . '/app/Views/partials/avatar.php'; ?>
                            <div class="member-card-body">
                                <div class="d-flex justify-content-between align-items-start gap-2">
                                    <div>
                                        <h2 class="h5 mb-1"><?= e($member['display_name']) ?></h2>
                                        <div class="text-primary small"><?= e($member['position_title'] ?? '团队成员') ?></div>
                                    </div>
                                    <span class="badge <?= $member['member_status'] === 'active' ? 'text-bg-success' : 'text-bg-secondary' ?>">
                                        <?= $member['member_status'] === 'active' ? '在队' : '已离队' ?>
                                    </span>
                                </div>
                                <?php if (!empty($member['bio'])): ?>
                                    <p class="text-secondary small mt-3 mb-3"><?= e(excerpt_text($member['bio'], 92)) ?></p>
                                <?php endif; ?>
                                <?php if ($skills !== []): ?>
                                    <div class="tag-cloud tag-cloud-sm">
                                        <?php foreach (array_slice($skills, 0, 4) as $skill): ?>
                                            <span class="tag-pill"><?= e($skill) ?></span>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                                <div class="mt-3">
                                    <a class="text-link" href="<?= e(url('/members/' . $member['membership_id'])) ?>">
                                        查看成员详情 <i class="bi bi-arrow-right" aria-hidden="true"></i>
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
