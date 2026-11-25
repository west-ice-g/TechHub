<?php
/**
 * TechHub - 高校技术团队展示与管理平台
 *
 * 文件：core/Router.php
 * 模块：基础设施
 * 职责：解析 GET/POST 路由、执行登录/权限/CSRF 中间件并分发 Controller。
 * 作者：待团队填写
 * 创建日期：2026-11-25
 * 最后修改：2026-11-25
 */

declare(strict_types=1);

namespace Core;

use App\Middleware\AuthMiddleware;
use App\Middleware\CsrfMiddleware;
use App\Middleware\GuestMiddleware;
use App\Middleware\PermissionMiddleware;

final class Router
{
    /** @var array<int, array<string, mixed>> */
    private array $routes = [];

    /**
     * @param array{0: class-string, 1: string} $handler
     * @param array<string, mixed> $options
     */
    public function get(string $path, array $handler, array $options = []): void
    {
        $this->add('GET', $path, $handler, $options);
    }

    /**
     * @param array{0: class-string, 1: string} $handler
     * @param array<string, mixed> $options
     */
    public function post(string $path, array $handler, array $options = []): void
    {
        $this->add('POST', $path, $handler, $options);
    }

    /**
     * @param array{0: class-string, 1: string} $handler
     * @param array<string, mixed> $options
     */
    private function add(string $method, string $path, array $handler, array $options): void
    {
        $path = $this->normalizePath($path);
        [$regex, $parameterNames] = $this->compile($path);

        $this->routes[] = [
            'method' => $method,
            'path' => $path,
            'regex' => $regex,
            'parameters' => $parameterNames,
            'handler' => $handler,
            'options' => $options,
        ];
    }

    public function dispatch(Request $request): Response
    {
        $allowedMethods = [];
        $isHeadRequest = $request->method() === 'HEAD';
        $matchMethod = $isHeadRequest ? 'GET' : $request->method();

        foreach ($this->routes as $route) {
            if (!preg_match($route['regex'], $request->path(), $matches)) {
                continue;
            }

            if ($route['method'] !== $matchMethod) {
                $allowedMethods[] = $route['method'];
                continue;
            }

            array_shift($matches);
            $parameters = [];
            foreach ($route['parameters'] as $index => $name) {
                $parameters[$name] = $matches[$index] ?? null;
            }

            $response = $this->runRoute($route, $request, array_values($parameters));

            return $isHeadRequest ? $response->withoutBody() : $response;
        }

        if ($allowedMethods !== []) {
            $allowed = array_values(array_unique($allowedMethods));

            return $this->errorResponse(405, '405 方法不允许', '该地址不支持当前请求方式。')
                ->withHeader('Allow', implode(', ', $allowed));
        }

        return $this->errorResponse(404, '404 页面不存在', '你访问的页面不存在或已被移动。');
    }

    /**
     * @param array<string, mixed> $route
     * @param array<int, mixed> $parameters
     */
    private function runRoute(array $route, Request $request, array $parameters): Response
    {
        $options = $route['options'];

        if (!empty($options['guest'])) {
            $response = GuestMiddleware::handle($request);
            if ($response !== null) {
                return $response;
            }
        }

        if (!empty($options['auth'])) {
            $response = AuthMiddleware::handle($request);
            if ($response !== null) {
                return $response;
            }
        }

        if (!empty($options['csrf']) && $request->isPost()) {
            $response = CsrfMiddleware::handle($request);
            if ($response !== null) {
                return $response;
            }
        }

        if (!empty($options['permission'])) {
            $response = PermissionMiddleware::handle($request, (string) $options['permission']);
            if ($response !== null) {
                return $response;
            }
        }

        [$class, $method] = $route['handler'];
        if (!class_exists($class)) {
            throw new \RuntimeException('Controller 不存在：' . $class);
        }

        $controller = new $class($request);
        if (!method_exists($controller, $method)) {
            throw new \RuntimeException('Controller 方法不存在：' . $class . '::' . $method);
        }

        $response = $controller->{$method}(...$parameters);
        if (!$response instanceof Response) {
            throw new \RuntimeException('Controller 必须返回 Response 实例');
        }

        return $response;
    }

    private function errorResponse(int $status, string $title, string $message): Response
    {
        $view = match ($status) {
            403 => 'errors/403',
            405 => 'errors/405',
            default => 'errors/404',
        };

        return Response::html(
            View::render($view, ['title' => $title, 'message' => $message], 'layouts/error'),
            $status
        );
    }

    private function normalizePath(string $path): string
    {
        $path = '/' . trim($path, '/');

        return $path === '/' ? '/' : rtrim($path, '/');
    }

    /**
     * @return array{0: string, 1: array<int, string>}
     */
    private function compile(string $path): array
    {
        if ($path === '/') {
            return ['#^/$#', []];
        }

        $segments = explode('/', trim($path, '/'));
        $parts = [];
        $names = [];

        foreach ($segments as $segment) {
            if (preg_match('/^\{([a-zA-Z_][a-zA-Z0-9_]*)\}$/', $segment, $match)) {
                $name = $match[1];
                $names[] = $name;
                $parts[] = $name === 'id' ? '([0-9]+)' : '([A-Za-z0-9_-]+)';
                continue;
            }

            $parts[] = preg_quote($segment, '#');
        }

        return ['#^/' . implode('/', $parts) . '$#', $names];
    }
}
