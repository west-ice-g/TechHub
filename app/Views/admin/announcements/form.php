<?php
/**
 * TechHub - 高校技术团队展示与管理平台
 *
 * 文件：app/Views/admin/announcements/form.php
 * 模块：公告管理
 * 职责：新增或编辑公告，维护类型、置顶、有效期和发布状态。
 * 作者：待团队填写
 * 创建日期：2026-11-25
 * 最后修改：2026-11-25
 */

declare(strict_types=1);

$a = static function (string $key, mixed $default = '') use ($formOld, $announcement): mixed {
    return $formOld[$key] ?? $announcement[$key] ?? $default;
};
$isPinned = (int) ($formOld['is_pinned'] ?? $announcement['is_pinned'] ?? 0) === 1;
$formAction = $editing ? url('/admin/announcements/' . $announcement['id'] . '/edit') : url('/admin/announcements');
$heading = $editing ? '编辑公告' : '新增公告';
$description = '公告按置顶和发布时间排序；过期后前台自动隐藏。';
$kicker = 'ANNOUNCEMENT';
$actions = '<a class="btn btn-sm btn-outline-secondary" href="' . e(url('/admin/announcements')) . '">返回公告列表</a>';
include BASE_PATH . '/app/Views/admin/partials/page-header.php';
?>

<form method="post" action="<?= e($formAction) ?>">
    <?= csrf_field() ?>
    <?php include BASE_PATH . '/app/Views/admin/partials/form-errors.php'; ?>

    <div class="row g-3">
        <div class="col-xl-9">
            <div class="admin-form-card">
                <div class="admin-form-section">
                    <h2>公告内容</h2>
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label" for="title">标题 <span class="text-danger">*</span></label>
                            <input class="form-control" id="title" name="title" type="text" maxlength="200"
                                   value="<?= e($a('title')) ?>" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="summary">摘要</label>
                            <textarea class="form-control" id="summary" name="summary" rows="2" maxlength="500"><?= e($a('summary')) ?></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="content">正文 <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="content" name="content" rows="12" required><?= e($a('content')) ?></textarea>
                            <div class="form-text">前台使用受控 Markdown 渲染，原始 HTML 会被转义。</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3">
            <div class="admin-form-card">
                <div class="admin-form-section">
                    <h2>发布设置</h2>
                    <div class="mb-3">
                        <label class="form-label" for="type">公告类型</label>
                        <select class="form-select" id="type" name="type">
                            <?php foreach ($typeOptions as $key => $label): ?>
                                <option value="<?= e($key) ?>" <?= $a('type', 'general') === $key ? 'selected' : '' ?>><?= e($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="status">状态</label>
                        <select class="form-select" id="status" name="status">
                            <?php foreach ($statusOptions as $key => $label): ?>
                                <option value="<?= e($key) ?>" <?= $a('status', 'draft') === $key ? 'selected' : '' ?>><?= e($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="expires_at">过期日期</label>
                        <input class="form-control" id="expires_at" name="expires_at" type="date"
                               value="<?= e($a('expires_at')) ?>">
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="is_pinned" name="is_pinned" value="1" <?= $isPinned ? 'checked' : '' ?>>
                        <label class="form-check-label" for="is_pinned">置顶公告</label>
                    </div>
                    <?php if ($editing): ?>
                        <div class="admin-divider"></div>
                        <dl class="row small mb-0">
                            <dt class="col-5 text-secondary">发布人</dt>
                            <dd class="col-7"><?= e($announcement['publisher_name']) ?></dd>
                            <dt class="col-5 text-secondary">发布时间</dt>
                            <dd class="col-7"><?= e(format_datetime($announcement['published_at'] ?? null)) ?></dd>
                        </dl>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex flex-wrap gap-2 mt-3">
        <button class="btn btn-primary" type="submit"><i class="bi bi-save me-1" aria-hidden="true"></i><?= $editing ? '保存公告' : '创建公告' ?></button>
        <a class="btn btn-outline-secondary" href="<?= e(url('/admin/announcements')) ?>">取消</a>
    </div>
</form>
