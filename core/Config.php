<?php
/**
 * TechHub - 高校技术团队展示与管理平台
 *
 * 文件：core/Config.php
 * 模块：基础设施
 * 职责：加载配置数组，并提供点号路径读取。
 * 作者：待团队填写
 * 创建日期：2026-11-25
 * 最后修改：2026-11-25
 */

declare(strict_types=1);

namespace Core;

final class Config
{
    /** @var array<string, mixed> */
    private static array $items = [];

    public static function load(string $file, string $namespace): void
    {
        $data = require $file;
        if (!is_array($data)) {
            throw new \RuntimeException('配置文件必须返回数组：' . $file);
        }

        self::$items[$namespace] = $data;
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        $segments = explode('.', $key);
        $value = self::$items;

        foreach ($segments as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return $default;
            }
            $value = $value[$segment];
        }

        return $value;
    }
}
