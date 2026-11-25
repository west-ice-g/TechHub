<?php
/**
 * TechHub - 高校技术团队展示与管理平台
 *
 * 文件：config/app.php
 * 模块：应用配置
 * 职责：集中声明非秘密应用配置，敏感值从 .env 读取。
 * 作者：待团队填写
 * 创建日期：2026-11-25
 * 最后修改：2026-11-25
 */

declare(strict_types=1);

return [
    'name' => (string) env('APP_NAME', 'TechHub'),
    'env' => (string) env('APP_ENV', 'local'),
    'debug' => filter_var(env('APP_DEBUG', false), FILTER_VALIDATE_BOOL),
    'url' => rtrim((string) env('APP_URL', 'http://localhost:8090'), '/'),
    'base_path' => rtrim((string) env('APP_BASE_PATH', ''), '/'),
    'timezone' => (string) env('APP_TIMEZONE', 'Asia/Shanghai'),

    'session' => [
        'name' => (string) env('SESSION_NAME', 'TECHHUBSESSID'),
        'lifetime' => (int) env('SESSION_LIFETIME', 7200),
        'save_path' => BASE_PATH . '/storage/sessions',
    ],

    'uploads' => [
        'max_size' => 20 * 1024 * 1024,
        'document_extensions' => ['pdf', 'doc', 'docx', 'ppt', 'pptx', 'zip', 'txt'],
        'image_extensions' => ['jpg', 'jpeg', 'png', 'webp', 'gif'],
    ],

    'security' => [
        'login_max_attempts' => 5,
        'login_lockout_seconds' => 600,
    ],
];
