<?php
/**
 * TechHub - 高校技术团队展示与管理平台
 *
 * 文件：app/Views/errors/405.php
 * 模块：错误页
 * 职责：输出请求方法不允许提示。
 * 作者：待团队填写
 * 创建日期：2026-11-25
 * 最后修改：2026-11-25
 */

declare(strict_types=1);
?>
<div class="error-code">405</div>
<h1 class="h3"><?= e($title ?? '405 方法不允许') ?></h1>
<p class="text-secondary"><?= e($message ?? '该地址不支持当前请求方式。') ?></p>
<a class="btn btn-primary" href="<?= e(url('/')) ?>">返回首页</a>
