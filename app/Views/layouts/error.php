<?php
/**
 * TechHub - 高校技术团队展示与管理平台
 *
 * 文件：app/Views/layouts/error.php
 * 模块：错误页面布局
 * 职责：不依赖数据库和登录状态，输出最小化错误页面。
 * 作者：待团队填写
 * 创建日期：2026-11-25
 * 最后修改：2026-11-25
 */

declare(strict_types=1);
?>
<!doctype html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title><?= e($title ?? '请求失败') ?> · TechHub</title>
    <link rel="icon" href="<?= e(url('/favicon.ico')) ?>" type="image/x-icon">
    <link rel="stylesheet" href="<?= e(asset('css/bootstrap.min.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset('css/bootstrap-icons.min.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>">
</head>
<body class="error-body">
    <main class="error-card">
        <a class="brand-mark" href="<?= e(url('/')) ?>"><span class="brand-dot"></span>TechHub</a>
        <?= $content ?? '' ?>
    </main>
</body>
</html>
