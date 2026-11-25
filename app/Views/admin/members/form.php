<?php
/**
 * TechHub - 高校技术团队展示与管理平台
 *
 * 文件：app/Views/admin/members/form.php
 * 模块：成员管理
 * 职责：添加或编辑团队成员关系与公开资料。
 * 作者：待团队填写
 * 创建日期：2026-11-25
 * 最后修改：2026-11-25
 */

declare(strict_types=1);

$m = static function (string $key, mixed $default = '') use ($formOld, $membership): mixed {
    return $formOld[$key] ?? $membership[$key] ?? $default;
};
$status = (string) $m('status', 'active');
$avatar = $editing ? media_url((string) ($membership['avatar_path'] ?? '')) : null;
$formAction = $editing ? url('/admin/members/' . $membership['id'] . '/edit') : url('/admin/members');
$heading = $editing ? '编辑成员' : '添加成员';
$description = $editing ? '账号不可更换；可调整职位、介绍、头像和成员状态。' : '从已启用账号中选择成员；账号需先在用户管理中创建。';
$kicker = 'MEMBER PROFILE';
$actions = '<a class="btn btn-sm btn-outline-secondary" href="' . e(url('/admin/members')) . '">返回成员列表</a>';
include BASE_PATH . '/app/Views/admin/partials/page-header.php';
?>

<form method="post" action="<?= e($formAction) ?>" enctype="multipart/form-data">
    <?= csrf_field() ?>
    <?php include BASE_PATH . '/app/Views/admin/partials/form-errors.php'; ?>

    <div class="row g-3">
        <div class="col-xl-8">
            <div class="admin-form-card">
                <div class="admin-form-section">
                    <h2>成员关系</h2>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label" for="user_id">账号 <span class="text-danger">*</span></label>
                            <?php if ($editing): ?>
                                <input class="form-control" id="user_id" type="text"
                                       value="<?= e($membership['display_name'] . '（' . $membership['username'] . '）') ?>" readonly>
                            <?php else: ?>
                                <select class="form-select" id="user_id" name="user_id" required>
                                    <option value="0">请选择账号</option>
                                    <?php foreach ($candidates as $candidate): ?>
                                        <option value="<?= e((string) $candidate['id']) ?>" <?= (int) $m('user_id') === (int) $candidate['id'] ? 'selected' : '' ?>>
                                            <?= e($candidate['display_name']) ?>（<?= e($candidate['username']) ?>）
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <?php if ($candidates === []): ?>
                                    <div class="form-text text-danger">没有可加入的账号，请先在用户管理中创建或停用关系。</div>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="position_title">职位</label>
                            <input class="form-control" id="position_title" name="position_title" type="text" maxlength="80"
                                   value="<?= e($m('position_title')) ?>" placeholder="示例：后端负责人">
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="responsibilities">职责</label>
                            <input class="form-control" id="responsibilities" name="responsibilities" type="text" maxlength="255"
                                   value="<?= e($m('responsibilities')) ?>" placeholder="项目规划、PHP MVC、数据库设计">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="joined_at">加入日期 <span class="text-danger">*</span></label>
                            <input class="form-control" id="joined_at" name="joined_at" type="date"
                                   value="<?= e($m('joined_at', date('Y-m-d'))) ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="left_at">离队日期</label>
                            <input class="form-control" id="left_at" name="left_at" type="date"
                                   value="<?= e($m('left_at')) ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="status">状态 <span class="text-danger">*</span></label>
                            <select class="form-select" id="status" name="status" required>
                                <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>在队</option>
                                <option value="alumni" <?= $status === 'alumni' ? 'selected' : '' ?>>已毕业</option>
                                <option value="removed" <?= $status === 'removed' ? 'selected' : '' ?>>已移除</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="sort_order">排序</label>
                            <input class="form-control" id="sort_order" name="sort_order" type="number" min="-999" max="999"
                                   value="<?= e((string) $m('sort_order', 0)) ?>">
                        </div>
                    </div>
                </div>

                <div class="admin-form-section">
                    <h2>公开资料</h2>
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label" for="bio">成员介绍</label>
                            <textarea class="form-control" id="bio" name="bio" rows="4" maxlength="5000"><?= e($m('bio')) ?></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="skills">技能</label>
                            <input class="form-control" id="skills" name="skills" type="text" maxlength="255"
                                   value="<?= e($m('skills')) ?>" placeholder="PHP, MySQL, Docker">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="public_email">公开邮箱</label>
                            <input class="form-control" id="public_email" name="public_email" type="email" maxlength="120"
                                   value="<?= e($m('public_email')) ?>">
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="github_url">GitHub 地址</label>
                            <input class="form-control" id="github_url" name="github_url" type="url" maxlength="255"
                                   value="<?= e($m('github_url')) ?>" placeholder="https://github.com/...">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-4">
            <div class="admin-form-card">
                <div class="admin-form-section">
                    <h2>成员头像</h2>
                    <?php if ($avatar !== null): ?>
                        <img class="admin-cover-preview mb-2" src="<?= e($avatar) ?>" alt="当前头像" style="width:110px;height:110px;border-radius:50%;">
                    <?php else: ?>
                        <div class="admin-help mb-2">尚未上传头像，前台使用姓名首字符占位。</div>
                    <?php endif; ?>
                    <input class="form-control form-control-sm" id="avatar" name="avatar" type="file" accept="image/jpeg,image/png,image/webp,image/gif">
                    <div class="form-text">JPG/PNG/WebP/GIF，最大 4MB。</div>
                    <?php if ($avatar !== null): ?>
                        <div class="form-check mt-2">
                            <input class="form-check-input" type="checkbox" id="remove_avatar" name="remove_avatar" value="1">
                            <label class="form-check-label small" for="remove_avatar">移除当前头像</label>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex flex-wrap gap-2 mt-3">
        <button class="btn btn-primary" type="submit" <?= (!$editing && $candidates === []) ? 'disabled' : '' ?>>
            <i class="bi bi-save me-1" aria-hidden="true"></i><?= $editing ? '保存成员资料' : '添加成员' ?>
        </button>
        <a class="btn btn-outline-secondary" href="<?= e(url('/admin/members')) ?>">取消</a>
    </div>
</form>
