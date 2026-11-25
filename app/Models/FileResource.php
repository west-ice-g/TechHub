<?php
/**
 * TechHub - 高校技术团队展示与管理平台
 *
 * 文件：app/Models/FileResource.php
 * 模块：文件资源管理
 * 职责：查询公开作业文件、文件详情并维护下载计数。
 * 作者：待团队填写
 * 创建日期：2026-11-25
 * 最后修改：2026-11-25
 */

declare(strict_types=1);

namespace App\Models;

use Core\Model;

final class FileResource extends Model
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function publicPageByCategory(string $category, int $limit, int $offset): array
    {
        $limit = max(1, min(50, $limit));
        $offset = max(0, $offset);

        return $this->fetchAll(
            "SELECT id, title, original_name, file_path, extension, mime_type, size_bytes,
                    category, description, download_count, created_at
             FROM files
             WHERE category = :category
               AND is_public = 1
               AND status = 'active'
             ORDER BY created_at DESC, id DESC
             LIMIT {$limit} OFFSET {$offset}",
            ['category' => $category]
        );
    }

    public function countPublicByCategory(string $category): int
    {
        return (int) $this->fetchValue(
            "SELECT COUNT(*)
             FROM files
             WHERE category = :category AND is_public = 1 AND status = 'active'",
            ['category' => $category]
        );
    }

    public function countPublic(): int
    {
        return (int) $this->fetchValue(
            "SELECT COUNT(*) FROM files WHERE is_public = 1 AND status = 'active'"
        );
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findPublicById(int $id): ?array
    {
        return $this->fetchOne(
            "SELECT id, title, original_name, file_path, extension, mime_type, size_bytes,
                    category, description, download_count, is_public, status, created_at
             FROM files
             WHERE id = :id AND is_public = 1 AND status = 'active'
             LIMIT 1",
            ['id' => $id]
        );
    }

    public function incrementDownload(int $id): void
    {
        $this->execute(
            'UPDATE files SET download_count = download_count + 1 WHERE id = :id',
            ['id' => $id]
        );
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function adminPage(
        int $limit,
        int $offset,
        string $category = '',
        string $status = '',
        string $query = ''
    ): array {
        $limit = max(1, min(100, $limit));
        $offset = max(0, $offset);
        [$where, $params] = $this->adminFilter($category, $status, $query);

        return $this->fetchAll(
            "SELECT f.id, f.uploader_id, f.related_member_id, f.title, f.original_name, f.stored_name,
                    f.file_path, f.extension, f.mime_type, f.size_bytes, f.category, f.description,
                    f.download_count, f.is_public, f.status, f.created_at, f.updated_at,
                    u.display_name AS uploader_name,
                    tm.id AS member_id, mu.display_name AS member_name
             FROM files f
             INNER JOIN users u ON u.id = f.uploader_id
             LEFT JOIN team_members tm ON tm.id = f.related_member_id
             LEFT JOIN users mu ON mu.id = tm.user_id
             WHERE 1 = 1 {$where}
             ORDER BY f.id DESC
             LIMIT {$limit} OFFSET {$offset}",
            $params
        );
    }

    public function countAdmin(string $category = '', string $status = '', string $query = ''): int
    {
        [$where, $params] = $this->adminFilter($category, $status, $query);

        return (int) $this->fetchValue(
            "SELECT COUNT(*)
             FROM files f
             INNER JOIN users u ON u.id = f.uploader_id
             LEFT JOIN team_members tm ON tm.id = f.related_member_id
             LEFT JOIN users mu ON mu.id = tm.user_id
             WHERE 1 = 1 {$where}",
            $params
        );
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findAdmin(int $id): ?array
    {
        return $this->fetchOne(
            'SELECT f.*, u.display_name AS uploader_name,
                    mu.display_name AS member_name
             FROM files f
             INNER JOIN users u ON u.id = f.uploader_id
             LEFT JOIN team_members tm ON tm.id = f.related_member_id
             LEFT JOIN users mu ON mu.id = tm.user_id
             WHERE f.id = :id
             LIMIT 1',
            ['id' => $id]
        );
    }

    /**
     * @param array<string, mixed> $data
     */
    public function createFile(array $data): int
    {
        return $this->insert(
            'INSERT INTO files
                (uploader_id, related_member_id, title, original_name, stored_name, file_path,
                 extension, mime_type, size_bytes, category, description, is_public, status)
             VALUES
                (:uploader_id, :related_member_id, :title, :original_name, :stored_name, :file_path,
                 :extension, :mime_type, :size_bytes, :category, :description, :is_public, :status)',
            [
                'uploader_id' => $data['uploader_id'],
                'related_member_id' => $data['related_member_id'],
                'title' => $data['title'],
                'original_name' => $data['original_name'],
                'stored_name' => $data['stored_name'],
                'file_path' => $data['file_path'],
                'extension' => $data['extension'],
                'mime_type' => $data['mime_type'],
                'size_bytes' => $data['size_bytes'],
                'category' => $data['category'],
                'description' => $data['description'],
                'is_public' => $data['is_public'],
                'status' => $data['status'],
            ]
        );
    }

    /**
     * @param array<string, mixed> $data
     */
    public function updateFile(int $id, array $data): void
    {
        $this->execute(
            'UPDATE files
             SET title = :title, description = :description, related_member_id = :related_member_id,
                 is_public = :is_public, status = :status
             WHERE id = :id',
            [
                'id' => $id,
                'title' => $data['title'],
                'description' => $data['description'],
                'related_member_id' => $data['related_member_id'],
                'is_public' => $data['is_public'],
                'status' => $data['status'],
            ]
        );
    }

    public function setStatus(int $id, string $status): void
    {
        $this->execute(
            'UPDATE files SET status = :status WHERE id = :id',
            ['id' => $id, 'status' => $status]
        );
    }

    public function setPublic(int $id, bool $public): void
    {
        $this->execute(
            'UPDATE files SET is_public = :public WHERE id = :id',
            ['id' => $id, 'public' => $public ? 1 : 0]
        );
    }

    public function deleteFile(int $id): void
    {
        $this->execute('DELETE FROM files WHERE id = :id', ['id' => $id]);
    }

    /**
     * @return array<string, int>
     */
    public function categoryCounts(): array
    {
        $rows = $this->fetchAll('SELECT category, COUNT(*) AS total FROM files GROUP BY category');
        $counts = ['team_assignment' => 0, 'personal_assignment' => 0, 'resource' => 0];
        foreach ($rows as $row) {
            $category = (string) $row['category'];
            if (array_key_exists($category, $counts)) {
                $counts[$category] = (int) $row['total'];
            }
        }

        return $counts;
    }

    /**
     * @return array{0: string, 1: array<string, string>}
     */
    private function adminFilter(string $category, string $status, string $query): array
    {
        $where = '';
        $params = [];

        if (in_array($category, ['team_assignment', 'personal_assignment', 'resource'], true)) {
            $where .= ' AND f.category = :category';
            $params['category'] = $category;
        }

        if (in_array($status, ['active', 'hidden'], true)) {
            $where .= ' AND f.status = :status';
            $params['status'] = $status;
        }

        $query = trim($query);
        if ($query !== '') {
            $like = '%' . $query . '%';
            $where .= ' AND (f.title LIKE :q_title OR f.original_name LIKE :q_name OR f.description LIKE :q_description OR f.stored_name LIKE :q_stored)';
            $params['q_title'] = $like;
            $params['q_name'] = $like;
            $params['q_description'] = $like;
            $params['q_stored'] = $like;
        }

        return [$where, $params];
    }
}
