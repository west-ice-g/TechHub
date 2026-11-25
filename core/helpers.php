<?php
/**
 * TechHub - 高校技术团队展示与管理平台
 *
 * 文件：core/helpers.php
 * 模块：基础设施
 * 职责：提供配置读取、URL 生成、HTML 转义、CSRF 字段和 HTTP 中止等公共函数。
 * 作者：待团队填写
 * 创建日期：2026-11-25
 * 最后修改：2026-11-25
 */

declare(strict_types=1);

use Core\Config;
use Core\Csrf;
use Core\Env;
use Core\HttpException;

if (!function_exists('env')) {
    function env(string $key, mixed $default = null): mixed
    {
        return Env::get($key, $default);
    }
}

if (!function_exists('config')) {
    function config(string $key, mixed $default = null): mixed
    {
        return Config::get($key, $default);
    }
}

if (!function_exists('e')) {
    /**
     * 统一 HTML 转义。所有动态文本进入视图时必须经过此函数。
     */
    function e(mixed $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

if (!function_exists('url')) {
    /**
     * 生成站点根路径 URL，支持通过 APP_BASE_PATH 部署在子目录。
     */
    function url(string $path = ''): string
    {
        if (preg_match('#^https?://#i', $path)) {
            return $path;
        }

        $basePath = rtrim((string) config('app.base_path', ''), '/');
        $path = '/' . ltrim($path, '/');

        return $basePath === '' ? $path : $basePath . $path;
    }
}

if (!function_exists('asset')) {
    function asset(string $path): string
    {
        return url('assets/' . ltrim($path, '/'));
    }
}

if (!function_exists('csrf_field')) {
    function csrf_field(): string
    {
        return '<input type="hidden" name="_token" value="' . e(Csrf::token()) . '">';
    }
}

if (!function_exists('abort')) {
    function abort(int $status, string $message = ''): never
    {
        throw new HttpException($status, $message);
    }
}

if (!function_exists('format_datetime')) {
    function format_datetime(?string $value, string $format = 'Y-m-d H:i'): string
    {
        if ($value === null || trim($value) === '') {
            return '—';
        }

        try {
            return (new DateTimeImmutable($value))->format($format);
        } catch (Exception) {
            return '—';
        }
    }
}

if (!function_exists('media_url')) {
    /**
     * 只允许项目内 assets/ 或 uploads/ 下的相对媒体路径。
     */
    function media_url(?string $path): ?string
    {
        if ($path === null || trim($path) === '') {
            return null;
        }

        $path = ltrim(str_replace('\\', '/', trim($path)), '/');
        if (
            str_contains($path, '..')
            || !preg_match('#^(assets|uploads)/[A-Za-z0-9._/-]+$#', $path)
        ) {
            return null;
        }

        return url($path);
    }
}

if (!function_exists('safe_external_url')) {
    function safe_external_url(?string $url): ?string
    {
        if ($url === null || trim($url) === '') {
            return null;
        }

        $url = trim($url);
        if (!preg_match('#^https?://#i', $url) || filter_var($url, FILTER_VALIDATE_URL) === false) {
            return null;
        }

        return $url;
    }
}

if (!function_exists('split_tags')) {
    /**
     * @return array<int, string>
     */
    function split_tags(?string $value): array
    {
        if ($value === null || trim($value) === '') {
            return [];
        }

        $parts = preg_split('/[,，、\/]+/u', $value) ?: [];
        $tags = [];
        foreach ($parts as $part) {
            $part = trim($part);
            if ($part !== '' && !in_array($part, $tags, true)) {
                $tags[] = $part;
            }
        }

        return $tags;
    }
}

if (!function_exists('format_file_size')) {
    function format_file_size(int $bytes): string
    {
        if ($bytes < 1024) {
            return $bytes . ' B';
        }

        $units = ['KB', 'MB', 'GB'];
        $size = $bytes / 1024;
        $unitIndex = 0;

        while ($size >= 1024 && $unitIndex < count($units) - 1) {
            $size /= 1024;
            $unitIndex++;
        }

        return number_format($size, 1) . ' ' . $units[$unitIndex];
    }
}

if (!function_exists('excerpt_text')) {
    function excerpt_text(string $value, int $length = 120): string
    {
        $text = trim((string) preg_replace('/\s+/u', ' ', strip_tags($value)));
        $length = max(20, $length);

        return mb_strlen($text, 'UTF-8') > $length
            ? mb_substr($text, 0, $length, 'UTF-8') . '…'
            : $text;
    }
}

if (!function_exists('url_with_query')) {
    /**
     * @param array<string, mixed> $query
     */
    function url_with_query(string $path, array $query = []): string
    {
        $query = array_filter(
            $query,
            static fn (mixed $value): bool => $value !== null && $value !== ''
        );
        $encoded = http_build_query($query, '', '&', PHP_QUERY_RFC3986);

        return $encoded === '' ? url($path) : url($path) . '?' . $encoded;
    }
}
