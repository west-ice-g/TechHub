<?php
/**
 * TechHub - 高校技术团队展示与管理平台
 *
 * 文件：config/database.php
 * 模块：数据库配置
 * 职责：从环境变量读取数据库连接参数，禁止把真实密码写入仓库。
 * 作者：待团队填写
 * 创建日期：2026-11-25
 * 最后修改：2026-11-25
 */

declare(strict_types=1);

return [
    'host' => (string) env('DB_HOST', '127.0.0.1'),
    'port' => (int) env('DB_PORT', 3308),
    'database' => (string) env('DB_DATABASE', 'techhub'),
    'username' => (string) env('DB_USERNAME', 'techhub_app'),
    'password' => (string) env('DB_PASSWORD', ''),
    'charset' => (string) env('DB_CHARSET', 'utf8mb4'),
];
