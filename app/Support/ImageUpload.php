<?php
/**
 * TechHub - 高校技术团队展示与管理平台
 *
 * 文件：app/Support/ImageUpload.php
 * 模块：后台上传支持
 * 职责：校验并保存后台图片到 public/uploads，限制只删除受管上传文件。
 * 作者：待团队填写
 * 创建日期：2026-11-25
 * 最后修改：2026-11-25
 */

declare(strict_types=1);

namespace App\Support;

use Core\Upload;

final class ImageUpload
{
    /**
     * @param array<string, mixed>|null $file
     * @return string|null 相对 public 的路径，例如 uploads/avatars/xxx.png；无文件返回 null
     */
    public static function store(?array $file, string $subdirectory, int $maxSize): ?string
    {
        if ($file === null || (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return null;
        }

        $subdirectory = trim(str_replace('\\', '/', $subdirectory), '/');
        if (preg_match('/^[a-z0-9-]+$/', $subdirectory) !== 1) {
            throw new \RuntimeException('上传目录不合法。');
        }

        $extensions = (array) config('app.uploads.image_extensions', ['jpg', 'jpeg', 'png', 'webp', 'gif']);
        $target = BASE_PATH . '/public/uploads/' . $subdirectory;
        $stored = Upload::store($file, $target, array_values(array_map('strval', $extensions)), $maxSize);

        return 'uploads/' . $subdirectory . '/' . $stored['stored_name'];
    }

    /**
     * 只删除 public/uploads 下由后台上传的图片，绝不删除示例 assets。
     */
    public static function removeManaged(?string $relativePath): void
    {
        if ($relativePath === null || !str_starts_with($relativePath, 'uploads/')) {
            return;
        }

        $candidate = BASE_PATH . '/public/' . ltrim($relativePath, '/');
        $base = realpath(BASE_PATH . '/public/uploads');
        $resolved = realpath($candidate);
        if ($base === false || $resolved === false) {
            return;
        }

        $base = rtrim(str_replace('\\', '/', $base), '/');
        $resolvedNormalized = str_replace('\\', '/', $resolved);
        if (!str_starts_with($resolvedNormalized, $base . '/')) {
            return;
        }

        if (is_file($resolved)) {
            @unlink($resolved);
        }
    }
}
