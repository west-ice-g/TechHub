<?php
/**
 * TechHub - 高校技术团队展示与管理平台
 *
 * 文件：core/Request.php
 * 模块：基础设施
 * 职责：封装 HTTP 请求方法、路径、参数、文件与来源信息。
 * 作者：待团队填写
 * 创建日期：2026-11-25
 * 最后修改：2026-11-25
 */

declare(strict_types=1);

namespace Core;

final class Request
{
    /**
     * @param array<string, mixed> $query
     * @param array<string, mixed> $post
     * @param array<string, mixed> $files
     * @param array<string, mixed> $server
     */
    public function __construct(
        private readonly string $method,
        private readonly string $path,
        private readonly array $query = [],
        private readonly array $post = [],
        private readonly array $files = [],
        private readonly array $server = []
    ) {
    }

    public static function capture(): self
    {
        $uri = (string) ($_SERVER['REQUEST_URI'] ?? '/');
        $rawPath = parse_url($uri, PHP_URL_PATH);
        $path = is_string($rawPath) ? rawurldecode($rawPath) : '/';
        $basePath = rtrim((string) config('app.base_path', ''), '/');

        if ($basePath !== '' && str_starts_with($path, $basePath)) {
            $path = substr($path, strlen($basePath));
        }

        $path = '/' . trim((string) preg_replace('#/+#', '/', $path), '/');
        if ($path === '') {
            $path = '/';
        }

        $method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));

        return new self(
            $method,
            $path,
            is_array($_GET) ? $_GET : [],
            is_array($_POST) ? $_POST : [],
            is_array($_FILES) ? $_FILES : [],
            is_array($_SERVER) ? $_SERVER : []
        );
    }

    public function method(): string
    {
        return $this->method;
    }

    public function isPost(): bool
    {
        return $this->method === 'POST';
    }

    public function path(): string
    {
        return $this->path;
    }

    public function query(string $key, mixed $default = null): mixed
    {
        return $this->query[$key] ?? $default;
    }

    public function post(string $key, mixed $default = null): mixed
    {
        return $this->post[$key] ?? $default;
    }

    public function input(string $key, mixed $default = null): mixed
    {
        return $this->post[$key] ?? $this->query[$key] ?? $default;
    }

    /**
     * @return array<string, mixed>
     */
    public function allPost(): array
    {
        return $this->post;
    }

    public function file(string $key): ?array
    {
        $file = $this->files[$key] ?? null;

        return is_array($file) ? $file : null;
    }

    public function ip(): ?string
    {
        $value = $this->server['REMOTE_ADDR'] ?? null;

        return is_string($value) && $value !== '' ? $value : null;
    }

    public function userAgent(): ?string
    {
        $value = $this->server['HTTP_USER_AGENT'] ?? null;
        if (!is_string($value) || $value === '') {
            return null;
        }

        return mb_substr($value, 0, 500);
    }

    public function header(string $name): ?string
    {
        $key = 'HTTP_' . strtoupper(str_replace('-', '_', $name));
        $value = $this->server[$key] ?? null;

        return is_string($value) && $value !== '' ? $value : null;
    }

    public function isSecure(): bool
    {
        $https = $this->server['HTTPS'] ?? '';

        return is_string($https) && $https !== '' && $https !== 'off';
    }
}
