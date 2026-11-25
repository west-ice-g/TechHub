<?php
/**
 * TechHub - 高校技术团队展示与管理平台
 *
 * 文件：app/Views/admin/files/form.php
 * 模块：文件管理
 * 职责：上传新文件或编辑文件元信息。
 * 作者：待团队填写
 * 创建日期：2026-11-25
 * 最后修改：2026-11-25
 */

declare(strict_types=1);

$f = static function (string $key, mixed $default = '') use ($formOld, $file): mixed {
    return $formOld[$key] ?? $file[$key] ?? $default;
};
$isPublic = (int) ($formOld['is_public'] ?? $file['is_public'] ?? 1) === 1;
$status = (string) $f('status', 'active');
$category = (string) $f('category', 'team_assignment');
$relatedMemberId = (int) $f('related_member_id', 0);
$formAction = $editing ? url('/admin/files/' . $file['id'] . '/edit') : url('/admin/files/upload');
$heading = $editing ? '编辑文件' : '上传文件';
$description = $editing ? '文件本体与分类不可在此修改；可调整标题、说明、关联成员和公开状态。' : '上传文件会保存到受控 data 目录，数据库记录相对路径。';
$kicker = 'FILE UPLOAD';
$actions = '<a class="btn btn-sm btn-outline-secondary" href="' . e(url('/admin/files')) . '">返回文件列表</a>';
include BASE_PATH . '/app/Views/admin/partials/page-header.php';
?>

<form method="post" action="<?= e($formAction) ?>" enctype="multipart/form-data">
    <?= csrf_field() ?>
    <?php include BASE_PATH . '/app/Views/admin/partials/form-errors.php'; ?>

    <div class="row g-3">
        <div class="col-xl-8">
            <div class="admin-form-card">
                <div class="admin-form-section">
                    <h2>文件信息</h2>
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label" for="title">文件标题 <span class="text-danger">*</span></label>
                            <input class="form-control" id="title" name="title" type="text" maxlength="200"
                                   value="<?= e($f('title')) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="category">分类 <span class="text-danger">*</span></label>
                            <?php if ($editing): ?>
                                <input class="form-control" id="category" type="text"
                                       value="<?= e($categories[$file['category']]['label'] ?? $file['category']) ?>" readonly>
                            <?php else: ?>
                                <select class="form-select" id="category" name="category" required>
                                    <?php foreach ($categories as $key => $config): ?>
                                        <option value="<?= e($key) ?>" <?= $category === $key ? 'selected' : '' ?>>
                                            <?= e($config['label']) ?>（<?= e($config['directory']) ?>）
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="related_member_id">关联成员</label>
                            <select class="form-select" id="related_member_id" name="related_member_id">
                                <option value="0">不关联成员</option>
                                <?php foreach ($memberOptions as $member): ?>
                                    <option value="<?= e((string) $member['id']) ?>" <?= $relatedMemberId === (int) $member['id'] ? 'selected' : '' ?>>
                                        <?= e($member['display_name']) ?>（<?= e($member['username']) ?>）
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="form-text">个人作业建议关联具体成员。</div>
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="description">文件说明</label>
                            <textarea class="form-control" id="description" name="description" rows="3" maxlength="500"><?= e($f('description')) ?></textarea>
                        </div>

                        <?php if (!$editing): ?>
                            <div class="col-12">
                                <label class="form-label" for="file">选择文件 <span class="text-danger">*</span></label>
                                <input class="form-control" id="file" name="file" type="file" required
                                       accept=".pdf,.doc,.docx,.ppt,.pptx,.zip,.txt">
                                <div class="form-text">
                                    允许：<?= e(implode(', ', (array) config('app.uploads.document_extensions', []))) ?>；单文件不超过
                                    <?= e(format_file_size((int) config('app.uploads.max_size', 20971520))) ?>。
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if ($editing): ?>
                    <div class="admin-form-section">
                        <h2>文件本体</h2>
                        <dl class="row small mb-0">
                            <dt class="col-sm-3 text-secondary">原始文件名</dt>
                            <dd class="col-sm-9 text-break"><?= e($file['original_name']) ?></dd>
                            <dt class="col-sm-3 text-secondary">MIME</dt>
                            <dd class="col-sm-9"><?= e($file['mime_type']) ?></dd>
                            <dt class="col-sm-3 text-secondary">大小</dt>
                            <dd class="col-sm-9"><?= e(format_file_size((int) $file['size_bytes'])) ?></dd>
                            <dt class="col-sm-3 text-secondary">下载次数</dt>
                            <dd class="col-sm-9"><?= e((string) $file['download_count']) ?></dd>
                            <dt class="col-sm-3 text-secondary">上传人</dt>
                            <dd class="col-sm-9"><?= e($file['uploader_name']) ?></dd>
                            <dt class="col-sm-3 text-secondary">上传时间</dt>
                            <dd class="col-sm-9"><?= e(format_datetime($file['created_at'])) ?></dd>
                        </dl>
                        <a class="btn btn-sm btn-outline-secondary mt-3" href="<?= e(url('/admin/files/' . $file['id'] . '/download')) ?>">
                            <i class="bi bi-download me-1" aria-hidden="true"></i>下载当前文件
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="col-xl-4">
            <div class="admin-form-card">
                <div class="admin-form-section">
                    <h2>公开与状态</h2>
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" id="is_public" name="is_public" value="1" <?= $isPublic ? 'checked' : '' ?>>
                        <label class="form-check-label" for="is_public">允许前台下载</label>
                    </div>
                    <label class="form-label" for="status">文件状态</label>
                    <select class="form-select" id="status" name="status">
                        <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>启用</option>
                        <option value="hidden" <?= $status === 'hidden' ? 'selected' : '' ?>>隐藏</option>
                    </select>
                    <div class="form-text">前台仅显示 public=1 且 active 的文件。</div>
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex flex-wrap gap-2 mt-3">
        <button class="btn btn-primary" type="submit"><i class="bi bi-save me-1" aria-hidden="true"></i><?= $editing ? '保存文件信息' : '上传文件' ?></button>
        <a class="btn btn-outline-secondary" href="<?= e(url('/admin/files')) ?>">取消</a>
    </div>
</form>
