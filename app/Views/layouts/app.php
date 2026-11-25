<?php
/**
 * TechHub - 高校技术团队展示与管理平台
 *
 * 文件：app/Views/layouts/app.php
 * 模块：前台布局
 * 职责：输出统一导航、登录状态、Flash 消息、主内容和底部信息。
 * 作者：待团队填写
 * 创建日期：2026-11-25
 * 最后修改：2026-11-25
 */

declare(strict_types=1);

use Core\Auth;
use Core\Session;

$flashes = Session::flashes();
$currentUser = Auth::check() ? Auth::user() : null;
$currentPath = (string) (parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/');
$basePath = rtrim((string) config('app.base_path', ''), '/');
if ($basePath !== '' && str_starts_with($currentPath, $basePath)) {
    $currentPath = substr($currentPath, strlen($basePath));
    $currentPath = $currentPath === '' ? '/' : $currentPath;
}

$navItems = [
    ['path' => '/', 'label' => '首页', 'icon' => 'bi-house-door'],
    ['path' => '/team', 'label' => '团队', 'icon' => 'bi-people'],
    ['path' => '/members', 'label' => '成员', 'icon' => 'bi-person-badge'],
    ['path' => '/projects', 'label' => '项目', 'icon' => 'bi-kanban'],
    ['path' => '/articles', 'label' => '文章', 'icon' => 'bi-journal-text'],
    ['path' => '/announcements', 'label' => '公告', 'icon' => 'bi-megaphone'],
    ['path' => '/messages', 'label' => '留言', 'icon' => 'bi-chat-square-text'],
    ['path' => '/downloads', 'label' => '下载', 'icon' => 'bi-cloud-arrow-down'],
];
$isActive = static function (string $path) use ($currentPath): bool {
    return $path === '/' ? $currentPath === '/' : str_starts_with($currentPath, $path);
};
?>
<!doctype html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="<?= e($pageDescription ?? 'TechHub 高校技术团队展示与管理平台') ?>">
    <title><?= e($title ?? 'TechHub') ?></title>
    <link rel="icon" href="<?= e(url('/favicon.ico')) ?>" type="image/x-icon">
    <link rel="stylesheet" href="<?= e(asset('css/bootstrap.min.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset('css/bootstrap-icons.min.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset('css/frontend.css')) ?>">
</head>
<body class="<?= e($bodyClass ?? '') ?>">
<a class="skip-link" href="#main-content">跳到主要内容</a>

<nav class="navbar navbar-expand-xl navbar-dark site-navbar sticky-top">
    <div class="container">
        <a class="navbar-brand brand-mark" href="<?= e(url('/')) ?>">
            <span class="brand-dot"></span>TechHub
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#siteNav"
                aria-controls="siteNav" aria-expanded="false" aria-label="切换导航">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="siteNav">
            <ul class="navbar-nav me-auto mb-2 mb-xl-0">
                <?php foreach ($navItems as $item): ?>
                    <li class="nav-item">
                        <a class="nav-link <?= $isActive($item['path']) ? 'active' : '' ?>"
                           href="<?= e(url($item['path'])) ?>"
                           <?= $isActive($item['path']) ? 'aria-current="page"' : '' ?>>
                            <i class="bi <?= e($item['icon']) ?> me-1" aria-hidden="true"></i><?= e($item['label']) ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>

            <div class="d-flex align-items-center gap-2 flex-wrap">
                <?php if ($currentUser !== null): ?>
                    <div class="dropdown">
                        <button class="btn btn-sm btn-outline-light dropdown-toggle" type="button"
                                data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-person-circle me-1" aria-hidden="true"></i>
                            <?= e($currentUser['display_name']) ?>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                            <li>
                                <a class="dropdown-item" href="<?= e(url('/profile')) ?>">
                                    <i class="bi bi-person-vcard me-2" aria-hidden="true"></i>个人资料
                                </a>
                            </li>
                            <?php if (Auth::can('dashboard.view')): ?>
                                <li>
                                    <a class="dropdown-item" href="<?= e(url('/admin')) ?>">
                                        <i class="bi bi-speedometer2 me-2" aria-hidden="true"></i>管理后台
                                    </a>
                                </li>
                            <?php endif; ?>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <form method="post" action="<?= e(url('/logout')) ?>" class="m-0" data-confirm="确定退出登录吗？">
                                    <?= csrf_field() ?>
                                    <button class="dropdown-item" type="submit">
                                        <i class="bi bi-box-arrow-right me-2" aria-hidden="true"></i>退出登录
                                    </button>
                                </form>
                            </li>
                        </ul>
                    </div>
                <?php else: ?>
                    <a class="btn btn-sm btn-light" href="<?= e(url('/login')) ?>">
                        <i class="bi bi-box-arrow-in-right me-1" aria-hidden="true"></i>登录
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>

<main id="main-content">
    <?php if ($flashes !== []): ?>
        <div class="container flash-container">
            <?php foreach ($flashes as $flash): ?>
                <?php
                $type = in_array($flash['type'], ['success', 'danger', 'warning', 'info'], true)
                    ? $flash['type']
                    : 'info';
                $icon = match ($type) {
                    'success' => 'bi-check-circle',
                    'danger' => 'bi-exclamation-octagon',
                    'warning' => 'bi-exclamation-triangle',
                    default => 'bi-info-circle',
                };
                ?>
                <div class="alert alert-<?= e($type) ?> alert-dismissible fade show shadow-sm" role="alert">
                    <i class="bi <?= e($icon) ?> me-2" aria-hidden="true"></i><?= e($flash['message']) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="关闭"></button>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?= $content ?? '' ?>
</main>

<footer class="site-footer">
    <div class="container py-5">
        <div class="row g-4">
            <div class="col-lg-5">
                <a class="brand-mark footer-brand" href="<?= e(url('/')) ?>">
                    <span class="brand-dot"></span>TechHub
                </a>
                <p class="footer-copy mt-3 mb-0">
                    高校技术团队展示与管理平台，集中呈现团队、成员、项目、技术文章、公告与课程资料。
                </p>
                <div class="footer-note mt-3">
                    <i class="bi bi-info-circle me-1" aria-hidden="true"></i>
                    当前页面包含明确标注的示例数据，正式团队资料待替换。
                </div>
            </div>
            <div class="col-6 col-lg-2">
                <h2 class="footer-title">浏览</h2>
                <ul class="footer-links">
                    <li><a href="<?= e(url('/team')) ?>">团队介绍</a></li>
                    <li><a href="<?= e(url('/members')) ?>">团队成员</a></li>
                    <li><a href="<?= e(url('/projects')) ?>">项目成果</a></li>
                    <li><a href="<?= e(url('/articles')) ?>">技术文章</a></li>
                </ul>
            </div>
            <div class="col-6 col-lg-2">
                <h2 class="footer-title">互动</h2>
                <ul class="footer-links">
                    <li><a href="<?= e(url('/announcements')) ?>">团队公告</a></li>
                    <li><a href="<?= e(url('/messages')) ?>">留言板</a></li>
                    <li><a href="<?= e(url('/downloads?type=team')) ?>">团队作业</a></li>
                    <li><a href="<?= e(url('/downloads?type=personal')) ?>">个人作业</a></li>
                </ul>
            </div>
            <div class="col-lg-3">
                <h2 class="footer-title">账号</h2>
                <ul class="footer-links">
                    <?php if ($currentUser !== null): ?>
                        <li><a href="<?= e(url('/profile')) ?>">个人资料</a></li>
                        <?php if (Auth::can('dashboard.view')): ?>
                            <li><a href="<?= e(url('/admin')) ?>">管理后台</a></li>
                        <?php endif; ?>
                    <?php else: ?>
                        <li><a href="<?= e(url('/login')) ?>">用户登录</a></li>
                        <li><span>不开放公开注册</span></li>
                    <?php endif; ?>
                </ul>
                <div class="footer-note mt-3">© <?= e(date('Y')) ?> TechHub</div>
            </div>
        </div>
    </div>
</footer>

<script src="<?= e(asset('js/bootstrap.bundle.min.js')) ?>" defer></script>
<script src="<?= e(asset('js/app.js')) ?>" defer></script>
</body>
</html>
