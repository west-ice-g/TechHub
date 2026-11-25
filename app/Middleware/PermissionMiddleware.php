<?php
/**
 * TechHub - 高校技术团队展示与管理平台
 *
 * 文件：app/Middleware/PermissionMiddleware.php
 * 模块：安全中间件
 * 职责：按权限码检查后台访问，未授权返回 403。
 * 作者：待团队填写
 * 创建日期：2026-11-25
 * 最后修改：2026-11-25
 */

declare(strict_types=1);

namespace App\Middleware;

use Core\Auth;
use Core\Request;
use Core\Response;
use Core\View;

final class PermissionMiddleware
{
    public static function handle(Request $request, string $permission): ?Response
    {
        if (Auth::can($permission)) {
            return null;
        }

        return Response::html(
            View::render('errors/403', [
                'title' => '403 无权访问',
                'message' => '当前账号没有访问该功能的权限。',
            ], 'layouts/error'),
            403
        );
    }
}
