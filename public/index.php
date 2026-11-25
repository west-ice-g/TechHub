<?php
/**
 * TechHub - 高校技术团队展示与管理平台
 *
 * 文件：public/index.php
 * 模块：Web 入口
 * 职责：所有动态请求的唯一入口，初始化应用并分发给 Router。
 * 作者：待团队填写
 * 创建日期：2026-11-25
 * 最后修改：2026-11-25
 */

declare(strict_types=1);

define('BASE_PATH', dirname(__DIR__));

require BASE_PATH . '/bootstrap/app.php';

$router = new Core\Router();
$routeRegistrar = require BASE_PATH . '/config/routes.php';
$routeRegistrar($router);

$response = $router->dispatch(Core\Request::capture());
$response->send();
