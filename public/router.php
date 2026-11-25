<?php
/**
 * TechHub - 高校技术团队展示与管理平台
 *
 * 文件：public/router.php
 * 模块：开发服务器入口
 * 职责：供 PHP 内置服务器转发静态文件和 MVC 动态请求，不用于生产路由。
 * 作者：待团队填写
 * 创建日期：2026-11-25
 * 最后修改：2026-11-25
 */

declare(strict_types=1);

$uri = (string) ($_SERVER['REQUEST_URI'] ?? '/');
$path = parse_url($uri, PHP_URL_PATH);
$file = __DIR__ . (is_string($path) ? $path : '/');

if (is_string($path) && $path !== '/' && is_file($file)) {
    return false;
}

require __DIR__ . '/index.php';
