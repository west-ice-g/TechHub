<?php
/**
 * TechHub - 高校技术团队展示与管理平台
 *
 * 文件：app/Models/FileDownload.php
 * 模块：文件资源管理
 * 职责：记录真实文件下载流水，供统计与安全排查。
 * 作者：待团队填写
 * 创建日期：2026-11-25
 * 最后修改：2026-11-25
 */

declare(strict_types=1);

namespace App\Models;

use Core\Model;

final class FileDownload extends Model
{
    public function record(int $fileId, ?int $userId, ?string $ipAddress, ?string $userAgent): void
    {
        $this->execute(
            'INSERT INTO file_downloads (file_id, user_id, ip_address, user_agent)
             VALUES (:file_id, :user_id, :ip_address, :user_agent)',
            [
                'file_id' => $fileId,
                'user_id' => $userId,
                'ip_address' => $ipAddress !== null ? mb_substr($ipAddress, 0, 45) : null,
                'user_agent' => $userAgent !== null ? mb_substr($userAgent, 0, 500) : null,
            ]
        );
    }

    public function countAll(): int
    {
        return (int) $this->fetchValue('SELECT COUNT(*) FROM file_downloads');
    }
}
