<?php
/**
 * TechHub - 高校技术团队展示与管理平台
 *
 * 文件：core/Controller.php
 * 模块：基础设施
 * 职责：为业务 Controller 提供统一的视图、重定向和 JSON 响应方法。
 * 作者：待团队填写
 * 创建日期：2026-11-25
 * 最后修改：2026-11-25
 */

declare(strict_types=1);

namespace Core;

abstract class Controller
{
    public function __construct(protected readonly Request $request)
    {
    }

    /**
     * @param array<string, mixed> $data
     */
    protected function view(string $template, array $data = [], string $layout = 'layouts/app'): Response
    {
        return Response::html(View::render($template, $data, $layout));
    }

    protected function redirect(string $path, int $status = 302): Response
    {
        if (preg_match('#^https?://#i', $path)) {
            return Response::redirect($path, $status);
        }

        $path = '/' . ltrim($path, '/');

        return Response::redirect(url($path), $status);
    }

    /**
     * @param array<string, mixed> $payload
     */
    protected function json(array $payload, int $status = 200): Response
    {
        return Response::json($payload, $status);
    }
}
