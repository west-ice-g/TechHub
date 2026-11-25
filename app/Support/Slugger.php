<?php
/**
 * TechHub - 高校技术团队展示与管理平台
 *
 * 文件：app/Support/Slugger.php
 * 模块：后台表单支持
 * 职责：生成和校验只含小写字母、数字和连字符的 URL 别名。
 * 作者：待团队填写
 * 创建日期：2026-11-25
 * 最后修改：2026-11-25
 */

declare(strict_types=1);

namespace App\Support;

final class Slugger
{
    public static function normalize(string $value): string
    {
        $value = strtolower(trim($value));
        $value = (string) preg_replace('/[^a-z0-9]+/', '-', $value);
        $value = trim($value, '-');

        return mb_substr($value, 0, 160);
    }

    public static function fromTitle(string $title, string $fallbackPrefix = 'item'): string
    {
        $slug = self::normalize($title);

        return $slug !== '' ? $slug : $fallbackPrefix . '-' . date('YmdHis');
    }

    public static function isValid(string $value): bool
    {
        return $value !== ''
            && strlen($value) <= 160
            && preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $value) === 1;
    }
}
