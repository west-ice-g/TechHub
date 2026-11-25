<?php
/**
 * TechHub - 高校技术团队展示与管理平台
 *
 * 文件：core/Csrf.php
 * 模块：安全
 * 职责：生成、渲染和校验 CSRF Token。
 * 作者：待团队填写
 * 创建日期：2026-11-25
 * 最后修改：2026-11-25
 */

declare(strict_types=1);

namespace Core;

final class Csrf
{
    private const SESSION_KEY = '_csrf_token';

    public static function token(): string
    {
        $token = Session::get(self::SESSION_KEY);
        if (!is_string($token) || strlen($token) < 32) {
            $token = bin2hex(random_bytes(32));
            Session::put(self::SESSION_KEY, $token);
        }

        return $token;
    }

    public static function field(): string
    {
        return '<input type="hidden" name="_token" value="' . e(self::token()) . '">';
    }

    public static function validate(?string $token): bool
    {
        $stored = Session::get(self::SESSION_KEY);
        if (!is_string($stored) || $stored === '' || !is_string($token) || $token === '') {
            return false;
        }

        return hash_equals($stored, $token);
    }

    public static function rotate(): string
    {
        $token = bin2hex(random_bytes(32));
        Session::put(self::SESSION_KEY, $token);

        return $token;
    }
}
