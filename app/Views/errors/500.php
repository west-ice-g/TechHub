<?php
/**
 * TechHub - 高校技术团队展示与管理平台
 *
 * 文件：app/Views/errors/500.php
 * 模块：错误页
 * 职责：输出服务器错误提示，不包含堆栈和敏感路径。
 * 作者：待团队填写
 * 创建日期：2026-11-25
 * 最后修改：2026-11-25
 */

declare(strict_types=1);
?>
<div class="error-code">500</div>
<h1 class="h3"><?= e($title ?? '500 服务器错误') ?></h1>
<p class="text-secondary"><?= e($message ?? '系统暂时无法处理该请求，请稍后重试。') ?></p>
<p class="small text-secondary">错误详情已写入服务器日志，不会直接暴露给访问者。</p>
<a class="btn btn-primary" href="<?= e(url('/')) ?>">返回首页</a>
