<?php
/**
 * TechHub - 高校技术团队展示与管理平台
 *
 * 文件：app/Middleware/AuthMiddleware.php
 * 模块：安全中间件
 * 职责：未登录访问受保护页面时记录目标地址，并跳转对应登录页。
 * 作者：待团队填写
 * 创建日期：2026-11-25
 * 最后修改：2026-11-25
 */

declare(strict_types=1);

namespace App\Middleware;

use Core\Auth;
use Core\Request;
use Core\Response;
use Core\Session;

final class AuthMiddleware
{
    public static function handle(Request $request): ?Response
    {
        if (Auth::check()) {
            return null;
        }

        $path = $request->path();
        $isAdmin = str_starts_with($path, '/admin');

        if ($path !== '/admin/login' && $path !== '/login') {
            Session::put('intended_url', $path);
        }

        Session::flash('error', '请先登录后再访问该页面。');

        return Response::redirect(url($isAdmin ? '/admin/login' : '/login'));
    }
}
