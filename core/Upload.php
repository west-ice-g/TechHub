<?php
/**
 * TechHub - 高校技术团队展示与管理平台
 *
 * 文件：core/Upload.php
 * 模块：安全
 * 职责：对上传文件执行扩展名、MIME、大小和错误码校验，并使用随机文件名保存。
 * 作者：待团队填写
 * 创建日期：2026-11-25
 * 最后修改：2026-11-25
 */

declare(strict_types=1);

namespace Core;

final class Upload
{
    /**
     * 扩展名到允许 MIME 的映射。SVG 不列入，避免脚本型图片。
     *
     * @var array<string, array<int, string>>
     */
    private const MIME_MAP = [
        'pdf' => ['application/pdf'],
        'doc' => ['application/msword'],
        'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
        'ppt' => ['application/vnd.ms-powerpoint'],
        'pptx' => ['application/vnd.openxmlformats-officedocument.presentationml.presentation'],
        'zip' => ['application/zip', 'application/x-zip-compressed'],
        'txt' => ['text/plain'],
        'jpg' => ['image/jpeg'],
        'jpeg' => ['image/jpeg'],
        'png' => ['image/png'],
        'webp' => ['image/webp'],
        'gif' => ['image/gif'],
    ];

    /**
     * 校验 $_FILES 结构；实际 Web 上传必须传 true。
     *
     * @param array<string, mixed> $file
     * @param array<int, string> $allowedExtensions
     * @return array{original_name: string, extension: string, mime_type: string, size: int, tmp_name: string}
     */
    public static function inspect(
        array $file,
        array $allowedExtensions,
        int $maxSize,
        bool $requireHttpUpload = true
    ): array {
        $error = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($error !== UPLOAD_ERR_OK) {
            throw new \RuntimeException(self::errorMessage($error));
        }

        $tmpName = (string) ($file['tmp_name'] ?? '');
        $originalName = trim((string) ($file['name'] ?? ''));
        $size = (int) ($file['size'] ?? 0);

        if ($tmpName === '' || !is_file($tmpName)) {
            throw new \RuntimeException('上传文件不存在。');
        }

        if ($requireHttpUpload && !is_uploaded_file($tmpName)) {
            throw new \RuntimeException('非法的上传来源。');
        }

        if ($size <= 0) {
            throw new \RuntimeException('不能上传空文件。');
        }

        if ($size > $maxSize) {
            throw new \RuntimeException('文件超过允许的大小限制。');
        }

        $originalName = basename(str_replace('\\', '/', $originalName));
        if ($originalName === '' || mb_strlen($originalName) > 255) {
            throw new \RuntimeException('文件名不合法。');
        }

        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        $allowedExtensions = array_values(array_map('strtolower', $allowedExtensions));
        if ($extension === '' || !in_array($extension, $allowedExtensions, true)) {
            throw new \RuntimeException('不允许的文件扩展名。');
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($tmpName);
        if (!is_string($mime) || $mime === '') {
            throw new \RuntimeException('无法识别文件类型。');
        }

        $allowedMimes = self::MIME_MAP[$extension] ?? [];
        if (!in_array($mime, $allowedMimes, true)) {
            throw new \RuntimeException('文件 MIME 类型与扩展名不匹配。');
        }

        if (in_array($extension, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true)) {
            if (@getimagesize($tmpName) === false) {
                throw new \RuntimeException('图片文件内容无效。');
            }
        }

        return [
            'original_name' => $originalName,
            'extension' => $extension,
            'mime_type' => $mime,
            'size' => $size,
            'tmp_name' => $tmpName,
        ];
    }

    /**
     * 将已校验的上传文件保存到目标目录，返回随机的存储文件名。
     *
     * @param array<string, mixed> $file
     * @param array<int, string> $allowedExtensions
     * @return array{original_name: string, stored_name: string, file_path: string, extension: string, mime_type: string, size: int}
     */
    public static function store(
        array $file,
        string $targetDirectory,
        array $allowedExtensions,
        int $maxSize
    ): array {
        $info = self::inspect($file, $allowedExtensions, $maxSize, true);

        if (!is_dir($targetDirectory) && !mkdir($targetDirectory, 0775, true) && !is_dir($targetDirectory)) {
            throw new \RuntimeException('上传目录不可用。');
        }

        $storedName = bin2hex(random_bytes(16)) . '.' . $info['extension'];
        $targetPath = rtrim($targetDirectory, '/\\') . DIRECTORY_SEPARATOR . $storedName;

        if (!move_uploaded_file($info['tmp_name'], $targetPath)) {
            throw new \RuntimeException('文件保存失败。');
        }

        @chmod($targetPath, 0644);

        return [
            'original_name' => $info['original_name'],
            'stored_name' => $storedName,
            'file_path' => $targetPath,
            'extension' => $info['extension'],
            'mime_type' => $info['mime_type'],
            'size' => $info['size'],
        ];
    }

    private static function errorMessage(int $error): string
    {
        return match ($error) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => '文件超过允许的大小限制。',
            UPLOAD_ERR_PARTIAL => '文件仅上传了一部分，请重试。',
            UPLOAD_ERR_NO_FILE => '请选择要上传的文件。',
            UPLOAD_ERR_NO_TMP_DIR => '服务器缺少临时目录。',
            UPLOAD_ERR_CANT_WRITE => '服务器无法写入文件。',
            UPLOAD_ERR_EXTENSION => '上传被服务器扩展拦截。',
            default => '文件上传失败。',
        };
    }
}
