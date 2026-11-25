<?php
/**
 * TechHub - 高校技术团队展示与管理平台
 *
 * 文件：app/Views/errors/404.php
 * 模块：错误页
 * 职责：输出页面不存在提示。
 * 作者：待团队填写
 * 创建日期：2026-11-25
 * 最后修改：2026-11-25
 */

declare(strict_types=1);
?>
<div class="error-code">404</div>
<h1 class="h3"><?= e($title ?? '404 页面不存在') ?></h1>
<p class="text-secondary"><?= e($message ?? '你访问的页面不存在或已被移动。') ?></p>
<a class="btn btn-primary" href="<?= e(url('/')) ?>">返回首页</a>
