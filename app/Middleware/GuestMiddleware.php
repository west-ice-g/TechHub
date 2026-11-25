<?php
/**
 * TechHub - 高校技术团队展示与管理平台
 *
 * 文件：app/Middleware/GuestMiddleware.php
 * 模块：安全中间件
 * 职责：已登录用户访问登录页时按前台/后台上下文重定向。
 * 作者：待团队填写
 * 创建日期：2026-11-25
 * 最后修改：2026-11-25
 */

declare(strict_types=1);

namespace App\Middleware;

use Core\Auth;
use Core\Request;
use Core\Response;

final class GuestMiddleware
{
    public static function handle(Request $request): ?Response
    {
        if (!Auth::check()) {
            return null;
        }

        $target = str_starts_with($request->path(), '/admin') ? '/admin' : '/profile';

        return Response::redirect(url($target));
    }
}
