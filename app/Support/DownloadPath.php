<?php
/**
 * TechHub - 高校技术团队展示与管理平台
 *
 * 文件：app/Support/DownloadPath.php
 * 模块：文件安全
 * 职责：校验数据库文件路径，拒绝绝对路径、目录遍历和越出允许目录的文件。
 * 作者：待团队填写
 * 创建日期：2026-11-25
 * 最后修改：2026-11-25
 */

declare(strict_types=1);

namespace App\Support;

final class DownloadPath
{
    public static function resolve(string $projectRoot, string $relativePath, string $allowedSubdirectory): ?string
    {
        $projectRoot = rtrim(str_replace('\\', '/', $projectRoot), '/');
        $relativePath = trim(str_replace('\\', '/', $relativePath));
        $allowedSubdirectory = trim(str_replace('\\', '/', $allowedSubdirectory), '/');

        if (
            $relativePath === ''
            || $allowedSubdirectory === ''
            || str_contains($relativePath, "\0")
            || str_starts_with($relativePath, '/')
            || preg_match('/^[A-Za-z]:/', $relativePath)
            || preg_match('#(^|/)\.\.(/|$)#', $relativePath)
        ) {
            return null;
        }

        $root = realpath($projectRoot . '/' . $allowedSubdirectory);
        $file = realpath($projectRoot . '/' . $relativePath);

        if ($root === false || $file === false || !is_file($file) || !is_readable($file)) {
            return null;
        }

        $rootPrefix = rtrim(str_replace('\\', '/', $root), '/') . '/';
        $normalizedFile = str_replace('\\', '/', $file);

        return str_starts_with($normalizedFile, $rootPrefix) ? $file : null;
    }
}
