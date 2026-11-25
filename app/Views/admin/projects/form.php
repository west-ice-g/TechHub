<?php
/**
 * TechHub - 高校技术团队展示与管理平台
 *
 * 文件：app/Views/admin/projects/form.php
 * 模块：项目管理
 * 职责：新增/编辑项目、封面上传、状态管理和项目成员关联。
 * 作者：待团队填写
 * 创建日期：2026-11-25
 * 最后修改：2026-11-25
 */

declare(strict_types=1);

$p = static function (string $key, mixed $default = '') use ($formOld, $project): mixed {
    return $formOld[$key] ?? $project[$key] ?? $default;
};
$isPublic = (int) ($formOld['is_public'] ?? $project['is_public'] ?? 1) === 1;
$isFeatured = (int) ($formOld['is_featured'] ?? $project['is_featured'] ?? 0) === 1;
$chosenMembers = array_key_exists('members', $formOld)
    ? array_map('intval', (array) $formOld['members'])
    : array_map('intval', $selectedMembers);
$chosenRoles = array_key_exists('member_roles', $formOld)
    ? (array) $formOld['member_roles']
    : $memberRoles;
$cover = $editing ? media_url((string) ($project['cover_path'] ?? '')) : null;
$formAction = $editing ? url('/admin/projects/' . $project['id'] . '/edit') : url('/admin/projects');
$heading = $editing ? '编辑项目' : '新增项目';
$description = '项目数据直接驱动前台项目列表与详情页；下线后前台不可见。';
$kicker = 'PROJECT EDITOR';
$actions = '<a class="btn btn-sm btn-outline-secondary" href="' . e(url('/admin/projects')) . '">返回项目列表</a>';
include BASE_PATH . '/app/Views/admin/partials/page-header.php';
?>

<form method="post" action="<?= e($formAction) ?>" enctype="multipart/form-data">
    <?= csrf_field() ?>
    <?php include BASE_PATH . '/app/Views/admin/partials/form-errors.php'; ?>

    <div class="row g-3">
        <div class="col-xl-8">
            <div class="admin-form-card mb-3">
                <div class="admin-form-section">
                    <h2>项目内容</h2>
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label" for="title">项目标题 <span class="text-danger">*</span></label>
                            <input class="form-control" id="title" name="title" type="text" maxlength="150"
                                   value="<?= e($p('title')) ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="slug">URL 别名 <span class="text-danger">*</span></label>
                            <input class="form-control" id="slug" name="slug" type="text" maxlength="160"
                                   value="<?= e($p('slug')) ?>" pattern="[a-z0-9]+(?:-[a-z0-9]+)*">
                            <div class="form-text">留空将按标题生成；仅小写字母、数字、连字符。</div>
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="summary">项目摘要 <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="summary" name="summary" rows="2" maxlength="500" required><?= e($p('summary')) ?></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="description">项目介绍 <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="description" name="description" rows="8" required><?= e($p('description')) ?></textarea>
                            <div class="form-text">前台使用受控 Markdown 渲染，原始 HTML 会被转义。</div>
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="outcomes">项目成果</label>
                            <textarea class="form-control" id="outcomes" name="outcomes" rows="3"><?= e($p('outcomes')) ?></textarea>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label" for="tech_stack">技术栈</label>
                            <input class="form-control" id="tech_stack" name="tech_stack" type="text" maxlength="255"
                                   value="<?= e($p('tech_stack')) ?>" placeholder="PHP 8.2 / MySQL 8.0 / Bootstrap 5">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="sort_order">排序</label>
                            <input class="form-control" id="sort_order" name="sort_order" type="number" min="-999" max="999"
                                   value="<?= e((string) $p('sort_order', 0)) ?>">
                        </div>
                    </div>
                </div>

                <div class="admin-form-section">
                    <h2>链接与时间</h2>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label" for="repo_url">代码仓库</label>
                            <input class="form-control" id="repo_url" name="repo_url" type="url" maxlength="255"
                                   value="<?= e($p('repo_url')) ?>" placeholder="https://github.com/...">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="demo_url">演示地址</label>
                            <input class="form-control" id="demo_url" name="demo_url" type="url" maxlength="255"
                                   value="<?= e($p('demo_url')) ?>" placeholder="https://...">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="start_date">开始日期</label>
                            <input class="form-control" id="start_date" name="start_date" type="date"
                                   value="<?= e($p('start_date')) ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="end_date">结束日期</label>
                            <input class="form-control" id="end_date" name="end_date" type="date"
                                   value="<?= e($p('end_date')) ?>">
                        </div>
                    </div>
                </div>
            </div>

            <div class="admin-form-card">
                <div class="admin-form-section">
                    <h2>项目成员</h2>
                    <p class="admin-help">团队：<?= e($team['name']) ?>。成员来自团队成员管理，可填写项目内角色。</p>
                    <?php if ($memberOptions === []): ?>
                        <div class="admin-note">当前团队暂无可用成员。</div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table admin-table">
                                <thead>
                                <tr>
                                    <th style="width:40px;">选择</th>
                                    <th>成员</th>
                                    <th>团队职位</th>
                                    <th>项目内角色</th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($memberOptions as $option): ?>
                                    <?php $optionId = (int) $option['id']; ?>
                                    <tr>
                                        <td>
                                            <input class="form-check-input" type="checkbox"
                                                   id="member-<?= e((string) $optionId) ?>"
                                                   name="members[]" value="<?= e((string) $optionId) ?>"
                                                   <?= in_array($optionId, $chosenMembers, true) ? 'checked' : '' ?>>
                                        </td>
                                        <td>
                                            <label class="form-check-label" for="member-<?= e((string) $optionId) ?>">
                                                <strong><?= e($option['display_name']) ?></strong>
                                                <span class="small text-secondary">（<?= e($option['username']) ?>）</span>
                                            </label>
                                        </td>
                                        <td class="small text-secondary">
                                            <?= e($option['position_title'] ?? '—') ?>
                                            <?php if ($option['status'] !== 'active'): ?>
                                                <span class="badge text-bg-warning">已毕业</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <input class="form-control form-control-sm" type="text" maxlength="80"
                                                   name="member_roles[<?= e((string) $optionId) ?>]"
                                                   value="<?= e((string) ($chosenRoles[$optionId] ?? '')) ?>"
                                                   placeholder="例如：后端负责人">
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-xl-4">
            <div class="admin-form-card mb-3">
                <div class="admin-form-section">
                    <h2>状态与发布</h2>
                    <div class="mb-3">
                        <label class="form-label" for="status">项目状态</label>
                        <select class="form-select" id="status" name="status">
                            <?php foreach ($statusOptions as $key => $label): ?>
                                <option value="<?= e($key) ?>" <?= $p('status', 'ongoing') === $key ? 'selected' : '' ?>><?= e($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" id="is_public" name="is_public" value="1" <?= $isPublic ? 'checked' : '' ?>>
                        <label class="form-check-label" for="is_public">发布到前台</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="is_featured" name="is_featured" value="1" <?= $isFeatured ? 'checked' : '' ?>>
                        <label class="form-check-label" for="is_featured">首页推荐</label>
                    </div>
                </div>
            </div>

            <div class="admin-form-card">
                <div class="admin-form-section">
                    <h2>项目封面</h2>
                    <?php if ($cover !== null): ?>
                        <img class="admin-cover-preview mb-2" src="<?= e($cover) ?>" alt="当前项目封面">
                    <?php else: ?>
                        <div class="admin-help mb-2">未上传封面时前台使用占位样式。</div>
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
        <button class="btn btn-primary" type="submit"><i class="bi bi-save me-1" aria-hidden="true"></i><?= $editing ? '保存项目' : '创建项目' ?></button>
        <a class="btn btn-outline-secondary" href="<?= e(url('/admin/projects')) ?>">取消</a>
    </div>
</form>
