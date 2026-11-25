<?php
/**
 * TechHub - 高校技术团队展示与管理平台
 *
 * 文件：app/Views/admin/partials/form-errors.php
 * 模块：后台公共组件
 * 职责：显示服务端表单校验错误。
 * 作者：待团队填写
 * 创建日期：2026-11-25
 * 最后修改：2026-11-25
 */

declare(strict_types=1);

$errors = is_array($formErrors ?? null) ? $formErrors : [];
?>
<?php if ($errors !== []): ?>
    <div class="alert alert-danger" role="alert">
        <div class="fw-bold mb-1"><i class="bi bi-exclamation-octagon me-1" aria-hidden="true"></i>请修正以下问题</div>
        <ul class="mb-0 ps-3">
            <?php foreach ($errors as $message): ?>
                <li><?= e($message) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>
