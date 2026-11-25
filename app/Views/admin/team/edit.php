<?php
/**
 * TechHub - 高校技术团队展示与管理平台
 *
 * 文件：app/Views/admin/team/edit.php
 * 模块：团队管理
 * 职责：编辑团队资料、研究方向、技术栈、联系方式与展示状态。
 * 作者：待团队填写
 * 创建日期：2026-11-25
 * 最后修改：2026-11-25
 */

declare(strict_types=1);

$t = static function (string $key, mixed $default = '') use ($formOld, $team): mixed {
    return $formOld[$key] ?? $team[$key] ?? $default;
};
$logo = media_url((string) ($team['logo_path'] ?? ''));
$heading = '团队信息';
$description = '这里的字段直接驱动前台团队介绍页；下架后前台不再展示当前团队。';
$kicker = 'TEAM PROFILE';
$actions = '<a class="btn btn-sm btn-outline-secondary" href="' . e(url('/team')) . '" target="_blank" rel="noopener"><i class="bi bi-box-arrow-up-right me-1" aria-hidden="true"></i>查看前台团队页</a>';
include BASE_PATH . '/app/Views/admin/partials/page-header.php';
?>

<form method="post" action="<?= e(url('/admin/team')) ?>" enctype="multipart/form-data">
    <?= csrf_field() ?>
    <?php include BASE_PATH . '/app/Views/admin/partials/form-errors.php'; ?>

    <div class="row g-3">
        <div class="col-xl-8">
            <div class="admin-form-card">
                <div class="admin-form-section">
                    <h2>基础资料</h2>
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label" for="name">团队名称 <span class="text-danger">*</span></label>
                            <input class="form-control" id="name" name="name" type="text" maxlength="100"
                                   value="<?= e($t('name')) ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="short_name">简称</label>
                            <input class="form-control" id="short_name" name="short_name" type="text" maxlength="50"
                                   value="<?= e($t('short_name')) ?>">
                        </div>
                        <div class="col-md-8">
                            <label class="form-label" for="slug">URL 别名 <span class="text-danger">*</span></label>
                            <input class="form-control" id="slug" name="slug" type="text" maxlength="100"
                                   value="<?= e($t('slug')) ?>" pattern="[a-z0-9]+(?:-[a-z0-9]+)*" required>
                            <div class="form-text">仅小写字母、数字和连字符。</div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="founded_at">成立日期</label>
                            <input class="form-control" id="founded_at" name="founded_at" type="date"
                                   value="<?= e($t('founded_at')) ?>">
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="slogan">口号</label>
                            <input class="form-control" id="slogan" name="slogan" type="text" maxlength="200"
                                   value="<?= e($t('slogan')) ?>">
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="description">团队介绍</label>
                            <textarea class="form-control" id="description" name="description" rows="6"><?= e($t('description')) ?></textarea>
                        </div>
                    </div>
                </div>

                <div class="admin-form-section">
                    <h2>方向与技术</h2>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label" for="research_areas">研究方向</label>
                            <input class="form-control" id="research_areas" name="research_areas" type="text" maxlength="255"
                                   value="<?= e($t('research_areas')) ?>" placeholder="Web 应用开发、数据库系统">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="tech_stack">技术栈</label>
                            <input class="form-control" id="tech_stack" name="tech_stack" type="text" maxlength="255"
                                   value="<?= e($t('tech_stack')) ?>" placeholder="PHP 8.2、MySQL 8.0、Bootstrap 5">
                        </div>
                    </div>
                </div>

                <div class="admin-form-section">
                    <h2>联系方式</h2>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label" for="email">公开邮箱</label>
                            <input class="form-control" id="email" name="email" type="email" maxlength="120"
                                   value="<?= e($t('email')) ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="location">地点</label>
                            <input class="form-control" id="location" name="location" type="text" maxlength="120"
                                   value="<?= e($t('location')) ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="github_url">GitHub 地址</label>
                            <input class="form-control" id="github_url" name="github_url" type="url" maxlength="255"
                                   value="<?= e($t('github_url')) ?>" placeholder="https://github.com/...">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="website_url">网站地址</label>
                            <input class="form-control" id="website_url" name="website_url" type="url" maxlength="255"
                                   value="<?= e($t('website_url')) ?>" placeholder="https://...">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-4">
            <div class="admin-form-card mb-3">
                <div class="admin-form-section">
                    <h2>展示控制</h2>
                    <label class="form-label" for="status">团队状态</label>
                    <select class="form-select" id="status" name="status">
                        <option value="active" <?= $t('status') === 'active' ? 'selected' : '' ?>>展示中（active）</option>
                        <option value="archived" <?= $t('status') === 'archived' ? 'selected' : '' ?>>已下架（archived）</option>
                    </select>
                    <div class="form-text">下架后首页和团队页不再显示该团队，但后台数据保留。</div>
                </div>
            </div>

            <div class="admin-form-card">
                <div class="admin-form-section">
                    <h2>团队 Logo</h2>
                    <?php if ($logo !== null): ?>
                        <div class="mb-2"><img class="admin-cover-preview" src="<?= e($logo) ?>" alt="当前团队 Logo"></div>
                    <?php else: ?>
                        <div class="admin-help mb-2">尚未设置 Logo。</div>
                    <?php endif; ?>
                    <input class="form-control form-control-sm" id="logo" name="logo" type="file" accept="image/jpeg,image/png,image/webp,image/gif">
                    <div class="form-text">JPG/PNG/WebP/GIF，上传后保存到 public/uploads/team。</div>
                    <?php if ($logo !== null): ?>
                        <div class="form-check mt-2">
                            <input class="form-check-input" type="checkbox" id="remove_logo" name="remove_logo" value="1">
                            <label class="form-check-label small" for="remove_logo">移除当前 Logo</label>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex flex-wrap gap-2 mt-3">
        <button class="btn btn-primary" type="submit"><i class="bi bi-save me-1" aria-hidden="true"></i>保存团队资料</button>
        <a class="btn btn-outline-secondary" href="<?= e(url('/admin')) ?>">取消</a>
    </div>
</form>
