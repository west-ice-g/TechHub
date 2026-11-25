<?php
/**
 * TechHub - 高校技术团队展示与管理平台
 *
 * 文件：config/routes.php
 * 模块：路由配置
 * 职责：声明前台内容、认证、个人资料和后台管理全部路由。
 * 作者：待团队填写
 * 创建日期：2026-11-25
 * 最后修改：2026-11-25
 */

declare(strict_types=1);

use App\Controllers\Admin\ActivityController;
use App\Controllers\Admin\AnnouncementController as AdminAnnouncementController;
use App\Controllers\Admin\ArticleController as AdminArticleController;
use App\Controllers\Admin\CategoryController as AdminCategoryController;
use App\Controllers\Admin\DashboardController;
use App\Controllers\Admin\FileController as AdminFileController;
use App\Controllers\Admin\MemberController as AdminMemberController;
use App\Controllers\Admin\MessageController as AdminMessageController;
use App\Controllers\Admin\ProjectController as AdminProjectController;
use App\Controllers\Admin\RoleController;
use App\Controllers\Admin\TeamController as AdminTeamController;
use App\Controllers\Admin\UserController;
use App\Controllers\AnnouncementController;
use App\Controllers\ArticleController;
use App\Controllers\AuthController;
use App\Controllers\DownloadController;
use App\Controllers\HomeController;
use App\Controllers\MemberController;
use App\Controllers\MessageController;
use App\Controllers\ProfileController;
use App\Controllers\ProjectController;
use App\Controllers\TeamController;
use Core\Router;

return static function (Router $router): void {
    $router->get('/', [HomeController::class, 'index']);
    $router->get('/team', [TeamController::class, 'index']);

    $router->get('/members', [MemberController::class, 'index']);
    $router->get('/members/{id}', [MemberController::class, 'show']);

    $router->get('/projects', [ProjectController::class, 'index']);
    $router->get('/projects/{slug}', [ProjectController::class, 'show']);

    $router->get('/articles', [ArticleController::class, 'index']);
    $router->get('/articles/{slug}', [ArticleController::class, 'show']);

    $router->get('/announcements', [AnnouncementController::class, 'index']);
    $router->get('/announcements/{id}', [AnnouncementController::class, 'show']);

    $router->get('/messages', [MessageController::class, 'index']);
    $router->post('/messages', [MessageController::class, 'store'], ['csrf' => true]);
    $router->get('/messages/{id}', [MessageController::class, 'show']);

    $router->get('/downloads', [DownloadController::class, 'index']);
    $router->get('/downloads/{id}/file', [DownloadController::class, 'download']);

    $router->get('/login', [AuthController::class, 'showFrontLogin'], ['guest' => true]);
    $router->post('/login', [AuthController::class, 'frontLogin'], ['guest' => true, 'csrf' => true]);
    $router->post('/logout', [AuthController::class, 'logout'], ['auth' => true, 'csrf' => true]);
    $router->get('/profile', [ProfileController::class, 'index'], ['auth' => true]);

    $router->get('/admin/login', [AuthController::class, 'showLogin'], ['guest' => true]);
    $router->post('/admin/login', [AuthController::class, 'login'], ['guest' => true, 'csrf' => true]);
    $router->post('/admin/logout', [AuthController::class, 'logout'], ['auth' => true, 'csrf' => true]);

    $router->get('/admin', [DashboardController::class, 'index'], [
        'auth' => true,
        'permission' => 'dashboard.view',
    ]);
    $router->get('/admin/api/stats', [DashboardController::class, 'stats'], [
        'auth' => true,
        'permission' => 'stats.view',
    ]);

    $router->get('/admin/team', [AdminTeamController::class, 'index'], [
        'auth' => true,
        'permission' => 'team.edit',
    ]);
    $router->post('/admin/team', [AdminTeamController::class, 'update'], [
        'auth' => true,
        'permission' => 'team.edit',
        'csrf' => true,
    ]);

    $router->get('/admin/members', [AdminMemberController::class, 'index'], [
        'auth' => true,
        'permission' => 'member.manage',
    ]);
    $router->get('/admin/members/create', [AdminMemberController::class, 'create'], [
        'auth' => true,
        'permission' => 'member.manage',
    ]);
    $router->post('/admin/members', [AdminMemberController::class, 'store'], [
        'auth' => true,
        'permission' => 'member.manage',
        'csrf' => true,
    ]);
    $router->get('/admin/members/{id}/edit', [AdminMemberController::class, 'edit'], [
        'auth' => true,
        'permission' => 'member.manage',
    ]);
    $router->post('/admin/members/{id}/edit', [AdminMemberController::class, 'update'], [
        'auth' => true,
        'permission' => 'member.manage',
        'csrf' => true,
    ]);
    $router->post('/admin/members/{id}/remove', [AdminMemberController::class, 'remove'], [
        'auth' => true,
        'permission' => 'member.manage',
        'csrf' => true,
    ]);
    $router->post('/admin/members/{id}/delete', [AdminMemberController::class, 'destroy'], [
        'auth' => true,
        'permission' => 'member.manage',
        'csrf' => true,
    ]);

    $router->get('/admin/projects', [AdminProjectController::class, 'index'], [
        'auth' => true,
        'permission' => 'project.manage',
    ]);
    $router->get('/admin/projects/create', [AdminProjectController::class, 'create'], [
        'auth' => true,
        'permission' => 'project.manage',
    ]);
    $router->post('/admin/projects', [AdminProjectController::class, 'store'], [
        'auth' => true,
        'permission' => 'project.manage',
        'csrf' => true,
    ]);
    $router->get('/admin/projects/{id}/edit', [AdminProjectController::class, 'edit'], [
        'auth' => true,
        'permission' => 'project.manage',
    ]);
    $router->post('/admin/projects/{id}/edit', [AdminProjectController::class, 'update'], [
        'auth' => true,
        'permission' => 'project.manage',
        'csrf' => true,
    ]);
    $router->post('/admin/projects/{id}/visibility', [AdminProjectController::class, 'visibility'], [
        'auth' => true,
        'permission' => 'project.manage',
        'csrf' => true,
    ]);
    $router->post('/admin/projects/{id}/delete', [AdminProjectController::class, 'destroy'], [
        'auth' => true,
        'permission' => 'project.manage',
        'csrf' => true,
    ]);

    $router->get('/admin/categories', [AdminCategoryController::class, 'index'], [
        'auth' => true,
        'permission' => 'category.manage',
    ]);
    $router->get('/admin/categories/create', [AdminCategoryController::class, 'create'], [
        'auth' => true,
        'permission' => 'category.manage',
    ]);
    $router->post('/admin/categories', [AdminCategoryController::class, 'store'], [
        'auth' => true,
        'permission' => 'category.manage',
        'csrf' => true,
    ]);
    $router->get('/admin/categories/{id}/edit', [AdminCategoryController::class, 'edit'], [
        'auth' => true,
        'permission' => 'category.manage',
    ]);
    $router->post('/admin/categories/{id}/edit', [AdminCategoryController::class, 'update'], [
        'auth' => true,
        'permission' => 'category.manage',
        'csrf' => true,
    ]);
    $router->post('/admin/categories/{id}/status', [AdminCategoryController::class, 'status'], [
        'auth' => true,
        'permission' => 'category.manage',
        'csrf' => true,
    ]);
    $router->post('/admin/categories/{id}/delete', [AdminCategoryController::class, 'destroy'], [
        'auth' => true,
        'permission' => 'category.manage',
        'csrf' => true,
    ]);

    $router->get('/admin/articles', [AdminArticleController::class, 'index'], [
        'auth' => true,
        'permission' => 'article.manage',
    ]);
    $router->get('/admin/articles/create', [AdminArticleController::class, 'create'], [
        'auth' => true,
        'permission' => 'article.manage',
    ]);
    $router->post('/admin/articles', [AdminArticleController::class, 'store'], [
        'auth' => true,
        'permission' => 'article.manage',
        'csrf' => true,
    ]);
    $router->get('/admin/articles/{id}/edit', [AdminArticleController::class, 'edit'], [
        'auth' => true,
        'permission' => 'article.manage',
    ]);
    $router->post('/admin/articles/{id}/edit', [AdminArticleController::class, 'update'], [
        'auth' => true,
        'permission' => 'article.manage',
        'csrf' => true,
    ]);
    $router->post('/admin/articles/{id}/status', [AdminArticleController::class, 'status'], [
        'auth' => true,
        'permission' => 'article.manage',
        'csrf' => true,
    ]);
    $router->post('/admin/articles/{id}/delete', [AdminArticleController::class, 'destroy'], [
        'auth' => true,
        'permission' => 'article.manage',
        'csrf' => true,
    ]);

    $router->get('/admin/announcements', [AdminAnnouncementController::class, 'index'], [
        'auth' => true,
        'permission' => 'announcement.manage',
    ]);
    $router->get('/admin/announcements/create', [AdminAnnouncementController::class, 'create'], [
        'auth' => true,
        'permission' => 'announcement.manage',
    ]);
    $router->post('/admin/announcements', [AdminAnnouncementController::class, 'store'], [
        'auth' => true,
        'permission' => 'announcement.manage',
        'csrf' => true,
    ]);
    $router->get('/admin/announcements/{id}/edit', [AdminAnnouncementController::class, 'edit'], [
        'auth' => true,
        'permission' => 'announcement.manage',
    ]);
    $router->post('/admin/announcements/{id}/edit', [AdminAnnouncementController::class, 'update'], [
        'auth' => true,
        'permission' => 'announcement.manage',
        'csrf' => true,
    ]);
    $router->post('/admin/announcements/{id}/status', [AdminAnnouncementController::class, 'status'], [
        'auth' => true,
        'permission' => 'announcement.manage',
        'csrf' => true,
    ]);
    $router->post('/admin/announcements/{id}/delete', [AdminAnnouncementController::class, 'destroy'], [
        'auth' => true,
        'permission' => 'announcement.manage',
        'csrf' => true,
    ]);

    $router->get('/admin/messages', [AdminMessageController::class, 'index'], [
        'auth' => true,
        'permission' => 'message.moderate',
    ]);
    $router->get('/admin/messages/{id}', [AdminMessageController::class, 'show'], [
        'auth' => true,
        'permission' => 'message.moderate',
    ]);
    $router->post('/admin/messages/{id}/status', [AdminMessageController::class, 'status'], [
        'auth' => true,
        'permission' => 'message.moderate',
        'csrf' => true,
    ]);
    $router->post('/admin/messages/{id}/reply', [AdminMessageController::class, 'reply'], [
        'auth' => true,
        'permission' => 'message.moderate',
        'csrf' => true,
    ]);
    $router->post('/admin/messages/{id}/delete', [AdminMessageController::class, 'destroy'], [
        'auth' => true,
        'permission' => 'message.moderate',
        'csrf' => true,
    ]);

    $router->get('/admin/files', [AdminFileController::class, 'index'], [
        'auth' => true,
        'permission' => 'file.manage',
    ]);
    $router->get('/admin/files/upload', [AdminFileController::class, 'upload'], [
        'auth' => true,
        'permission' => 'file.manage',
    ]);
    $router->post('/admin/files/upload', [AdminFileController::class, 'store'], [
        'auth' => true,
        'permission' => 'file.manage',
        'csrf' => true,
    ]);
    $router->get('/admin/files/{id}/edit', [AdminFileController::class, 'edit'], [
        'auth' => true,
        'permission' => 'file.manage',
    ]);
    $router->post('/admin/files/{id}/edit', [AdminFileController::class, 'update'], [
        'auth' => true,
        'permission' => 'file.manage',
        'csrf' => true,
    ]);
    $router->post('/admin/files/{id}/status', [AdminFileController::class, 'status'], [
        'auth' => true,
        'permission' => 'file.manage',
        'csrf' => true,
    ]);
    $router->post('/admin/files/{id}/visibility', [AdminFileController::class, 'visibility'], [
        'auth' => true,
        'permission' => 'file.manage',
        'csrf' => true,
    ]);
    $router->get('/admin/files/{id}/download', [AdminFileController::class, 'download'], [
        'auth' => true,
        'permission' => 'file.manage',
    ]);
    $router->post('/admin/files/{id}/delete', [AdminFileController::class, 'destroy'], [
        'auth' => true,
        'permission' => 'file.manage',
        'csrf' => true,
    ]);

    $router->get('/admin/users', [UserController::class, 'index'], [
        'auth' => true,
        'permission' => 'user.manage',
    ]);
    $router->get('/admin/users/create', [UserController::class, 'create'], [
        'auth' => true,
        'permission' => 'user.manage',
    ]);
    $router->post('/admin/users', [UserController::class, 'store'], [
        'auth' => true,
        'permission' => 'user.manage',
        'csrf' => true,
    ]);
    $router->get('/admin/users/{id}/edit', [UserController::class, 'edit'], [
        'auth' => true,
        'permission' => 'user.manage',
    ]);
    $router->post('/admin/users/{id}/edit', [UserController::class, 'update'], [
        'auth' => true,
        'permission' => 'user.manage',
        'csrf' => true,
    ]);
    $router->post('/admin/users/{id}/status', [UserController::class, 'status'], [
        'auth' => true,
        'permission' => 'user.manage',
        'csrf' => true,
    ]);
    $router->post('/admin/users/{id}/delete', [UserController::class, 'destroy'], [
        'auth' => true,
        'permission' => 'user.manage',
        'csrf' => true,
    ]);

    $router->get('/admin/roles', [RoleController::class, 'index'], [
        'auth' => true,
        'permission' => 'role.manage',
    ]);
    $router->get('/admin/roles/create', [RoleController::class, 'create'], [
        'auth' => true,
        'permission' => 'role.manage',
    ]);
    $router->post('/admin/roles', [RoleController::class, 'store'], [
        'auth' => true,
        'permission' => 'role.manage',
        'csrf' => true,
    ]);
    $router->get('/admin/roles/{id}/edit', [RoleController::class, 'edit'], [
        'auth' => true,
        'permission' => 'role.manage',
    ]);
    $router->post('/admin/roles/{id}/edit', [RoleController::class, 'update'], [
        'auth' => true,
        'permission' => 'role.manage',
        'csrf' => true,
    ]);
    $router->post('/admin/roles/{id}/permissions', [RoleController::class, 'permissions'], [
        'auth' => true,
        'permission' => 'role.manage',
        'csrf' => true,
    ]);
    $router->post('/admin/roles/{id}/delete', [RoleController::class, 'destroy'], [
        'auth' => true,
        'permission' => 'role.manage',
        'csrf' => true,
    ]);

    $router->get('/admin/activity', [ActivityController::class, 'index'], [
        'auth' => true,
        'permission' => 'activity.view',
    ]);
};
