<?php
/**
 * TechHub - 高校技术团队展示与管理平台
 *
 * 文件：app/Views/admin/categories/form.php
 * 模块：文章分类
 * 职责：新增或编辑文章分类。
 * 作者：待团队填写
 * 创建日期：2026-11-25
 * 最后修改：2026-11-25
 */

declare(strict_types=1);

$c = static function (string $key, mixed $default = '') use ($formOld, $category): mixed {
    return $formOld[$key] ?? $category[$key] ?? $default;
};
$parentId = (int) $c('parent_id', 0);
$formAction = $editing ? url('/admin/categories/' . $category['id'] . '/edit') : url('/admin/categories');
$heading = $editing ? '编辑分类' : '新增分类';
$description = '分类数据用于前台文章筛选和文章表单选择。';
$kicker = 'CATEGORY';
$actions = '<a class="btn btn-sm btn-outline-secondary" href="' . e(url('/admin/categories')) . '">返回分类列表</a>';
include BASE_PATH . '/app/Views/admin/partials/page-header.php';
?>

<div class="row justify-content-center">
    <div class="col-xl-8">
        <div class="admin-form-card">
            <?php include BASE_PATH . '/app/Views/admin/partials/form-errors.php'; ?>
            <form method="post" action="<?= e($formAction) ?>">
                <?= csrf_field() ?>
                <div class="admin-form-section">
                    <h2>分类信息</h2>
                    <div class="row g-3">
                        <div class="col-md-7">
                            <label class="form-label" for="name">名称 <span class="text-danger">*</span></label>
                            <input class="form-control" id="name" name="name" type="text" maxlength="80"
                                   value="<?= e($c('name')) ?>" required>
                        </div>
                        <div class="col-md-5">
                            <label class="form-label" for="slug">别名 <span class="text-danger">*</span></label>
                            <input class="form-control" id="slug" name="slug" type="text" maxlength="100"
                                   value="<?= e($c('slug')) ?>" pattern="[a-z0-9]+(?:-[a-z0-9]+)*">
                            <div class="form-text">留空按名称生成。</div>
                        </div>
                        <div class="col-md-7">
                            <label class="form-label" for="parent_id">父分类</label>
                            <select class="form-select" id="parent_id" name="parent_id">
                                <option value="0">无（顶级分类）</option>
                                <?php foreach ($parentOptions as $parent): ?>
                                    <option value="<?= e((string) $parent['id']) ?>" <?= $parentId === (int) $parent['id'] ? 'selected' : '' ?>>
                                        <?= e($parent['name']) ?><?= $parent['parent_name'] ? '（' . e($parent['parent_name']) . ' 下）' : '' ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" for="status">状态</label>
                            <select class="form-select" id="status" name="status">
                                <option value="enabled" <?= $c('status', 'enabled') === 'enabled' ? 'selected' : '' ?>>启用</option>
                                <option value="disabled" <?= $c('status') === 'disabled' ? 'selected' : '' ?>>停用</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label" for="sort_order">排序</label>
                            <input class="form-control" id="sort_order" name="sort_order" type="number" min="-999" max="999"
                                   value="<?= e((string) $c('sort_order', 0)) ?>">
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="description">说明</label>
                            <textarea class="form-control" id="description" name="description" rows="3" maxlength="255"><?= e($c('description')) ?></textarea>
                        </div>
                    </div>
                </div>

                <div class="d-flex flex-wrap gap-2">
                    <button class="btn btn-primary" type="submit"><?= $editing ? '保存分类' : '创建分类' ?></button>
                    <a class="btn btn-outline-secondary" href="<?= e(url('/admin/categories')) ?>">取消</a>
                </div>
            </form>
        </div>
    </div>
</div>
