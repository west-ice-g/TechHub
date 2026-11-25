<?php
/**
 * TechHub - 高校技术团队展示与管理平台
 *
 * 文件：app/Middleware/CsrfMiddleware.php
 * 模块：安全中间件
 * 职责：对所有声明 csrf 的 POST 请求校验 Token，失败返回标准 403。
 * 作者：待团队填写
 * 创建日期：2026-11-25
 * 最后修改：2026-11-25
 */

declare(strict_types=1);

namespace App\Middleware;

use Core\Csrf;
use Core\Logger;
use Core\Request;
use Core\Response;
use Core\View;

final class CsrfMiddleware
{
    public static function handle(Request $request): ?Response
    {
        $token = $request->post('_token');
        $token = is_string($token) ? $token : null;

        if (Csrf::validate($token)) {
            return null;
        }

        Logger::warning('CSRF 校验失败', [
            'path' => $request->path(),
            'ip' => $request->ip(),
        ]);

        return Response::html(
            View::render('errors/403', [
                'title' => '403 请求被拒绝',
                'message' => '页面安全令牌已失效，请刷新页面后重新提交。',
            ], 'layouts/error'),
            403
        );
    }
}
