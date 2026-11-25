<?php
/**
 * TechHub - 高校技术团队展示与管理平台
 *
 * 文件：app/Views/layouts/admin.php
 * 模块：后台布局
 * 职责：输出响应式侧边栏、顶部导航、面包屑、用户信息、Flash 和主内容区。
 * 作者：待团队填写
 * 创建日期：2026-11-25
 * 最后修改：2026-11-25
 */

declare(strict_types=1);

use Core\Auth;
use Core\Session;

$flashes = Session::flashes();
$currentUser = $currentUser ?? Auth::user();
$permissions = $permissions ?? Auth::permissions();
$pageTitle = $pageTitle ?? '后台管理';
$activeMenu = $activeMenu ?? '';
$breadcrumbs = $breadcrumbs ?? [['label' => '后台首页', 'url' => '/admin'], ['label' => $pageTitle]];
$currentPath = (string) (parse_url($_SERVER['REQUEST_URI'] ?? '/admin', PHP_URL_PATH) ?: '/admin');
$basePath = rtrim((string) config('app.base_path', ''), '/');
if ($basePath !== '' && str_starts_with($currentPath, $basePath)) {
    $currentPath = substr($currentPath, strlen($basePath)) ?: '/';
}

$can = static fn (string $permission): bool => in_array($permission, $permissions, true);
$isActive = static function (string $path) use ($currentPath): bool {
    return $path === '/admin'
        ? $currentPath === '/admin'
        : str_starts_with($currentPath, $path);
};

$menuGroups = [
    [
        'label' => '概览',
        'items' => [
            ['key' => 'dashboard', 'path' => '/admin', 'label' => '数据统计首页', 'icon' => 'bi-speedometer2', 'permission' => 'dashboard.view'],
        ],
    ],
    [
        'label' => '内容管理',
        'items' => [
            ['key' => 'team', 'path' => '/admin/team', 'label' => '团队信息', 'icon' => 'bi-people', 'permission' => 'team.edit'],
            ['key' => 'members', 'path' => '/admin/members', 'label' => '团队成员', 'icon' => 'bi-person-badge', 'permission' => 'member.manage'],
            ['key' => 'projects', 'path' => '/admin/projects', 'label' => '项目管理', 'icon' => 'bi-kanban', 'permission' => 'project.manage'],
            ['key' => 'categories', 'path' => '/admin/categories', 'label' => '文章分类', 'icon' => 'bi-tags', 'permission' => 'category.manage'],
            ['key' => 'articles', 'path' => '/admin/articles', 'label' => '文章管理', 'icon' => 'bi-journal-text', 'permission' => 'article.manage'],
            ['key' => 'announcements', 'path' => '/admin/announcements', 'label' => '公告管理', 'icon' => 'bi-megaphone', 'permission' => 'announcement.manage'],
        ],
    ],
    [
        'label' => '互动与资源',
        'items' => [
            ['key' => 'messages', 'path' => '/admin/messages', 'label' => '留言管理', 'icon' => 'bi-chat-square-text', 'permission' => 'message.moderate'],
            ['key' => 'files', 'path' => '/admin/files', 'label' => '文件管理', 'icon' => 'bi-folder2-open', 'permission' => 'file.manage'],
        ],
    ],
    [
        'label' => '系统管理',
        'items' => [
            ['key' => 'users', 'path' => '/admin/users', 'label' => '用户管理', 'icon' => 'bi-people-fill', 'permission' => 'user.manage'],
            ['key' => 'roles', 'path' => '/admin/roles', 'label' => '角色权限', 'icon' => 'bi-shield-lock', 'permission' => 'role.manage'],
            ['key' => 'activity', 'path' => '/admin/activity', 'label' => '操作日志', 'icon' => 'bi-clock-history', 'permission' => 'activity.view'],
        ],
    ],
];
?>
<!doctype html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <title><?= e($pageTitle) ?> · TechHub 管理后台</title>
    <link rel="icon" href="<?= e(url('/favicon.ico')) ?>" type="image/x-icon">
    <link rel="stylesheet" href="<?= e(asset('css/bootstrap.min.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset('css/bootstrap-icons.min.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset('css/admin.css')) ?>">
</head>
<body class="admin-body">
<div class="admin-app">
    <aside class="admin-sidebar offcanvas-lg offcanvas-start" tabindex="-1" id="adminSidebar" aria-labelledby="adminSidebarLabel">
        <div class="offcanvas-header admin-sidebar-mobile-head">
            <span class="admin-brand" id="adminSidebarLabel"><span class="brand-dot"></span>TechHub</span>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" data-bs-target="#adminSidebar" aria-label="关闭导航"></button>
        </div>
        <div class="offcanvas-body admin-sidebar-body">
            <a class="admin-brand d-none d-lg-flex" href="<?= e(url('/admin')) ?>">
                <span class="brand-dot"></span>
                <span>TechHub<small>管理后台</small></span>
            </a>

            <?php if ($currentUser !== null): ?>
                <div class="admin-user-card">
                    <span class="admin-avatar" aria-hidden="true"><?= e(mb_strtoupper(mb_substr((string) $currentUser['display_name'], 0, 1))) ?></span>
                    <span class="admin-user-meta">
                        <strong><?= e($currentUser['display_name']) ?></strong>
                        <small><?= e($currentUser['role_name']) ?></small>
                    </span>
                </div>
            <?php endif; ?>

            <nav class="admin-nav" aria-label="后台主导航">
                <?php foreach ($menuGroups as $group): ?>
                    <?php
                    $visibleItems = array_values(array_filter(
                        $group['items'],
                        static function (array $item) use ($can): bool {
                            if (in_array($item['key'], ['users', 'roles'], true) && !\Core\Auth::isSuperAdmin()) {
                                return false;
                            }

                            return $can($item['permission']);
                        }
                    ));
                    ?>
                    <?php if ($visibleItems !== []): ?>
                        <div class="admin-nav-group">
                            <div class="admin-nav-label"><?= e($group['label']) ?></div>
                            <?php foreach ($visibleItems as $item): ?>
                                <a class="admin-nav-link <?= ($activeMenu === $item['key'] || $isActive($item['path'])) ? 'active' : '' ?>"
                                   href="<?= e(url($item['path'])) ?>"
                                   <?= $activeMenu === $item['key'] ? 'aria-current="page"' : '' ?>>
                                    <i class="bi <?= e($item['icon']) ?>" aria-hidden="true"></i>
                                    <span><?= e($item['label']) ?></span>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </nav>

            <div class="admin-sidebar-foot">
                <a href="<?= e(url('/')) ?>" target="_blank" rel="noopener">
                    <i class="bi bi-box-arrow-up-right me-1" aria-hidden="true"></i>查看前台网站
                </a>
                <span>TechHub 课程设计 · 本地环境</span>
            </div>
        </div>
    </aside>

    <div class="admin-workspace">
        <header class="admin-topbar">
            <div class="d-flex align-items-center gap-2 gap-lg-3 min-w-0">
                <button class="btn btn-sm btn-outline-light d-lg-none" type="button"
                        data-bs-toggle="offcanvas" data-bs-target="#adminSidebar"
                        aria-controls="adminSidebar" aria-label="打开后台导航">
                    <i class="bi bi-list" aria-hidden="true"></i>
                </button>
                <a class="admin-topbar-brand d-lg-none" href="<?= e(url('/admin')) ?>">TechHub</a>
                <div class="admin-topbar-title d-none d-lg-block">
                    <span class="admin-topbar-eyebrow">管理后台</span>
                    <strong><?= e($pageTitle) ?></strong>
                </div>
            </div>

            <div class="d-flex align-items-center gap-2">
                <a class="btn btn-sm btn-outline-light d-none d-sm-inline-flex" href="<?= e(url('/')) ?>" target="_blank" rel="noopener">
                    <i class="bi bi-globe2 me-1" aria-hidden="true"></i>前台
                </a>
                <?php if ($currentUser !== null): ?>
                    <div class="dropdown">
                        <button class="btn btn-sm btn-light dropdown-toggle" type="button"
                                data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-person-circle me-1" aria-hidden="true"></i>
                            <span class="d-none d-md-inline"><?= e($currentUser['display_name']) ?></span>
                            <span class="d-md-none">账号</span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                            <li class="px-3 py-2 small text-secondary">
                                <div class="fw-semibold text-dark"><?= e($currentUser['display_name']) ?></div>
                                <div><?= e($currentUser['role_name']) ?></div>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="<?= e(url('/profile')) ?>"><i class="bi bi-person-vcard me-2" aria-hidden="true"></i>个人资料</a></li>
                            <li>
                                <form method="post" action="<?= e(url('/admin/logout')) ?>" class="m-0" data-confirm="确定退出后台登录吗？">
                                    <?= csrf_field() ?>
                                    <button class="dropdown-item" type="submit"><i class="bi bi-box-arrow-right me-2" aria-hidden="true"></i>退出登录</button>
                                </form>
                            </li>
                        </ul>
                    </div>
                <?php endif; ?>
            </div>
        </header>

        <div class="admin-breadcrumb-bar">
            <nav aria-label="面包屑">
                <ol class="breadcrumb mb-0">
                    <?php foreach ($breadcrumbs as $index => $crumb): ?>
                        <?php $last = $index === count($breadcrumbs) - 1; ?>
                        <li class="breadcrumb-item <?= $last ? 'active' : '' ?>" <?= $last ? 'aria-current="page"' : '' ?>>
                            <?php if (!$last && isset($crumb['url'])): ?>
                                <a href="<?= e(url($crumb['url'])) ?>"><?= e($crumb['label']) ?></a>
                            <?php else: ?>
                                <?= e($crumb['label']) ?>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ol>
            </nav>
            <span class="admin-breadcrumb-date"><?= e(date('Y-m-d')) ?></span>
        </div>

        <main class="admin-main" id="main-content">
            <?php if ($flashes !== []): ?>
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
                    <div class="alert alert-<?= e($type) ?> alert-dismissible fade show admin-flash" role="alert">
                        <i class="bi <?= e($icon) ?> me-2" aria-hidden="true"></i><?= e($flash['message']) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="关闭"></button>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>

            <?= $content ?? '' ?>
        </main>

        <footer class="admin-footer">
            <span>TechHub 管理后台</span>
            <span>PHP 8.2 · MySQL 8.0 · 原生 MVC</span>
        </footer>
    </div>
</div>

<script src="<?= e(asset('js/bootstrap.bundle.min.js')) ?>" defer></script>
<script src="<?= e(asset('js/app.js')) ?>" defer></script>
</body>
</html>
