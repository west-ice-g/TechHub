<?php
/**
 * TechHub - 高校技术团队展示与管理平台
 *
 * 文件：app/Views/team/index.php
 * 模块：团队介绍
 * 职责：展示团队介绍、成立时间、研究方向、技术栈和联系方式。
 * 作者：待团队填写
 * 创建日期：2026-11-25
 * 最后修改：2026-11-25
 */

declare(strict_types=1);

$researchTags = split_tags($team['research_areas'] ?? null);
$techTags = split_tags($team['tech_stack'] ?? null);
$github = safe_external_url($team['github_url'] ?? null);
$website = safe_external_url($team['website_url'] ?? null);
$logoUrl = media_url($team['logo_path'] ?? null);
?>
<section class="page-hero">
    <div class="container">
        <div class="page-hero-grid">
            <div>
                <nav class="mini-breadcrumb" aria-label="面包屑">
                    <a href="<?= e(url('/')) ?>">首页</a>
                    <i class="bi bi-chevron-right" aria-hidden="true"></i>
                    <span>团队介绍</span>
                </nav>
                <div class="eyebrow mt-4">ABOUT THE TEAM</div>
                <h1><?= e($team['name'] ?? '团队介绍待完善') ?></h1>
                <p class="page-hero-lead">
                    <?= e($team['slogan'] ?? '用技术连接想法，把创意做成可以运行的作品。') ?>
                </p>
            </div>
            <div class="page-hero-side">
                <?php if ($logoUrl !== null): ?>
                    <img class="team-logo-large" src="<?= e($logoUrl) ?>" alt="<?= e($team['name'] ?? '团队') ?> Logo" width="160" height="160">
                <?php endif; ?>
                <div class="hero-side-stat">
                    <strong><?= e((string) $memberCount) ?></strong>
                    <span>团队成员</span>
                </div>
            </div>
        </div>
    </div>
</section>

<?php if ($team === null): ?>
    <section class="section-block">
        <div class="container">
            <div class="empty-state">
                <i class="bi bi-people display-5 d-block mb-3" aria-hidden="true"></i>
                团队资料尚未初始化。
            </div>
        </div>
    </section>
<?php else: ?>
    <section class="section-block">
        <div class="container">
            <div class="stat-strip mb-5">
                <div class="stat-strip-item">
                    <i class="bi bi-calendar3" aria-hidden="true"></i>
                    <div><strong><?= e(format_datetime($team['founded_at'] ?? null, 'Y 年 m 月')) ?></strong><span>成立时间</span></div>
                </div>
                <div class="stat-strip-item">
                    <i class="bi bi-people" aria-hidden="true"></i>
                    <div><strong><?= e((string) $memberCount) ?></strong><span>团队成员</span></div>
                </div>
                <div class="stat-strip-item">
                    <i class="bi bi-kanban" aria-hidden="true"></i>
                    <div><strong><?= e((string) $projectCount) ?></strong><span>公开项目</span></div>
                </div>
                <div class="stat-strip-item">
                    <i class="bi bi-journal-text" aria-hidden="true"></i>
                    <div><strong><?= e((string) $articleCount) ?></strong><span>技术文章</span></div>
                </div>
            </div>

            <div class="row g-4">
                <div class="col-lg-8">
                    <article class="content-card about-card h-100">
                        <div class="eyebrow">WHO WE ARE</div>
                        <h2 class="h3 mt-2">团队介绍</h2>
                        <div class="prose-block">
                            <?= \App\Support\Markdown::render((string) ($team['description'] ?? '团队介绍待补充。')) ?>
                        </div>

                        <hr class="section-divider">

                        <div class="row g-4">
                            <div class="col-md-6">
                                <div class="info-heading">
                                    <i class="bi bi-compass" aria-hidden="true"></i>
                                    <h3>研究方向</h3>
                                </div>
                                <div class="tag-cloud">
                                    <?php if ($researchTags === []): ?>
                                        <span class="text-secondary small">待补充</span>
                                    <?php else: ?>
                                        <?php foreach ($researchTags as $tag): ?>
                                            <span class="tag-pill"><?= e($tag) ?></span>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="info-heading">
                                    <i class="bi bi-braces" aria-hidden="true"></i>
                                    <h3>团队技术栈</h3>
                                </div>
                                <div class="tag-cloud">
                                    <?php if ($techTags === []): ?>
                                        <span class="text-secondary small">待补充</span>
                                    <?php else: ?>
                                        <?php foreach ($techTags as $tag): ?>
                                            <span class="tag-pill tag-pill-alt"><?= e($tag) ?></span>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </article>
                </div>

                <div class="col-lg-4">
                    <aside class="content-card contact-card h-100">
                        <div class="eyebrow">CONTACT</div>
                        <h2 class="h4 mt-2 mb-3">联系我们</h2>
                        <ul class="contact-list">
                            <li>
                                <span class="contact-icon"><i class="bi bi-geo-alt" aria-hidden="true"></i></span>
                                <div><small>所在地</small><strong><?= e($team['location'] ?? '待补充') ?></strong></div>
                            </li>
                            <?php if (!empty($team['email'])): ?>
                                <li>
                                    <span class="contact-icon"><i class="bi bi-envelope" aria-hidden="true"></i></span>
                                    <div><small>团队邮箱</small><a href="mailto:<?= e($team['email']) ?>"><?= e($team['email']) ?></a></div>
                                </li>
                            <?php endif; ?>
                            <?php if ($github !== null): ?>
                                <li>
                                    <span class="contact-icon"><i class="bi bi-github" aria-hidden="true"></i></span>
                                    <div><small>代码仓库</small><a href="<?= e($github) ?>" target="_blank" rel="noopener noreferrer">访问 GitHub</a></div>
                                </li>
                            <?php endif; ?>
                            <?php if ($website !== null): ?>
                                <li>
                                    <span class="contact-icon"><i class="bi bi-globe2" aria-hidden="true"></i></span>
                                    <div><small>团队网站</small><a href="<?= e($website) ?>" target="_blank" rel="noopener noreferrer">访问网站</a></div>
                                </li>
                            <?php endif; ?>
                        </ul>

                        <div class="d-grid gap-2 mt-4">
                            <a class="btn btn-primary" href="<?= e(url('/members')) ?>">认识团队成员</a>
                            <a class="btn btn-outline-secondary" href="<?= e(url('/projects')) ?>">查看项目成果</a>
                        </div>
                    </aside>
                </div>
            </div>
        </div>
    </section>
<?php endif; ?>
