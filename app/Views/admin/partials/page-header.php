<?php
/**
 * TechHub - 高校技术团队展示与管理平台
 *
 * 文件：app/Views/admin/partials/page-header.php
 * 模块：后台公共组件
 * 职责：输出后台页面标题、说明和右侧操作区。
 * 作者：待团队填写
 * 创建日期：2026-11-25
 * 最后修改：2026-11-25
 */

declare(strict_types=1);
?>
<div class="admin-page-head">
    <div>
        <?php if (!empty($kicker)): ?>
            <div class="admin-page-kicker"><?= e($kicker) ?></div>
        <?php endif; ?>
        <h1><?= e($heading ?? '') ?></h1>
        <?php if (!empty($description)): ?>
            <p><?= e($description) ?></p>
        <?php endif; ?>
    </div>
    <?php if (!empty($actions)): ?>
        <div class="d-flex flex-wrap gap-2"><?= $actions ?></div>
    <?php endif; ?>
</div>
