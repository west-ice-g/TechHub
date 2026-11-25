<?php
/**
 * TechHub - 高校技术团队展示与管理平台
 *
 * 文件：app/Views/admin/articles/form.php
 * 模块：文章管理
 * 职责：新增或编辑文章，维护分类、封面和发布状态。
 * 作者：待团队填写
 * 创建日期：2026-11-25
 * 最后修改：2026-11-25
 */

declare(strict_types=1);

$a = static function (string $key, mixed $default = '') use ($formOld, $article): mixed {
    return $formOld[$key] ?? $article[$key] ?? $default;
};
$isFeatured = (int) ($formOld['is_featured'] ?? $article['is_featured'] ?? 0) === 1;
$categoryId = (int) $a('category_id', 0);
$cover = $editing ? media_url((string) ($article['cover_path'] ?? '')) : null;
$formAction = $editing ? url('/admin/articles/' . $article['id'] . '/edit') : url('/admin/articles');
$heading = $editing ? '编辑文章' : '新增文章';
$description = '前台正文使用受控 Markdown 渲染；草稿不会出现在公开文章列表。';
$kicker = 'ARTICLE EDITOR';
$actions = '<a class="btn btn-sm btn-outline-secondary" href="' . e(url('/admin/articles')) . '">返回文章列表</a>';
include BASE_PATH . '/app/Views/admin/partials/page-header.php';
?>

<form method="post" action="<?= e($formAction) ?>" enctype="multipart/form-data">
    <?= csrf_field() ?>
    <?php include BASE_PATH . '/app/Views/admin/partials/form-errors.php'; ?>

    <div class="row g-3">
        <div class="col-xl-9">
            <div class="admin-form-card">
                <div class="admin-form-section">
                    <h2>文章内容</h2>
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label" for="title">标题 <span class="text-danger">*</span></label>
                            <input class="form-control" id="title" name="title" type="text" maxlength="200"
                                   value="<?= e($a('title')) ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="slug">URL 别名 <span class="text-danger">*</span></label>
                            <input class="form-control" id="slug" name="slug" type="text" maxlength="220"
                                   value="<?= e($a('slug')) ?>" pattern="[a-z0-9]+(?:-[a-z0-9]+)*">
                            <div class="form-text">留空按标题生成。</div>
                        </div>
                        <div class="col-md-7">
                            <label class="form-label" for="category_id">分类 <span class="text-danger">*</span></label>
                            <select class="form-select" id="category_id" name="category_id" required>
                                <option value="0">请选择分类</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?= e((string) $category['id']) ?>" <?= $categoryId === (int) $category['id'] ? 'selected' : '' ?>>
                                        <?= e($category['name']) ?><?= $category['status'] === 'disabled' ? '（停用）' : '' ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-5">
                            <label class="form-label" for="status">状态</label>
                            <select class="form-select" id="status" name="status">
                                <?php foreach ($statusOptions as $key => $label): ?>
                                    <option value="<?= e($key) ?>" <?= $a('status', 'draft') === $key ? 'selected' : '' ?>><?= e($label) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="summary">摘要 <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="summary" name="summary" rows="2" maxlength="500" required><?= e($a('summary')) ?></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="content">正文 <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="content" name="content" rows="18" required><?= e($a('content')) ?></textarea>
                            <div class="form-text">支持标题、列表、引用、代码块、粗体、斜体和 http(s) 链接；原始 HTML 会被转义。</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3">
            <div class="admin-form-card mb-3">
                <div class="admin-form-section">
                    <h2>发布设置</h2>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="is_featured" name="is_featured" value="1" <?= $isFeatured ? 'checked' : '' ?>>
                        <label class="form-check-label" for="is_featured">首页推荐</label>
                    </div>
                    <?php if ($editing): ?>
                        <div class="admin-divider"></div>
                        <dl class="row small mb-0">
                            <dt class="col-5 text-secondary">作者</dt>
                            <dd class="col-7"><?= e($article['author_name']) ?></dd>
                            <dt class="col-5 text-secondary">浏览</dt>
                            <dd class="col-7"><?= e((string) $article['view_count']) ?></dd>
                            <dt class="col-5 text-secondary">发布时间</dt>
                            <dd class="col-7"><?= e(format_datetime($article['published_at'] ?? null)) ?></dd>
                        </dl>
                    <?php endif; ?>
                </div>
            </div>

            <div class="admin-form-card">
                <div class="admin-form-section">
                    <h2>文章封面</h2>
                    <?php if ($cover !== null): ?>
                        <img class="admin-cover-preview mb-2" src="<?= e($cover) ?>" alt="当前文章封面">
                    <?php else: ?>
                        <div class="admin-help mb-2">未设置封面。</div>
                    <?php endif; ?>
                    <input class="form-control form-control-sm" id="cover" name="cover" type="file" accept="image/jpeg,image/png,image/webp,image/gif">
                    <div class="form-text">JPG/PNG/WebP/GIF，最大 5MB。</div>
                    <?php if ($cover !== null): ?>
                        <div class="form-check mt-2">
                            <input class="form-check-input" type="checkbox" id="remove_cover" name="remove_cover" value="1">
                            <label class="form-check-label small" for="remove_cover">移除当前封面</label>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex flex-wrap gap-2 mt-3">
        <button class="btn btn-primary" type="submit"><i class="bi bi-save me-1" aria-hidden="true"></i><?= $editing ? '保存文章' : '创建文章' ?></button>
        <a class="btn btn-outline-secondary" href="<?= e(url('/admin/articles')) ?>">取消</a>
    </div>
</form>
