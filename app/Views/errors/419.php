<?php
/**
 * TechHub - 高校技术团队展示与管理平台
 *
 * 文件：app/Views/errors/419.php
 * 模块：错误页
 * 职责：输出 CSRF/页面过期提示。
 * 作者：待团队填写
 * 创建日期：2026-11-25
 * 最后修改：2026-11-25
 */

declare(strict_types=1);
?>
<div class="error-code">419</div>
<h1 class="h3"><?= e($title ?? '419 页面已过期') ?></h1>
<p class="text-secondary"><?= e($message ?? '页面安全令牌已失效，请刷新后重试。') ?></p>
<div class="d-flex flex-wrap gap-2">
    <a class="btn btn-primary" href="<?= e(url('/admin/login')) ?>">重新登录</a>
    <a class="btn btn-outline-secondary" href="<?= e(url('/')) ?>">返回首页</a>
</div>
