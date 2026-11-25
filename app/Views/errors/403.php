<?php
/**
 * TechHub - 高校技术团队展示与管理平台
 *
 * 文件：app/Views/errors/403.php
 * 模块：错误页
 * 职责：输出无权访问提示。
 * 作者：待团队填写
 * 创建日期：2026-11-25
 * 最后修改：2026-11-25
 */

declare(strict_types=1);
?>
<div class="error-code">403</div>
<h1 class="h3"><?= e($title ?? '403 无权访问') ?></h1>
<p class="text-secondary"><?= e($message ?? '当前账号没有访问该功能的权限。') ?></p>
<div class="d-flex flex-wrap gap-2">
    <a class="btn btn-primary" href="<?= e(url('/admin')) ?>">返回后台</a>
    <a class="btn btn-outline-secondary" href="<?= e(url('/')) ?>">返回首页</a>
</div>
